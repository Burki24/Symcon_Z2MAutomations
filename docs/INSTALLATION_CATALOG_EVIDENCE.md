# R3: Installation und automatischer Geräteabruf

Belegabgleich und abschließende Praxisprüfung vom **06.11.2026** zur
[Prüfliste](PRUEFLISTE.md). Zunächst vorhandene Berichte und Dateidebugs
zugeordnet, anschließend den fehlenden S1-Vergleich nach ausdrücklicher
Praxisfreigabe ergänzt.

**Ergebnis:** Die reale Kette bestätigte Installation → genau ein automatischer
Geräteabruf → erfolgreich geladener Katalog ist für beide Systeme belegt.
Für beide Systeme ist zusätzlich der unabhängige Vorher-/Nachher-Vergleich
vollständig. **R3 ist im vereinbarten Umfang abgeschlossen.** Der ursprüngliche
Bearbeitungsstand und Automatikbetrieb sind wieder bestätigt; keine YAML
gespeichert, keine Regel manuell aktiviert und keine Geräte geschaltet.

## Zuordnung der vorhandenen Belege

S1 und S2 bezeichnen das erste und zweite System der bisherigen Praxisprüfung.
Die Test-Symcon-Verbindungen führen zu produktiv genutzten Z2M-Systemen;
die historischen Installationen waren jeweils ausdrücklich freigegeben.
Rohberichte, Gerätekennungen, Topics, Empfängersitzungen und Prüfsummen bleiben
in der privaten Belegablage außerhalb des Repositorys.

| Beleg | Was er tatsächlich nachweist | Grenze |
| --- | --- | --- |
| Frühere Browserinstallationen beider Systeme am 05.11.2026, vor 15:39 Uhr | Bestätigung, tatsächlicher Upload und erfolgreicher Installationsabschluss mit passendem Build; Abbruch ohne Upload. | Diese Läufe liegen vor der Implementierung des automatischen Nachladens. Kein Nachweis dieses späteren Ablaufs. Die damaligen nativen Zählergrenzen sind in NATIVE_INSTALLATION_TEST.md erläutert. |
| Manueller Geräteabruf im Browserdesigner auf beiden Systemen | Erfolgreiches Laden, richtige Gerätelisten, sichtbare Phasen und erhaltene Entwürfe/Betriebszustände durch unabhängige Vergleiche. | Eigenständige manuelle Abrufe. Ihre Vergleiche ersetzen keine Ausgangs- und Endstände desselben automatischen Installationslaufs. |
| Archivierte Datei-Debugkette von S1 am 05.11.2026, 15:48:46–15:48:51 Uhr | Ein Upload, Bestätigung des erwarteten Builds `4d6cda174348`, automatischer Start und erfolgreicher Geräteabruf im selben System. Anfrage und Antwort sind jeweils eindeutig zugeordnet. | Keine unabhängigen Ausgangs-/Endkopien des lokalen Bearbeitungsstands und Betriebs für genau diesen Lauf in den zugeordneten Belegen. |
| Kombinierter R2-Praxisbeleg von S2 am 06.11.2026 | Strikter nativer Klicknachweis, neue Empfängersitzung, erwarteter Build, genau ein automatischer Katalogabruf mit Abschluss und unabhängige Vergleiche beider lokalen Arbeitsbereiche und des Betriebs. | Nachweis an Test-Symcon 9.1 und dem genannten Build, keine allgemeine Versionsabnahme. |
| Freigegebener R3-Praxislauf von S1 am 06.11.2026, Installationskette 07:28:08–07:28:13 Uhr, abschließender Vergleich 07:29:17 Uhr | Ein zentraler Installationsaufruf per Test-MCP, ein Upload, neue Sitzung und erwarteter Build, genau ein automatischer Katalogabruf. Nichtleere Regelauswahl und absichtlich offene Eingabe bleiben ebenso wie beide Dokumente, die gemeinsame Systemauswahl und der Betrieb erhalten; anschließend ursprüngliche Bearbeitungsdaten exakt wiederhergestellt. | Öffentlicher Moduleinstieg an Test-Symcon 9.1 mit produktiv genutztem Z2M; kein zusätzlicher nativer Zeigerklick- oder Gerätefunktionstest. |
| Synthetische Modul-/Browserprüfungen | Bestätigung vor Abruf, Einmaligkeit, Statusphasen, Fehler/Unterbrechung und Erhalt des Bearbeitungsstands. | Künstliche Gegenstellen; kein zusätzlicher realer Praxislauf. |

Alle Uhrzeiten oben beziehen sich auf Europe/Berlin. Die Originalberichte
bleiben erhalten; der private R3-Abgleich enthält die zusammengehörigen
S1-Debugereignisse sowie Verweise auf den R2-Ausgangsstand und Abschlussbeleg.
Der ergänzende private R3-Praxisbeleg enthält ursprüngliche Daten, vorbereiteten
Vergleichsstand, Zustand nach Installation, endgültige Wiederherstellung und
den zugehörigen Dateidebug mit Abschlussprüfung.

## Reale Ablaufkette je System

| Teilbeleg | S1 | S2 |
| --- | --- | --- |
| Tatsächlicher Installationsauftrag | Am 06.11. genau ein kanonisches START/ENDE-Paar für den zentralen Auftrag um 07:28:08 Uhr; ein `extension/save` um 07:28:09 Uhr mit passender Antwort. | Im R2-Beleg genau ein RPC, ein kanonisches START/ENDE-Paar und ein `extension/save`; Nein und spätere Statusabfrage ohne weiteren Upload. |
| Installation bestätigt | Neue Sitzung, erwarteter Build `4d6cda174348` und Ergebnis `installed` bestätigt. | Neue Sitzung und Build `4d6cda174348` anhand frischer Empfängermetadaten bestätigt. |
| Automatischer Start | Zentrale meldet am 06.11. um 07:28:10 Uhr nach `installed` das automatische Nachladen für dasselbe System. | Zentrale meldet am 06.11. um 06:47:39 Uhr denselben systemgebundenen Ablauf. |
| Genau ein tatsächlicher Geräteabruf | Ein Katalogauftrag um 07:28:11 Uhr, genau eine passende Antwort, Ergebnis `catalog_loaded`. | Dateidebug des R2-Laufs zählt genau einen Katalogauftrag mit erfolgreicher Antwort. |
| Gerätedaten übernommen und Ablauf abgeschlossen | Verbindung bestätigt den Katalog; Zentrale bestätigt um 07:28:13 Uhr den erfolgreichen automatischen Abschluss mit Phase `loaded`. | Katalogübernahme und automatischer Abschluss um 06:47:41 Uhr bestätigt. |
| Entwurf, Auswahl, offene Arbeit und Partnerfreigaben erhalten | Beide Datenobjekte in PHP unabhängig und exakt mit dem vorbereiteten Ausgangsstand verglichen. S1 besitzt dabei eine echte Regelauswahl und absichtlich offene Namenseingabe; gemeinsame Systemauswahl bleibt auf S2. Nach Entfernen der Testeingabe beide ursprünglichen Datenobjekte erneut exakt bestätigt. | Beide lokalen Datenobjekte mit Dokument, Auswahl und offener Arbeit exakt mit den Ausgangskopien verglichen. Partnerfreigaben sind Bestandteil der Dokumente. |
| Gespeicherte Automationen und ursprünglicher Betrieb erhalten | Gespeicherte/aktive Revisionen und Prüfsummen, Regelanzahlen, Wiederanlauf, Meshbereitschaft und Automatikbetrieb beider Systeme gleich. Eine aktive Regel auf S1, zwei auf S2; zweite Sitzung unverändert. Beide abschließenden Statusantworten frisch und ohne Laufzeitfehler. | Gespeicherte/aktive Revisionen und Prüfsummen, eingeschalteter Wiederanlauf, Meshbereitschaft und vorheriger Automatikbetrieb bestätigt; erstes System bleibt unverändert. |

Beim S1-Abgleich wurden auch Debugseiten ohne passende Treffer bis zum Dateiende
berücksichtigt. Die Einmaligkeit folgt aus den tatsächlichen MQTT-Aufträgen
und ihrer Antwortzuordnung, nicht allein aus einer Erfolgsmeldung.
Die Abschlussmeldung über erhaltene Entwürfe ist dagegen kein unabhängiger
Vergleich gespeicherter Ausgangs- und Enddaten. Diese Belegarten bleiben getrennt.

## Ergänzter Vorher-/Nachher-Vergleich

Der zunächst fehlende Vergleich für **S1** ist mit zusammengehörigen privaten
Ausgangs- und Endkopien desselben Installationslaufs ergänzt:

- Dokument einschließlich Partnerfreigaben, Auswahl und offene Arbeit beider
  lokalen Systembereiche sowie die gemeinsame Systemauswahl bleiben gleich.
- Gespeicherte und aktive Revision/Prüfsumme, Wiederanlaufeinstellung und
  abschließend bestätigter bisheriger Betrieb bleiben erhalten. Das jeweils
  andere System bleibt unverändert.

Die Sicherungen enthalten die unveränderten öffentlichen Modulantworten als
Zeichenketten. Der unabhängige Vergleich dekodiert sie in PHP und vergleicht
die Daten streng einschließlich Typen. Es werden weder der geänderte
Katalogzeitpunkt noch Auftragsquittungen mit einem Entwurfsverlust verwechselt.
Die Dateidebugs stammen aus genau der Zentrale und den beiden Verbindungen;
alle drei Testaufzeichnungen sind anschließend mit Endmarke beendet.

Im begrenzten Installationsfenster zählt S1 neben einem Upload und einem
Katalogabruf zwei Health-Anfragen und drei lesende Statusanfragen. S2 erhält
nur eine abschließende lesende Statusanfrage. Alle Erweiterungsanfragen haben
genau eine passende Antwort. Keine YAML-Speicher-, Aktivierungs-, Stopp- oder
Gerätebefehle. Temporäre Regelauswahl und offene Eingabe werden nur lokal
vorbereitet und nach der Prüfung durch die gesicherten Originaldaten ersetzt;
Partnerfreigaben und Dokumente werden dabei nicht geändert.

Die Freigabe galt ausschließlich für diesen S1-Praxislauf. Sie ist keine
dauerhafte Freigabe weiterer Installationen. Modul-, Erweiterungs- und
Helperquellen sowie Tests und erzeugtes Artefakt bleiben unverändert.

R4 und R5 bleiben eigene Aufgaben. Dieser Abgleich erweitert weder die reale
Zwei-System-Abnahme auf acht Broker noch die bestätigten Symcon-/Z2M-Versionen.
