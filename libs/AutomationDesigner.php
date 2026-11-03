<?php

declare(strict_types=1);

namespace Z2MAutomations;

use Throwable;

require_once __DIR__ . '/AutomationDesignerCreation.php';

/** Scoped browser access to existing caches and guarded local draft creation. */
trait AutomationDesigner
{
    use AutomationDesignerCreation;
    public function OpenAutomationDesigner(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->WithTransportLock(function (): string
        {
            if (!$this->RegisterHook($this->DesignerHookName())) {
                $this->DebugEvent('FEHLER', 'Automationsdesigner konnte nicht geöffnet werden: Browserzugang nicht verfügbar.');
                return '';
            }
            $token = bin2hex(random_bytes(32));
            $sessions = $this->DesignerSessions();
            $sessions[] = ['hash' => hash('sha256', $token), 'expires' => $this->Now() + 900, 'scope' => $this->TransportScope(), 'create' => true, 'receipts' => []];
            $this->SetBuffer('DesignerSessions', json_encode(array_slice($sessions, -4), JSON_THROW_ON_ERROR));
            $this->DebugEvent('AKTION', 'Automationsdesigner mit 15 Minuten Zugang geöffnet. Neue ausgeschaltete Regeln können lokal ergänzt werden; keine Abfrage oder Geräteaktion.', ['DauerSekunden' => 900]);
            return 'link:/hook/' . $this->DesignerHookName() . '/index.html#' . $token;
        }));
    }

    public function GetDesignerData(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->WithTransportLock(fn (): string => $this->DesignerData()));
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
            $types = ['index.html' => 'text/html', 'designer.css' => 'text/css', 'reader.js' => 'application/javascript', 'creator.js' => 'application/javascript', 'data' => 'application/json', 'create' => 'application/json'];
            if (!isset($types[$asset])) {
                $this->DesignerResponse(404, 'text/plain', 'Diese Designeradresse ist nicht verfügbar.');
                return;
            }
            $method = $asset === 'create' ? 'POST' : 'GET';
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
                header('Allow: ' . $method);
                $this->DesignerResponse(405, 'text/plain', 'Diese Aktion unterstützt die verwendete Aufrufart nicht.');
                return;
            }
            try {
                if ($asset === 'create') {
                    [$status, $result] = $this->DesignerCreateRequest();
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
                    $body = str_replace(
                        ['defer src="designer.js"', "connect-src 'none'", '<body>', 'VORSCHAU', 'Klickbarer Oberflächenentwurf',
                            'Beispieldaten · keine Verbindung zu Symcon oder Z2M · Änderungen gelten nur in diesem Fenster.'],
                        ['type="module" src="reader.js"', "connect-src 'self'", '<body class="module-designer">', 'ENTWURF', 'Modulansicht mit Regelerstellung',
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

    private function DesignerData(): string
    {
        $scope = $this->TransportScope();
        $cached = $this->GetBuffer('TransportState');
        $state = $cached === '' ? [] : json_decode($cached, true, 32, JSON_THROW_ON_ERROR);
        $state = ($state['scope'] ?? '') === $scope ? $state : [];
        $raw = $this->ReadAttributeString('EditorSnapshot');
        $snapshot = $raw === '' ? [] : json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        $snapshot = ($snapshot['scope'] ?? '') === $scope ? $this->EditorSnapshot() : [];
        $draft = $this->ReadEditorDraft();
        $draftCurrent = ($draft['scope'] ?? '') === $scope
            && ($draft['draftChecksum'] ?? '') === hash('sha256', $this->ReadAttributeString('YamlDraft'));
        $configuration = $draftCurrent ? ($draft['configuration'] ?? null) : null;
        $devices = [];
        foreach ($snapshot['devices'] ?? [] as $key => $device) {
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
        $openFormEdits = $this->DesignerPendingInput();
        $data = ['contractVersion' => 1, 'mode' => 'read', 'module' => ['id' => $this->InstanceID, 'name' => IPS_GetName($this->InstanceID),
            'baseTopic'                                                      => $this->ReadPropertyString('BaseTopic'), 'gateway' => $this->GetParentID()], 'readAt' => $this->Now(),
            'catalogAvailable' => $snapshot !== [], 'catalogLoadedAt' => $snapshot['loadedAt'] ?? null, 'draftAvailable' => $configuration !== null,
            'draftMismatch'    => $draft !== null && !$draftCurrent, 'openFormEdits' => $openFormEdits, 'configuration' => $configuration, 'devices' => $devices,
            'status'           => ['confirmed' => ($state['runtimeConfirmed'] ?? false) === true, 'mqttConnected' => ($state['mqttConnected'] ?? false) === true,
                'executing'                    => ($extension['executing'] ?? false) === true, 'storedRevision' => $extension['storedRevision'] ?? 0,
                'activeRevision'               => $extension['activeRevision'] ?? 0, 'enabledCount' => $extension['enabledCount'] ?? 0],
            'createContext' => $this->DesignerCreateContext(), 'createAllowed' => $this->DesignerCreateBlock() === '',
            'createNotice'  => $this->DesignerCreateBlock()];
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
        $text = $this->GetBuffer('DesignerSessions');
        $sessions = $text === '' ? [] : json_decode($text, true, 8, JSON_THROW_ON_ERROR);
        return array_values(array_filter($sessions, fn (array $session): bool => $session['expires'] > $this->Now() && $session['scope'] === $this->TransportScope()));
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
