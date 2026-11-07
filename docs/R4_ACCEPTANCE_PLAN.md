# R4: Nachweisabgleich und begrenzte Restabnahme

Stand: **06.11.2026**. Planungsbasis: `e537ce579b673d6a5be631518834ebd0094e7692`
auf `dev`; zu Beginn sauberer Arbeitsbaum. **R4/B3 ist im beschriebenen Testumfang abgeschlossen.**
Der ursprüngliche Abgleich prüfte Belege und Offline-Tests und plante
die Restabnahme. Dabei wurde keine Test-/Live-Instanz angesprochen, angelegt,
umgestellt oder neu gestartet. Kein Besitzerwechsel und kein MQTT-Versand an
reale Broker. Nachfolgende Praxisergänzungen nennen die inzwischen ausgeführten Teilfälle.

Lokaler Prüfbezug: PHP 8.5.10, Node 24.20.0, VisualizationAssetHelper 1.1.4.
Das generierte Erweiterungsartefakt mit Buildkennung
`4d6cda17434819e331927b17515470f9d7f427a192f9b874b28e671228e00722` stimmt
mit Quelle und Schema überein (`npm run check:extension`). Eine aktuelle
Kernel-/Z2M-Version wurde für diesen lokalen Abgleich nicht neu abgefragt.

## Was bereits belegt ist

Praxisergänzung vom 06.11.2026: Eigener temporärer MQTT-Server in Test-Symcon
9.1 und zwei synthetische Erweiterungs-Peers über echtes MQTT verbunden.
Vorschau, Verwerfen, Bestätigung und Rücknahme über die tatsächlich erzeugten
Formularcallbacks im Kernel ausgeführt; kein zusätzlicher Zeigerklicknachweis.
Beide YAML-Dateien und Definitionen einschließlich IDs, Folge und Partnerfreigabe
exakt übernommen. Zweites Einrichten erhält das erste System. Lokale Änderung
vor Rücknahme zusätzlich archiviert; Rücknahme stellt den ursprünglichen
Systemplatz wieder her und gibt dessen Claim frei. Wiederholung sowie erneute
Einrichtung/Rücknahme erfolgreich. Vier Archive zunächst unabhängig auf
Prüfsummen geprüft; Windows-ACLs daraus nicht abgeleitet.
MQTT-Zähler: sieben Status-, vier Editoranfragen, keine anderen Aufträge.
Deutsche Datei-Debugmeldungen für Vorschau, Übernahme und Rücknahme bestätigt.
Eigene Testobjekte, Prozesse, Listener und Zugangsdaten entfernt; Aufzeichnungen
beendet. Private Sicherungen erhalten. Vorhandene Systemdaten und Auswahl
unverändert; Live-Symcon und produktive Broker/Z2M nicht angesprochen.

Altübernahme P3 am 06.11.2026: Zwei eigene synthetische Einzelinstanzen in
Test-Symcon 9.1 per MCP gesichert, vorbereitet, übernommen und zurückgegeben.
YAML, Kennungen, Aktionsfolgen und Partnerfreigaben exakt erhalten; alte Editoren
während der Übernahme gesperrt und nach dem Rückweg wieder bearbeitbar.
Quellattribute, Konfigurationen, Arbeitsdaten, Objektkennungen und MQTT-Zuordnungen
wiederhergestellt; zentrale Änderung zusätzlich in der Rückwegsicherung erhalten.
Acht private Archive unabhängig auf Prüfsummen geprüft. Wiederholung erfolgreich,
deutsche Debugmeldungen bestätigt; Übernahme und Rückweg verursachten null
MQTT-Aufträge. Eigene Testobjekte, Prozesse und Zugangsdaten entfernt; bestehende
Systemdaten und Auswahl unverändert. Native Zeigerbedienung und Zuordnungen echter
Geräteaktionen/Archive wurden dabei nicht geprüft; Live-System blieb unberührt.

P4 am 06.11.2026 in Test-Symcon 9.1 mit isoliertem MQTT-Server und zwei
synthetischen Erweiterungs-Peers bestanden (öffentliche Modulaufrufe per MCP).
Zurückgehaltene Antwort nach sechs Sekunden durch den echten Timer als Timeout
beendet; eigener Client-Socket während des nächsten Leseauftrags geschlossen:
Auftrag abgebrochen, zweites System weiterhin erreichbar. Nach Wiederverbindung
kein automatischer Neuversand; verspätete Antworten verworfen. Erst ein neuer
ausdrücklicher Abruf mit frischen Anfragekennungen bestätigte Status und Katalog.
Entwürfe, Auswahl, offene Eingaben und Besitzer auf beiden Systemen unverändert;
deutsche Debugmeldungen geprüft. Testobjekte, Prozesse, Listener und Zugangsdaten
entfernt, bestehende Benutzerstände unverändert; Live-System nicht angesprochen.

P5 am 06.11.2026 bestanden: Benutzer hat den Windows-Dienst von Test-Symcon 9.1
geordnet neu gestartet; neuer Dienstprozess und Kernel-Startzeitpunkt bestätigt.
Vorschau samt Vorgangskennung, Entwürfe, Auswahl, offene Eingaben und Besitzer
unverändert erhalten; beide privaten Sicherungen bytegleich und prüfsummengültig.
Natives Formular per Kernelaufruf wieder nutzbar, Übernahmebutton freigegeben.
Weder Neustart noch Vorschauabruf wiederholen MQTT-Aufträge; erst ausdrückliche
Bestätigung sendet eine frische Statusprüfung. Übernahme und wiederholter Rückweg
erfolgreich; lokale Zusatzänderung in dritter geprüfter Rückwegsicherung erhalten.
Deutsche Debugmeldungen bestätigt. Eigene Testobjekte, Prozesse und Zugangsdaten
entfernt; bestehende Benutzerentwürfe, Zuordnungen, Konfigurationen und Statuswerte
im Ausgangszustand. Live-System und reale Z2M-Prozesse unverändert.

P6 am 06.11.2026 in Test-Symcon 9.1 per MCP bestanden: synthetischen
Erweiterungs-Peer bei erhaltener Datenablage in einem neuen Node-Prozess gestartet.
Prozess- und Sitzungskennung geändert, YAML-Prüfsumme und Revision unverändert.
Alte Vorschau bei Bestätigung verständlich abgelehnt; kein Claim, kein Import,
vorhandener zweiter Entwurf einschließlich Auswahl und offener Eingaben erhalten.
Frisches Einlesen und Bestätigen erfolgreich, danach lokaler Rückweg exakt.
Nur Status-/Editoranfragen über MQTT, deutsche Debugmeldungen bestätigt.
Eigene Testobjekte, Prozesse, Datenablagen und Zugangsdaten entfernt; bestehende
Benutzerstände unverändert, Live-System unberührt. Kein realer Z2M-Neustart.

Restprüfung am 06.11.2026 in Test-Symcon 9.1:
- Gerätezuordnungen bestanden: zwei native Geräte- und zwei Gruppeninstanzen an
  isoliertem MQTT, gleiche Geräte-/Gruppenkennungen je Basistopic. Vier vom nativen
  Modul angelegte Variablen, Standardaktionen, eine benutzerdefinierte Aktion sowie
  Archivzuordnung und je zwei synthetische Historienwerte bleiben nach Altübernahme,
  frischem Katalogabruf und Rückweg unverändert. Aktionsauswahl je System bestätigt;
  deutsche Debugmeldungen geprüft, keine Schaltbefehle. Testobjekte, Zugangsdaten
  und synthetische Archivdaten entfernt, vorhandene Benutzerentwürfe unverändert.
- **Windows-Dateirechte: lokale Schutzempfehlung, kein Beta-Blocker.** Auf der
  geprüften Testinstallation erben auch frische Archive Leserechte für die lokale
  Gruppe Benutzer (SID S-1-5-32-545); ein nicht erhöhter Prozess konnte das
  synthetische Manifest öffnen. `chmod(0600)` in
  [MigrationArchive.php](../libs/MigrationArchive.php) belegt keine private NTFS-ACL.
  Bei mehreren oder nicht vollständig vertrauenswürdigen lokalen Konten wird
  empfohlen, die Rechte des Sicherungsverzeichnisses lokal einzuschränken.
  Der Befund belegt lokalen Dateizugriff, keinen Zugriff über MQTT oder Internet;
  eine allgemeine Storepflicht oder Beta-Sperre wurde daraus nicht nachgewiesen.
  Rechte und Implementierung bleiben unverändert.
- Native Zeigerbedienung war zunächst offen: Windows-Prüfsteuerung startet auch nach
  Wiederholung und Reset mit „failed to write kernel assets“ nicht. Kein Nachweis
  eines Modulfehlers; der folgende manuelle Konsolenlauf schließt den Nachweis.

Native Klickprüfung am 06.11.2026 abgeschlossen: Benutzer bedient die echte
Testkonsole manuell und bestätigt die Anzeigen; Kernelzustände, MQTT und native
Dateidebugs werden je Schritt unabhängig geprüft. Verbindungshäkchen und Zuordnung,
Systemauswahl über „Entwurf dieses Systems bearbeiten“, Einlesen, Verwerfen,
Bestätigungsdialoge, Übernahme beider Systeme und automatischer Formularneuaufbau
ohne falsche Sperrmeldung bestanden. B bleibt beim erneuten Einrichten von A
bytegleich erhalten. Zwei native Rücknahmen erfolgreich; eine zuvor per MCP
eingebrachte lokale Namensänderung bleibt zusätzlich gesichert. Sechs Archive
prüfsummengültig. TXT-Debugstarts: eine Zuordnung, vier Leseaufträge, drei
Bestätigungen, einmal Verwerfen, zwei Rücknahmen; HEX-Zeilen nicht doppelt zählen.
Sieben Status- und vier Editoranfragen, keine Speicher-/Aktivierungs-/Gerätebefehle.
Eigene Testobjekte, Prozesse und Zugangsdaten entfernt; bestehende Benutzerstände
unverändert, Live-System unberührt. Kein automatisierter Windows-Zeigerlauf behauptet.

| Bereich | Vorhandener Beleg | Grenze / Konsequenz für R4 |
| --- | --- | --- |
| Lokale Sicherung, Altübernahme und Rückweg | `tests/migration.php`: 1/2/8 Systeme, echte temporäre Archivdateien, YAML-Bytes, Freigaben, gleiche lokale Kennungen, Besitzer, Konflikte und alle instrumentierten Übergabe-/Rückgabeschritte. Frisch erfolgreich unter PHP 8.5.10. | Symcon-Stubs; neu erzeugte Testobjekte mit übernommenen persistenten Attributen simulieren Wiederanlauf. Kein echter Kernelneustart, keine native Dateirechteabnahme. |
| Direkte Einrichtung ohne Altinstanzen | `tests/workspace-setup.php`: 1/2/8 Systeme, gemeinsame/getrennte Broker, gespeicherter/leerer Empfänger, Vorschau, Bestätigung, Claims und lokaler Rückweg. Native Callback-Skripte werden an Kernel-Fixtures ausgeführt. Frisch erfolgreich. | Kein vollständiger Zeiger-Bediennachweis am echten Kernel. Unterbrochene lokale Schritte werden mit Test-Checkpoints und neuen Stubobjekten fortgesetzt. |
| Zentrale Betriebsaufträge und Rückwegsperre | `tests/workspace-runtime.php`: 1/2/8 Systeme und vorhandener Peer mit tatsächlichem Erweiterungsartefakt; Status, Betrieb, Einzellauf, Bedingungen und unveränderte Entwürfe frisch erfolgreich. `tests/workspace-transfer.php` und die Gesamtsuite decken zusätzlich mögliche externe Schreibvorgänge ab. | Der Peer ersetzt Z2M und Zigbee-Umgebung synthetisch. Der frische Lauf hier ist keine erneute Abnahme der gesamten Suite oder eines realen Z2M-Neustarts. |
| Bisheriger Zentralen-/Designerweg | DEVELOPMENT.md, insbesondere „Browserstart und sichtbarer Systemwechsel am 04.11.2026“, sowie die nachfolgenden Bediennachweise. Systemtrennung, Bearbeitung, Übertragung und Betrieb wurden praktisch geprüft und ursprüngliche Daten wiederhergestellt. | Kein zusammenhängender aktueller nativer Einrichtungs-/Rückweglauf mit eigener unbeanspruchter Verbindung. Nicht wegen historischer „offen“-Sätze pauschal wiederholen. |
| Reine Neuanlage am Beta-Kandidaten | BETA_ABNAHME.md: beide Modultypen auf echtem Test-Symcon 9.1 ohne Parent angelegt, Konfiguration wiederholt angewandt, Formulare gelesen und eigene Objekte entfernt. | Keine vollständige Einrichtung, Besitzübergabe, Archivauswertung oder Rücknahme. |
| Installation und automatischer Katalogabruf | R2 und R3, NATIVE_INSTALLATION_TEST.md und INSTALLATION_CATALOG_EVIDENCE.md. Beide Systeme mit Build, Sitzung, unabhängigem Zustandsvergleich und ursprünglichem Betrieb belegt. | Abgeschlossen; keine weitere Erweiterungsinstallation für R4 nötig. Die damalige Freigabe gilt nicht für einen neuen Installationslauf. |
| Wiederanlauf der Erweiterung | DEVELOPMENT.md, „Gemeinsame Folgen: Parallelbetrieb und Neustartprüfung am 02.11.2026“; zusätzlich die bestätigten Schuppen-/Schlafzimmer-Neustarttests aus dem Verlauf. Neue Sitzung, wieder aktiver Automatikbetrieb und verworfener wartender Lauf belegt. | Belege vor dem Zentralenumbau. Sie gelten für die jeweilige geprüfte Erweiterung und ersetzen keinen Kernel-/Journalnachweis der Zentrale. Kein Anlass, erneut reale Leuchten zu schalten. |
| Native Kachel / Scrollgrenze | B4 am 06.11.2026 mit regulärem Helper-Bundle 3.30.7 nach Test-Modulupdate geschlossen. | Keine erneute Mausradkorrektur; bei den späteren Formularabläufen nur normale Bedienbarkeit mitprüfen. |

Fundstellen: [MIGRATION.md](MIGRATION.md), [WORKSPACE.md](WORKSPACE.md),
[ADR 0027](adr/0027-direct-central-setup-from-z2m.md) und
[Beta-Abnahme](BETA_ABNAHME.md). Historische Implementierungsnotizen bleiben
erhalten; die Grenzen in dieser Tabelle sind für den nächsten Lauf maßgeblich.

## Testaufbau vor jeder operativen Prüfung

1. Ausschließlich die freigegebene Test-Symcon-Installation verwenden. Die
   vorhandenen Test-MQTT-Clients erreichen produktive Z2M-Systeme und dienen
   deshalb nicht als Gegenstelle für die Einrichtungs-/Rückwegversuche.
2. Vorgesehen ist ein eigener temporärer nativer MQTT-Testserver mit freiem
   Port, zwei eigenen Clientverbindungen und ausschließlich synthetischen
   Basistopics. Verfügbarkeit und Port werden vor dem Anlegen geprüft.
   Keine Bridge oder Weiterleitung zu den vorhandenen Mosquitto-Brokern.
   Dieser Aufbau ist Testinstrumentierung, keine Änderung der Betriebsarchitektur.
3. `tests/extension-peer.mjs` nutzt bereits das tatsächliche Erweiterungsartefakt,
   aber nur JSON-Zeilen über stdin/stdout und ein künstliches Z2M-Eventsystem.
   Die Anbindung an den temporären MQTT-Testserver fehlt noch. Sie ist vor
   nativer Abnahme vorzubereiten; kein eigenes Brokerprotokoll implementieren.
   Der vorhandene `control: restart` erzeugt außerdem nur eine neue
   Erweiterungsinstanz im selben Peerprozess. Ein echter neuer Peerprozess mit
   erhaltener privater Datenablage ist gesondert vorzubereiten, falls P6 als
   Prozessneustart ausgeführt wird; das heutige Skript räumt seine Ablage beim
   Beenden auf. Peer und Testtransport bleiben außerhalb des Betriebsmoduls. Keine neue
   Produktfunktion, Bibliotheksabhängigkeit oder Änderung an `libs/helper` nötig.
4. Eigene temporäre Zentralen, Verbindungen und für den Altweg eigene
   Quellinstanzen anlegen. Keine bereits beanspruchte Benutzerverbindung
   übernehmen. Falls native Geräte-/Actionbezüge benötigt werden, ausschließlich
   eigene synthetische Geräteinstanzen am Testtransport verwenden; Verfügbarkeit
   der vorhandenen Z2M-Gerätemodule vorher klären. Kein Ersatz durch reale Geräte.
5. Gegenstelle mit unterschiedlichen, bekannten Dokumenten je System vorbereiten:
   deaktivierte Regeln, eine wiederverwendbare Folge und eine gerichtete
   Partnerfreigabe. Gleiche lokale Kennungen in beiden Systemen sind beabsichtigt.
   Keine Regel aktivieren und keine Gerätebefehle ausführen.
6. Versions-/Buildnachweis festhalten: tatsächlicher Gitstand und Asset-Hashes,
   PHP-/Kernel-/Clientversion, Erweiterungsbuild sowie Testtransport. Beim Peer
   ausdrücklich „synthetische Z2M-Gegenstelle“ eintragen, keine erfundene Z2M-Version.
   Library-Version `0.1` mit Build/Datum `0` identifiziert den Kandidaten nicht allein.

Der Aufbau ist eine Voraussetzung, noch kein vorhandener Praxisbeleg. Scheitert
seine sichere Trennung, bleibt der betroffene Teilfall offen. Produktives Z2M
ist kein stiller Ersatz für die fehlende Testgegenstelle.

## Geplante Teilfälle in Ausführungsreihenfolge

| ID | Ablauf | Abnahmekriterium |
| --- | --- | --- |
| R4-P0 | Testaufbau, Versionsbezug und private Ausgangssicherung herstellen. | Nur eigene Objekte erreichbar; keine Route zu produktiven Brokern. Dokumente, Auswahl, offene Arbeit, Referenzen, MQTT-Zuordnungen und Besitzer vorab privat erfasst. Bisherige Benutzerobjekte bleiben unberührt. |
| R4-P1 | Frische Zentrale im nativen Formular: beide eigenen Verbindungen auswählen; erstes System einlesen, Vorschau verwerfen, erneut einlesen und ausdrücklich übernehmen. Zweites System anschließend übernehmen. | Vorschau beansprucht keinen Besitzer. Nach Bestätigung YAML bytegleich, Kennungen und Freigaben erhalten, passende Geräte und Entwürfe je System. Automatischer Formularneuaufbau ohne falsche Sperrmeldung; erstes Dokument beim zweiten unverändert. |
| R4-P2 | Am zuletzt direkt eingerichteten System eine harmlose lokale Entwurfsänderung übernehmen, sichern und den nativen lokalen Rückweg wählen. Dieselbe Rücknahme erneut abfragen; danach erneut einrichten. | Zusätzliche Rückwegsicherung enthält den geänderten Entwurf. Vorheriger Systemplatz, Referenzen und Claim exakt wiederhergestellt; anderes System unverändert. Wiederholung führt nichts doppelt aus; alte Browser-/Editorberechtigungen bleiben ungültig. Keine Z2M-Speicherung oder Betriebsänderung. |
| R4-P3 | Getrennt vom Direktweg eigene bisherige Einzelinstanzen mit synthetischen Entwürfen über die vorhandenen öffentlichen Migrationsfunktionen per MCP sichern, vorbereiten und übernehmen. Formularzustände danach nativ prüfen, lokale Änderung sichern und zurücknehmen. | Private Quellarchive/Manifest lesbar und prüfsummengültig; Ziel erhält Original-YAML, IDs und Freigaben. Alte Quelle ist während Zentralenbesitz gesperrt. Rückweg gibt nur die zugehörigen Claims/Quellen frei und stellt beide Seiten exakt wieder her. Geräte-/Variablen-/Action-/Archivzuordnungen bleiben erhalten. Null durch Migration/Rückweg verursachte MQTT-Aufträge. Kein behaupteter Zeigerklick auf einen nicht vorhandenen vollständigen Legacy-Übernahmeassistenten. |
| R4-P4 | Nur einen eigenen temporären Client-Socket während eines Leseauftrags unterbrechen, Fristablauf abwarten, wieder verbinden und ausdrücklich neu lesen. | Ausfall und Wiederverbindung anhand tatsächlicher Antworten nachgewiesen. Kein automatischer Neuversand eines verlorenen Auftrags. Zweites Testsystem nutzbar, Entwürfe erhalten. Neue ausdrückliche Anfrage verwendet frischen Kontext. |
| R4-P5 | Bei stabil gespeicherter eigener Einrichtungsvorschau Test-Symcon-Kernel geordnet neu starten, Wiedererreichbarkeit abwarten, Vorschau lesen, ausdrücklich bestätigen und lokal zurücknehmen. | Vorschau/Journal und eigene Archive über echten Neustart erhalten; kein impliziter MQTT-Neuversand beim Wiederöffnen. Frische Verifikation erst nach Bestätigung. Besitzer, Dokumente und Rückweg bleiben konsistent. Alle bestehenden Testverbindungen und lokalen Benutzerstände nachher wieder im Ausgangszustand. |
| R4-P6 | Eigene Einrichtungsvorschau erzeugen; dann nur den synthetischen Peer in einem neuen Prozess mit erhaltener privater Datenablage starten. Die alte Vorschau ausdrücklich bestätigen und anschließend neu einlesen. | Neue Empfängersitzung führt zur verständlichen Ablehnung der alten Vorschau, ohne Claim oder Dokumentübernahme. Erst ein neuer ausdrücklicher Lese-/Bestätigungsablauf erlaubt die Einrichtung; vorhandene Dokumente bleiben erhalten. Falls nur der vorhandene Objektneustart ausgeführt wird, genau so abgrenzen und den Prozessnachweis offen lassen. Keine allgemeine reale Z2M-/Gerätefreigabe daraus ableiten. |

P1/P2 sind der erste praktische Block. P3 hat eigene Quellen und verwendet
nicht dieselbe Besitzer-Epoche. P4–P6 folgen erst nach abgeschlossenem lokalem
Rückweg und geprüftem Ausgangszustand. Ein Kernelneustart betrifft die gesamte
Testinstallation einschließlich ihrer bestehenden MQTT-Clients; er ist kein
nebenwirkungsfreier Einzelinstanztest und benötigt einen eigenen Praxisauftrag.
Keine Pi-, Broker-, produktive Z2M- oder Live-Symcon-Neustarts in diesem Plan.

P5 prüft einen echten geordneten Kernelneustart an einer stabilen Journalphase.
Die gezielt unterbrochenen Commit-/Rückgabegrenzen bleiben durch die vorhandenen
Offline-Tests belegt. Weder ein harter Prozessabbruch mitten in `rename` noch
ein Stromausfall wird damit als praktisch geprüft ausgegeben.
P6 prüft den Empfängersitzungswechsel im zentralen Einrichtungsweg. Es werden
keine Automationen gestartet; daraus folgt kein neuer praktischer Nachweis
für das Fortsetzen oder Verwerfen laufender Aktionen. Dafür bleiben die
abgegrenzten vorhandenen Runtime-/Z2M-Neustartbelege maßgeblich.

## Vergleich, Debug und Wiederherstellung

- Alle vollständigen Ausgangsdaten und Archive ausschließlich privat außerhalb
  der Repositories speichern. YAML-Bytes und SHA-256 unabhängig vergleichen;
  Editorinhalt, IDs, `mesh_peers`, Auswahl und offene Arbeit separat vergleichen.
  Flüchtige Zugangsdaten nicht wiederherstellen oder in Berichte übernehmen.
- Besitzer, Generation, Referenzen und native Objekt-/Variablenzuordnungen vor
  und nach jedem Übergang erfassen. Ein übereinstimmender Listenname genügt nicht.
  Nach Rückweg muss die alte zuständige Quelle wieder bedienbar sein; nach
  erneuter Übernahme darf eine alte Browseransicht nicht schreiben.
- Private Archivdateien nach Manifest und SHA-256 kontrollieren. Dateirechte
  passend zum tatsächlichen Betriebssystem prüfen: Windows-Zugriffsrechte nicht
  aus einem Unix-Modus `0600` ableiten. Das vorhandene synthetische Dateitest-
  Ergebnis ersetzt diese Hostprüfung nicht.
- Native Dateidebugs nur an eigenen Testinstanzen beginnen und nachher beenden.
  Vorgang, Systembezug, Journalphase und Annahme/Bestätigung getrennt auswerten.
  Keine YAML, Gerätewerte, Tokens oder Archivkopien in versionierte Debugauszüge.
- Der Testtransport zählt die tatsächlich versendeten Aufträge. Direkte
  Einrichtung verwendet `status`/`editor`; etwaige automatische reine
  Verbindungsprüfungen beim Aufbau separat ausweisen. Migration/Rückweg selbst
  sendet nichts. `store`, Aktivierung, Stopp, Einzelstart, Erweiterungsinstallation,
  Z2M-Neustart und Gerätebefehle sind für die Einrichtungs-/Rückwegfälle gesperrt.
- Reine Anzeigen können lokale Arbeit fortsetzen: insbesondere
  `GetWorkspaceSetupStatus` ist bei `committing`/`rolling_back` kein rein
  beobachtender Getter. Ihn nur für den eigenen Testvorgang verwenden.
- Nach einem möglichen externen Schreib- oder Betriebsauftrag ist der lokale
  Rückweg absichtlich gesperrt. Bestehende Benutzerzentralen daher weder als
  Testquelle zurückrollen noch Besitzerattribute zum Erzwingen ändern.
- Bei Abbruch die eigenen Archive erhalten und die Phase dokumentieren.
  Keine unvollständig freigegebenen Testobjekte löschen. Erst nach konsistentem
  Rückweg eigene Instanzen, Transport, Peer und Browser schließen; bei Bedarf
  private Belege weiter aufbewahren. Keine fremden Objekte oder Sicherungen löschen.

## Abschlussmaßstab und derzeitiger Status

Für jeden Praxisfall sind Kandidatenstand, tatsächlicher Bedienweg, erwartete
Wirkung, Vorher-/Nachher-Vergleich und Debugbeleg erforderlich. Ein reiner
MCP-Funktionsaufruf bestätigt den Modulweg, kein nativer Zeigerklick. Ergebnis
je Fall: bestanden, fehlgeschlagen oder offen; keine Übertragung eines
Stub-/Peer-Nachweises auf eine ungeprüfte reale Z2M-Installation.

- Nachweisabgleich und Testplan: abgeschlossen.
- Frische Offline-Prüfung: `php tests/migration.php`,
  `php tests/workspace-setup.php`, `php tests/workspace-runtime.php` erfolgreich.
  Struktur, zentrale Kodierung, Erweiterungsartefakt, lokale Dateilinks und
  UTF-8/LF ohne BOM ebenfalls geprüft. Keine neue Gesamtsuitenabnahme behauptet.
- P0–P2: MQTT-Testanbindung und Modul-/Callbackpraxis für direkte Einrichtung
  und Rückweg einschließlich manuell bestätigter nativer Klickprüfung bestanden,
  anschließend aufgeräumt. P3 samt nativen Geräte-/Action-/Archivzuordnungen mit synthetischen
  Daten im echten Kernel bestätigt; P4–P6 im beschriebenen Umfang bestanden.
- R4/B3: im beschriebenen Test-Symcon-9.1-Umfang abgeschlossen.
  Windows-Dateirechte werden separat als lokale Schutzempfehlung geführt,
  nicht als Beta-Blocker. R1–R3 und B4 bleiben abgeschlossen.
- Symcon 9.0, stabile Z2M-Versionen und weitere Converter gehören weiterhin zu
  R5/B1/B2. Store-/Releaseidentität bleibt B5. Keine Beta-Veröffentlichungsfreigabe.
