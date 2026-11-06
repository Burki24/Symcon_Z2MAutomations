<?php

declare(strict_types=1);

namespace Z2MAutomations;

require_once __DIR__ . '/helper/IPSViewHTMLPageHelper.php';

/** Optional HTMLBox access to the same designer; no second editor or MQTT transport. */
trait DesignerIPSView
{
    use \Burki24\SymconModuleHelper\IPSViewHTMLPageHelper;

    /** Explicit regeneration replaces the entry grant and revokes previous IPSView sessions. */
    public function GetIPSViewHTML(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->WithTransportLock(function (): string
        {
            $this->WriteAttributeString('IPSViewAccessHash', '');
            $this->WriteAttributeString('IPSViewAccessScope', '');
            if (!$this->IsIPSViewHTMLPageEnabled()) return '';
            $origin = $this->DesignerIPSViewOrigin();
            $grant = '';
            if ($origin !== null && $this->WorkspaceMode()) {
                if (!$this->RegisterHook($this->DesignerHookName())) throw new \RuntimeException('hook_unavailable');
                $grant = bin2hex(random_bytes(32));
                $this->WriteAttributeString('IPSViewAccessHash', hash('sha256', $grant));
                $this->WriteAttributeString('IPSViewAccessScope', $this->DesignerIPSViewScope());
                $this->DebugEvent('AKTION', 'IPSView-Einstiegszugang erneuert. Bisherige IPSView-Zugänge und Sitzungen sind gesperrt. Die neue Ansicht öffnet denselben Designer mit Systemauswahl; Entwürfe und laufender Betrieb bleiben unverändert.');
            } else {
                $this->DebugEvent('FEHLER', $origin === null
                    ? 'IPSView-Ansicht noch nicht nutzbar: Als Symcon-Serveradresse nur http:// oder https:// mit Rechnername und optionalem Port eintragen, ohne Zugangsdaten oder zusätzlichen Pfad.'
                    : 'IPSView-Ansicht noch nicht nutzbar: Bitte zuerst die Z2M-Systeme in der Zentrale zuordnen.');
            }
            $html = $this->RenderVisualizationHTMLPage(true, [
                'templateAsset' => 'ipsview-loader.html', 'styleAsset' => 'ipsview-loader.css', 'scriptAsset' => 'ipsview-loader.js',
                'language'      => 'de', 'title' => 'Z2MAutomations · IPSView',
                'viewport'      => 'width=device-width, initial-scale=1',
                'runtime'       => ['origin' => $origin ?? '', 'path' => '/hook/' . $this->DesignerHookName() . '/ipsview.html', 'grant' => $grant,
                    'message'                => $origin === null ? 'Bitte die Symcon-Serveradresse im Modul prüfen und die Einstellungen übernehmen.'
                        : 'Bitte zuerst Z2M-Systeme in der Zentrale zuordnen und danach die IPSView-Ausgabe neu erzeugen.'],
                'replacements' => ['{{HTTP_ORIGIN_SCRIPT}}' => $this->VisualizationHTTPOriginScript()]
            ]);
            if ($html === '') {
                $this->WriteAttributeString('IPSViewAccessHash', '');
                $this->WriteAttributeString('IPSViewAccessScope', '');
                $this->DebugEvent('FEHLER', 'IPSView-HTML konnte nicht erstellt werden, weil Moduldateien fehlen. Der neue Zugang wurde gesperrt; bitte das Modulupdate prüfen.');
            }
            return $html;
        }));
    }

    private function RegisterDesignerIPSView(): void
    {
        $this->RegisterIPSViewHTMLPageProperties();
        $this->RegisterPropertyString('IPSViewServerURL', '');
        $this->RegisterAttributeString('IPSViewAccessHash', '');
        $this->RegisterAttributeString('IPSViewAccessScope', '');
    }

    private function ConfigureDesignerIPSView(): void
    {
        $this->MaintainIPSViewHTMLVariable('IPSViewDesigner', 'Automationsdesigner für IPSView', 300, '', false);
        if (!$this->IsIPSViewHTMLPageEnabled()) {
            $this->WriteAttributeString('IPSViewAccessHash', '');
            $this->WriteAttributeString('IPSViewAccessScope', '');
            $this->DebugEvent('AKTION', 'IPSView-Ausgabe ausgeschaltet. Vorhandene HTML-Variable bleibt erhalten; ihr Zugang und ihre bisherigen Sitzungen sind gesperrt. Entwürfe und Z2M bleiben unverändert.');
            return;
        }
        $html = $this->GetIPSViewHTML();
        if ($html === '' || !$this->UpdateIPSViewHTMLVariable('IPSViewDesigner', $html)) {
            $this->DebugEvent('FEHLER', 'IPSView-HTML konnte nicht in die Ausgabevariable geschrieben werden. Bitte die Variable und das Modul-Debug prüfen.');
            return;
        }
        $this->DebugEvent('SENDEN', 'Vollständige IPSView-Ansicht in die HTML-Variable geschrieben. In IPSView ein HTMLBox-Element mit dieser Variable verbinden; keine Z2M-Anfrage.', ['AusgabeBytes' => strlen($html)]);
    }

    private function DesignerIPSViewForm(): array
    {
        return ['type' => 'ExpansionPanel', 'caption' => 'IPSView-Ansicht', 'items' => array_merge($this->IPSViewHTMLPageFormItems(
            'Den vollständigen Designer in IPSView anzeigen. Nach dem Einschalten und Übernehmen die Variable „Automationsdesigner für IPSView“ mit einem HTMLBox-Element verbinden. Beim Ausschalten bleibt die Variable erhalten; der Zugriff wird gesperrt.'
        ), [
            ['type' => 'ValidationTextBox', 'name' => 'IPSViewServerURL', 'caption' => 'Symcon-Serveradresse für IPSView (optional)'],
            ['type' => 'Label', 'caption' => 'Zum Beispiel https://symcon.example:3777, ohne /console/ oder weitere Pfade. Wenn IPSView den Server nicht selbst erkennt, hier die aus dem IPSView-Client erreichbare Adresse eintragen.'],
            ['type' => 'Label', 'caption' => 'Wer die HTML-Variable lesen kann, kann über den Designer die zugeordneten Systeme verwalten. Diese Variable deshalb nur berechtigten Benutzern zugänglich machen.'],
            ['type' => 'Label', 'caption' => '„IPSView-HTML neu generieren“ ersetzt den Einstiegszugang und sperrt bisherige IPSView-Sitzungen. Danach die Ansicht in IPSView neu öffnen. „Zugang verlängern“ im geöffneten Designer erhält dagegen offene Eingaben.']
        ])];
    }

    private function DesignerIPSViewOrigin(): ?string
    {
        $value = trim($this->ReadPropertyString('IPSViewServerURL'));
        if ($value === '') return '';
        $value = rtrim($value, '/');
        $url = parse_url($value);
        return is_array($url) && in_array($url['scheme'] ?? '', ['http', 'https'], true) && !empty($url['host'])
            && !isset($url['user']) && !isset($url['pass'])
            && !isset($url['query']) && !isset($url['fragment']) && ($url['path'] ?? '') === ''
            && !preg_match('/[\x00-\x20\x7f]/', $value) ? $value : null;
    }

    private function DesignerIPSViewScope(): string
    {
        return hash('sha256', json_encode([$this->DesignerAccessScope(), $this->DesignerIPSViewOrigin()], JSON_THROW_ON_ERROR));
    }

    private function DesignerIPSViewGrantAllowed(mixed $hash): bool
    {
        return $this->IsIPSViewHTMLPageEnabled() && $this->WorkspaceMode() && $this->DesignerIPSViewOrigin() !== null
            && is_string($hash) && preg_match('/\A[a-f0-9]{64}\z/D', $hash) === 1
            && hash_equals($this->ReadAttributeString('IPSViewAccessHash'), $hash)
            && hash_equals($this->ReadAttributeString('IPSViewAccessScope'), $this->DesignerIPSViewScope());
    }

    /** The HTML variable's entry grant only opens short-lived sessions; it cannot edit directly. */
    private function DesignerIPSViewAccessRequest(): array
    {
        $failure = $this->DesignerWriteHeaders();
        if ($failure !== null) return $failure;
        return $this->WithTransportLock(function (): array
        {
            $grant = $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '';
            $hash = is_string($grant) && preg_match('/\A[a-f0-9]{64}\z/D', $grant) === 1 ? hash('sha256', $grant) : '';
            if (!$this->DesignerIPSViewGrantAllowed($hash)) {
                $this->DebugEvent('FEHLER', 'IPSView-Zugang abgelehnt: Ausgabe ausgeschaltet, Zugang ersetzt oder Systemzuordnung geändert. Bitte die IPSView-Ausgabe im Modul neu erzeugen; keine Änderung an Entwürfen oder Z2M.');
                return [403, ['message' => 'Der IPSView-Zugang ist nicht mehr gültig. Bitte im Modul die IPSView-Ausgabe neu erzeugen und die Ansicht in IPSView neu öffnen.']];
            }
            if (trim($this->DesignerRequestBody()) !== '{}') return [400, ['message' => 'Die IPSView-Anfrage konnte nicht gelesen werden. Bitte die Ansicht erneut öffnen.']];
            $selected = $this->ReadWorkspace()['selectedSystem'];
            $access = $this->IssueDesignerAccess($selected, $hash);
            $this->DebugEvent('SENDEN', 'IPSView-Einstieg bestätigt und befristete Designersitzung erstellt. Systemauswahl und Status werden aus vorhandenen Moduldaten gelesen; keine Z2M-Anfrage.');
            return [200, ['ok' => true, 'token' => $access['token'], 'expires' => $access['expires']]];
        });
    }
}
