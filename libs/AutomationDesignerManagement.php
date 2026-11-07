<?php

declare(strict_types=1);

namespace Z2MAutomations;

use JsonException;

/** Explicit local draft management; never sends a command to Z2M. */
trait AutomationDesignerManagement
{
    private function DesignerManageRequest(): array
    {
        $failure = $this->DesignerWriteHeaders();
        if ($failure !== null) return $failure;
        $token = $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '';
        return $this->WithTransportLock(function () use ($token): array
        {
            if (!$this->DesignerWriteAccess($token, 'manage')) return $this->DesignerCreateFailure(403, 'Der Zugang zur Regelverwaltung fehlt oder ist abgelaufen. Bitte den Designer erneut im Modul öffnen.');
            $text = $this->DesignerRequestBody();
            if (strlen($text) > 4096) return $this->DesignerCreateFailure(413, 'Der Verwaltungsauftrag ist zu umfangreich. Bitte die Ansicht aktualisieren.');
            try {
                $request = json_decode($text, true, 8, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                return $this->DesignerCreateFailure(400, 'Der Verwaltungsauftrag konnte nicht gelesen werden. Bitte erneut auswählen.');
            }
            if (!$this->DesignerEditShape($request, ['requestId', 'context', 'operation', 'id'])
                || !is_string($request['requestId']) || !RuntimeDebugContract::UUID($request['requestId'])
                || !is_string($request['context']) || !is_string($request['id']) || strlen($request['id']) > 120
                || !in_array($request['operation'], ['copy-rule', 'delete-rule', 'delete-sequence', 'filter'], true)) {
                return $this->DesignerCreateFailure(400, 'Bitte eine angebotene Verwaltungsaktion und einen vorhandenen Eintrag auswählen.');
            }
            $sessions = $this->DesignerSessions();
            $sessionIndex = array_search(hash('sha256', $token), array_column($sessions, 'hash'), true);
            $digest = hash('sha256', json_encode([$request['operation'], $request['id']], JSON_THROW_ON_ERROR));
            foreach ($sessions[$sessionIndex]['receipts'] ?? [] as $receipt) {
                if ($receipt['request'] !== $request['requestId']) continue;
                if (($receipt['kind'] ?? '') !== 'manage' || $receipt['digest'] !== $digest) return $this->DesignerCreateFailure(409, 'Dieser Auftrag wurde bereits mit anderen Angaben verwendet. Bitte die Ansicht aktualisieren.');
                $this->DebugEvent('AKTION', 'Bereits bestätigte Browserverwaltung erneut quittiert; kein zweites Kopieren oder Löschen.', ['Aktion' => $request['operation']]);
                return [200, $receipt['result']];
            }
            if (!hash_equals($this->DesignerCreateContext(), $request['context'])) return $this->DesignerCreateFailure(409, 'Der Entwurf oder die Auswahl wurde inzwischen geändert. Bitte die Ansicht aktualisieren und die Aktion erneut auswählen.');
            $block = $this->DesignerCreateBlock(false);
            if ($block !== '') return $this->DesignerCreateFailure(409, $block);
            $draft = $this->ReadEditorDraft();
            $operation = $request['operation'];
            if ($operation === 'filter') {
                if (!in_array($request['id'], ['all', 'enabled', 'disabled'], true)) return $this->DesignerCreateFailure(400, 'Bitte Alle, Aktiviert oder Deaktiviert auswählen.');
                $count = count(array_filter($draft['configuration']['automations'], static fn (array $rule): bool => $request['id'] === 'all' || $rule['enabled'] === ($request['id'] === 'enabled')));
                $this->DebugEvent('AKTION', 'Statusfilter im Browser gewählt; er bezieht sich auf den lokalen Regelschalter, nicht auf den laufenden Z2M-Betrieb.', ['Filter' => match ($request['id']) {
                    'enabled' => 'Aktiviert', 'disabled' => 'Deaktiviert', default => 'Alle'
                }, 'Treffer' => $count]);
                return [200, ['message' => 'Statusfilter auf den lokalen Entwurf angewendet.', 'count' => $count]];
            }
            $sequence = $operation === 'delete-sequence';
            $field = $sequence ? 'sequences' : 'automations';
            $index = array_search($request['id'], array_column($draft['configuration'][$field] ?? [], 'id'), true);
            if ($index === false) return $this->DesignerCreateFailure(409, 'Dieser Eintrag ist nicht mehr im Entwurf vorhanden. Bitte die Ansicht aktualisieren.');
            $selected = $this->EditorSelection()['id'] ?? '';
            if ($sequence) {
                $usage = $this->SequenceUsage($request['id'], $draft);
                if ($usage > 0) {
                    $users = array_values(array_filter($draft['configuration']['automations'], static fn (array $rule): bool => array_any($rule['actions'], static fn (array $action): bool => ($action['type'] ?? '') === 'sequence' && $action['sequence_id'] === $request['id'])));
                    $this->DebugEvent('FEHLER', 'Aktionsfolge nicht gelöscht: Sie wird noch von Automationen verwendet. Zuerst dort den Folgenaufruf entfernen.', ['Verwendungen' => $usage]);
                    return [422, ['message' => 'Diese Aktionsfolge wird noch verwendet und kann nicht gelöscht werden. Entfernen Sie zuerst die Aufrufe in den aufgeführten Automationen.', 'users' => array_column($users, 'name')]];
                }
            }
            if ($operation === 'copy-rule') {
                if (count($draft['configuration']['automations']) >= 100) return $this->DesignerCreateFailure(422, 'Es sind bereits 100 Automationen im Entwurf. Bitte zuerst eine nicht mehr benötigte Regel entfernen.');
                $copy = $draft['configuration']['automations'][$index];
                do {
                    $selected = 'rule_' . bin2hex(random_bytes(8));
                } while ($this->EditorRuleIndex($draft, $selected) !== null);
                $copy['id'] = $selected;
                $copy['name'] = $this->AutomationCopyName($copy['name'], $draft);
                $copy['enabled'] = false;
                $draft['configuration']['automations'][] = $copy;
            } else {
                array_splice($draft['configuration'][$field], $index, 1);
                if (!$sequence && $selected === $request['id']) $selected = '';
            }
            $state = $this->RefreshState();
            $status = match ($operation) {
                'copy-rule' => 'draft_copied', 'delete-rule' => 'draft_deleted', default => 'sequence_deleted'
            };
            if (!$this->CommitEditorChange($state, $draft, $status, $selected)) return $this->DesignerCreateFailure(422, 'Die Änderung konnte nicht übernommen werden. Bitte Entwurfsgröße und Aktionsfolgen prüfen.');
            if ($sequence) $this->RememberReusableSelection('', $this->ReadEditorDraft());
            $message = match ($operation) {
                'copy-rule'   => 'Automation als ausgeschaltete Kopie im lokalen Entwurf angelegt. Z2M bleibt unverändert.',
                'delete-rule' => 'Automation aus dem lokalen Entwurf gelöscht. Eine in Z2M gespeicherte Regel bleibt bis zur nächsten Übertragung erhalten.',
                default       => 'Unbenutzte Aktionsfolge aus dem lokalen Entwurf gelöscht. Z2M bleibt unverändert.'
            };
            $result = ['message' => $message, 'ruleId' => $operation === 'copy-rule' ? $selected : '', 'operation' => $operation];
            $sessions[$sessionIndex]['receipts'][] = ['request' => $request['requestId'], 'kind' => 'manage', 'digest' => $digest, 'result' => $result];
            $sessions[$sessionIndex]['receipts'] = array_slice($sessions[$sessionIndex]['receipts'], -8);
            $this->WriteEditorBuffer('DesignerSessions', json_encode($sessions, JSON_THROW_ON_ERROR));
            $this->DebugEvent('AKTION', $message, ['Aktion' => $operation, 'Automationen' => count($draft['configuration']['automations']), 'Aktionsfolgen' => count($draft['configuration']['sequences'] ?? []), 'KopieAusgeschaltet' => $operation === 'copy-rule']);
            return [200, $result];
        });
    }
}
