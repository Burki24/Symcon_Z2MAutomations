# Kompatibilität und belegte Versionen

Stand: 03.11.2026. Diese Übersicht trennt reale Praxisnachweise von lokal
geprüften Verträgen. Eine ähnliche Versionsnummer ist keine eigene Abnahme.

| Umgebung | Nachweis | Grenze |
| --- | --- | --- |
| Z2M **2.14.2-dev** | Praktisch belegt: Installation, Editor-/YAML-Übertragung, Aktivierung, Stopp und reale Geräteausführung. Der dokumentierte erste native Lauf nennt diese Version ausdrücklich. Die vorhandene Versionsvariable eines im Praxisbetrieb verwendeten Systems meldet sie am 03.11.2026 erneut. | Die genaue Git-Revision des Entwicklungsstands ist nicht dokumentiert. Deshalb keine Zusage für jeden Stand mit derselben Bezeichnung. Nicht jeder spätere Funktionsnachweis enthält eine eigene Versionsaufnahme. |
| Z2M **2.14.2** ohne `-dev` | Schnittstellenziel der Implementierung; Abhängigkeiten und Host-Vertrag als Referenz geprüft. | Keine gesonderte reale Abnahme der stabilen Ausgabe dokumentiert. |
| Andere Z2M-Versionen | Kein belastbarer versionsbezogener Praxisnachweis vorhanden. | Keine pauschale Mindestversions- oder Kompatibilitätszusage. |
| Symcon **9.1**, PHP **8.5** | Native Formularbedienung und Praxisabläufe in der freigegebenen Installation dokumentiert. | Keine vollständige Abnahme sämtlicher Kombinationen. |
| Symcon **9.0**, PHP **8.5** | Deklariertes Projektziel; Offline-Prüfungen gegen die eingebundenen Stubs. | Keine vollständige native 9.0-Praxisabnahme dokumentiert. |

Die ausführlichen Praxisberichte stehen in [DEVELOPMENT.md](DEVELOPMENT.md):
„Native Praxisprüfung: Klick-Editor und Steuerung“ benennt 2.14.2-dev;
spätere Berichte belegen unter anderem Mesh, Szenen, Astro, Wiederanlauf,
Einzelsteuerung, wiederverwendbare Folgen und gemeinsame Farbgerätebefehle.
Diese Funktionsberichte sind keine zusätzliche Z2M-Versionsfreigabe.
Leere Versionsanzeigen werden als unbekannt behandelt und nicht ergänzt.

## Technische Grundlage

Unsere Erweiterung verwendet die externe Z2M-Erweiterungsschnittstelle sowie
Host-Schnittstellen für Geräte, Zustände, Ereignisse und Befehlsübergabe.
Letztere benötigen bei weiteren Z2M-Versionen eine erneute Prüfung.
Die lokale Testbasis verwendet Node ab 22.2, js-yaml 5.4.2 und Ajv 8.20.0.
Die Parser und der Validator werden auf dem Z2M-Host aus dessen vorhandenen
Abhängigkeiten geladen; das Uploadartefakt enthält keine Kopien davon.
Die zum jeweiligen Z2M gehörende Node-Anforderung bleibt maßgeblich.

Quellen für das Referenzziel:

- [Paketmetadaten von Z2M 2.14.2](https://github.com/Koenkk/zigbee2mqtt/blob/2.14.2/package.json): Node-Anforderung und Abhängigkeiten.
- [Offizielle externe Erweiterungsschnittstelle](https://www.zigbee2mqtt.io/advanced/more/external_extensions.html): Installation und Laden einer Erweiterung während des Betriebs.

## Umfang der bisherigen Praxisprüfung

Belegt sind einzelne reale Abläufe und die in den Berichten genannten
Bedien-/Speicherprüfungen. Offen bleiben insbesondere eine breite Geräte- und
Gruppenauswahl, weitere Z2M-Versionen und die native Symcon-9.0-Abnahme.
Die erneute Auslösung laufender Regeln wurde bislang im Editor/Speicher und
offline geprüft; eine reale erneute Geräteauslösung ist noch offen.
Ein lokaler gemeinsamer Farbbefehl ist bestätigt, ein zusätzlicher realer
Mehrfach-Farbbefehl über Mesh nicht. Daraus folgt keine allgemeine
Geräte- oder Converter-Freigabe.

Die Library hat weiterhin keine veröffentlichte, allgemein für den Live-Betrieb
freigegebene Version. Für eine zusätzliche Versionsabnahme mindestens genaue
Z2M-Version und Git-Revision, Node-/Symcon-Version, Erweiterungs-Build und
Prüfumfang aufnehmen. Installation, Speicherung, Ausführung, Stopp,
Wiederanlauf und gegebenenfalls Mesh getrennt nachweisen und den Ausgangsbetrieb
wiederherstellen. Private Installationsdaten bleiben außerhalb des Repositorys.
