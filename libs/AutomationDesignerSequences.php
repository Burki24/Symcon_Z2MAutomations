<?php

declare(strict_types=1);

namespace Z2MAutomations;

use JsonException;

trait AutomationDesignerSequences
{
    private function DesignerSequenceActionsAllowed(mixed $actions): bool
    {
        return is_array($actions) && array_filter($actions, static fn (mixed $a): bool => !is_array($a) || ($a['type'] ?? '') === 'sequence') === []
            && $this->DesignerEditSectionError('actions', $actions) === '';
    }

    private function DesignerSequenceSections(?array $configuration): array
    {
        $result = [];
        foreach ($configuration['sequences'] ?? [] as $sequence) {
            $result[$sequence['id']] = $this->DesignerSequenceActionsAllowed($sequence['actions']);
        }
        return $result;
    }

    private function DesignerSequenceRequest(): array
    {
        $failure = $this->DesignerWriteHeaders();
        if ($failure !== null) return $failure;
        $token = $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '';
        return $this->WithTransportLock(function () use ($token): array
        {
            if (!$this->DesignerWriteAccess($token, 'sequences')) {
                return $this->DesignerCreateFailure(403, 'Der Zugang für Aktionsfolgen fehlt oder ist abgelaufen. Bitte den Designer erneut im Modul öffnen.');
            }
            $text = $this->DesignerRequestBody();
            if (strlen($text) > 65536) return $this->DesignerCreateFailure(413, 'Die Aktionsfolge ist zu umfangreich. Bitte höchstens 16 Schritte eintragen.');
            try {
                $request = json_decode($text, true, 16, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return $this->DesignerCreateFailure(400, 'Die Aktionsfolge konnte nicht gelesen werden. Ihre Eingaben bleiben im Dialog.');
            }
            if (!$this->DesignerEditShape($request, ['requestId', 'context', 'sequenceId', 'changes'])
                || !is_string($request['requestId']) || !RuntimeDebugContract::UUID($request['requestId']) || !is_string($request['context']) || !is_string($request['sequenceId'])
                || !is_array($request['changes']) || array_diff(array_keys($request['changes']), ['name', 'actions']) !== []) {
                return $this->DesignerCreateFailure(400, 'Die Angaben zur Aktionsfolge sind unvollständig oder nicht unterstützt.');
            }
            $sessions = $this->DesignerSessions();
            $sessionIndex = array_search(hash('sha256', $token), array_column($sessions, 'hash'), true);
            if ($sessionIndex === false) return $this->DesignerCreateFailure(403, 'Der Zugang ist inzwischen abgelaufen. Bitte den Designer erneut im Modul öffnen.');
            $digest = hash('sha256', json_encode([$request['sequenceId'], $request['changes']], JSON_THROW_ON_ERROR));
            foreach ($sessions[$sessionIndex]['receipts'] ?? [] as $receipt) {
                if ($receipt['request'] === $request['requestId']) {
                    if (($receipt['kind'] ?? '') !== 'sequence' || $receipt['digest'] !== $digest) {
                        return $this->DesignerCreateFailure(409, 'Dieser Auftrag wurde bereits mit anderen Angaben verwendet. Bitte die Ansicht aktualisieren.');
                    }
                    $this->DebugEvent('AKTION', 'Wiederholte Browser-Aktionsfolge bestätigt; keine zweite Folge oder erneute Änderung angelegt.');
                    return [200, ['sequenceId' => $receipt['sequence'], 'message' => 'Die Aktionsfolge wurde bereits im lokalen Entwurf übernommen.']];
                }
            }
            if (!hash_equals($this->DesignerCreateContext(), $request['context'])) {
                return $this->DesignerCreateFailure(409, 'Entwurf, Auswahl oder Gerätedaten wurden inzwischen geändert. Ihre Eingaben bleiben erhalten. Bitte die Ansicht neu laden und die Änderung prüfen.');
            }
            $block = $this->DesignerCreateBlock(false);
            if ($block !== '') return $this->DesignerCreateFailure(409, $block);
            $draft = $this->ReadEditorDraft();
            $definitions = $draft['configuration']['sequences'] ?? [];
            $id = $request['sequenceId'];
            $new = $id === '';
            $index = array_search($id, array_column($definitions, 'id'), true);
            if (!$new && $index === false) return $this->DesignerCreateFailure(409, 'Diese Aktionsfolge ist nicht mehr im Entwurf vorhanden. Bitte die Ansicht aktualisieren.');
            if ($new && count($definitions) >= 32) return $this->DesignerCreateFailure(422, 'Der Entwurf enthält bereits 32 Aktionsfolgen. Vorhandene Folgen können Sie weiterhin bearbeiten.');
            if ($new && array_diff(['name', 'actions'], array_keys($request['changes'])) !== []) return $this->DesignerCreateFailure(422, 'Bitte einen Namen und mindestens einen Aktionsschritt eintragen.');
            $original = $new ? null : $definitions[$index];
            $sequence = $original ?? ['id' => 'sequence_' . bin2hex(random_bytes(8)), 'name' => '', 'actions' => []];
            foreach ($request['changes'] as $key => $value) {
                if ($key === 'name') {
                    if (!is_string($value) || !$this->ValidEditorName(trim($value))) return $this->DesignerCreateFailure(422, 'Bitte einen Namen mit 1 bis 120 Zeichen ohne Steuerzeichen eingeben.');
                    $value = trim($value);
                } elseif ($value !== ($original['actions'] ?? null)) {
                    if (!$new && !$this->DesignerSequenceActionsAllowed($original['actions'])) return $this->DesignerCreateFailure(422, 'Die vorhandenen Schritte bleiben geschützt erhalten. Bitte fehlende Gerätedaten oder Schaltrechte zuerst im nativen Formular prüfen.');
                    if (!$this->DesignerSequenceActionsAllowed($value)) {
                        $actionError = $this->DesignerEditSectionError('actions', $value);
                        return $this->DesignerCreateFailure(422, $actionError !== '' ? 'Aktionsfolge: ' . $actionError : 'Eine Aktionsfolge darf keine weitere Aktionsfolge aufrufen. Bitte stattdessen Geräte- oder Szenenaktionen wählen.');
                    }
                }
                $sequence[$key] = $value;
            }
            $changed = $sequence !== $original;
            $definitions[$new ? count($definitions) : $index] = $sequence;
            $draft['configuration']['sequences'] = $definitions;
            $planError = SequencePlan::Error($draft['configuration']);
            if ($planError !== '') {
                return $this->DesignerCreateFailure(422, match ($planError) {
                    'sequence_limit' => 'Durch diese Änderung hätte eine verwendende Automation mehr als 16 ausgeführte Schritte. Bitte die Folge oder die Automation kürzen.',
                    'sequence_delay' => 'Die Wartezeit vor einem Folgenaufruf und die erste Wartezeit der Folge ergeben zusammen mehr als 24 Stunden. Bitte eine dieser Wartezeiten kürzen.',
                    default          => 'Die Aktionsfolge passt nicht zu allen verwendenden Automationen. Bitte Schrittliste und Wartezeiten prüfen.'
                });
            }
            $state = $this->RefreshState();
            if ($state['scope'] !== $draft['scope'] || !$this->DesignerWriteAccess($token, 'sequences')) return $this->DesignerCreateFailure(409, 'Die Verbindung oder der Zugang wurde geändert. Bitte den Designer erneut im Modul öffnen.');
            if ($changed && !$this->CommitEditorChange($state, $draft, $new ? 'sequence_created' : 'sequence_updated', $this->EditorSelection()['id'] ?? '')) return $this->DesignerCreateFailure(422, 'Die Aktionsfolge konnte nicht übernommen werden. Bitte Entwurfsgröße und Schrittliste prüfen.');
            $sessions[$sessionIndex]['receipts'][] = ['request' => $request['requestId'], 'kind' => 'sequence', 'digest' => $digest, 'sequence' => $sequence['id']];
            $sessions[$sessionIndex]['receipts'] = array_slice($sessions[$sessionIndex]['receipts'], -8);
            $this->SetBuffer('DesignerSessions', json_encode($sessions, JSON_THROW_ON_ERROR));
            $this->DebugEvent(
                'AKTION',
                $changed ? 'Browser-Aktionsfolge im lokalen Entwurf übernommen. Automationsregeln und Partnerfreigaben bleiben erhalten; Z2M wurde nicht verändert.'
                : 'Browser-Aktionsfolge ohne Änderung bestätigt; Entwurf und Z2M bleiben unverändert.',
                ['Neu' => $new, 'Schritte' => count($sequence['actions']), 'Verwendungen' => $this->SequenceUsage($sequence['id'], $draft)]
            );
            return [200, ['sequenceId' => $sequence['id'], 'message' => 'Aktionsfolge im lokalen Symcon-Entwurf übernommen. Z2M bleibt unverändert.']];
        });
    }
}
