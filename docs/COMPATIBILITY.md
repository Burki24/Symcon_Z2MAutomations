# Kompatibilität und belegte Versionen

## Benutzerbestätigung aus dem Live-Betrieb – 07.11.2026

Der Benutzer bestätigt die Praxisnachweise für **Symcon 9.0** und die stabile
**Zigbee2MQTT-Version 2.14.2** im eigenen Live-System. B1/B2 sind damit als
Benutzer-Praxisnachweise abgeschlossen. Dies ist keine neue unabhängige
Agentenprüfung. Genaue Git-Revision, Erweiterungsbuild und eine gesonderte
Aufschlüsselung der dortigen Einzelprüfungen wurden dabei nicht neu erfasst.
Die fehlende separate Testinstallation ist kein weiterhin fehlender
Live-Praxisnachweis. Frühere Aussagen dazu beschreiben den damaligen Stand.

## Nachtrag zur Beta-Abnahme am 06.11.2026

Die aktuelle Codebasis b1ae70b besteht lokal mit PHP 8.5.10 und Node 24.20.0;
die GitHub-Prüfung desselben Commits verwendet PHP 8.5 und Node 22. Auf echtem
Test-Symcon 9.1 wurden die fehlerfreie Neuanlage beider Modultypen ohne Parent
und die aktuelle Browserbedienung beider Systeme ergänzt. Beide Z2M-Empfänger
bestätigen Build `4d6cda174348`; ihre Versionsfelder bleiben in dieser Abnahme
leer. Daraus wird keine neue Versionsfreigabe abgeleitet.

Eine getrennte Symcon-9.0-Installation und eine stabile Z2M-Testinstanz standen
bei dieser Agentenabnahme nicht zur Verfügung. Die spätere Benutzerbestätigung
aus dem Live-Betrieb ist oben getrennt erfasst. Der
[Beta-Abnahmebericht](BETA_ABNAHME.md) nennt Umfang, CI-Bezug und Grenzen.

## Bisherige Versionsnachweise

Abgleich: 07.11.2026. Die versionsbezogenen Nachweise vom 03.11.2026 bleiben
erhalten und werden um die obige Benutzerbestätigung ergänzt.
Weitere bestätigte Zentralen-/Designerabläufe sind in
[PRUEFLISTE.md](PRUEFLISTE.md) berücksichtigt; daraus folgt keine neue
Z2M-Versionsfreigabe. Diese Übersicht trennt reale Praxisnachweise von lokal
geprüften Verträgen. Eine ähnliche Versionsnummer ist keine eigene Abnahme.

| Umgebung | Nachweis | Grenze |
| --- | --- | --- |
| Z2M **2.14.2-dev** | Praktisch belegt: Installation, Editor-/YAML-Übertragung, Aktivierung, Stopp und reale Geräteausführung. Der dokumentierte erste native Lauf nennt diese Version ausdrücklich. Die vorhandene Versionsvariable eines im Praxisbetrieb verwendeten Systems meldet sie am 03.11.2026 erneut. | Die genaue Git-Revision des Entwicklungsstands ist nicht dokumentiert. Deshalb keine Zusage für jeden Stand mit derselben Bezeichnung. Nicht jeder spätere Funktionsnachweis enthält eine eigene Versionsaufnahme. |
| Z2M **2.14.2** ohne `-dev` | Schnittstellenziel; Abhängigkeiten und Host-Vertrag als Referenz geprüft. Benutzer bestätigt am 07.11.2026 die erfolgreiche Live-Praxis. | Kein zusätzlicher Agentenlauf; Git-Revision und vollständige Einzelprüfmatrix nicht neu erfasst. |
| Andere Z2M-Versionen | Kein belastbarer versionsbezogener Praxisnachweis vorhanden. | Keine pauschale Mindestversions- oder Kompatibilitätszusage. |
| Symcon **9.1**, PHP **8.5** | Native Formularbedienung und Praxisabläufe in der freigegebenen Installation dokumentiert. | Keine vollständige Abnahme sämtlicher Kombinationen. |
| Symcon **9.0**, PHP **8.5** | Deklariertes Projektziel; Offline-Prüfungen gegen die eingebundenen Stubs. Benutzer bestätigt am 07.11.2026 die erfolgreiche Live-Praxis unter Symcon 9.0. | Keine zusätzliche unabhängige Agentenabnahme oder neue PHP-Versionsaufnahme. |

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
Gruppenauswahl und weitere Z2M-Versionen. Symcon 9.0 und stabiles Z2M 2.14.2
sind zusätzlich durch die oben genannte Benutzerbestätigung belegt.
Die drei Varianten der erneuten Auslösung sind zusätzlich mit echten
Uhrzeitstarts und einer realen Leuchte bestätigt. Wiederholte physische
Geräteereignisse und weitere Bedingungskombinationen sind separat zu prüfen.
Ein lokaler gemeinsamer Farbbefehl ist bestätigt, ein zusätzlicher realer
Mehrfach-Farbbefehl über Mesh nicht. Daraus folgt keine allgemeine
Geräte- oder Converter-Freigabe.

Die Library hat weiterhin keine veröffentlichte, allgemein für den Live-Betrieb
freigegebene Version. Für eine zusätzliche Versionsabnahme mindestens genaue
Z2M-Version und Git-Revision, Node-/Symcon-Version, Erweiterungs-Build und
Prüfumfang aufnehmen. Installation, Speicherung, Ausführung, Stopp,
Wiederanlauf und gegebenenfalls Mesh getrennt nachweisen und den Ausgangsbetrieb
wiederherstellen. Private Installationsdaten bleiben außerhalb des Repositorys.
