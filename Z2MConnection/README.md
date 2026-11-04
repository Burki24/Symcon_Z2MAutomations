# Z2M-Verbindung

Dieses Modul verbindet genau ein Zigbee2MQTT-System mit Symcon. Es verwendet
einen vorhandenen MQTT Client oder MQTT Server. Anmeldung, Passwort, TLS und
Abonnements werden dort eingestellt.

Die zentrale Verwaltung mehrerer Systeme wird schrittweise vorbereitet. Dieser
Baustein kann bereits Verbindung, Erweiterungsstand und Gerätedaten prüfen sowie
Automationsabläufe zeitlich begrenzt aufzeichnen. Bestehende Automationsinstanzen
und Entwürfe bleiben erhalten. Sie werden durch ein Modulupdate oder das Anlegen
einer Verbindung **noch nicht in eine zentrale Instanz übernommen**.

Eine spätere ausdrücklich gestartete [lokale Übernahme](../docs/MIGRATION.md)
ordnet diese Verbindung genau einer zentralen Verwaltung zu. Zwei Verwaltungen
können sie danach nicht unabhängig beschreiben. Status, Katalog und Debug bleiben
an dieser Verbindung verfügbar; ein unabhängiger Erweiterungsupload ist nach
dem Besitzerwechsel gesperrt. Der Rückweg löst die Besitzerzuordnung, ohne die
Instanz, MQTT-Einstellungen oder Geräte zu löschen.

## Voraussetzungen und Einrichtung

- IP-Symcon 9.0 mit PHP 8.5 und ein erreichbares Zigbee2MQTT-System.
- Ein vorhandenes MQTT-Gateway zum zugehörigen Broker.
- Für Erweiterungsstatus, Gerätedaten und Ablaufaufzeichnung die eigene
  z2m-automations.mjs in diesem Z2M-System.

1. Instanz „Zigbee2MQTT Verbindung“ anlegen und mit dem vorhandenen MQTT-Gateway
   verbinden. Das Modul legt weder einen MQTT-Client noch Geräte an.
2. Das exakte Z2M-Basistopic eintragen; Groß-/Kleinschreibung beachten.
3. Im MQTT Client `BaseTopic/bridge/#` und `BaseTopic/symcon/automations/#`
   abonnieren. Ein bereits vorhandenes `#` genügt ebenfalls.
4. MQTT-Anbindung einschalten und die Einstellungen übernehmen.
5. „Zigbee2MQTT-Verbindung prüfen“ wählen. Das Ergebnis erscheint nach der
   passenden Antwort als Popup. Bei ausbleibender Antwort erscheint ein Hinweis.
6. „Erweiterungsstatus abfragen“ und danach „Gerätedaten aktualisieren“ wählen.
   Die Rückmeldungen stehen direkt darunter.

Mehrere Verbindungen dürfen denselben MQTT Client verwenden, wenn ihre
Basistopics verschieden sind. Unterschiedliche Broker benötigen jeweils ihre
eigene native MQTT-Anbindung. Gleiche Topics auf getrennten Brokern sind für
den einzelnen Transport unterscheidbar; für einen gemeinsamen Automationsverbund
bleiben eindeutige Basistopics erforderlich.

Das Öffnen des Formulars und Übernehmen unveränderter Einstellungen senden
keine MQTT-Anfrage. Die Prüfung wird über die beschriebenen Buttons gestartet.

## Einstellungen

| Feld / Property | Typ | Standard | Bedeutung |
| --- | --- | --- | --- |
| MQTT-Anbindung aktivieren / `Active` | Boolean | false | Empfang und Anfragen dieser Verbindung einschalten. |
| Zigbee2MQTT-Basistopic / `BaseTopic` | String | zigbee2mqtt | Höchstens 128 Zeichen; Buchstaben, Ziffern, Unterstrich und Bindestrich, optional durch `/` getrennt. Keine Platzhalter. |
| Wartezeit auf eine Antwort / `RequestTimeout` | Integer | 10 | 1 bis 120 Sekunden pro Anfragephase. |
| `SystemID` | String | automatisch erzeugte UUID | Stabile interne Systemkennung; nicht von Hand ändern. |
| `Generation` | Integer | 1 | Interne Zuordnungsgeneration; nicht von Hand ändern. Zulässig sind 1 bis 9007199254740991. |

Gateway, Topic, Systemkennung und Generation begrenzen, zu welchem System
Antworten und Gerätedaten gehören. Nach einem Wechsel ist eine neue Bestätigung
nötig. Ein vorhandener Katalog bleibt gespeichert, wird für eine geänderte oder
nicht bestätigte Verbindung jedoch nicht als aktuell ausgegeben.

## Erweiterung und Aufzeichnung

Die Anzeige nennt installierte und mitgelieferte Erweiterungsstände und weist
auf unterschiedliche Builds hin. Ein unbekannter Stand gilt nicht als aktuell.
„Mitgelieferte Erweiterung installieren oder aktualisieren“ verwendet die
offizielle Z2M-Schnittstelle und fordert vorher eine Bestätigung an. Externes
JavaScript muss in Z2M bereits freigegeben sein. Das Modul schaltet es nicht ein.
Ein Update kann laufende Automationen kurz unterbrechen. Erfolg wird erst nach
Bestätigung des neu geladenen Empfängers gemeldet.

„Aufzeichnung für 10 Minuten starten“ macht die Ablaufmeldungen im Debugfenster
dieser Instanz sichtbar. Bei Aktionen zwischen Systemen beide Verbindungen
aufzeichnen. „Eigene Aufzeichnung beenden“ beendet nur die eigene Aufzeichnung.
Sie endet auch automatisch; Automationen werden dadurch weder gestartet noch
gestoppt. Unveränderte Einstellungen und Formularaufrufe verlängern ihre Endzeit
nicht. Bei geänderter Verbindung oder Empfängersitzung endet die Aufzeichnung.

## Statusvariablen

Alle Variablen dienen der Anzeige und sind nicht schaltbar.

| Ident | Typ / Einheit | Bedeutung |
| --- | --- | --- |
| MQTTConnected | Boolean / keine | Native MQTT-Anbindung aktiv und verbunden. |
| BridgeOnline | Boolean / keine | Z2M hat online gemeldet oder die Verbindungsprüfung beantwortet. |
| Z2MVersion | String / keine | Zuletzt empfangene Z2M-Version der aktuellen Verbindung. |
| ExtensionInstalled | Boolean / keine | Erweiterung in der Z2M-Liste erkannt, Upload bestätigt oder bereiter Empfänger durch passende Statusantwort bestätigt. |
| HealthStatus | String / keine | Ergebnis der Verbindungsprüfung, zum Beispiel „Funktionsfähig“, „Antwort von Z2M steht noch aus“ oder „Keine rechtzeitige Antwort“. |
| ExtensionReady | Boolean / keine | Empfänger durch passende frische Antwort bestätigt. |
| StoredRevision | Integer / keine | Bestätigte gespeicherte Revision; 0 bedeutet leer oder nicht bestätigt. |
| OperationStatus | String / keine | Laufender oder zuletzt abgeschlossener Erweiterungsauftrag. |
| LastError | String / keine | Verständlicher Grund eines fehlgeschlagenen Auftrags. |
| ExecutionActive | Boolean / keine | Ausführung laut zuletzt bestätigtem Z2M-Status aktiv. False bedeutet gestoppt oder nicht bestätigt. |

„Online“ und „Ausführung aktiv“ sind Zustandsmeldungen, keine ständige Prüfung.
Bei Verbindungsabbruch verlieren Bestätigungen ihre Gültigkeit. Nach erneutem
Verbinden den Status abfragen. Eine laufende Z2M-Automation arbeitet unabhängig
von der Erreichbarkeit von Symcon weiter.

## Öffentliche Funktionen

`InstanceID` ist jeweils die ID dieser Verbindungsinstanz im Symcon-Objektbaum.

| Funktion | Rückgabe und Wirkung |
| --- | --- |
| `Z2MC_CheckConnection(int InstanceID): string` | Startet Verbindungsprüfung mit Popup; Transaktionskennung bei angenommener Anfrage, sonst leer. |
| `Z2MC_CheckHealth(int InstanceID, bool RefreshExtension = false): string` | Gleiche Prüfung ohne Popup; optional anschließend einmalig Erweiterungsstatus abfragen. |
| `Z2MC_RequestExtensionStatus(int InstanceID): string` | Auftragskennung oder leer. Liest Status und Build; verändert keine Regeln. |
| `Z2MC_RequestDeviceCatalog(int InstanceID): string` | Auftragskennung oder leer. Prüft zuerst den Empfänger, lädt danach Geräte, Kanäle, Gruppen und Szenen. Überträgt keine Entwürfe. |
| `Z2MC_InstallExtension(int InstanceID, bool Notify = false): string` | Auftragskennung oder leer. Lädt ausschließlich die mitgelieferte Datei hoch und prüft anschließend die Bereitschaft. `Notify` aktiviert die Popup-Rückmeldung. |
| `Z2MC_GetConnectionInfo(int InstanceID): string` | JSON mit Vertragsversion, Systemkennung, Generation, Gateway, Topic und bereinigtem Status; keine neue MQTT-Anfrage. |
| `Z2MC_GetTransportState(int InstanceID): string` | Bereinigter lokaler Transportstatus als JSON; keine neue MQTT-Anfrage. |
| `Z2MC_GetDeviceCatalog(int InstanceID): string` | JSON mit Systemkennung, Generation, `available`, Ladezeit und normalisiertem Katalog; keine neue MQTT-Anfrage. Bei `available: false` leere Gerätelisten. |
| `Z2MC_StartRuntimeDebug(int InstanceID, int Seconds = 600): bool` | Fragt eine eigene Aufzeichnung für 1 bis 900 Sekunden an. True bedeutet Anfrage gesendet, noch nicht von Z2M bestätigt. |
| `Z2MC_StopRuntimeDebug(int InstanceID): bool` | Fragt Ende der eigenen Aufzeichnung an; True bedeutet gesendet. |
| `Z2MC_ExpireRequest(int InstanceID): void` | Prüft lokale Anfragezeitlimits; vom Modultimer verwendet. |
| `Z2MC_ExpireRuntimeDebug(int InstanceID): void` | Prüft Ende der lokalen Aufzeichnung; vom Modultimer verwendet. |
| `Z2MC_GetCompatibleParents(int InstanceID): string` | JSON mit den unterstützten nativen MQTT-Gateways für die Konsole. |

Eine Auftragskennung bestätigt das Annehmen beziehungsweise Absenden der Anfrage,
nicht ihren erfolgreichen Abschluss. Der Status folgt erst nach passender
Z2M-Antwort. Je Verbindung läuft höchstens ein Erweiterungsauftrag; eine zweite
Anfrage ersetzt ihn nicht. Zeitablauf führt zu keiner automatischen Wiederholung.

Der Katalog enthält die von Z2M gemeldeten Zugriffsrechte (`access`: 1 lesbar,
2 schreibbar, 3 beides). Diese Daten ersetzen nicht die zusätzliche Prüfung der
Symcon-Schaltrechte im Automationseditor. `loadedAt` ist ein UTC-Unixzeitstempel
in Sekunden; er zeigt den Ladezeitpunkt, keine permanente Aktualität.

Das Modul stellt in dieser Stufe keine Funktionen zum Speichern, Aktivieren
oder Stoppen von Automationsregeln bereit. Dafür wird weiterhin die vorhandene
[Z2MAutomations-Instanz](../Z2MAutomations/README.md) verwendet.

Lizenz: [PolyForm Noncommercial License 1.0.0](../LICENSE).
