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
| Erweiterungsinstallation | Browserweg auf beiden Systemen mit Bestätigung, Installation und abschließendem Status praktisch bestätigt. Nativer Weg auf einem System bestätigt; der abschließende native Status auf beiden Systemen ebenfalls. Der einzelne noch unvollständige native Klicknachweis ist R2. |
| Automatischer Geräteabruf nach Installation | Implementiert und synthetisch für Bestätigung, genau einen Abruf, Fehler, Unterbrechung und Entwurfserhalt geprüft. Die noch nicht vollständig zusammengeführte reale Ablaufkette ist R3; sie ist kein erneut offener Implementierungspunkt. |
| HTTP-LAN-Bedienung | Gemeinsame sichere Kennungserzeugung implementiert. Systemwechsel lokal über echtes HTTP ohne randomUUID geprüft und vom Anwender im Live-System ausdrücklich als funktionierend bestätigt. |
| Partnerfreigaben | Am 05.11.2026 auf beiden Testsystemen Richtung, Vorschau, betroffene Regel, Entziehen/erneutes Freigeben, Abbruch und Debug bestätigt. Entwürfe und Auswahl exakt wiederhergestellt. Korrigierte Einzahl am 06.11.2026 im echten Testdesigner erneut rein lesend bestätigt. |
| Zwei unabhängige Broker | Installation, Authentifizierung, gegenseitiger Diagnoseempfang und Mesh-Rechte im gemeinsamen Praxisablauf bestätigt. Reale systemübergreifende Schaltungen und automatischer Wiederanlauf einzelner Regeln bestätigt. Kein Nachweis für einen Verbund aus drei bis acht Systemen. |
| Bisherige Laufzeitfunktionen | Reale Nachweise für Geräte-/Zeit-/Astro-Regeln, Szenen, Aktionsfolgen, Mesh, MQTT-Debug, Einzelsteuerung und Nachlaufverlängerung vorhanden; Einzelheiten und Grenzen in DEVELOPMENT.md. Diese Nachweise nicht pauschal als ungeprüft führen. |

Die Gesamtprüfung am 06.11.2026 für diese reine Dokumentationspflege bestand mit
**185 JavaScript-Tests** sowie den PHP-/Modul-/Erweiterungsprüfungen. Diese Zahl
ist ein datierter Nachweis und kein dauerhaft zugesicherter Testumfang.

## Tatsächlich offene Restpunkte

| ID | Fehlender Nachweis | Nächster begrenzter Schritt |
| --- | --- | --- |
| R1 | Kommunikation mit mehr als zwei Erweiterungsinstanzen | Zunächst drei echte Instanzen unserer Erweiterung in einer synthetischen MQTT-Umgebung: A steuert B und C in getrennten Regeln/Läufen, ein Zweig fällt aus, der andere bleibt nutzbar, Freigaben/Antworten bleiben getrennt, keine Doppelaktion. Danach auf acht erweitern. Ein fehlgeschlagener Schritt innerhalb desselben Laufs behält seine bisherige Abbruchwirkung. Die bisherigen Acht-System-Modultests und Zweier-Mesh-Tests ersetzen diesen Nachweis nicht. |
| R2 | Vollständiger nativer Installations-Klicknachweis auf dem zweiten System | Der vorhandene Einzelbericht endete an der Prüfbedingung „genau ein Callback nach Bestätigung“ mit null erfassten Aufrufen. Der spätere bestandene Statusbericht belegt den Endstatus, nicht diesen Callback. Zuerst die Testinstrumentierung und vorhandenen Belege abgleichen; nur den fehlenden Klickpfad nach gesondertem Praxisauftrag wiederholen. Kein bestätigter Produktfehler daraus ableiten. |
| R3 | Durchgängiger realer Nachweis des automatischen Geräteabrufs nach Installation | Vorhandene Installations- und Katalognachweise zu einer Kette je System zusammenführen: neue Erweiterung bestätigt → genau ein automatischer Abruf → Geräte geladen oder eigener Gerätefehler; Entwürfe/Auswahl/Betrieb erhalten. Falls ein Teilbeleg fehlt, nur diesen nach gesondertem Auftrag prüfen. |
| R4 | Geschlossene Restabnahme des Zentralenwegs | Zuordnung von Version/Build, Bedienweg und Rückweg vervollständigen. Echte Unterbrechung/Wiederverbindung bzw. Wiederanlauf im Zentralenweg nur dort nachprüfen, wo die vorhandenen synthetischen und älteren Praxisbelege den konkreten Fall nicht abdecken. Kein pauschaler Neustart aller bestandenen Prüfungen. |
| R5 | Breitere Kompatibilitätsabnahme | Native Symcon-9.0-Abnahme, stabile/weitere Z2M-Versionen und zusätzliche Geräte-/Gruppen-/Farbconverter-Kombinationen getrennt belegen. Maßgeblich ist COMPATIBILITY.md; bisherige einzelne Schalterfolge ist keine allgemeine Versionsfreigabe. |

R1 ist zuerst offline möglich. R2–R5 enthalten gegebenenfalls operative
Praxisaktionen und benötigen jeweils einen konkreten Auftrag. Die neue
[Brokeranleitung](../README.md#bis-zu-acht-mqtt-broker-miteinander-verbinden)
beschreibt den Ausbau; sie ist keine neue Drei-/Acht-System-Abnahme.

## Prüfliste für die nächste Stufe

- [x] Planungsstand von historischen Implementierungsnotizen trennen.
- [x] Die fünf umgesetzten Designerpunkte und bestätigte Praxis berücksichtigen.
- [x] Partnerfreigaben und Einzahlkorrektur als bestätigt aufnehmen.
- [x] Zweibroker-Praxis von ungeprüftem Drei-/Acht-System-Ausbau trennen.
- [ ] R1: Drei Erweiterungen mit getrennten Erfolgs-/Fehlerzweigen synthetisch prüfen.
- [ ] R1: Anschließend acht Erweiterungen und eindeutige Ziele prüfen.
- [ ] R2: Den begrenzten nativen Bestätigungsnachweis vervollständigen.
- [ ] R3: Reale Installation samt automatischem Katalogabschluss je System belegen.
- [ ] R4: Zentralen-Restabnahme mit Versions-/Buildbezug und Rückweg abschließen.
- [ ] R5: Vereinbarte Kompatibilitätsfälle aufnehmen und gezielt prüfen.

Erst danach über eine Veröffentlichung entscheiden. Die eigenständige
Windows-Anwendung bleibt ein separates späteres Projekt. Diese Prüfliste
erteilt weder eine Live-Freigabe noch einen Installations-, Neustart- oder
Schaltauftrag.
