# Aktueller Umsetzungs- und Prüfstand

## Neustartprüfungen und Planungsabgleich – 08.11.2026

Beide Praxisaufträge abgeschlossen. Umgebung: Test-Symcon 9.1 mit ausdrücklich
freigegebenem, gemeinsam genutztem Z2M-Empfänger 2.14.2-dev. Native Debugausgaben,
MQTT-Rückmeldungen und Vorher-/Nachher-Vergleiche sind außerhalb des Repositorys
gesichert; keine privaten Installationsdaten übernehmen.

| Nachweis | Bestätigtes Ergebnis | Grenze |
| --- | --- | --- |
| N1: Wartende Automatikläufe fortsetzen | Modul 0.186, Erweiterungsbuild 829a9cbf479b. Dieselbe Laufkennung und Schrittposition nach Neustart; 180 Sekunden Wartezeit enden zur ursprünglichen Frist. Bei während des Neustarts abgelaufener Frist beendet resume den Lauf; resume_expired prüft den ausstehenden Schritt und verwirft ihn wegen der abgelaufenen Testbedingung. Status und Debug stimmen überein. Testregeln entfernt, ursprüngliche Regeln und Betrieb wiederhergestellt. | Keine Test-Schaltbefehle. Eine tatsächliche Gerätewirkung des nachgeholten Schritts und ein harter Stromausfall wurden hier nicht geprüft. |
| N2: Automatische Statusbestätigung | Modul 0.188. Erste Anfrage im Z2M-Anlauf unbeantwortet; nach zehn Sekunden Zeitlimit und zwei Sekunden Pause einmal automatisch wiederholt. Frische, nicht gespeicherte Antwort mit neuer passender Anfragekennung bestätigt Erweiterung und Betrieb. Keine dritte Anfrage; Status bleibt bestätigt. Entwürfe und Auswahl exakt erhalten, gespeicherte Revision/Prüfsumme und ursprünglicher Betrieb unverändert; Aufzeichnungen beendet. | Kein manueller Statusaufruf nach Neustart. Erweiterungsbuild 4d6cda174348 blieb unverändert; dieser Lauf belegt die Modulkorrektur, keine erneute Fortsetzungsprüfung. Ausschöpfen aller drei Versuche bleibt durch Regressionstests belegt. |

Die ursprüngliche Funktionsagenda, die fünf Designerpunkte und R1–R4 sind im
jeweils dokumentierten Umfang abgeschlossen. B1–B4 bleiben abgeschlossen.
R5 bezeichnet zusätzliche Geräte-/Versionsabnahmen; drei bis acht reale Broker
sind weiterhin nicht praktisch abgenommen. Die neuen Neustartfälle sind kein
Anlass, bereits bestandene Praxisprüfungen pauschal zu wiederholen.

Releasebasis auf dem lokal eingesehenen main: 0.185, Commit 973929a.
Entwicklungsstand auf dev: 0.188; diese Dokumentationsänderung ist darin noch
nicht enthalten. Für die nächste Beta bleiben Versionsautomatik und CI des
endgültigen Commits, PR von dev nach main und die ausdrücklich freigegebene
Store-Einreichung getrennte Schritte. Aktueller Online-CI-/Storestatus wurde in
diesem Dokumentationsauftrag nicht geprüft. Keine feste nächste Versionsnummer
vorwegnehmen; Details im [Beta-Abnahmebericht](BETA_ABNAHME.md).

Die datierten Abschnitte darunter bleiben historische Nachweise; dieser Abgleich
ersetzt ihre inzwischen erledigten Offenpunkte. Windows/Linux bleiben ein
separates Vorhaben. In diesem Auftrag ausschließlich Dokumentation geändert.

## Historischer Beta-Abschluss – 07.11.2026

Lokaler Kandidat geprüft: 229 JavaScript-Tests samt PHP-Gesamtsuite,
Paketgrenzen und isolierte echte Browserprüfung bestanden. B1–B4 sind
abgeschlossen. Die automatische Versionierung arbeitet bereits auf GitHub;
B5 betrifft den finalen Benutzer-Commit/Push, dessen CI und den festgelegten
Veröffentlichungscommit. Storekonto/Kategorie sind nur lesend geprüft,
Storetexte im [aktuellen Abnahmebericht](BETA_ABNAHME.md) vorbereitet.
Keine erneute vollständige Praxisprüfung und keine Desktop-Funktion für die
Beta ergänzen. Keine Änderung am Live-System oder am Dokumentationsaufbau.

## Live-Praxis bestätigt – 07.11.2026

Symcon 9.0 und stabiles Z2M 2.14.2 sind vom Benutzer als erfolgreich live
geprüft bestätigt. B1/B2 geschlossen; keine zusätzliche Agentenabnahme.
R4/B3 und B4 sind ebenfalls abgeschlossen. Offen bleibt B5 mit finaler CI, Veröffentlichungscommit und Storeabschluss.
Der erste Lauf der automatischen Versionierung ist inzwischen bestätigt.
Breitere Geräte-/Versionsprüfungen bleiben zusätzliche R5-Kompatibilitätsarbeit.
Historische offene Bewertungen weiter unten werden durch diesen Nachtrag ersetzt.

## Nachtrag zur Mausradkorrektur – 06.11.2026

Die Ursache von B4 ist in der kanonischen Helperquelle korrigiert. Regressionen
prüfen tatsächlich begrenzte Scrollpositionen, beide Richtungen, horizontale
und RTL-Grenzen, das Ausbleiben doppelter Bewegung und den Dialoghintergrund.
Quelle und minifiziertes Asset bestehen echte isolierte Browserprüfungen.
Der native Test-Kachelclient scrollt mit ausschließlich im Testbrowser ersetzter
Ressource wieder in beide Richtungen. Dokumente, Bindungen, Auswahl und offene
Arbeit beider Systeme blieben beim Vorher-/Nachher-Vergleich unverändert.
Der Server behält sein bisheriges Paket; keine Geräte-/Z2M-Befehle gesendet.

Die reguläre Verteilung ist inzwischen angekommen: VisualizationAssetHelper
1.1.4 in Helper-Bundle 3.30.7, Consumer-Commit e65fd7d. Herkunft, Helper und
alle drei Assets stimmen mit dem veröffentlichten zentralen Manifest überein.
**B4 ist nach Test-Modulupdate geschlossen.** Die echte Kachel liefert das
reguläre Asset ohne Browser-Ressourcenaustausch. Echtes Hochscrollen über dem
inneren Ablaufbereich an dessen oberer Grenze bewegt das Dokument von 506 auf
206 Pixel. Native Debugmeldung und Assetkennung stimmen; Systemdaten und
ursprüngliche Auswahl sind unverändert. Eigene Dateiaufzeichnung beendet.
Kein lokales Testbundle im Arbeitsbaum. Frühere B4-Bewertungen weiter unten
beschreiben den Ausgangsbefund und werden durch diesen Nachtrag ersetzt.
Einzelheiten und Verteilungsreihenfolge stehen im Beta-Abnahmebericht.

## Beta-Abnahme – aktueller Nachtrag vom 06.11.2026

Die vier beauftragten Abschlusspunkte sind im
[Beta-Abnahmebericht](BETA_ABNAHME.md) mit Nachweisen und verbleibenden Grenzen
zugeordnet. Die [Beta-Anleitung](BETA.md) ergänzt Einrichtung und Fehlerberichte.
Die Codebasis b1ae70b besteht lokal einschließlich 221 JavaScript-Tests; Tests
und Check Style sind auch in GitHub Actions für genau diesen Commit grün.

Die neue Browseranordnung ist inzwischen an der Testinstallation auf beiden
Systemen geprüft: vollständige Katalogseiten, Systemwechsel, Gerätenamen,
Vorbelegung, Gerätesuche/Kanal/Wert, Abbruch, Diagnose/Build und Erneuerung mit
erhaltener Eingabe. Hell/Dunkel und 360/760/1600/3840 Pixel ohne Seitenüberlauf
sowie echte Mausradbewegungen bestätigt. Dokument, Auswahl, offene Arbeit und
Bindung je System sind unverändert; ursprüngliche Systemauswahl wiederhergestellt.
Die alte Aussage „Praxisprüfung der neuen Anordnung offen“ ist für diesen
Browserumfang durch diesen Nachtrag ersetzt, nicht für einen ungeprüften Client.

**Neuer Beta-Blocker B4:** Im echten Symcon-Kachelclient 9.1 funktionieren
Systemwechsel/Status beider Systeme und Zugangserneuerung mit erhaltener Eingabe.
Die anschließende Prüfung findet jedoch eine Mausradfalle: An der oberen Grenze
des inneren Ablaufbereichs erreicht Hochscrollen das noch gescrollte Dokument
nicht. Der zentrale Interaktionshelper verbraucht das Ereignis ohne Bewegung.
Reproduziert mit echten Wheel-Aktionen und Positionsvergleich. Die native
Gesamtprüfung ist deshalb nicht bestanden; kanonische Korrektur und erneute
Praxisprüfung stehen vor der Beta an. Kein Modul-/Helpercode in diesem Auftrag geändert.

R4 bleibt für die konkret noch fehlende native Einrichtungs-/Rückwegkette und
Restartfälle teilweise offen. R5 ist mangels zusätzlicher Umgebungen nicht
abschließbar: Native Symcon-9.0- und stabile Z2M-Praxis fehlen. Eine neue native
Kachelprüfung des endgültigen Kandidaten ist nach Korrektur von B4 abzuschließen.
Der Bericht benennt den Blocker B4 und die Hindernisse B1–B3/B5;
keine Änderung der zugesagten Mindestversion und keine Veröffentlichungsfreigabe.

## Native Kachel und vollständiger Designer – 06.11.2026

- **Implementiert:** derselbe vollständige Designer innerhalb der HTML-SDK-Kachel;
  Systemauswahl, Status, Bearbeitungsfunktionen und Zugangserneuerung ohne Neuladen.
- **Praxis bestätigt, Test-Symcon 9.1:** beide Systeme mit acht beziehungsweise
  einer Automation, Systemwechsel, bestätigter aktiver Betrieb, Verlängerung bei
  offenem Dialog und Erhalt des unübernommenen Namens. Kein zusätzliches Fenster.
- **Praxis bestätigt:** native Farbparameter für Hell/Dunkel, Großansicht auf
  derselben Instanz, Breiten 360/760/1600 Pixel und echtes Mausrad abwärts/aufwärts
  innerhalb der verschachtelten Frames. Vorhandene Entwürfe und Betrieb erhalten.
- **Synthetisch bestätigt:** Typ 1 unter 9.0, Typ 2 ab 9.1, sichere Frame-/Origin-
  Zuordnung, JSON-Texttransport, Wartefolge vor dem ersten Lesen sowie unveränderte
  Rechte/Quittungen und Eingaben bei der Verlängerung. Ablauf/Widerruf bleiben geprüft.
- **Versionsgrenze:** native HTML-Großansicht benötigt Symcon 9.1; unter 9.0 den
  Designer in einer ausreichend großen normalen Kachel verwenden. Kein echter
  9.0-Kachelclient in diesem Auftrag geprüft.
- **Weiterhin nicht umgesetzt:** eigene IPSView-Ansicht. Keine offene Wiederholung
  der bereits bestandenen R1–R3-Nachweise oder der bisherigen Designeragenda.

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
Installations- und Geräteabrufketten getrennt von manuellen Abrufen zu und
enthält die ergänzende freigegebene S1-Praxisprüfung mit exaktem Vergleich.

## Umgesetzt und bereits bestätigt

| Bereich | Nachweis und Grenze |
| --- | --- |
| Responsives Designerlayout nach Mockup | Lokal mit synthetischen Daten an separatem Edge geprüft: automatische Hell-/Dunkeldarstellung, größere Schrift bei breiterem Browser, 320–3840 Pixel ohne Seitenüberlauf, lange Listen mit echtem Mausrad, aufklappbare Bereiche, Dialoge und sichtbarer Systemwechsel. Originale Controller weiterverwendet; Öffnen/Umschalten von Darstellungsbereichen ohne POST. Praxisprüfung der neuen Anordnung nach Modulupdate noch offen; bisherige Funktionsnachweise bleiben erhalten. |
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
| R3: Automatischer Geräteabruf nach Installation | Am 06.11.2026 im vereinbarten Umfang abgeschlossen: beide realen Ablaufketten mit genau einem automatischen Abruf und unabhängigem Entwurfs-/Betriebsvergleich belegt. Der ergänzende S1-Lauf bestätigt Regelauswahl und absichtlich offene Eingabe, unveränderte Partnerfreigaben und Betrieb sowie exakte Wiederherstellung beider ursprünglicher Arbeitsbereiche. Ein Upload, keine Schalt-/Speicher-/Aktivierungsbefehle, Testaufzeichnungen beendet. Fehler-/Unterbrechungsfälle zusätzlich synthetisch geprüft. |
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
| B4 | Geschlossen: native Mausradfalle an innerer Scrollgrenze | Helper-Bundle 3.30.7 regulär eingebunden, nach Test-Modulupdate im echten Kachelclient ohne Ressourcenaustausch praktisch bestätigt; native Debugkennung und unveränderte Systemdaten geprüft. |
| R4 | Geschlossen im beschriebenen Testumfang | [Praxisnachweis](R4_ACCEPTANCE_PLAN.md): direkte Einrichtung, Altübernahme, Rückwege, native Geräte-/Action-/Archivzuordnungen und P4–P6 bestanden. Native Verbindungsauswahl, Systemwechsel, Vorschau, Verwerfen, Übernahme, Dialoge, Formularneuaufbau und Rücknahmen durch Benutzerklicks bestätigt; Kernel-, MQTT- und Debugabgleich je Schritt erfolgreich. Aufgeräumt, Benutzerentwürfe unverändert. Windows-Dateirechte bleiben eine lokale Schutzempfehlung, kein Beta-Blocker oder Auftrag zur Codeänderung. |
| R5 | Breitere Kompatibilitätsabnahme | Symcon 9.0 und stabiles Z2M 2.14.2 durch Benutzer-Live-Praxis bestätigt. Weitere Versionen und zusätzliche Geräte-/Gruppen-/Farbconverter-Kombinationen bleiben getrennt zu belegen. Maßgeblich ist COMPATIBILITY.md; keine allgemeine Versionsfreigabe. |

R1 ist im vereinbarten synthetischen Umfang abgeschlossen, R2 und R3 praktisch
bestätigt. R4 ist im beschriebenen Testumfang abgeschlossen. Weitere R5-Praxisaktionen benötigen
jeweils einen konkreten Auftrag. Die neue
[Brokeranleitung](../Z2MConnection/README.md#bis-zu-acht-mqtt-broker-miteinander-verbinden)
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
- [x] R3: Unabhängigen Vorher-/Nachher-Vergleich für S1 im selben automatischen Installationslauf am 06.11.2026 ergänzt; ursprüngliche lokale Bearbeitungsdaten und Betrieb wieder bestätigt.
- [x] R4-Vorbereitung: vorhandene Belege abgleichen, verbleibende Fälle P0–P6 und isolierten Testaufbau festlegen; direkte Einrichtung, Altübernahme und echte Neustarts unterscheiden.
- [x] R4-Praxis: Zentralen-Restabnahme gemäß R4_ACCEPTANCE_PLAN.md im dort abgegrenzten Umfang abgeschlossen, einschließlich nativer Klickprüfung und Rückweg.
- [x] N1: Fortsetzung nach Z2M-Neustart mit Restfrist und abgelaufener Frist praktisch geprüft; erneute Bedingungsprüfung verhindert Test-Schaltbefehle.
- [x] N2: Automatische Statusbestätigung mit Zeitlimit, verzögerter Wiederholung, frischer Antwort, Debug und unverändertem Betrieb praktisch bestätigt.
- [ ] R5, zusätzliche Kompatibilitätsarbeit: weitere Versionen und Converter nur auf konkreten Praxisauftrag prüfen; kein pauschales Hindernis der ersten Beta.
- [x] B4: Native Mausradfalle an innerer Scrollgrenze korrigieren und mit regulär installiertem Paket praktisch nachprüfen.

Für die erste Beta den verbleibenden B5-Freigabeweg abschließen. Zusätzliche
R5-Kompatibilität und die eigenständige Windows-/Linux-Anwendung bleiben getrennt. Diese Prüfliste
erteilt weder eine Live-Freigabe noch einen Installations-, Neustart- oder
Schaltauftrag.
