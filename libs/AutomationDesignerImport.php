<?php

declare(strict_types=1);

namespace Z2MAutomations;

use JsonException;

/** Called only inside the authenticated, system-bound workspace editor entry. */
trait AutomationDesignerImport
{
    private function DesignerImportRequest(): array
    {
        $token = $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '';
        if (!$this->DesignerWriteAccess($token, 'imports')) return $this->DesignerCreateFailure(403, 'Der Zugang zum Ereignisimport fehlt oder ist abgelaufen. Bitte den Designer erneut im Modul öffnen.');
        $text = $this->DesignerRequestBody();
        if (strlen($text) > 4096) return $this->DesignerCreateFailure(413, 'Die Importanfrage ist zu umfangreich. Bitte die Ansicht aktualisieren.');
        try {
            $request = json_decode($text, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->DesignerCreateFailure(400, 'Die Importanfrage konnte nicht gelesen werden. Bitte den Eintrag erneut auswählen.');
        }
        $action = $request['action'] ?? null;
        $fields = ['requestId', 'context', 'action'];
        if (in_array($action, ['preview', 'import'], true)) $fields[] = 'eventId';
        if ($action === 'import') $fields[] = 'previewToken';
        if (!$this->DesignerEditShape($request, $fields) || !in_array($action, ['scan', 'preview', 'import'], true)
            || !RuntimeDebugContract::UUID($request['requestId'] ?? null) || !is_string($request['context'] ?? null)
            || ($action !== 'scan' && (!is_int($request['eventId']) || $request['eventId'] <= 0))
            || ($action === 'import' && (!is_string($request['previewToken']) || preg_match('/\A[a-f0-9]{32}\z/D', $request['previewToken']) !== 1))) {
            return $this->DesignerCreateFailure(400, 'Die Angaben zum Import sind unvollständig. Bitte ein Ereignis auswählen und die Vorschau prüfen.');
        }
        $sessions = $this->DesignerSessions();
        $sessionIndex = array_search(hash('sha256', $token), array_column($sessions, 'hash'), true);
        if ($sessionIndex === false) return $this->DesignerCreateFailure(403, 'Der Designerzugang ist abgelaufen. Bitte den Designer erneut öffnen.');
        $digest = hash('sha256', json_encode([$action, $request['eventId'] ?? 0, $request['previewToken'] ?? ''], JSON_THROW_ON_ERROR));
        foreach ($sessions[$sessionIndex]['receipts'] ?? [] as $receipt) {
            if ($receipt['request'] !== $request['requestId']) continue;
            if (($receipt['kind'] ?? '') !== 'import' || $receipt['digest'] !== $digest) return $this->DesignerCreateFailure(409, 'Dieser Auftrag wurde bereits mit anderen Angaben verwendet. Bitte die Ansicht aktualisieren.');
            $this->DebugEvent('AKTION', 'Ereignisimport erneut bestätigt; keine zweite Automation angelegt und kein Originalereignis verändert.');
            return [200, $receipt['result'] + ['view' => $this->EventImportView()]];
        }
        if (!hash_equals($this->DesignerCreateContext(), $request['context'])) return $this->DesignerCreateFailure(409, 'Der Entwurf oder die Gerätedaten wurden geändert. Bitte die Ansicht aktualisieren und die Importvorschau erneut prüfen.');
        if ($action === 'scan') {
            $this->ScanEventImports();
            return [200, ['ok' => true, 'action' => $action, 'view' => $this->EventImportView()]];
        }
        if ($action === 'preview') {
            $this->PreviewEventImport($request['eventId']);
            return [200, ['ok' => true, 'action' => $action, 'view' => $this->EventImportView()]];
        }
        $blocked = $this->DesignerCreateBlock();
        if ($blocked !== '') return $this->DesignerCreateFailure(409, $blocked);
        if (!$this->ImportSymconEvent($request['eventId'], $request['previewToken'])) {
            return [422, ['ok' => false, 'message' => 'Nicht übernommen: Das Ereignis, die Gerätezuordnung oder die Vorschau wurde geändert. Bitte die aktuelle Vorschau erneut prüfen.', 'view' => $this->EventImportView()]];
        }
        $draft = $this->ReadEditorDraft();
        $rule = $draft['configuration']['automations'][array_key_last($draft['configuration']['automations'])];
        $result = ['ok' => true, 'action' => $action, 'ruleId' => $rule['id'], 'message' => 'Ereignis als ausgeschaltete Automation im gewählten Systementwurf übernommen. Das ursprüngliche Symcon-Ereignis bleibt unverändert.'];
        $sessions[$sessionIndex]['receipts'][] = ['request' => $request['requestId'], 'kind' => 'import', 'digest' => $digest, 'result' => $result];
        $sessions[$sessionIndex]['receipts'] = array_slice($sessions[$sessionIndex]['receipts'], -8);
        $this->WriteEditorBuffer('DesignerSessions', json_encode($sessions, JSON_THROW_ON_ERROR));
        return [200, $result + ['view' => $this->EventImportView()]];
    }
}
