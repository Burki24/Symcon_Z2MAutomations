<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

/** One access contract for the native tile and the existing browser designer. */
trait DesignerEmbedding
{
    public function GetVisualizationTile(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): string
        {
            $html = $this->VisualizationAsset('tile.html');
            $this->DebugEvent('AKTION', 'Symcon-Kachel bereitgestellt. Systemauswahl und Status lesen nur vorhandene Moduldaten; der Designer wird ausdrücklich geöffnet.');
            return str_replace(
                ['/* TILE_STYLE */', '/* TILE_ORIGIN */', '/* TILE_SCRIPT */', '/* TILE_BOOTSTRAP */'],
                [$this->VisualizationAsset('tile.css'), $this->VisualizationHTTPOriginScript(), $this->VisualizationAsset('tile.js'),
                    json_encode(['version' => 1, 'path' => '/hook/' . $this->DesignerHookName() . '/index.html'], JSON_THROW_ON_ERROR | JSON_HEX_TAG)],
                $html
            );
        });
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Ident, $Value): void
        {
            $request = is_string($Value) && strlen($Value) <= 1024 ? json_decode($Value, true) : null;
            if ($Ident !== 'DesignerTile' || !is_array($request) || !$this->DesignerEditShape($request, ['client', 'request', 'action', 'systemId'])
                || !is_string($request['client']) || !is_string($request['request'])
                || preg_match('/\A[a-f0-9]{32}\z/D', $request['client']) !== 1 || preg_match('/\A[a-f0-9]{32}\z/D', $request['request']) !== 1
                || !in_array($request['action'], ['status', 'access'], true) || !is_string($request['systemId'])) {
                $this->DebugEvent('FEHLER', 'Kachelanfrage abgelehnt: Aufruf oder Auswahl ist ungültig. Es wurde kein Auftrag an Z2M gesendet.');
                return;
            }
            $reply = ['type' => 'z2ma-tile', 'version' => 1, 'client' => $request['client'], 'request' => $request['request'], 'action' => $request['action']];
            try {
                $state = $this->DesignerTileState($request['systemId']);
                $reply += ['ok' => true, 'state' => $state];
                if ($request['action'] === 'access') $reply['access'] = $this->WithTransportLock(fn (): array => $this->IssueDesignerAccess($state['selectedSystem']));
                $this->DebugEvent('SENDEN', $request['action'] === 'access'
                    ? 'Designerzugang für das in der Kachel gewählte System bereitgestellt. Zugangsdaten bleiben geschützt; offene Entwürfe unverändert.'
                    : 'Kachelstatus aus vorhandenen Moduldaten aktualisiert. Keine Z2M-Abfrage und kein Systemwechsel im nativen Editor.', ['Systeme' => count($state['systems'])]);
            } catch (Throwable) {
                $reply = array_replace($reply, ['ok' => false, 'message' => 'Die Ansicht ist derzeit nicht verfügbar. Bitte die Systemzuordnung im Modul prüfen und erneut versuchen.']);
                $this->DebugEvent('FEHLER', 'Kachelstatus oder Designerzugang konnte nicht bereitgestellt werden. Bitte die Systemzuordnung und das Modul-Debug prüfen.');
            }
            $this->UpdateVisualizationValue($reply);
        });
    }

    private function DesignerTileState(string $selected): array
    {
        $systems = [];
        if ($this->WorkspaceMode()) {
            $overview = json_decode($this->WorkspaceOverview($this->ReadWorkspace()), true, 32, JSON_THROW_ON_ERROR);
            foreach ($overview['systems'] as $system) {
                $systems[] = ['systemId' => $system['systemId'], 'name' => $system['name'], 'baseTopic' => $system['binding']['baseTopic'],
                    'rules'              => $system['rules'], 'sequences' => $system['sequences'], 'openInput' => $system['openInput'],
                    'status'             => match (true) {
                        !$system['connectionCurrent']      => 'Systemzuordnung bitte prüfen',
                        !$system['online']                 => 'Z2M noch nicht als erreichbar bestätigt',
                        !$system['confirmed']              => 'Erweiterung noch nicht bestätigt',
                        $system['activeRevision'] !== null => 'Automatik aktiv · Stand ' . $system['activeRevision'],
                        default                            => 'Kein aktiver Automatikbetrieb bestätigt'
                    }];
            }
            if ($selected === '') $selected = $overview['selectedSystem'];
        }
        if ($selected !== '' && !in_array($selected, array_column($systems, 'systemId'), true)) throw new RuntimeException('binding_changed');
        if ($selected === '' && $systems !== []) $selected = $systems[0]['systemId'];
        return ['systems' => $systems, 'selectedSystem' => $selected, 'readAt' => $this->Now()];
    }

    /** Tokens stay in the URL fragment; the module stores only their hash. */
    private function IssueDesignerAccess(string $system = ''): array
    {
        if (!$this->RegisterHook($this->DesignerHookName())) throw new RuntimeException('hook_unavailable');
        if ($system !== '') $this->WorkspaceRecord($this->ReadWorkspace(), $system);
        $token = bin2hex(random_bytes(32));
        $sessions = $this->DesignerSessions();
        $session = ['hash' => hash('sha256', $token), 'expires' => $this->Now() + 900, 'limit' => $this->Now() + 28800,
            'scope'        => $this->DesignerAccessScope(), 'initialSystem' => $system,
            'create'       => true, 'edit' => true, 'sequences' => true, 'manage' => true, 'receipts' => []];
        if ($this->WorkspaceMode()) foreach (['runtime', 'catalog', 'imports', 'diagnostics', 'installation', 'permissions'] as $right) $session[$right] = true;
        $sessions[] = $session;
        $this->WriteEditorBuffer('DesignerSessions', json_encode(array_slice($sessions, -4), JSON_THROW_ON_ERROR));
        $this->DebugEvent('AKTION', 'Designerzugang erstellt: 15 Minuten gültig, bei geöffneter Ansicht bis zu acht Stunden verlängerbar. Entwürfe und Auswahl bleiben unverändert; keine Abfrage oder Geräteaktion.');
        return ['path' => '/hook/' . $this->DesignerHookName() . '/index.html', 'token' => $token, 'systemId' => $system, 'expires' => $session['expires']];
    }

    private function DesignerInitialSystem(string $token): string
    {
        foreach ($this->DesignerSessions() as $session) if (hash_equals($session['hash'], hash('sha256', $token))) return $session['initialSystem'] ?? '';
        return '';
    }

    private function RenewDesignerAccess(): array
    {
        $failure = $this->DesignerWriteHeaders();
        if ($failure !== null) return $failure;
        $token = $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '';
        return $this->WithTransportLock(function () use ($token): array
        {
            if (!$this->DesignerTokenAllowed($token)) return [403, ['message' => 'Der Designerzugang ist abgelaufen. Bitte über das Modul oder die Kachel erneut öffnen. Offene Eingaben werden nicht gelöscht.']];
            if (trim($this->DesignerRequestBody()) !== '{}') return [400, ['message' => 'Der Zugang konnte nicht verlängert werden. Bitte erneut versuchen.']];
            $sessions = $this->DesignerSessions();
            $expires = null;
            foreach ($sessions as &$session) {
                if (!hash_equals($session['hash'], hash('sha256', $token))) continue;
                $limit = $session['limit'] ?? $session['expires'];
                if ($limit <= $this->Now()) return [403, ['message' => 'Bitte den Designer nach acht Stunden erneut über das Modul oder die Kachel öffnen.']];
                $session['expires'] = min($this->Now() + 900, $limit);
                $expires = $session['expires'];
            }
            unset($session);
            if ($expires === null) return [403, ['message' => 'Der Designerzugang ist inzwischen abgelaufen. Bitte über das Modul oder die Kachel erneut öffnen.']];
            $this->WriteEditorBuffer('DesignerSessions', json_encode($sessions, JSON_THROW_ON_ERROR));
            $this->DebugEvent('AKTION', 'Designerzugang verlängert. Offene Eingaben, laufende Anfragen, Systemauswahl und alle Zugriffsrechte bleiben erhalten; keine Z2M-Anfrage.');
            return [200, ['ok' => true, 'expires' => $expires]];
        });
    }
}
