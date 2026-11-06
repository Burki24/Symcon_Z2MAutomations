<?php

declare(strict_types=1);

namespace Z2MAutomations;

use Throwable;

require_once __DIR__ . '/AutomationDesignerCreation.php';
require_once __DIR__ . '/AutomationDesignerEditing.php';
require_once __DIR__ . '/AutomationDesignerSequences.php';
require_once __DIR__ . '/AutomationDesignerManagement.php';
require_once __DIR__ . '/AutomationDesignerImport.php';

/** Scoped browser access to existing caches and guarded local draft creation. */
trait AutomationDesigner
{
    use AutomationDesignerCreation;
    use AutomationDesignerEditing;
    use AutomationDesignerSequences;
    use AutomationDesignerManagement;
    use AutomationDesignerImport;
    private const DESIGNER_ASSETS = ['index.html' => 'text/html', 'designer.css' => 'text/css', 'layout.js' => 'application/javascript', 'startup.js' => 'application/javascript',
        'reader.js'                               => 'application/javascript', 'creator.js' => 'application/javascript', 'editor.js' => 'application/javascript', 'workspace.js' => 'application/javascript', 'transfer.js' => 'application/javascript', 'runtime.js' => 'application/javascript', 'management.js' => 'application/javascript', 'catalog.js' => 'application/javascript', 'imports.js' => 'application/javascript', 'action-order.js' => 'application/javascript', 'diagnostics.js' => 'application/javascript', 'installation.js' => 'application/javascript', 'identity.js' => 'application/javascript', 'permissions.js' => 'application/javascript'];

    public function OpenAutomationDesigner(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->WithTransportLock(function (): string
        {
            if ($this->WorkspaceMode()) return $this->WorkspaceDesignerOpen();
            if (!$this->RegisterHook($this->DesignerHookName())) {
                $this->DebugEvent('FEHLER', 'Automationsdesigner konnte nicht geöffnet werden: Browserzugang nicht verfügbar.');
                return '';
            }
            $token = bin2hex(random_bytes(32));
            $sessions = $this->DesignerSessions();
            $sessions[] = ['hash' => hash('sha256', $token), 'expires' => $this->Now() + 900, 'scope' => $this->TransportScope(), 'create' => true, 'edit' => true, 'sequences' => true, 'manage' => true, 'receipts' => []];
            $this->WriteEditorBuffer('DesignerSessions', json_encode(array_slice($sessions, -4), JSON_THROW_ON_ERROR));
            $this->DebugEvent('AKTION', 'Automationsdesigner mit 15 Minuten Zugang geöffnet. Neue ausgeschaltete Regeln und Aktionsfolgen lokal erstellen und bearbeiten; keine Abfrage oder Geräteaktion.', ['DauerSekunden' => 900]);
            return 'link:/hook/' . $this->DesignerHookName() . '/index.html#' . $token;
        }));
    }

    public function GetDesignerData(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->WorkspaceMode() ? $this->WorkspaceDesignerData() : $this->WithTransportLock(fn (): string => $this->DesignerData()));
    }

    protected function ProcessHookData(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            // No CORS, query tokens, arbitrary assets or general action endpoint.
            header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; connect-src 'self'; img-src 'self' data:; base-uri 'none'; form-action 'none'; frame-ancestors 'self'");
            header('Referrer-Policy: no-referrer');
            $prefix = '/hook/' . $this->DesignerHookName() . '/';
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $asset = str_starts_with($uri, $prefix) ? substr($uri, strlen($prefix)) : '';
            $types = self::DESIGNER_ASSETS + ['runtime' => 'application/json', 'runtime-status' => 'application/json', 'transfer' => 'application/json', 'transfer-status' => 'application/json', 'select-system' => 'application/json', 'devices' => 'application/json', 'data' => 'application/json', 'create' => 'application/json', 'edit' => 'application/json', 'sequence' => 'application/json', 'manage' => 'application/json', 'catalog-refresh' => 'application/json', 'catalog-status' => 'application/json', 'event-import' => 'application/json', 'diagnostics' => 'application/json', 'diagnostics-status' => 'application/json', 'installation' => 'application/json', 'installation-status' => 'application/json', 'permissions' => 'application/json', 'permissions-status' => 'application/json'];
            if (!isset($types[$asset])) {
                $this->DesignerResponse(404, 'text/plain', 'Diese Designeradresse ist nicht verfügbar.');
                return;
            }
            $method = in_array($asset, ['create', 'edit', 'sequence', 'manage', 'select-system', 'transfer', 'runtime', 'catalog-refresh', 'event-import', 'diagnostics', 'installation', 'permissions'], true) ? 'POST' : 'GET';
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
                header('Allow: ' . $method);
                $this->DesignerResponse(405, 'text/plain', 'Diese Aktion unterstützt die verwendete Aufrufart nicht.');
                return;
            }
            try {
                if ($this->WorkspaceMode() && in_array($asset, ['data', 'devices', 'create', 'edit', 'sequence', 'manage', 'select-system', 'transfer', 'transfer-status', 'runtime', 'runtime-status', 'catalog-refresh', 'catalog-status', 'event-import', 'diagnostics', 'diagnostics-status', 'installation', 'installation-status', 'permissions', 'permissions-status'], true)) {
                    [$status, $result] = $this->WorkspaceDesignerRequest($asset);
                    $this->DesignerResponse($status, 'application/json', json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                    return;
                }
                if (in_array($asset, ['select-system', 'devices', 'transfer', 'transfer-status', 'runtime', 'runtime-status', 'catalog-refresh', 'catalog-status', 'event-import', 'diagnostics', 'diagnostics-status', 'installation', 'installation-status', 'permissions', 'permissions-status'], true)) {
                    $this->DesignerResponse(404, 'text/plain', 'Bitte zuerst Systeme zuordnen.');
                    return;
                }
                if (in_array($asset, ['create', 'edit', 'sequence', 'manage'], true)) {
                    [$status, $result] = match ($asset) {
                        'create' => $this->DesignerCreateRequest(), 'edit' => $this->DesignerEditRequest(), 'sequence' => $this->DesignerSequenceRequest(), 'manage' => $this->DesignerManageRequest()
                    };
                    $this->DesignerResponse($status, 'application/json', json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                    return;
                }
                if ($asset === 'data') {
                    $token = $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '';
                    $data = $this->WithTransportLock(function () use ($token): ?string
                    {
                        if (!$this->DesignerTokenAllowed($token)) {
                            return null;
                        }
                        $body = $this->DesignerData();
                        return $this->DesignerTokenAllowed($token) ? $body : null;
                    });
                    if ($data === null) {
                        $this->DesignerResponse(403, 'text/plain', 'Der Lesezugang fehlt oder ist abgelaufen. Bitte den Designer erneut im Modul öffnen.');
                        return;
                    }
                    $this->DesignerResponse(200, $types[$asset], $data);
                    return;
                }
                $body = $this->VisualizationAsset($asset);
                if ($body === '') {
                    throw new \RuntimeException('Designer asset unavailable.');
                }
                if ($asset === 'index.html') {
                    $this->DebugEvent('AKTION', 'Responsive Designeroberfläche ausgeliefert. Darstellung folgt dem Browser; Verwaltungsbereiche bleiben bis zum Öffnen zugeklappt. Keine Z2M-Anfrage.');
                    $body = str_replace(
                        ['defer src="designer.js"', "connect-src 'none'", '<body>', 'VORSCHAU', 'Klickbarer Oberflächenentwurf',
                            'Beispieldaten · keine Verbindung zu Symcon oder Z2M · Änderungen gelten nur in diesem Fenster.'],
                        ['defer src="startup.js"', "connect-src 'self'", '<body class="module-designer">', 'ENTWURF', 'Modulansicht mit Regelerstellung',
                            'Neue Regeln werden ausgeschaltet im Symcon-Entwurf ergänzt. Übertragen und Aktivieren weiterhin im nativen Formular.'],
                        $body
                    );
                }
                $this->DesignerResponse(200, $types[$asset], $body);
            } catch (Throwable $error) {
                $this->DebugEvent('FEHLER', 'Designeransicht konnte nicht geladen werden. Vorhandene Entwürfe und laufender Betrieb bleiben unverändert.', ['Ausnahmeklasse' => get_class($error)]);
                $this->DesignerResponse(503, 'text/plain', 'Die Ansicht konnte nicht geladen werden. Bitte Moduldateien und Debug prüfen.');
            }
        });
    }

    /** Only immutable, explicitly allowed GET files may wait behind another entry. */
    private function DesignerAssetRequest(): bool
    {
        $prefix = '/hook/' . $this->DesignerHookName() . '/';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && str_starts_with($uri, $prefix)
            && isset(self::DESIGNER_ASSETS[substr($uri, strlen($prefix))]);
    }

    private function DesignerData(): string
    {
        $scope = $this->TransportScope();
        $cached = $this->ReadEditorBuffer('TransportState');
        $state = $cached === '' ? [] : json_decode($cached, true, 32, JSON_THROW_ON_ERROR);
        $state = ($state['scope'] ?? '') === $scope ? $state : [];
        $raw = $this->ReadEditorAttribute('EditorSnapshot');
        $snapshot = $this->workspaceEditorContext['snapshot'] ?? ($raw === '' ? [] : json_decode($raw, true, 32, JSON_THROW_ON_ERROR));
        $snapshot = ($snapshot['scope'] ?? '') === $scope ? $this->EditorSnapshot() : [];
        $draft = $this->ReadEditorDraft();
        $draftCurrent = ($draft['scope'] ?? '') === $scope
            && ($draft['draftChecksum'] ?? '') === hash('sha256', $this->ReadEditorAttribute('YamlDraft'));
        $configuration = $draftCurrent ? ($draft['configuration'] ?? null) : null;
        $devices = [];
        $allDevices = $snapshot['devices'] ?? [];
        $offset = $this->workspaceEditorContext['catalogOffset'] ?? 0;
        $pageDevices = $this->workspaceEditorContext === null ? $allDevices : array_slice($allDevices, $offset, 20, true);
        foreach ($pageDevices as $key => $device) {
            $properties = [];
            $readable = $this->EditorProperties($snapshot, $key, 'Source');
            $writable = $this->EditorProperties($snapshot, $key, 'Target');
            foreach ($device['properties'] ?? [] as $property => $meta) {
                $properties[] = ['id' => $property, 'label' => $this->EditorPropertyLabel($snapshot, $key, $property, isset($writable[$property]) ? 'Target' : 'Source'),
                    'type'            => $meta['type'], 'readable' => isset($readable[$property]), 'writable' => isset($writable[$property]),
                    'values'          => $meta['values'] ?? [], 'actionValues' => $writable[$property]['values'] ?? [], 'valueLabels' => array_map(fn (mixed $value): array => ['value' => $value,
                        'label'                                                                                                                                                         => $this->EditorChoiceValueLabel($snapshot, $key, $property, $value)], $meta['values'] ?? []),
                    'unit' => $meta['unit'] ?? ($device['numeric'][$property] ?? '')]
                    + array_intersect_key($meta, array_flip(['minimum', 'maximum', 'step', 'endpoint', 'sceneEndpoint', 'sceneNames']));
            }
            $devices[] = ['key' => $key, 'name' => $device['name'], 'target' => $device['remoteTarget'] ?? (str_starts_with($key, 'group.')
                ? ['type' => 'group', 'id' => (int) substr($key, 6)] : ['type' => 'device', 'id' => $key]), 'properties' => $properties];
        }
        $extension = $state['extension'] ?? [];
        if ($this->workspaceEditorContext['catalogOnly'] ?? false) {
            $ctx = $this->workspaceEditorContext;
            return json_encode(['devices' => $devices,
                'devicePage'              => ['offset' => $offset, 'total' => count($allDevices), 'hasMore' => $offset + count($devices) < count($allDevices)],
                'workspaceContext'        => ['systemId' => $ctx['id'], 'generation' => $ctx['record']['binding']['generation'], 'fingerprint' => WorkspaceState::Fingerprint($ctx['record'])]], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
        $openFormEdits = $this->DesignerPendingInput();
        $data = ['contractVersion' => 1, 'mode' => 'read', 'module' => ['id' => $this->InstanceID, 'name' => IPS_GetName($this->InstanceID),
            'baseTopic'                                                      => $this->ReadPropertyString('BaseTopic'), 'gateway' => $this->GetParentID()], 'readAt' => $this->Now(),
            'catalogAvailable' => $snapshot !== [], 'catalogLoadedAt' => $snapshot['loadedAt'] ?? null, 'draftAvailable' => $configuration !== null,
            'draftMismatch'    => $draft !== null && !$draftCurrent, 'openFormEdits' => $openFormEdits, 'configuration' => $configuration, 'devices' => $devices,
            'status'           => ['confirmed' => ($state['runtimeConfirmed'] ?? false) === true, 'mqttConnected' => ($state['mqttConnected'] ?? false) === true,
                'executing'                    => ($extension['executing'] ?? false) === true, 'storedRevision' => $extension['storedRevision'] ?? 0,
                'activeRevision'               => $extension['activeRevision'] ?? 0, 'enabledCount' => $extension['enabledCount'] ?? 0],
            'timezones'             => \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC),
            'createContext'         => $this->DesignerCreateContext(), 'createAllowed' => $this->DesignerCreateBlock() === '',
            'createNotice'          => $this->DesignerCreateBlock(), 'editAllowed' => $this->DesignerCreateBlock(false) === '', 'editNotice' => $this->DesignerCreateBlock(false),
            'manageAllowed'         => $this->DesignerCreateBlock(false) === '' && $this->DesignerWriteAccess($_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '', 'manage'),
            'editSections'          => $this->DesignerEditableSections($configuration),
            'sequenceAllowed'       => $this->DesignerCreateBlock(false) === '',
            'sequenceCreateAllowed' => $this->DesignerCreateBlock(false) === '' && count($configuration['sequences'] ?? []) < 32,
            'sequenceNotice'        => $this->DesignerCreateBlock(false), 'sequenceSections' => $this->DesignerSequenceSections($configuration)];
        if ($this->workspaceEditorContext !== null) {
            $ctx = $this->workspaceEditorContext;
            $data['module']['baseTopic'] = $ctx['record']['binding']['baseTopic'];
            $data['module']['gateway'] = $ctx['record']['binding']['mqttParent'];
            $data['workspace'] = json_decode($this->WorkspaceOverview($ctx['workspace']), true, 32, JSON_THROW_ON_ERROR);
            $data['workspaceContext'] = ['systemId' => $ctx['id'], 'generation' => $ctx['record']['binding']['generation'], 'fingerprint' => WorkspaceState::Fingerprint($ctx['record'])];
            $data['catalogAvailable'] = $ctx['catalogAvailable'];
            $data['devicePage'] = ['offset' => $offset, 'total' => count($allDevices), 'hasMore' => $offset + count($devices) < count($allDevices)];
            $data['transfer'] = $this->WorkspaceTransferView($ctx['record']);
            $data['runtime'] = $this->WorkspaceRuntimeView($ctx['record']);
            $data['catalogRefresh'] = $this->WorkspaceCatalogView($ctx['record']);
            if (!($ctx['catalogOnly'] ?? false)) {
                $data['permissionsAllowed'] = $this->DesignerWriteAccess($_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '', 'permissions');
                $data['installationAllowed'] = $this->DesignerWriteAccess($_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '', 'installation');
                $data['diagnosticsAllowed'] = $this->DesignerWriteAccess($_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '', 'diagnostics');
                $data['eventImport'] = $this->EventImportView();
                $data['eventImportAllowed'] = $this->DesignerWriteAccess($_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '', 'imports') && $this->WorkspaceConnectionCurrent($ctx['record']['binding']);
            }
            $data['status'] = ['confirmed' => $ctx['info']['state']['runtimeConfirmed'] ?? false,
                'mqttConnected'            => $ctx['info']['state']['mqttConnected'] ?? false, 'executing' => $ctx['info']['state']['extension']['executing'] ?? false,
                'storedRevision'           => $ctx['info']['state']['extension']['storedRevision'] ?? 0, 'activeRevision' => $ctx['info']['state']['extension']['activeRevision'] ?? 0];
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (strlen($json) > 2097152) {
            throw new \RuntimeException('Designer response exceeds size limit.');
        }
        $this->DebugEvent(
            'SENDEN',
            'Vorhandene Daten für die lesende Designeransicht zusammengestellt; Entwurf und Z2M unverändert.',
            ['Geräte'              => count($devices), 'Regeln' => count($configuration['automations'] ?? []), 'Aktionsfolgen' => count($configuration['sequences'] ?? []),
                'EntwurfVerfügbar' => $configuration !== null, 'GerätedatenVerfügbar' => $snapshot !== [], 'AusgabeBytes' => strlen($json)]
        );
        return $json;
    }

    private function DesignerHookName(): string
    {
        return 'Z2MAutomationsDesigner' . $this->InstanceID;
    }

    private function DesignerSessions(): array
    {
        $text = $this->ReadEditorBuffer('DesignerSessions');
        $sessions = $text === '' ? [] : json_decode($text, true, 8, JSON_THROW_ON_ERROR);
        return array_values(array_filter($sessions, fn (array $session): bool => $session['expires'] > $this->Now() && $session['scope'] === $this->DesignerAccessScope()));
    }

    private function DesignerTokenAllowed(mixed $token, bool $create = false): bool
    {
        if (!is_string($token) || preg_match('/\A[a-f0-9]{64}\z/D', $token) !== 1) {
            return false;
        }
        foreach ($this->DesignerSessions() as $session) {
            if (hash_equals($session['hash'], hash('sha256', $token)) && (!$create || ($session['create'] ?? false))) {
                return true;
            }
        }
        return false;
    }

    private function DesignerResponse(int $status, string $type, string $body): void
    {
        $this->DebugEvent($status >= 400 ? 'FEHLER' : 'SENDEN', 'Designeraufruf beantwortet.', ['HTTP-Status' => $status, 'AusgabeBytes' => strlen($body)]);
        $this->SendResponse($status, $type, $body);
    }
}
