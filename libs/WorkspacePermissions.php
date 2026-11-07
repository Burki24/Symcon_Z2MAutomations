<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;

/** Directed, explicitly confirmed local grants. Never sends MQTT or changes another document. */
trait WorkspacePermissions
{
    private function WorkspacePermissionsRequest(bool $write): array
    {
        $token = $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '';
        if (!$this->DesignerWriteAccess($token, 'permissions')) {
            $this->DebugEvent('FEHLER', 'Die Designersitzung erlaubt keine Partnerfreigaben. Bitte den Designer erneut im Modul öffnen. Es wurde nichts geändert.');
            return [403, ['message' => 'Bitte den Designer für die Partnerfreigaben erneut im Modul öffnen.']];
        }
        $system = $_SERVER['HTTP_X_Z2MA_SYSTEM_ID'] ?? '';
        $generation = $_SERVER['HTTP_X_Z2MA_GENERATION'] ?? '';
        if (!SystemContract::SystemID($system) || !is_string($generation) || preg_match('/\A[1-9][0-9]{0,15}\z/D', $generation) !== 1) throw new RuntimeException('invalid_request');
        if (!$write) {
            $state = $this->ReadWorkspace();
            $record = $this->WorkspaceRecord($state, $system, (int) $generation);
            $view = $this->WorkspacePermissionsView($state, $record);
            $this->DebugEvent('EMPFANG', 'Gerichtete Partnerfreigaben des empfangenden Systems nur gelesen. Keine Freigabe geändert und keine Z2M-Anfrage gestartet.', [
                'Systembezug' => $system, 'Empfangsfreigaben' => count($view['peers']), 'Auswahlmöglichkeiten' => count($view['choices']), 'Bearbeitbar' => $view['canEdit'], 'Sperrgrund' => $view['blocked']]);
            return [200, ['ok' => true, 'permissions' => $view]];
        }
        $body = $this->DecodeWorkspaceInput($this->DesignerRequestBody(), 4096);
        if (!$this->DesignerEditShape($body, ['requestId', 'context', 'peers', 'confirmed']) || !RuntimeDebugContract::UUID($body['requestId'])
            || !ExtensionContract::Checksum($body['context']) || $body['confirmed'] !== true || !is_array($body['peers'])
            || !array_is_list($body['peers']) || count($body['peers']) > 8) throw new RuntimeException('invalid_request');
        foreach ($body['peers'] as $peer) if (!SystemContract::Topic($peer)) throw new RuntimeException('invalid_request');
        if (count(array_unique($body['peers'])) !== count($body['peers'])) throw new RuntimeException('invalid_request');
        $fingerprint = $_SERVER['HTTP_X_Z2MA_FINGERPRINT'] ?? '';
        if (!ExtensionContract::Checksum($fingerprint)) throw new RuntimeException('invalid_request');
        $operation = $this->WorkspaceOperation(str_replace('-', '', $body['requestId']), [$system, (int) $generation, $fingerprint, $body]);
        $response = $this->WithWorkspaceLock(function () use ($system, $generation, $fingerprint, $body, $operation): string
        {
            $state = $this->ReadWorkspace();
            $replay = $this->WorkspaceReplay($state, $operation);
            if ($replay !== null) {
                $this->DebugEvent('AKTION', 'Bereits bestätigten Freigabeauftrag wiedererkannt. Vorhandene Rückmeldung zurückgegeben; kein Entwurf erneut geschrieben.', ['Systembezug' => $system]);
                return $replay;
            }
            $record = $this->WorkspaceRecord($state, $system, (int) $generation, $fingerprint);
            $view = $this->WorkspacePermissionsView($state, $record);
            if (!hash_equals($view['context'], $body['context'])) throw new RuntimeException('permissions_stale');
            if (!$view['canEdit']) throw new RuntimeException($view['blocked']);
            if (array_diff($body['peers'], array_column($view['choices'], 'topic')) !== []) throw new RuntimeException('invalid_request');
            $before = WorkspaceState::Permissions($record['data'])['mesh_peers'] ?? [];
            $added = array_values(array_diff($body['peers'], $before));
            $removed = array_values(array_diff($before, $body['peers']));
            $changed = $added !== [] || $removed !== [];
            if ($changed) {
                $editor = $record['data']['document']['editorDraft'];
                // Retained entries keep their order; an unchanged set preserves exact YAML bytes.
                $editor['configuration']['mesh_peers'] = array_merge(array_values(array_intersect($before, $body['peers'])), $added);
                $editor['configuration']['revision'] = $editor['baseRevision'] + 1;
                $yaml = json_encode($editor['configuration'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
                $editor['draftChecksum'] = hash('sha256', $yaml);
                $record['data']['document'] = ['yaml' => $yaml, 'editorDraft' => $editor];
                if (WorkspaceState::DataError($record['data']) !== '') throw new RuntimeException('invalid_data');
                $state['systems'][$system] = $record;
            }
            $result = $this->CommitWorkspace($state, $operation, ['permissionsSaved' => true, 'systemId' => $system, 'generation' => (int) $generation,
                'fingerprint'                                                        => WorkspaceState::Fingerprint($record), 'changed' => $changed, 'peerCount' => count($body['peers']),
                'notice'                                                             => $changed ? 'Partnerfreigaben im lokalen Entwurf übernommen. Dieses empfangende System anschließend in Z2M prüfen, speichern und aktivieren. Der laufende Betrieb ist unverändert.'
                    : 'Die Partnerfreigaben sind unverändert. Der Entwurf wurde nicht neu geschrieben.']);
            $this->DebugEvent('AKTION', $changed
                ? 'Gerichtete Empfangsfreigaben ausdrücklich bestätigt und nur im Zielentwurf übernommen. Andere Regeln, Aktionsfolgen, Auswahl und Partnerentwürfe bleiben erhalten. Keine Übertragung oder Aktivierung in Z2M.'
                : 'Unveränderte Partnerauswahl bestätigt; YAML und Entwurf unverändert erhalten.', ['Systembezug' => $system, 'NeuFreigegeben' => count($added), 'Entzogen' => count($removed)]);
            return $result;
        });
        return [200, json_decode($response, true, 16, JSON_THROW_ON_ERROR)];
    }

    private function WorkspacePermissionsView(array $state, array $record): array
    {
        $peers = WorkspaceState::Permissions($record['data'])['mesh_peers'] ?? [];
        $choices = [];
        $bindingsCurrent = $this->WorkspaceConnectionCurrent($record['binding']);
        foreach ($state['systems'] as $id => $source) {
            if ($id === $record['binding']['systemId']) continue;
            $bindingsCurrent = $this->WorkspaceConnectionCurrent($source['binding']) && $bindingsCurrent;
            $topic = $source['binding']['baseTopic'];
            $affected = $this->WorkspacePermissionUsers($source, $record['binding']['baseTopic']);
            $choices[] = ['systemId' => $id, 'topic' => $topic, 'name' => $source['name'], 'assigned' => true, 'selected' => in_array($topic, $peers, true),
                'affectedCount'      => count($affected), 'affected' => array_slice($affected, 0, 20)];
        }
        foreach (array_diff($peers, array_column($choices, 'topic')) as $topic) $choices[] = [
            'systemId' => '', 'topic' => $topic, 'name' => $topic === $record['binding']['baseTopic'] ? 'Bisheriger Eintrag für das eigene System' : 'Bisherige Freigabe für ein nicht zugeordnetes System',
            'assigned' => false, 'selected' => true, 'affectedCount' => 0, 'affected' => []];
        $blocked = !$bindingsCurrent ? 'binding_changed' : ($record['data']['document'] === null ? 'no_document' : (WorkspaceState::Dirty($record) ? 'open_input' : ''));
        return ['systemId' => $record['binding']['systemId'], 'generation' => $record['binding']['generation'], 'fingerprint' => WorkspaceState::Fingerprint($record),
            'context'      => hash('sha256', json_encode([$record['binding'], $this->OwnershipMarker(), WorkspaceState::Fingerprint($state)], WorkspaceState::JSON_FLAGS)),
            'name'         => $record['name'], 'baseTopic' => $record['binding']['baseTopic'], 'choices' => $choices, 'peers' => $peers,
            'canEdit'      => $blocked === '', 'blocked' => $blocked, 'message' => $blocked === '' ? 'Dieses System darf Befehle von den angehakten Systemen empfangen.' : WorkspaceState::MESSAGES[$blocked],
            'notice'       => 'Die Freigabe gilt nur in dieser Richtung. Änderungen gelten zuerst nur im lokalen Entwurf. Danach dieses empfangende System in Z2M prüfen, speichern und aktivieren. Eine nötige MQTT-Verbindung zwischen den Systemen wird hier nicht eingerichtet.'];
    }

    private function WorkspacePermissionUsers(array $source, string $target): array
    {
        $configuration = $source['data']['document']['editorDraft']['configuration'] ?? null;
        $users = [];
        foreach ($configuration['automations'] ?? [] as $rule) {
            foreach (SequencePlan::Expand($configuration, $rule['actions']) as $step) {
                if (($step['action']['target']['system'] ?? '') !== $target) continue;
                $users[] = ['id' => $rule['id'], 'name' => $rule['name'], 'enabled' => $rule['enabled']];
                break;
            }
        }
        return $users;
    }
}
