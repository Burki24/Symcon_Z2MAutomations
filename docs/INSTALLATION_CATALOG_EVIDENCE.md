# R3: Installation und automatischer Geräteabruf

Belegabgleich vom **06.11.2026** zur [Prüfliste](PRUEFLISTE.md).
Es wurden ausschließlich vorhandene Prüfberichte, Prüfskripte und archivierte
Dateidebugs ausgewertet. Keine neue Installation, Geräte- oder Statusabfrage,
Debugaufzeichnung oder Änderung am laufenden Betrieb.

**Ergebnis:** Die reale Kette bestätigte Installation → genau ein automatischer
Geräteabruf → erfolgreich geladener Katalog ist für beide Systeme belegt.
Beim zweiten System ist zusätzlich der unabhängige Vorher-/Nachher-Vergleich
vollständig. Beim ersten System fehlt dieser Vergleich für denselben
Installationslauf; R3 bleibt nur für diesen Teilnachweis offen.

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
| Synthetische Modul-/Browserprüfungen | Bestätigung vor Abruf, Einmaligkeit, Statusphasen, Fehler/Unterbrechung und Erhalt des Bearbeitungsstands. | Künstliche Gegenstellen; kein zusätzlicher realer Praxislauf. |

Alle Uhrzeiten oben beziehen sich auf Europe/Berlin. Die Originalberichte
bleiben erhalten; der private R3-Abgleich enthält die zusammengehörigen
S1-Debugereignisse sowie Verweise auf den R2-Ausgangsstand und Abschlussbeleg.

## Reale Ablaufkette je System

| Teilbeleg | S1 | S2 |
| --- | --- | --- |
| Tatsächlicher Installationsauftrag | Ein `extension/save` um 15:48:47 Uhr, passende Installationsantwort um 15:48:48 Uhr. | Im R2-Beleg genau ein RPC, ein kanonisches START/ENDE-Paar und ein `extension/save`; Nein und spätere Statusabfrage ohne weiteren Upload. |
| Installation bestätigt | Ergebnis `installed`, installierter und mitgelieferter Build stimmen überein. | Neue Sitzung und Build `4d6cda174348` anhand frischer Empfängermetadaten bestätigt. |
| Automatischer Start | Zentrale meldet am 05.11. um 15:48:49 Uhr nach `installed` das automatische Nachladen für dasselbe System. | Zentrale meldet am 06.11. um 06:47:39 Uhr denselben systemgebundenen Ablauf. |
| Genau ein tatsächlicher Geräteabruf | Ein Katalogauftrag um 15:48:50 Uhr, genau eine passende Antwort, Ergebnis `catalog_loaded`. | Dateidebug des R2-Laufs zählt genau einen Katalogauftrag mit erfolgreicher Antwort. |
| Gerätedaten übernommen und Ablauf abgeschlossen | Verbindung bestätigt den Katalog; Zentrale bestätigt um 15:48:51 Uhr die Übernahme und den erfolgreichen automatischen Abschluss. | Katalogübernahme und automatischer Abschluss um 06:47:41 Uhr bestätigt. |
| Entwurf, Auswahl, offene Arbeit und Partnerfreigaben erhalten | Abschlussmeldungen nennen den Erhalt. Ein unabhängiger Vergleich derselben Ausgangs-/Enddaten fehlt; daher nur teilweise belegt. | Beide lokalen Datenobjekte mit Dokument, Auswahl und offener Arbeit exakt mit den Ausgangskopien verglichen. Partnerfreigaben sind Bestandteil der Dokumente. |
| Gespeicherte Automationen und ursprünglicher Betrieb erhalten | Erfolgreicher Installationspfad prüft den Speichervertrag; die Debugkette enthält keinen Speicher-/Aktivierungsauftrag. Ein unabhängiger Vergleich des vorherigen und abschließend bestätigten Betriebs fehlt für diesen Lauf. | Gespeicherte/aktive Revisionen und Prüfsummen, eingeschalteter Wiederanlauf, Meshbereitschaft und vorheriger Automatikbetrieb bestätigt; erstes System bleibt unverändert. |

Beim S1-Abgleich wurden auch Debugseiten ohne passende Treffer bis zum Dateiende
berücksichtigt. Die Einmaligkeit folgt aus den tatsächlichen MQTT-Aufträgen
und ihrer Antwortzuordnung, nicht allein aus einer Erfolgsmeldung.
Die Abschlussmeldung über erhaltene Entwürfe ist dagegen kein unabhängiger
Vergleich gespeicherter Ausgangs- und Enddaten. Diese Belegarten bleiben getrennt.

## Noch fehlender Teilnachweis

Für **S1** fehlt ausschließlich die zusammengehörige Sicherung und der
unabhängige Vergleich vor und nach einem automatischen Installationslauf:

- Dokument einschließlich Partnerfreigaben, Auswahl und offene Arbeit beider
  lokalen Systembereiche sowie die gemeinsame Systemauswahl bleiben gleich.
- Gespeicherte und aktive Revision/Prüfsumme, Wiederanlaufeinstellung und
  abschließend bestätigter bisheriger Betrieb bleiben erhalten. Das jeweils
  andere System bleibt unverändert.

Das sind fehlende Vergleichsbelege, kein beobachteter Datenverlust und kein
nachgewiesener Fehler des automatischen Geräteabrufs. Für S2 ist dieser Umfang
bereits abgedeckt. Auch die S1-Installation und der einmalige automatische
Geräteabruf müssen nicht erneut als ungeprüft geführt werden.

Sollten keine weiteren zeitlich passenden privaten Sicherungen vorliegen,
kann dieser Vergleich bei einem später ausdrücklich beauftragten S1-Update
mit erhoben werden. Vorher Ausgangsstände sichern, danach mit der frischen
Empfängerbestätigung vergleichen; keine YAML speichern, keine Regeln manuell
aktivieren und keine Geräte schalten. Eine Installation kann den laufenden
Automatikbetrieb kurz unterbrechen und ist durch diesen Belegabgleich nicht
freigegeben. Kein weiterer S2-Installationslauf und keine neue Implementierung
sind für den hier verbliebenen R3-Teil erforderlich.

R4 und R5 bleiben eigene Aufgaben. Dieser Abgleich erweitert weder die reale
Zwei-System-Abnahme auf acht Broker noch die bestätigten Symcon-/Z2M-Versionen.
