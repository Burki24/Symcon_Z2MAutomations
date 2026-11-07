# Erweiterungsprotokoll Version 1

## Erweiterungs-Buildkennung

Statusantworten ergänzen `extension_build` als 64-stellige kleingeschriebene
SHA-256-Hexkennung des erzeugten eigenen Codes einschließlich eingebettetem
Schema und Debugkatalog. Die Kennung gehört zum geladenen Artefakt; sie wird
bei dessen Erzeugung deterministisch eingebettet. Versionsnummer, Protokoll und
YAML-Identität bleiben unverändert. Ältere Antworten ohne dieses Feld bleiben
gültig; ein vorhandener ungültiger Wert wird abgewiesen. Im Modul ergibt die
Gleichheit mit dem eigenen mitgelieferten Artefakt den Updatehinweis. Keine
Installation oder Aktivierung durch den Vergleich. Siehe [ADR 0016](adr/0016-content-based-extension-build.md).

## Wiederverwendbare Aktionsfolgen

Der Status ergänzt additiv `sequence_version: 1`. Das optionale Schema-1-Feld
`sequences` wird zusammen mit `automations` über bestehende validate/store/editor-
Operationen verarbeitet. Keine neue Datei oder separate Aktivierung. Eindeutige
Definitions-IDs, vorhandene Referenzen, maximal 16 aufgelöste Schritte und die
addierte erste Wartezeit werden semantisch geprüft. Feste Diagnoseursachen sind
`sequence_duplicate`, `sequence_missing`, `sequence_nested`, `sequence_limit`
und `sequence_delay`; die Position bezeichnet Definition oder aufrufenden Schritt.

Debug-Records und Mesh-`prepare.trace` ergänzen optional `sequence` (1–32) und
`sequence_step` (1–16). `step` bleibt die Position im konkreten Ausführungsplan.
Die Gegenstelle prüft weiterhin ausschließlich einzelne konkrete Aktionen.
Für vollständige Folgebezüge auch die ausführenden Partner-Erweiterungen
aktualisieren. Siehe [YAML-Schema](YAML_SCHEMA.md#wiederverwendbare-aktionsfolgen) und ADR 0015.

## Zeitlich begrenzte Ablaufdiagnose (debug_version 1)

Der Status ergänzt additiv `debug_version: 1`. Ohne diese Fähigkeit bietet
Symcon keine bestätigte Aufzeichnung an. Anfrage an `request/debug`:
`{protocol_version: 1, transaction, session, client, seconds}` mit exakt diesen
Feldern. `session` ist die aktuelle Erweiterungssitzung, `client` eine eigene
zufällige UUID je Aufzeichnung/Instanz, `seconds` eine Ganzzahl 1–900 oder 0 zum
Beenden nur dieses Clients. Erneutes Starten mit demselben Client erneuert die
Frist. Acht aktive Clients sind erlaubt; weitere werden abgewiesen. Retained
Anfragen werden ohne Ausführung und Antwort ignoriert. Sonstige ungültige
Anfragen erhalten bei gültiger Transaktion `invalid_request`.

`response/debug` liefert `{protocol_version: 1, transaction, status: "ok",
data: {debug_version: 1, client, seconds, active}}`. Die Antwort betrifft allein
die Aufzeichnungsfreigabe und hat keine Auswirkung auf Speicher, Ausführung,
Aktivierungsfreigabe oder Z2M-Protokolllevel. Fehlende Antworten werden nicht
automatisch wiederholt. Beide Topics sind nicht retained.

`debug` veröffentlicht `{protocol_version: 1, debug_version: 1, session,
clients, dropped, record}` mit QoS 0, retain false, skipLog und skipReceive.
`clients` enthält höchstens acht noch berechtigte UUIDs; `dropped` zählt bekannte
verlorene Meldungen seit der letzten Ausgabe. `record` hat UUID `id`, UTC-Epoch-
Millisekunden `at` und `event` aus `debug-events.json`. Optional: UUID `run`,
SHA-256 `origin`, einsbasierte `rule` (1–100), `trigger` (1–8), `condition`
(1–16), `step` (1–16), sichere positive `revision`, `delay` (0–86400), boolesches
`passed` und ein fester `reason` aus RuntimeDebugContract. Texte werden lokal
aufgelöst; fremde Texte sind nicht Bestandteil des Protokolls. Begrenzungen
und Verlustverhalten: [DEBUG.md](DEBUG.md).

Mesh-`prepare` darf optional `trace: {run, rule, step, revision}` tragen. Diese
Diagnose wird nach Prüfung mit der freigegebenen Quelle in der Challenge
gehalten und bei `execute` verwendet. Sie ist keine Aktionsfreigabe und ändert
keinen Aktionsdigest. Eine ungültige optionale Diagnose wird ignoriert; die
bestehende Aktionsvalidierung bleibt unverändert. Für `execute` sind weiterhin
keine zusätzlichen Trace-Felder zulässig. Es gibt keine Debug-Weiterleitung
über das Mesh: jede Erweiterung veröffentlicht nur im eigenen Basistopic.

## Szenenkatalog

Die `editor`-Antwort ergänzt optional `scenes` mit höchstens 512 Einträgen.
Ein Eintrag enthält `target` (`type` device/group und `id`), `id` (0–255),
`name` (höchstens 120 Unicode-Zeichen), `control_property` (zugehöriger state-Kanal)
und für Geräte zwingend `endpoint` (1–240). Pro Ziel liefert die begrenzte
Metadatenlesung höchstens 64 Szenen. Fehlendes `scenes` bedeutet keine angebotenen
Szenen; unvollständige, doppelte oder ungültige Einträge werden nicht übernommen.
Es werden keine Zigbee-Abfragen oder Szenenänderungen zur Ermittlung ausgelöst.
Lokale fehlende Szenen ergeben bei validate/store `invalid_semantics` mit genauer
Position und Ursache `scene_unavailable`. Bei Aktivierung und Ausführung ist
`scene_unavailable` ein fester Fehlercode. Mesh-Ergebnisse können diesen festen
Code zusätzlich übertragen; andere Fehler bleiben wie bisher `mesh_rejected`.

Der additive Gerätekatalog enthält optional `numeric`: bis zu 64 lesbare
numerische Eigenschaften je Gerät mit `property` und einer auf 24 Unicode-Zeichen
begrenzten `unit`. Sie zählen zur gemeinsamen Grenze von 1024 Auswahlwerten.
Auch reine Messgeräte werden geliefert. Roh-Exposes und schreibbare Zahlenwerte
werden nicht übertragen; das Schema und `editor_version: 1` bleiben unverändert.
Ältere Empfänger ohne `numeric` unterstützen weiter Uhrzeiten/Zeitfenster und
diskrete Zustandsbedingungen. Für Messwertbedingungen ist das neue Artefakt nötig.

## Umfang und Installation

Diese Ausbaustufe implementiert die eigene Z2M-Seite: Empfang, Validierung,
persistente Speicherung, Wiederanlauf und Rückmeldung. Der Symcon-Transport
integriert Installation, aktive Statusabfragen, validate und store;
siehe [Modulanleitung](../Z2MAutomations/README.md). Eine tatsächlich ausgeführte
Installation und YAML-Übertragung wurden praktisch geprüft. Die neue
Ausführungsengine ergänzt getrennte Aktivierungsbefehle; ihr Praxistest steht aus.

Für eine spätere Installation wird ausschließlich
[z2m-automations.mjs](../libs/z2m/z2m-automations.mjs) mit der offiziellen
extension/save-API hochgeladen. Der Host stellt Ajv und js-yaml bereit.
Das Schema und sämtliche eigene Logik sind in dieser Datei enthalten.

## Topics

Alle Topics liegen unter dem in Z2M konfigurierten Basistopic.
Der Präfix symcon/automations ist unser eigener Vertrag.

| Suffix | Bedeutung |
| --- | --- |
| symcon/automations/request/status | Aktuellen Zustand einschließlich Sitzung abfragen |
| symcon/automations/request/validate | YAML ohne Schreibzugriff prüfen |
| symcon/automations/request/store | YAML persistent speichern, ohne Ausführung |
| symcon/automations/request/get | Geladene gültige YAML und Metadaten zurückgeben |
| symcon/automations/request/activate | Gespeicherte Revision ausdrücklich aktivieren; [Vertrag](RUNTIME.md) |
| symcon/automations/request/deactivate | Ausführung und ausstehende Verzögerungen stoppen |
| symcon/automations/request/editor | Begrenzte diskrete Geräte-Exposes und die unveränderte gespeicherte Konfiguration für den Klick-Editor lesen |
| symcon/automations/response/OPERATION | Antwort auf die jeweilige Anfrage |
| symcon/automations/status | Zustand bei Start, erfolgreicher Speicherung und Stop; retained |

Ein MQTT Client in Symcon muss zusätzlich BaseTopic/symcon/automations/#
abonnieren. Der Modul-Empfang verarbeitet diesen Präfix bereits. Ein bestehendes
Abonnement # genügt auf Gateway-Ebene. Die bisherigen bridge/#-Daten decken den
neuen Präfix nicht ab.

Jede Anfrage ist ein JSON-Objekt mit protocol_version: 1 und transaction als
String mit 1–64 ASCII-Zeichen aus A–Z, a–z, 0–9, _ und -.
Unbekannte Felder werden abgewiesen. validate benötigt zusätzlich yaml.

Bei `invalid_yaml`, `invalid_schema` oder `invalid_semantics` kann `error.details`
die Felder `issues` (höchstens 20 Einträge) und `truncated` (Boolean) enthalten.
Jeder Eintrag besitzt einen nullbasierten JSON-Pointer `path` und einen festen
`reason`-Code; Zahlenbegrenzungen ergänzen `limit`, YAML-Syntaxfehler optional
einsbasierte `line`/`column`. Namen, Werte, Parserausschnitte und dynamische
Schlüssel aus Schaltwerten werden nicht zurückgegeben. Beispiel:
`{"path":"/automations/11/actions/1/delay_seconds","reason":"minimum","limit":0}`.
Die ursprüngliche Schema-Prüfung entscheidet weiterhin unverändert über die
Annahme. Eine zusätzliche Diagnoseprüfung wählt anhand des deklarierten Typs
den passenden Schema-Zweig und sammelt Fehler, ohne Meldungen unpassender
Alternativen (etwa Set statt Toggle) auszugeben. Semantische Prüfungen ordnen
doppelte Kennungen, Zeitzonen, Zeitfenster und Partnerfreigaben konkret zu.
Symcon akzeptiert ausschließlich begrenzte, bekannte Felder und Codes; fehlt
die optionale Diagnose oder ist sie ungültig, bleibt die feste Kurzmeldung.
Namen löst es ausschließlich aus der tatsächlich geprüften JSON/YAML-Anfrage
des Klick-Editors auf. Die Diagnose verändert weder gespeicherte Regeln noch
ihre Ausführung. Erst der Start einer neuen Entwurfsprüfung verwirft alte
Diagnosedaten. `validationDetails` bleibt unabhängig vom letzten Vorgangsfehler
erhalten; automatische/manuelle Statusabfragen und deren Fehler löschen es nicht.
Zusätzliche Vorgangsfehler werden im Formular neben der Diagnose angezeigt.

status, get und editor haben keine weiteren Felder. Die zusätzlichen Felder für
activate/deactivate stehen in [RUNTIME.md](RUNTIME.md).

`editor` antwortet mit den normalen Statusfeldern, `editor_version: 1`, `devices`,
`configuration` und `yaml`. Die Konfiguration stammt aus der bereits validierten
Speicherung; `yaml` enthält deren exakte Quelle (bei leerem Speicher null).
Bei leerem Speicher hat `configuration` vor der ersten Ergänzung Revision 0.
Geräte enthalten nur IEEE-ID, Anzeigename und diskrete `triggers`/`actions`
mit `property` und skalarem `value`. Rohdefinitionen, Netzwerkadressen und
Gerätesitzungen werden nicht an Symcon übertragen. Begrenzung: 256 gelesene Geräte,
128 Exposes-Knoten je Gerät, 64 Auswahlwerte pro Richtung/Gerät und 1024 insgesamt.
Numerische Exposes ohne diskrete Werteliste sind im ersten Klick-Editor nicht enthalten.

Beispiel für store; SESSION stammt aus der aktuellen Statusmeldung:

```json
{
    "protocol_version": 1,
    "transaction": "synthetic-store-1",
    "session": "SESSION",
    "expected_revision": 0,
    "yaml": "schema_version: 1\nrevision: 1\nautomations: []\n"
}
```

Die erste Speicherung verwendet expected_revision 0 und YAML-revision 1.
Danach müssen expected_revision und gespeicherte Revision übereinstimmen;
die neue YAML-revision ist genau um eins höher.
Ein bytegleiches Wiederholen der aktuellen Fassung mit gleichem oder unmittelbar
vorherigem expected_revision wird ohne erneutes Schreiben als unchanged bestätigt.
Gleiche Revision mit anderem Inhalt ist kein erlaubtes Update.

## Sitzung und Antworten

Die Erweiterung erzeugt bei jedem Start eine neue zufällige session und gibt sie
im Status zurück. store verlangt diese Sitzung. Sie ist eine Aktualitätskennung,
keine Authentifizierung. MQTT-Berechtigungen müssen Schreiben und Lesen auf
berechtigte Clients begrenzen. Befehle werden grundsätzlich ohne Retain gesendet.

Wichtige Z2M-Grenze: Der geprüfte MQTT-Eventbus liefert topic und message,
aber kein Retain-Merkmal. Eine alte retained Nachricht aus einer vorherigen
Erweiterungssitzung wird deshalb über session abgewiesen. Liefert ein Adapter
retain: true ausdrücklich mit, wird store ebenfalls abgewiesen.
Innerhalb derselben Sitzung schützen Revision, Prüfsumme und Idempotenz vor
erneutem Anwenden einer bereits gespeicherten Fassung. Die Erweiterung kann den
Retain-Zustand im nativen Bus nicht allgemein erkennen.

Erfolg: protocol_version, transaction, status: ok und data. Bei store enthält
data stored_revision, checksum, automation_count, storage, executing und
unchanged. Die Prüfsumme ist SHA-256 über die exakten UTF-8-YAML-Bytes einschließlich
Kommentaren und Zeilenenden. Eine Bestätigung bedeutet gespeichert; sie aktiviert
nichts. Eine geänderte erfolgreiche Speicherung stoppt die Engine. Eine bytegleiche
Speicherung erhält die bereits aktive Revision und meldet dann executing: true.

Fehler: status: error und error mit code und fester message. Gültige
Transaktions-IDs werden auch bei ungültigem Inhalt gespiegelt. Eine ungültige
oder fehlende ID wird nicht übernommen.

Codes: invalid_request, invalid_yaml, invalid_schema, invalid_semantics,
session_mismatch, revision_conflict, revision_reused, storage_unavailable,
storage_error, busy und not_ready. Keine Parser-Snippets, Roh-Konfigurationen,
internen Pfade oder rohen Dateisystemfehler in Antworten oder eigenen Logs.

Die JSON-Anfrage ist maximal 1 MiB groß. Es werden höchstens 16 Anfragen
einschließlich der laufenden Anfrage gehalten und seriell verarbeitet.
Antworten werden mit QoS 0 ohne Retain veröffentlicht. Ein verlorenes ACK kann
durch die idempotente Wiederholung und Statusabfrage aufgelöst werden.

## Persistenz und Fehlerzustände

Eigener Ordner neben external_extensions im tatsächlich verwendeten
Z2M-Datenverzeichnis: data/z2m-automations/. Keine festen Hostpfade und keine
vom Client übergebenen Dateinamen.

- automations.yaml: aktuelle gültige YAML.
- automations.previous.yaml: unmittelbar vor dem letzten Update gültige Fassung.
- .write-UUID.tmp: ausschließlich eigene temporäre Schreibdateien.

Vor einem Update wird die Hauptdatei erneut validiert und mit der geladenen
Prüfsumme verglichen. Fremde Änderungen sperren Schreibzugriffe. Auch eine
idempotente Anfrage umgeht diesen Vergleich nicht.

Jede Datei wird im selben Verzeichnis exklusiv temporär angelegt, vollständig
geschrieben und mit FileHandle.sync gespült. Erst nach dem Schließen wird sie
per rename ersetzt. Die Vorversion wird vor dem Ersetzen der Hauptdatei gesichert.
Der geladene Zustand wechselt erst nach erfolgreichem Ersetzen der Hauptdatei.
Temporäre Dateien werden nach Fehlern bestmöglich entfernt; etwaige Überreste
werden beim Start nicht als Konfiguration geladen.

Neue Ordner erhalten unter POSIX 0700, neue Dateien 0600. Die Prüfungen akzeptieren
keine symbolischen Links als verwaltete Dateien oder als direkten Speicherordner.
Die Speicherung ist für einen Erweiterungsschreiber in einem lokalen persistenten
Z2M-Datenverzeichnis vorgesehen. Netzwerkdateisysteme, mehrere Prozesse mit
gemeinsamem Datenordner und Stromausfallsicherheit sind nicht nachgewiesen.
Es gibt keine zusätzliche Verzeichnis-fsync-Garantie nach rename.

| storage | Bedeutung |
| --- | --- |
| empty | Hauptdatei und Sicherung fehlen; Erstübertragung mit Revision 1 möglich |
| ok | Gültige Hauptdatei geladen oder gespeichert |
| recovered_read_only | Hauptdatei fehlt/ist beschädigt; gültige Sicherung nur lesend geladen |
| error | Speicher nicht verfügbar, Dateien ungültig oder fremde Änderung erkannt |

Bei recovered_read_only bleibt die beschädigte Hauptdatei erhalten. Neue
Schreibzugriffe werden abgewiesen; die Sicherung ist mit get lesbar. Eine
Wiederherstellung benötigt eine bewusste Prüfung der Dateien und Revisionen
bei gestoppter Erweiterung. Keine automatische Rücksetzung auf Revision 0.

state: ready bezeichnet die Erreichbarkeit des Empfängers. storage ist gesondert
auszuwerten. executing bezeichnet die ausdrücklich aktivierte Engine, nicht eine
bestätigte Gerätewirkung. Status und Aktivierungsvertrag stehen in [RUNTIME.md](RUNTIME.md).

## Quellen und Testgrenzen

- [Z2M-Erweiterungsvertrag](https://www.zigbee2mqtt.io/advanced/more/external_extensions.html)
- [Geprüfter MQTT-Eventbus](https://github.com/Koenkk/zigbee2mqtt/blob/master/lib/mqtt.ts)
- [Z2M-Abhängigkeiten](https://github.com/Koenkk/zigbee2mqtt/blob/master/package.json)
- [js-yaml API](https://github.com/nodeca/js-yaml/blob/master/docs/usage.md)
- [Node-Dateisystem](https://nodejs.org/api/fs.html)

Vertragsprüfung am 27.10.2026 gegen Z2M 2.14.2. Offline geprüft mit js-yaml 5.4.2,
Ajv 8.20.0, echter Dateispeicherung in temporären Testordnern und synthetischem
MQTT-/Eventbus. Keine fremde Automationsimplementierung übernommen.
Die Tests bestätigen keinen Betrieb in einer echten Z2M-Installation.

## Wiederanlauf

request/activate kann resume_after_restart (Boolean, Default true) enthalten.
Statusmeldungen ergänzen resume_version, resume_enabled, resume_status und
resume_error; Werte und dauerhafte Semantik stehen in [RUNTIME.md](RUNTIME.md).
Die Freigabe gehört zum aktivierten Regelstand und wird nicht durch retained
Aktivierungsbefehle oder eine Symcon-Statusabfrage ersetzt.

## Statusbestätigung nach Speicherung

Nach einer passenden `response/store` fordert das Modul im selben Workflow einen
frischen `status` mit neuem Anfragebezug an. Bis zu dessen Antwort sind Laufzeit
und Aktivierungsbutton unbestätigt. Verglichen werden Sitzung, gespeicherte
Revision, Prüfsumme, Regelanzahl, Speicherzustand und der in der Speicherantwort
genannte Ausführungszustand. Erst die passende vollständige Runtime-Version-1-
Antwort schließt den Auftrag als `stored` ab. Retained-/fremde/verspätete Antworten
erteilen keine Freigabe. Der vollständige Snapshot verhindert, dass ein identischer
anschließender Statusbroadcast wegen veralteter Mesh-/Wiederanlauffelder erneut
die Bestätigung entzieht. Unverändertes Speichern erhält eine laufende Ausführung.

Fehler oder Timeout nach Speicherbestätigung ergeben `stored_unconfirmed` mit
Fehlercode, nicht die Behauptung einer fehlgeschlagenen Speicherung. Keine
automatische erneute Speicherung, Aktivierung oder Deaktivierung. Ein späterer
lesender Statusauftrag kann die Bestätigung wiederherstellen. Die Vorschau
erkennt anhand der exakten Entwurfsprüfsumme einen bereits gespeicherten Stand,
auch wenn der Validierungsbeleg nach dem Speichern verbraucht wurde. Sie vergibt
dadurch keinen neuen Validierungsbeleg. Keine Erweiterungs-/Schemaänderung.
