# ADR 0021: Lesender Automationsdesigner im Browser

- Status: angenommen für den ersten Integrationsschritt
- Datum: 03.11.2026

Der Folgeschritt [ADR 0022](0022-browser-create-disabled-rule.md) ergänzt eine
feste Route für lokale ausgeschaltete Regeln. Die hier beschriebene erste
Leseanbindung bleibt die Grundlage für GET; neue Zugänge erlauben zusätzlich
die begrenzte Regelerstellung. Alte Lesezugänge erhalten dieses Recht nicht.

## Anlass

Die bestätigte eigene Bedienvorschau soll vorhandene Regeln und Gerätedaten
übersichtlicher darstellen. In diesem Schritt werden keine Entwürfe bearbeitet,
in Z2M gespeichert oder ausgeführt. Native Formulare und Automationslaufzeit
bleiben der vorhandene Weg.

## Entscheidung

Ein Formularbutton ruft OpenAutomationDesigner auf. Er liefert einen relativen
Browserlink über den dokumentierten `link:`-Mechanismus des
[Formularbuttons](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/konfigurationsformulare/button/).
Ein eigener Konsolentab wie beim IPSView-Designer ist damit nicht zugesagt.
Keine Kachel, WebContent-Variable oder IPSView-Abhängigkeit wird eingerichtet.

Der flüchtige instanzbezogene WebHook wird über
[RegisterHook](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/registerhook/)
registriert und durch ProcessHookData beantwortet. Ziel bleibt Symcon 9.0.
VisualizationAssetHelper lädt die kanonischen Modulassets; HttpResponseHelper
setzt die gemeinsamen Antwortheader. Beide werden unverändert als zentrale
Bundles samt Manifest und Subscription eingebunden. IPSViewHTMLPageHelper wird
hier nicht verwendet, da weder HTML-SDK-Kachel noch IPSView-Ausgabe vorliegt.

Die HTML-Hülle und zwei ausdrücklich freigegebene Assets enthalten keine
Installationsdaten. Nur der Datenabruf benötigt einen zufälligen 256-Bit-Zugang.
Der Link übergibt ihn im Fragment, der Browser sendet ihn ausschließlich im
Header X-Z2MA-Read-Token zum selben Ursprung. Serverseitig liegen nur Hash,
ursprüngliches Ende und Verbindungskontext im flüchtigen Buffer. Laufzeit 15
Minuten, höchstens vier parallele Zugänge pro Modul; Aktualisieren verlängert
nichts. Neue Einstellungen, Kontextwechsel oder Modulneuladen entwerten alte
Zugänge. Keine Token in Query, HTTP-Antwortdaten, Debug oder Repository.

Nur GET und eine feste Routenliste sind erlaubt. Keine generische Aktion,
Anfragetext-Verarbeitung oder freie Dateiausgabe. Kein CORS; no-store,
no-referrer, nosniff und eine CSP mit ausschließlich lokalen Assets und Abrufen.
Die Browseransicht hat keine Speicher-/Aktivierungs-/Stopp-/Schaltbedienelemente.

GetDesignerData erzeugt einen versionierten Lesevertrag aus vorhandenen Caches.
Es verwendet weder RefreshState/SaveState noch MQTT, verändert keine Attribute
und übernimmt keine offenen Formulareingaben. Gateway-/Topic-Kontext wird über
dieselbe TransportScope-Methode wie der MQTT-Pfad verglichen. Falscher Kontext,
fehlender Katalog und vom Editor abweichende YAML werden sichtbar als nicht
verfügbar gemeldet; keine Beispiele oder alten Regeln als Ersatz.

Regeln und Folgen werden vollständig aus dem vorhandenen Editorentwurf gelesen.
Katalogmetadaten verwenden dieselben Geräte-, Gruppen-, Szenen- und Partnerpfade
wie das native Formular. Lesbarkeit und aktuelle Symcon-Schaltrechte bleiben
getrennt. Native offene Eingaben werden nur als ausstehend gekennzeichnet;
Inhalt und Auswahl werden nicht exportiert. Die Antwort ist auf 2 MiB begrenzt.
Status bedeutet ausdrücklich den zuletzt im Modul bestätigten Stand, keine
neue Live-Prüfung. Die Bedienvorschau bleibt als getrennter Offline-Modus erhalten;
ihre HTML-/CSS-Grundlage liegt jetzt kanonisch unter dem Modul.

Öffnen, Datenbereitstellung, HTTP-Antworten und Ablehnungen nutzen den vorhandenen
DebugFlowHelper. Protokolliert werden Gründe, Mengen und Byteumfang, keine
Regelnamen, Werte, Nutzdaten oder Zugangslinks.

## Folgen und Grenzen

Ergänzung: Der native Modulbutton RefreshDeviceCatalog verwendet inzwischen
eine getrennte status/catalog-Abfrage ohne Entwurfsübernahme. Dies erweitert
den GET-Browserzugang nicht: „Ansicht aktualisieren“ liest weiterhin nur Caches.
Die neue optionale Antwortangabe catalogLoadedAt bezeichnet den erfolgreichen
Katalogabruf dieses Moduls, nicht die letzte Browseranzeige oder Messwerterfassung.
Damit kann auch bei einem unpassenden alten Entwurf der Katalog gefahrlos
erneuert werden. Partnerdaten werden im jeweils zuständigen Modul aktualisiert.

- Kein zweiter Regel- oder YAML-Speicher, keine neue Z2M-Operation und keine
  Laufzeitänderung. Ein Modulupdate genügt; kein Erweiterungsupdate erforderlich.
- Die erste lesende HTML-Oberfläche ist deutsch. Eine vollständige lokalisierte
  Bearbeitungsoberfläche und deren Datenänderungsvertrag gehören zum Folgeschritt.
- Nach dem Laden bestehende Entwürfe weiterhin im nativen Formular verwalten.
  Ein fehlender passender Cache wird hier bewusst nicht automatisch nachgeladen,
  da das vorhandene Laden den Editorentwurf ersetzen kann.
- Ein Zugang ist eine kurzlebige Leseberechtigung für diese Modulinstanz. Der
  normale Symcon-Zugang und sichere Erreichbarkeit des Webservers bleiben nötig.
- Test-Symcon und Z2M können dieselben produktiven Geräte verwenden. Die
  Browserprüfung darf deshalb keine Testregeln speichern oder aktivieren.
- Native Desktopkonsole, Fernzugriff, TLS-/Proxy-Konfigurationen und andere
  Browser benötigen eigene Praxisnachweise. Die direkte HTTP-Prüfung ersetzt
  keinen Klicknachweis in der nativen Konsole.
