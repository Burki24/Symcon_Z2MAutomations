<?php

declare(strict_types=1);

namespace Z2MAutomations;

use DateTimeZone;
use JsonException;

/** Constrained local rule updates; uses the same rights and commit path as the native editor. */
trait AutomationDesignerEditing
{
    private function DesignerEditableSections(?array $configuration): array
    {
        $result = [];
        foreach ($configuration['automations'] ?? [] as $rule) {
            foreach (['triggers', 'conditions', 'actions'] as $section) {
                $result[$rule['id']][$section] = $this->DesignerEditSectionError($section, $rule[$section]) === '';
            }
        }
        return $result;
    }

    private function DesignerWriteAccess(mixed $token, string $permission): bool
    {
        if (!$this->DesignerTokenAllowed($token)) {
            return false;
        }
        foreach ($this->DesignerSessions() as $session) {
            if (hash_equals($session['hash'], hash('sha256', $token)) && ($session[$permission] ?? false)) {
                return true;
            }
        }
        return false;
    }

    private function DesignerEditRequest(): array
    {
        $failure = $this->DesignerWriteHeaders();
        if ($failure !== null) {
            return $failure;
        }
        $token = $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '';
        return $this->WithTransportLock(function () use ($token): array
        {
            if (!$this->DesignerWriteAccess($token, 'edit')) {
                return $this->DesignerCreateFailure(403, 'Der Bearbeitungszugang fehlt oder ist abgelaufen. Bitte den Designer erneut im Modul öffnen.');
            }
            $text = $this->DesignerRequestBody();
            if (strlen($text) > 65536) {
                return $this->DesignerCreateFailure(413, 'Die Änderung ist zu umfangreich. Bitte weniger Angaben auf einmal bearbeiten.');
            }
            try {
                $request = json_decode($text, true, 16, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return $this->DesignerCreateFailure(400, 'Die Änderung konnte nicht gelesen werden. Ihre Eingaben bleiben im Dialog.');
            }
            if (!$this->DesignerEditShape($request, ['requestId', 'context', 'ruleId', 'changes'])
                || !is_string($request['requestId']) || !RuntimeDebugContract::UUID($request['requestId'])
                || !is_string($request['context']) || !is_string($request['ruleId'])
                || !is_array($request['changes']) || array_diff(array_keys($request['changes']), ['name', 'enabled', 'retrigger', 'restart_policy', 'triggers', 'conditions', 'actions']) !== []) {
                return $this->DesignerCreateFailure(400, 'Die Angaben zur Bearbeitung sind unvollständig oder nicht unterstützt.');
            }
            $sessions = $this->DesignerSessions();
            $sessionIndex = array_search(hash('sha256', $token), array_column($sessions, 'hash'), true);
            if ($sessionIndex === false) {
                return $this->DesignerCreateFailure(403, 'Der Bearbeitungszugang ist inzwischen abgelaufen. Bitte den Designer erneut im Modul öffnen.');
            }
            $digest = hash('sha256', json_encode([$request['ruleId'], $request['changes']], JSON_THROW_ON_ERROR));
            foreach ($sessions[$sessionIndex]['receipts'] ?? [] as $receipt) {
                if ($receipt['request'] === $request['requestId']) {
                    if (($receipt['kind'] ?? 'create') !== 'edit' || $receipt['digest'] !== $digest) {
                        return $this->DesignerCreateFailure(409, 'Dieser Auftrag wurde bereits mit anderen Angaben verwendet. Bitte die Ansicht aktualisieren.');
                    }
                    $this->DebugEvent('AKTION', 'Wiederholte Browserbearbeitung bestätigt; Änderung nicht erneut angewendet.');
                    return [200, ['ruleId' => $receipt['rule'], 'message' => 'Die Änderung wurde bereits im lokalen Entwurf übernommen.']];
                }
            }
            if (!hash_equals($this->DesignerCreateContext(), $request['context'])) {
                return $this->DesignerCreateFailure(409, 'Der Entwurf oder die Gerätedaten wurden inzwischen geändert. Ihre Eingaben bleiben erhalten. Bitte die Ansicht aktualisieren und die Änderung prüfen.');
            }
            $block = $this->DesignerCreateBlock(false);
            if ($block !== '') {
                return $this->DesignerCreateFailure(409, $block);
            }
            $draft = $this->ReadEditorDraft();
            $index = $this->EditorRuleIndex($draft, $request['ruleId']);
            if ($index === null) {
                return $this->DesignerCreateFailure(409, 'Diese Automation ist nicht mehr im Entwurf vorhanden. Bitte die Ansicht aktualisieren.');
            }
            $original = $draft['configuration']['automations'][$index];
            $error = '';
            $fields = $this->DesignerRuleFields($request['changes'], $original, $error);
            if ($fields === null) return $this->DesignerCreateFailure(422, $error);
            $rule = array_replace($original, $fields);
            $changed = $rule !== $original;
            $state = $this->RefreshState();
            if ($state['scope'] !== $draft['scope'] || !$this->DesignerWriteAccess($token, 'edit')) {
                return $this->DesignerCreateFailure(409, 'Die Verbindung oder der Zugang wurde geändert. Bitte den Designer erneut im Modul öffnen.');
            }
            if ($changed) {
                $draft['configuration']['automations'][$index] = $rule;
                if (!$this->CommitEditorChange($state, $draft, 'draft_updated', $rule['id'])) {
                    return $this->DesignerCreateFailure(422, 'Die Änderung konnte nicht übernommen werden. Bitte Länge der Aktionsfolge und Entwurfsgröße prüfen.');
                }
            }
            if ($changed && array_key_exists('actions', $fields)) $this->DesignerActionOrderDebug($original['actions'], $rule['actions']);
            if (array_key_exists('restart_policy', $fields)) $this->DebugRestartPolicy($rule);
            $sessions[$sessionIndex]['receipts'][] = ['request' => $request['requestId'], 'kind' => 'edit', 'digest' => $digest, 'rule' => $rule['id']];
            $sessions[$sessionIndex]['receipts'] = array_slice($sessions[$sessionIndex]['receipts'], -8);
            $this->WriteEditorBuffer('DesignerSessions', json_encode($sessions, JSON_THROW_ON_ERROR));
            $this->DebugEvent(
                'AKTION',
                $changed
                ? 'Browserbearbeitung im lokalen Entwurf übernommen. Andere Regeln, Partnerfreigaben und Aktionsfolgen bleiben erhalten; keine Übertragung oder Aktivierung in Z2M.'
                : 'Browserbearbeitung ohne inhaltliche Änderung bestätigt; Entwurf und Z2M bleiben unverändert.',
                ['Automation' => $index + 1, 'Auslöser' => count($rule['triggers']), 'Bedingungen' => count($rule['conditions']), 'Aktionen' => count($rule['actions']), 'Regelschalter' => $rule['enabled']]
            );
            return [200, ['ruleId' => $rule['id'], 'message' => 'Änderung im lokalen Symcon-Entwurf übernommen. Z2M bleibt unverändert.']];
        });
    }

    private function DesignerRuleFields(array $fields, ?array $original, string &$error): ?array
    {
        foreach ($fields as $key => &$value) {
            $error = match ($key) {
                'name'           => !is_string($value) || !$this->ValidEditorName(trim($value)) ? 'Bitte einen Namen mit 1 bis 120 Zeichen ohne Steuerzeichen eingeben.' : '',
                'enabled'        => !is_bool($value) ? 'Bitte den Regelschalter prüfen. Er betrifft nur den lokalen Entwurf.' : '',
                'retrigger'      => !in_array($value, ['ignore', 'restart', 'extend'], true) ? 'Bitte ein angebotenes Verhalten bei erneuter Auslösung wählen.' : '',
                'restart_policy' => !in_array($value, ['abort', 'resume', 'resume_expired'], true) ? 'Bitte ein angebotenes Verhalten nach einem Z2M-Neustart wählen.' : '',
                default          => ''
            };
            if ($error !== '') return null;
            if ($key === 'name') $value = trim($value);
            if (in_array($key, ['triggers', 'conditions', 'actions'], true) && ($original === null || $value !== $original[$key])) {
                if ($original !== null && $this->DesignerEditSectionError($key, $original[$key]) !== '') {
                    $error = 'Dieser Bereich bleibt geschützt erhalten. Bitte fehlende Gerätedaten oder nicht unterstützte Angaben zuerst im nativen Formular prüfen.';
                    return null;
                }
                $error = $this->DesignerEditSectionError($key, $value);
                if ($error !== '') return null;
            }
        }
        unset($value);
        return $fields;
    }

    private function DesignerActionOrderDebug(array $before, array $after): void
    {
        if ($before === $after) return;
        $remaining = $before;
        $positions = [];
        foreach ($after as $step) {
            $index = array_search($step, $remaining, true);
            if ($index === false) {
                $positions = [];
                break;
            }
            $positions[] = $index + 1;
            unset($remaining[$index]);
        }
        $onlyOrder = $positions !== [] && count($before) === count($after);
        $this->DebugEvent(
            'AKTION',
            $onlyOrder
            ? 'Reihenfolge der Aktionsschritte im lokalen Entwurf geändert. Wartezeiten bleiben bei ihrem jeweiligen Schritt; es wurde keine Aktion ausgeführt.'
            : 'Aktionsschritte im lokalen Entwurf geändert. Reihenfolge und Wartezeiten werden gemeinsam übernommen; es wurde keine Aktion ausgeführt.',
            ['SchritteVorher' => count($before), 'SchritteNachher' => count($after), 'NurReihenfolgeGeändert' => $onlyOrder]
            + ($onlyOrder ? ['VorherigeSchrittnummernInNeuerReihenfolge' => $positions] : [])
        );
    }

    private function DesignerEditShape(mixed $value, array $required, array $optional = []): bool
    {
        return is_array($value) && array_diff($required, array_keys($value)) === []
            && array_diff(array_keys($value), [...$required, ...$optional]) === [];
    }

    private function DesignerEditDays(mixed $days): bool
    {
        return is_array($days) && array_is_list($days) && count($days) >= 1 && count($days) <= 7
            && count(array_unique($days, SORT_REGULAR)) === count($days)
            && array_filter($days, static fn (mixed $d): bool => !is_int($d) || $d < 1 || $d > 7) === [];
    }

    private function DesignerEditSectionError(string $section, mixed $items): string
    {
        $label = ['triggers' => 'Auslöser', 'conditions' => 'Bedingung', 'actions' => 'Aktion'][$section];
        if (!is_array($items) || !array_is_list($items) || count($items) > ($section === 'triggers' ? 8 : 16) || ($section !== 'conditions' && $items === [])) {
            return $label . ': Bitte Anzahl und Aufbau der Einträge prüfen.';
        }
        $clocks = [];
        foreach ($items as $index => $item) {
            if (!$this->DesignerEditItemAllowed($section, $item)) {
                return $label . ' ' . ($index + 1) . ': Angaben fehlen oder sind nicht mehr verfügbar. Bitte Gerät, Kanal, Wert und Zeitangaben prüfen.';
            }
            if ($section === 'triggers' && $item['type'] === 'time') {
                $identity = $item['timezone'] . '|' . $item['at'];
                if (isset($clocks[$identity])) {
                    return 'Auslöser ' . ($index + 1) . ': Diese Uhrzeit ist bereits für dieselbe Zeitzone eingetragen.';
                }
                $clocks[$identity] = true;
            }
        }
        return '';
    }

    private function DesignerEditItemAllowed(string $section, mixed $item): bool
    {
        if (!is_array($item) || !is_string($item['type'] ?? null)) {
            return false;
        }
        $type = $item['type'];
        if (($section === 'triggers' && $type === 'device') || ($section === 'conditions' && $type === 'device_value')) {
            $optional = $type === 'device' ? ['for_seconds', 'on_change'] : ['start_only'];
            if (!$this->DesignerEditShape($item, ['type', 'device', 'property', 'operator', 'value'], $optional)
                || !is_string($item['device']) || preg_match('/\A0x[a-f0-9]{16}\z/D', $item['device']) !== 1 || !is_string($item['property'])
                || !(is_scalar($item['value']) || $item['value'] === null) || (is_string($item['value']) && strlen($item['value']) > 256)
                || (array_key_exists('for_seconds', $item) && !is_int($item['for_seconds']))
                || array_filter(array_intersect_key($item, array_flip(['on_change', 'start_only'])), static fn (mixed $v): bool => !is_bool($v)) !== []) {
                return false;
            }
            return $type === 'device' ? $this->EditorTriggerRows(['triggers' => [$item]]) !== null : $this->EditorConditionRows(['conditions' => [$item]]) !== null;
        }
        if ($section === 'triggers') {
            return match ($type) {
                'time'  => $this->DesignerEditShape($item, ['type', 'at', 'timezone']) && $this->ClockEditorRows([$item]) !== null,
                'sun'   => $this->DesignerEditShape($item, ['type', 'event', 'latitude', 'longitude', 'offset_minutes', 'timezone']) && $this->SolarEditorFields($item) !== null,
                default => false
            };
        }
        if ($section === 'conditions') {
            if (!is_string($item['timezone'] ?? null) || !in_array($item['timezone'], DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
                return false;
            }
            return match ($type) {
                'weekday'     => $this->DesignerEditShape($item, ['type', 'weekdays', 'timezone']) && $this->DesignerEditDays($item['weekdays']),
                'time_window' => $this->DesignerEditShape($item, ['type', 'after', 'before', 'timezone'], ['weekdays'])
                    && is_string($item['after']) && is_string($item['before'])
                    && $this->EditorClockTime($item['after']) === $item['after'] && $this->EditorClockTime($item['before']) === $item['before']
                    && $item['after'] !== $item['before'] && (!array_key_exists('weekdays', $item) || $this->DesignerEditDays($item['weekdays'])),
                default => false
            };
        }
        if (array_key_exists('delay_seconds', $item) && !is_int($item['delay_seconds'])) {
            return false;
        }
        if ($type === 'sequence') {
            return $this->DesignerEditShape($item, ['type', 'sequence_id'], ['delay_seconds']) && is_string($item['sequence_id'])
                && $this->EditorActionRows(['actions' => [$item]]) !== null;
        }
        if (!in_array($type, ['set', 'toggle', 'scene'], true) || !$this->DesignerEditShape($item['target'] ?? null, ['type', 'id'], ['system'])) {
            return false;
        }
        $target = $item['target'];
        if (!(($target['type'] === 'device' && is_string($target['id']) && preg_match('/\A0x[a-f0-9]{16}\z/D', $target['id']) === 1)
            || ($target['type'] === 'group' && is_int($target['id']) && $target['id'] >= 1 && $target['id'] <= 65535))
            || (array_key_exists('system', $target) && (!is_string($target['system']) || strlen($target['system']) > 128))) {
            return false;
        }
        $valid = match ($type) {
            'set' => $this->DesignerEditShape($item, ['type', 'target', 'values'], ['delay_seconds']) && is_array($item['values']) && !array_is_list($item['values']) && count($item['values']) <= 8
                && array_filter(array_keys($item['values']), static fn (mixed $key): bool => !is_string($key) || preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,63}\z/D', $key) !== 1) === [],
            'toggle' => $this->DesignerEditShape($item, ['type', 'target', 'property'], ['delay_seconds']) && is_string($item['property']) && preg_match('/\Astate(?:_[A-Za-z0-9_]+)?\z/D', $item['property']) === 1,
            'scene'  => $this->DesignerEditShape($item, ['type', 'target', 'scene_id'], ['delay_seconds', 'endpoint']) && is_int($item['scene_id']) && $item['scene_id'] >= 0 && $item['scene_id'] <= 255
                && (!array_key_exists('endpoint', $item) || (is_int($item['endpoint']) && $item['endpoint'] >= 1 && $item['endpoint'] <= 240))
        };
        return $valid && $this->EditorActionRows(['actions' => [$item]]) !== null;
    }
}
