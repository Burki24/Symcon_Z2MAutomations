<?php

declare(strict_types=1);

namespace Z2MAutomations;

/** A bounded native set command; all properties keep their individual action binding. */
trait EditorCommands
{
    public function ChangeEditorCommand(string $List, string $Device, string $Choice, string $Property, string $Value, bool $Remove = false): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($List, $Device, $Choice, $Property, $Value, $Remove): bool
        {
            $context = $this->EditorItemContext($List);
            if ($context === null || $context['side'] !== 'Target' || !$this->CheckEditorArea($context['area'])) {
                $this->DebugEvent('FEHLER', 'Gemeinsamer Gerätebefehl abgewiesen; keine bearbeitbare Aktionsliste im aktuellen Bereich.');
                return false;
            }
            $snapshot = $this->EditorSnapshot();
            $parts = explode(':', $Choice);
            $values = $Choice === '' ? [] : (count($parts) === 2 && $parts[0] === $Device ? $this->DecodeCommand($snapshot, $Device, $parts[1]) : null);
            $meta = $this->EditorItemProperty($snapshot, $Device, $Property, 'Target');
            $valid = $values !== null && $meta !== null && !isset($meta['sceneEndpoint']) && strlen($Value) <= 4096;
            if ($valid && $Remove) {
                $valid = array_key_exists($meta['property'], $values);
                unset($values[$meta['property']]);
            } elseif ($valid) {
                $input = json_decode($Value, true, 8);
                if ($meta['type'] === 'numeric') {
                    $value = $input;
                } elseif ($meta['type'] === 'color') {
                    $value = $this->EditorColorValue($input);
                } else {
                    $candidate = is_string($input) ? $this->FindEditorChoice($snapshot, $Device, $input, 'Target') : null;
                    $valid = ($candidate['property'] ?? '') === $meta['property'];
                    $value = $candidate['value'] ?? null;
                }
                $values[$meta['property']] = $value;
            }
            $key = $valid && $values !== [] ? $this->CommandChoice($snapshot, $Device, $values) : null;
            $valid = $valid && ($key !== null || ($Remove && $values === []));
            $this->UpdateEditorFormField('RowCommandHint', 'caption', $this->Translate($valid ? 'Command updated locally. Only the listed properties are included.' : 'Property not applied. Choose a permitted value; use up to 8 properties and either color or color temperature per channel.'));
            $binding = $meta === null ? null : $this->EditorActionVariable($snapshot, $Device, $meta['property']);
            $this->DebugEvent($valid ? 'AKTION' : 'FEHLER', $valid ? 'Eigenschaften des Aktionsschritts lokal geändert; kein Gerätebefehl gesendet.' : 'Eigenschaft nicht übernommen; Schaltrecht, Wert oder gemeinsame Befehlsgrenze ungültig.', ['Entfernen' => $Remove, 'Eigenschaften' => count($values ?? []), 'Schaltvariable' => $binding['id'] ?? 0]);
            if (!$valid) {
                return false;
            }
            $encoded = $key === null ? '' : $Device . ':' . $key;
            $this->UpdateEditorFormField('Choice', 'value', $encoded);
            $this->UpdateCommandSummary($snapshot, $Device, $values);
            $this->RememberEditorListChoices('Target', $encoded === '' ? [] : [['Choice' => $encoded]], 'pending');
            $this->UpdateEditorField($List, 'columns', json_encode($this->EditorActionColumns($snapshot, [], $List !== 'SequenceEditorActions'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            return true;
        });
    }
    private function EditorColorValue(mixed $value): ?array
    {
        return is_int($value) && $value >= 0 && $value <= 0xFFFFFF ? ['hex' => sprintf('#%06X', $value)] : null;
    }

    private function CommandChoice(array $snapshot, string $device, array $values): ?string
    {
        if ($values === [] || array_is_list($values) || count($values) > 8) {
            return null;
        }
        foreach ($values as $property => $value) {
            $meta = $this->EditorProperties($snapshot, $device, 'Target')[$property] ?? null;
            if ($meta === null || isset($meta['sceneEndpoint']) || !$this->EditorValueAllowed($snapshot, $device, $property, $value, 'Target')
                || ($meta['type'] === 'binary' && $value === 'TOGGLE')) {
                return null;
            }
            if ($meta['type'] === 'color' && preg_match('/\Acolor(_[A-Za-z0-9_]+)?\z/D', $property, $parts) && array_key_exists('color_temp' . ($parts[1] ?? ''), $values)) {
                return null;
            }
        }
        ksort($values, SORT_STRING);
        $json = json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        return strlen($json) <= 1200 ? 'command.' . rtrim(strtr(base64_encode($json), '+/', '-_'), '=') : null;
    }

    private function DecodeCommand(array $snapshot, string $device, string $key): ?array
    {
        if (!str_starts_with($key, 'command.') || strlen($key) > 1610) {
            return null;
        }
        $raw = base64_decode(strtr(substr($key, 8), '-_', '+/'), true);
        $values = $raw === false ? null : json_decode($raw, true, 8);
        return is_array($values) && $this->CommandChoice($snapshot, $device, $values) === $key ? $values : null;
    }

    private function CommandSummary(array $snapshot, string $device, array $values): array
    {
        $rows = [];
        foreach ($values as $property => $value) {
            $rows[] = ['Property' => $this->EditorPropertyLabel($snapshot, $device, $property, 'Target'), 'Value' => $this->EditorValueLabel($value)];
        }
        return $rows;
    }

    private function UpdateCommandSummary(array $snapshot, string $device, array $values): void
    {
        $this->UpdateEditorFormField('RowCommandSummary', 'values', json_encode($this->CommandSummary($snapshot, $device, $values), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
