<?php

declare(strict_types=1);

namespace Z2MAutomations;

use JsonException;

trait AutomationDesignerCreation
{
    protected function DesignerRequestBody(): string
    {
        return (string) file_get_contents('php://input', false, null, 0, 16385);
    }

    private function DesignerPendingInput(): bool
    {
        foreach (['rule', 'sequence'] as $kind) {
            $work = json_decode($this->GetBuffer('EditorWork.' . $kind) ?: '[]', true, 16, JSON_THROW_ON_ERROR);
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
            $parts[] = $this->ReadAttributeString($attribute);
        }
        foreach (['EditorWork.rule', 'EditorWork.sequence', 'EditorNavigation'] as $buffer) {
            $parts[] = $this->GetBuffer($buffer);
        }
        return hash('sha256', json_encode($parts, JSON_THROW_ON_ERROR));
    }

    private function DesignerCreateBlock(): string
    {
        $snapshot = json_decode($this->ReadAttributeString('EditorSnapshot') ?: '[]', true, 32, JSON_THROW_ON_ERROR);
        $draft = $this->ReadEditorDraft();
        $state = json_decode($this->GetBuffer('TransportState') ?: '[]', true, 32, JSON_THROW_ON_ERROR);
        return match (true) {
            ($snapshot['scope'] ?? '') !== $this->TransportScope()                                                                                             => 'Bitte im nativen Modul „Nur Gerätedaten aktualisieren“ wählen und diese Ansicht aktualisieren.',
            $draft === null                                                                                                                                    => 'Bitte zuerst im nativen Modul „Geräte und gespeicherte Automationen laden“ wählen. Danach hier die Ansicht aktualisieren.',
            ($draft['scope'] ?? '') !== $this->TransportScope() || ($draft['draftChecksum'] ?? '') !== hash('sha256', $this->ReadAttributeString('YamlDraft')) => 'Der vorhandene Entwurf passt nicht zu dieser Ansicht. Bitte im nativen Formular prüfen; er wird hier nicht ersetzt.',
            $this->DesignerPendingInput()                                                                                                                      => 'Im nativen Formular gibt es offene Änderungen. Bitte diese zuerst in den Entwurf übernehmen oder zurücknehmen und danach die Ansicht aktualisieren.',
            ($state['workflow'] ?? null) !== null                                                                                                              => 'Im Modul läuft noch ein Auftrag. Bitte dessen Abschluss abwarten und die Ansicht aktualisieren.',
            count($draft['configuration']['automations']) >= 100                                                                                               => 'Der Entwurf enthält bereits 100 Automationen. Bitte zuerst eine Regel entfernen.',
            default                                                                                                                                            => ''
        };
    }

    private function DesignerCreateRequest(): array
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
        $token = $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '';
        return $this->WithTransportLock(function () use ($token): array
        {
            if (!$this->DesignerTokenAllowed($token, true)) {
                return $this->DesignerCreateFailure(403, 'Der Zugang zur Regelerstellung ist abgelaufen. Bitte den Designer erneut im Modul öffnen.');
            }
            $text = $this->DesignerRequestBody();
            if (strlen($text) > 16384) {
                return $this->DesignerCreateFailure(413, 'Die Eingaben sind zu umfangreich. Bitte eine einfache Regel mit einem Auslöser und einer Aktion anlegen.');
            }
            try {
                $request = json_decode($text, true, 8, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return $this->DesignerCreateFailure(400, 'Die Eingaben konnten nicht gelesen werden. Bitte die Auswahl erneut vornehmen.');
            }
            if (!is_array($request) || count($request) !== 5 || array_diff(array_keys($request), ['requestId', 'context', 'name', 'source', 'target']) !== []
                || !is_string($request['requestId'] ?? null) || !RuntimeDebugContract::UUID($request['requestId'])
                || !is_string($request['context'] ?? null) || !is_string($request['name'] ?? null)) {
                return $this->DesignerCreateFailure(400, 'Die Angaben zur neuen Automation sind unvollständig. Bitte die Auswahl prüfen.');
            }
            $sessions = $this->DesignerSessions();
            $sessionIndex = array_search(hash('sha256', $token), array_column($sessions, 'hash'), true);
            if ($sessionIndex === false) {
                return $this->DesignerCreateFailure(403, 'Der Zugang ist inzwischen abgelaufen. Bitte den Designer erneut im Modul öffnen.');
            }
            // A view refresh may replace its optimistic context after a lost success response.
            // Retrying the same intended rule must still return its original receipt.
            $digest = hash('sha256', json_encode([$request['name'], $request['source'], $request['target']], JSON_THROW_ON_ERROR));
            foreach ($sessions[$sessionIndex]['receipts'] ?? [] as $receipt) {
                if ($receipt['request'] === $request['requestId']) {
                    if ($receipt['digest'] !== $digest) {
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
            $snapshot = $this->EditorSnapshot();
            $source = $request['source'];
            $target = $request['target'];
            if (!$this->DesignerChoiceAllowed($snapshot, $source, 'Source') || !$this->DesignerChoiceAllowed($snapshot, $target, 'Target')
                || !$this->ValidEditorName(trim($request['name']))
                || !$this->EditorComparisonAllowed($snapshot, $source['device'], $source, $source['operator'] ?? null)) {
                return $this->DesignerCreateFailure(422, 'Bitte Name, Gerät, Kanal und Wert prüfen. Als Ziel sind nur aktuell schaltbare Variablen erlaubt.');
            }
            $draft = $this->ReadEditorDraft();
            $rule = $this->SimpleEditorRule(trim($request['name']), $source['device'], $source, $target['device'], $target, false, $source['operator']);
            $draft['configuration']['automations'][] = $rule;
            $state = $this->RefreshState();
            if ($state['scope'] !== $draft['scope'] || !$this->DesignerTokenAllowed($token, true)) {
                return $this->DesignerCreateFailure(409, 'Die Verbindung oder der Zugang wurde inzwischen geändert. Bitte den Designer erneut im Modul öffnen.');
            }
            if (!$this->CommitEditorChange($state, $draft, 'draft_created', $rule['id'])) {
                return $this->DesignerCreateFailure(422, 'Die Automation konnte nicht in den Entwurf übernommen werden. Bitte Größe und Angaben im nativen Formular prüfen.');
            }
            $sessions[$sessionIndex]['receipts'][] = ['request' => $request['requestId'], 'digest' => $digest, 'rule' => $rule['id']];
            $sessions[$sessionIndex]['receipts'] = array_slice($sessions[$sessionIndex]['receipts'], -8);
            $this->SetBuffer('DesignerSessions', json_encode($sessions, JSON_THROW_ON_ERROR));
            $this->DebugEvent(
                'AKTION',
                'Neue Automation aus der Browserauswahl ausgeschaltet im lokalen Entwurf ergänzt. Bestehende Regeln erhalten; keine Übertragung oder Aktivierung.',
                ['Automationen' => count($draft['configuration']['automations']), 'Auslöser' => 1, 'Aktionen' => 1]
            );
            return [200, ['ruleId' => $rule['id'], 'message' => 'Automation ausgeschaltet im Symcon-Entwurf ergänzt. Prüfen, Übertragen und Aktivieren erfolgen weiterhin im nativen Formular.']];
        });
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
        $this->DebugEvent('FEHLER', 'Neue Browserautomation nicht übernommen; Entwurf und Z2M bleiben unverändert.', ['HTTP-Status' => $status, 'Hinweis' => $message]);
        return [$status, ['message' => $message]];
    }
}
