<?php

declare(strict_types=1);

namespace Z2MAutomations;

use JsonException;

trait AutomationDesignerCreation
{
    protected function DesignerRequestBody(): string
    {
        return (string) file_get_contents('php://input', false, null, 0, 65537);
    }

    private function DesignerPendingInput(): bool
    {
        foreach (['rule', 'sequence'] as $kind) {
            $work = json_decode($this->ReadEditorBuffer('EditorWork.' . $kind) ?: '[]', true, 16, JSON_THROW_ON_ERROR);
            if (($work['dirty'] ?? false) && ($work['context']['scope'] ?? '') === $this->TransportScope()) {
                return true;
            }
        }
        return false;
    }

    private function DesignerCreateContext(): string
    {
        $parts = [$this->TransportScope()];
        foreach (['YamlDraft', 'EditorDraft', 'EditorSnapshot', 'EditorSelection', 'SequenceSelection'] as $attribute) {
            $value = $this->ReadEditorAttribute($attribute);
            $parts[] = $this->workspaceEditorContext === null ? $value : hash('sha256', $value);
        }
        foreach (['EditorWork.rule', 'EditorWork.sequence', 'EditorNavigation'] as $buffer) {
            $parts[] = $this->ReadEditorBuffer($buffer);
        }
        return hash('sha256', json_encode($parts, JSON_THROW_ON_ERROR));
    }

    private function DesignerCreateBlock(bool $creating = true): string
    {
        if ($this->workspaceEditorContext !== null && $creating && !$this->workspaceEditorContext['catalogAvailable']) return 'Bitte zuerst „Gerätedaten aktualisieren“ für dieses System wählen. Vorhandene Entwürfe bleiben erhalten.';
        $snapshot = $this->workspaceEditorContext['snapshot'] ?? json_decode($this->ReadEditorAttribute('EditorSnapshot') ?: '[]', true, 32, JSON_THROW_ON_ERROR);
        $draft = $this->ReadEditorDraft();
        $state = json_decode($this->ReadEditorBuffer('TransportState') ?: '[]', true, 32, JSON_THROW_ON_ERROR);
        return match (true) {
            ($snapshot['scope'] ?? '') !== $this->TransportScope()                                                                                             => 'Bitte im nativen Modul „Nur Gerätedaten aktualisieren“ wählen und diese Ansicht aktualisieren.',
            $draft === null                                                                                                                                    => $this->workspaceEditorContext !== null ? 'Für dieses System fehlt noch ein lokaler Entwurf. Bitte einen vorhandenen Entwurf über den gesicherten Übernahmeablauf zuordnen und die Ansicht aktualisieren.' : 'Bitte zuerst im nativen Modul „Geräte und gespeicherte Automationen laden“ wählen. Danach hier die Ansicht aktualisieren.',
            ($draft['scope'] ?? '') !== $this->TransportScope() || ($draft['draftChecksum'] ?? '') !== hash('sha256', $this->ReadEditorAttribute('YamlDraft')) => 'Der vorhandene Entwurf passt nicht zu dieser Ansicht. Bitte im nativen Formular prüfen; er wird hier nicht ersetzt.',
            $this->DesignerPendingInput()                                                                                                                      => 'Im nativen Formular gibt es offene Änderungen. Bitte diese zuerst in den Entwurf übernehmen oder zurücknehmen und danach die Ansicht aktualisieren.',
            ($state['workflow'] ?? null) !== null                                                                                                              => 'Im Modul läuft noch ein Auftrag. Bitte dessen Abschluss abwarten und die Ansicht aktualisieren.',
            $creating && count($draft['configuration']['automations']) >= 100                                                                                  => 'Der Entwurf enthält bereits 100 Automationen. Bitte zuerst eine Regel entfernen.',
            default                                                                                                                                            => ''
        };
    }

    private function DesignerWriteHeaders(): ?array
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $url = is_string($origin) ? parse_url($origin) : false;
        $authority = is_array($url) ? ($url['host'] ?? '') . (isset($url['port']) ? ':' . $url['port'] : '') : '';
        if (!is_array($url) || !in_array($url['scheme'] ?? '', ['http', 'https'], true)
            || strcasecmp($authority, $_SERVER['HTTP_HOST'] ?? '') !== 0
            || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment']) || ($url['path'] ?? '') !== ''
            || !in_array($_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'same-origin', ['same-origin'], true)) {
            return $this->DesignerCreateFailure(403, 'Der Aufruf stammt nicht aus dieser Designeransicht. Bitte den Designer erneut im Modul öffnen.');
        }
        $contentTypes = [];
        foreach (['HTTP_CONTENT_TYPE', 'CONTENT_TYPE'] as $header) {
            $value = $_SERVER[$header] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $contentTypes[] = is_string($value) ? strtolower(trim(explode(';', $value)[0])) : '';
        }
        $json = $contentTypes !== [] && array_diff($contentTypes, ['application/json']) === [];
        $this->DebugEvent(
            'EMPFANG',
            'Inhaltstyp der Browseranfrage geprüft; Headerwerte und Anfrageinhalt bleiben geschützt.',
            ['SymconHeaderVorhanden' => isset($_SERVER['HTTP_CONTENT_TYPE']), 'StandardHeaderVorhanden' => isset($_SERVER['CONTENT_TYPE']), 'JSONErkannt' => $json]
        );
        if (!$json) {
            return $this->DesignerCreateFailure(415, 'Die Eingaben konnten nicht gelesen werden. Bitte den Designer erneut öffnen.');
        }
        return null;
    }

    private function DesignerCreateRequest(): array
    {
        $failure = $this->DesignerWriteHeaders();
        if ($failure !== null) {
            return $failure;
        }
        $token = $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '';
        return $this->WithTransportLock(function () use ($token): array
        {
            if (!$this->DesignerTokenAllowed($token, true)) {
                return $this->DesignerCreateFailure(403, 'Der Zugang zur Regelerstellung ist abgelaufen. Bitte den Designer erneut im Modul öffnen.');
            }
            $text = $this->DesignerRequestBody();
            if (strlen($text) > 65536) {
                return $this->DesignerCreateFailure(413, 'Die Eingaben sind zu umfangreich. Bitte höchstens acht Auslöser und 16 Aktionsschritte eintragen.');
            }
            try {
                $request = json_decode($text, true, 16, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return $this->DesignerCreateFailure(400, 'Die Eingaben konnten nicht gelesen werden. Bitte die Auswahl erneut vornehmen.');
            }
            $complete = $this->DesignerEditShape($request, ['requestId', 'context', 'rule']);
            $simple = $this->DesignerEditShape($request, ['requestId', 'context', 'name', 'source', 'target']);
            if ((!$complete && !$simple) || !is_string($request['requestId'] ?? null) || !RuntimeDebugContract::UUID($request['requestId'])
                || !is_string($request['context'] ?? null)
                || ($complete && !$this->DesignerEditShape($request['rule'], ['name', 'retrigger', 'triggers', 'conditions', 'actions'])
                    && !$this->DesignerEditShape($request['rule'], ['name', 'retrigger', 'restart_policy', 'triggers', 'conditions', 'actions']))
                || ($simple && !is_string($request['name'] ?? null))) {
                return $this->DesignerCreateFailure(400, 'Die Angaben zur neuen Automation sind unvollständig. Bitte die Auswahl prüfen.');
            }
            $sessions = $this->DesignerSessions();
            $sessionIndex = array_search(hash('sha256', $token), array_column($sessions, 'hash'), true);
            if ($sessionIndex === false) {
                return $this->DesignerCreateFailure(403, 'Der Zugang ist inzwischen abgelaufen. Bitte den Designer erneut im Modul öffnen.');
            }
            // A view refresh may replace its optimistic context after a lost success response.
            // Retrying the same intended rule must still return its original receipt.
            $digest = hash('sha256', json_encode($complete ? ['rule' => $request['rule']] : [$request['name'], $request['source'], $request['target']], JSON_THROW_ON_ERROR));
            foreach ($sessions[$sessionIndex]['receipts'] ?? [] as $receipt) {
                if ($receipt['request'] === $request['requestId']) {
                    if (($receipt['kind'] ?? 'create') !== 'create' || $receipt['digest'] !== $digest) {
                        return $this->DesignerCreateFailure(409, 'Dieser Auftrag wurde bereits mit anderen Angaben verwendet. Bitte die Ansicht aktualisieren.');
                    }
                    $this->DebugEvent('AKTION', 'Wiederholte Browseranfrage bestätigt; keine zweite Automation angelegt.');
                    return [200, ['ruleId' => $receipt['rule'], 'message' => 'Die Automation wurde bereits als ausgeschalteter Entwurf ergänzt.']];
                }
            }
            if (!hash_equals($this->DesignerCreateContext(), $request['context'])) {
                return $this->DesignerCreateFailure(409, 'Der Entwurf oder die Gerätedaten wurden inzwischen geändert. Ihre Eingaben bleiben im Dialog. Bitte die Ansicht aktualisieren und die Auswahl prüfen.');
            }
            $blocked = $this->DesignerCreateBlock();
            if ($blocked !== '') {
                return $this->DesignerCreateFailure(409, $blocked);
            }
            if ($complete) {
                $fields = $request['rule'];
            } else {
                // The compact request is normalised into the same full rule validation path.
                $snapshot = $this->EditorSnapshot();
                $reason = '';
                $triggers = $this->DesignerCreationTriggers($snapshot, $request['source'], $reason);
                if ($triggers === null) return $this->DesignerCreateFailure(422, $reason);
                if (!$this->DesignerChoiceAllowed($snapshot, $request['target'], 'Target')) return $this->DesignerCreateFailure(422, 'Bitte Zielgerät, Kanal und Wert prüfen. Als Ziel sind nur aktuell schaltbare Variablen erlaubt.');
                $fields = ['name' => $request['name'], 'retrigger' => 'ignore', 'triggers' => $triggers, 'conditions' => [],
                    'actions'     => [$this->BuildEditorAction($request['target']['device'], $request['target'])]];
            }
            $reason = '';
            $fields = $this->DesignerRuleFields($fields, null, $reason);
            if ($fields === null) return $this->DesignerCreateFailure(422, $reason);
            $draft = $this->ReadEditorDraft();
            do {
                $rule = array_replace($this->NewEditorRule($fields['name'], false, $fields['triggers'], $fields['actions']), $fields);
            } while ($this->EditorRuleIndex($draft, $rule['id']) !== null);
            $draft['configuration']['automations'][] = $rule;
            $state = $this->RefreshState();
            if ($state['scope'] !== $draft['scope'] || !$this->DesignerTokenAllowed($token, true)) {
                return $this->DesignerCreateFailure(409, 'Die Verbindung oder der Zugang wurde inzwischen geändert. Bitte den Designer erneut im Modul öffnen.');
            }
            if (!$this->CommitEditorChange($state, $draft, 'draft_created', $rule['id'])) {
                return $this->DesignerCreateFailure(422, 'Die Automation konnte nicht übernommen werden. Bitte Aktionsfolgen und Entwurfsgröße prüfen; einschließlich aufgerufener Folgen sind höchstens 16 Schritte erlaubt.');
            }
            $sessions[$sessionIndex]['receipts'][] = ['request' => $request['requestId'], 'kind' => 'create', 'digest' => $digest, 'rule' => $rule['id']];
            $sessions[$sessionIndex]['receipts'] = array_slice($sessions[$sessionIndex]['receipts'], -8);
            $this->WriteEditorBuffer('DesignerSessions', json_encode($sessions, JSON_THROW_ON_ERROR));
            $this->DebugRestartPolicy($rule);
            $this->DebugEvent(
                'AKTION',
                'Neue Automation aus der Browserauswahl ausgeschaltet im lokalen Entwurf ergänzt. Bestehende Regeln erhalten; keine Übertragung oder Aktivierung.',
                ['Automationen'     => count($draft['configuration']['automations']), 'Auslöser' => count($rule['triggers']), 'Bedingungen' => count($rule['conditions']), 'Aktionen' => count($rule['actions']), 'RegelEingeschaltet' => false,
                    'Auslöserarten' => array_values(array_unique(array_map(static fn (array $trigger): string => ['device' => 'Geräteereignis', 'time' => 'Uhrzeit', 'sun' => 'Sonnenzeit'][$trigger['type']], $rule['triggers'])))]
                + ($rule['triggers'][0]['type'] === 'sun' ? ['Sonnenereignis' => $rule['triggers'][0]['event'] === 'sunrise' ? 'Sonnenaufgang' : 'Sonnenuntergang', 'ZeitversatzMinuten' => $rule['triggers'][0]['offset_minutes']] : [])
            );
            return [200, ['ruleId' => $rule['id'], 'message' => $this->workspaceEditorContext !== null ? 'Automation ausgeschaltet im gewählten Systementwurf ergänzt. In Z2M wurde nichts geändert.' : 'Automation ausgeschaltet im Symcon-Entwurf ergänzt. Prüfen, Übertragen und Aktivieren erfolgen weiterhin im nativen Formular.']];
        });
    }

    private function DesignerCreationTriggers(array $snapshot, mixed $source, string &$reason): ?array
    {
        $reason = 'Bitte auslösendes Gerät, Kanal, Vergleich und Wert prüfen.';
        if (!is_array($source)) {
            return null;
        }
        if (!array_key_exists('type', $source)) {
            return $this->DesignerChoiceAllowed($snapshot, $source, 'Source')
                && $this->EditorComparisonAllowed($snapshot, $source['device'], $source, $source['operator'] ?? null)
                ? [['type' => 'device', 'device' => $source['device'], 'property' => $source['property'], 'operator' => $source['operator'], 'value' => $source['value']]] : null;
        }
        if ($source['type'] === 'time') {
            $reason = 'Bitte eine bis acht unterschiedliche Uhrzeiten und eine gültige Zeitzone auswählen.';
            if (count($source) !== 3 || array_diff(array_keys($source), ['type', 'times', 'timezone']) !== []
                || !is_array($source['times'] ?? null) || !array_is_list($source['times']) || count($source['times']) < 1 || count($source['times']) > 8
                || !is_string($source['timezone'] ?? null) || array_filter($source['times'], static fn (mixed $time): bool => !is_string($time)) !== []) {
                return null;
            }
            $triggers = array_map(static fn (string $time): array => ['type' => 'time', 'at' => $time, 'timezone' => $source['timezone']], $source['times']);
            $rows = $this->ClockEditorRows($triggers);
            return $rows === null ? null : array_map(static fn (array $row): array => ['type' => 'time', 'at' => $row['At'], 'timezone' => $source['timezone']], $rows);
        }
        if ($source['type'] === 'sun') {
            $reason = 'Bitte Sonnenaufgang oder Sonnenuntergang, Standort, Zeitzone und einen ganzen Zeitversatz zwischen -720 und 720 Minuten angeben. Negative Werte bedeuten früher, positive später.';
            return array_diff(array_keys($source), ['type', 'event', 'latitude', 'longitude', 'offset_minutes', 'timezone']) === []
                && $this->SolarEditorFields($source) !== null ? [$source] : null;
        }
        $reason = 'Bitte Geräteereignis, Uhrzeit oder Sonnenzeit als Auslöser wählen.';
        return null;
    }

    private function DesignerChoiceAllowed(array $snapshot, mixed $choice, string $side): bool
    {
        $keys = $side === 'Source' ? ['device', 'property', 'value', 'operator'] : ['device', 'property', 'value'];
        if (!is_array($choice) || count($choice) !== count($keys) || array_diff(array_keys($choice), $keys) !== []
            || !is_string($choice['device'] ?? null) || !is_string($choice['property'] ?? null) || !array_key_exists('value', $choice)) {
            return false;
        }
        if ($side === 'Source' && preg_match('/\A0x[a-f0-9]{16}\z/D', $choice['device']) !== 1) {
            return false;
        }
        return $this->EditorValueAllowed($snapshot, $choice['device'], $choice['property'], $choice['value'], $side);
    }

    private function DesignerCreateFailure(int $status, string $message): array
    {
        $this->DebugEvent('FEHLER', 'Browseränderung nicht übernommen; Entwurf und Z2M bleiben unverändert.', ['HTTP-Status' => $status, 'Hinweis' => $message]);
        return [$status, ['message' => $message]];
    }
}
