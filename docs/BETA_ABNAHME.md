# Beta-Abnahme – Veröffentlichungsstand vorbereiten

Stand: 07.11.2026. Ziel: **erste Beta**, kein Stable-Antrag.

**BEDINGT EINREICHUNGSREIF:** Der lokale Quellkandidat ist geprüft. Kein
bestätigter technischer Blocker; offen bleibt B5: Benutzer-Commit/Push, finale
CI und genauer Veröffentlichungscommit. Im Store wurde nichts angelegt oder
veröffentlicht. Der noch uncommittete Arbeitsbaum ist kein unveränderlicher
Veröffentlichungscommit.

## Kandidat und Grenzen

- Basis: cd5cfc33c921365c69814078238444c3ea7d5ac5 auf dev plus die geprüften
  gemeinsamen Designer-Anpassungen und diese Beta-Vorbereitung.
- Bisherige Metadaten: Library 0.182, Build 221627305. Die automatische
  Versionierung bestimmt den nächsten Stand nach dem Benutzer-Push. Keine
  Versionsnummer, kein Build und kein Datum wurden hier von Hand erhöht.
- Gemeinsamer Designer: @z2ma/designer 0.2.0, Inhalt 7909e0e7638c49a1fd4f6c58d35a0347005a1568fddcaa9fa9725c11e0d39007.
  Herkunft, Lizenz und SHA-256 sind festgeschrieben. [ADR 0035](adr/0035-pinned-shared-designer-package.md)
  beschreibt Übernahme und Rückweg. Die Desktop-App wird nicht benötigt.
- Native Kachel und Helper unverändert; kein lokales Helper-Testpaket.
  Z2M-Erweiterung, Schema, Entwürfe und MQTT-Protokoll unverändert.
- Keine Test-/Live-Installation aktualisiert, keine Geräte geschaltet und keine
  Automationen gespeichert, aktiviert oder gestoppt. Der auf Benutzerwunsch
  erhaltene Dokumentationsaufbau bleibt bestehen.

## Nachweise und verbleibende Punkte

| ID | Basis | Stand |
| --- | --- | --- |
| B1 | Benutzer-Praxis | Symcon 9.0 live bestätigt; abgeschlossen. |
| B2 | Benutzer-Praxis | Stabiles Z2M 2.14.2 live bestätigt; abgeschlossen. |
| B3 | Project | Native Klickprüfung, Einrichtung, Altübernahme, Sicherungen und Rückwege einschließlich P4–P6 abgeschlossen. [R4-Nachweis](R4_ACCEPTANCE_PLAN.md). |
| B4 | Project | Native Mausradgrenzen mit regulärem Helper-Paket praktisch bestätigt; abgeschlossen. |
| B5 | Project / Symcon | Finaler Commit samt automatischer Metadaten und grüner CI fehlt noch. Nach Benutzer-Push und Release-PR genau diesen Commit für den Store festlegen. |

Die Herkunft und Grenzen der bisherigen Praxis bleiben in
[COMPATIBILITY.md](COMPATIBILITY.md), [PRUEFLISTE.md](PRUEFLISTE.md) und im
[historischen Abnahmebericht](BETA_ABNAHME_2026-11-06.md) erhalten. Deren frühere
Offenbewertungen werden durch den obigen Stand ersetzt.

Aktuelle lokale Prüfung: PHP-Gesamtsuite einschließlich **229 JavaScript-Tests**
bestanden. Paketprüfung und Regression gegen Überschreiben nativer Kachel-/
Helperdateien bestanden; der neue Grenzfall war vor der Korrektur rot. Echte
isolierte Browserprüfung bestätigt DE/EN, offene Eingaben, Systemwahl und
360–3840 Pixel. Die neue Paketübernahme wurde nicht auf einem installierten
Symcon nachgetestet; die bisherigen nativen Praxisnachweise bleiben abgegrenzt
bestehen. Das ist keine allgemeine Versions-/Geräte- oder Sicherheitszertifizierung.

Der statische Store-Scanner findet 0 Blocker und 18 Dateizugriffs-Prüfhinweise.
Diese Stellen wurden kontextuell geprüft: begrenztes php://input, feste eigene
Assets/Kataloge/Metadaten oder private, instanzgebundene Sicherungen mit festen
Schlüsseln und Größen-/Prüfsummengrenzen. Keine frei wählbaren Dateipfade in
Browseraufträgen. Die Store-Regel zu lokalen Dateien bleibt bei der Einreichung
sichtbar; Windows-Dateirechte sind weiterhin eine lokale Schutzempfehlung.

Drei/acht Erweiterungen sind synthetisch geprüft, zwei Systeme praktisch.
Weitere reale Broker, Converter und Z2M-Versionen sind zusätzliche R5-Arbeit.
Ubuntu-Desktop-Prüfungen gehören zur unabhängigen Anwendung und blockieren
nicht diese Symcon-Beta.

## Abschließende mechanische Prüfung

| Prüfung | Ergebnis |
| --- | --- |
| php tests/run.php | Bestanden: PHP-Prüfprogramme und 229 JavaScript-Tests; keine übersprungenen JS-Fälle. |
| Ausgeliefertes PHP | 92 PHP-Dateien unter libs und beiden Modulen ohne Syntaxfehler. |
| Offizieller JSON-Stylecheck | 22 eigene JSON-Dateien bestanden; Fremdquellen ausgeschlossen. |
| PHP CS Fixer 3.95.27 | Prüfmodus: 0 von 154 Dateien zu ändern. PHP 8.5.10. |
| Struktur, Kodierung, Erweiterungsartefakt | Bestanden; kein manuell geändertes Z2M-Artefakt. |
| Gemeinsamer Designer | Paket-/Sprachprüfung auch aus isolierter Kopie ohne Desktop-Repo und ohne npm-Abhängigkeiten bestanden. |
| Browser | Separater Edge-Testprozess, ausschließlich Loopback/Synthetik: DE/EN, Eingabeerhalt, Systemwahl, Scrollen und 360/1920/3840 Pixel in Hell/Dunkel bestanden. |
| Zentraler Helper | Dateiprüfsummen stimmen mit dem eingebundenen Manifest überein; kein Helper geändert. |
| Historische GitHub-CI | Tests und Check Style zu cd5cfc33 grün. Für den neuen Kandidaten erst nach Benutzer-Push gültig. |

## Zuordnung der aktuellen Store-Reviewpunkte

| Punkt | Ergebnis im abgegrenzten Umfang / Nachweis |
| --- | --- |
| SYM-01 Dateien | Begrenzte eigene Assets in libs/AutomationDesigner.php und libs/DesignerPresentation.php, private instanzgebundene Archive in libs/MigrationArchive.php; 18 Scannerstellen kontextuell eingegrenzt. Lokalen Dateizugriff bei Einreichung erläutern. |
| SYM-02 Lokalisierung | Erste Storevorlage de; beide Module besitzen de-Kataloge. validate_structure.php und Paket-/Sprachprüfungen bestanden. Browser zusätzlich DE/EN getestet. |
| SYM-03 Neuanlage | tests/module.php und tests/connection.php ohne vollständige Voraussetzungen bestanden; reale native Neuanlage bereits in B3 belegt. |
| SYM-04 Datenfluss | MQTT-Anbindung über libs/MQTTTransport.php und unveränderte zentrale Datenflusshelper; Verbindungstests und vorhandene native Praxis belegt. |
| SYM-05 Objektzuständigkeit | Eigene Statusvariablen und ausdrücklich zugeordnete Systeme; Scope-/Owner-Prüfungen in Workspace und Migration getestet. |
| SYM-06 Objekterstellung | Interne RegisterTimer in beiden Modulen; keine eigene externe Ereigniserstellung im Laufzeitcode. R4-Prüfungen bleiben maßgeblich. |
| SYM-07 Ausnahmen | Keine behauptete individuelle Symcon-Ausnahmegenehmigung. Der begrenzte Dateizugriff wird offengelegt; keine neue Abweichung eingeführt. |
| SYM-08 Qualität | Eigene Prüfungen zusätzlich zum Storeverfahren ausgeführt; Review wird nicht als Fehlerfreiheitsnachweis ausgegeben. |
| SYM-09 Namen | library.json, beide module.json und vorgeschlagener Storename ohne gesperrten Symcon-/IPS-Namensbestandteil. |
| SYM-10 Version | Mindestversion 9.0. Optionale native Großansicht ab 9.1 ist getrennt abgefragt; Zielkanal Beta. |
| SYM-11 Benutzereigenschaften | Keine neue Manipulation von Namen, Sichtbarkeit, Profilen oder Logging; nur Metadaten-URLs und Paketimportgrenze geändert. Bestehende Modul-/R4-Nachweise erhalten. |
| SYM-12 PHP-Tags | Scanner und Syntaxprüfung bestanden; keine kurzen PHP-Tags neu eingeführt. |
| SYM-13 Properties | Keine IPS_SetProperty-/IPS_ApplyChanges-Aufrufe im eigenen Laufzeitcode; Konfiguration bleibt benutzergeführt. |
| SYM-14 Storetexte | Name, Beschreibung, Versionsinformation, Kategorie und Dokumentationsziel unten vorbereitet. Vollständiger SHA bleibt B5. |
| SYM-15 Configurator | Nicht anwendbar: kein Configurator-Modultyp angeboten. System-/Gerätezuständigkeiten zusätzlich in Workspace-Tests geprüft. |
| SYM-16 Discovery | Nicht anwendbar: kein Discovery-Modultyp angeboten; vorhandene Verbindungsauswahl bleibt Modulkonfiguration. |
| SYM-17 Logging | Kein IPS_LogMessage im eigenen Laufzeitcode; bestehende native Debughelper unverändert und mitgeprüft. |

## Vorbereitete Storeangaben

Am 07.11.2026 das erreichbare Entwicklerkonto und die Kategorien nur lesend
geprüft: kein bestehender Z2MAutomations-Eintrag im Konto. Keine Vorlage angelegt.
Die vorgeschlagene Bundle-ID ist damit noch nicht reserviert.

| Feld | Vorbereiteter Wert |
| --- | --- |
| Kanal | beta |
| Bundle-ID | de.burki24.z2mautomations |
| Git-URL | https://github.com/Burki24/Symcon_Z2MAutomations |
| Git-Commit | Nach Commit/Push und geprüftem Release-PR den exakten vollständigen SHA wählen; keinen Branchnamen und keinen alten Abnahmecommit eintragen. |
| Sprache | de für die erste Storevorlage. Der Browserdesigner bietet zusätzlich Englisch; keine vollständige englische Store-/Handbuchausgabe behauptet. |
| Name | Z2MAutomations |
| Kategorie | Komfort, aktuelle Store-Kategorie 3. |
| Dokumentation | https://github.com/Burki24/Symcon_Z2MAutomations/blob/VEROEFFENTLICHUNGS_SHA/README.md – vor Einreichung den Platzhalter durch den gewählten 40-stelligen Commit ersetzen. |
| Lizenz | PolyForm Noncommercial 1.0.0 |
| Flags | Normales Funktionsrelease; bestehende Kanäle nicht schließen. |

**Beschreibung:** Automationen für bis zu acht Zigbee2MQTT-Systeme zentral in
Symcon erstellen und verwalten. Im Browserdesigner Geräte, Kanäle, Werte,
Uhrzeiten und Sonnenzeiten ohne YAML-Kenntnisse auswählen. Bedingungen, Szenen
und Aktionsfolgen ergänzen; Regeln ausdrücklich prüfen, speichern und aktivieren.
Die eigene Zigbee2MQTT-Erweiterung führt aktivierte Regeln unabhängig von Symcon
aus. Der Designer ist zusätzlich in der nativen Symcon-Kachel verfügbar.
Aktionen zwischen getrennten Brokern benötigen eine MQTT-Bridge und gerichtete
Partnerfreigaben. Belegte Versionen und Grenzen stehen in der Dokumentation.

**Versionsinformation:** Erste Beta mit zentraler Verwaltung von bis zu acht
Z2M-Systemen, Browserdesigner auf Deutsch und Englisch, nativer Symcon-Kachel,
Geräte-/Zeit-/Sonnenregeln, Bedingungen, Szenen und Aktionsfolgen. Mit sicherem
Einlesen vorhandener Regeln, ausgewähltem Symcon-Ereignisimport, verständlichen
Prüfungsfehlern, Erweiterungsverwaltung und zeitbegrenzten Debug-Aufzeichnungen.
Neue Regeln beginnen ausgeschaltet. Vor Einrichtung und Änderungen Sicherungen
anlegen; die Testinstallation am selben Broker verwendet denselben Z2M-Regelspeicher.

## Verbleibender Freigabeweg

1. Benutzer committet und pusht diesen vorbereiteten Stand auf dev.
2. Automatische Versionsanpassung abwarten und pullen. Tests und Check Style
   müssen für den tatsächlich gewählten Stand grün sein; alte CI gilt nicht
   für diese uncommitteten Änderungen.
3. Vom geprüften Metadatenstand einen temporären Releasezweig für den PR nach
   main verwenden. Dort das Changelog der bestätigten Version und dem tatsächlichen
   Veröffentlichungsdatum zuordnen. Die Metadaten-Action läuft nur auf dev;
   so erzeugt diese reine Release-Zuordnung keine weitere Versionsanhebung.
   Tests und Check Style müssen auch für den Release-PR grün sein. Danach den
   endgültigen main-Commit festhalten; dev erhalten und zurücksynchronisieren.
   Keine manuelle vorgezogene Versionsanhebung in dieser Vorbereitung.
4. Dokumentationslink auf denselben Commit setzen und erst auf ausdrücklichen
   Veröffentlichungsauftrag den Beta-Kanal einreichen. Die Beta wird dabei
   unmittelbar angeboten; es gibt keinen vorgeschalteten Stable-Review.

Die zentrale Versionsregel zählt auch reine Dokumentationscommits als
Quelländerung. Deshalb die bestätigte Versionsüberschrift nicht wiederholt auf
dev nachführen; das würde die nächste automatische Anhebung auslösen. Der
Releasezweig enthält dieselben Quell-/Metadaten und nur die endgültige
Changelog-Zuordnung. Nach Rücksynchronisierung darf dev regulär weiterzählen;
die bereits veröffentlichte main-Version bleibt dabei unverändert.

Quelle: [aktuelle Symcon-Einreichungsregeln](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/),
am 07.11.2026 erneut gelesen. Die Markdown-Seiten waren beim Abruf nicht
verfügbar; die offizielle HTML-Seite wurde verwendet. Alle 17 Reviewpunkte
wurden dem bestehenden Modulaufbau und den genannten Nachweisen zugeordnet;
kein eigener Configurator oder Discovery-Modultyp wird angeboten.
