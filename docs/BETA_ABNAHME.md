# Beta-Abnahme – 06.11.2026

## Ergebnis und geprüfter Stand

**NICHT EINREICHUNGSREIF für eine erste Beta.** Die aktuelle echte Kachelprüfung
zeigt einen reproduzierbaren Mausradfehler an einer inneren Scrollgrenze.
Zusätzlich bestehen Nachweislücken und noch nicht festgelegte
Veröffentlichungsangaben. Dieses Urteil
gilt für den unten abgegrenzten Umfang, nicht als vollständige Geräte-, Versions-
oder Sicherheitszertifizierung.

- Codebasis: `b1ae70b5c673feaea3857bda36ceecfb2c161ec8`, Branch `dev`, zu Beginn
  sauberer Arbeitsbaum. Die hier ergänzten Unterlagen sind nachfolgende Änderungen.
- Library-Metadaten: Version `0.1`, Build `0`, Datum `0`; noch Entwicklungsstand.
  Keine Version, kein Datum und kein Storeeintrag wurden für eine Veröffentlichung
  geändert oder eingereicht.
- Erweiterung: `4d6cda17434819e331927b17515470f9d7f427a192f9b874b28e671228e00722`.
  Das vorhandene Artefakt wurde geprüft, nicht neu installiert.
- Ziel: erste Beta. Stable ist nicht Gegenstand dieser Freigabevorbereitung.
- Lokale Werkzeuge: PHP 8.5.10, Node 24.20.0; der vorhandene CI-Testjob verwendet
  PHP 8.5 und Node 22 unter Ubuntu.
- Laufzeit: freigegebene Test-Symcon-Installation 9.1. Ihre MQTT-Verbindungen
  verwenden produktive Z2M-Systeme. Deshalb nur Verbindungs-/Status-/Katalogabfragen,
  lokale Ansichtswechsel und abgebrochene Bearbeitungsdialoge; keine Installation,
  YAML-Speicherung, Aktivierung, Stopps, Neustarts oder Gerätebefehle.

## Die vier Arbeitspunkte

| Punkt | Ergebnis | Nachweis und verbleibende Grenze |
| --- | --- | --- |
| 1. Zentrale und Rückweg | Teilweise abgeschlossen | R1–R3 und die ältere Zentralen-/Designerabnahme zugeordnet. Aktuelle Gesamtsuite prüft direkte Einrichtung, Sicherung, Übernahme, Konflikte, Wiederaufnahme und Rückweg mit 1/2/8 Systemen. Auf echtem Symcon 9.1 beide Modultypen ohne Parent neu angelegt, Konfiguration zweimal angewandt und Formular ohne Fehler gelesen; temporäre Instanzen anschließend entfernt. Eine neue vollständige native Einrichtungs-/Rückwegkette am Kandidaten und ein echter Kernelneustart werden damit nicht behauptet. |
| 2. Kompatibilität | Teilweise abgeschlossen | Native Symcon-9.1-Praxis bestätigt. Beide Erweiterungen melden den erwarteten Build und beantworten die Verbindungsprüfung. Die Versionsfelder bleiben in diesem Lauf leer; daraus wird keine Z2M-Version ergänzt. Der ältere belegte Stand bleibt 2.14.2-dev ohne genaue Entwicklungsrevision. Symcon 9.0 und eine stabile Z2M-Version sind mangels getrennter Testumgebungen nicht praktisch nachprüfbar. |
| 3. Aktuelle Oberfläche | Browser bestätigt; echte Kachelprüfung mit Fehler | Nach bestätigtem Testmodulupdate Systemwechsel, vorhandene Geräte-/Gruppennamen, Vorbelegung von Regelname, Auslöser, Kanal, Wert und Zielgerät, neue Geräteauswahl, Abbruch, Diagnose/Buildanzeige und Zugangserneuerung mit erhaltener offener Eingabe geprüft. Browsergrößen 360/760/1600/3840 Pixel in Hell/Dunkel, ohne horizontalen Seitenüberlauf; echtes Mausrad in beide Richtungen am jeweils betroffenen Scrollbereich. Eine Transportprüfung der Zugangserneuerung bei offenem Modal wurde über den bestehenden Button per DOM-Klick ausgelöst und ist kein Zeigerklicknachweis dieses verdeckten Buttons. Im echten Symcon-Kachelclient 9.1 funktionieren Systemwechsel und aktiver Status beider Systeme, Erneuerung per sichtbarem äußeren Button ohne Eingabeverlust sowie Abweisen der Großansicht bei offenem Dialog. Die anschließende Mausradprüfung findet B4; die native Gesamtprüfung gilt nicht als bestanden. |
| 4. Beta-Unterlagen und Qualitätsprüfung | Lokal vorbereitet | Tests, Syntax, Struktur, Kodierung, StylePHP, Formatierung, Artefakt und GitHub-CI zum Codecommit geprüft. Einrichtungs-/Fehlermeldehilfe und Storevorlage vorbereitet. Der endgültige Veröffentlichungscommit einschließlich dieser Unterlagen, Versions-/Datumsangaben und Storefelder sind vor einer Einreichung nochmals festzulegen und zu prüfen. |

## Aktuelle Praxis und Bestandserhalt

Beide Systeme antworten auf die bewusst gestartete Gesundheitsprüfung und
bestätigen denselben mitgelieferten Erweiterungsbuild. Nach anschließendem
reinem Geräteabruf sind Kataloge vollständig verfügbar. Für beide Systeme wurden
Dokument, Auswahl und offene Bearbeitungsdaten vor/nach diesem Abruf unabhängig
verglichen und unverändert gefunden.

Der separate Edge-Testbrowser verwendet weder das persönliche Browserprofil noch
die Benutzerfenster. Seine zugelassenen POSTs sind auf `select-system` und
`access-renew` begrenzt. Geräteauswahl und Änderungen des Namens erfolgen in
abgebrochenen Dialogkopien. Der ursprüngliche Systemkontext wird wiedergewählt;
Dokumente, Auswahl, offene Arbeit und Bindung wurden nach dem Browserlauf und
nach dem abschließenden nativen Fehlerlauf separat unverändert verglichen.
Die ursprüngliche gemeinsame Systemauswahl ist wiederhergestellt. Beide Systeme
bestätigen weiterhin den aktiven gespeicherten Stand. Die eigene native
Debugweiterleitung und Dateiaufzeichnung sind beendet; die Testbrowser geschlossen.
Die Debugauswertung bestätigt deutsche Ablauf-/Zugangsmeldungen ohne Ausgabe
des verwendeten Designerzugangs. Keine Regel wurde für den Test gespeichert,
gestartet, gestoppt oder geschaltet.

Vorläufige Browserläufe zeigten Fehler der Prüfinstrumentierung: exakte
Labelauswahl für umschließende Select-Labels, noch laufende Katalogseiten,
CSS `color-scheme: light dark` und verschachtelte statt ausschließlich globale
Scrollpositionen. Diese Erwartungen wurden am vorhandenen Verhalten korrigiert.
Es wurde keine Produktionslogik geändert, um einen Test grün zu machen. Die
abschließende Browserprüfung verlangt beide Systemkontexte, vollständige
Katalogseiten und tatsächliche Vorher-/Nachher-Scrollbewegungen bei Überlauf.

Private Rohantworten, Screenshots und Entwürfe bleiben außerhalb des Repositorys.
Eine leere Versionsanzeige wird als unbekannt behandelt. Erfolgreiche Status-
und Katalogabfragen sind kein neuer Nachweis einer physischen Geräteausführung.

## Mechanische Prüfungen

| Prüfung | Ergebnis am Codekandidaten |
| --- | --- |
| `php tests/run.php` | Erfolgreich, alle PHP-Prüfprogramme und 221 JavaScript-Tests, keine übersprungenen JS-Fälle. Enthält Struktur-/Kodierungs-/Artefaktprüfung und die synthetischen Mehrsystem-/Sicherungs-/Rückwegfälle. |
| PHP-Syntax | 162 getrackte PHP-Dateien ohne Syntaxfehler; StylePHP und externe Stubs ausgenommen, eingebundene Helper eingeschlossen. |
| Offizielles StylePHP `json-check.php` | Erfolgreich an einer unveränderten temporären Kopie der eigenen JSON-Dateien; Fremdquellen ausgeschlossen. |
| PHP CS Fixer im Prüfmodus | Erfolgreich: 0 von 152 Dateien zu ändern; keine Formatierung durchgeführt. |
| `npm run check:extension` über Gesamtsuite | Artefakt stimmt mit eigenen Quellen, Schema und Debugkatalog überein. |
| Preflightscanner | 0 statische Blocker, 16 zu prüfende Dateizugriffe; anschließend kontextuell geprüft, siehe unten. |
| Git-Diff | Ausgangsbaum sauber; Dokumentationsänderungen separat geführt. Private Rohdaten bleiben außerhalb des Repositorys; keine Helper-/Modul-/Artefaktänderung. |
| GitHub Tests | [Erfolgreicher Lauf zum Codecommit](https://github.com/Burki24/Z2MAutomations/actions/runs/42236882873). |
| GitHub Check Style | [Erfolgreicher Lauf zum Codecommit](https://github.com/Burki24/Z2MAutomations/actions/runs/42236882787). |

Die CI-Nachweise gelten für den genannten Commit. Sie sind kein Nachweis einer
noch nicht committeten Dokumentationsänderung oder eines zukünftigen Releasecommits.
Abhängigkeiten und gemeinsame Helper wurden nicht installiert oder verändert.

Nach den Dokumentationsänderungen sind Struktur, zentrale Kodierungsprüfung,
Artefaktprüfung und `git diff --check` erneut erfolgreich. Der neue
Dokumentationsumfang besitzt noch keinen eigenen Commit oder CI-Lauf.

## Kontextprüfung der Scanstellen

| Stellen | Einordnung |
| --- | --- |
| `AutomationDesignerCreation.php:13` | Begrenztes Lesen des HTTP-Requestbodys über `php://input`, kein frei wählbarer Dateipfad. |
| `ExtensionContract.php:35`, `ExtensionTransport.php:33`, `MigrationSource.php:40`, `RuntimeDebugContract.php:17` | Feste mitgelieferte Moduldateien: Buildheader, Uploadartefakt, Librarymetadaten und Debugkatalog. Größen-/Formatprüfungen und feste Herkunft geprüft. |
| `MigrationArchive.php:15,33,40,42,52` | Beabsichtigte private Sicherungen unter einem vom Modul festgelegten Instanzverzeichnis. Begrenzte Schlüssel, atomare Ersetzung, Rechte, Größenprüfung und Prüfsummen; kein Browserpfadparameter. Dies ist dokumentationspflichtiger lokaler Dateizugriff, kein allein durch den Scan bestätigter Verstoß. |
| `ConfigurationFormHelper.php:47`, `HelperTranslationHelper.php:116`, `VisualizationAssetHelper.php:36,49,59` | Unveränderte manifestierte Helper laden Modulformular, Übersetzungen und erlaubte Visualisierungsressourcen. Die Designerroute verwendet eine feste Assetliste. |
| `IPSViewHTMLPageHelper.php:528` | Bestandteil des unverändert gelieferten Helperpakets, aber nach Entfernung der IPSView-Integration von den Modulen nicht eingebunden. Kein aktiver IPSView-Endpunkt. |

Zusätzlich betrachtet: eigene Objektzuständigkeit und MQTT-Datenfluss,
System-/Generationsbindung, Wiederholungsquittungen, Größenbegrenzungen,
Origin-/JSON-Prüfung, feste Hookrouten, zeitlich begrenzte Hash-Zugänge und
Widerruf alter IPSView-Sitzungen. Die Gesamtsuite enthält die entsprechenden
Positiv-/Negativfälle. Kein allgemeines Shell-/PHP-Ausführungsinterface und
kein beliebiger Datei-/URL-Parameter in der Designerroute festgestellt.
Dies ersetzt kein unabhängiges Penetrationstestgutachten.

## Reproduzierter nativer Mausradfehler

Im echten Symcon-Kachelclient 9.1 bei 1400 × 900 Pixeln den eingebetteten
Designer laden, einen vorhandenen Geräteablauf anzeigen und über der mittleren
Fläche nach unten scrollen. Die äußere Designer-Dokumentposition erreicht im
Nachweis 424 Pixel. Der Zeiger liegt danach innerhalb von `canvas-scroll`,
dessen eigene Position 0 und dessen mögliche Scrollstrecke 201 Pixel beträgt.
Ein echtes Hochscrollen mit `deltaY: -300` ändert keine der beiden Positionen;
auch der zuvor geprüfte große negative Delta änderte nichts.

Der unveränderte zentrale Helper prüft, ob ein Element grundsätzlich überläuft,
aber nicht, ob es sich in der angeforderten Richtung bewegen kann:
`libs/helper/visualization/interaction.js:62` setzt `scrollTop` und verbraucht
das Ereignis stets. Der Aufrufer bei Zeile 107 beendet die Suche beim ersten
solchen Element. An dessen oberer Grenze erreicht das Ereignis den noch
beweglichen äußeren Bereich daher nicht. Der Fehler wurde an echter
Zeigereingabe mit Vorher-/Nachher-Positionen und Screenshot reproduziert;
es ist kein ausschließlich synthetischer Eventtest.

Systemauswahl, Status und offene Eingaben bleiben dabei erhalten. Die native
Gesamtprüfung wurde nicht auf Grün gesetzt. Eine Korrektur gehört in die
kanonische Helperquelle mit Regression für verschachtelte Scrollgrenzen,
regulärer Paketverteilung und anschließender Prüfung desselben echten Clients.
In diesem Abnahmeauftrag wurden weder Helper noch Modulquellen geändert.

## Offene Befunde

Nachtrag vom 06.11.2026 zum ausdrücklich beauftragten Korrekturpaket:
Die kanonische Quelle in Symcon_ModuleHelper reicht das Mausrad an den nächsten
tatsächlich beweglichen Vorfahren einschließlich Dokument-Viewport weiter.
Ein teilweise bewegter Bereich verbraucht den gesamten Schritt, damit keine
doppelte Bewegung entsteht. Offene Dialoge blockieren auch die native
Scrollweitergabe in den Hintergrund. Regressionen waren vor den jeweiligen
Korrekturen rot: Dokumentposition 424 statt 124 sowie Dialoghintergrund 304
statt unverändert 424. Quelle und generiertes Asset bestehen nun diese Tests,
einschließlich echter Browser-Mausradaktionen und expliziter Frameweitergabe.
Der PHP-Test prüft die deutsche DebugFlow-Meldung und bereinigte Assetkennung.

Im echten Test-Kachelclient 9.1 bei 1400 × 900 Pixeln die korrigierte Ressource
ausschließlich per Route im separaten Testbrowser geliefert. Echtes Mausrad
bewegt das Dokument abwärts und wieder aufwärts (506 nach 206 Pixeln).
Dieser isolierte Nachweis ändert keine Serverdateien und ersetzt keine Abnahme
des später installierten regulären Pakets. Beide Systemdokumente einschließlich
Bindung, Auswahl und offener Arbeit sind danach identisch zum Ausgangsstand.
Der derzeit nur zwischengespeicherte Betriebsstatus war nicht bestätigt; daraus
wird keine neue Aussage über aktive Z2M-Regeln abgeleitet. Keine Katalogabfrage,
YAML-Speicherung, Aktivierung oder Gerätebefehle für diesen Test ausgelöst.

Das Consumer-Testpaket entsteht isoliert über die vorhandenen Metadaten- und
Bundlefunktionen. Synthetische Herkunftsangaben bleiben außerhalb des normalen
Arbeitsbaums. Die endgültige Gesamtsuite besteht unter PHP 8.5.10 einschließlich
aller 221 JavaScript-Tests; Struktur, Kodierung, Build- und Formatierungsprüfung
des Consumer-Pakets sind ebenfalls grün. Der verbindliche Weg bleibt:
Benutzer commit/push auf Helper-dev,
Freigabe nach main, automatische Metadaten/Consumer-Verteilung, Pull in
Z2MAutomations/dev, Integritätsprüfung und Modulupdate der Testinstallation.
Danach denselben nativen Grenzfall ohne Browser-Ressourcenaustausch prüfen.
Bis dahin ist B4 trotz lokal bestätigter Korrektur nicht geschlossen.

Bestätigte **BLOCKER: 1**, **HINDERNIS: 4** im untersuchten Umfang.

| ID | Schwere / Basis | Befund und kleinster nächster Schritt |
| --- | --- | --- |
| B4 | BLOCKER / Project | Zentrale Korrektur samt Regressionen und isoliertem Kacheltest bestätigt. Reguläre Helper-Verteilung und native Nachprüfung des tatsächlich installierten Pakets fehlen noch. Testbrowser-Ressourcenaustausch gilt nicht als installierter Freigabestand. |

| ID | Basis | Hindernis und kleinster nächster Schritt |
| --- | --- | --- |
| B1 | Project | Native Symcon-9.0-Abnahme fehlt trotz deklarierter Mindestversion. Getrennte 9.0-Umgebung bereitstellen und Formular/Kachel/Grundablauf prüfen oder vor Veröffentlichung die zugesagte Versionsspanne ausdrücklich neu entscheiden. Hier wurde sie nicht geändert. |
| B2 | Risk | Stabile Z2M-Ausgabe nicht praktisch abgenommen; aktuelle Versionsfelder leer. Genaue Version/Revision in einer getrennten stabilen Testumgebung erfassen und Installation, Übertragung, Ausführung, Stopp und Wiederanlauf gezielt belegen. Keine produktive Umstellung als Ersatz. |
| B3 | Project | R4 bleibt für eine neue vollständige native Einrichtungs-/Rückwegkette und die konkret noch fehlenden Restartfälle teilweise offen. Die vorhandenen Sicherungen und synthetischen Fehlerfälle sind bestätigt. Eine begrenzte Praxisabnahme an getrennten Testdaten mit Vorher-/Nachher-Besitzer- und Dokumentvergleich ergänzen; bestehende Übernahmen nicht zu Testzwecken zurückrollen. |
| B5 | Symcon / Project | Veröffentlichungsidentität und Storevorlage noch nicht verbindlich festgelegt beziehungsweise im Account geprüft. Releaseversion/-datum, finalen Commit samt grüner CI und untenstehende Pflichtfelder festlegen. Keine Einreichung in diesem Auftrag. |

**HINWEIS:** Reale Drei-/Acht-Broker-Abnahme, weitere Geräte-/Farbconverter und
weitere Z2M-Versionen sind zusätzliche Kompatibilitätsarbeit. Sie dürfen nicht
als bereits bestätigt beworben werden. Für die erste Beta den tatsächlich
belegten Umfang ausdrücklich benennen. Windows bleibt ein separates Projekt.

## Vorbereitete Storeangaben

Die [aktuelle Symcon-Einreichungsdokumentation](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/)
wurde am 06.11.2026 herangezogen. Beta und Testing werden direkt angeboten;
der Symcon-Review betrifft Stable. Ein erfolgreicher Storeeintrag bestätigt
deshalb nicht unsere Codequalität oder die fehlenden Laufzeitnachweise.

| Feld | Vorbereiteter Inhalt / noch erforderliche Entscheidung |
| --- | --- |
| Kanal | Beta, erste Veröffentlichung; kein Stable-Antrag. |
| Bundle-ID | Falls noch nicht angelegt: Vorschlag `de.burki24.z2mautomations`. Eine bereits vorhandene Store-ID unverändert verwenden; Accountbestand hier nicht geprüft. |
| Git-URL | `https://github.com/Burki24/Z2MAutomations` |
| Commit | Erst den vollständig geprüften finalen Veröffentlichungscommit wählen. `b1ae70b` ist die hier geprüfte Codebasis, nicht automatisch die spätere Einreichung. |
| Sprache | Für die erste Vorlage Deutsch; weitere Sprachen erst nach eigener vollständiger Sprachprüfung anbieten. |
| Name | Z2MAutomations |
| Beschreibung | Automationen für bis zu acht Zigbee2MQTT-Systeme zentral in Symcon erstellen und verwalten. Browserdesigner und native Kachel unterstützen Geräte-, Zeit- und Sonnenregeln, Bedingungen, Szenen und Aktionsfolgen. Eine eigene Z2M-Erweiterung führt übertragene und aktivierte Regeln unabhängig von Symcon aus. Systemübergreifende Aktionen erfordern eine passende MQTT-Verbindung und gerichtete Freigaben. |
| Versionsinformation | Erste Beta der zentralen Automationsverwaltung mit Browserdesigner, nativer Symcon-Kachel und eigener Zigbee2MQTT-Erweiterung. Kompatibilitätsgrenzen und belegte Versionen in der Dokumentation beachten. Versionsnummer und Datum vor Einreichung mit Library und Changelog abgleichen. |
| Dokumentation | Nach Veröffentlichung auf die Anleitung des exakt veröffentlichten Stands verlinken; Einstieg README und `docs/BETA.md`. Keine lokale oder vorläufige Vorschauadresse verwenden. |
| Kategorie | Mindestens eine passende im Account angebotene Kategorie wählen; die konkrete Auswahl wurde nicht im Account geprüft. |
| Lizenz | Vorhandene PolyForm Noncommercial 1.0.0 unverändert benennen. Dieser Bericht ist keine gesonderte Lizenzprüfung. |

## Freigabeschritte

1. B4 korrigieren und im echten Kachelclient nachprüfen. B1–B3 schließen oder
   die zulässige Beta-Zielmatrix ausdrücklich entscheiden. Fehlende Pflichtnachweise
   nicht allein durch einen Warntext als erledigt markieren.
2. Dokumentation, Library-Metadaten und Changelog für die bestätigte Version
   konsistent vorbereiten; bis dahin `Unveröffentlicht` beibehalten.
3. Benutzer führt Commit/Push über GitHub Desktop aus. CI zum finalen Commit
   prüfen, anschließend den vereinbarten Release-/Branchablauf durchführen.
4. Storepflichtfelder kontrollieren. Erst auf gesonderten Veröffentlichungsauftrag
   einreichen. Dieser Bericht erteilt keine Freigabe zum Merge, Tag oder Release.

Die aktuelle Empfehlung bleibt: Funktionsumfang eingefroren lassen, den
Mausradfehler korrigieren und fehlende Laufzeitnachweise gezielt ergänzen. Keine pauschale
Wiederholung der bereits bestandenen R1–R3- und Designerprüfungen.
