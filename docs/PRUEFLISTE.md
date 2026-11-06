# Aktueller Umsetzungs- und Prüfstand

Stand: **06.11.2026**. Diese Liste ist der aktuelle Abgleich zur
[Funktionsagenda](AGENDA.md) und zum [Mehrsystemplan](MULTISYSTEM_PLAN.md).
Ältere Entwicklungsberichte beschreiben den damaligen Stand und sind keine
aktuelle Aufgabenliste. Eine Aktualisierung oder ein Prüfauftrag allein zählt
nicht als bestandener Nachweis.

## Bedeutung der Angaben

- **Praxis bestätigt:** Der genannte Ablauf wurde tatsächlich an der
  freigegebenen Installation geprüft. Das gilt für diesen Umfang, nicht für
  jede Z2M-Version, jedes Gerät oder acht reale Systeme.
- **Synthetisch bestätigt:** Eigener Modul-/Erweiterungscode wurde mit
  künstlichen Daten und Gegenstellen ausgeführt; kein Hardware-Nachweis.
- **Offen:** Der konkret genannte Nachweis fehlt oder ist nicht vollständig
  zugeordnet. Bereits bestätigte Teilprüfungen werden nicht erneut verlangt.

Quellen sind DEVELOPMENT.md, die im Chat bestätigten Praxisabläufe und am
06.11.2026 lokal eingesehene, nicht versionierte Prüfberichte. Private Berichte,
Adressen, Instanz-IDs, Gerätekennungen und Sicherungen bleiben außerhalb des
Repositorys. Die nachfolgende Zusammenfassung enthält keine solchen Rohdaten.
Der [R3-Belegabgleich](INSTALLATION_CATALOG_EVIDENCE.md) ordnet archivierte
Installations- und Geräteabrufketten getrennt von manuellen Abrufen zu.

## Umgesetzt und bereits bestätigt

| Bereich | Nachweis und Grenze |
| --- | --- |
| Ursprüngliche Funktionsagenda 1–6 | Native Bearbeitungsstände, Suche/Filter/Kopien, mehrere Uhrzeiten und Wochentage, erneute Auslösung, gemeinsame Farbgerätebefehle und Benutzerführung praktisch bestätigt. Grenzen stehen in AGENDA.md und COMPATIBILITY.md. |
| Mehrsystem-Schritte 1–6 | Verträge, Z2MConnection, getrennte Entwürfe, Sicherung/Übernahme/Besitzerwechsel/Rückweg, gemeinsame Oberfläche und systemgebundene Betriebsaufträge implementiert. Modulprüfungen für 1/2/8 Systeme sowie gemeinsame/getrennte MQTT-Parents vorhanden. |
| Sichtbarer Systemwechsel | An Test-Symcon 9.1 bestätigt: richtige Geräte/Gruppen und Vorbelegung, getrennte lokale Änderungen, Hin-/Zurückwechsel und exakte Wiederherstellung. Bericht vom 04.11.2026 in DEVELOPMENT.md. |
| Designeragenda 1: Regelverwaltung | Auf beiden Testsystemen Kopieren als ausgeschaltete Regel, Löschen, Abbruch, Schutz verwendeter Folgen, Löschen unbenutzter Folgen und Statusfilter bestätigt. Lokaler Praxisbericht vom 05.11.2026 enthält beide Systeme ohne Browserfehler. |
| Designeragenda 2: Gerätedaten | Auf beiden Testsystemen Nachladen, sichtbare Phasen, lesende Statusabfrage, passende Geräteliste und Mausrad bestätigt. Entwürfe und Betrieb erhalten. |
| Designeragenda 3: gemeinsamer Editor | Auf beiden Testsystemen vollständiges Erstellen/Bearbeiten, Vorbelegung, Geräte-/Zeit-/Sonnenauslöser, Bedingungen, Folgen, Drag & Drop, Pfeilbuttons mit erhaltenen Wartezeiten, konkrete Ablehnung und Abbruch bestätigt. Originaldokument, Auswahl und offene Arbeit exakt wiederhergestellt; keine Z2M-Speicherung oder Geräteaktion. |
| Designeragenda 4: Ereignisimport | Auf beiden Testsystemen Vorschau, Auswahl, Import als ausgeschalteter lokaler Entwurf und Abbruch bestätigt. Originalereignisse und bisheriger Betrieb erhalten; keine Testregel in Z2M aktiviert. |
| Designeragenda 5: Systeme und Diagnose | Beide Systeme praktisch bestätigt: Erweiterungsstände und Updatehinweise, getrennte Aufzeichnungen, ursprüngliche Endzeit, automatischer Zeitablauf und gezielter Stopp. Gespeicherte/aktive Revisionen und Betrieb unverändert. |
| Zentrale Betriebsbuttons im Designer | Separater Playwright-/Edge-Testbrowser bestätigt auf beiden Systemen die Betriebs- und Bestätigungswege einschließlich Einzellauf und Bedingungen. Abgesicherte Testdaten und ursprünglicher Betrieb wiederhergestellt; keine Geräte geschaltet. Der erste fehlgeschlagene Browserversuch ist durch den späteren bestandenen Bericht ersetzt. |
| Erweiterungsinstallation | Browserweg auf beiden Systemen mit Bestätigung, Installation und abschließendem Status praktisch bestätigt. Nativer Weg auf dem ersten System bereits belegt; der strikte zweite native Klicknachweis R2 ist am 06.11.2026 an Test-Symcon 9.1 bestätigt: Nein null, Ja ein RPC/Debug-Paar/Upload, Status ohne Wiederholung. |
| Automatischer Geräteabruf nach Installation | Reale Kette bestätigte Installation → genau ein automatischer Abruf → Katalog übernommen für beide Systeme zugeordnet: S1 am 05.11., S2 im R2-Lauf am 06.11.2026. S2 einschließlich exaktem Entwurfs-/Betriebsvergleich bestätigt. Für S1 fehlt nur der unabhängige Vergleich im selben Lauf; siehe R3-Belegabgleich. Fehler-/Unterbrechungsfälle zusätzlich synthetisch geprüft. |
| HTTP-LAN-Bedienung | Gemeinsame sichere Kennungserzeugung implementiert. Systemwechsel lokal über echtes HTTP ohne randomUUID geprüft und vom Anwender im Live-System ausdrücklich als funktionierend bestätigt. |
| Partnerfreigaben | Am 05.11.2026 auf beiden Testsystemen Richtung, Vorschau, betroffene Regel, Entziehen/erneutes Freigeben, Abbruch und Debug bestätigt. Entwürfe und Auswahl exakt wiederhergestellt. Korrigierte Einzahl am 06.11.2026 im echten Testdesigner erneut rein lesend bestätigt. |
| Zwei unabhängige Broker | Installation, Authentifizierung, gegenseitiger Diagnoseempfang und Mesh-Rechte im gemeinsamen Praxisablauf bestätigt. Reale systemübergreifende Schaltungen und automatischer Wiederanlauf einzelner Regeln bestätigt. Kein Nachweis für einen Verbund aus drei bis acht Systemen. |
| R1: Drei-/Acht-Erweiterungs-Mesh synthetisch | Am 06.11.2026 sechs Drei-System- und fünf Acht-System-Fälle bestanden. Acht echte Erweiterungsinstanzen prüfen alle 56 gerichteten Geräte-/Gruppenziele, getrennte Freigaben, Zweigausfall/Wiederkehr, Antwortzuordnung, Doppelvermeidung und Debug. Sieben äußere Systeme tauschen Aktionen über den mittleren synthetischen Broker bei gestoppter mittlerer Erweiterung aus. Keine reale Mosquitto- oder Geräteabnahme. |
| Bisherige Laufzeitfunktionen | Reale Nachweise für Geräte-/Zeit-/Astro-Regeln, Szenen, Aktionsfolgen, Mesh, MQTT-Debug, Einzelsteuerung und Nachlaufverlängerung vorhanden; Einzelheiten und Grenzen in DEVELOPMENT.md. Diese Nachweise nicht pauschal als ungeprüft führen. |

Die Gesamtprüfung am 06.11.2026 für diese reine Dokumentationspflege bestand mit
**185 JavaScript-Tests** sowie den PHP-/Modul-/Erweiterungsprüfungen. Diese Zahl
ist ein datierter Nachweis und kein dauerhaft zugesicherter Testumfang.

Die anschließende R1-Ergänzung am selben Tag besteht mit sechs neuen
Drei-Erweiterungsfällen und insgesamt **191 JavaScript-Tests** in der
vollständigen Projektsuite. Struktur, Kodierung, StylePHP, PHP CS Fixer und
Erweiterungsartefakt sind ebenfalls geprüft; Details in DEVELOPMENT.md.

Die Ausweitung auf acht Erweiterungen ergänzt fünf Fälle. Die vollständige
Suite besteht jetzt mit **196 JavaScript-Tests**, einschließlich aller elf
Drei-/Acht-System-Fälle. Struktur, Kodierung, StylePHP, PHP CS Fixer und
Artefaktprüfung sind erneut erfolgreich. R1 ist synthetisch abgeschlossen;
reale Drei-/Acht-Broker-Installationen bleiben davon getrennt.

Die lokale Korrektur der R2-Testinstrumentierung besteht mit sieben neuen
Node-Fällen und insgesamt **203 JavaScript-Tests**, einem separaten synthetischen
SharedWorker-Browsernachweis und dem nativen Debug-Abgleich an zwei Kernel-
Fixtures. Die anschließende ausdrückliche Praxisfreigabe und Prüfung am
06.11.2026 schließen den realen zweiten Klicknachweis: genau eine Installation,
frische Empfängersitzung, erwarteter Build, unveränderte Entwürfe und wieder
bestätigter bisheriger Betrieb. Details in NATIVE_INSTALLATION_TEST.md.

## Tatsächlich offene Restpunkte

| ID | Fehlender Nachweis | Nächster begrenzter Schritt |
| --- | --- | --- |
| R3 | Unabhängiger Vorher-/Nachher-Vergleich für S1 im selben automatischen Installationslauf | Die Ablaufketten beider Systeme sind zusammengeführt; S2 ist vollständig belegt. Für S1 fehlen passende Ausgangs-/Endkopien von Entwurf einschließlich Partnerfreigaben, Auswahl und offener Arbeit sowie bestätigtem Betrieb, einschließlich des unveränderten anderen Systems. Vorhandene manuelle Abrufe ersetzen diesen Vergleich nicht. Nur diesen Teil bei einem später ausdrücklich beauftragten S1-Update ergänzen; keine pauschale Wiederholung beider Installationen. |
| R4 | Geschlossene Restabnahme des Zentralenwegs | Zuordnung von Version/Build, Bedienweg und Rückweg vervollständigen. Echte Unterbrechung/Wiederverbindung bzw. Wiederanlauf im Zentralenweg nur dort nachprüfen, wo die vorhandenen synthetischen und älteren Praxisbelege den konkreten Fall nicht abdecken. Kein pauschaler Neustart aller bestandenen Prüfungen. |
| R5 | Breitere Kompatibilitätsabnahme | Native Symcon-9.0-Abnahme, stabile/weitere Z2M-Versionen und zusätzliche Geräte-/Gruppen-/Farbconverter-Kombinationen getrennt belegen. Maßgeblich ist COMPATIBILITY.md; bisherige einzelne Schalterfolge ist keine allgemeine Versionsfreigabe. |

R1 ist im vereinbarten synthetischen Umfang abgeschlossen, R2 praktisch
bestätigt. R3–R5 enthalten gegebenenfalls operative Praxisaktionen und benötigen
jeweils einen konkreten Auftrag. Die neue
[Brokeranleitung](../README.md#bis-zu-acht-mqtt-broker-miteinander-verbinden)
beschreibt den Ausbau; sie ist keine neue Drei-/Acht-System-Abnahme.

## Prüfliste für die nächste Stufe

- [x] Planungsstand von historischen Implementierungsnotizen trennen.
- [x] Die fünf umgesetzten Designerpunkte und bestätigte Praxis berücksichtigen.
- [x] Partnerfreigaben und Einzahlkorrektur als bestätigt aufnehmen.
- [x] Zweibroker-Praxis von ungeprüftem Drei-/Acht-System-Ausbau trennen.
- [x] R1: Drei Erweiterungen mit getrennten Erfolgs-/Fehlerzweigen synthetisch prüfen; sechs Fälle in `tests/mesh-three.test.mjs`, Details in DEVELOPMENT.md.
- [x] R1: Acht Erweiterungen und eindeutige Ziele synthetisch prüfen; fünf Fälle in `tests/mesh-eight.test.mjs`, einschließlich aller 56 Zielrichtungen.
- [x] R2: Begrenzter nativer Bestätigungsnachweis auf dem zweiten System am 06.11.2026 mit strikter Probe und Dateidebug bestätigt.
- [x] R3: Vorhandene reale Installation samt genau einem automatischen Katalogabschluss je System zusammenführen; siehe INSTALLATION_CATALOG_EVIDENCE.md.
- [x] R3: S2 mit exaktem Vergleich beider lokalen Arbeitsbereiche und bestätigtem ursprünglichem Betrieb zuordnen.
- [ ] R3: Nur noch den unabhängigen Vorher-/Nachher-Vergleich für S1 im selben automatischen Installationslauf ergänzen.
- [ ] R4: Zentralen-Restabnahme mit Versions-/Buildbezug und Rückweg abschließen.
- [ ] R5: Vereinbarte Kompatibilitätsfälle aufnehmen und gezielt prüfen.

Erst danach über eine Veröffentlichung entscheiden. Die eigenständige
Windows-Anwendung bleibt ein separates späteres Projekt. Diese Prüfliste
erteilt weder eine Live-Freigabe noch einen Installations-, Neustart- oder
Schaltauftrag.
