# Bestehende Automations-Einzelinstanzen

Diese Referenz gilt ausschließlich für bereits vorhandene Automationsinstanzen
mit direkter MQTT-Anbindung. Für neue Einrichtungen die
[Automationszentrale](../Z2MAutomations/README.md) und eigene
[Z2M-Verbindungen](../Z2MConnection/README.md) verwenden. Ein Modulupdate
übernimmt vorhandene Einzelinstanzen nicht automatisch. Den gesicherten
Wechsel beschreibt [Sicherung und Rückweg](MIGRATION.md).

Die folgenden Angaben dokumentieren die Bedienung der bisherigen Einzelinstanzen.
Deren Statusvariablen und direkte MQTT-Funktionen beschreiben nicht den Betrieb
der einzelnen Systeme einer Zentrale.

## Voraussetzungen und Einrichtung

IP-Symcon ab 9.0 mit PHP 8.5 sowie eine konfigurierte MQTT-Client- oder
MQTT-Server-Instanz, die mit derselben Z2M-Installation verbunden ist.

1. Die vorhandene Einzelinstanz öffnen und ihre übergeordnete MQTT-Verbindung
   prüfen. Gemeinsame Nutzung mit anderen Modulen ist möglich. Neue Anlagen
   werden über die Automationszentrale und Z2M-Verbindungen eingerichtet.
2. Das Basistopic der Z2M-Installation eintragen.
3. Bei MQTT Client das Basistopic mit /bridge/# und /symcon/automations/#
   abonnieren. Ein umfassenderes Abonnement wie # genügt. Die Gateway-
   Einstellungen werden nicht geändert.
4. „MQTT-Anbindung aktivieren“ einschalten und Änderungen übernehmen.
5. Bei Bedarf „Zigbee2MQTT-Verbindung prüfen“ betätigen. Das Ergebnis erscheint
   als Popup und in der Statusvariable „Verbindungsprüfung“.

Broker, Zugangsdaten und TLS werden ausschließlich im vorhandenen Gateway und
seiner Socket-Verbindung eingerichtet. Die Library benötigt keine eigene Kopie.

## MQTT-Gateway und Suchbereich

Für zwei Z2M-Systeme mit unterschiedlichen Basistopics werden zwei getrennte
Automationsinstanzen verwendet, auch bei einem gemeinsamen MQTT-Broker oder
Symcon-MQTT-Client.
Jede Instanz nutzt ausschließlich ihre eigene übergeordnete MQTT-Verbindung
und das unter **Zigbee2MQTT-Basistopic** konfigurierte Topic (Groß-/Kleinschreibung
beachten). Gleichlautende Topics, IEEE-Kennungen oder Gruppen-IDs an anderen
Gateways werden nicht vermischt.

Unter **An diesem MQTT-Gateway gefundene Basistopics** lassen sich Topics aus
vorhandenen Z2M-Geräte-, Gruppen- und Bridge-Instanzen auswählen. Die Auswahl
befüllt nur das Basistopic-Feld; eine manuelle Eingabe bleibt möglich. Danach
**Änderungen übernehmen** und für den Import den Z2M-Katalog neu laden.
Die Auswahl ändert weder das Gateway noch Konfigurationen anderer Instanzen.
Die Vorschläge werden beim Öffnen des Formulars aus der lokalen Installation
ermittelt; es wird kein MQTT-Netzwerkscan durchgeführt.

Nach **Gateway ändern** wird die Topic-Auswahl im geöffneten Formular erneuert.
Dabei wird das Formular neu geladen. Übernehmen Sie noch offene Formulareingaben
vor dem Gateway-Wechsel; bereits übernommene Regelentwürfe bleiben erhalten.
Das bisher gespeicherte Basistopic bleibt erhalten. Steht dort **hier nicht
gefunden**, wählen Sie das passende Topic aus der Liste oder tragen es manuell
ein und übernehmen die Einstellungen. Bei mehreren Z2M-Systemen am selben
Gateway muss das gewünschte System ausdrücklich ausgewählt werden. Ein
Gateway-Wechsel allein ändert keine Regelentwürfe. Passt das bisher gespeicherte
Topic nach dem Wechsel nicht zu den erkannten Topics, wartet die automatische
Statusprüfung auf die Übernahme der Einstellungen. Auch ein bewusst manuell
eingetragenes Topic lässt sich übernehmen und prüfen.

**Vorhandene Ereignisse prüfen** findet passende Symcon-Ereignisse anhand ihrer
Quellvariablen unabhängig vom Ablageort im Objektbaum. Auch ohne geladenen
Z2M-Katalog werden diese Ereignisse angezeigt. Ob sie importierbar sind, wird
anschließend gegen den Katalog desselben Gateways und Topics geprüft. Nach einem
Gateway- oder Topic-Wechsel werden bisherige Suchergebnisse und Freigaben nicht
weiter angeboten. Es sind keine konkreten Instanz-IDs im Modul hinterlegt.

## Verbindung prüfen und Rückmeldung erhalten

Mit **Zigbee2MQTT-Verbindung prüfen** starten Sie eine Prüfung über das zugeordnete
MQTT-Gateway. Sobald eine passende Antwort vorliegt, erscheint ein Popup mit dem
Ergebnis. Bei Erfolg bestätigt es, dass Zigbee2MQTT erreichbar ist und einen
fehlerfreien Zustand meldet. Meldet Z2M ein Problem, fehlt die Verbindung oder
bleibt die Antwort aus, nennt das Popup den nächsten Prüfschritt.

Der Versand allein wird noch nicht als Erfolg angezeigt. Mehrere Klicks während
derselben Prüfung erzeugen keine weiteren Anfragen. Automatische Prüfungen beim
Öffnen des Formulars, nach einem Modulupdate oder nach Wiederverbindung bleiben
ohne Popup. Die Prüfung schaltet keine Geräte und verändert keine Automationen.

## Erweiterung installieren

Z2M muss erreichbar sein und externe JavaScript-Erweiterungen erlauben.
Ist diese Funktion in Z2M ausgeschaltet, lehnt das Modul die Installation ab.
Fehlt dazu noch eine Statusinformation, prüft die offizielle Z2M-Schnittstelle
den Installationsauftrag. Das Modul schaltet diese Funktion nicht selbst ein.
Die bisher praktisch belegte Z2M-Version ist **2.14.2-dev**. Z2M 2.14.2 ist
das Schnittstellenziel; für die unveränderte stabile Ausgabe liegt keine eigene
Praxisabnahme vor. Siehe [Versionsübersicht und Grenzen](../docs/COMPATIBILITY.md).

„Mitgelieferte Erweiterung installieren oder aktualisieren“ lädt ausschließlich
die mitgelieferte z2m-automations.mjs über MQTT hoch. Die Bestätigung weist auf
das Ersetzen einer bereits vorhandenen Datei dieses Namens hin. Nach der
Z2M-Upload-Antwort fragt das Modul den Empfänger aktiv ab. Erst dessen passende
Antwort bestätigt die erfolgreiche Installation. Eine vorhandene Datei oder
ein gespeicherter MQTT-Status allein genügen nicht.

Die Z2M-API schreibt die JavaScript-Datei vor dem Laden. Ein Ladefehler ist
deshalb kein Nachweis einer unveränderten früheren Datei. Das Modul führt
keine automatische Rücknahme auf eine vorherige JavaScript-Version aus.

Der Installationsbutton zeigt nach Abschluss ein Popup mit Erfolg oder einer
Fehlerbeschreibung. Erfolgreicher Upload und bestätigte Bereitschaft der
Erweiterung sind beide erforderlich. Ein bloßer Dateieintrag reicht nicht.
Z2M lädt die Erweiterung beim Speichern über seine Schnittstelle neu; ein
zusätzlicher manueller Neustart ist dafür nicht nötig.

Bei bereits installierter Erweiterung genügt „Erweiterungsstatus abfragen“.
Das Modul aktiviert kein externes JavaScript und löst keinen Z2M-Neustart aus.

## Erweiterungsstand und Updatehinweis

Unter **Einrichtung und Diagnose** stehen der **installierte Erweiterungsstand**
und der **mitgelieferte Erweiterungsstand**, jeweils mit Version und kurzer
Buildkennung. Die Kennung wird automatisch aus dem Erweiterungscode erzeugt.
Sie erkennt Änderungen auch dann, wenn die Versionsnummer gleich bleibt.

**Aktuell** bedeutet, dass beide Stände übereinstimmen. **Aktualisierung verfügbar**
bedeutet, dass sie sich unterscheiden. Mit **Mitgelieferte Erweiterung installieren
oder aktualisieren** übernehmen Sie den Stand aus dem Modul. Die Kennung selbst
ist kein Datum und sagt nicht, welcher Stand jünger ist.

Beim Öffnen des Formulars und nach einem Modulupdate erfolgt die vorhandene
automatische Statusprüfung. **Erweiterungsstatus abfragen** aktualisiert die
Anzeige auch manuell. Nach bestätigter Installation wird der Stand erneut geprüft.
Die Anzeige installiert oder aktiviert selbst keine Erweiterung oder Automation.

Bei **Noch nicht geprüft** fehlt eine bestätigte Statusantwort. Meldet eine ältere
Erweiterung **ohne Buildkennung**, lässt sich ihr Stand noch nicht vergleichen.
Installieren beziehungsweise aktualisieren Sie dann die mitgelieferte Erweiterung
und fragen Sie deren Status erneut ab. Für die neue Anzeige zunächst das Modul
aktualisieren; für den vollständigen Vergleich auch die Erweiterung aktualisieren.

## Ereignisimport im bisherigen Einzelmodul

Die Prüfung unterscheidet zwischen dem Gerät und der einzelnen Variable:
Ein Gerät kann richtig verbunden sein, obwohl eine seiner Variablen noch nicht
importiert werden kann. In diesem Fall nennt die Meldung die betroffene Variable.
Die **auslösende Variable** wird überwacht; die **zu schaltende Variable** ist das
Ziel der Aktion. Das Ereignis kann im Objektbaum beim Ziel liegen und trotzdem
eine Variable eines anderen Geräts überwachen.

Ein leerer Ereignisname verhindert den Import nicht. Das Modul vergibt dann
„Importierte Automation“ mit der Ereignisnummer als Namen. Dieser lässt sich
anschließend im Entwurf ändern; das ursprüngliche Ereignis bleibt unverändert.

Die Übersicht zeigt Ereignisname, **auslösendes Gerät** und dessen
**Symcon-Instanz-ID**. Die Liste bietet Platz für 15 sichtbare Einträge;
weitere Einträge sind durch Scrollen erreichbar. Auch in der Ereignisauswahl stehen diese Angaben, damit
sich gleichnamige Ereignisse unterscheiden lassen.

Die Importprüfung nennt das Ergebnis ausdrücklich: **Importierbar** oder
**Nicht importierbar**, jeweils mit einer Erklärung.

Fehlt die Gerätezuordnung aus Z2M für die gewählte Verbindung, steht in der
Übersicht **Noch nicht prüfbar: Gerätedaten fehlen**. Die Meldung darunter verweist direkt
auf **Gerätedaten für den Import laden**. Dieser Button lädt Geräte, Gruppen und
gespeicherte Regeln über die Erweiterung. Nach der Antwort wird die Übersicht
aktualisiert und das gewählte Ereignis erneut geprüft. Dafür ist kein erneutes
Öffnen des Formulars notwendig. Ein unterstütztes Ereignis lässt sich danach
übernehmen; andernfalls erscheint der konkrete Ablehnungsgrund.

Beim Nachladen über **Gerätedaten für den Import laden** bleiben vorhandene
Entwürfe in Symcon erhalten. Erst wenn es noch keinen Entwurf gibt, werden die
in Z2M gespeicherten Regeln als Ausgangspunkt geladen. Der separate Button
**Geräte und gespeicherte Automationen laden** ersetzt weiterhin den Entwurf
nach Bestätigung. Bei zwischenzeitlichen Änderungen in Z2M oder unübernommenen
Änderungen im YAML-Editor wird das Nachladen abgebrochen und der Entwurf erhalten.
Während der Antwort aus Z2M bleiben Laden und Übernahme gesperrt. Ladefehler
werden verständlich angezeigt und bleiben beim erneuten Öffnen sichtbar.


1. Modul und mitgelieferte Z2M-Erweiterung auf denselben aktuellen Stand bringen.
2. **Geräte und gespeicherte Automationen laden**. Das ersetzt den lokalen Entwurf;
   vorherige Änderungen deshalb zuerst übernehmen bzw. speichern.
3. **Symcon-Ereignisse importieren** öffnen und **Vorhandene Ereignisse prüfen** wählen.
4. Ein Ereignis auswählen und die Vorschau mit Auslöser, Ziel und Aktion prüfen.
5. **Ereignis in den Entwurf übernehmen** wählen. Die deaktivierte Regel öffnet
   sich vorbefüllt im Bereich der geräteausgelösten Automationen und lässt sich
   dort ohne YAML bearbeiten. Anschließend gemeinsam prüfen und speichern.

Der Import unterstützt ausgelöste Ereignisse **bei bestimmtem Wert** mit oder
ohne **mehrmalige Auslösung bei wiederholt erfüllter Bedingung**, ohne zusätzliche
Bedingungen oder Ausführungsbegrenzung. Unterstützt sind native feste boolesche
bzw. unmittelbar zuordenbare Text-/Auswahlwerte sowie **Wert umschalten** für
Ein-/Aus-Zustände. Zahlenwerte mit möglicher Symcon-Skalierung, Prozent-/Farbaktionen,
PHP-Code, Ablaufpläne und weitere Aktionsarten werden mit Begründung abgewiesen.
Es wird keine teilweise vereinfachte Regel erzeugt.

Aktionen direkt unter einer Variablen verwenden diese als Ziel. Auch die native
Instanzaktion **Statusvariable schalten** wird unterstützt: Die Statusvariable
wird über ihren Ident ausgewählt und ihre enthaltene feste Wert-/Umschaltaktion
übernommen. Das Ereignis muss kein ausdrücklich gespeichertes TARGET enthalten.

Ist die wiederholte Ausführung in Symcon ausgeschaltet, erhält der Auslöser im
Klick-Editor die Einstellung **Nur bei Wertänderung**. Eine gleiche passende
Meldung startet dann keinen zweiten Lauf. Die Einstellung lässt sich im
Auslöserdialog ändern. Beim Aktivieren verwendet Z2M seinen vorhandenen
Quellzustand als Ausgangspunkt. Für nicht gespeicherte Ereigniswerte (etwa
Tastendrücke) zählt die erste frische Meldung als Änderung; danach wird der
letzte beobachtete Wert verglichen. Ein-/Ausführung und Sperrzeit gelten weiter.

Die Zuordnung verwendet die referenzierten Variablen-IDs, den Instanztyp, die
Gateway-Verbindung, das konfigurierte Basistopic und die Z2M-Geräte-/Gruppenkennung. Namen sind nur Anzeige.
Aktionsziele benötigen eine native schaltbare Variable ohne eigene Aktionsskripte
und eine passende schreibbare Katalogeigenschaft. Sensoren bleiben lesbare Quellen.
Gruppen sind als Aktionsziele für ihren Ein-/Aus-Zustand verfügbar.

**Wert umschalten** verwendet unmittelbar vor der Aktion den in Z2M bekannten
Zustand der Gruppe bzw. des Gerätekanals und sendet den entgegengesetzten
Ein-/Aus-Wert. Bei unbekanntem Zustand wird der Schritt mit Fehler abgebrochen.
Es wird kein möglicherweise veralteter Symcon-Wert übernommen und kein pauschaler
TOGGLE-Befehl an alle Gruppenmitglieder gesendet.

Das ursprüngliche Symcon-Ereignis wird weder geändert noch deaktiviert.
**Vor Aktivierung der importierten Regel in Z2M das Originalereignis deaktivieren**,
damit beide Systeme die Aktion nicht doppelt ausführen. Die allgemeinen
Laufzeitgrenzen gelten weiter, insbesondere eine Sekunde Sperrzeit pro Regel und
keine parallelen Läufe derselben Regel. Das ist keine Garantie identischer
Ausführungshäufigkeit wie in Symcon.

Die Vorschau wird vor der Übernahme erneut geprüft. Geänderte Ereignisse,
Zuordnungen oder ungespeicherte YAML-Änderungen werden nicht stillschweigend
übernommen. Ein bereits importiertes Ereignis erzeugt keine zweite Regel.
Die Suche berücksichtigt höchstens 4096 Ereignisse und zeigt maximal 256 passende
Quellen aus dem geladenen Katalog; eine erreichte Grenze wird angezeigt.

## Erweiterte YAML-Ansicht

Den Bereich „Erweiterter YAML-Editor“ aufklappen, um die YAML direkt anzuzeigen
oder zu bearbeiten. Für die grafische Erstellung muss dieser Bereich nicht
geöffnet werden.

1. YAML in „YAML-Entwurf der Automationen“ einfügen. Ein
   [synthetisches Beispiel](../libs/z2m/examples/automations.yaml) und der
   [YAML-Vertrag](../docs/YAML_SCHEMA.md) beschreiben das Format.
2. Für eine neue Fassung die nächste Revision verwenden. Eine leere Installation
   beginnt mit Revision 1. Eine unveränderte Kopie der bereits gespeicherten
   Fassung kann nach erneuter Prüfung idempotent bestätigt werden.
3. „Entwurf in Z2M prüfen“ starten. Das Modul fragt zuerst den aktuellen Empfänger-
   und Speicherzustand ab und sendet dann die YAML zur dortigen Schema-Prüfung.
4. In der Vorschau Revision, Anzahl der Automationen, bisher gespeicherte
   Revision und SHA-256 kontrollieren.
5. „Geprüften Entwurf in Z2M speichern“ bestätigen. Das Modul prüft Sitzung und
   Revision erneut, bevor genau die geprüften Bytes übertragen werden.

Der zuletzt zur Prüfung oder Speicherung eingereichte Entwurf bleibt beim
erneuten Öffnen des Formulars erhalten. Reines Tippen ohne Bedienaktion wird
nicht gespeichert. Der Entwurf ist kein PHP-Skript und wird niemals ausgeführt.
Es wird auch keine bereits in Z2M gespeicherte YAML automatisch in den Editor geladen.

Textänderungen, Topic-/Gateway-Wechsel, ein Erweiterungsneustart oder eine
geänderte gespeicherte Revision machen die bisherige Prüffreigabe ungültig.
Bei Konflikten wird keine neue Revision automatisch erzeugt und nichts blind
überschrieben. Ein Empfängerspeicher in Fehler- oder Sicherungslesemodus blockiert
die Übertragung. Fehlermeldungen stehen im Formular und in „Letzter Erweiterungsfehler“.

Eine Zeitüberschreitung beweist nicht, dass Z2M nichts gespeichert hat. Vor erneutem
Übertragen den Erweiterungsstatus abfragen, Revision prüfen und den Entwurf bei
Bedarf erneut validieren. Das Modul wiederholt Installationen oder Speicheraufträge
nicht automatisch. „YAML gespeichert“ bestätigt Persistenz, keine Ausführung.

## Automatische Statusprüfung

Nach Anwenden der Einstellungen bzw. einem Modulupdate und beim Öffnen des
Formulars prüft das aktive Modul einmalig die Erreichbarkeit von Zigbee2MQTT.
Eine passende positive Health-Antwort startet anschließend die Abfrage des
Erweiterungsstatus. Die Statusanzeige aktualisiert sich anhand der Antworten.

Die Prüfung startet verzögert nach etwa einer Sekunde und benötigt eine
verbundene MQTT-Schnittstelle. Aufrufe innerhalb von zehn Sekunden werden
zusammengefasst. Laufende Aufträge haben Vorrang; Fehler oder Zeitüberschreitungen
führen nicht zu einer automatischen Wiederholung. Entwürfe werden nicht verändert;
Installation, Übertragung und Aktivierung bleiben ausdrückliche Bedienaktionen.
Die manuellen Prüfbuttons stehen weiterhin zur Verfügung.

## Einstellungen

| Feld | Standard | Bedeutung |
| --- | --- | --- |
| MQTT-Anbindung aktivieren (Active, Boolean) | Aus | Empfang sowie automatische Statusprüfung und manuelle Anfragen erlauben |
| Zigbee2MQTT-Basistopic (BaseTopic, String) | zigbee2mqtt | Exaktes Topic der Installation, auch mehrstufig |
| Wartezeit auf eine Antwort (RequestTimeout, Integer) | 10 Sekunden | Wartezeit auf eine Antwort; 1 bis 120 Sekunden |
| Automationsmodul des zweiten Z2M-Systems (PeerModuleID, Integer) | 0: kein Partner | ID des anderen Automationsmoduls; eigenes Modul und gleiches Basistopic sind nicht erlaubt. Auswahl auf beiden Seiten, Gerätedaten laden und beide Stände speichern/aktivieren |
| Aktivierte Automationen nach einem Z2M-Neustart automatisch fortsetzen (ResumeAfterRestart, Boolean) | Ein | Wird erst bei der nächsten Aktivierung in Z2M wirksam; bewusstes Stoppen bleibt dauerhaft wirksam |

Die Liste gefundener Basistopics ist eine Auswahlhilfe für BaseTopic, keine
weitere Verbindungseinstellung. Suchfelder, Zeilendialoge und die Auswahl einer
Automation sind Bearbeitungshilfen und keine dauerhaft gespeicherten Properties.

Basistopics dürfen keine MQTT-Platzhalter +/#, Steuerzeichen oder leeren
Pfadabschnitte enthalten. Führende und abschließende Schrägstriche sind ungültig.
Ein Wechsel des Topics oder MQTT-Gateways verwirft den bisherigen Statuscache
und eine noch offene Anfrage.

## Statusvariablen

Die Erweiterung kann ausdrücklich aktivierte Speicherrevisionen ausführen;
[Ausführungsregeln und Aktivierungsbefehle](../docs/RUNTIME.md). Speichern aktiviert nichts; eine
geänderte erfolgreich gespeicherte Revision stoppt die bisherige Ausführung.
`GetTransportState` übernimmt den gemeldeten Boolean `extension.executing`,
ohne damit eine konkrete Gerätewirkung zu bestätigen.

Alle Variablen sind ausschließlich lesbar und besitzen keine Schaltaktion.

| Ident | Typ | Bedeutung |
| --- | --- | --- |
| MQTTConnected | Boolean | Anbindung aktiviert, Konfiguration gültig und MQTT-Gateway aktiv |
| BridgeOnline | Boolean | Zusätzlich hat Z2M den Zustand online gemeldet |
| Z2MVersion | String | Gemeldete Z2M-Version; leer bis zur ersten gültigen Information |
| ExtensionInstalled | Boolean | z2m-automations.mjs in der Erweiterungsliste gefunden, Upload bestätigt oder bereiter Empfänger durch passende Statusantwort bestätigt |
| HealthStatus | String | Noch nicht geprüft, Antwort ausstehend, funktionsfähig, nicht funktionsfähig, Fehler, Zeitüberschreitung oder abgebrochen |
| ExtensionReady | Boolean | Empfängerprotokoll und Version durch eine frische passende Antwort bestätigt |
| StoredRevision | Integer | Zuletzt gemeldete Speicherrevision; 0 auch bei noch unbekanntem Zustand |
| OperationStatus | String | Fortschritt und Ergebnis der letzten Erweiterungsaktion |
| LastError | String | Feste verständliche Meldung zum letzten Erweiterungsfehler |
| ExecutionActive | Boolean | Ausführung durch einen frischen Antwortablauf bestätigt; false kann auch unbekannt bedeuten |
| ActiveRevision | Integer | Bestätigte aktive Revision; 0 bei gestopptem oder unbekanntem Zustand |
| EnabledAutomations | Integer | Bestätigte Anzahl aktivierter Regeln |
| ExecutionStatus | String | Maßgebliche Unterscheidung zwischen aktiv, gestoppt und unbekannt |
| RuntimeError | String | Feste Erklärung des zuletzt gemeldeten Engine-Fehlers |
| MeshReady | Boolean | Bestätigte Freigabe für Aktionen des zweiten Z2M-Systems; false auch bei unbekanntem Zustand |

Eine erkannte Erweiterungsdatei bestätigt noch nicht deren Betriebsbereitschaft.
Die beiden Online-Anzeigen unterscheiden die MQTT-Verbindung vom Zustand der
Z2M-Bridge. Nach Verbindungsabbruch ist eine neue Bridge-Statusmeldung nötig.
Eine Gesundheitsantwort setzt den Bridge-Onlinezustand nicht eigenständig.
StoredRevision allein bestätigt keinen eigenen Speicherauftrag: Auch eine
Statusmeldung kann diesen Wert aktualisieren. Maßgeblich ist das bestätigte
Ergebnis in OperationStatus.

## Instanzstatus

| Code | Bedeutung |
| --- | --- |
| 102 | MQTT verbunden und Z2M meldet online |
| 104 | Anbindung deaktiviert |
| 200 | Ungültiges Topic oder Antwortzeitlimit |
| 201 | Keine aktive kompatible MQTT-Verbindung |
| 202 | MQTT verbunden, Z2M offline oder noch kein Bridge-Status empfangen |

## Öffentliche Funktionen

Für eigene Skripte stehen die folgenden Aufrufe zur Verfügung. Die Bedienung
per Formular benötigt keinen Programmcode. InstanceID ist immer die ID Ihrer
Automationsinstanz. Alle Parameter müssen beim nativen Symcon-Aufruf ausdrücklich
angegeben werden, auch wenn die PHP-Methode einen Standardwert besitzt.

| Aufruf nach dem Präfix Z2MA_ | Rückgabe und Bedeutung |
| --- | --- |
| CheckConnection(int InstanceID) | String: startet die Verbindungsprüfung mit Ergebnis-Popup; entspricht dem Formularbutton |
| CheckHealth(int InstanceID, bool RefreshExtension) | String: Gesundheitsanfrage ohne Popup; true fragt nach positiver Antwort zusätzlich den Erweiterungsstatus ab |
| GetTransportState(int InstanceID) | String: JSON mit zuletzt bekanntem Verbindungs-, Speicher-, Prüf- und Ausführungszustand |
| InstallExtension(int InstanceID, bool Notify) | String: mitgelieferte Erweiterung installieren/ersetzen und Bereitschaft prüfen; true zeigt das Ergebnis-Popup |
| RequestExtensionStatus(int InstanceID) | String: aktuellen Empfänger-/Speicher-/Betriebszustand anfragen |
| LoadAutomationEditor(int InstanceID) | String: Geräte und gespeicherte Regeln laden; ersetzt nach erfolgreicher Antwort den lokalen Entwurf. Vorher eigene Änderungen sichern |
| ValidateAutomationDraft(int InstanceID) | String: aktuellen lokalen Entwurf in Z2M prüfen |
| StoreAutomationDraft(int InstanceID) | String: genau den erfolgreich geprüften lokalen Entwurf in Z2M speichern |
| ValidateYaml(int InstanceID, string Yaml) | String: übergebenen UTF-8-Text in Z2M prüfen; höchstens 128 KiB |
| StoreYaml(int InstanceID, string Yaml) | String: genau die zuvor erfolgreich geprüften Bytes speichern |
| ActivateAutomations(int InstanceID) | String: gespeicherten Stand aktivieren; eingeschaltete Regeln und Empfangsfreigaben werden wirksam |
| StopAutomations(int InstanceID) | String: gesamten Automatikbetrieb und Empfangsfreigaben stoppen |
| RunAutomationOnce(int InstanceID, string RuleID, string CurrentDraft, bool CheckDraft) | String: gespeicherte Regel einmal ausführen, auch bei ausgeschaltetem Regelschalter; Bedingungen gelten weiter |
| StopAutomationRun(int InstanceID, string RuleID) | String: den zugehörigen laufenden Ablauf stoppen, andere Auslöser und Regeln bleiben erhalten |
| RefreshExecutionStatus(int InstanceID) | Keine Rückgabe: Zustände einzelner Läufe anfragen |
| StartRuntimeDebug(int InstanceID, int Seconds) | Boolean: zeitlich begrenzte Ablaufaufzeichnung anfragen; 1 bis 900 Sekunden |
| StopRuntimeDebug(int InstanceID) | Boolean: nur die eigene Aufzeichnung beenden |

Die String-Rückgabe eines Auftrags ist dessen Anfragekennung oder ein leerer
String, wenn nichts gestartet wurde. Sie bestätigt noch keinen Erfolg.
Die Boolean-Rückgaben der Aufzeichnung bestätigen ebenfalls nur den angefragten
Versand. Maßgeblich sind die nachfolgende passende Antwort, OperationStatus und
der bestätigte Laufzeitstatus. Gleichartige Doppelklicks erzeugen keinen zweiten
Auftrag; andere Aufträge können währenddessen als beschäftigt abgewiesen werden.

GetTransportState liefert unter anderem mqttConnected, bridgeState
(unknown/online/offline), version, health, extension, extensionConfirmed,
runtimeConfirmed, operation, validation, validationDetails, execution und
lastError. Fehlende oder alte Statusdaten sind kein bestätigter aktueller Betrieb.
Interne Sitzungskennungen und vollständige YAML werden nicht ausgegeben.

RuleID ist die feste id einer Automation im gespeicherten Dokument, nicht ihr
Name, ihre Listennummer oder eine Symcon-Objekt-ID. Einmalige Ausführung erfordert,
dass der lokale Entwurf zum gespeicherten Stand passt. Mit CurrentDraft und
CheckDraft=true wird zusätzlich der übergebene Text gegen den lokalen Entwurf
verglichen. Für einen Aufruf ohne diesen Zusatzvergleich ausdrücklich leeren
String und false übergeben. Die normale Prüfung des gespeicherten Stands bleibt
wirksam. Ein Start kann echte Geräte schalten; Stopp nimmt gesendete Befehle
nicht zurück.

Beispiel für eine reine Verbindungsprüfung mit einer synthetischen Instanz-ID:

```php
$instanceID = 12345; // Durch die ID Ihrer Automationsinstanz ersetzen.
$request = Z2MA_CheckHealth($instanceID, false);
echo $request === '' ? 'Keine Anfrage gestartet.' : 'Anfrage gestartet; Antwort abwarten.';
```

Nach der Antwort lässt sich der Zustand separat lesen:

```php
$state = json_decode(Z2MA_GetTransportState($instanceID), true, 32, JSON_THROW_ON_ERROR);
echo $state['health'];
```

Weitere öffentliche Methoden dienen Formularcallbacks und Timern. Sie werden
durch das Modul aufgerufen und sind nicht für die manuelle Skriptbedienung nötig.
Hinweise zur begrenzten Ablaufaufzeichnung stehen in
[Debug und MQTT-Diagnose](../docs/DEBUG.md).
