## Vollständige IPSView-Ansicht lokal integriert – 06.11.2026

Das regulär verteilte IPSViewHTMLPageHelper-Paket ist eingebunden; keine lokalen
Helperkopien oder veränderten Hashmanifeste. DesignerIPSView registriert die
optionale Ausgabe samt Serveradresse und verwaltet genau eine HTMLBox-Variable
über den Helper. Dessen zentrale Interaktionsressource und der vorhandene
HTTP-Origin-Resolver bleiben unverändert. Es gibt keine neue MQTT-Anbindung und
keinen zweiten Editor.

Ein langlebiger, ausdrücklich freigegebener Einstieg steht nur in der
HTML-Variable. Sein Hash und der aktuelle Besitzer-/Systemscope stehen in
Attributen. Die IPSView-Shell tauscht ihn innerhalb ihrer eigenen HTTP-Origin
gegen eine normale befristete Designersitzung. Danach verwendet der vorhandene
Reader dieselben System-, Geräte-, Editor- und Statuspfade. Die einzige
Framing-Ausnahme betrifft die opt-in ipsview.html-Shell; das normale Webfrontend
behält frame-ancestors self. Keine CORS-Freigabe. Details: ADR 0033.

Ausschalten oder Neuerzeugen widerruft Einstieg und alle damit eröffneten
IPSView-Sitzungen. Die bestehende HTML-Variable bleibt bei Deaktivierung erhalten.
Der Parent darf nur eine in-place Verlängerung anfordern; er erhält keine
Systemdaten oder Kennungen. Native Farbsteuerung bleibt same-origin.

Synthetische Vertragsprüfungen verwenden acht Systeme und prüfen Anzeige,
Berechtigungen, Ursprungskontrolle, Widerruf und erhaltene Entwürfe. Die lokale
Browserprüfung verwendet den echten Modulcode hinter einem synthetischen
HTTP-Transport und eine opake data-HTMLBox. Der tatsächliche IPSView-Client wurde
nicht verwendet. Bei 1600, 760 und 360 Pixeln gab es keinen horizontalen Überlauf.
Echte Mausradaktionen scrollten den verschachtelten Designer nach unten und bis
0 zurück. Zugangserneuerung mit offenem Dialog erhielt Namen, URL und Auswahl.
Alle HTTP-Aufträge wurden vom echten Modulcode im synthetischen Kernel verarbeitet;
POSTs waren auf Einstieg, Systemauswahl und Verlängerung beschränkt. Keine
MQTT-Ausgabe. Die Mausrad-, Touch-, Darstellungs- und Größenabnahme des echten
IPSView-Clients bleibt ausdrücklich offen. Kein Zugriff auf Live-Symcon, keine
Installation oder Z2M-Änderung und keine Geräteaktion.

Prüfungen mit PHP 8.5.10: vollständiger Offline-Lauf einschließlich 224
JavaScript-Tests erfolgreich. Der lokale 128-MB-Standard reichte für den großen
module-extension-Integrationstest nicht aus; der erfolgreiche vollständige Lauf
verwendete eine ausschließlich temporäre zusätzliche Test-INI mit 256 MB,
auch für die PHP-Unterprozesse. Keine Änderung an PHP- oder Symcon-Konfiguration.
Struktur, Kodierung, unverändertes Erweiterungsartefakt, offizielle StylePHP-
JSON-Prüfung und php-cs-fixer im Prüfmodus ebenfalls erfolgreich.
Die ergänzten Fehlerfälle für ungültige Adressen und fehlende Assets wurden
anschließend nochmals mit der synthetischen Acht-System-Fixture geprüft.

Die persistenten IPSView-Einstellungen liegen in elements, nicht in den
vorübergehenden actions des nativen Formulars; ein eigener Regressionstest
sichert diesen Vertrag.

## Responsiven Browserdesigner nach Mockup umgesetzt am 06.11.2026

## Vollständiger Designer in der nativen Kachel – 06.11.2026

Den bisherigen kompakten Öffner durch einen äußeren HTML-SDK-Loader für denselben
vorhandenen Designer ersetzt. Keine zweite Editorimplementierung. Der benannte
Child bleibt same-origin und verwendet unverändert die bestehenden Zugänge,
Systemheader und Aufträge. Native Text-/Karten-/Akzentfarben und Grundschriftgröße
werden ausschließlich an den eigenen Child weitergegeben. Die ursprüngliche
Vermischung von Browser-Dunkelfarben mit Symcons Textfarbe ist damit beseitigt.

Vorab VisualizationAssetHelper, VisualizationThemeHelper und IPSViewHTMLPageHelper
geprüft. Der eigenständige äußere Loader bindet die bereits manifestierte
VisualizationInteractionScript-Ressource ein; auch der Child lädt dieselbe
unveränderte zentrale Ressource über einen festen erlaubten Assetpfad. Keine
Helperkopien und keine neue allgemeine Theme- oder Scrollbibliothek eingeführt.
Die Ladereihenfolge wartet auf diese Ressource, bevor sie erstmals Daten abfragt:
der Praxislauf hatte sonst eine echte instance_busy-Antwort durch den eigenen
parallelen Abruf gezeigt. Die Wartefolge besitzt einen Regressionstest.

Versionsgrenze im echten Client nachvollzogen: Typ 1 zeigt beim nativen Öffnen
weiterhin die Statusvariablen. Ab Symcon 9.1 muss Typ 2 registriert sein, damit
HTML auch in der Großansicht erhalten bleibt. Create und ApplyChanges wählen
dies versionsabhängig. Eigener PHP-Test prüft 9.0, 9.1 und 10.0, ohne die fremden
Kernelstubs zu verändern. Unter 9.0 bleibt die normale eingebettete Kachel nutzbar.

Praxisprüfung im separaten Edge am echten Test-Symcon 9.1, mit folgenden Grenzen:
keine Z2M-Speicherung, Installation, Aktivierung oder Schaltbefehle. Beide Systeme
mit acht beziehungsweise einer Automation ausgewählt und Status geprüft.
Zugangserneuerung mit offenem Dialog und unübernommenem Namen bestätigt; die URL,
Systemauswahl und Eingabe blieben erhalten. Großansicht ist bei offenen Dialogen
gesperrt und wurde nach deren Schließen auf derselben Instanz erfolgreich geöffnet.
Keine neue Browserseite. Die vorhandene CSP wurde nicht gelockert.

Native SDK-Farbparameter für Hell/Dunkel geprüft, danach Großansicht bei 1600,
760 und 360 Pixeln Browserbreite. Effektive Designerbreiten 1536, 696 und 296 Pixel,
kein horizontaler Überlauf. Reale Mausradaktionen bewegten das Dokument um 600 Pixel
abwärts und wieder bis 0 aufwärts. Der native Host kann beim Vergrößern oder
Responsive-Umbruch seine Frames ersetzen; die Teststeuerung ermittelt sie danach
neu. Ein vorheriger Frame-detached-Befund war ein Teststeuerungsfehler.

Alle Entwurfsfingerprints und die ursprüngliche globale Auswahl anschließend
identisch zum Ausgangsstand; beide gespeicherten Revisionen weiterhin aktiv.
Die kontrollierten Mutationsrouten waren im Testbrowser auf select-system und
access-renew begrenzt. Kein verbotener Aufruf, kein HTTP- oder JavaScript-Fehler
im abschließenden Lauf. Native Debug-Dateiaufzeichnung danach beendet.
Die zugrunde liegenden privaten Rohbelege bleiben außerhalb des Repositorys.

Abschließende Projektprüfung: php tests/run.php einschließlich 219 JavaScript-Tests
erfolgreich, zusätzlich Struktur, Kodierung, StylePHP (18 eigene JSON-Dateien) und
php-cs-fixer im Prüfmodus über 152 PHP-Dateien bestanden. Der eigenständige
Browserdesigner wurde separat im Dunkelmodus geöffnet: zwei Systeme verfügbar,
keine native Themebindung und keine JavaScript-Fehler. Er bleibt neben der
nativen Kachel nutzbar.


## Kachel-SDK-Texttransport korrigiert – 06.11.2026

Die Praxis meldete an UpdateVisualizationValue einen Konvertierungsfehler für
Value. DesignerEmbedding hatte ein PHP-Array übergeben, das unsere lokale
SDK-Nachbildung ohne Typprüfung akzeptierte. Die PHP-Probe verlangt jetzt JSON-Text;
vor der Korrektur reproduziert sie den Fehler am öffentlichen RequestAction-Einstieg.
Auch die JavaScript-Gegenstelle liefert ausschließlich Text und zeigte vor der
Korrektur ausbleibende Status-/Zugangsrückmeldungen. Gemeinsam korrigiert:
json_encode am SDK-Ausgang und JSON.parse vor der bestehenden Client-/Anfrageprüfung.
Beschädigter Text und fremde Nachrichten verbrauchen keine ausstehende Anfrage.
Eine von Symcon abgelehnte Übergabe erzeugt eine verständliche Fehlermeldung im
kanonischen Debug; SENDEN wird erst nach angenommener Übergabe protokolliert.
Keine Tokenwerte und keine neuen MQTT-/Z2M-Aufträge.

Vorab VisualizationAssetHelper und IPSViewHTMLPageHelper geprüft: Der vorhandene
HTML-JSON-Encoder des IPSView-Helfers dient der Einbettung in Skripte und ist hier
nicht eingebunden. Für den reinen SDK-Texttransport genügt PHPs json_encode;
keine Helperdatei kopiert oder geändert. Echte Symcon-Kachelprüfung nach dem
korrigierten Modulupdate weiterhin offen; der bisherige Mock war dafür kein Beleg.

Nach der Korrektur: vollständige Projektprüfung einschließlich 214 JavaScript-Tests,
Struktur, Kodierung, StylePHP (18 eigene JSON-Dateien) und php-cs-fixer im Prüfmodus
bestanden. Separater lokaler Edge mit JSON-Text-SDK-Mock: acht Systemoptionen,
Designeröffnung, Zugangserneuerung mit offenen Eingaben, Hell/Dunkel, 320–1920 Pixel
und Mausradscrollen erfolgreich. Keine Installationsabfrage oder Live-Änderung.


## Native Kachel und gemeinsamer Browserzugang – 06.11.2026

Vor der Umsetzung VisualizationAssetHelper einschließlich HTTP-Origin-Ressource,
VisualizationInteractionScript, VisualizationThemeHelper und IPSViewHTMLPageHelper
geprüft. Die bereits unverändert eingebundenen Asset-/Origin-/Debug-Helper genügen
für die native HTML-SDK-Kachel; keine Helper-Testkopien oder Änderungen an Fremdquellen.
Die Palette bleibt bei den eigenen Designerressourcen. Kein eigener allgemeiner
HTML-SDK-/IPSView-Helper eingeführt, kein neuer MQTT- oder Z2M-Befehl.

Neue synthetische PHP-Nachweise in designer-access.inc.php und designer-tile.inc.php:
acht Systeme, unabhängige Auswahl, unveränderte Entwürfe und offene native Eingaben,
gezielter Browserstart, keine MQTT-Ausgabe, abgewiesene Fremdaktionen, identische
Rechte/Quittungen bei Verlängerung, 15-Minuten-Ablauf, harte Acht-Stunden-Grenze,
Originprüfung sowie Widerruf durch Modulupdate und Bindungswechsel.
Sechs zusätzliche JavaScript-Nachweise prüfen SDK-Korrelation, verspätete Antworten,
Popup-Sperre, feste Öffnungsadresse aus opakem Host und Eingabeerhalt beim Verlängern.
Native SDK-/Browserprüfungen verwenden ausschließlich eigene synthetische Daten.
Zusätzliche sichtbare Prüfung im separaten lokalen Edge mit opakem SDK-Mock:
acht Systemoptionen, gezielte Designeröffnung, Hell/Dunkel bei 320/360/760/1920
Pixeln ohne horizontalen Überlauf, neuer Link ohne Navigation offener Fenster sowie
manuelle und simulierte periodische Verlängerung mit offenem Regeldialog und
unübernommenem Namen. Keine JavaScript-Fehler und ausschließlich Status-/Zugangsaufrufe.
Alle Projektprüfungen einschließlich 213 JavaScript-Tests bestanden.
Die praktische Abnahme der Kachel in der echten Symcon-Testvisualisierung bleibt offen.

Den abgestimmten Oberflächenentwurf in den vorhandenen Designer integriert.
Librarylokale Komponentenstile verwenden eine semantische Palette für die
automatische Browser-Hell-/Dunkeldarstellung; Schriftgrößen skalieren mit rem
und begrenzter Browserbreite. Auslöser, Bedingungen und Aktionen erhalten
kräftige, konsistente Farben samt Symbolen und Beschriftungen. Tabellen,
Eingaben, Auswahlfelder, Fehlermeldungen und Dialoge sind ebenfalls berücksichtigt.

Vor Umsetzung VisualizationThemeHelper, VisualizationAssetHelper,
HttpResponseHelper und DebugFlowHelper geprüft. Die eigenständige
Designeranwendung verwendet keinen HTML-SDK-Host und keine konfigurierbaren
Kachelfarben. Ihre Komponentenpalette bleibt im vorhandenen librarylokalen CSS;
keine Helperdatei kopiert oder geändert. Assetfreigabe und nativer Debug
verwenden die bestehenden zentralen Verträge. Der Shell-Abruf protokolliert
die neue Darstellung mit bereinigter deutscher Meldung.

layout.js ordnet ausschließlich vorhandene DOM-Knoten samt ihren bisherigen
Controllern neu an: „Prüfen & übertragen“, „Betrieb“ und „Gerätedaten“ öffnen
je einen Bereich; Status-/Fehlerhinweise bleiben schon an den Bereichsbuttons
sichtbar. Verwaltung bündelt neue Aktionsfolge, Diagnose, Freigaben und Import.
Längere Rückmeldungen stehen unter dem Editor. Schmale Ansichten verwenden
native aufklappbare Listen-/Eigenschaftsbereiche; beim gewählten Baustein können
die Eigenschaften geöffnet werden. Der Bearbeitungszugang dort delegiert an
denselben bestehenden vollständigen Editor. Keine neue fachliche Schreibroute,
kein eigener Requestpfad, keine Änderung von Entwürfen, Freigaben oder Betrieb.

Vier Node-Regressionstests trennen unbekannte, laufende, erfolgreiche und
fehlerhafte Zustände ohne Mutation des Beobachtungsmodells. Die Asset-
Whitelistprüfung berücksichtigt layout.js einschließlich Besitzerkonkurrenz.
Der synthetische Browserlauf verwendet zwei aus dem lokalen Kernel-Fixture
gewonnene Ansichten und lange Beispiellisten. Kein Zugriff auf Symcon oder Z2M.
Ein separater unsichtbarer Edge-Prozess prüft:

- Helle/dunkle Darstellung nach Browserpräferenz und größere Schrift bei
  breiterem Browser; 320, 390, 760, 1024, 1366, 1920, 2560 und 3840 Pixel ohne
  horizontalen Seitenüberlauf. Keine bestehenden Browsertabs bedient.
- Anfangs geschlossene Systembereiche, Öffnen/Schließen ohne POST oder
  Befehlswiederholung, erhaltene Originalbuttons und zugängliches Verwaltungsmenü.
- Suche, aufklappbare Liste/Eigenschaften, bestehender Bearbeitungsdialog mit
  Vorbelegung und neuer Dialog auf schmalem Browser ohne horizontalen Überlauf.
- Sichtbarer Wechsel auf das zweite synthetische System mit dessen eigener
  Ansicht. Genau ein POST aus dieser ausdrücklichen Systemwahl; kein Store-,
  Aktivierungs- oder Schaltauftrag. Keine Browser-JavaScript-Fehler.
- Echte Mausradaktion über 64 Einträgen. Der erste Lauf zeigte, dass die native
  details-Inhaltsbox die Listenhöhe nicht begrenzte. Flexanordnung auch an
  ::details-content ergänzt; Wiederholung scrollt die Liste von 0 auf 700 Pixel.

Private lokale Prüfberichte und Bilder bleiben außerhalb des Repositorys.
Modul-README, Kontext und Prüfliste aktualisiert. Die reale Konsolen-/Designer-
Praxisprüfung nach Modulupdate bleibt getrennt offen. Keine Installation,
Aufzeichnung, Speicherung oder Geräteaktion an einem laufenden System;
Erweiterungsquellen und erzeugtes Artefakt unverändert.

Gesamtsuite `php tests/run.php` erfolgreich mit **207 JavaScript-Tests** und
allen PHP-/Modulprüfungen. Nach den abschließenden Grafik-/CSS-Anpassungen die
betroffenen Node-Fälle, Syntax und den vollständigen lokalen Browserlauf erneut
geprüft; einschließlich Hell-/Dunkelwechsel im geöffneten Bearbeitungsdialog
und Bearbeitungszugang direkt bei den Eigenschaften. Struktur, zentrale
Kodierungsprüfung, Erweiterungsartefakt und `git diff --check` erfolgreich.
Offizieller StylePHP-Check von 18 eigenen JSON-Dateien in isolierter Kopie und
PHP CS Fixer 3.95.27 für 148 PHP-Dateien im Prüfmodus ohne Befund. Alle 13
geänderten/neuen Dateien als UTF-8 ohne BOM/LF geprüft; Komponentenfarben nur
über semantische Tokens, Schriftgrößen relativ. Keine Commits oder Pushes.

## R3: Fehlenden S1-Vergleich praktisch ergänzt am 06.11.2026

Nach ausdrücklichem Auftrag genau eine erneute Erweiterungsinstallation auf
dem ersten System über den öffentlichen zentralen Moduleinstieg per Test-MCP
durchgeführt. Test-Symcon **9.1**, produktiv genutztes Z2M, eigener Build
`4d6cda174348`. Kein zusätzlicher nativer Klicknachweis und keine Bedienung
persönlicher Fenster; R2 bleibt der separate native Nachweis.

Beide Systeme zuerst mit Health-/Erweiterungsstatus frisch bestätigt und
ursprüngliche öffentliche Modulantworten privat als unveränderte Zeichenketten
gesichert. Auf S1 eine vorhandene Regel als Auswahl und eine nicht übernommene
Namenseingabe mit Umlauten im lokalen Arbeitsbereich vorbereitet. Dokument
und Partnerfreigaben unverändert, gemeinsame Systemauswahl bewusst auf S2
belassen. Ein erster lokaler Vorbereitungsaufruf wurde wegen des Formats seiner
Prüfkennung ohne Datenänderung oder MQTT abgelehnt; korrigierte Vorbereitung
vor der Installationsbaseline erfolgreich. Kein Installationswiederholungsversuch.

Zusammengehöriger Praxislauf am 06.11.2026, Uhrzeiten Europe/Berlin:

- 07:28:08 Uhr genau ein zentrales Installations-START/ENDE-Paar mit gleicher
  Instanz, gleichem Vorgang und Aufruf. Ein `extension/save` um 07:28:09 Uhr.
  Neue Empfängersitzung und erwarteten Build frisch bestätigt.
- Automatisches Nachladen um 07:28:10 Uhr gestartet. Genau ein Katalogauftrag
  um 07:28:11 Uhr mit einer passenden Antwort; Abschluss `loaded` um
  07:28:13 Uhr. Keine Wiederholung durch lesende Statusaufrufe.
- Beide lokalen Dokumente einschließlich Partnerfreigaben, Auswahlen und
  offener Arbeit in PHP streng mit der gesicherten Baseline verglichen;
  gemeinsame Systemauswahl ebenfalls identisch. Die absichtlich offene
  S1-Eingabe und nichtleere Regelauswahl sind dabei tatsächlich vorhanden.
- Gespeicherte/aktive Revisionen und Prüfsummen, Regelanzahlen, eingeschalteter
  Wiederanlauf, Meshbereitschaft und Automatikbetrieb beider Systeme gleich.
  Eine aktive Regel auf S1, zwei auf S2. Zweite Empfängersitzung unverändert,
  beide abschließenden lesenden Statusanfragen beantwortet, kein Laufzeitfehler.
- Nur die temporäre S1-Auswahl/Eingabe durch die gesicherten Originaldaten
  ersetzt, mit Schutz gegen zwischenzeitliche fremde Änderungen. Um
  07:29:17 Uhr beide ursprünglichen lokalen Datenobjekte unabhängig und exakt
  wiederhergestellt bestätigt. Keine YAML-Speicherung, Aktivierungs-/Stopp-
  oder Gerätebefehle. Drei instanzbezogene Test-Dateiaufzeichnungen beendet;
  Endmarken privat gesichert.

Der private Abschlussbeleg enthält Ausgangsstand, vorbereitete Baseline,
Nachzustand, endgültige Wiederherstellung, zugehörige Dateidebugs und geprüfte
Anfrage-/Antwortzuordnung. R3 ist damit im vereinbarten Umfang abgeschlossen;
Prüfliste, Belegmatrix, Agenda, Mehrsystemplan und Projektkontext aktualisiert.
Modul-/Erweiterungsquellen, Helper, Tests und Artefakt unverändert. R4/R5 bleiben
eigenständige Aufgaben und erhalten hierdurch keine operative Freigabe.

Lokale Abschlussprüfungen erfolgreich: `php tests/run.php` mit **203 JavaScript-Tests**
und allen PHP-/Modulprüfungen, Struktur-/Kodierungsprüfung und
`npm run check:extension`. Offizieller StylePHP-Check von 18 eigenen JSON-Dateien
in isolierter Kopie und PHP CS Fixer 3.95.27 für 148 PHP-Dateien im Prüfmodus
ohne Befund. Sieben Markdown-Dateien als UTF-8 ohne BOM/LF, geschlossene
Codeblöcke und 36 lokale Dateiverweise geprüft; geprüfte private Kennungen und
Ablagepfade nicht in den Ergänzungen enthalten. `git diff --check` sauber.
Keine Commits oder Pushes.

## R3: Vorhandene Installations-/Geräteabrufbelege zugeordnet am 06.11.2026

Vorhandene private Berichte, zugehörige Prüfskripte und archivierte Dateidebugs
nur lesend abgeglichen. Keine neue Installation, Status-/Geräteabfrage,
Debugaufzeichnung oder Betriebsänderung. Keine Bedienung einer persönlichen
Konsole und kein Zugriff über den Produktionsconnector.

Die frühen Browserinstallationsberichte belegen beide Systeme, liegen aber
vor der Implementierung des automatischen Nachladens. Die separaten manuellen
Katalogprüfungen belegen Entwurfs-/Betriebserhalt bei manuellen Abrufen; diese
Vergleiche werden nicht als derselbe automatische Installationslauf umgedeutet.

Für das erste System ist im archivierten Dateidebug vom 05.11.2026 die reale
Kette 15:48:47 Uhr ein Upload → 15:48:48 Uhr Installation und erwarteter Build
bestätigt → 15:48:49 Uhr automatisches Nachladen → 15:48:50 Uhr ein Katalogauftrag
mit passender Antwort → 15:48:51 Uhr Übernahme und erfolgreicher Abschluss
belegt. Zeiten Europe/Berlin. Auch zunächst trefferlose Archivseiten bis zum
Dateiende berücksichtigt; Aufträge und Antworten eindeutig zugeordnet.
Der S1-Abgleich bleibt privat einschließlich der zugehörigen Debugereignisse.

Für das zweite System deckt der kombinierte R2-Beleg vom 06.11.2026 diese Kette
vollständig ab: genau ein Upload und automatischer Geräteabruf, neue Sitzung,
erwarteter Build sowie unabhängiger Vergleich beider lokaler Arbeitsbereiche
und des ursprünglichen Betriebs. Keine zusätzliche Praxisprüfung nötig.

R3 bleibt ausschließlich für den unabhängigen Vergleich von Ausgangs- und
Endständen im selben S1-Installationslauf offen: Dokument mit Partnerfreigaben,
Auswahl, offene Arbeit, bestätigter gespeicherter/aktiver Stand und Betrieb,
einschließlich unverändertem anderen System. Die S1-Abschlussmeldungen nennen
den Erhalt, sind aber keine unabhängigen Datenvergleiche. Das ist eine Beleglücke,
kein nachgewiesener Datenverlust. Bei einem später freigegebenen S1-Update kann
dieser Teil mit erhoben werden; kein neuer Auftrag aus der Dokumentation.

Der [R3-Belegabgleich](INSTALLATION_CATALOG_EVIDENCE.md) enthält die gemeinsame
Matrix und den begrenzten Restumfang. Prüfliste, Agenda, Mehrsystemplan und
Projektkontext entsprechend eingegrenzt. Keine Modul-/Erweiterungsquellen,
Tests, Helper oder generierten Artefakte geändert.

Lokale Abschlussprüfung erfolgreich: `php tests/run.php` mit **203 JavaScript-Tests**
und allen PHP-/Modulprüfungen, separate Struktur-/Kodierungsprüfung und
`npm run check:extension`. Offizieller StylePHP-Check der 18 eigenen JSON-Dateien
in isolierter Kopie und PHP CS Fixer 3.95.27 im Prüfmodus für 148 PHP-Dateien
ohne Befund. Sieben Markdown-Dateien auf UTF-8 ohne BOM/LF, geschlossene
Codeblöcke und 36 lokale Dateiverweise geprüft; geprüfte private Adressen,
System-/Instanzkennungen und Ablagepfade nicht in den Ergänzungen enthalten.
`git diff --check` sauber. Keine Commits oder Pushes.

## R2: Nativer zweiter Installations-Klick praktisch bestätigt am 06.11.2026

Nach ausdrücklicher Praxisfreigabe den nativen Button des zweiten Systems an
Test-Symcon **9.1** im separaten unsichtbaren Edge-Testbrowser geprüft. Die
Testverbindung nutzt das produktive Z2M. Ausgangsdaten, Entwürfe und Betrieb
vorher privat gesichert; keine persönlichen Browsertabs oder sichtbare
Desktopkonsole bedient. Erwarteter eigener Erweiterungsbuild: `4d6cda174348`.

Ein erster Vorbereitungsdurchlauf endete nach Nein vor Ja, ohne Installation:
Der globale Debugpuffer verlor unter dem übrigen Nachrichtenaufkommen Einträge.
Dieser Durchlauf wird nicht als vollständige Abnahme geführt. Die Probe zählte
null Installations-RPCs und wurde wiederhergestellt; es gab keinen Upload, der
erneut versucht wurde. Für den positiven Nachweis Dateiaufzeichnung ausschließlich
der beteiligten Zentrale und Z2M-Verbindung aktiviert und vor der Bestätigung
den Abbruchteil samt Debug erneut geprüft.

Die vollständige Kette ist bestätigt:

- Native Formularöffnung positiv im Debug beobachtet. Nein: gültige Probe mit
  null Installations-RPCs/Aufrufstellen, null Kernel-Installationseinstiegen
  und null Uploads.
- Ja: genau eine RPC-Übergabe mit einer Installations-Aufrufstelle, leere
  Rückgabe und keine ausstehende/abgelehnte Antwort. Genau ein kanonisches
  START/ENDE-Paar für `RequestWorkspaceInstallation`; Instanz, Vorgang und
  Aufruf stimmen überein. Genau ein `extension/save`-Upload in der zugehörigen
  Verbindung; keine weitere native Bestätigung und kein automatischer Neuversand.
- Neue Empfängersitzung und erwarteten Build bestätigt. Gespeicherte und aktive
  Revision sowie Prüfsummen unverändert, zwei aktivierte Regeln wieder im
  Automatikbetrieb, eingeschalteter Wiederanlauf und Meshbereitschaft erhalten.
  Kein Laufzeitfehler. Der Installer lud anschließend genau einmal die
  Gerätedaten erfolgreich nach; dieser Teilbeleg wird für R3 erhalten.
- Statusbutton: genau eine zusätzliche Status-RPC, Installationszähler weiterhin
  eins. Dateidebug belegt null zusätzliche Installationseinstiege und Uploads.
- Beide lokalen Entwürfe einschließlich Auswahl und offener Arbeit exakt
  mit dem Ausgangsstand verglichen. Das erste System behält Sitzung, Revision,
  Prüfsummen und seinen aktiven Betrieb. Keine YAML-Speicherung, kein manueller
  Aktivierungs-/Stopp- oder Schaltbefehl.
- Probe auf die ursprüngliche Methode zurückgestellt, eigenen Testbrowser
  geschlossen und beide Test-Dateiaufzeichnungen sowie temporäre Debugweiterleitung
  beendet. Endmarken der Dateisitzungen im Beleg vorhanden.

Der kombinierte private Beleg führt UI-/RPC-Bericht, kanonische Dateidebugs,
frische Empfängermetadaten und exakten Entwurfsvergleich zusammen. Historische
Berichte, Vorbereitungsabbruch und positive Belegkette bleiben getrennt erhalten.
Private Adressen, IDs, Topics, Sessions, Prüfsummen und Rohbilder werden nicht
ins Repository übernommen. R2 ist im vereinbarten Umfang abgeschlossen;
dieser Nachweis ersetzt weder die Symcon-9.0-Abnahme noch die übrigen R3–R5-Fälle.
Keine Änderung an Modul-/Erweiterungsquellen oder erzeugtem Artefakt.

Abschließende lokale Projektprüfung erfolgreich: `php tests/run.php` mit
**203 JavaScript-Tests**, separate Struktur- und zentrale Kodierungsprüfung,
`npm run check:extension`, offizieller StylePHP-Check der 18 eigenen JSON-Dateien
in isolierter Kopie und PHP CS Fixer 3.95.27 im Prüfmodus ohne Befund für
148 PHP-Dateien. Acht geänderte Markdown-Dateien sind UTF-8 ohne BOM/LF,
geschlossene Codeblöcke und 32 lokale Dateiverweise geprüft; keine geprüften
privaten Installationskennungen in den Ergänzungen. `git diff --check` sauber.
Keine Commits oder Pushes.

## R2: Native Testinstrumentierung lokal korrigiert am 06.11.2026

Vorhandene private Einzelberichte, Screenshot, erhaltenes Prüfskript und
späteren Statusbericht nur lesend geprüft. Der zweite native Bericht scheiterte
am Zähler null statt eins, nachdem er die neue Prüfphase und den erfolgreichen
Installationsabschluss bereits abgewartet hatte. Auch der später bestandene
erste Bericht hat null gezählte Aufrufe; sein jüngerer Skriptstand lässt die
sichtbare Prüfphase als Ersatz zu. Keine exakte Einmaligkeit daraus ableiten
und keinen Modulfehler allein aus der fehlgeschlagenen Zählbedingung folgern.
Originalberichte und ursprüngliches Prüfskript bleiben unverändert außerhalb
des Repositorys; ein gesonderter privater Runner mit der neuen strikten Probe
ist vorbereitet und syntaktisch geprüft, nicht gegen Symcon ausgeführt.

Der vorhandene zwischengespeicherte Konsolenclient delegiert RPCs an einen
Worker. Die alte reine Seiten-POST-/WebSocket-Suche erfasst diese Weiterleitung
nicht zuverlässig. Mit echtem SharedWorker, synthetischem Loopback-RPC und
separatem unsichtbarem Edge-Prozess reproduziert: ein Serveraufruf, alter
Seitenzähler null. Keine bestehende Konsole oder persönliche Browsersitzung
bedient und keine Verbindung zu Symcon oder Z2M hergestellt.

`tests/support/native-console-probe.mjs` beobachtet deshalb den öffentlichen
`ips-proxy.makeRequest`-Einstieg vor der Weiterleitung. Sieben neue Node-Fälle
prüfen Argumente/Empfänger/Promise-Identität, leere Rückgabe, Fehler und Feedback,
Status getrennt, falsche Bindung, Text-/Kommentar-Falschpositive, mehrere echte
Aufrufe, Grenzen, Wiederherstellung und selbstständige Browserinjektion. Die
Probe liefert ausschließlich Zähler und feste Kategorien, keine Skripttexte,
Parameter, Antworten oder rohe Exceptions. Kein neuer Moduldebugkanal und kein
Laufzeithelper; vorhandene zentrale Debug-/Formularhelper bleiben unverändert.

Der optionale `tests/native-console-probe.browser.mjs` bestätigt echte
SharedWorker-Weiterleitung mit Nein null, Ja eins und Status ohne zusätzlichen
Installationsaufruf. Nach Wiederherstellung zählt die Probe nicht weiter. Der
Testserver führt weder PHP noch MQTT aus; Playwright wird über eine externe
Modul-URL bereitgestellt und nicht als Abhängigkeit aufgenommen.

`tests/workspace-installation.php` führt die erzeugten nativen Callbacktexte
auf zwei synthetischen Systemen aus: je ein kanonisches START/ENDE-Paar, genau
ein Upload und kein erneuter Einstieg durch den Statusbutton. Die Fixture
enthält absichtlich offene Eingaben; deren Schutz blockierte den anfänglichen
Versuch über die öffentliche Systemnavigation erwartungsgemäß. Nur die
Testvorbereitung wählt daher das Formular direkt im Fixture-Zustand, erhält
die Eingaben und stellt die Auswahl wieder her. Kein Produktcode geändert.

Der neue [begrenzte Prüfablauf](NATIVE_INSTALLATION_TEST.md) trennt RPC-Übergabe,
PHP-Debug, Upload und frische Empfängerbestätigung. R2 bleibt für diesen echten
zweiten Klicknachweis offen; eine neue Prüfphase ist keine Ersatzbedingung.
Keine Installation, Speicherung, Aktivierung oder Geräteaktion an Test/Live.

Prüfung: sieben neue Node-Tests und der separate SharedWorker-/Edge-Browserlauf
bestanden. `php tests/workspace-installation.php` nach Ergänzung des konkreten
Instanz-/Vorgangs-/Aufrufabgleichs erneut erfolgreich; die Gesamtsuite
`php tests/run.php` besteht mit **203 JavaScript-Tests**. Struktur, zentrale
Kodierungsprüfung und `npm run check:extension` erfolgreich; offizieller
StylePHP-Check der 18 eigenen JSON-Dateien ohne Fremdquellen sowie PHP CS Fixer
3.95.27 im Prüfmodus ohne Befund für 148 PHP-Dateien. Syntax des optionalen
Browserrunners und des vorbereiteten privaten nativen Runners geprüft; ersterer
wird in CI nur syntaktisch geprüft, nicht als Browserlauf gestartet. UTF-8 ohne
BOM/LF, lokale Dokumentlinks und `git diff --check` geprüft. Keine Commits/Pushes.

## R1: synthetischer Mesh-Nachweis mit acht Erweiterungen am 06.11.2026

Die gemeinsame Fixture `tests/fixtures/mesh-star.mjs` auf zwei bis acht
eindeutig benannte Systeme und sieben Sternkanten erweitert. Der bisherige
Drei-System-Aufbau bleibt der Standard; seine sechs Prüfungen bestehen weiter.
Fünf zusätzliche Fälle in `tests/mesh-eight.test.mjs` sind in `npm test` und
`php tests/run.php` eingebunden. Alle acht Instanzen verwenden das unveränderte
erzeugte Erweiterungsartefakt mit getrenntem temporärem Speicher und bestehenden
öffentlichen Protokoll-Einstiegen. Reguläre Antworten, Freigaben und
Ausführungsentscheidungen stammen aus der echten Erweiterung; nachgebildet
bleiben Broker, native Befehlsübergabe, Gerätebestand und Uhr. Fehlerfälle
verändern oder wiederholen ausdrücklich die zuvor erfassten Nachrichten.

Neue Nachweise:

- Alle **56 gerichteten Verbindungen** zwischen acht Systemen: pro Sender
  sieben unabhängige Regeln, gemischte Geräte-/Gruppenaktionen und auf allen
  Systemen gleiche Geräte-, Gruppen-, Quellen- und Regelkennungen. Jedes Ziel
  erhält genau seinen Befehl und typisierten Wert, der Sender keinen lokalen
  Ersatzbefehl. Trotz doppelter Execute-Zustellung keine Doppelaktion. Je
  56 Prepare-, Challenge-, Execute- und Result-Nachrichten sowie eindeutige
  Transaktionen und Tokens; jede Veröffentlichung durchläuft die sieben
  Sternkanten einmal. Die Senderwellen haben eine Sekunde Abstand, damit die
  vorhandenen Befehls-/Debuggrenzen eingehalten werden. Kein Belastungsnachweis
  für 56 gleichzeitig gestartete Regeln oder unbegrenztes Nachrichtenvolumen.
- Getrennte Empfangsfreigaben: Vier Ziele erlauben den Sender, drei lehnen
  ihn trotz anderer Freigaben ab. Nur ein Empfänger wird zusätzlich freigegeben,
  später nur einem anderen die Freigabe entzogen. Nur der jeweils geänderte
  Empfänger erhält eine neue Dokumentprüfsumme; andere Dokumente bleiben gleich.
  Jeder neue Versuch prüft frische Fehlermeldungen. Keine nachgeholten Befehle.
- Ausfall des achten Zweigs bei einem äußeren Sender: Sechs andere Ziele bleiben
  nutzbar, einschließlich des mittleren Systems. Der fehlende Zweig ist vor
  vier Sekunden noch offen und scheitert genau dann mit `mesh_timeout`. Ein
  weiterer Auslöser erreicht die sechs gesunden Ziele erneut. Nach Wiederkehr
  kein Nachholen; erst ein frischer Auslöser erreicht wieder alle sieben Ziele.
  Die Automatik bleibt auf allen acht Systemen aktiv.
- Sieben gleichzeitig offene Ergebnisantworten: Zirkulär vertauschte
  Transaktionskennungen mit fremdem Sender lösen keinen wartenden Lauf auf.
  Sechs echte Antworten schließen nur ihre Läufe ab, die siebte läuft ins
  Zeitlimit. Ihre verspätete Antwort ändert diesen Fehler nicht. Wiederholte
  und auf andere Empfänger umadressierte Execute-Nachrichten bewirken nichts.
- Alle sieben äußeren Systeme starten gleichzeitig je eine Regel zum nächsten
  äußeren System. Gleiche Regelkennung, getrennte Laufkennungen, genau eine
  native Übergabe je Ziel und korrekt zugeordnete Debugmeldungen. Die mittlere
  Erweiterung bleibt gestoppt und ohne Partnerfreigaben; ihr synthetischer
  Broker vermittelt trotzdem sämtliche Anfragen und Antworten. Keine direkte
  Verbindung zwischen äußeren Brokern erforderlich.

Die vorhandenen deutschen Meldungen und MQTT-Debugereignisse werden auf allen
Systemen geprüft: Laufkennung, Regelnummer, Revision, Schritt und bereinigte
Herkunft bleiben getrennt; fremde Erweiterungen berichten keinen anderen Lauf.
Keine Roh-Topics, Gerätekennungen, Regelnamen oder Aktionskörper im MQTT-Debug.
Der kanonische DebugFlowHelper und die vorhandene Erweiterungsdiagnose werden
nicht geändert. Keine neue Laufzeitlogik, Helperkopie oder Protokollvariante.

R1 ist damit im vereinbarten synthetischen Umfang abgeschlossen. Reale
Mosquitto-Bridges, ACLs und Warteschlangen mit drei bis acht Brokern, Gerätewirkung
und konkrete Z2M-/Symcon-Versionen sind durch diese Tests nicht abgenommen.
Kein Zugriff auf Test-/Live-Systeme und keine Änderung an laufenden Automationen.

Prüfung: Alle elf Drei-/Acht-System-Fälle und die vollständige Suite
`php tests/run.php` mit **196 JavaScript-Tests** unter Node 24.20.0 und
PHP 8.5.10 bestanden. Struktur, zentrale Kodierungsprüfung und
`npm run check:extension` erfolgreich. Offizieller StylePHP-Check der 18 eigenen
JSON-Dateien in isolierter Kopie bestanden; PHP CS Fixer 3.95.27 im Prüfmodus
ohne Befund für 148 Dateien. UTF-8 ohne BOM/LF, geschlossene Markdown-Codeblöcke,
49 lokale Dateiverweise und `git diff --check` geprüft. Keine Commits, Pushes,
Helperänderungen oder neuen Erweiterungsartefakte.

## R1: synthetischer Mesh-Nachweis mit drei Erweiterungen am 06.11.2026

Sechs neue Fälle in `tests/mesh-three.test.mjs`, angebunden an `npm test` und
`php tests/run.php`. Die Fixture `tests/fixtures/mesh-star.mjs` startet drei
echte Instanzen des erzeugten `libs/z2m/z2m-automations.mjs`, jeweils mit eigenem
temporärem Speicher, MQTT-Abonnement, Status, nativer Befehlsübergabe und
bestehender Diagnose. Speichern, Aktivieren und Debugstart verwenden den
regulären Erweiterungsprotokoll-Einstieg mit Sitzungs-/Revisionsbestätigung.
Alle Daten sind synthetisch; die temporären Verzeichnisse werden entfernt.

Nur der Brokertransport ist nachgebildet: zwei getrennt unterbrechbare Kanten
eines Sterns, Weiterleitung ausschließlich von `z2ma/mesh/#`, Hop-Protokoll
und gezieltes Zurückhalten einer Antwort. Challenge, Token, Aktionsfreigabe,
Befehlsübergabe, Laufstatus und Debug entstehen durch die echte Erweiterung.
Eine kontrollierte Uhr prüft den Timeout vor und genau bei vier Sekunden,
ohne reale Wartezeit oder Zugriff auf externe Broker.

Bestätigt sind:

- Zwei parallele Regeln A nach B und A nach C: getrennte Laufkennungen,
  Transaktionen und Tokens; gleiche Gruppen-ID und gleicher Gruppenname führen
  nur auf dem jeweiligen Ziel zu genau einer nativen Übergabe. Doppelte
  Execute-Nachrichten erzeugen keine zweite Aktion.
- Unterbrochener B-Zweig: nur sein Lauf scheitert mit `mesh_timeout`, C bleibt
  auch für einen neuen Lauf nutzbar. Wiederverbindung holt nichts nach; erst
  ein neuer Auslöser erreicht B. Die Automatik bleibt auf allen Systemen aktiv.
- Getrennte Freigaben: A/B und B/C erlauben nicht automatisch A/C. Entzug bei
  einem Empfänger sperrt nur die betreffende Richtung; die übrigen Freigaben
  bleiben nutzbar. Eine später erteilte Freigabe wiederholt keinen alten Lauf.
- Der Fehler eines Schritts bricht weiterhin die restlichen Aktionen desselben
  Laufs ab; eine unabhängige Regel zum gesunden Zweig läuft erfolgreich.
- Eine Antwort des falschen Systems beendet keine fremde Transaktion. Eine
  verspätete echte Antwort ändert den fehlgeschlagenen Status nicht; Tokens
  sind am anderen Ziel und nach Verwendung wirkungslos.
- S3 nach S4 läuft über den mittleren synthetischen Broker S1 einschließlich
  Rückantworten, auch bei gestoppter S1-Erweiterung ohne deren Partnerfreigaben.
  Die drei Broker brauchen keine zusätzliche direkte S3/S4-Verbindung.

Die vorhandenen deutschen Logmeldungen und MQTT-Debugereignisse werden auf
Zuordnung zu Lauf, Regel, Schritt, Revision und bereinigter Herkunft geprüft;
ein fehlgeschlagener Zweig erscheint nicht als Lauf des gesunden Empfängers.
Keine Roh-Topics, Gerätekennungen, Regelnamen oder Aktionskörper im MQTT-Debug.
Kein neuer Helper und keine Änderung an PHP-Modulen, Erweiterungsquellen oder
Artefakt. Die bisherigen Zweierprüfungen bleiben erhalten.

Grenzen: Der synthetische Transport ist keine Mosquitto-Implementierung.
Diese Prüfung bestätigt weder reale Bridges, ACLs, Warteschlangen und
Netzunterbrechungen noch Gerätewirkung oder eine konkrete Z2M-Version. Acht
Erweiterungen bleiben der nächste offene Teil von R1. Keine Test-/Live-Instanz
angesprochen, kein Erweiterungsupdate und keine reale Geräteaktion.

Prüfung: Alle sechs neuen Fälle und die vollständige `php tests/run.php`-Suite
mit **191 JavaScript-Tests** bestanden. Struktur, zentrale Kodierungsprüfung
und `npm run check:extension` erfolgreich. Offizieller StylePHP-JSON-Check
der 18 eigenen JSON-Dateien in isolierter Kopie ohne Fremdquellen bestanden;
PHP CS Fixer 3.95.27 im Prüfmodus unter PHP 8.5.10 ohne Befund für 148 Dateien.
Geänderte/neue Dateien als UTF-8 ohne BOM/LF und Markdown-Codeblöcke geprüft;
`git diff --check` sauber. Keine Commits, Pushes oder Installationsänderungen.

## Planungs- und Nachweisabgleich am 06.11.2026

Planung, Architektur, Modul-READMEs und Prüfliste anhand des aktuellen Codes,
der bisherigen Berichte und der im Chat bestätigten Praxis abgeglichen. Zusätzlich
lokale, nicht versionierte Prüfberichte eingesehen: beide Testsysteme für
Regelverwaltung, Geräteabruf, vollständigen Editor mit Drag & Drop, Ereignisimport,
Diagnose und Browser-Betriebssteuerung bestätigt. Die Browserinstallation ist
ebenfalls für beide Systeme belegt. Private Berichte und Installationsdaten
werden nicht ins Repository übernommen.

Partnerfreigaben am 05.11.2026 im tatsächlichen Testdesigner auf beiden Systemen
geprüft: Richtung, betroffene lokale Regeln, Vorschau, Zurück, Abbruch/Escape,
Entzug und erneute Freigabe. Native deutsche Debugmeldungen nennen die reine
lokale Änderung und die Anzahlen. Nach jedem Versuch Originaldokument inklusive
YAML, Basis, Auswahl und Arbeit exakt zurückgespielt; abschließend stimmte der
gesamte Workspace-Fingerabdruck mit dem Ausgangsstand überein. Keine Speicherung,
Aktivierung oder Schaltung in Z2M. Die Einzahlkorrektur wurde am 06.11.2026 nach
Modulupdate rein lesend im echten Designer bestätigt; null Freigabe-POSTs und
unveränderter Workspace. Diese Prüfungen nicht als weiterhin offen führen.

Verbleibende Lücken sind in [PRUEFLISTE.md](PRUEFLISTE.md) einzeln abgegrenzt.
Insbesondere ist ein anfänglich fehlgeschlagener Browser-Betriebstest durch
einen später bestandenen Bericht ersetzt. Beim nativen Installationsweg auf
dem zweiten System fehlt hingegen der eindeutig erfasste Bestätigungs-Callback;
der spätere bestandene Statusbericht ist kein Ersatz für genau diesen Klick.
Die reale Kette aus Installation und automatischem Katalogabruf ist noch als
zusammenhängender Nachweis zuzuordnen. Daraus keine unbelegten Produktfehler
und keine pauschale Neuprüfung bereits bestandener Abläufe ableiten.

Die neue Benutzeranleitung für Mosquitto übernimmt die bestätigte Zweibroker-
Schrittfolge mit anonymisierten Beispielen: erst lesen und sichern, eigene
Bridge-Benutzer, Z2M- und Bridge-ACLs, Neuladen, genau eine Bridge, Neustart nur
des betroffenen Brokers und beidseitiger Diagnoseempfang als Z2M-Benutzer.
Der Ausbau auf acht Broker ist eine Sternstruktur mit sieben Verbindungen,
noch kein neuer Praxisnachweis. Syntax und Versionsgrenzen anhand offizieller
Mosquitto-Quellen geprüft; keine fremden Implementierungen übernommen. Für reine
Dokumentation ist kein Helperumbau erforderlich; vorhandene MQTT-/Mesh-Verträge
und kanonische Helper bleiben unverändert.

Prüfung dieser Dokumentationspflege: Gesamtsuite php tests/run.php einschließlich
185 JavaScript-Tests erfolgreich. Struktur, zentrale Kodierungsprüfung und
npm run check:extension bestanden; unverändertes Erweiterungsartefakt.
Offizieller StylePHP-Check der 18 eigenen JSON-Dateien in isolierter Kopie ohne
Fremdquellen erfolgreich, PHP CS Fixer im Prüfmodus ohne Befund für 148 Dateien.
14 geänderte Markdown-Dateien sind UTF-8 ohne BOM/LF; 119 interne Links/Anker,
geschlossene Codeblöcke, sieben eindeutige Sternverbindungen und Paar-Kennungen
geprüft. Alle 41 Bash-Blöcke mit bash -n ausschließlich geparst, nicht ausgeführt.
git diff --check sauber. Keine neue Broker- oder Hardwareabnahme daraus ableiten.

Diese Dokumentationspflege greift weder auf Test-/Live-Installationen zu noch
führt sie die beschriebenen Linux-Befehle aus. Die folgenden Entwicklungsberichte
sind historische Einzelstände; aktuelle Umsetzung und Restabnahme stehen in
PRUEFLISTE.md.

## Partnerfreigaben im Designer lokal geprüft am 05.11.2026

WorkspacePermissions und permissions.js ergänzen einen ausdrücklich bestätigten
Bearbeitungsweg für gerichtete Empfangsfreigaben. Vor Umsetzung zentrale Debug-
und HTTP-Helper, Workspace- und Eigentumsverträge sowie vorhandene Designer-
Bestätigungen geprüft. Der erste HTTP-Test reproduzierte die fehlende Route mit
404. Zentrale Helper, Fremdquellen und Erweiterungsartefakt bleiben unverändert.
ADR 0031 beschreibt Zuständigkeiten und Grenzen.

Der reale Moduleinstieg mit Kernelstubs und acht synthetischen Systemen an zwei
MQTT-Eltern prüft exakte YAML-Erhaltung bei gleicher Auswahl, gerichtete Ziele,
getrennte Dokumente, Basisrevision, Auswahl und Arbeit, unzugeordnete bestehende
Freigaben, expliziten Entzug, ausgeschaltete Folgenbenutzer und begrenzte Ausgabe
bei je 100 Regeln. Veraltete Vorschau, Generation und MQTT-Zuordnung, offene
Eingaben, fehlendes Dokument, Origin, Methode, eigene Berechtigung und schmale
Payload werden geprüft. Wiederholung verwendet dieselbe Quittung; ein anderer
Auftrag unter derselben Kennung wird abgelehnt. Alle Fälle senden null MQTT.

Sieben JavaScript-Regressionstests prüfen Auswahl, Antwortgrenzen, gerichtete
Bestätigung, Abbruch, Herkunftsbezug, fehlende Berechtigung, späte fremde Antwort
und ausdrückliche Wiederholung nach verlorener Rückmeldung ohne Neuversand.
Ein separater unsichtbarer Edge-Prozess lädt die echten Assets über eine lokale
synthetische HTTP-Domain mit isSecureContext=false und ohne randomUUID. Auswahl,
Empfängeranzeige, sichere Textausgabe, Bestätigung mit 100 abhängigen Regeln,
Erhalt unbekannter Freigaben, Zurück, Abbruch und Escape bestehen. Nach verlorener
erfolgreicher Antwort bestätigt der Anwender denselben Auftrag erneut: zwei
POST-Versuche, genau ein Commit. Ein verspäteter GET nach Systemwechsel bleibt
geschlossen. 600/900 Pixel passen in die Breite, echtes Mausrad funktioniert
in beiden Richtungen. Keine JavaScriptfehler; Skript und Bilder nur temporär.

Gesamtsuite php tests/run.php mit 185 JavaScript-Tests erfolgreich; die neuen
Modul-/HTTP-Prüfungen nach den letzten Debugergänzungen erneut bestanden.
Struktur, zentrale Kodierungsprüfung, npm run check:extension und git diff --check
erfolgreich. Offizieller StylePHP-Check der 18 eigenen JSON-Dateien in isolierter
Kopie ohne Fremdquellen bestanden; PHP CS Fixer im Prüfmodus ohne Befund für
148 Dateien. Alle 22 eigenen geänderten Dateien sind UTF-8 ohne BOM und LF.

Keine Test-/Produktivinstallation kontaktiert oder verändert. Nach Modulupdate
den Designer erneut öffnen; reale Praxisprüfung bleibt ein gesonderter Auftrag.
Kein Update der Z2M-Erweiterung nötig.

## Designer-UUIDs über HTTP lokal geprüft am 05.11.2026

Der Systemwechsel scheiterte vor dem POST, wenn randomUUID auf einer HTTP-LAN-
Adresse fehlt. Ein neuer Regressionstest ohne diese Methode reproduzierte genau
„crypto.randomUUID is not a function“. Vor Umsetzung zentrale Helper und vorhandene
Browserkennungen geprüft: requestIdentity im Creator nutzte bereits getRandomValues.
Die Implementierung liegt nun gemeinsam in identity.js; der bisherige Export bleibt
als Alias erhalten. Alle bisherigen randomUUID-Aufrufer einschließlich Vorschau
verwenden denselben Weg. Keine Math.random-, Zeitstempel- oder schwachen Ersatzwerte.

Die neuen Prüfungen decken UUID-Version/Variante und bekannte Bitmuster, frische
Zufallswerte, fehlende/abgelehnte Browserzufallswerte mit bereinigter deutscher
Meldung, beide Wechselrichtungen und drei öffentliche Auftragswege ohne explizite
Kennung ab. Die PHP-Designerprüfung lädt auch das neue freigegebene Asset.

Ein separater unsichtbarer Edge-Prozess verwendet eine ausschließlich lokale,
synthetische HTTP-Testdomain. Dort sind isSecureContext=false und randomUUID
tatsächlich nicht verfügbar; es wird keine Browser-API künstlich freigeschaltet.
Sichtbarer Quellenwechsel in beide Richtungen, gültige unterschiedliche Kennungen
für Katalog/Prüfung/Betriebsstatus und Sperre bei offenem Dialog bestehen. Die lokale
Vorschau legt über dieselbe HTTP-Adresse eine Beispielregel an. Keine JavaScriptfehler;
die Systemauswahl passt bei 900 Pixeln in die sichtbare Breite. Keine Live-Zugriffe,
Gerätebefehle oder Arbeit im persönlichen Browserprofil für die Korrektur.

Gesamtsuite php tests/run.php einschließlich 178 JavaScript-Tests erfolgreich;
die fokussierten Identitäts-, Bearbeitungs- und HTTP-Grenzprüfungen bestehen.
Struktur- und zentrale Kodierungsprüfung sowie npm run check:extension bestanden.
Offizieller StylePHP-Check der 18 eigenen JSON-Dateien in isolierter Kopie ohne
Fremdquellen erfolgreich; PHP CS Fixer im Prüfmodus ohne Befund für 146 Dateien.
Eigene Änderungen UTF-8 ohne BOM/LF; git diff --check sauber. Reale Abnahme nach
Modulupdate und erneutem Öffnen des Designers separat; Live-System unverändert.

## Automatischer Geräteabruf nach Installation lokal geprüft am 05.11.2026

WorkspaceInstallationDevices ergänzt den bestehenden zentralen Installer um
einen getrennten Folgeschritt über WorkspaceCatalogRefresh. Vor Umsetzung die
kanonischen ConfigurationFormHelper-/DebugFlowHelper-Verträge und vorhandenen
Installations-/Katalogabläufe geprüft. Ein neuer Nachweis genau eines automatischen
Abrufs scheiterte zunächst am fehlenden Folgeschritt. Quellen der Erweiterung,
Artefakt, zentrale Helper und Fremdquellen bleiben unverändert.

Der synthetische Kerneltest verwendet acht Systeme und echte Modul-Einstiege.
Er prüft bestätigte Sitzung/Build vor dem Abruf, GET ohne MQTT, genau einen Abruf,
Mitbenutzung eines laufenden Abrufs, Wiederholungsquittungen, verzögerte Antwort,
Ablehnung, ungültige Daten, Sitzungstausch, Zeitüberschreitung und Modulupdate.
Entwurf, Partnerfreigaben, Auswahl und zwischenzeitliche Arbeit bleiben gleich;
es gibt keinen zusätzlichen Speicher-, Aktivierungs- oder Gerätebefehl. Ein
Gerätefehler lässt die Installation bestätigt und löst keinen zweiten Upload aus.
Native Formularbezüge werden nur bei unverändertem Dokument erneuert; kein
Neuaufbau offener Eingaben. Bestehende Installations- und Geräteabrufprüfungen
bleiben Teil der Gesamtsuite.

Ein eigener unsichtbarer Edge-Prozess lädt die echten Diagnose-/Installationsassets
von einem lokalen synthetischen Server. Auf zwei Systemen sind Ladezustand,
weiterlaufende Statusabfragen, gesperrter zweiter Upload, Geräteanzahlen und
getrennte Gerätefehler sichtbar geprüft. Abbruch/Escape ohne POST, Aktualisierung
ohne Neuversand und Neuladen nach Abschluss bestehen. 900-Pixel-Diagnose und
600-Pixel-Bestätigung passen in die Breite; echtes Mausrad funktioniert im kleinen
Dialog in beiden Richtungen. Keine JavaScriptfehler, kein Desktop oder persönliches
Browserprofil bedient. Testskript und Bilder liegen ausschließlich temporär.

Gesamtsuite php tests/run.php einschließlich 174 JavaScript-Tests bestanden.
Fokussierte Installations-/Katalog-/Diagnoseprüfungen, Strukturprüfung, zentrale
Kodierungsprüfung und npm run check:extension erfolgreich. Offizieller StylePHP-
Check der 18 eigenen JSON-Dateien in isolierter Kopie ohne Fremdquellen bestanden;
PHP CS Fixer im Prüfmodus ohne Befund für 146 Dateien. Eigene Änderungen sind
UTF-8 ohne BOM/LF; git diff --check sauber.

Keine Test-/Produktivinstallation kontaktiert oder verändert. Die reale Abnahme
nach Modulupdate bleibt separat; keine neue Z2M-Version als geprüft eingetragen.

## Erweiterungsinstallation je System lokal geprüft am 05.11.2026

WorkspaceInstallation, ConnectionInstallation und der native Formularbereich
ergänzen den bestehenden Installer. Vor Umsetzung kanonischen DebugFlowHelper,
ConfigurationFormHelper und HttpResponseHelper sowie ExtensionTransport,
ConnectionWorkflow und die vorhandenen Besitz-/Workspace-Verträge geprüft.
Der erste fokussierte Test scheiterte ausdrücklich an den fehlenden öffentlichen
Installationseinstiegen. Zentrale Helper, Fremdquellen und Erweiterungsartefakt
bleiben unverändert. ADR 0030 dokumentiert die Zuständigkeiten und Grenzen.

Der Kerneltest verwendet acht synthetische Systeme an zwei MQTT-Eltern. Er prüft
Erstinstallation vor dem Besitzerwechsel, ausdrückliche Bestätigung, frische
Health-Prüfung und exakt das mitgelieferte Artefakt am festen extension/save-Topic.
Neue Sitzung und erwarteter Build sowie bekannte gespeicherte Revision/Prüfsumme
bestätigen den Abschluss. Falsche Sitzung/Build, veränderter Speicher, Ablehnung,
abweichende Dateibytes bei gleicher Buildkennung, fehlende Antwort und abgelaufene
Vorprüfung sind getrennte Gegenbeispiele. Andere Dokumente, offene Eingaben,
Empfangsfreigaben und YAML-Dateien bleiben bytegleich; es gibt keinen Start-,
Stopp- oder Gerätebefehl. Ein laufender Auftrag eines Systems blockiert die
weiteren Systeme nicht.

Dauerhafte Quittungen verhindern Neuversand und veränderte Wiederholungsaufträge.
Modulupdate und verlorener Transportworkflow setzen keinen Upload erneut fort.
Der reine Authority-Callback besteht auch mit simuliertem separatem Kernelaufruf
bei bereits gehaltenem Besitzerlock. Ein möglicher Upload sperrt den rein lokalen
Übernahmerückweg. Manager-ApplyChanges muss deshalb seine eigene, weiterhin
besitzergeprüfte Aktualisierung durchführen dürfen. Native echte Buttoncallbacks
und der bestehende geschützte Neuladeweg werden gegen die Kernelstubs ausgeführt;
eine noch nicht übernommene Namenseingabe bleibt beim Neuaufbau erhalten.

HTTP-Prüfungen decken eigene Browserberechtigung, Bestätigung, feste Parameter,
Fremdherkunft, Generation und GET-/POST-Trennung ab. Browser-Unitprüfungen sichern
feste Routen, Systembindung, Größenbudget, Fehler und fehlenden automatischen
Neuversand. Ein separater unsichtbarer Edge-Prozess lädt die tatsächlichen
Diagnose-/Installationsassets von einem lokalen synthetischen Server: acht
Systeme in der Übersicht, Installation auf zwei Systemen, Abbruch per Button und
Escape ohne POST, ausstehender Auftrag, neue Buildanzeige, Fehlererhalt und
Neuladen der Hauptansicht nach Erfolg. Namen mit HTML werden als Text angezeigt.
900-Pixel-Diagnose und 600-Pixel-Bestätigung passen in die sichtbare Breite;
echtes Mausrad scrollt den kleinen Bestätigungsdialog in beide Richtungen.
Keine JavaScriptfehler. Kein persönliches Browserprofil oder Desktop bedient.

Gesamtsuite php tests/run.php einschließlich 173 JavaScript-Tests bestanden.
Fokussierte Installations-, Diagnose- und Verbindungstests sowie Struktur- und
zentrale Kodierungsprüfung erfolgreich. Offizieller StylePHP-Check der 18 eigenen
JSON-Dateien in isolierter Kopie ohne Fremdquellen erfolgreich; PHP CS Fixer im
Prüfmodus ohne Befund für 144 Dateien. npm run check:extension bestätigt das
unveränderte Artefakt. Eigene Änderungen UTF-8 ohne BOM/LF; git diff --check sauber.

Keine Test-/Produktivinstallation kontaktiert oder verändert. Die sichtbare
Abnahme mit echter nativer Testkonsole und Z2M erfolgt nach Modulupdate separat;
die Liste tatsächlich geprüfter Z2M-Versionen wird nicht erweitert.

## Systeme und Diagnose lokal geprüft am 05.11.2026

Agenda Punkt 5 ergänzt WorkspaceDiagnostics sowie die feste diagnostics-
Browserberechtigung und die GET-/POST-Routen. Der erste fokussierte Kerneltest
scheiterte ausdrücklich an den noch fehlenden öffentlichen Diagnoseeinstiegen.
GetConnectionDiagnostics verwendet den vorhandenen ExtensionBuildInfo-Vergleich
und eine bereinigte Ansicht der RuntimeDebug-Lease. Health, Status, Start und
Stopp verwenden die bisherigen Verbindungseinstiege. Es gibt keine zweite
MQTT-Verbindung, Laufzeitsteuerung oder Speicherung von Ablaufmeldungen.

Vor Umsetzung DebugFlowHelper, HttpResponseHelper, VisualizationAssetHelper
und die vorhandenen WorkspaceCatalog-/Runtime-Verträge geprüft. Zentrale
Helper und Erweiterungsquellen bleiben unverändert. Native Konsole und Browser
bedienen dieselbe begrenzte Aufzeichnung je Z2MConnection. Die Meldungen bleiben
im nativen Debugkanal; der Designer zeigt Status, absolute Endzeit und Instanz.

php tests/workspace-diagnostics.php prüft acht Systeme an zwei Brokern mit
dem echten PHP-Verbindungs- und MQTT-Datenfluss gegen synthetische Peers.
Öffnen/Nachladen bleibt ohne MQTT; nur ausdrücklich angeforderte Health-,
Status- und Debugbefehle werden versendet. Bestätigung, fehlende Antwort,
ausdrückliche Ablehnung, verspäteter Start nach Stopp, Ablauf und Generation-
bzw. Verbindungswechsel sind abgedeckt. Quittungen verhindern Doppelversand
und Verlängerung; ein Zentralenupdate erhält die ursprüngliche Endzeit. Stopp
während einer Statusprüfung lässt deren Antwort und die zweite Aufzeichnung
unverändert. Passender, abweichender, alter und unbestätigter Build werden
unterschieden. Alle Dokumente und offenen nativen Eingaben bleiben bytegleich.

Die HTTP-Prüfung deckt eigene Berechtigung, feste Aktionstypen, Größenbudget,
veränderte Generationen, beliebige Parameter/Topics und wiederholte Aufträge
ab. Der Browservertrag prüft zusätzlich fremde oder übergroße Antworten,
absolute Endzeit und unverändertes Anfragemodell. Installationshinweise führen
aus der zentralen Ansicht ausdrücklich nicht zum gesperrten Uploadbutton einer
übernommenen Verbindung; der zentrale Installationsablauf ist kein Teil dieses
Diagnoseschritts und bleibt gesondert offen.

Ein separater unsichtbarer Edge-Prozess lädt die echten Designerassets gegen
einen lokalen synthetischen Server. Acht Systeme, sichere wörtliche HTML-Namen,
Systemwechsel über den bestehenden Auswahlweg, Updatehinweis, sichtbare
Prüfphasen sowie Aufzeichnung mit unveränderter Endzeit und gezieltem Stopp
während Statusprüfung bestehen. Schließen lässt die begrenzte Aufzeichnung
laufen; erneutes Öffnen und lesender Abruf zeigen sie bis zum Ablauf. Echtes
Mausrad scrollt Systemtabelle und Dialog jeweils in beide Richtungen. Ansichten
mit 900 und 600 Pixeln Breite haben keine abgeschnittenen Bedienelemente.
Keine JavaScriptfehler; POSTs ausschließlich für Diagnose und Systemwahl.

Prüfnachweis: php tests/run.php erfolgreich einschließlich 170 JavaScript-Tests
und synthetischer Modul-/Erweiterungsintegration. Nach dem abschließenden
Fehlertext-/Ansichtsabgleich betroffene PHP- und Browsertests erneut bestanden.
Struktur und zentrale Kodierungsprüfung grün. Offizieller StylePHP-Check für
18 eigene JSON-Dateien in isolierter Kopie und PHP CS Fixer 3.95.27 im Prüfmodus
für 139 Dateien ohne Befund. Eigene Änderungen UTF-8 ohne BOM mit LF;
Fremdquellen und Z2M-Erweiterungsartefakt unverändert.

Keine Test- oder Live-Installation angesprochen, keine Geräte geschaltet und
keine Automationen gespeichert oder aktiviert. Reale Abnahme nach dem
Library-Modulupdate bleibt offen. Kein Z2M-Erweiterungsupdate erforderlich.

## Zentraler Ereignisimport lokal geprüft am 05.11.2026

Agenda Punkt 4 integriert den vorhandenen SymconEventImport-Konverter in
WorkspaceEditorContext und den Browserdesigner. Der erste neue Kerneltest
scheiterte am nicht erlaubten zentralen Scan-Aufruf. Danach wurden die
systemgebundenen Speicher-, Identitäts- und Formularadapter ergänzt. Der
native Importweg und die feste event-import-Browserroute verwenden dieselben
Vorschau- und Übernahmeprüfungen. Im Browser wurde zuerst der fehlende Client
mit Vertragstests nachgewiesen, danach die aktive Oberfläche angeschlossen.

Vor der Umsetzung zentrale Helper und vergleichbare Projektpfade geprüft:
ConfigurationFormHelper, DebugFlowHelper, HttpResponseHelper und
VisualizationAssetHelper bleiben unverändert eingebunden. Fachlicher
Konverter, systemgebundene Identität und HTTP-Verträge bleiben librarylokal.
Z2MInstanceIdentity, NativeVariableBindings und CommitEditorChange werden
weiterverwendet. Keine neue MQTT-Anbindung oder parallele Ausführungslogik.

php tests/workspace-ui.php --import-only prüft acht Systeme an zwei Brokern
mit identischen Geräte-/Gruppenkennungen: exakte Themenzuordnung, native
Callbackskripte, namenlose Ereignisse, lesbare Sensorauslöser, lokale und
freigegebene entfernte Gruppenziele am selben und am zweiten Broker. Ein
identisches Topic auf einem fremden Broker wird ausgeschlossen.
Originalereignisse und andere Dokumente bleiben
unverändert, kein Import sendet MQTT. Geänderte Ereignisse, eigene Skripte,
fehlende Metadaten, entzogene Rechte, doppelte Importe, offene Formulareingaben,
fehlende Sitzungsrechte, Generationen, fremde Requestinhalte und übergroße
Bodies sind abgedeckt. Quittungen verhindern doppelte Übernahmen. 4200
synthetische Ereignisse ergeben 256 sichtbare Treffer mit Grenzhinweis;
Antwort und natives Formular bleiben innerhalb ihrer Ausgabebudgets.

Die bestehende PHP-/JavaScript-Integration bestätigt weiterhin den bisherigen
Einzelimport. Ihr erster Lauf deckte auf, dass das Verwerfen jeder Vorschau
nach einer reinen YAML-Abweichung einen erlaubten erneuten Versuch sperrte.
Jetzt wird nur eine tatsächlich geänderte Ereignis-/Zuordnungsfreigabe
verworfen; ein korrigierter Entwurfsparameter darf dieselbe gültige Vorschau
weiter verwenden. Der gezielte Integrationslauf besteht anschließend.

Ein separater unsichtbarer Edge-Prozess lädt die echten Designerassets gegen
einen rein lokalen synthetischen Server mit zwei Systemen. Öffnen ohne Scan,
explizite Prüfung, sichtbare Geräte-/Ereignis-IDs, Suche und Importierbarfilter,
Vorschau und konkrete Ablehnung, sichere Textanzeige eines HTML-Namens,
Schließen ohne Import und Erhalt der Vorschau sind bestätigt. Eine ausdrückliche
Übernahme erzeugt genau eine ausgeschaltete lokale Regel und lässt das zweite
System sowie die Originalereignisse unverändert. Liste und Dialog scrollen
mit echtem Mausrad in beide Richtungen; die 900-Pixel-Ansicht ist geprüft.
Es wurden ausschließlich die vorgesehenen Import- und Systemwahl-POSTs gesendet.

Prüfnachweis: Gesamtsuite php tests/run.php erfolgreich einschließlich 166
JavaScript-Tests und tatsächlichem synthetischem Erweiterungs-Peer. Native
Importcallbacks und HTTP-Grenzen zusätzlich mit --import-only geprüft. Nach
Ergänzung der gesperrten Importhinweise und Assetfreigabe betroffene Kernel-
und Browsertests erneut bestanden. Struktur und zentrale Kodierungsprüfung
grün; offizieller StylePHP-Check aller 18 eigenen JSON-Dateien in isolierter
Kopie und PHP CS Fixer 3.95.27 im Prüfmodus für 137 Dateien ohne Befund.
Fremdquellen und Z2M-Artefakt unverändert; eigene Änderungen UTF-8 ohne BOM/LF.

Keine Test-/Live-Installation angesprochen, kein Originalereignis verändert,
keine Automation übertragen oder aktiviert und kein Gerät geschaltet. Reale
Abnahme nach Modulupdate bleibt offen; ein Erweiterungsupdate ist nicht nötig.

## Gemeinsamer Browsereditor lokal geprüft am 05.11.2026

Agenda Punkt 3 führt Erstellen und Bearbeiten in editor.js zusammen. Die
vollständige create-Regel verwendet dieselben Abschnittsprüfungen wie edit.
Der erste neue PHP-Test scheiterte am fehlenden vollständigen Erstellformat;
nach dessen Implementierung bestanden Erstellung und anschließende Vorbelegung.
DesignerRuleFields prüft Rechte, Listen, Zeiten, Bedingungen und Aktionen vor
dem einzigen Commit. Serverkennung und enabled=false sind nicht vom Browser
setzbar. Passende zentrale Helper vorab geprüft: vorhandene DebugFlowHelper-,
HTTP- und Assetpfade bleiben unverändert; die eigene Editor- und Drag-Logik
wird nur von dieser Library benötigt und bleibt deshalb librarylokal.

Tests prüfen vollständige gemischte Auslöser, Bedingungen, Farbbefehle mit
Nullwert, Szenen, Folgen und Verzögerungen, unveränderte Partnerfreigaben,
Ablehnungen ohne Teilcommit, entzogene native Rechte, doppelte Uhrzeiten,
Listenbegrenzung, veraltete Kontexte und Wiederholungen. Der zentrale
Workspace-Test erstellt eine vollständige ausgeschaltete Regel in System B
und vergleicht System A bytegenau unverändert. Alle Aufträge bleiben ohne MQTT.

Reine Node-Tests prüfen komplette typisierte Requests und das Verschieben
ganzer Schritte, doppelte Schritte, Ablageposition, ungültige Indizes sowie
fremde, abgebrochene, gesperrte und veraltete Drag-Vorgänge. Ein separater
unsichtbarer Edge-Prozess lädt die echten Designerassets gegen einen lokalen
synthetischen Server: Gerätesuche, Kanal/Wert, mehrere Geräte-/Zeit-/Astro-
Auslöser, Bedingungen und Aktionen im ersten Erstellvorgang, Fehlerrückmeldung
mit erhaltenen Eingaben, erfolgreiche Übernahme und Vorbelegung. Echte
Mausbewegungen verschieben Aktionen und Folgendefinitionen; Pfeilbuttons,
Abbrechen ohne POST, exakte Wartezeiten und Systemheader sind bestätigt.
Mausrad scrollt den langen Dialog in beide Richtungen; 900-Pixel-Ansicht
auf sichtbare Breite geprüft. Keine Fehler im Browser. Der synthetische Server
prüft die Oberfläche; die separaten PHP-Kerneltests prüfen die echte Übernahme.

Prüfnachweis: php tests/run.php erfolgreich einschließlich 161 JavaScript-
Tests und PHP-/JavaScript-Integration. Nach dem letzten Abgleich der deutschen
Debugtexte Designer-Kerneltests erneut bestanden. Struktur, zentrale
Kodierungsprüfung, offizieller StylePHP-Check aller 18 eigenen JSON-Dateien
in isolierter Kopie und PHP CS Fixer 3.95.27 im Prüfmodus für 134 Dateien grün.
Erweiterungsartefakt aktuell; eigene geänderte Dateien ohne BOM und mit LF.
git diff --check ohne Befund.

Keine Test-/Live-Installation angesprochen, keine Automationen übertragen oder
aktiviert und kein Gerät geschaltet. Praxisabnahme nach Modulupdate bleibt
offen. Erweiterungsartefakt und Fremdquellen bleiben unverändert.

## Gerätedaten im Browserdesigner lokal geprüft am 05.11.2026

Agenda Punkt 2 ergänzt WorkspaceCatalogRefresh und die festen catalog-refresh-
und catalog-status-Routen. Neue Sitzungen erhalten eine eigene catalog-Freigabe.
Health- und Katalogablauf von Z2MConnection bleiben unverändert; die bestätigte
Übernahme verwendet RefreshWorkspaceCatalog samt aktueller nativer Bindungen.
Der flüchtige Jobbuffer hält höchstens acht Systeme und 32 Quittungen. Nach
Modulupdate werden Aufträge nicht wiederholt. GET und Designerstart lesen nur.

Vor der Umsetzung passende Helper geprüft: DebugFlowHelper bleibt kanonisch;
HttpResponseHelper und VisualizationAssetHelper werden über die vorhandenen
Pfadgrenzen weiterverwendet. PersistentJsonCacheHelper ist für den bewusst
flüchtigen Auftragszustand kein Ersatz für Systembindung und Korrelation.
Keine Helperdateien oder Erweiterungsquellen geändert.

Der erste gezielte Test scheiterte am noch fehlenden öffentlichen Einstieg.
Die anschließenden synthetischen Prüfungen verwenden acht Systeme an zwei
nativen MQTT-Parents. Unbestätigte Starts, passende Health-/Status-/Katalog-
Antworten, parallele Anfragen, Replays, falsche und retained Antworten,
zwischenzeitliche Eingaben, ungültige Kataloge, Fristen, späte Antworten,
geänderte Bindungen, Modulupdate und HTTP-Grenzen sind abgedeckt. Exakte YAML,
Freigaben, Auswahl und offene Eingaben bleiben erhalten. Alle beobachteten
MQTT-Aufträge sind ausschließlich health_check, status und catalog.

Ein separater unsichtbarer Edge-Prozess lädt die echten Designerdateien gegen
einen rein lokalen synthetischen Server mit zwei Systemen. Sichtbare Ladephase,
Fehler mit erhaltenen Geräten, erneuter ausdrücklicher Abruf, Systemtrennung,
offene Browserbearbeitung sowie ausbleibende Entwurfsänderungen geprüft.
Eine überlappende Hinweisanordnung korrigiert und bei 900 Pixeln Breite erneut
kontrolliert, einschließlich Abständen und echter Mausradbedienung in beiden
Richtungen. Node-Vertragstests prüfen zusätzlich verspätete fremde Antworten,
Transportfehler und den ausgebliebenen automatischen POST-Neuversand.

php tests/run.php einschließlich 157 JavaScript-Tests und PHP-/JavaScript-
Integration erfolgreich. Gezielte Katalog- und Designertests nach dem letzten
Hinweisabgleich erneut geprüft. Separate Struktur-/Kodierungsprüfung,
offizielle JSON-Styleprüfung und php-cs-fixer im Prüfmodus grün.

Keine Test-/Live-Installation angesprochen, kein Gerät geschaltet und keine
Automationsdatei gespeichert oder aktiviert. Praxisabnahme im echten Symcon-
Host nach Modulupdate steht noch aus. Erweiterungsupdate nicht erforderlich.

## Browser-Regelverwaltung lokal geprüft am 05.11.2026

Punkt 1 der Designeragenda ergänzt die feste POST-manage-Route mit eigener
Sitzungsberechtigung. Bestehende Helper geprüft: DebugFlowHelper bleibt für
Abläufe kanonisch; HttpResponseHelper und VisualizationAssetHelper werden über
den vorhandenen Hook-/Assetpfad weiterverwendet. Keine Helperkopie oder Änderung
an zentralen Quellen. Fachliche Verwaltung bleibt librarylokal.

Synthetische Hooktests prüfen ausgeschaltete Kopien aktivierter Regeln,
identische Nutzdaten und Freigaben, kollisionsfreie Identitäten, Löschung,
Folgenabhängigkeiten, Wiederholungsquittungen, volle Entwürfe, veraltete
Ansichten, offene native Eingaben, Berechtigungen, Herkunft, Inhaltstyp und
Anfragegrenzen. Mehrsystemtests erhalten den zweiten Entwurf byteidentisch,
auch bei gleichen Regel-IDs. Alle Verwaltungsaufrufe erzeugen null MQTT-Pakete.
Filtertests unterscheiden true/false und erhalten Katalog-/Folgeneinträge.

Ein separater unsichtbarer Edge-Testprozess hat die echten Designerdateien
gegen einen ausschließlich lokalen synthetischen HTTP-Server geladen:
Bestätigen/Abbrechen, Kopieren, Löschen, Schutz verwendeter Folgen, Löschen
unbenutzter Folgen, Statusfilter und Mausrad in beiden Richtungen geprüft.
Die Darstellung bei 900 Pixeln Breite visuell kontrolliert. Dieser Nachweis
ersetzt keine Abnahme im echten Symcon-Host. Keine Verbindung zu Test-/Live-
Symcon, keine privaten Daten und keine Änderungen an Z2M. Erweiterungsupdate
nicht erforderlich; reale Testabnahme nach Modulupdate bleibt offen.

Abschließend php tests/run.php einschließlich 153 JavaScript-Tests und der
PHP-/JavaScript-Integration bestanden. Separate Struktur- und Kodierungsprüfung,
offizielle JSON-Styleprüfung der 18 eigenen Dateien in isolierter Kopie und
PHP CS Fixer 3.95.27 im Prüfmodus (0/131) grün. Synthetische Tests verwenden
PHP 8.5.10 und Node 24; unverändertes Erweiterungsartefakt geprüft.

## Parallele Aufrufe und Besitzermeldungen lokal geprüft am 05.11.2026

Eine belegte Z2MA.Owner-Eintrittssperre wurde bisher wie eine tatsächliche
Besitzersperre beantwortet. Die Ablehnung erfolgt jetzt vor dem Callback mit
instance_busy und einer deutschen Erklärung: Das Modul ist gerade beschäftigt;
die Aktion wurde nicht ausgeführt und wird nicht automatisch wiederholt.
Erst nach erfolgreichem Sperrerwerb werden Besitzer und Übernahmezustand geprüft.
Deren Ablehnungen bleiben ownership_conflict mit dem vorhandenen sicheren Hinweis.
HTTP 409, Boolean-/Void-Rückgaben und das native Formularformat bleiben erhalten.
HTTP-Fehler enthalten zusätzlich den unterscheidbaren Fehlercode.

Nur feste Designerdateien dürfen weiterhin höchstens fünf Sekunden warten.
Bei Fristablauf wird ebenfalls Belegung gemeldet. Daten- und Betriebsaufträge
erhalten weder eine neue Wartefreigabe noch eine automatische Wiederholung.
Debug nennt Aufruf und Grund im unveränderten kanonischen DebugFlowHelper.
Vorhandene Helper und deren Verträge geprüft: DebugFlowHelper bleibt kanonisch;
die fachliche Besitzerprüfung wird nicht durch die anders gebundene Sperre des
ChunkedJsonTransferHelper ersetzt. Keine Helperdatei verändert.

Der neue Regressionstest schlug zunächst an der falschen Besitzermeldung fehl.
Nach der Korrektur sind synthetische Zentralen mit 1/2/8 Systemen und gemeinsamen
oder getrennten MQTT-Parents geprüft: öffentliche Lese-/Schreibaufrufe, native
Formulare, Timer, Boolean-Rückgabe und alle Browser-Betriebsarten. Die fremde
Sperre bleibt gehalten; keine MQTT-Pakete, Änderungen an Entwürfen, Auswahl,
Freigaben, Journalen oder Empfängerzuständen und kein nachträglicher Auftrag.
Ein neuer ausdrücklicher Aufruf funktioniert nach Freigabe. Echte fremde sowie
beschädigte Besitzzuordnungen bleiben geschützt. Debugpaare sind ausgeglichen,
Tokens, Auftragskennungen und private Testwerte fehlen. Dateiprüfungen sichern
die bisherigen Wartegrenzen; der JavaScript-Vertrag zeigt den Busy-Hinweis ohne
erneuten Versand und erlaubt einen späteren ausdrücklichen Auftrag.

Keine Test-/Live-Installation angesprochen oder verändert. Reale Abnahme nach
Modulupdate bleibt offen; Erweiterungsupdate nicht erforderlich.

Abschließende lokale Prüfung: php tests/run.php einschließlich 151 JavaScript-
Tests und PHP-/JavaScript-Integration bestanden. Separate Struktur- und zentrale
Kodierungsprüfung erfolgreich. Offizieller StylePHP-Check aller 18 eigenen
JSON-Dateien in isolierter Kopie grün; Fremdquellen unverändert. PHP CS Fixer
3.95.27 im Prüfmodus ohne Befund für 129 Dateien. Ziel bleibt Symcon 9.0;
synthetische Tests unter PHP 8.5.10 ersetzen keine reale Symcon-Abnahme.

## Erste Mehrsystem-Verträge lokal geprüft am 04.11.2026

Schritt 1 aus ADR 0026: SystemContract v1 definiert stabile UUID-Systemkennungen,
systemgebundene Entitätsschlüssel, maximale Systemzahl, Besitzer-/Generationsbindung
und Rollenklassifikation. Z2MConnection-GUID und vierstelliges Präfix sind reserviert;
keine zusätzliche Modulinstanz oder Rolle wird bereits angelegt oder migriert.
Vorhandene Parent-/Datenfluss-/Debughelper und PersistentJsonCacheHelper geprüft.
Eine neue Cacheintegration ist für diese rein lesende Stufe nicht erforderlich.

GetSystemMigrationSource liest bekannte Attribute und Eingabepuffer unter der
lokalen Transportsperre. Hash und Metadaten werden ohne Sitzungen, YAML oder
Roh-Eingaben exportiert. PreviewSystemMigration liest ausschließlich ausgewählte,
typgeprüfte Quellen; der Gesamtaufruf hält keine verschachtelte Quellsperre.
Es gibt kein RefreshState, Formularladen, Anlegen, Umhängen oder MQTT-Senden.
Die Bereitschaft gilt nur für weitere Prüfung, niemals als canApply-Freigabe.
Aktuelle Variablenrechte, tatsächliche Migration und persistente Besitzübergabe
werden erst in späteren Schritten nachgewiesen.

tests/multisystem.php verwendet synthetische echte Modulinstanzen an den
vorhandenen nativen MQTT-Fixtures. Ein, zwei und acht Quellen an gemeinsamem
Parent sowie zwei getrennte Parents geprüft; neunte Quelle, Dopplungen, fremder
Modultyp, defekte/abweichende Metadaten, unbekanntes Schema, offene Eingaben und
nicht ausgewählter Partner werden geschützt abgewiesen. Gleiche Topics bleiben
auch auf verschiedenen Parents ein Konflikt. Noch nie gespeicherte lokale
Entwürfe mit Basisrevision null bleiben prüfbar. Wiederholte Vorschauen ergeben
denselben Bericht. Quellattribute, YAML, Freigaben, lokale Kennungen, Eingaben und
Transportspeicher bleiben identisch; beide MQTT-Fixtures erhalten keine Pakete.
Debugausgaben enthalten sichere Entscheidungen und keine privaten Fixturewerte.

Ein früher Prüfversuch verglich zusätzlich Debugmeldungen aus der Fixture-
Einrichtung und schlug deshalb am Datenschutzcheck fehl. Die Messung auf den
eigentlichen Vorschauaufruf begrenzt. Ein weiterer Fixturewechsel benötigte
zuerst das native Disconnect vor Connect; nur den Testaufbau korrigiert.
Keine Änderung oder Abnahme in Test-/Live-Symcon und kein Acht-System-Praxisnachweis.

php tests/run.php einschließlich der neuen PHP-Vertragsprüfung und 137
JavaScript-Tests bestanden. Nach zusätzlichen Fixture-/Fingerprint-Fällen die
gezielte Mehrsystemprüfung erneut erfolgreich ausgeführt. Struktur-/Kodierungs-
prüfung, aktuelles Erweiterungsartefakt und Formatprüfungen ebenfalls grün.
Die bisherigen Modulmetadaten, automatischen Startpfade, zentralen Helper und
das Z2M-Artefakt bleiben unverändert. Der spätere native Assistent sowie eine
echte Test-Symcon-Abnahme sind nicht Bestandteil dieser lokalen Stufe.

## Wiederverwendbare Browserfolgen lokal geprüft am 04.11.2026

ADR 0025 ergänzt den bestehenden Browser-Aktionseditor um Definitionen. Relevante
zentrale HTTP-, Asset- und Debughelper geprüft; diese bleiben unverändert.
Der fachliche Folgenvertrag und die lokalen Entwurfsprüfungen bleiben librarylokal.

Hooktests prüfen Anlegen und Bearbeiten, exakte Werte und Reihenfolge, Szenen,
Kanäle, Namen, Wartezeiten, fehlende Schaltrechte und geschützte Bestandsbereiche.
Aufrufer und Partnerfreigaben bleiben erhalten. Kontextwechsel und offene native
Eingaben blockieren Übernahme. Eigene Berechtigung, alte Sitzungen, fremder Ursprung,
POST-Pflicht, 64-KiB-Grenze, unbekannte Identität und Wiederholungsquittungen geprüft.
32 Definitionen erlauben weiterhin Bearbeitung, aber keine weitere Anlage.
Erweiterte Schrittgrenzen und kombinierte Wartezeiten verwendender Regeln werden
vor jeder Entwurfsänderung geprüft; die Meldung nennt bei Aktionsfehlern die
Schrittnummer. Keine MQTT-Ausgabe; bereinigte deutsche Debugtexte bestätigt.

Lokaler PHP-Prüfserver mit synthetischen Modul-/Gerätestubs, ohne Symcon-Zugang:
vorhandene Folge einschließlich Name, Kanal und Wartezeit vorgefüllt geöffnet
und geändert. Neue Folge per Gerätesuche, Kanal und Wert erstellt, zweiten
Schritt mit fünf Sekunden Pause ergänzt. Szenenschritt ausgewählt, nach oben
verschoben und wieder entfernt; die übrigen Werte blieben erhalten. Übernommen,
erneut geöffnet und beide Schritte vollständig vorbelegt bestätigt. Negative
Wartezeit durch Browservalidierung abgewiesen; Abbrechen erhielt den Entwurf.
Neue Definition anschließend als Aktion einer vorhandenen ausgeschalteten Regel
ausgewählt und übernommen. Verweis, Regelschalter, sonstige Regeln und Freigaben
erhalten. Beim erneuten Öffnen der Folge nennt der Hinweis genau einen Verwender.

Bei 1280 Pixeln kein horizontaler Überlauf des Dialogs. Mausrad scrollt über
beide Schritte bis zu den erreichbaren Übernahme-/Abbruchschaltflächen.
Browserkonsole ohne Fehler/Warnungen. Prüfserver und eigener Hintergrundtab
beendet; synthetische Belege außerhalb des Repositorys. Keine installierten
Test-/Live-Dateien oder Z2M-Daten verändert, keine Gerätebefehle.

php tests/run.php einschließlich 137 JavaScript-Tests bestanden. Nach der
abschließenden Präzisierung der Fehlermeldungen Designer-Hooktests erneut
erfolgreich. Struktur, zentrale Kodierungsprüfung, Artefaktvergleich, offizielle
StylePHP-Prüfung der 15 eigenen JSON-Dateien in isolierter Kopie und PHP CS Fixer
3.95.27 im Prüfmodus erfolgreich. Fremdquellen und Erweiterungsartefakt unverändert.
Eine echte Browserabnahme in der Testinstallation bleibt nach Modulupdate offen;
ein Erweiterungsupdate ist nicht erforderlich.

## Uhrzeit-/Sonnenerstellung im Testmodul am 04.11.2026

Nach dem vom Anwender ausgeführten Modulupdate den echten Browserdesigner im
ursprünglichen Testmodul über einen eigenen Hintergrundtab geprüft. Geladener
Quellstand stimmt mit dem lokalen Erstellhandler überein. Acht Bestandsregeln,
passende Gerätedaten und keine offenen nativen Eingaben; lokaler YAML-Entwurf
vor der Prüfung identisch zum gespeicherten Z2M-Stand. Ausgangsdaten außerhalb
des Repositorys gesichert.

- Uhrzeitregel mit zwei Zeiten und expliziter Zeitzone über „Neue Automation“
  ausgeschaltet übernommen. Zuvor identische Zeiten verständlich abgewiesen.
  Beim erneuten Öffnen alle Zeiten, Zonen, Zielkanal, Wert und Regelschalter
  korrekt vorbelegt; Originalregeln und Partnerfreigabe unverändert.
- Sonnenaufgang mit numerischen Nullkoordinaten und negativem Versatz sowie
  Sonnenuntergang mit synthetischem Standort und positivem Versatz ausgeschaltet
  übernommen. Beim Öffnen zum Bearbeiten Ereignis, Koordinaten, Vorzeichen,
  Minutenwert, Zone und ausgeschalteter Zustand mit den Entwurfsdaten verglichen.
- Browservalidierung verhindert Breitengrad oberhalb der Grenze, Versatz unterhalb
  der Grenze und Bruchteile einer Minute. Eingaben bleiben erhalten; nach Korrektur
  erfolgreiche Übernahme. Drei temporäre Regeln zusätzlich, keine Bestandsänderung.
- Der echte Z2M-Empfänger hat den vollständigen Prüfentwurf per validate bestätigt.
  Keine Speicherung oder Aktivierung. Gespeicherte/aktive Prüfsumme, Revision,
  Zahl aktiver Regeln und Wiederanlauferlaubnis blieben unverändert.
- Native Datei-Debugaufzeichnung bestätigt Uhrzeitanzahl, beide Sonnenereignisse
  und die exakten Vorzeichen der Minutenversätze. Keine Standortkoordinaten,
  Regelnamen oder Tokens in den Erstellmeldungen. Nur validate und lesender
  Editorabruf als Erweiterungsaufträge im Prüfintervall; kein store/activate.
  Kein Gerätebefehl. Mausrad bedient den langen Dialog, bei 1280 Pixeln kein
  horizontaler Überlauf. Keine Browserwarnungen oder Skriptfehler.

Der erste Wiederherstellungsaufruf der Prüfinstrumentierung griff auf einen
falschen Sicherungsschlüssel zu. Die Schutzprüfung brach vor dem Ladeaufruf ab;
kein Modulfehler und keine weitere Entwurfsänderung. Den Prüfaufruf korrigiert,
die unveränderte Ausgangsbasis erneut geprüft und den gespeicherten Editorstand
lesend geladen. Danach YAML bytegleich zur Sicherung und vollständige Konfiguration
identisch: acht Regeln, unveränderte Regelschalter und Freigaben. Gespeicherter
und aktiver Z2M-Stand identisch zur Ausgangsmessung; Modulstatus aktiv. Browser
aktualisiert und das Entfernen aller temporären Regeln bestätigt.

Prüftab geschlossen und eigene Debugdatei-Aufzeichnung beendet. Bestehende
Benutzertabs erhalten. Keine Modul-/Erweiterungsdateien auf einem System geändert;
keine Codekorrektur erforderlich. Reale zeit-/astrogesteuerte Ausführung war
nicht Teil dieses Auftrags. Für den ausschließlich ergänzten Prüfnachweis
Kodierung, Struktur und Diff geprüft; keine erneute vollständige Programmsuite.

## Neue Uhrzeit- und Sonnenregeln im Browser am 04.11.2026 (lokal)

ADR 0024 erweitert ausschließlich den Moduldesigner und seine lokale
Entwurfserstellung. Vorhandene HTTP-/Asset-/DebugFlow-Helper geprüft und
unverändert eingebunden. Keine neue Bibliothek, kein Standortdienst, keine
Erweiterungsänderung. Der neue Hooktest schlug zunächst an der fehlenden
Uhrzeitquelle fehl; danach wurden Quellen über native Kalender-/Astroprüfungen
an den gemeinsamen Regel-/Commitpfad angebunden.

Nachweise mit ausschließlich synthetischen Daten:

- Hook: mehrere Uhrzeiten mit Sekunden-Normalisierung, Sonnenaufgang mit
  negativen und Sonnenuntergang mit positiven Minutenversätzen, echte numerische
  Nullkoordinaten und typisierte Ziele. Neue Regeln stets ausgeschaltet;
  andere Regeln, Folgen und Partnerfreigaben erhalten. Keine MQTT-Pakete.
- Fehlende/falsche Typen, unbekannte Quellfelder, Zeitzonen, Grenzen,
  doppelte normalisierte Zeiten und mehr als acht Uhrzeiten abgewiesen.
  Veralteter Kontext und entzogene Zielrechte erhalten den Entwurf unverändert.
  Wiederholtes Senden einer erfolgreichen Uhrzeitregel legt kein Duplikat an.
- Hintergrundbrowser gegen lokalen PHP-Server mit echtem Hook und Symcon-Stubs:
  zwei Uhrzeiten angelegt, Dublette vorher verständlich abgewiesen. Sonnenaufgang
  mit negativem und Sonnenuntergang mit positivem Versatz angelegt. Alle drei
  Regeln zum Bearbeiten geöffnet und Zeiten, Ereignis, Standort, Zone, Vorzeichen,
  Zielkanal und ausgeschalteten Zustand mit dem gespeicherten Prüfstand verglichen.
  Ein Wechsel der Auslöserart und ein reiner Ansichtsrefresh erhalten Eingaben;
  beim nächsten neuen Dialog ist Europe/Berlin wieder vorbelegt.
- Deutscher Debug bestätigt Uhrzeitanzahl und beide Sonnenereignisse samt
  Minutenversatz. Koordinaten, Namen und Tokens fehlen in diesen Meldungen.
  Originalregeln, Folge und Freigabe erhalten; MQTT-Differenz durchgehend null.
  Keine Browserfehler oder Warnungen. Mausrad bewegt den langen Dialog; bei
  1280 Pixeln Fensterbreite kein horizontaler Überlauf. Schmale Fenster nicht
  gesondert praktisch geprüft. Eigener Prüftab und PHP-Server nach Prüfung beendet.

php tests/run.php einschließlich 136 JavaScript-Tests und nativer PHP-/JavaScript-
Integration erfolgreich; ergänzte Koordinaten-Debugprüfung nochmals fokussiert
bestanden. Struktur, Kodierung, offizieller StylePHP-Check eigener JSON-Dateien
und php-cs-fixer im Prüfmodus grün. Eigene Texte UTF-8 ohne BOM mit LF.
Keine Test-/Produktivinstallation angesprochen oder aktualisiert; keine Speicherung,
Aktivierung oder Geräteaktion in Z2M. Reale Prüfung nach Modulupdate steht aus.

## Browserbearbeitung im ursprünglichen Testmodul am 04.11.2026

Nach dem vom Anwender ausgeführten Modulupdate die echte Browseransicht im
ursprünglichen Testmodul über einen eigenen Hintergrundtab geprüft. Acht Regeln
und aktueller Katalog verfügbar, keine offenen nativen Eingaben; der lokale
YAML-Entwurf stimmte vor der Prüfung mit dem gespeicherten Z2M-Stand überein.
Ausgangsdaten einschließlich YAML außerhalb des Repositorys gesichert.

- Aktive Bewegungsmelderregel ausschließlich geöffnet: boolescher Auslöser,
  Helligkeitsbedingung, Nur-beim-Start-Option, erneute Auslösung und beide
  Aktionen mit Nachlaufzeit korrekt vorbelegt. Abgebrochen, nicht geändert.
- Ausgeschaltete Tasterregel: Name, auslösender Wert und Wartezeit lokal geändert.
  Wieder geöffnet und mit den tatsächlichen Entwurfsdaten verglichen. Zielgerät,
  Kanal, Umschaltaktion und ausgeschalteter Regelschalter erhalten.
- Ausgeschaltete Zeitregel: Uhrzeit, Zeitzone, nächtliches Zeitfenster,
  Wochentage, numerische Gerätebedingung und Aktion korrekt vorbelegt.
  Uhrzeit, Samstag und Vergleichswert lokal geändert, übernommen und erneut
  vorgefüllt geprüft. Der Vergleich mit der Ausgangskopie zeigte ausschließlich
  die beabsichtigten Änderungen und die vorbereitete lokale Folgerevision.
- Absichtlich identische Zeitfenstergrenzen wurden mit HTTP 422 und dem Hinweis
  auf Bedingung 1 abgewiesen. Eingaben blieben im Dialog und der vorherige
  Entwurf unverändert. Danach abgebrochen. Mausrad bewegt den langen Dialog;
  bei 1280 Pixeln kein horizontaler Überlauf, keine Browserwarnungen/Skriptfehler.
- Native Datei-Debugaufzeichnung bestätigt lokale Übernahmen und Ablehnung,
  ohne Regelnamen oder Schaltwerte in diesen Meldungen. Der globale Debugpuffer
  rotierte auf dem laufenden Testsystem zu schnell; deshalb gezielt die
  Datei-Aufzeichnung benutzt und anschließend beendet.

Nach Prüfung der unveränderten gespeicherten Z2M-Prüfsumme ausschließlich den
Editorstand lesend neu geladen. Alle acht Regeln, ihre Schalter und Freigaben
wiederhergestellt; YAML anschließend bytegleich zur Sicherung. Auch der Browser
zeigt wieder die ursprünglichen Zeit-/Bedingungswerte. Gespeicherter und aktiver
Z2M-Stand, Zahl aktiver Regeln und automatischer Wiederanlauf unverändert.
Die erfassten Erweiterungsaufträge enthalten nur Editor- und Statusabfragen.
Keine Speicherung, Aktivierung, Laufzeitsteuerung oder Geräteaktion.

Keine Modul-/Erweiterungsdateien auf einem System geändert. Prüftab geschlossen,
eigene Debugaufzeichnungen beendet; bestehende Benutzertabs erhalten. Der native
Öffnungsbutton, weitere Regeltypen und schmale Fenster wurden in dieser Runde
nicht erneut praktisch geprüft. Die vorigen synthetischen Nachweise bleiben
separat dokumentiert. Für diese Dokumentation Kodierung, Struktur und Diff geprüft;
keine erneute vollständige Programmsuite wegen unveränderter Programmquellen.

## Partnerfreigaben bei lokaler Regelerstellung am 03.11.2026

## Browserbearbeitung vorhandener Regeln am 03.11.2026 (lokal)

ADR 0023: feste POST-edit-Route mit eigenem Sitzungsrecht, Kontextprüfung und
begrenzten Wiederholungsquittungen. Gemeinsame Headerprüfung mit create;
CommitEditorChange/SaveEditorDraft übernehmen ausschließlich lokale Änderungen.
Keine Erweiterungsänderung. Vorher fehlende edit-Route durch den synthetischen
Hook geprüft, danach erfolgreiche Übernahme. Bestehende Bereiche werden exakt
kopiert; geänderte Bereiche durch native Katalog- und Rechteprüfungen validiert.

Nachweise mit ausschließlich synthetischen Daten:

- PHP-Hook: Name und Regelschalter, mehrere Auslöser, Uhrzeiten, Astro,
  Bedingungen samt start_only, gemeinsame Farb-/Helligkeitsbefehle, Szene mit
  Kanal, Aktionsfolge und Verzögerungen; andere Regeln und Freigaben erhalten.
- Konflikte, native offene Eingaben, entzogene Aktionsrechte, alte Lese-/Erstell-
  sitzungen, andere Herkunft, falscher Inhaltstyp, ungültige Körper, unbekannte
  Felder, fehlende Regel und Ablauf werden ohne Entwurfsüberschreiben abgewiesen.
  Leere Änderung schreibt nichts; Bearbeiten bei 100 Regeln bleibt möglich.
- Hintergrund-Browser gegen lokalen PHP-Prüfserver und echten Modul-Hook:
  komplexe Geräteregel vollständig vorbelegt; Name und Szenenwartezeit geändert,
  übernommen und wieder geöffnet. Zeitregel mit zwei Uhrzeiten und Wochentagen
  vorbelegt; zweite Uhrzeit und Samstag geändert und übernommen. Sonnenaufgang,
  Koordinaten 0, negativer Versatz und Zone vorbelegt; Änderung abgebrochen und
  ursprünglicher Versatz erhalten. Zwei lokale Commits im bereinigten deutschen
  Debug bestätigt. Partnerfreigabe und Aktionsfolge im Ergebnis unverändert;
  alle drei Beispielregeln bleiben ausgeschaltet.
- Dialog mit Mausrad gescrollt: scrollTop steigt, kein horizontaler Überlauf bei
  1280 Pixeln Fensterbreite. Keine Browserwarnungen oder Skriptfehler. Eigener
  Prüftab und Prüfserver beendet; vorhandene Vorschau unberührt. Schmale Fenster
  sind per CSS vorgesehen, in dieser Runde nicht gesondert praktisch geprüft.

php tests/run.php einschließlich 134 JavaScript-Tests bestanden. Offizieller
StylePHP-Check für 15 eigene JSON-Dateien und php-cs-fixer 3.95.27 im Prüfmodus
ohne Befund. Eigene Texte UTF-8 ohne BOM mit LF; Fremdquellen unverändert.
Keine Test- oder Produktivinstallation aktualisiert. Reale Browserabnahme nach
Modulupdate bleibt offen. Nichts in Z2M gespeichert, aktiviert oder geschaltet.


Die Browserprüfung im ursprünglichen Testmodul zeigte nach dem verlustfreien
Laden noch einen gesonderten Fehler beim Hinzufügen einer Regel: Die fehlende
lokale Partnermodul-Auswahl wurde als leere Empfangsfreigabe gespeichert. Die
neue Regel war korrekt ausgeschaltet und Bestandsregeln unverändert; Z2M blieb
unverändert. Nach der Prüfung wurde der Ausgangsentwurf bytegleich hergestellt.

Auf Korrekturauftrag den gemeinsamen SaveEditorDraft-Pfad angepasst. Ohne ein
verfügbares Partnermodul bleibt mesh_peers exakt erhalten, einschließlich
fehlender und ausdrücklich leerer Angaben. Eine verfügbare ausdrückliche
Partnerauswahl verwendet weiterhin den bisherigen Synchronisierungspfad.
Katalog-, Ziel- und Aktionsrechte werden dadurch nicht erweitert. Der Debug
erklärt den Erhalt mit der Anzahl der Freigaben, ohne Topics oder Nutzdaten.

Regression über den echten Browser-Hook mit synthetischer vorhandener Freigabe:
zuerst am konkreten Freigabeverlust fehlgeschlagen, nach Korrektur erfolgreich.
Fehlendes und leeres Feld sowie Debugdatenschutz ebenfalls geprüft. Der native
Erstellungspfad wird zusätzlich in der PHP-/JavaScript-Integration geprüft.
Keine Test-/Produktivinstallation oder Z2M-Datei für diese Korrektur aktualisiert;
keine Geräteaktion, Speicherung oder Aktivierung. Modulupdate erforderlich,
Erweiterungsartefakt unverändert. Erneute reale Browserabnahme steht noch aus.

Frische Prüfung: php tests/run.php einschließlich nativer PHP-/JavaScript-
Integration und 131 JavaScript-Tests erfolgreich. Offizielle StylePHP-Prüfung
für eigene JSON-Dateien und php-cs-fixer im Prüfmodus ohne Befund. Kodierung,
LF-Zeilenenden, Struktur und unverändertes Erweiterungsartefakt bestätigt.

## Verlustfreier Testentwurfsabgleich am 03.11.2026

Auf ausdrücklichen Auftrag den bisherigen Testentwurf mit einem getrennt
gelesenen aktuellen Z2M-Stand verglichen. Alle bisherigen Regeln waren darin
inhaltlich identisch enthalten; hinzugekommen war eine später gespeicherte
Regel. Keine lokalen Regelkonflikte oder offenen Formulareingaben. Beide
Fassungen vor dem Abgleich außerhalb des Repositorys gesichert.

Dabei einen Ladefehler nachgewiesen: Eine fehlende lokale Partnerauswahl
entfernte beim Einlesen die gespeicherte Partnerfreigabe und erhöhte die lokale
Revision. Synthetischer Regressionstest schlug vor der Korrektur genau daran
fehl. SaveEditorDraft synchronisiert Partner jetzt nur bei tatsächlicher
Entwurfsbearbeitung; der reine Ladepfad erhält YAML-Bytes, Revision und Freigaben.
Vorhandene Auswahl- und Aktionsrechte bleiben unverändert.

Ausschließlich AutomationEditor.php im Testmodul aktualisiert, ohne Reload
oder Erweiterungsupdate. Zunächst in temporärer Instanz, dann im ursprünglichen
Testmodul den exakten YAML-Hash des gespeicherten Z2M-Stands bestätigt. Alle
Bestandsregeln erhalten, Designerentwurf wieder aktuell und Erstellung erlaubt.
Native Debugmeldungen bestätigen vollständige lokale Übernahme; ausschließlich
Editor-/Statusabfragen, keine Speicherung oder Aktivierung. Gespeicherter und
aktiver Z2M-Hash, Regelschalter und laufender Betrieb unverändert. Temporäre
Instanz samt Statusvariablen entfernt und eigene Debugdateien beendet.

Alle Projektprüfungen einschließlich 131 JavaScript-Tests, StylePHP,
php-cs-fixer-Prüfmodus und Kodierungsprüfung bestanden. Keine erneute sichtbare
Browserbedienung; die Designerfreigabe über GetDesignerData frisch geprüft.
Der vorherige Vermerk zum noch ungeklärten Testentwurf ist damit erledigt.

## Symcon-Header und reale Browserabnahme am 03.11.2026

Die Praxisprüfung zeigte HTTP 415 bei einer gültigen JSON-Anfrage: Der native
Symcon-WebHook stellt HTTP_CONTENT_TYPE bereit; der bisherige lokale Prüfserver
hatte CONTENT_TYPE gesetzt. Die Korrektur akzeptiert beide Varianten und behält
die JSON-Pflicht bei. Mehrere vorhandene, widersprüchliche Medientypen werden
abgewiesen. Der Debug protokolliert ausschließlich Verfügbarkeit der beiden
Header und erfolgreiche JSON-Erkennung. Regressionsfälle umfassen die native
Variante ohne Standardheader, Groß-/Kleinschreibung mit charset, fehlenden oder
fremden Inhaltstyp und widersprüchliche Header.

Nach Zustimmung ausschließlich AutomationDesignerCreation.php im Testmodul
aktualisiert; Syntaxprüfung erfolgreich, kein Modulreload oder Z2M-Update nötig.
Browserprüfung in einer temporären Modulinstanz mit frisch gelesenen vorhandenen
Regeln: getrennte Gerätesuche, Auswahl eines Mehrkanal-Tasters, Kanal L2, Ein als
Auslöser und Umschalten als Zielaktion erfolgreich. POST und anschließender GET
bestätigten genau eine neue ausgeschaltete Regel. Die bisherigen Regeln waren
unverändert. Native Debugausgaben bestätigten Symcons Header, JSON-Erkennung,
lokale Entwurfsübernahme und HTTP-Erfolg; keine Erweiterungsbefehle im Prüfintervall.
Browser ohne Skriptfehler. Temporäre Instanz samt Statusvariablen und Prüftab
entfernt, Debugdatei beendet. Gespeicherter und aktiver Z2M-Stand unverändert.

Der bisherige Testentwurf gehörte zu einem älteren Stand. Er wurde außerhalb
des Repositorys gesichert und bytegleich erhalten. Der Geräteabruf ergänzte nur
den aktuellen Katalog. Die Prüfung hat diesen Entwurf bewusst nicht durch den
gespeicherten Z2M-Stand ersetzt; dafür bleibt ein gesonderter Bedienentscheid nötig.

## Automationsdesigner: Regelerstellung am 03.11.2026

ADR 0022 ergänzt den bestehenden Browserzugang um eine eng begrenzte lokale
Regelerstellung. AutomationDesignerCreation verwaltet keine zweite Regeldatei,
sondern nutzt SimpleEditorRule und CommitEditorChange. Die native einfache
Erstellung nutzt denselben Regelaufbau. creator.js ergänzt Suchfelder,
Gerätelisten, rollenabhängige Kanal-/Wertauswahl und einen eigenen Dialog.
Schreibbare Aktionswerte einschließlich Umschalten kommen aus dem nativen
Katalog. Keine eigenen Aktionsrechte oder MQTT-Befehle im Browser.

Die vorhandenen zentralen HTTP-/Asset-/DebugFlow-Helper wurden geprüft und
unverändert verwendet. HTTP-Anfragen und Antworten bleiben lokal ergänzt;
ein allgemeiner Request- oder Berechtigungshelper ist dafür nicht eingebunden.
Die neue Route begrenzt JSON, Herkunft und Zugang, prüft eine Inhaltskennung und
bekannte native Eingaben und speichert nur ausgeschaltete Entwurfsergänzungen.
Auftragsbestätigungen verhindern Dubletten auch bei erneuter Ansicht nach einer
verlorenen Antwort. Abgelaufene, alte reine Lesezugänge und Kontextwechsel
berechtigen nicht zur Erstellung. Neue öffentliche Symcon-Funktionen oder
Änderungen der Z2M-Erweiterung sind dafür nicht erforderlich.

Lokaler Praxisnachweis gegen den echten PHP-Hook-/Entwurfspfad mit Symcon-Stubs:
50 synthetische Geräte, Suche nach einem einzelnen Auslöser, Zielkanal L2,
Umschalten, Browserübernahme und anschließender GET. Drei Regeln statt zwei,
die neue ausgeschaltet, Quellkennung und Zielkanal korrekt; MQTT-Zähler null.
Deutsche Debugmeldungen für Commit und Browserübernahme vorhanden. Geräteliste
und Dialog per Mausrad bedienbar; Browser meldet keine Skriptfehler. Temporärer
Prüfserver und Prüftab werden danach geschlossen. Keine Dateien oder Entwürfe
einer laufenden Installation geändert. Native Formular-/Browserabnahme nach
dem Modulupdate bleibt offen; weitere Bearbeitung und Übertragung bleiben nativ.

Abschluss: php tests/run.php einschließlich aller PHP-Prüfungen und 131
JavaScript-Tests erfolgreich. Offizielle StylePHP-Prüfung für 15 eigene JSON-
Dateien, php-cs-fixer im Prüfmodus (0/81), Kodierungs-/Strukturprüfung und
Extension-Buildabgleich erfolgreich. Die Erweiterungsquellen und eingebundenen
Fremdquellen bleiben unverändert.

## Automationsdesigner: getrennter Geräteabruf am 03.11.2026

RefreshDeviceCatalog verwendet den bestehenden Workflow mit status/catalog.
Die eigene Erweiterung liefert nur Katalog und Status; der gemeinsame Parser
übernimmt erst den vollständig geprüften Snapshot mit erfolgreicher Abrufzeit.
Keine Entwurfs- oder Laufzeitübernahme und keine Erweiterung des GET-Zugangs.
Der alte editor-Abruf bleibt als bewusstes Laden gespeicherter Regeln erhalten.
Passende zentrale Helper geprüft: DebugFlowHelper, ConfigurationFormHelper,
VisualizationAssetHelper und HttpResponseHelper werden unverändert verwendet;
der Z2M-Katalogvertrag bleibt fachlich lokal.

Synthetische Nachweise über PHP, natives MQTT-Envelope und erzeugtes Artefakt:
reiner Abruf ohne store/activate/deactivate; bytegleiche Entwürfe, Auswahl,
EditorWork-Puffer und Aufzeichnungsfrist; erhaltene detaillierte Prüfungsfehler;
fehlerhafte Antwort erhält alten Katalog samt Zeitstempel; ältere Erweiterung
meldet Updatebedarf. Auch ein fremder alter Entwurf verhindert den Abruf nicht
und wird danach weder im Browser noch in der nativen Regelübersicht als aktuell
ausgegeben. JavaScript prüft die unveränderte aktivierte Laufzeit zusätzlich.

Lokale Browser-Sichtprüfung mit ausschließlich synthetischen Daten: Zeitstempel,
Auswahl nach „Ansicht aktualisieren“, fehlender Entwurf und Scrollen durch 50
Geräte geprüft. Temporären Prüftab und Server anschließend geschlossen; die
bestehende Offline-Vorschau auf Port 7405 bleibt erhalten. Keine MCP-Installation,
kein Erweiterungsupdate und keine Geräteaktion in Test oder Produktion. Der
neue native Abrufbutton und reale MQTT-Debugausgaben sind nach dem gemeinsamen
Modul-/Erweiterungsupdate noch praktisch abzunehmen.

Abschlussprüfungen: php tests/run.php einschließlich PHP-/JavaScript-Integration
und 128 JavaScript-Tests erfolgreich. Struktur, zentrale Kodierungsprüfung,
offizielle StylePHP-Prüfung für 15 eigene JSON-Dateien, php-cs-fixer im Prüfmodus
(0/79), Extension-Buildabgleich sowie UTF-8 ohne BOM/LF und git diff --check
erfolgreich. Keine eingebundenen Fremdquellen geändert.

## Automationsdesigner: lesende Anbindung am 03.11.2026

Den bestätigten Entwurf als ersten Integrationsschritt an vorhandene Moduldaten
angebunden (ADR 0021). Formularbutton mit dokumentiertem relativem Browserlink,
flüchtiger instanzbezogener WebHook, streng begrenzter GET-Vertrag und gehashte
15-Minuten-Zugänge. Kein neuer YAML-Speicher und keine Z2M-Operation. Der Export
liest vorhandene Editor-/Katalogcaches, prüft den gemeinsamen TransportScope und
verwendet aktuelle Symcon-Schaltrechte. Offene native Formulareingaben bleiben
erhalten und werden nur als ausstehend gekennzeichnet. Fehlende oder abweichende
Daten werden nicht durch Beispiele ersetzt. Antworten auf 2 MiB begrenzt.

VisualizationAssetHelper und HttpResponseHelper aus dem zentralen Manifest mit
dem vorhandenen Bundle-Verfahren einschließlich Assets übernommen. Subscription
und Consumer-Manifest ergänzt, zentrale Dateien unverändert. Keine Kachel oder
IPSView-Seite; der entsprechende Seiten-Helper wird deshalb nicht eingebunden.
HTML/CSS und Offline-JavaScript aus dem Prototypordner in den kanonischen
Modulordner verschoben. WebHook lädt nur reader.js; die separate Offline-Vorschau
bleibt über denselben lokalen Vorschau-Server erreichbar.

Neue synthetische Grenztests prüfen fehlende Caches, vollständige vorhandene
Regeln/Folgen und Wartezeiten, offene Formulareingaben, entzogene Schaltrechte,
HTTP-Routen, verweigerte Schreibzugriffe und unzulässige Datei-/Queryzugriffe.
Zugang ohne Token, falscher Token, andere Instanz, Ablaufzeit, maximal vier
Sitzungen und angewendeter Topicwechsel werden abgewiesen. Getrennte native
Eingaben, lokaler Entwurf und Transportzustand bleiben bei Leseabrufen bytegleich.
Debug enthält deutsche Entscheidungen und Mengen, keine Werte/Namen/Zugänge.
Beide Designer-Skripte werden jetzt auch im zentralen Projektlauf syntaktisch
geprüft. Die Originalerweiterung und ihr Artefakt bleiben unverändert.

Nur Test-Symcon 9.1 per MCP aktualisiert, Originaldateien und Testentwurf außerhalb
des Repositorys gesichert. Vorhandene Datei-Hashes mit lokalem Ausgangsstand
abgeglichen. Beim ersten Reload trifft ein MQTT-Paket vor Registrierung der
BaseTopic-Property ein und erzeugt Warnung/TypeError. Empfang während der
Initialisierung jetzt gesperrt und im Handler über einen Bereitschaftsbuffer
abgewiesen. Gezielt synthetisch nachgestellt; erneuter echter Reload ohne
Warnungen/Fehler, MQTT-Regression erfolgreich.

Der bisherige lokale Testentwurf/Katalog gehört nicht mehr zur aktuellen
Verbindung. Keine automatische Ersetzung. Deshalb eine ausdrücklich temporäre
Prüfinstanz am vorhandenen Testgateway angelegt und nur bestehende Z2M-Daten
geladen. Keine Regel validiert, gespeichert, aktiviert oder gestoppt und keine
Erweiterung installiert. Die vorhandene Editorlogik bereitet gegebenenfalls
eine lokale Folgerevision vor; die Browseranzeige unterscheidet diesen lokalen
Entwurf vom zuletzt bestätigten aktiven Stand.

Im eigenen Hintergrund-Browsertab acht vorhandene Regeln und 91 Geräte/Gruppen
gelesen. Geräteereignisse, Zeitregel mit Zeitfenster und Temperatur, Startbedingung,
Schaltaktionen und Schrittwartezeit korrekt sichtbar. Einzelne Tasterkanäle samt
Symcon-Schaltrecht und reine Anzeigeeigenschaften getrennt dargestellt. Suche,
Bereichs-/Regelauswahl, Detailvorbelegung und erneutes Lesen bestätigt. Lange
Gerätetexte verursachten zunächst einen horizontalen Listenüberlauf; gezielt
umbrochen, danach Client-/Scrollbreite gleich. Browser enthält außer Suchfeld
keine Regelfelder und keine Speicher-/Betriebsaktionen. Keine Browserwarnungen
oder JavaScriptfehler. Mausrad über Liste, Ablauf und Eigenschaften jeweils in
beide Richtungen mit unveränderten Nachbarbereichen geprüft. Responsive Ansicht
bei 760 und 390 Pixel ohne horizontale Seiten-/Panelüberbreite.

Echte HTTP-Nachweise: Shell 200, Daten ohne Token 403, Beispielskript 404,
POST 405; no-store, no-referrer, nosniff und CSP gesetzt. Ein autorisierter
GET liefert denselben Datenvertrag wie der Modulaufruf. Wegen stark rotierendem
gemeinsamem Debugbuffer zuletzt kurz eine native Debugdatei verwendet. Sie
bestätigt Öffnen und Export sowie den HTTP-Handler mit Start/Sendung/Ende,
deutscher Erläuterung, Mengen und Byteumfang, ohne Token oder Inhalte. Symcon
führt die WebHook-Klasse als HookInstance; die Instanz-ID bleibt der Bezug.
Aufzeichnung danach beendet. Temporäre Prüfinstanz samt eigenen Statusvariablen
entfernt und Fehlen bestätigt. Originaler Testentwurf bytegleich; zuvor bestätigte
gespeicherte/aktive Revision, eingeschaltete Anzahl, Mesh und Fehlerfreiheit
unverändert. Keine Änderung der Produktivinstallation oder des Z2M-Regelstands.

Native Desktopkonsolen-Klicks, Fernzugriff/Proxy, andere Browser und native
Symcon 9.0 sind damit nicht abgenommen. Ein eigener Konsolentab wird nicht
behauptet. Im realen Datenbestand waren keine wiederverwendbaren Folgen
gespeichert; deren Export wurde synthetisch, ihr Aufruf nicht real geprüft.
Screenshots, Zugänge, Debugrohdatei und private Daten außerhalb des Repositorys.

Abschluss erfolgreich: php tests/run.php einschließlich der neuen Designer-
Grenzprüfung, beider JavaScript-Syntaxprüfungen und 127 JavaScript-Tests;
Erweiterungsartefakt aktuell, Struktur- und zentrale Kodierungsprüfung sauber.
Offizielle StylePHP-Prüfung der 15 eigenen JSON-Dateien in isolierter Kopie
und PHP CS Fixer 3.95.27 im Prüfmodus (0 von 78 Dateien zu ändern) erfolgreich.
Helper-/Asset-Hashes und neue Helper gegen die dokumentierte zentrale
Commitquelle verglichen. Eigene Dateien UTF-8 ohne BOM/LF, git diff --check
sauber. Kein Commit, Push oder Release.

## Automationsdesigner: erste Offline-Vorschau am 03.11.2026

Auf ausdrücklichen Auftrag die bestätigte Aufteilung als klickbaren Entwurf
umgesetzt: linke Bereichs-/Regelauswahl, mittlerer Ablauf Wenn/Nur wenn/Dann,
rechte Eigenschaften und obere Prüf-/Speicher-/Betriebsanzeige. Ausschließlich
eigene HTML-/CSS-/JavaScript-Dateien und lokale SVG-Symbole unter
docs/prototypes/automation-designer. Keine Fremdimplementierung, neuen Pakete
oder Änderungen an Modul, Erweiterung, Helper-Bundle oder Live-System.

Vor Umsetzung die vorhandenen VisualizationAssetHelper- und
IPSViewHTMLPageHelper-Angebote geprüft. In diesem statischen Offline-Schritt
gibt es noch keinen PHP-/Modulhost; deshalb keine zusätzliche Helper-Einbindung
oder lokale Kopie. Die Integration wird gesondert festgelegt. Insbesondere ist
ein eigener Tab wie beim IPSView-Designer noch kein bestätigter Symcon-Vertrag.
Kein Kachelprojekt. Das Ansichtsmodell ist kein zweiter YAML-Vertrag.

Praktische Bedienprüfung über einen ausschließlich an Loopback gebundenen
Vorschau-Server im eigenen In-App-Browsertab, ohne Übernahme bestehender
Desktopfenster. Namen ändern, Bedingungen und Aktionseigenschaften bearbeiten,
Gerät/Kanal/Wert getrennt wählen und nach Wechsel erneut vorbelegen bestätigt.
Aktionen bieten nur die beschreibbaren Beispielgeräte; Szenen und gemeinsame
Folgen sind wählbar. Eine verwendete Folge lässt sich nicht löschen. Neu- und
Kopieranlage beginnen ausgeschaltet; Abbrechen und Bestätigen des Löschdialogs
geprüft. Ungültige Uhrzeit und leere neue Regel liefern benannte Hinweise und
geben das Speichern nicht frei. Mehrere Uhrzeiten, Astro-Versatz und abgewählte
Wochenendtage bleiben nach erneutem Auswählen erhalten.

Der erste HTML-Dragversuch änderte die Reihenfolge im Prüfbrowser nicht.
Gezielte Pointer-Bedienung am Griff umgesetzt; tatsächliches Mausziehen
verschiebt anschließend den zweiten Schritt vor den ersten, einschließlich
seiner 60-Sekunden-Wartezeit. Rückverschieben über den Pfeil ebenfalls bestätigt.
Prüfen, Speichern, simulierter Aktivstatus und Stopp praktisch bestätigt;
nach einer Eingabe ist eine neue Prüfung nötig. Diese Statusanzeige führt
keine Geräteaktionen oder Zeitgeber aus und schreibt nur in den Fensterspeicher.
Neuladen setzt den Beispielstand wieder her. Das sichtbare Bedienprotokoll ist
auf 30 Meldungen begrenzt und ausdrücklich kein nativer Modul-/Z2M-Debugkanal.

Mausrad in allen drei überlaufenden Bereichen separat abwärts und aufwärts
betätigt. DOM-Nachweis: nur der jeweils angesprochene Bereich scrollt, anschließend
alle wieder bei Null. Responsive Sichtkontrolle bei 1280, 1000, 760 und 390 Pixel
Breite; keine horizontale Seitenüberbreite. Bei schmaler Ansicht werden die
Eigenschaften unter dem Ablauf angeordnet. Keine JavaScriptfehler oder Warnungen
im Browserprotokoll. Direkte Dateiöffnung, Touch und andere Browser sowie die
spätere Einbettung sind damit nicht abgenommen. README erklärt weitere fehlende
Editorfunktionen, flüchtige Speicherung und die reine Beispielvalidierung.

Abschlussprüfungen erfolgreich: node --check für designer.js, php tests/run.php
einschließlich 127 JavaScript-Tests, Struktur- und zentrale Kodierungsprüfung,
aktuelles Erweiterungsartefakt, offizielle StylePHP-Prüfung der 15 eigenen
JSON-Dateien in isolierter Kopie und PHP CS Fixer 3.95.27 im Prüfmodus
(0 von 76 Dateien zu ändern). Eigene neue/bearbeitete Dateien UTF-8 ohne BOM
mit LF; git diff --check sauber. Browserstand auf die Beispiele zurückgesetzt.
Keine Commits, Pushes oder Veröffentlichung.

## Bewegungsmelderregel: Reale Nachlaufverlängerung am 03.11.2026

Auf ausdrücklichen Auftrag bei passender Außenhelligkeit die dauerhaft aktive
Regel mit echten Bewegungsmeldungen geprüft. Vorab Licht aus, Sensorbereich frei,
Helligkeitsbedingung erfüllt; gespeicherte und aktive Prüfsumme identisch,
Wiederanlauf armed und kein Laufzeitfehler. Zeitlich begrenzte eigene MQTT-
Aufzeichnung bestätigt und native Debugdatei gestartet. Keine Regel-, Geräte-
oder Ereigniseinstellung verändert und keine Testereignisse eingespeist.

Der Anwender bestätigt das Einschalten nach Bewegung. Anfängliche Aufforderungen
zur Wiederholung kamen zu spät: Die ursprüngliche Minute war bereits abgelaufen,
und eine weitere Bewegung startete einen neuen Lauf. Dies ist kein Nachweis
eines Verlängerungsfehlers; diese Durchgänge wurden nicht als Verlängerung
gewertet. Danach Sensorfreigabe und tatsächliche Einschaltzeit als Bezug genutzt.

Im auswertbaren Lauf erfolgt die zweite Bewegung 43 Sekunden nach dem Start.
Debug bestätigt „Wartezeit wegen erneuter Auslösung auf volle Dauer
zurückgesetzt“ mit derselben Laufkennung und Schritt 2. Genau ein Einschalt-
und ein Ausschaltauftrag, kein erneutes Einschalten. Eine lesende Statusprobe
26 Sekunden nach der alten Ausschaltfrist bestätigt weiterhin Ein. Genau
60 Sekunden nach der erneuten Meldung endet die Wartezeit; die native
Lichtvariable wechselt im selben Sekundenintervall auf Aus. Frische weitere
Variablenmeldungen bestätigen Aus und Helligkeit null. Laufstatus completed,
Schritt 2 und Fehler leer. Debug zeigt, dass die Startbedingung vor der
abschließenden Aktion nicht erneut geprüft wird.

Abschluss bestätigt: gespeicherte/aktive Revision und Prüfsumme unverändert,
acht Regeln und zwei eingeschaltete Regeln, Automatikbetrieb aktiv, Mesh bereit,
Wiederanlauf armed und kein Laufzeitfehler. Die ursprünglichen Symcon-Ereignisse
bleiben ausgeschaltet. Nur eigene Aufzeichnungen beendet; die dauerhaft aktive
Regel bleibt wie beauftragt erhalten. Kein Update, Speichern oder Neustart in
diesem Durchgang. Rohdaten, genaue Zeitbezüge und Installationsdaten ausschließlich
außerhalb des Repositorys gesichert. Ein Helligkeitsanstieg über die Grenze
während der Wartezeit wurde real nicht herbeigeführt. Kein Funktionscode geändert.

Kodierungs- und Strukturprüfung sowie git diff --check erfolgreich. Nur die
drei Nachweisdokumente geändert; unveränderte Offline-Laufzeit- und Stilprüfungen
nicht wiederholt. Kein Commit oder Push.

## Startbedingungen: Praxis und dauerhafte Übernahme am 03.11.2026

Nach ausdrücklicher Fortsetzung zunächst Modul- und Erweiterungsstände beider
Installationen geprüft. Testmodul und gemeinsame Z2M-Erweiterung waren aktuell;
das Produktivmodul erhielt das noch ausstehende Update durch den Anwender.
Kein Erweiterungsupdate oder Neustart durch den Agenten. Status und lokale
Entwürfe sowie exakten gespeicherten Inhalt außerhalb des Repositorys gesichert.
Der ältere lokale Produktiventwurf stimmt bis auf die Revision mit dem aktuellen
Z2M-Inhalt überein. Keine fachlichen lokalen Änderungen verworfen.

In der getrennten Prüfkopie eine ausgeschaltete Bewegungsmelderregel über die
Modul-API erstellt und von Z2M ausschließlich validieren lassen. Reales
Formular-JSON und das tatsächlich hinterlegte Zeilendialog-Skript bestätigen
Gerät/Eigenschaft, Zahlenvergleich, StartOnly, feste Ein-/Aus-Aktionen und
verlängerbare Schrittwartezeit. Kein Maus-/Bildschirmtest; kein neuer Nachweis
physischer Gerätewirkung. Derselbe Aufbau anschließend im Produktivmodul
ergänzt, nach Anwenderwunsch mit eingeschaltetem Regelschalter. Vor dem
Speichern strukturell geprüft: Alle sieben bisherigen Regeln einschließlich
ihrer Aktivierungsschalter und Partnerfreigaben bleiben unverändert.

Korrelierte Z2M-Validierung erfolgreich, geänderten Stand einmal gespeichert
und automatische Statusbestätigung abgewartet. Danach Automatikbetrieb mit
gespeicherter Wiederanlaufabsicht aktiviert. Frische Antworten bestätigen
gespeicherte/aktive Prüfsummengleichheit, acht gespeicherte und zwei
eingeschaltete Regeln, Mesh bereit, Wiederanlauf armed und keinen Laufzeitfehler.
Der gespeicherte Regelstand des anderen Z2M-Systems bleibt unverändert aktiv.
Anschließend neu aus Z2M geladen: YAML bytegleich, neue Regel im Editor
ausgewählt, Auslöser, Bedingung und beide Aktionsschritte vollständig vorbelegt.

Native Debugausgaben bestätigen lokale Übernahme, eine Startbedingung und die
beiden Schrittwartezeiten; zeitlich begrenzte MQTT-Aufzeichnung bestätigt.
Die vom Anwender ausgeschalteten Originalereignisse bleiben aus und bestehen
weiter. Die gemessene Außenhelligkeit liegt oberhalb der gewünschten Schwelle.
Der Anwender verschiebt den echten Sensor-/Lichttest bis zur passenden
Helligkeit. Die neue Regel bleibt dauerhaft aktiv; keine Testereignisse,
Geräteabfragen oder direkten Schaltbefehle eingespeist. Ihr realer Lauf und
ein weiterer Neustart wurden in diesem Durchgang nicht geprüft.

Abschluss: Eigene MQTT-Aufzeichnung und native Debugdateien beendet, zwei
eigene Vorbereitungsinstanzen samt Statusvariablen entfernt. Ursprünglicher
lokaler Testentwurf bleibt unverändert. Native Abfrageskripte mussten bei
isolierten Modulklassen und einem Funktionsnamen korrigiert werden; diese
Fehler der Prüfinstrumentierung haben keine Geräteaktion ausgeführt und sind
kein nachgewiesener Modulfehler. Kein Funktionscode geändert. Rohdaten und
Wiederherstellungsinformationen liegen ausschließlich außerhalb des Repositorys.

Nur Praxisnachweis und Projektstand geändert. Kodierungs- und Strukturprüfung,
offizielle StylePHP-Prüfung der 15 eigenen JSON-Dateien sowie PHP CS Fixer im
Prüfmodus erfolgreich. Unveränderte Offline-Laufzeittests nicht erneut ausgeführt.
UTF-8 ohne BOM mit LF und git diff --check sauber; kein Commit oder Push.

## Startbedingungen und ausgeschalteter Bewegungsmelder-Entwurf am 03.11.2026

Nach Anwenderwahl einer Nachlaufzeit einen getrennten, ausgeschalteten
Regelentwurf in Test-Symcon vorbereitet. Der dortige ältere Originalentwurf
bleibt bytegleich erhalten. Die Quelle ist anhand der Geräteidentität und
Variablenzuordnung dieselbe wie bei der bisherigen Bewegungsmeldersteuerung.
Feste Ein-/Aus-Aktionen statt Umschalten; erneute passende Präsenzmeldungen
setzen die Wartezeit zurück. Sieben bestehende Regeln und Partnerfreigaben in
der Prüfkopie erhalten. Eine zusätzliche inaktive Partnerzuordnung verhindert,
dass beim Entwurfsladen die vorhandene Mesh-Freigabe entfernt wird. Beide
Vorbereitungsinstanzen bleiben für die Fortsetzung erhalten. Ihre native
Debugdatei wurde wieder gestoppt.

Die native Modul-API erstellt die ausgeschaltete Regel; Formular-JSON und
Zeilendialoge bestätigen Auslöser, Ein/Aus, Wartezeit und extend. Z2M bestätigt
nur ihre Validierung. Keine Speicherung, Aktivierung, Installation oder
Schaltanforderung. Gespeicherte und aktive Revision/Prüfsumme unverändert,
bisheriger Automatikbetrieb und Wiederanlauf bestätigt, keine Laufzeitfehler.
Die zwei vom Anwender ausgeschalteten Originalereignisse bleiben aus.

Anschließend wünscht der Anwender dauerhafte Aktivierung mit Helligkeitsgrenze.
Die gewünschte externe Helligkeitsquelle wurde lesend bestätigt; maßgeblich ist
die zuletzt vom Anwender korrigierte Grenze; konkrete Installationsdaten bleiben
außerhalb des Repositorys. Noch keine Aktivierung: Eine
fortlaufende Bedingung könnte bei steigender Helligkeit auch das Ausschalten
verhindern. Deshalb lokal start_only für Gerätebedingungen ergänzt (ADR 0020).
Ein neuer Lauf prüft vollständig; spätere Schritte und extend lassen nur diese
explizit markierten Startbedingungen aus. restart prüft sie erneut. Zeitfenster,
Wochentage und normale Gerätebedingungen bleiben fortlaufend.

Der native Bedingungen-Dialog erhält „Nur beim Start“, einschließlich
Formularserialisierung, Vorbelegung, Schutz offener Eingaben und detaillierter
Validierung. Fehlende/ausgeschaltete Auswahl behält die bisherige Semantik;
falsche Datentypen und unvollständige Speicheraufrufe werden verworfen.
Der bestehende DebugFlowHelper und feste MQTT-Ereigniskatalog bleiben der
Diagnosepfad. Eigene Quellen und Schema geändert, Artefakt ausschließlich mit
npm run build:extension erzeugt. Keine Helper- oder Fremdquellen geändert.

Zuerst gezielte Laufzeittests gegen den alten Stand fehlgeschlagen, anschließend
mit der Erweiterung erfolgreich: unbekannt/zu hell, Helligkeitsanstieg,
Verlängern, abschließendes Aus, neuer Lauf und Neustart sowie Einzelstart mit
gemischten Bedingungen. PHP-/JavaScript-Integration prüft native Bearbeitung,
Speicherung/Wiederladen und ungültige Flags mit synthetischen Daten. Der
endgültige ausgeschaltete Entwurf mit Startbedingung ist außerhalb des
Repositorys für die Fortsetzung gesichert und gegen die neue Erweiterung
validiert. Er ist noch nicht in die installierte ältere Modul-/Erweiterungsversion
übernommen. Gemeinsames Update, native Praxis der neuen Option, Speicherung und
Aktivierung der gewünschten Bewegungsmelderregel stehen noch aus.

Abschließend php tests/run.php mit 127 JavaScript-Tests und sämtlichen PHP-
Prüfungen erfolgreich. Zusätzlich offizielle StylePHP-Prüfung der 15 eigenen
JSON-Dateien in isolierter Kopie und PHP CS Fixer im Prüfmodus erfolgreich;
Erweiterungsartefakt aktuell, UTF-8 ohne BOM mit LF und git diff --check sauber.
Der neue Eingabeschutztest berücksichtigt auch das normalisierte false einer
nicht angehakten Bedingungszeile. Die konkrete Helligkeitsquelle und der vom
Anwender korrigierte Zahlenvergleich wurden zusätzlich im installierten
Zeilendialog vorbelegt; die neue Startoption ist dort erst nach Update prüfbar.
Kein Commit oder Push.

## Agenda 4: Frühe Aus-Meldung und isolierte Nachprüfung am 03.11.2026

Zunächst ausdrücklich nur lesend untersucht: gesicherte Testbeobachtungen,
Archivwerte, bestehende Debugdateien sowie native Ereignis-/Aktionsmetadaten
auf Test- und Produktiv-Symcon abgeglichen. Kein Geräte-GET, Schaltbefehl,
Aufzeichnungsstart oder Konfigurationsauftrag in dieser Diagnosephase.
Das Archiv bestätigt Aus etwa sechs Sekunden nach dem ersten Start; der
MQTT-Debug der eigenen Erweiterung enthält die Ausschaltübergabe erst bei
Sekunde 17. Die vorhandenen Geräte-/Gateway-Debugdateien decken diesen
Zeitraum nicht ab. Eine historische Sensor- oder Senderaufzeichnung für
den fraglichen Wechsel steht nicht zur Verfügung.

Der Anwender weist auf die zusätzliche Bewegungsmeldersteuerung hin und
deaktiviert sie selbst. Lesend gefunden: Zwei native Ereignisse am selben
Licht reagieren auf Anwesenheit wahr beziehungsweise falsch. Beide verwenden
die native Aktion Toggle Value für die Statusvariable des Eltern-Geräts;
der positive Zweig besitzt zusätzlich eine Helligkeitsbedingung. Ziel und
Aktion wurden anhand der verschachtelten nativen Aktionsmetadaten geprüft,
nicht aus Namen geraten. Die frühere Suche ausschließlich nach expliziten
Ziel-IDs erfasste diesen aus dem Eltern-Gerät abgeleiteten Zielpfad nicht.
Damit ist ein konkurrierender Schaltweg belegt: Eine passende Sensormeldung
kann ein bereits eingeschaltetes Testlicht ausschalten. Der konkrete
historische Auftrag ist ohne passendes Senderprotokoll nicht bewiesen.

Nach ausdrücklicher erneuter Testfreigabe durch den Anwender eine isolierte
Verlängerungsprüfung durchgeführt. Aktuellen Originalentwurf, gespeicherten
Regelstand und Lichtzustand neu gesichert. Beide Bewegungsmelder-Ereignisse
vorher und nachher als ausgeschaltet gelesen; nicht durch den Agenten geändert.
Eine temporäre Regel startet zu zwei echten Uhrzeiten mit fünf Sekunden Abstand:
einschalten, zwölf Sekunden warten, ausschalten. Native Debugmeldungen zeigen
dieselbe Laufkennung und nur eine Einschaltübergabe. Nach der zweiten Auslösung
werden volle zwölf Sekunden abgewartet. Gerätedaten und Symcon-Variable bleiben
über die alte Ausschaltfrist hinaus Ein und melden erst nach der verlängerten
Frist Aus. Regellauf abgeschlossen, kein Laufzeitfehler.

Die Bewegungsmeldersteuerung ist damit die wahrscheinlichste Erklärung für die
erste Abweichung. Ein einzelner erfolgreicher isolierter Lauf beweist jedoch
nicht rückwirkend den Verursacher der früheren Meldung; kein nachgewiesener
Erweiterungsfehler. Das Umschalten in beiden Sensorzweigen ist insbesondere
bei parallelen manuellen oder automatischen Befehlen zustandsabhängig.

Abschluss: Testregel und temporäre Module entfernt, eigene Aufzeichnungen
beendet, Original-YAML nur mit fortlaufender Revision wiederhergestellt und
Originalentwurf bytegleich erhalten. Sieben ursprüngliche Regeln, eine
eingeschaltet, gespeicherte/aktive Prüfsumme identisch, Mesh bereit,
Wiederanlauf armed und kein Laufzeitfehler. Lichtzustand und Helligkeit
entsprechen dem neu gesicherten Ausgangsstand. Die vom Anwender ausgeschaltete
Bewegungsmeldersteuerung bleibt ausgeschaltet. Keine Implementierung geändert.

Nur Diagnose-/Praxisnachweis dokumentiert. Kodierungs- und Strukturprüfung,
offizielle StylePHP-Prüfung der 15 eigenen JSON-Dateien und PHP CS Fixer im
Prüfmodus erfolgreich; UTF-8 ohne BOM, LF und git diff --check sauber.
Unveränderte Offline-Laufzeittests nicht erneut ausgeführt. Kein Commit/Push.

## Agenda 4: Reale erneute Auslösung am 03.11.2026

Mit ausdrücklichem Praxisauftrag über Test-Symcon am gemeinsam genutzten
Z2M-Empfänger geprüft. Keine Erweiterung installiert, kein Neustart und keine
Rechnerbedienung. Originalentwurf, exakten gespeicherten YAML-Text, Regel- und
Betriebszustand sowie rohe Lichtwerte außerhalb des Repositorys gesichert.
Zwei temporäre Module erhalten die vorhandenen Partnerfreigaben; das
Partnermodul bleibt inaktiv und verändert den anderen Empfänger nicht.

Drei temporäre Regeln werden zusammen mit den unveränderten Originalregeln
geprüft, gespeichert und aktiviert. Jede Regel startet zu zwei echten UTC-
Uhrzeiten im Abstand von fünf Sekunden. Aktionen: einschalten, zwölf Sekunden
warten, ausschalten. Kein Einzelstart und keine künstliche MQTT-Gerätemeldung;
damit durchlaufen beide Starts die echte Zeit- und Erneuerungsverarbeitung.
Die Varianten laufen nacheinander. Der Speichervorgang unterbricht den
Automatikbetrieb kurz; danach läuft die ursprüngliche eingeschaltete Regel mit.

| Variante | Laufzeit- und Debugnachweis | Gerätebeobachtung |
| --- | --- | --- |
| Ignorieren | Zweite Auslösung verworfen; kein zweiter Aktionsdurchlauf. Ausschaltübergabe zwölf Sekunden nach dem ersten Start. | Licht zunächst ein und an der ursprünglichen Ausschaltfrist wieder aus. |
| Neu starten | Alter Lauf während Schrittwartezeit beendet; neuer Lauf wiederholt Schritt 1. Ausschaltübergabe zwölf Sekunden nach der zweiten Auslösung. | An der alten Ausschaltfrist noch ein, nach der neuen Frist aus. |
| Wartezeit zurücksetzen | Derselbe Lauf und Schritt; genau eine Einschaltübergabe. Wartezeit erneut zwölf Sekunden; Ausschaltübergabe insgesamt 17 Sekunden nach dem ersten Start. | In gezielter Nachprüfung sowohl Gerätedaten als auch Symcon-Variable über die alte Frist hinaus ein, danach aus. |

Statusmeldungen zeigen abgeschlossene Läufe ohne Fehler. Native Debugdatei und
zeitlich begrenzter MQTT-Debugkanal ordnen Start, Erneuerungsentscheidung,
Wartezeit und Schritte zu. Die Befehlsannahme wird weiterhin ausdrücklich nicht
als Gerätebestätigung bezeichnet. Zusätzlich die tatsächlich empfangenen
Lichtwerte zeitlich erfasst, ohne Variablenwerte künstlich zu setzen.

Erster Verlängerungsversuch: Obwohl die Erweiterung den Ausschaltbefehl erst
zur verlängerten Frist übergibt, meldet die Lichtvariable bereits vorher Aus.
Ursache nicht geklärt; nicht als erfolgreicher Geräteversuch gewertet. Zunächst
Originalbetrieb wiederhergestellt, nur Geräte-/Ereignisdaten auf Produktiv-Symcon
lesend geprüft und anschließend ausschließlich Verlängern wiederholt. Dort
bestätigen zeitnahe rohe Geräterückmeldungen und Symcon-Variable den vollständigen
Ablauf. Keine fremden Ereignisse oder Geräteeinstellungen deaktiviert/geändert.
Auch bei der Wiederherstellung die Lichtwerte erst nach frischer passender
Rückmeldung als wiederhergestellt gewertet; eine Versandbestätigung genügt nicht.

Abschluss bestätigt: ursprünglich eingeschaltetes Licht und Helligkeit wieder
hergestellt; sieben ursprüngliche Regeln mit einer eingeschalteten Regel,
identische aktive/gespeicherte Prüfsumme, Mesh bereit, Wiederanlauf armed und
kein Laufzeitfehler. Original-YAML ausschließlich mit fortlaufender Revision
bytegleich wiederhergestellt; lokaler Originalentwurf bytegleich erhalten.
Testregeln entfernt, eigene MQTT-/Dateiaufzeichnung beendet und beide temporären
Module samt Statusvariablen entfernt. Originalereignisse und Gerätezuordnungen
unverändert. Keine Implementierung, Fremdquellen oder Versionsmetadaten geändert.

Grenzen: reale Gerätewirkung mit erneuten Uhrzeitauslösern, keine wiederholten
physischen Taster-/Bewegungsmeldungen, Mindestdauer oder zusätzlichen Bedingungen.
Die erste abweichende Geräterückmeldung bleibt eine offene Beobachtung; die
erfolgreiche Wiederholung erklärt ihre Ursache nicht.

Nur Dokumentation geändert. Kodierungs- und Strukturprüfung, offizielle
StylePHP-Prüfung der 15 eigenen JSON-Dateien in isolierter Kopie sowie PHP CS
Fixer im Prüfmodus erfolgreich. UTF-8 ohne BOM, LF und git diff --check sauber.
Unveränderte Offline-Laufzeitsuite nicht erneut ausgeführt. Kein Commit/Push.

## Agenda 6: Bestätigung der korrigierten Spaltenbreite am 03.11.2026

Nach gemeldetem Modulupdate stimmen lokale und hochgeladene form.json im
Hashvergleich überein; alle vier Regelschalterspalten verwenden 245 Pixel.
Nach angekündigter Rechnerbedienung das Formular in der nativen Testkonsole
frisch geöffnet und die Geräte- sowie Zeitübersicht mit dem Mausrad erreicht.
Bei rund 1400 Pixel Fensterbreite ist „Im Entwurf eingeschaltet“ in beiden
Übersichten vollständig lesbar, ebenso die übrigen Spaltentitel. Kein
horizontaler Überlauf dieser Übersichten. Keine neue Funktionslogik geändert.

Der Originalentwurf bleibt im Hashvergleich unverändert. Gespeicherter Stand,
eine eingeschaltete Regel, Mesh und fehlerfreier Automatikbetrieb bleiben
bestätigt. Keine Geräteabfrage zum Laden des Katalogs, keine Speicherung,
Aktivierung, Löschung oder Schaltaktion. Die Übersichten sind weiterhin leer;
keine zusätzliche Prüfung von Zeileninhalten oder Löschdialogen. Nur die beiden
sichtbaren Hauptübersichten wurden nativ geprüft, nicht weitere Fensterbreiten.
Prüftab geschlossen und vorherige Such-/Modulansicht wiederhergestellt.
Nur Dokumentation geändert; Kodierungs- und Strukturprüfung sowie UTF-8/LF und
git diff --check erfolgreich. Unveränderte Funktions-/Stilprüfungen nicht
erneut ausgeführt. Kein Commit/Push.

## Agenda 6: Native Sichtprüfung am 03.11.2026

Nach vorangekündigter Rechnerbedienung die vorhandene Testinstanz in der nativen
Symcon-9.1-Konsole geöffnet. Hochgeladene Formular- und Übersetzungsdatei per Hash
mit dem vorherigen lokalen Stand abgeglichen. Keine Installation oder Aktualisierung
auf den laufenden Systemen vorgenommen. Die erste von zwei Konsolenverbindungen
war produktiv und wurde nicht bedient; Prüfung ausschließlich in der Testkonsole.

Bestätigt: Formular öffnet ohne Fehlermeldung. Bearbeitungsstand, fehlender
Gerätekatalog und tatsächlicher Z2M-Betrieb sind getrennt verständlich angezeigt.
Der ursprüngliche Automatikbetrieb meldet weiterhin eine eingeschaltete Regel,
gespeicherten Stand, Wiederanlauf und Mesh ohne Laufzeitfehler. Mausradnavigation
zwischen Geräte- und Zeitbereich funktioniert. Die neue deutsche Spaltenüberschrift
ist eingebunden, wird bei rund 1400 Pixel Fensterbreite mit 170 Pixel Spaltenbreite
jedoch in beiden Übersichten abgeschnitten. Deshalb lokal auf 245 Pixel erhöht,
auch in den beiden weiteren Regelübersichten. Bestehenden ConfigurationFormHelper
und zentrale Breitenvorgaben geprüft; keine neue Layoutlogik.

Grenzen: Der lokale Entwurf besitzt derzeit keinen geladenen Katalog; beide
Regelübersichten sind leer. Kein Ladevorgang, kein Erstellen oder Bearbeiten,
kein Bestätigen eines Löschdialogs und keine Änderung an gespeicherten Regeln.
Der Löschtext ist nur im eingebundenen Formular geprüft. Die korrigierte Breite
benötigt ein Modulupdate und erneute native Sichtprüfung; die bereits sichtbare
Kürzung ist kein bestandener Breitennachweis.

Der ursprüngliche lokale Entwurf bleibt im Hashvergleich unverändert. Der
Prüftab wurde geschlossen. Kodierungs-/Strukturprüfung, bestehende Formular-
Größentests, offizielle StylePHP-Prüfung der 15 eigenen JSON-Dateien und PHP CS
Fixer im Prüfmodus erfolgreich; UTF-8 ohne BOM, LF und git diff --check sauber.
Keine Funktionslogik geändert; unveränderte vollständige Laufzeitsuite nicht
erneut ausgeführt. Kein Commit/Push.

## Agenda 6: Benutzerführung und Dokumentation am 03.11.2026

Formulartexte und deutsche Übersetzungen, Modul-/Library-Anleitung, sichtbare
Einstellungen und Statusvariablen gegen Implementierung und Praxisberichte
abgeglichen. Vorhandenen ConfigurationFormHelper geprüft; kein neuer Helper
oder zweiter Formularpfad erforderlich. Lokaler Ausgangsarbeitsbaum sauber.

Die Benutzeranleitung beginnt jetzt bei der Einrichtung und führt zur ersten
Regel sowie Übernehmen, Prüfen, Speichern und Aktivieren. Vorhandene Themen
wurden in Bedienreihenfolge geordnet und über Einstiegspfade verlinkt.
Widersprüchliche Alttexte zu automatischer Statusprüfung, numerischen Aktionen,
Gruppen, Zeitbereich und Wiederanlauf korrigiert. Alle fünf Properties und alle
15 registrierten Statusvariablen sind beschrieben; die Skriptreferenz enthält
die gebräuchlichen Aufrufe mit vollständigen nativen Parametern, Rückgabebedeutung,
Regelkennung und Seiteneffekten. Ablaufaufzeichnung nach DEBUG.md verschoben.

Die deutsche Listenspalte heißt ausdrücklich „Im Entwurf eingeschaltet“ und
erhält dafür 170 Pixel statt 80. Der Löschdialog erklärt die Wirkung des späteren
Speicherns. „Revision“ wird als „Standnummer“ bezeichnet. Keine neuen
Callbacks, Abläufe oder Debuglogik und keine Änderung an Erweiterung/Build.

COMPATIBILITY.md trennt die praktisch belegte Version 2.14.2-dev vom
Schnittstellenziel 2.14.2 und anderen ungeprüften Versionen. Die genaue Git-
Revision des Entwicklungsstands ist unbekannt. Vorhandene Z2MVersion-Variablen
auf Test- und Produktiv-Symcon ausschließlich lesend abgefragt: ein nicht leerer
Wert bestätigt 2.14.2-dev, leere Werte bleiben unbekannt. Keine MQTT-Anfrage,
kein Formular geöffnet, keine Konfigurationsänderung, Installation oder
Geräteaktion auf laufenden Systemen. Offizielle Z2M-Erweiterungsdokumentation
und Paketmetadaten des Referenzziels nur als Schnittstellenquelle geprüft.

Grenzen: lokale Text-/Formularprüfung und dokumentierter Versionsnachweis,
keine neue native Sicht-/Bedienprüfung und keine zusätzliche Versionsabnahme.
Native Symcon-9.0-Abnahme, weitere Z2M-Versionen und breitere Geräte-/Gruppen-
prüfung bleiben ausdrücklich offen. Keine Veröffentlichung oder Metadatenänderung.

Abschluss: php tests/run.php vollständig erfolgreich, einschließlich PHP-/JS-
Integration, Formulargrößenprüfung und 124 JavaScript-Tests. Struktur- und
Kodierungsprüfung eingeschlossen; offizielle StylePHP-Prüfung der 15 eigenen
JSON-Dateien in isolierter Kopie sowie PHP CS Fixer im Prüfmodus erfolgreich.
Geänderte Markdown-Verweise und Anker, statische Formularübersetzungen,
UTF-8 ohne BOM, LF und git diff --check geprüft. Das sprachneutrale „OK“ benötigt
keine Übersetzung. Ein erster Lauf traf während der Dateibearbeitung auf eine
unvollständig neu geschriebene Formular-Datei; der anschließend vollständig
wiederholte Lauf auf dem fertigen Stand ist erfolgreich. Keine Tests oder
Fremdquellen geändert und kein Commit/Push.

## Agenda 5: Reale gemeinsame Geräteaktion am 03.11.2026

Mit ausdrücklichem Praxisauftrag über Test-Symcon am gemeinsam genutzten
Z2M-Empfänger geprüft. Keine Erweiterung installiert, kein Neustart und keine
Bedienung des Rechners erforderlich. Vorherigen YAML-Text, lokalen Originalentwurf,
Betriebszustand und rohe Gerätewerte außerhalb des Repositorys gesichert.

Eine ausgeschaltete temporäre Regel mit einem einzigen set-Schritt enthält
Einschalten, Helligkeit 120 und Blau als verschachtelten hex-Farbwert. Über die
öffentlichen Editor- und Übernahmemethoden erstellt, in Z2M geprüft und gespeichert.
Diese Regel einmalig gestartet, ohne den gesamten Teststand zu aktivieren.
Der Regellauf meldet Schritt 1 und anschließend abgeschlossen ohne Fehler.
Die zeitlich begrenzte MQTT-Aufzeichnung ordnet Übergabe und Bestätigung
demselben Lauf und Schritt zu. Ihre ausdrückliche Grenze bleibt sichtbar:
Eine angenommene Befehlsübergabe bestätigt allein noch keine Gerätewirkung.

Die zusätzliche frische Geräteabfrage bestätigt eingeschaltet, Helligkeit 120
und die vom Geräte-Converter gelieferten XY-Werte für Blau. Auch die tatsächlichen
Symcon-Variablen melden Ein, rund 47 Prozent und den blauen RGB-Wert. Die
Helligkeitsskalen unterscheiden sich: 0 bis 254 im Z2M-Gerätebefehl gegenüber
Prozent in der vorhandenen Symcon-Geräteinstanz. Kein SetValue zur Simulation.

Zur Wiederherstellung zunächst ursprüngliche rohe Helligkeit und XY-Farbe gesetzt
und deren Geräte-Rückmeldung mit der Sicherung verglichen, danach wieder
ausgeschaltet. Farbe, Helligkeit, Aus-Zustand und Symcon-Werte entsprechen dem
Ausgangsstand. Die Testregel entfernt; ursprünglichen YAML-Inhalt ausschließlich
mit fortlaufender Revision geprüft und gespeichert. Nur den wiederhergestellten
Originalstand reaktiviert: sieben Regeln, eine eingeschaltet, identische aktive
und gespeicherte Prüfsumme, Mesh bereit, Wiederanlauf armed, kein Laufzeitfehler.
Der andere Empfänger behält seinen gespeicherten Stand und Betrieb.

Lokaler Originalentwurf bleibt bytegleich, Status der Originalinstanz 102.
Eigene MQTT-Aufzeichnung automatisch abgelaufen, native Dateiaufzeichnung beendet
und beide temporären Prüfmodule samt Statusvariablen entfernt. Keine
Originalereignisse oder Gerätezuordnungen geändert. Keine Implementierung geändert.

Grenzen: dieser Nachweis gilt für die geprüfte Farbleuchte und den lokalen
gemeinsamen Befehl. Kein zusätzlicher realer Farbtest über Mesh oder an Gruppen,
keine vollständige Geräte-/Converter- oder Symcon-9.0-Abnahme.

Nur Dokumentation geändert. Kodierungs- und Strukturprüfung, offizielle StylePHP-
Prüfung der 15 eigenen JSON-Dateien in isolierter Kopie und PHP CS Fixer im
Prüfmodus erfolgreich; UTF-8 ohne BOM, LF und git diff --check bestätigt.
Unveränderte Offline-Laufzeittests nicht erneut ausgeführt; kein Commit/Push.

## Agenda 5: Native Editor- und Speicherprüfung am 03.11.2026

Nach gemeldetem Modul- und Erweiterungsupdate über Test-Symcon geprüft.
Relevante hochgeladene PHP-/Formulardateien entsprechen den lokalen Hashes;
beide erreichbaren Z2M-Erweiterungen melden den mitgelieferten Build. Die
Testanbindung verwendet weiterhin gemeinsam genutzte Z2M-Systeme. Produktiv-
Symcon-MCP nicht verwendet, keine Erweiterung installiert und kein Neustart.

Zwei temporäre Automationsmodule verwenden die bestehenden MQTT-Verbindungen
und einander als Partner. Ursprünglichen lokalen Entwurf, exakten Z2M-YAML-Text,
Prüfsummen, Regelschalter, Partnerfreigaben und Betriebszustand außerhalb des
Repositorys gesichert. Ausgangspunkt am Speicherempfänger: sieben Regeln,
eine eingeschaltet. Das zweite System behielt seinen gespeicherten Stand.

Bildschirmbedienung vorher angekündigt und Computer-Use-Skill verwendet.
Native Symcon-9.1-Konsole: ausgeschaltete Zeitregel erstellt, Aktionsart für
mehrere Eigenschaften gewählt und eine vorhandene Farbleuchte ausgewählt.
Status, Helligkeit und Farbe nacheinander über die getrennten Auswahlfelder
und den Hinzufügen-/Ersetzen-Button zusammengefügt. Den tatsächlichen nativen
Farbwähler geöffnet, Farbton und Sättigung/Helligkeit per Maus gewählt und
in den Schritt übernommen. Gemeinsame Wartezeit gesetzt. Der echte native
Übernehmen-Button erzeugt einen einzelnen set-Schritt mit drei typisierten
Werten; die sieben Originalregeln sind inhaltlich unverändert.

Vorhandene Regel nativ erneut ausgewählt und Schrittdialog geöffnet: Gerät,
Aktionsart, alle Werte und Wartezeit korrekt vorbelegt. Ungültige Helligkeit
über die öffentliche Modulgrenze abgewiesen, mit sichtbarem Hinweis und
unveränderter Zusammenstellung. Helligkeit nativ ersetzt, entfernt und wieder
hinzugefügt; die übrigen Werte bleiben erhalten. Beim erneuten Auswählen der
Farbe zeigt auch das native Farbfeld den übernommenen Farbwert. Bearbeitete
Regel bleibt ausgeschaltet und wird über den nativen Button übernommen.

Öffentliche Modulmethoden prüfen zusätzlich dieselben drei Eigenschaften als
entferntes Ziel mit tatsächlichen Partner-Variablen-IDs. Vorbelegung und
native Create-Callback-Ausführung erzeugen einen ausgeschalteten Entwurf mit
eindeutigem Zielsystem. Dessen Z2M-Prüfung erfolgreich; dort nichts gespeichert
oder aktiviert. Eine wiederverwendbare Definition am Speicherempfänger erhält
denselben vollständigen gemeinsamen Befehl. Diese zusätzliche Bearbeitung und
Partnerprüfung erfolgten über Modulmethoden, nicht als vollständige Mausprüfung.

Der Empfänger bestätigt Validierung und Speicherung von ausgeschalteter
Testregel und Definition. Aus Z2M erneut geladen: drei Werte einschließlich
des exakten RGB-Werts und gemeinsame Wartezeit in Regel und wiederverwendbarer
Definition erhalten; Vorbelegung über GetEditorItemForm geprüft. Keine
Testregel aktiviert oder einmalig gestartet. Der Speichertest unterbrach den
Automatikbetrieb kurz; anschließend Originaltext ausschließlich mit fortlaufender
Revision geprüft und gespeichert. Regelinhalt, Partnerfreigaben und sonstige
Angaben entsprechen wieder der Sicherung. Erst diesen ursprünglichen Stand
reaktiviert; frischer Status bestätigt sieben Regeln, eine eingeschaltet,
identische gespeicherte/aktive Prüfsumme, Mesh, Wiederanlauf armed und keinen
Laufzeitfehler. Der andere Empfänger behält Revision, Prüfsumme und Betrieb.

Debugdateien bestätigen Vorbelegung, Eigenschaftsanzahl, Zurückweisung,
Ersetzen, Entfernen, Wartezeit und ZweitesSystem=true bei der Partnerprüfung.
Der Debugtext enthält weder den Testnamen noch Farbcode oder command-Kodierung.
Originaler Symcon-Entwurf bleibt bytegleich. Eigene Dateiaufzeichnungen beendet,
Prüfkopien samt Statusvariablen entfernt und nativen Prüf-Tab geschlossen.
Originalinstanz danach erneut mit Status 102 und bestätigtem Betrieb gelesen.

Grenzen: kein realer Farb-/Mehrfach-Schaltbefehl und keine physische Converter-
Prüfung, keine vollständige Gruppen- oder Symcon-9.0-Abnahme. Nach animiertem
Scrollen zunächst versehentlich einen leeren Hinzufügen-Dialog geöffnet und
abgebrochen; die Bearbeitung danach gezielt über das tatsächliche Zahnradsymbol
geprüft. Ein außerhalb des Fensters liegender Button erforderte weiteres Scrollen.
Das sind Grenzen der Testbedienung, keine belegten Modulfehler. Wiederholte
Auswahltexte und lange Gerätenamen werden in den nativen Auswahlfeldern gekürzt;
die Menüs zeigen die vollständige Auswahl. Keine Implementierung geändert.

Für diesen Praxisnachweis ausschließlich Dokumentation aktualisiert. Zentrale
Kodierungsprüfung, validate_structure, offizielle StylePHP-Prüfung der 15 eigenen
JSON-Dateien in isolierter Kopie sowie PHP CS Fixer im Prüfmodus erfolgreich.
Neue Texte UTF-8 ohne BOM mit LF; git diff --check sauber. Unveränderte Offline-
Laufzeittests hier nicht erneut ausgeführt. Keine Änderungen an Fremdquellen,
Helpern oder Versionsmetadaten; kein Commit/Push.

## Agenda 5: Gemeinsame Gerätebefehle und Farben

Lokal am 03.11.2026 gemäß ADR 0019 implementiert. Vorhandene ConfigurationForm-
und DebugFlow-Helper sowie Geräte-/Partnerrechte und der bisherige Aktionspfad
geprüft. Fachlogik bleibt in EditorCommands; zentrale Quellen unverändert.
Der native Dialog ergänzt den Modus „Mehrere Eigenschaften gemeinsam setzen“.
Explizites Hinzufügen/Ersetzen/Entfernen bearbeitet eine begrenzte Zusammenstellung;
ein Schritt erzeugt genau einen set-Befehl. Bestehende Aktionen und Folgen werden
vollständig vorbelegt. Unbekannte oder nicht vollständig erlaubte Mehrfachwerte
bleiben in der erweiterten Metadatenbearbeitung erhalten. Alte Einzelwertaufrufe
können mehrere Eigenschaften nicht stillschweigend verkürzen.

Die Erweiterung liest schreibbare color_xy/color_hs-Composites als Elternwerte.
Ein zuerst roter Katalogtest zeigte die bisherige falsche Verflachung von x/y,
hue/saturation und unbekannten Composite-Kindern. Nach Korrektur bleiben diese
Kinder intern. Native SelectColor-Ganzzahlen werden als verschachteltes hex-
Objekt weitergegeben. Keine eigene Farbkonvertierung, kein neuer Schema- oder
Runtime-Vertrag. Farbtemperatur und Farbe desselben Kanals, Szenen im gemeinsamen
Befehl und binäres TOGGLE werden abgewiesen; Gruppeneigenschaften nicht erfunden.

Fokussierte PHP-Prüfung führt echte native Dialogcallbacks mit synthetischen
Variablen aus: Erstellen, Vorbelegung, numerischen Wert ersetzen, Farbe entfernen,
letzte Eigenschaft entfernen, Einzelfarbe und Wechsel von Einzel- zu Mehrfachwert.
Sie prüft Anzeigevariablen, Schrittweiten, Zahlen-/Farbgrenzen, nicht unterstützte
Strukturen, zu große Kodierungen und nachträglich entzogene Schaltrechte. Eine
wiederverwendbare Definition lädt die exakte Farbe erneut. Zwei Gateways mit
gleicher synthetischer IEEE und verschiedenen Topics prüfen die tatsächliche
Partner-Variablen-ID, native Übernahme, Vorbelegung und vollständige Ablehnung
bei Verlust eines einzelnen Schaltrechts. Debug enthält weder Choice noch
Farbcodes oder Namen. Der Großkatalog bleibt bei 611.982 Bytes unter 750 KiB.
Ein zusätzlicher Belastungstest fand zu lange wiederholte Beschreibungen bei
16 gemeinsamen Befehlen. Kompakte Zeilen nennen jetzt Gerät und Anzahl; der
Dialog zeigt weiterhin jede Eigenschaft mit Wert. Der 16-Zeilen-Test benötigt
760.976 Bytes und bleibt unter dem bisherigen 750-KiB-Normalbudget. Ein weiterer
Test mit 16 Befehlen nahe der erlaubten 1200-Byte-Wertegrenze belegt 850.064
Bytes unter einem eigenen 900-KiB-Sicherheitsbudget. Dieses nutzt reservierten
Spielraum für größere Werte und lässt mindestens 124 KiB Abstand zur bekannten
1-MiB-Ausgabebegrenzung; das bisherige Normalbudget wurde nicht erhöht.

Die vollständige PHP-/JavaScript-Integration ergänzt Erstellung, Validierung,
Speicherung und Wiederladen eines ausgeschalteten Farbbefehls mit drei Werten;
anschließendes Entfernen erhält die vorherigen synthetischen Regeln. Zusätzliche
Runtime-/Mesh-Prüfungen betrachten die vollständige Übergabe als einen Schritt
mit unveränderten typisierten Werten und einmaliger Verzögerung.

Ein vollständiger Prüflauf fand den fehlenden neuen Kontext in alten Auslöser-/
Bedingungs-Callbacks. Diese liefern jetzt ebenfalls sämtliche Argumente, damit
die nativen Symcon-Wrapper nicht an fehlenden optionalen PHP-Parametern scheitern.
Die Szenenregression verwendet nun ebenfalls den echten Wrapper statt einer
lokalen Ersatzclosure. Kein MCP-, Bildschirm- oder Installationszugriff und
keine echten Gerätebefehle. Native Bedienung (einschließlich Breite/Scrollen),
Symcon 9.0 und tatsächliche Farbconverter bleiben praktisch zu prüfen.

Abschlussnachweis: php tests/run.php erfolgreich, einschließlich der echten
synthetischen PHP-/JavaScript-Speicherintegration und 124 JavaScript-Tests.
Die abschließenden Kataloggrenzen (Propertylänge und nichtnegative Zugriffsbits)
sowie Farb-/Schaltrechte wurden zusätzlich gezielt erneut geprüft. Struktur,
zentrale Kodierungsprüfung, aktuelles generiertes Artefakt, offizielle StylePHP-
Prüfung von 15 eigenen JSON-Dateien und PHP CS Fixer 3.95.27 im Prüfmodus
bestehen. Neue/geänderte Texte sind UTF-8 ohne BOM mit LF; git diff --check
sauber. Fremdquellen, zentrale Helper und Versionsmetadaten unverändert;
kein Commit oder Push.

## Agenda Punkt 4: Erneute Auslösung einer laufenden Automation

Lokal am 03.11.2026 umgesetzt gemäß ADR 0018. Vorhandene ConfigurationFormHelper-
und DebugFlowHelper-Quellen sowie verwandte Schedule-/EditorWork- und Runtime-
Pfade geprüft; zentrale Quellen unverändert. Fachliche Ablaufentscheidungen
bleiben in der Library. Keine Änderung öffentlicher Methodensignaturen.

EditorRetrigger erweitert den bestehenden Optionsvertrag und die geschützte
Eingabe. Beide nativen Bereiche erhalten dieselben drei Optionen und Hilfetexte.
Fehlende Angabe älterer Aufrufer erhält eine gespeicherte Auswahl, statt diese
stillschweigend auf ignore zurückzustellen. Metadatenbearbeitung nicht komplett
darstellbarer Regeln erhält die Laufzeitangabe. Synthetische native Callbacks
prüfen offene Eingaben, Übernehmen, Auswahlwechsel, Vorbelegung und Kopieren.
Unbekannte Optionen werden ohne Entwurfsänderung abgewiesen. Die bestehende
PHP-/JavaScript-Integration prüft neue und geänderte Auswahl bis zum Wiederladen
aus dem echten synthetischen Empfängerspeicher.

Runtime.wait behält den vorhandenen Timerbesitz und kann nur seine aktuelle
Schrittwartezeit neu setzen. Keine zweite Timerengine und kein Warteschlangen-
speicher. Neustart wartet einen bereits begonnenen Dispatch ab und hält dafür
maximal einen flüchtigen Wunsch im bisherigen Lauf. Stopp und geändertes Speichern
löschen ihn. Fehler oder Timeout der Übergabe verwerfen den Wunsch, statt einen
unsicheren Befehl automatisch zu wiederholen. Frische Bedingungen gelten bei
Erneuerung und tatsächlichem Neustart; Sekundensperre und Parallelitätsgrenzen
bleiben wirksam. Einzelstarts werden weder ersetzt noch automatisch verlängert.

Zuerst fehlender Schema-/Editorvertrag durch fokussierte rote Tests belegt,
anschließend implementiert. Deterministische virtuelle Uhr prüft erneuerte
Fristen, unveränderte erste Aktionen, neue Laufkennung, alte verworfene Reste,
unerfüllte Bedingungen, Sekundensperre, Einzelstart und Stopp. Laufende native
Handoffs werden kontrolliert aufgelöst, abgewiesen oder bis Timeout offengehalten.
Zusätzlicher Zwei-Erweiterungs-Mesh-Test hält die echte Result-Nachricht zurück:
kein zweites Execute bis zur Bestätigung, danach genau ein neuer Lauf; bei
verlorenem Result kein Neustart. Uhrzeitauslöser und Wiederanlauf mit gespeicherter
Option geprüft; eine unterbrochene erneuerte Wartezeit wird nicht fortgesetzt.

Ein zusätzlicher Diagnosevertrag fand zunächst den fehlenden Schrittbezug einer
Wartezeit-Erneuerung. Die Wartezeit erhält jetzt den tatsächlichen Schrittkontext
einschließlich Folgenbezug; der MQTT-Datensatz nennt dieselbe Laufkennung, Schritt
und volle Verzögerung. Gemeinsamer fester Katalog, keine freien Werte oder Namen.

Kein MCP-/Bildschirmzugriff, keine Installation und keine Schaltanforderung an
reale Geräte. Native Symcon-9.0-/9.1-Bedienung und realer Empfänger bleiben nach
gemeinsamem Update praktisch zu prüfen. Bestehende Statusabfrage-/Editor-
Kollisionsmeldung aus dem vorherigen Praxisauftrag hier nicht verändert.

Abschluss am 03.11.2026: php tests/run.php vollständig erfolgreich, einschließlich
synthetischer nativer Formularskripte, PHP-/JavaScript-Speicherintegration und
121 JavaScript-Tests. Struktur- und zentrale Kodierungsprüfung sowie aktuelles
generiertes Artefakt bestätigt. Offizielle StylePHP-Prüfung der 15 eigenen
JSON-Dateien in isolierter Kopie und PHP CS Fixer 3.95.27 im Prüfmodus erfolgreich.
Großkatalog-Formular: 611.982 Bytes unter 750 KiB. Neue/geänderte Texte UTF-8
ohne BOM mit LF; git diff --check sauber. Keine Fremdquellen, zentralen Helper
oder Versionsmetadaten geändert; kein Commit/Push.

## Erneute Auslösung: Editor-/Speicherprüfung am 03.11.2026

Nach Modulupdate zunächst lesend Dateiidentitäten, laufenden Erweiterungsbuild
und Status bestätigt. Mitgelieferte und installierte Kennung waren identisch.
Ausdrücklicher Praxisauftrag für Test-Symcon am gemeinsam verwendeten Z2M:
deaktivierte Prüfregel speichern und den ursprünglichen Betrieb wiederherstellen,
keine Geräte schalten. Kein Produktiv-Symcon-MCP verwendet.

Temporäre Modulkopie mit eigener Debugdateiaufzeichnung angelegt; ursprünglicher
lokaler Entwurf blieb unangetastet. Gespeicherten Originaltext anhand der frischen
SHA-256-Prüfsumme exakt rekonstruiert und außerhalb des Repositorys gesichert.
Sieben Originalregeln, eine eingeschaltet, sowie Partnerfreigabe und Wiederanlauf
bildeten den Wiederherstellungsstand. Keine Extension neu installiert und kein
Z2M-Neustart durchgeführt.

Bildschirmbedienung angekündigt und Computer-Use-Skill verwendet. Die separate
native Testkonsole und ausschließlich die Modulkopie bedient. Eine Zeitregel
über die öffentliche CopyAutomation-Methode ausgeschaltet kopiert; Name und
Optionen anschließend nativ bearbeitet. Alle drei verständlichen Menüeinträge
waren vorhanden. Von-vorn-beginnen und Wartezeit-zurücksetzen jeweils nativ
ausgewählt und über den echten Übernehmen-Button in den Entwurf geschrieben.
Name, ausgeschalteter Regelschalter und unveränderte sieben Originalregeln
anschließend per Formular-/Entwurfsvergleich bestätigt. Hilfetext und volle
Feldbreite waren in der verwendeten Konsole lesbar. Eine vorhandene Geräte-
regel zusätzlich per öffentlicher Auswahlmethode geöffnet: bisheriger Default
ignore, alle drei Optionen und bearbeitbares Feld korrekt vorbelegt.

Speicherprüfung per MCP: jeweils aus der nativ erzeugten Testregel ein Dokument
für restart, extend und ignore vorbereitet. Ausschließlich Testoption und
fortlaufende Revision variiert; die Original-Partnerfreigabe statt der im
unverknüpften Testmodul normalisierten leeren Liste erhalten. Alle sieben
Originalregeln vor jeder Übertragung inhaltlich verglichen. Jedes Dokument
über ValidateYaml geprüft und über StoreYaml gespeichert; anschließend über
LoadAutomationEditor geladen und die Testregel ausgewählt. Z2M bestätigte je
acht gespeicherte Regeln bei gestoppter Ausführung. GetConfigurationForm und
geladener Entwurf enthielten jeweils genau die gespeicherte Option und den
ausgeschalteten Regelschalter. Die Testregel wurde nie aktiviert oder gestartet.

Eine direkt nach GetConfigurationForm gesendete Folgeprüfung traf auf die
automatische Statusabfrage und erhielt keine neue Auftragskennung. Der Debug-
verlauf belegte den gleichzeitig laufenden Statusauftrag. Zunächst den Original-
stand wiederhergestellt, danach die restlichen Prüfungen mit abgewarteter
Statusbestätigung durchgeführt. Keine Quellcodeänderung wegen dieses bereits
bekannten Ablaufs; keine ungeprüfte automatische Wiederholung von Speicher-
oder Aktivierungsbefehlen.

Nach Abschluss Testregel durch Wiederherstellung entfernt. Originaltext war
bis auf fortlaufende Revision bytegleich zur Sicherung; dessen berechnete
Prüfsumme entsprach dem gespeicherten und aktiven Z2M-Stand. Erst den ursprüng-
lichen Sieben-Regel-Stand wieder aktiviert. Frische Antwort bestätigte eine
eingeschaltete Regel, Mesh-Bereitschaft, Partneranzahl und Wiederanlauf armed,
ohne Fehler. Ungültige Testkopie mit retrigger=parallel danach nur validiert:
invalid_schema mit /automations/7 und enum; gespeicherte Revision und laufender
Betrieb blieben unverändert. Gültigen Originaltext erneut geprüft.

Debug bestätigte feste deutsche Übernahmemeldungen und Erneuerungsart sowie den
Schemafehler. Der eingegebene Testname kam in der Debugdatei nicht vor. Eigene
Dateiaufzeichnung beendet und temporäre Modulkopie samt Statusvariablen per MCP
entfernt. Frische Abfrage des Originalmoduls: nur die ursprüngliche Instanz,
Status 102, lokaler Entwurf bytegleich, gespeicherte/aktive Prüfsumme identisch.

Prüfgrenzen: native Zeitregel-Auswahl und lokale Übernahme in Symcon 9.1;
Prüfung/Speicherung/Wiederladen und Geräte-Vorbelegung über öffentliche Methoden.
ignore wurde als Default nativ beobachtet und als gezielt vorbereiteter Wert
gespeichert. Keine neue geräteausgelöste Testregel, keine vollständige Abnahme
weiterer Fensterbreiten oder Symcon 9.0. Tatsächliche erneute Auslösung laufender
Automationen bleibt durch die synthetischen Runtime-/Mesh-Tests belegt; in diesem
Praxisauftrag keine Testausführung, kein Einzelstart und kein Gerätebefehl.

Abschlussprüfungen: php tests/run.php erneut vollständig erfolgreich,
einschließlich PHP-/JavaScript-Speicherintegration und 121 JavaScript-Tests.
Struktur-/Kodierungsprüfung und Aktualität des generierten Artefakts bestätigt.
Offizielle StylePHP-Prüfung der 15 eigenen JSON-Dateien in isolierter Kopie
und PHP CS Fixer im Prüfmodus erfolgreich. Ausschließlich Praxisdokumentation
geändert; UTF-8 ohne BOM mit LF und git diff --check sauber. Keine Änderungen
an Implementierung, Fremdquellen, Helpern oder Versionen; kein Commit/Push.

## Agenda Punkt 3: Kalendertage und mehrere Uhrzeiten

AutomationEditorCalendar ergänzt eine Liste von 1–8 normalisierten, verschiedenen
Uhrzeiten und unabhängige Wochentagsfelder. Keine zweite Liste im YAML: jede
Uhrzeit bleibt ein vorhandener time-Auslöser. Der neue Schema-1-Bedingungstyp
weekday prüft den aktuellen Kalendertag seiner Zeitzone; bisherige Fenster-
Starttage bleiben unverändert. Beide werden als UND-Bedingungen mitgezählt.
Der bestehende Laufzeitpfad prüft die neue Klausel auch vor verzögerten Aktionen
und beim Einzelstart. Debugereignis im bestehenden festen Meldungskatalog.

Vorhandene Helper einschließlich DateHelper geprüft; ConfigurationFormHelper
und DebugFlowHelper bleiben unverändert. DateHelper formatiert tolerante
Datumswerte und ersetzt keinen strikten Uhrzeitvertrag. Schema-, Automations-
und Zeitzonenlogik bleiben lokal. Quellen und kanonisches Schema werden regulär
mit npm run build:extension erzeugt; neue Buildkennung ohne Versionsanhebung.
Entscheidung und Updategrenzen in ADR 0017.

EditorTimes verwendet ausschließlich List.values, nie ein paralleles value.
Die erste Callback-Prüfung deckte eine solche falsche Befüllung auf: Eine frische
Liste wurde sichtbar erhalten, beim Übernehmen aber durch den alten value-Wert
ersetzt. Befüllung und Live-Updates korrigiert; echte generierte Skripte prüfen
das Erfassen, Wiederöffnen und vollständige Übernehmen. Einzelzeit-Aufrufe
ohne Liste bleiben unterstützt; eine gespeicherte Mehrfachzeit kann nicht
durch einen alten Einzelzeit-Aufruf unbeabsichtigt gekürzt werden.

tests/editor-calendar.php nutzt die vorhandene synthetische Editorgrenze in
einem eigenen Prozess, damit große Formular-Snapshots nicht zusammen mit den
50/100-Regel-Prüfungen dauerhaft gehalten werden. Native Skripte decken neue
Regeln, Bearbeiten, Löschen bis zur ungültigen leeren Liste, Vorbelegung von
Geräte-/Zeit-/Astro-Regeln, offene Eingaben, Auswahlbindungen und veraltete
Aufrufe ab. Ungültige, doppelte und mehr als acht Zeiten, leere Tage, falsche
Zeitzonen und die gemeinsame 16-Bedingungsgrenze bleiben ohne Entwurfsänderung.
Unterschiedliche Zeitzonen je Zeit-Auslöser bleiben metadatenbearbeitbar und
inhaltlich erhalten. Keine stillschweigende Vereinfachung.

editor-calendar-integration.inc.php prüft den Weg vom tatsächlichen Formular-
Callback durch den realen JavaScript-Empfänger: deaktivierte Mehrfachzeit mit
unabhängigen Wochentagen erstellen, prüfen, speichern, wieder laden, bearbeiten,
erneut prüfen/speichern/laden und schließlich nur diese synthetische Regel
entfernen. Alle ursprünglichen synthetischen Regeln bleiben erhalten.

Neue JavaScript-Prüfungen: Schemafehler bei Tagen und Zeitzonen, 8/9-Zeitgrenze,
beide Uhrzeiten während der doppelten DST-Stunde, nicht erlaubter Tag,
abweichender lokaler Kalendertag, UND mit einem Nachtfenster-Starttag, Einzelstart,
erneute Prüfung nach Mitternachtsverzögerung und Wiederanlauf ohne Nachholen.
In der langen PHP-Integration hielten ältere Prüffälle ausgewertete Formular-
und Debugkopien unnötig fest. Diese werden nach ihrer letzten Prüfung freigegeben;
die originale Debugaufzeichnung und ihre abschließenden Datenschutz-/Ablauf-
Prüfungen bleiben erhalten. Keine Änderung der produktiven Speichergrenzen.

Keine Installation, kein MCP- oder Bildschirmzugriff und keine Geräteaktion.
Native Symcon-9.0-/9.1-Bedienung, schmale/breite Konsole und reale Ausführung
stehen nach gemeinsamem Modul- und Erweiterungsupdate noch zur Praxisprüfung an.

Abschlussprüfungen am 02.11.2026: php tests/run.php vollständig erfolgreich,
einschließlich PHP-/JavaScript-Speicherintegration und 111 JavaScript-Tests.
Fokussierte Editor- und Laufzeitprüfungen, Struktur- und zentrale Kodierungs-
prüfung, Artefaktvergleich, offizielle StylePHP-Prüfung der 15 eigenen JSON-
Dateien in isolierter Kopie sowie PHP CS Fixer 3.95.27 im Prüfmodus erfolgreich.
Großkatalog-Formular: 605.934 Bytes, unter der Grenze von 750 KiB. Geänderte
Textdateien UTF-8 ohne BOM mit LF; git diff --check sauber. Kein Commit/Push
und keine Änderung an Fremdquellen, zentralen Helpern oder Versionsmetadaten.

## Kalendereditor: Praxisprüfung am 02.11.2026

Auftrag: mehrere Uhrzeiten und unabhängige Wochentage über Test-Symcon erstellen,
bearbeiten, vorbelegen, prüfen und speichern, ohne Geräte zu schalten. Modul-
Dateihashes bestätigten den neuen Stand. Der gemeinsame Z2M-Empfänger meldete
noch den alten Erweiterungsbuild. Nach ausdrücklicher zusätzlicher Freigabe
die mitgelieferte Erweiterung auf genau diesem Empfänger aktualisiert. Neuer
Build und automatische Wiederaufnahme des bisherigen Betriebs bestätigt.

Bildschirmbedienung vorab angekündigt. Temporäre Modulkopie statt Überschreiben
des originalen lokalen Entwurfs. Gespeicherten Originaltext anhand der gemeldeten
SHA-256-Prüfsumme exakt gesichert; Sicherung und private Debugdaten außerhalb
des Repositorys. Sieben bestehende Regeln, davon eine eingeschaltet, und die
vorhandene Partnerfreigabe waren der Wiederherstellungsstand.

Die native Liste zeigte zunächst nur Bearbeiten: List.add und List.delete
fehlten, obwohl die Spalten und Callbacks vorhanden waren. Fokussierte Prüfung
der tatsächlich angebotenen Formularoptionen schlug zuerst fehl. Beide Optionen
für die kanonische sowie Geräte-/Zeitliste ergänzt; Prüfung danach erfolgreich.
Nur form.json nach Test-Symcon übertragen und das Formular frisch geöffnet.
Hinzufügen-Button und Löschen-Symbole waren sichtbar. Keine erneute Änderung
der Erweiterungsquellen durch diese Formularkorrektur.

Eine ausgeschaltete neue Zeitregel nativ erstellt: erste Uhrzeit im vorhandenen
Bearbeiten-Dialog ohne Sekunden geändert, zweite über Hinzufügen mit Sekunden
ergänzt. Unabhängige Wochentage Montag bis Freitag gewählt; Zeitfenster blieb
aus. Vorhandenes Gerät, schaltbarer Statuskanal und Wert im Aktionsdialog nur
als Definition ausgewählt. Über den nativen Erstellen-Button in den lokalen
Entwurf übernommen. Zwei normalisierte time-Auslöser und eine weekday-Klausel
bestätigt; alle sieben Originalregeln unverändert.

Prüfung, Speicherung und Wiederladen über die öffentlichen Modulfunktionen.
Z2M bestätigte den neuen gespeicherten Stand und blieb nach geändertem Speichern
wie vorgesehen gestoppt. Neue Regel weiterhin ausgeschaltet. Regelauswahl und
GetConfigurationForm bestätigten beide Zeiten, alle Tage, getrennte Zeitzonen,
ausgeschaltetes Zeitfenster, Aktion und Regelschalter. Danach über die öffentliche
SaveScheduledAutomation-Methode Uhrzeiten und Name geändert, Dienstag/Donnerstag
sowie UTC für die Tage gewählt; Uhrzeiten behielten ihre andere Zeitzone.
Erneut geprüft, gespeichert, geladen und alle Werte verglichen. Die native
Konsole zeigte anschließend ebenfalls beide geänderten Zeiten und UTC.

Zwei unmittelbar nach GetConfigurationForm ausgeführte Bearbeitungsaufrufe
überschnitten sich mit der verzögerten automatischen Statusabfrage. Sie wurden
mit editor_changed abgewiesen; Auswahlkennung und Entwurf blieben stabil. Nach
protokolliertem Abschluss der Abfrage funktionierte derselbe gebundene Aufruf.
Die allgemeine Schutzmeldung unterscheidet diesen laufenden Auftrag noch nicht
von einer tatsächlich veränderten Auswahl. Kein verlorener Inhalt; hier keine
zusätzliche Änderung dieses bestehenden Ablaufvertrags vorgenommen.

Testregel aus dem übertragenen Stand entfernt und Originaltext mit fortlaufender
Revision wieder gespeichert. Der Text war bis auf genau die Revision bytegleich
zur Sicherung. Erst diesen ursprünglichen Sieben-Regel-Stand wieder aktiviert;
die Testregel war zu keinem Zeitpunkt eingeschaltet oder aktiv. Frischer Status
bestätigte laufenden Betrieb mit der ursprünglichen eingeschalteten Anzahl,
identische gespeicherte/aktive Prüfsumme, Partnerfreigabe und Wiederanlauf.

Zusätzliche Negativprüfungen nach Wiederherstellung ohne Speicherung: doppelte
Uhrzeiten und leere unabhängige Tage über SaveScheduledAutomation abgewiesen,
lokaler Prüfentwurf bytegleich. Eine fehlerhafte Kopie über ValidateYaml an Z2M
gesendet; invalid_schema benannte die genaue Automations-/Bedingungsposition
weekdays mit minItems=1. Gespeicherter und aktiver Stand blieben erhalten.
Danach nur gültigen Originaltext erneut validiert. Debug enthielt verständliche
deutsche Ursachen, Uhrzeitenanzahl und Wochentags-/Zeitfensterkennzeichen, aber
nicht den eingegebenen Prüf-Namen. Eigene Debugdateiaufzeichnung beendet,
temporäre Modulkopie samt Statusvariablen entfernt. Originaler Symcon-Entwurf
bytegleich; keine Schaltanforderung oder Testausführung gesendet.

Prüfgrenzen: native Konsole 9.1 und bestehender realer Empfänger, keine reale
zeit-/wochentagsgesteuerte Geräteausführung, keine native Löschbetätigung und
keine vollständige Abnahme weiterer Fensterbreiten oder Symcon 9.0. Das Ändern
der bereits gespeicherten Gesamtregel erfolgte per öffentlicher Modul-API;
die nativen Erstellen-/Listen-/Aktionsdialoge und die sichtbare Vorbelegung
wurden separat bedient bzw. beobachtet. Offline-Nachweise ergänzen diese Grenzen.

Abschluss nach der Formularkorrektur: php tests/run.php vollständig erfolgreich,
einschließlich Speicherintegration, Struktur-/Kodierungsprüfung, aktuellem
Erweiterungsartefakt und 111 JavaScript-Tests. Offizielle StylePHP-Prüfung der
15 eigenen JSON-Dateien in isolierter Kopie sowie PHP CS Fixer im Prüfmodus
erfolgreich. Großkatalog-Formular: 606.021 Bytes, unter 750 KiB. Geänderte
Textdateien UTF-8 ohne BOM mit LF; git diff --check sauber. Keine Änderung
an Fremdquellen, zentralen Helpern oder Versionsmetadaten; kein Commit/Push.

## Agenda Punkt 2: Übersicht und Kopien

AutomationOverview verwaltet eine begrenzte, an den MQTT-Kontext gebundene
flüchtige Suche und Regelschalterfilter je Geräte-, Zeit- und weiterem Bereich.
Filter projizieren denselben vollständigen Entwurf in Listeneinträge und
Auswahloptionen. Die ausgewählte Regel wird bei abweichendem Filter als
Bearbeitungsausnahme in den Optionen gehalten. Es werden weder Auswahl-Token
noch Editorfelder neu gesetzt. Frische unübernommene Felder werden vor nativen
Filteraufrufen über den vorhandenen EditorWork-Pfad erfasst. Suchwörter werden
literal, ohne Beachtung der Groß-/Kleinschreibung verglichen; maximal 160 Zeichen.
Referenzierte Auslöser-, Bedingungs- und Zielgeräte einschließlich aufgelöster
Folgen werden berücksichtigt. Leere weitere Bereiche mit gesetztem Filter
bleiben zum Rücksetzen sichtbar. Bestehende stabile Regel-IDs bleiben maßgeblich.

CopyAutomation prüft Kontext, aktuelle Auswahlbindung, offenen Bearbeitungsstand
und die bestehende 100-Regel-Grenze. Die Regel wird mit neuer ID, eindeutigem
Namenszusatz und enabled=false über den bestehenden CommitEditorChange-Pfad
angelegt. Namen bleiben UTF-8 und höchstens 120 Zeichen lang. Aktionen und
Folgenverweise werden vollständig übernommen; keine neuen Folgendefinitionen,
Aktivierungen oder Laufzeitaufrufe. Nur der Filter der Kopierkategorie wird
zurückgesetzt, damit die neue ausgeschaltete Regel direkt sichtbar ist.

ConfigurationFormHelper und DebugFlowHelper wurden vorab geprüft und bleiben
unverändert eingebunden. Filter-/Regellogik ist librarylokal, da sie nur diese
Library verwendet. Native Symcon-9.0-Felder und rowCount werden verwendet;
kein erst in 9.1 verfügbarer onSelectionChanged-Handler, keine eigene Kachel.
Schema, MQTT-Protokoll, Erweiterung, Abhängigkeiten und Versionsmetadaten
bleiben unverändert. Öffentliche neue Wrapper erhalten vollständige Parameter.

Neue Nachweise in tests/editor-overview.inc.php laufen über tests/editor-work.php:
50 Regeln, gleiche Namen bei verschiedenen IDs, kombinierte Bereichsfilter,
Unicode-Suche, Auswahl außerhalb der Treffer, Erhalt frischer Eingaben und Token,
Kopiersperre, veraltete wiederholte Aufrufe, getrennte Filterrücksetzung,
120-Zeichen-Namen, Astro-, Geräte-, gemischte Auslöser, Bedingungen, Wartezeiten,
Folgenverweise und systemübergreifende Szenen. 100 Regeln begrenzen eine weitere
Kopie. Bestehende Inhalte und bestätigter Laufzeitstand bleiben gleich; kein
MQTT-Workflow wird gestartet. Der erste fokussierte Test scheiterte an der noch
kleinen Übersicht, nach Umsetzung sind die tatsächlichen Formskripte geprüft.

Lokale Abschlussprüfungen am 02.11.2026: php tests/run.php vollständig erfolgreich
mit PHP-/JavaScript-Integration und 106 JavaScript-Tests. Fokussierte Übersicht-
und Callback-Tests, separate Struktur- und Artefaktprüfung, Kodierungsprüfung,
offizielle StylePHP-Prüfung der 15 eigenen JSON-Dateien, PHP CS Fixer im
Prüfmodus und git diff --check erfolgreich. Alle 19 geänderten Textdateien als
UTF-8 ohne BOM mit LF geprüft. Großkatalog-Formular: 438.969 Bytes, unter 750 KiB.

Die lokale Implementierungsphase veränderte keine Installation. Der folgende
Praxisnachweis ergänzt die Offline-Prüfungen nach dem Modulupdate.

## Native Abnahme der Übersicht und Kopien am 02.11.2026

Bildschirmbedienung vorab angekündigt. Auf Test-Symcon den aktualisierten
Modulstand anhand der Dateihashes bestätigt. Eine vorübergehende Modulkopie las
die sieben gespeicherten Regeln aus dem gemeinsam genutzten Z2M; der lokale
Originalentwurf blieb erhalten. Kein Speichern, Aktivieren, Stoppen, Gerätebefehl
oder Erweiterungsupdate in Z2M.

Die native Konsole zeigte zehn Zeilen Platz je Übersicht, Trefferzahlen sowie
Such- und Regelfilter. Gemischte Groß-/Kleinschreibung fand Gerätenamen und
Automationsnamen. Suche und eingeschalteter Regelfilter ergaben gemeinsam null
Treffer; Zurücksetzen der Suche behielt den Regelfilter und zeigte genau die
eingeschaltete Regel. Auswahloptionen folgten den Treffern. Eine frische offene
Namensänderung blieb beim Ausblenden der ausgewählten Regel unverändert; der
Hinweis erklärte, dass die geöffnete Regel weiter bearbeitet werden kann.
Kopieren bei dieser offenen Eingabe öffnete den Schutzdialog und erzeugte
keine zusätzliche Regel.

Nach Rücknahme der Probeänderung die tatsächlichen nativen Kopierbuttons im
Geräte- und Zeitbereich betätigt. Beide Kopien erhielten neue IDs und den
Namenszusatz Kopie, waren ausgeschaltet und direkt zur Bearbeitung ausgewählt.
Uhrzeit, Zeitzone, Zeitfenster, Wochentage, Gerätebedingung und Aktionen der
Zeitregel waren korrekt vorbelegt und in der Kopie vollständig gleich.
Alle sieben Originalregeln blieben strukturell gleich; der Prüfentwurf wuchs
auf neun Regeln. Jeweils der eigene Filter wurde zurückgesetzt. Zielgerätesuche
im Zeitbereich sowie eine leere Suche mit Umlauten funktionierten. Nach
Schließen und Neuöffnen blieben Suchtext, Trefferhinweis und ausgewählte Kopie
erhalten.

Die Sichtprüfung deckte verschobene Spalten auf: Drei auto-Spalten pro Liste
lassen Symcon Überschrift und einzelne Zeilen unterschiedlich aufteilen. Die
offizielle List-Schnittstelle unterstützt nur eine auto-Spalte. Namen und
erstes Zielgerät erhielten begrenzte Pixelbreiten, Auslöser bzw. Zeitpunkt
nutzen den Rest. Fokussierter Test scheiterte zuvor an mehreren auto-Spalten
und besteht nach Korrektur für Geräte-, Zeit- und weiteren Bereich. Nur
form.json in Test-Symcon aktualisiert; Geräte- und Zeitübersicht anschließend
erneut visuell mit gleich ausgerichteten Zeilen bestätigt. Lange Namen werden
bei knapper Breite gekürzt; vollständige Namen bleiben im Bearbeitungsfeld.
Mausrad und Formular-Scrollleiste ließen sich bedienen; Scrollen innerhalb
einer Liste und außerhalb zum gesamten Formular sind zu unterscheiden.

Die native Debugdatei bestätigte Bereich, gesetzte Suche, Regelfilter und
Trefferzahl sowie beide lokalen ausgeschalteten Kopien mit Anzahl der
Auslöser, Bedingungen und Aktionen. Der bewusst eingegebene private Prüftext
erschien weder aus dem Namensfeld noch aus dem Suchfeld im Debug. Keine
FEHLER-Meldungen im aufgezeichneten Lauf. Dateiaufzeichnung beendet und
temporäre Modulkopie mit ihren Statusvariablen entfernt. Originalentwurf
bytegleich, gespeicherte und aktive Z2M-Revision und Prüfsumme, Regelanzahl,
eingeschaltete Anzahl, laufender Betrieb, Mesh und Wiederanlauf unverändert
durch frische Statusantwort bestätigt. Keine private Rohaufzeichnung im Repo.

Prüfgrenzen: Native Konsole 9.1, sieben vorhandene Regeln und zwei vorübergehende
Kopien; 50/100 Regeln, weitere/gemischte Auslöser, Astro-, Szenen- und
Folgenkopien sowie MQTT-Kontextwechsel bleiben synthetisch belegt. Kein
vollständiger nativer Nachweis unter Symcon 9.0 oder für weitere Fensterbreiten.
Erweiterung und Helper blieben unverändert.

Abschlussprüfung nach der Spaltenkorrektur: php tests/run.php einschließlich
PHP-/JavaScript-Integration und 106 JavaScript-Tests, separate Struktur-,
Kodierungs- und Artefaktprüfung erfolgreich. Offizielle StylePHP-Prüfung der
15 eigenen JSON-Dateien in isolierter Kopie und PHP CS Fixer 3.95.27 im
Prüfmodus ohne Befund. Sechs geänderte Textdateien als UTF-8 ohne BOM mit LF
geprüft; git diff --check sauber. Kein Commit oder Push durch den Agenten.

## Native Abnahme des Bearbeitungsstands am 02.11.2026

Auf Test-Symcon wurde der aktualisierte Modulstand dateigenau bestätigt. Eine
vorübergehende Modulkopie las die gespeicherten Regeln vom gemeinsam genutzten
Z2M. Der vorhandene lokale Entwurf der ursprünglichen Instanz blieb unberührt.
Die native Konsole zeigte getrennte Angaben zu Eingaben, Entwurf und Betrieb.

Regelauswahl und Vorbelegung funktionierten nach dem vorangegangenen
Klassenkontext-Fix. Ein geänderter Name wurde als offene Bearbeitung angezeigt.
Der Wechsel zu einer anderen Geräteregel fragte nach. Weiterbearbeiten erhielt
Name und Auswahl. Auch Statusabfrage, Einklappen und Schließen/Neuöffnen des
Formulars erhielten die Eingabe. Der Wechsel in den Zeitbereich fragte ebenfalls
nach; nach ausdrücklichem Verwerfen erschienen Name, Uhrzeit, Bedingungen und
Aktionen der Zeitregel. Bis dahin blieb der Prüfentwurf bytegleich.

Dabei aufgedeckter Popup-Fehler: EditorFormFields berücksichtigte nur items,
jedoch nicht popup.items. Die dynamische Erfassung des Verwerf-Buttons wurde
somit übersprungen; dessen statischer Platzhalter lieferte einen leeren Input.
Der Debug-Einstieg lehnte die Bestätigung deshalb ab. Neue feste deutsche
Ablehnungsgründe halfen bei der Eingrenzung. Die Traversierung umfasst nun auch
Popup-Felder; beim Formularaufbau wird der bestehende Bestätigungswert ergänzt.
Der Regressionstest führt jetzt das tatsächlich erzeugte Popup-Skript aus,
statt die Resolve-Methode direkt aufzurufen: zunächst fehlgeschlagen, nach der
Korrektur erfolgreich. Veraltete Bestätigungen und wiederholte Verwendung
bleiben gesperrt; keine vereinfachte oder ausgelassene Zustandsprüfung.

Die drei betroffenen PHP-Dateien wurden auf Test-Symcon aktualisiert und das
Formular frisch geöffnet. Der native Verwerf-Button schloss das Popup und
wechselte korrekt. Bei einer weiteren offenen Zeitregeländerung zeigte der
Prüfbutton einen Übernahmehinweis; die abgegrenzte Debug-Aufzeichnung enthielt
keinen Versand einer Validierungsanfrage. Die anschließende lokale Übernahme
in den Entwurf ausschließlich der Prüfkopie übernahm den Namen und setzte die
Änderungsanzeige zurück. Übrige Angaben und Zahl der Regeln blieben gleich.
Es wurde nichts in Z2M gespeichert, aktiviert, gestoppt oder geschaltet.

Debug-Nachweise erklären Wechselabbruch, ausdrückliches Verwerfen,
Prüfungsschutz und lokale Übernahme. Die Suchprüfung auf die eingegebenen
Testnamen lieferte keinen Treffer. Bei hoher Meldungsrate wurde die eigene
Aufzeichnung auf eine Datei begrenzt; sie wurde anschließend ausdrücklich
beendet. Prüfkopie einschließlich Statusvariablen entfernt. Der ursprüngliche
lokale Entwurf blieb bytegleich. Gespeicherte und aktive Revision/Prüfsumme,
Zahl eingeschalteter Regeln, Betrieb und Wiederanlauf wurden unverändert
bestätigt. Kein Zugriff über den Produktiv-MCP und kein Erweiterungsupdate.

Abschlussprüfungen: php tests/run.php einschließlich PHP-/JavaScript-Integration
und 106 JavaScript-Tests erfolgreich; Strukturprüfung, Kodierungsprüfung,
Artefaktprüfung, offizielle StylePHP-Prüfung der eigenen JSON-Dateien,
PHP CS Fixer im Prüfmodus und git diff --check erfolgreich. Das Großkatalog-
Formular umfasst 430.178 Bytes. Nach reiner Ausrichtung der PHP-Zuweisungen
wurden fokussierter Callback-Test und Formatierungsprüfung erneut ausgeführt.

Prüfgrenzen: Listendialoge mit Löschen/Umsortieren und die gleichzeitige native
Bearbeitung wiederverwendbarer Folgen wurden hier nicht erneut durchgespielt;
deren gezielte Offline-Nachweise bleiben maßgeblich. Laufzeit und Gerätewirkung
sind nicht Gegenstand dieser Formularprüfung. Andere Konsolen als die native
Windows-Testkonsole wurden nicht bedient.

## Bearbeitungsstand und geschützter Formularwechsel

Agenda Punkt 1: EditorWorkInput erfasst ausschließlich ausdrücklich freigegebene
Bearbeitungsfelder. EditorWork bindet normalisierte Eingaben in flüchtigen
Symcon-Buffern an Regel-/Folgenauswahl, Token, MQTT-Kontext und Inhaltsstand.
EditorWorkForm ergänzt die vorhandenen nativen Callbacks. Ein einmaliges
Bestätigungstoken erlaubt den Wechsel nur bei unveränderter Ausgangslage.
Formulareingaben werden getrennt vom persistenten Entwurf und von bestätigten
Z2M-Statusdaten geführt. Erfolgreiche Übernahme bereinigt ausschließlich den
betroffenen Bearbeitungsbereich; parallele Folgenbearbeitung bleibt erhalten.
Ein MQTT-Kontextwechsel darf das Laden des neuen Systems nicht blockieren.

Geprüfte vorhandene Helper: ConfigurationFormHelper und DebugFlowHelper bleiben
unverändert eingebunden. Regel-/Folgenzustand und deren Navigation sind
librarylokal, da keine zweite Library denselben Vertrag verwendet.
Kein Protokoll-, YAML-Schema-, Erweiterungs- oder Abhängigkeitswechsel.

`tests/editor-work.php` führt die tatsächlich erzeugten nativen Callback-Skripte
mit synthetischen Regeln aus: Wechsel mit noch nicht gemeldeter Namenseingabe,
Weiterbearbeitung, ausdrückliches Verwerfen, abgelehnte Wiederholung, getrennte
Bearbeitungsbereiche, Übernahme, parallele Folgenbearbeitung, Statusabfragen,
MQTT-Kontextwechsel und bereinigtes Debug. Bestehender Ereignisimport und
PHP-/JS-Integration bleiben Bestandteil der vollständigen Projektprüfung.

Native Listen können beim onDelete noch die Zeilen vor der Löschung liefern.
Der Callback markiert deshalb offene Änderungen; der anschließende Wechsel
liest die aktuelle Liste erneut. Vor dieser frischen Erfassung werden die
möglicherweise veralteten Löschzeilen nicht in ein neu aufgebautes Formular
zurückgeschrieben. Der Schutz ist kein dauerhaftes Sitzungsbackup und ersetzt
nicht das Übernehmen in den Entwurf. Native Praxisprüfung insbesondere für
Listendialoge, Löschen, Umsortieren, Popup-Abbruch und Scrollbedienung steht aus.
Während der ersten lokalen Umsetzung wurden weder Test- noch Live-System
verändert. Die anschließende native Praxisprüfung ist unten getrennt beschrieben.

Lokale Nachweise am 02.11.2026: vollständiges `php tests/run.php` mit
PHP-/JavaScript-Integration und 106 JavaScript-Tests, separate Strukturprüfung,
`npm run check:extension`, offizielle StylePHP-Prüfung der 15 eigenen JSON-Dateien,
PHP CS Fixer im Prüfmodus und `git diff --check` erfolgreich. Geänderte
Textdateien als UTF-8 ohne BOM mit LF geprüft. Das Großkatalog-Formular bleibt
mit 429.636 Bytes unter dem projektinternen Budget von 750 KiB.

## Native Formularskripte ohne Modulklassen

Die Praxisprüfung auf Test-Symcon am 02.11.2026 deckte eine Grenze der bisherigen
Stub-Prüfung auf: Ein natives Formularskript kann die librarylokale Klasse
EditorWorkInput nicht direkt aufrufen. Der Auswahlcallback scheiterte deshalb
vor dem registrierten Moduleinstieg; für diesen Fehler gab es keine Modul-
Debug-Ausgabe. Der lokale Regressionstest führt jetzt das tatsächlich erzeugte
Auswahlskript in einem separaten PHP-Prozess ohne Modul-Autoload aus. Er scheiterte
zuerst mit demselben Klassenfehler und besteht nach der Korrektur.

EditorWorkInput::Callback erzeugt ausschließlich eigenständige PHP-Grundfunktionen.
Das Skript filtert anhand der zentralen Feldliste, überführt native Listen in
Arrays und übergibt den begrenzten JSON-Schnappschuss an den bestehenden Wrapper.
Die eigentliche Erfassung und Normalisierung bleibt im Modul. Unbeteiligte
Formularvariablen werden bereits vor der Übergabe ausgeschlossen. Der Verwerf-
Button erhält seine vollständige Erfassung ebenfalls beim Formularaufbau.
Öffentliche Modulverträge, YAML, MQTT und Erweiterungsartefakt bleiben unverändert.

Die erste eigenständige Fassung vergrößerte die wiederholten Formularskripte
so stark, dass die PHP-/JavaScript-Integration beim Sammeln der Debug-Nachweise
an 128 MiB scheiterte. Die erzeugte Feldprüfung verwendet deshalb einen aus der
Feldliste abgeleiteten regulären Ausdruck und kompakte PHP-Grundfunktionen.
Es wurden weder das PHP-Speicherlimit noch die Testspeichergrenzen erhöht.
Auch die kompakte Fassung zeigte den Engpass beim abschließenden gemeinsamen
JSON-Kopieren aller Debug-Nachweise. Die unveränderte Datenschutzprüfung erfolgt
nun für jeden kodierten Datensatz einzeln statt über eine zusätzliche Gesamtkopie.
Alle verbotenen Namen, Auswahlwerte und Token werden weiterhin in jedem Eintrag
geprüft. Die vollständige Aufzeichnung bleibt für die übrigen Ablaufprüfungen
vorhanden; es werden weder Meldungen ausgelassen noch Prüffälle abgeschaltet.

Native Prüfkopie anschließend einschließlich Statusvariablen entfernt.
Der ursprüngliche lokale Entwurf blieb bytegleich. Gespeicherte und aktive
Revision, Prüfsumme, Zahl eingeschalteter Regeln und Wiederanlauf blieben gleich.
Kein Schaltversuch, Speichern, Aktivieren, Neustart oder Erweiterungsupdate.
Die korrigierte Fassung wurde nicht auf die Installation übertragen; erneutes
Modulupdate und Schließen/Neuöffnen des Formulars stehen vor der Fortsetzung an.

Abschließender lokaler Gesamtlauf erfolgreich mit unverändertem PHP-Limit 128M:
`php tests/run.php` einschließlich PHP-/JavaScript-Integration und 106
JavaScript-Tests. Separate Struktur-, Kodierungs- und Artefaktprüfung, offizielle
StylePHP-Prüfung der eigenen JSON-Dateien, PHP CS Fixer im Prüfmodus und
`git diff --check` erfolgreich. Alle zehn geänderten Textdateien mit UTF-8 ohne
BOM und LF. Der korrigierte native Gesamtablauf bleibt praktisch ungeprüft.

## Vorhandene Szenen als Aktionen

EditorScenes und src/scenes.mjs sind librarylokal; vorhandene zentrale Formular-,
Datenfluss- und DebugFlow-Helper bleiben unverändert. Szenenmetadaten werden
begrenzt gelesen und mit dem passenden Geräteendpoint bzw. der Gruppe verbunden.
Der Editor bindet jede Szene an die native Schaltberechtigung des Kanals. Die
eigene Schema-1-Aktion `scene` speichert ausschließlich stabile Z2M-Identitäten.
Prüfung und Speicherung melden fehlende lokale Szenen mit präziser Regel-/
Schrittposition. Aktivierung, Wiederanlauf und jede Übergabe prüfen erneut;
bei entfernten Szenen übernimmt dies der vorhandene Mesh-Empfänger.

`tests/editor-scenes.inc.php` verwendet die realen nativen Formularskripte und
den erzeugten JavaScript-Empfänger: Geräte-/Zeiteditor, Szenennamen, getrennte
Gerätekanäle, Callback-Auswahl, Prüfung/Speicherung, Wiederladen, Bearbeitung,
Partnerauswahl, Rechteentzug und bereinigtes Debug. Runtime-/Mesh-Tests prüfen
Szenen-ID 0, Endpoints, fremde Gruppenmetadaten, Verzögerung, gelöschte Szenen,
Stopp, blockierten Wiederanlauf und genau einmalige entfernte Übergabe.
Es werden ausschließlich synthetische Daten und temporäre Speicher verwendet.
Diese Offline-Tests greifen nicht auf Test- oder Live-Installationen zu.

Native Praxisprüfung am 01.11.2026 über die Test-Symcon-Installation am gemeinsam
genutzten Z2M: vorübergehende Geräteszene angelegt, Gerät und Szenenkanal im
Aktionsdialog ausgewählt und als deaktivierte Zeitregel mit zwei Sekunden
Wartezeit übernommen. Z2M-Prüfung und Speicherung sowie erneutes Laden und
native Vorbelegung von Gerät, Kanal, Szenenname, Wartezeit und Regelstatus
bestätigt. Die Szene wurde nicht aufgerufen. Testregel und Geräteszene danach
entfernt; ursprüngliche Regeln einschließlich Aktivierungskennzeichen und
Mesh-Freigaben bis auf die fortgeschriebene Revision unverändert. Vorherige
Ausführung und Wiederanlauffreigabe anschließend durch Z2M bestätigt.
Deutsche Debug-Ausgaben belegen Katalog, Auswahl, Vorbelegung und Speicherung.
Die zusätzliche Partnerzuordnung im Testmodul wurde wieder entfernt.

Dabei aufgedeckten Aktionsdialogfehler korrigiert: öffentliche Kernel-Wrapper
verlangen auch für PHP-Optionen die vollständige Parameterliste. Alle Geräte-,
Eigenschafts- und Wert-Callbacks der drei Aktionsbereiche übergeben nun den
Vergleich explizit. Ein Regressionstest führt sämtliche betroffenen Skripte
mit derselben verpflichtenden Argumentanzahl aus; vorher ArgumentCountError,
danach erfolgreich. Dieser erste Praxislauf prüfte ausschließlich die Verwaltung.

Anschließender ausdrücklich beauftragter Ausführungstest am 01.11.2026:
temporäre Geräteszene mit Ein-Zustand und definierter Helligkeit angelegt und
über eine zeitgesteuerte Regel mit zwei Sekunden Verzögerung aufgerufen.
Vor dem Termin meldete das Ziel Aus; zum erwarteten Ausführungszeitpunkt
bestätigte Z2M den Szenenaufruf. Frische MQTT-Geräterückmeldungen enthielten
Ein und exakt den Szenen-Helligkeitswert; die nativen Symcon-Variablen zeigten
den passenden Zustand und umgerechneten Prozentwert. Laufzeitstatus ohne Fehler.
Symcon-Debug belegt Prüfung, Speicherung und Aktivierung; der Bridge-Debug
enthält die erfolgreiche native Szenenbestätigung und Geräte-Rückmeldungen.
Die internen Debug-Meldungen der eigenen Z2M-Engine wurden nicht über MQTT
geliefert; deren vollständige Start-/Endfolge ist damit kein Praxisnachweis.
Keine zusätzliche manuelle Szenenwiederholung über die Bridge.

Testregel und Szene anschließend entfernt, ursprüngliche Regeln einschließlich
Mesh-Freigaben und Aktivierungskennzeichen bis auf die Revision erhalten.
Gespeicherte Prüfsumme entspricht exakt dem Wiederherstellungsdokument;
bisherige Ausführung und Wiederanlauf wieder bestätigt. Licht auf den vorab
gelesenen Ein-Zustand und Prozentwert zurückgesetzt, Z2M-Protokollstufe wieder
auf den Ausgangswert gesetzt und temporäre Debug-Aufzeichnungen beendet.
Keine Modul- oder Erweiterungsänderung in diesem Ausführungstest.
Entfernte Szenen sind weiterhin nur offline geprüft.

Nachweise am 01.11.2026: `php tests/run.php` vollständig erfolgreich; nach
Ergänzung des zweiten Endpoint-/Mesh-Nachweises die PHP-/JS-Integration und
alle 73 JavaScript-Tests erneut erfolgreich. `php tests/validate_structure.php`,
Kodierungsprüfung, `npm run check:extension`, offizielle StylePHP-JSON-Prüfung
der eigenen Projektdateien und PHP CS Fixer 3.95.27 im Prüfmodus bestanden.
JSON-Style lief auf einer temporären Kopie der eigenen JSON-Dateien, ohne
Fremdquellen oder node_modules zu verschieben. Geänderte Texte verwenden LF.

## Wiederanlauf nach Z2M-Neustart

Die Moduloption ResumeAfterRestart (Default true) wird im Aktivierungsauftrag
übertragen. Statusvertrag und Formular zeigen die tatsächliche Z2M-Freigabe.
Die eigene Erweiterung speichert Betriebswunsch und Snapshot-Identität in
execution-state.json über ihren vorhandenen atomaren Speichercode. Kein neuer
zentraler Helper und keine Änderung am YAML-Schema. Siehe ADR 0010 und RUNTIME.md.

Offline-Nachweise: neue Erweiterungsinstanzen über echten temporären Dateien,
aktivierter und ausgeschalteter Wiederanlauf, dauerhaft bestätigter Stopp,
Stopp während Start, veränderte und beschädigte Dateien, fehlende Ziele,
geänderte gegenüber identischer Speicherung, Schreibfehler, frische Bedingungen,
verworfene Verzögerungen und keine Nachholung verpasster Zeiten. Beide Mesh-Seiten
starten mit neuer Empfangsfreigabe. PHP-/JS-Integration bestätigt beide Werte der
Formularoption über die echte Erweiterung. Native Konsole, Installation und
reale Z2M-Neustarts sind für diesen Stand noch nicht geprüft oder beauftragt.

## Gateway-Wechsel und native Topic-Auswahl

Die native MultiSelect verlangt in jeder Option dieselben Property-Namen.
Der frühere leere Platzhalter `[]` verletzte diesen Vertrag und verursachte
„Aktueller Wert [] ist nicht verfügbar“. Optionen weisen jetzt ausnahmslos
`BaseTopic` zu. Es gibt weder im Formular noch beim Live-Update ein explizites
`value` für die MultiSelect. Insbesondere behandelt die Konsole einen mittels
`UpdateFormField(..., 'value', json_encode(...))` gesetzten Wert als skalaren
Text; der Anwenderscreenshot belegte deshalb die weiterhin rote Warnung.
Die native Auswahl wird ausschließlich aus den gebundenen Properties ermittelt.
Der Regressionstest verbietet direkte Wertupdates und prüft die fehlende
Wertüberschreibung auch beim Formularaufbau. Nicht erkannte gespeicherte Werte bleiben als
solche markiert gültig darstellbar. Das Textfeld erlaubt weiterhin manuelle Topics.

Die native Konsole prüft MultiSelect-Arraywerte per Objektidentität. Ein
`UpdateFormField` der Optionen erzeugt neue Arrays, lässt den ausgewählten
Arraywert aber am alten Objekt hängen. Inhaltlich gleiche Werte werden dadurch
als nicht verfügbar markiert. Die Praxisprüfung widerlegte somit die vorherige
Annahme, gültige Optionsinhalte allein reichten beim Live-Update aus.

`ApplyChanges` sowie FM_CONNECT/FM_DISCONNECT vergleichen jetzt den zuletzt
aufgebauten Formularkontext aus ConnectionID, gespeichertem BaseTopic und
sortierten gefundenen Topics. Nur bei geändertem Kontext folgt `ReloadForm`;
keine direkten Updates der MultiSelect-Optionen oder ihres Werts. Ohne geöffnetes
Formular oder bei gleichem Kontext entfällt der Reload. Der vorhandene zentrale
ConfigurationFormHelper bleibt unverändert für den Formularaufbau zuständig.
Der Lookup verwendet die tatsächliche aktuelle ConnectionID. Gespeicherte
Properties und YAML-Entwürfe bleiben unverändert. Noch nicht übernommene
Formulareingaben gehen gemäß nativem ReloadForm-Vertrag verloren; dies ist im
Benutzerhandbuch erklärt. Vor einem Gateway-Wechsel müssen sie übernommen werden.
Automatische Prüfungen werden bei bekannten, nicht passenden Topics nicht
eingeplant. Nach Übernahme eines passenden Topics läuft die einmalige Prüfung
im neuen Kontext; alte Zustände und Aufträge sind durch den Scope ungültig.

`tests/gateway-topics.php` reproduzierte zuerst den ungültigen leeren
MultiSelect-Eintrag. Es prüft gültige Options-/Wertbindungen, sofortige
Aktualisierung ohne erneutes Öffnen, Erhalt des gespeicherten Topics und der
Entwurfsbytes, deaktivierte Fehladressierung, richtige neue Anfrageroute,
mehrere Topics am selben Client und Abkoppeln. Alle Daten sind synthetisch.
Native Prüfung auf der freigegebenen Testinstallation am 31.10.2026: Gateway
gewechselt, gespeichertes fremdes Topic weiterhin gültig angezeigt, neues Topic
ausgewählt und Einstellungen übernommen. Keine rote Warnung nach dem Neuaufbau.
Ursprüngliche Verbindung und Basistopic wiederhergestellt, YAML-Entwurf bytegleich,
Z2M weiterhin gestoppt. Debug bestätigt den Neuaufbau und die Topic-Erkennung.
Keine Änderung am Live-System oder an der Z2M-Erweiterung.

## Systemübergreifende Aktionen

`tests/shared-gateway.inc.php` reproduzierte zuerst die abgewiesene Partnerwahl
am gemeinsamen MQTT-Client. Nach der Korrektur prüft die PHP-/JS-Integration
identische IEEE-/Gruppenkennungen auf unterschiedlichen Topics desselben Clients:
getrennte Auswahl, lokale und systemübergreifende Importprüfung, deaktivierte
Übernahme, Vorbelegung, Schema-Prüfung und Speicherung. Rechteentzug betrifft nur
das richtige Topic; fremde Quellen und nicht ausgewählte Verbindungen bleiben
ausgeschlossen. Gleiche Basistopics und Selbstzuordnung werden weiterhin abgewiesen.
Der neue Debug-Bezug benennt den gemeinsam genutzten MQTT-Elternknoten als Boolean.
Keine Änderung am Mesh-Protokoll, an der Erweiterung oder an Broker-Einstellungen.

Der neue Stand erweitert den Klick-Editor und die eigene Engine um Aktionen auf
einem gewählten zweiten Z2M-System. Kein neues Helper-Bundle: Katalogbindung,
Mesh-Protokoll und Laufzeit sind librarylokale Automationslogik. Vorhandene
DataFlow-, Formular- und DebugFlow-Helper bleiben unverändert eingebunden.

`tests/mesh.test.mjs` prüft zwei Instanzen des erzeugten Artefakts mit getrennten
Speichern, MQTT-Subscription-/Publish-Grenze und nativer Z2M-Befehlsübergabe.
Gleichlautende Gruppen werden anhand des Systems auseinandergehalten. Tests
prüfen Umschalten am Ziel, doppelte/abgelaufene/veränderte Execute-Pakete,
unberechtigte Quellen, fehlerhaftes JSON, fehlende Ziele, Stopp und Verbindungsfehler.
`tests/remote-targets.inc.php` ergänzt die PHP-/JavaScript-Integration um echte
Modulauswahl, getrennte Gateways, Rechteentzug, deaktivierten Import,
Vorbelegung, Schema-Prüfung und persistente Speicherung. Testdaten sind synthetisch.

Lokale Nachweise: `php tests/run.php` einschließlich neun neuer Mesh-Tests,
Struktur-/Helper-/Kodierungsprüfung, offizielle StylePHP-JSON-Prüfung eigener
Projektdateien und php-cs-fixer 3.95.27 im Prüfmodus. Native Formularbedienung,
Broker-/ACL-Reconnect, reale Geräteausführung und GitHub Actions sind für diesen
Stand noch nicht geprüft. Keine Änderungen an Test- oder Live-Installation.

## Native Ereignisziele und Wertänderungen

Die bisherige Annahme, dass EventActionParameters immer TARGET enthält, war falsch.
Symcon liefert TARGET aus dem Elternobjekt des Ereignisses. `ReadImportEvent`
liest deshalb zusätzlich die Eltern-ID; `ResolveImportAction` normalisiert sie
für die bestehende Variablen-/Gateway-/Topic-Prüfung. Explizite exportierte Ziele
bleiben nur zulässig, wenn sie keinem vorhandenen Elternziel widersprechen.
Der native Wrapper „Statusvariable schalten“ wird ausschließlich anhand seines
bekannten Vertrags IDENT + ACTION aufgelöst: IDENT bezeichnet eine native
Statusvariable der Instanz, ACTION ist ein begrenztes JSON-Objekt mit actionID
und parameters. Kein eval, keine Skriptinterpretation, keine beliebigen
verschachtelten Abläufe. Eigene TARGET-Overrides in der inneren Aktion werden
abgewiesen. Freigabe und Werteprüfung erfolgen nach der Normalisierung unverändert.

`TriggerSubsequentExecution=false` wird für bestimmte Werte als `on_change: true`
übertragen. Die Schema-1-Erweiterung ist optional. „Nur bei Wertänderung“ erscheint
als boolesches Changed-Feld im nativen Auslöserdialog und als kompakte Listenspalte.
Callbacks, Vorbelegung und Übernahme erhalten die Einstellung; alte drei Felder
bleiben gültig, unbekannte zusätzliche Felder werden weiterhin abgewiesen.
Die Runtime merkt frühere Werte getrennt von den frischen Bedingungswerten.
Nur konfigurierte Änderungs-Auslöser werden aus state.get(entity) initialisiert;
Bedingungen behalten ihre Anforderung an frisch beobachtete Werte. Nicht im
Z2M-Cache vorhandene Aktionseigenschaften starten mit der ersten frischen Meldung.
Stopp verwirft die Beobachtung. Wiederholte Berichte, Cacheveröffentlichungen und
fremde Eigenschaften lösen keine zusätzliche Änderung aus.

Die lesende Praxisdiagnose belegte falsche Importannahmen; sie ist kein Nachweis
einer produktiven Übernahme. Keine privaten Ereignisse, IDs oder Geräteantworten
wurden in Repository oder Testfixtures übernommen. Native Speicherformen,
IDENT-Auflösung, Änderungseinstellung, echte Formularcallbacks und Speicherung
werden synthetisch geprüft. Produktionsänderungen waren nicht beauftragt und
wurden nicht durchgeführt. Für die lokale Korrektur müssen Modul und Erweiterung
anschließend auf der Testinstallation aktualisiert und praktisch geprüft werden.

## Import-Gerätedaten direkt laden

`LoadImportDevices(EventID, Confirmed)` startet nach ausdrücklicher Bestätigung
nur den vorhandenen lesenden Editor-Workflow. Dessen optionale `importEvent`-
Kennzeichnung speichert die auszuwertende Auswahl. Nach `editor_loaded` werden
Suchbericht und Vorschau erneuert. Der Hook läuft nach Aktualisierung des
Editorentwurfs und Entfernen des Workflows, aber noch unter der Transport-Sperre;
Suche und Vorschau führen keine eigene Sperre oder MQTT-Anfrage aus.
Live-Formularfelder erhalten den gerade abgeschlossenen Transportzustand direkt,
damit alte Bufferwerte keine dauerhafte Ladeanzeige hinterlassen.

Ein kontextgebundener Fehlerhinweis bleibt beim Formularabruf erhalten; Start,
neue Suche oder Auswahl löschen ihn. Laden deaktiviert bisherige Vorschautokens.
Ein anderer laufender Auftrag wird nicht ersetzt. Ohne Bestätigung bleiben
Netzwerk und Entwurf unverändert; die native Bestätigung benennt ausdrücklich
auch den Ersatz ungespeicherter lokaler Änderungen. Es erfolgt keine Installation,
Aktivierung oder Änderung eines Symcon-Quellereignisses.

Auf ausdrücklichen Anwenderwunsch zeigt die Importübersicht die Symcon-Instanz-ID
neben dem Gerätenamen. Das ist eine begrenzte Anzeigeausnahme zum zentralen
Identitätsstandard, keine neue technische Zuordnung. Quelle bleibt die
Variablen-ID. Namen/IDs der Geräte werden nicht zusätzlich in Debug geschrieben.

`tests/event-import.inc.php` prüft den leeren Katalog, die tatsächlichen Buttons,
verzögerte MQTT-Antwort, sofortige Live-Freigabe nach Bestätigung, Timeout mit
persistenter Fehlermeldung, Retry sowie anschließende Übernahme und Speicherung.
Die Praxisprüfung des neuen Formularstands steht noch aus.

## Gatewaygebundene Instanz- und Ereignissuche

`Z2MInstanceDiscovery` ermittelt bekannte Z2M-Modultypen lokal. GUID-Konstanten
bezeichnen Modultypen, keine konkreten Instanzen. Vor Konfigurationszugriffen
prüft der Code `IPS_InstanceExists`, Modultyp und die eigene `ConnectionID`.
Er liest dann `IPS_GetConfiguration` und validiert vorhandene Felder; es gibt
keine ungesicherte `MQTTBaseTopic`-Abfrage an fremde Elternobjekte.
Der vorhandene ParentConnectionHelper liefert die eigene Gateway-ID unverändert.
Geräte-/Gruppenbindungen im gesamten Klick-Editor nutzen denselben Suchbereich.

`DetectedBaseTopic` verwendet die native MultiSelect-Wertübergabe von Symcon 9.0:
Eine Auswahl setzt nur das bestehende BaseTopic-Formularfeld. Das Modul speichert
oder wechselt dadurch keine Verbindung und überschreibt keine Konfiguration.
Die lokal ermittelte Auswahl wird pro Formulardurchlauf neu aufgebaut.
Die Ereignissuche filtert zunächst nur die native Quelle nach Gateway/Topic;
Katalog und Zugriffsrechte bleiben Voraussetzungen der Importvorschau.
Caches für Suchbericht und Vorschau enthalten diesen Kontext. Ein fremder oder
veralteter Katalog kann keine Importfreigabe erteilen.

`tests/event-import-scope.inc.php` ergänzt den echten PHP-/JS-Test um Kategorien,
fremde Instanzen, zwei getrennte Gateways/Automationsmodule und identische
IEEE-/Gruppenkennungen. Namen und Objektbaumposition sind keine Identität.
`tests/import-kernel-guards.php` zählt unzulässige Reads an der Kernelgrenze,
weil die offiziellen Stubs dort Exceptions statt der realen PHP-Warnungen liefern.
Der Test verlangt null solcher Reads und prüft Suche ohne Katalog, Topic-Vorschläge,
Gateway-/Topic-Trennung, fremde Ziele und Kontextwechsel. Keine Live-Mutation.

## Ereignisimport und Umschaltaktionen

Der native Bereich `EventImportSection` führt Suche, Vorschau und deaktivierte
Übernahme aus. `SymconEventImport` liest nur Kernel-Ereignisse; es wertet keine
Skripte aus und ruft keine Aktionsausführung auf. `ReadImportEvent` und
`ImportEventIDs` sind die schmalen geschützten Kernel-Testgrenzen.
Eine Vorschau bindet Ereignisinhalt, Variablenzuordnung und Scope; beim Import
werden diese neu ermittelt. Entwurfskonflikte nutzen den vorhandenen Editorvertrag.
Deterministische Import-IDs verhindern Duplikate, ohne Symcon-IDs in YAML zu speichern.

Die native Action-ID-Zulassung basiert auf den Symcon-Aktionsmetadaten und deren
Parametern (`TARGET`, ggf. `VALUE`). Unbekannte Action-IDs oder zusätzliche Parameter
werden abgewiesen. Boolesche ON/OFF-Metadaten werden ausdrücklich übersetzt;
Zahlenwerte bleiben wegen möglicher Modulskalierung außerhalb des Importumfangs.
Keine Helper-Erweiterung: Ereignisprüfung und Z2M-Zuordnung sind librarylokal.

`EditorGroupTargets` ergänzt die bestätigten Gruppen aus der begrenzten
Editorantwort als interne Zielschlüssel `group.<Z2M-ID>`. Im YAML bleiben sie
native `target: {type: group, id: <Z2M-ID>}`. Gruppen bieten keine Sensorquellen.
Der Klick-Editor erzeugt für Umschalten eine eigene `toggle`-Aktion mit `property`;
`set` behält `values`. Kein neues Schemaformat und keine Metadatenversion geändert.
Die Host-Schnittstelle `state.get(entity)` wird nur zur Umschaltausführung genutzt.

Offline-Nachweis: `tests/event-import.inc.php` im realen PHP-/JS-Harness prüft die
Formularcallbacks, Vorschau, Import, Vorbelegung, erneute Bearbeitung, Validierung,
Speicherung, Reload, Konflikte, Duplikate und bereinigte Debug-Ausgaben.
Zusätzliche Schema-/Runtime-Tests prüfen Gruppen-/Kanal-Umschalten, fehlenden Cache
und fehlende Host-Schnittstelle. Praxisprüfung auf Testinstallation steht aus;
für Gruppenimport und Umschalten müssen Modul und Erweiterung aktualisiert werden.

## Einmalige automatische Statusprüfung

`ApplyChanges`, Formularabruf und Wiederverbindung planen einen einmaligen
`StatusRefresh`-Timer mit 1000 ms ein. Der Formularaufbau sendet selbst kein MQTT
und lädt das Formular nicht neu; bestehende Editoreingaben bleiben erhalten.
Der bereite Kernel und eine aktive MQTT-Anbindung sind Voraussetzung.
Nahezu gleichzeitige Anlässe werden zusammengefasst (zehn Sekunden Abstand).
Eine frische passende positive Health-Antwort startet genau eine lesende
Erweiterungsabfrage. Laufende manuelle Aufträge haben Vorrang; Kontextwechsel,
Abschaltung, Transportfehler und Zeitüberschreitung beenden den Versuch.
Es gibt keine automatische Installation, Aktivierung oder Wiederholungsschleife.

`tests/status-refresh.php` prüft mit synthetischem MQTT-Gateway beide Antworten,
Formular-/Updateauslöser, Zusammenfassung, Erhalt des Entwurfs, Vorrang manueller
Aufträge, fremde/veraltete Antworten, Kontextwechsel, Verbindungswiederkehr,
Transportfehler, Zeitüberschreitungen und bereinigte Debug-Ausgaben.
Die Praxisprüfung des Timers und der Statusanzeige in der Konsole steht aus.

## Sichtbare Formularbreite

Alle Listen erhalten width=100%; übergeordnete Panels behalten ihre flexible
Breite ohne Pixelvorgaben. Listenspalten verwenden genau eine auto-Hauptspalte
und Pixelbreiten für die übrigen Felder. Auslöser verwenden je 140px für Vergleich
und Mindestdauer, Aktionen 140px für Wartezeit und Bedingungen 140px für Vergleich.
Die Übersichten verwenden jeweils 150px für Auslöser/Ziel und 80px für Aktiviert.
Kompakte Zeitspaltenüberschriften lassen die ausführlichen Beschriftungen im
Zeilendialog unverändert. Statische Listen und dynamische Spaltendefinitionen
verwenden dieselbe Breitenregel. Keine Änderung an Regelwerten oder Skripten.

Die Symcon-List-Dokumentation erlaubt Pixelbreiten und eine auto-Spalte.
Prozentbreiten der bisherigen Spalten summierten sich teilweise bereits zu 100%,
wodurch Bedienknöpfe und lange Beschriftungen zusätzlich überlaufen konnten.
Die Struktur und die tatsächlich erzeugten Formulare werden offline geprüft.
Die schmale/breite visuelle Abnahme in der nativen Testkonsole erfordert nach
Modulupdate einen gesonderten Praxislauf; dieser Stand wurde nicht live eingespielt.

## Messwertvergleiche und UND-Bedingungsliste

Der native Geräteauslöser-Zeilendialog bietet einen Vergleich an. Numerische
Eigenschaften unterstützen sechs Operatoren, diskrete Eigenschaften eq/ne.
Die serialisierte Auslöserzeile enthält Choice, Compare und Hold. Die neue
EditorConditionList enthält Choice und Compare pro Zeile; sie ersetzt die
sichtbare einzelne Gerätebedingung in Geräte- und Zeitbereich. Ein optionales
Zeitfenster bleibt separat. Alle Bedingungen verwenden unverändert die
bestehenden device_value-/time_window-Klauseln und die UND-Semantik der Engine.
Kein neues YAML-Schema und keine geänderte Erweiterung erforderlich.

AutomationEditorConditions hält Validierung, Vorbelegung und Debug der Liste
librarylokal. Der vorhandene ConfigurationFormHelper und DebugFlowHelper bleiben
kanonisch eingebunden und unverändert. GetEditorItemForm und ConfigureEditorItem
verwenden dieselben geräteabhängigen Dialogfelder für Auslöser und Bedingungen.
Vergleichsschwellen werden als endliche Zahlen geprüft; Messbereich und Schritt
beschränken weiterhin Aktionswerte, nicht Vergleichsschwellen. Diskrete Werte
bleiben auf lesbare Katalogeinträge beschränkt. Unvollständige Listen und mehr
als 16 Bedingungen einschließlich Zeitfenster werden vor der Übernahme abgewiesen.

Offline-Nachweis in tests/editor-comparisons.inc.php, eingebunden in den echten
PHP-/JavaScript-Integrationstest: Formularcallbacks für Vergleich und Listen,
Erstellen/Bearbeiten/Löschen, alle numerischen Operatoren, Zustandsvergleiche,
Grenzen, falsche Datentypen, Vorbelegung in beiden Bereichen, Validierung und
persistente Speicherung. Die synthetische Ausführung blockiert bei jeweils
einer falschen Bedingung und schaltet erst bei gemeinsam erfüllten Bedingungen.
Danach wird gestoppt. Start-/Ende-Debug, UND-Meldungen und Vorbelegungsanzahl
sind geprüft. Kein Praxisauftrag, kein Deployment und keine reale Schaltung;
die Darstellung der neuen Felder in der Testkonsole ist noch praktisch zu prüfen.

## Gemeinsame Kodierungsprüfung aus Symcon_ModuleCI

`php tests/run.php` führt `tests/check_encoding.php` aus. Dieser dünne Einstieg
lädt ausschließlich die kanonische Implementierung aus
`Symcon_ModuleCI/php-tests/scripts/check-encoding.php`. Implementierung und
Regressionstests liegen vollständig im zentralen CI-Repository.

Lokal wird standardmäßig das benachbarte `../Symcon_ModuleCI` verwendet.
Alternativ setzt `SYMCON_MODULE_CI_ROOT` dessen absoluten Repositorypfad.
Die neue php-tests-Action stellt diese Variable automatisch bereit und führt
den Prüfer außerdem als gemeinsame Basisprüfung aus. Fehlt die zentrale Quelle,
bricht die lokale Prüfung mit klarer Fehlermeldung ab; kein stilles Überspringen
und kein Download. Ein separat kopierter Library-Checkout benötigt daher auch
einen zentralen CI-Checkout beziehungsweise die gesetzte Variable.

Z2MAutomations nutzt für diese Integration `php-tests@main`; die Style-Action
bleibt auf v1.0.0. Der zentrale Stand muss zuerst auf main verfügbar sein,
bevor die Consumer-CI ausgeführt wird.
Nach einem gesondert beauftragten ModuleCI-Release kann auf dessen Tag umgestellt
werden. Keine anderen Libraries oder veröffentlichten Tags wurden verändert.

Die Prüfung liest eigene Textdateien, meldet ungültiges UTF-8, Ersatzzeichen
und typische einfach/doppelt beschädigte deutsche Sonderzeichen mit relativem
Pfad und Zeile. Fremdquellen unter `.style`, `tests/stubs`, `libs/helper` und
`libs/vendor`, Abhängigkeiten, Git-/IDE-/Vorschauordner und symbolische Links
bleiben ausgeschlossen. Keine Datei wird automatisch korrigiert. Details und
Regressionstests sind in Symcon_ModuleCI dokumentiert.

## Installation bei fehlender bridge/info-Bestätigung

Praxisbefund: Nach einem Modulupdate war externalJS unbekannt (null) und die
Z2M-Version im Transportcache leer. Die vorhandene Erweiterung bestätigte dennoch
Bereitschaft und den gestoppten Zustand. Der bisherige Check externalJS !== true
verwechselte fehlende Metadaten mit deaktivierter Freigabe und blockierte vor
jedem Versand. Nun blockiert nur ausdrücklich gemeldetes false. Bei unbekanntem
Wert entscheidet die offizielle extension/save-Schnittstelle; Einstellungen
werden nicht geändert. Fehlende oder negative Antworten bleiben Fehler, Erfolg
benötigt unverändert Upload-ACK und eine korrelierte Empfängerprüfung.
Offline geprüft: unbekannte Metadaten mit Erfolg, sicherer Installationsfehler
und Timeout; explizit deaktivierte Freigabe weiterhin ohne Upload abgewiesen.
Diese Korrektur ist lokal, noch nicht auf der Testinstallation eingespielt.

## Praxisprüfung der getrennten Auswahl und Installationsrückmeldung

Formularanordnung auf der aktualisierten Testinstallation geprüft:
Einrichtung/Diagnose folgt unmittelbar auf MQTT. Vorhandene Zeitregel einschließlich
Zeitfenster, Wochentagen, numerischer Bedingung und Aktion korrekt vorbelegt.
Aktionsdialog stellt Gerät, schaltbare Eigenschaft und Wert wieder her.
Mehrkanaltaster bietet L1–L3 getrennt; Wechsel des Geräts verwirft alte
Eigenschafts-/Wertauswahl. Lesbare Sensoren bleiben in der Quellenauswahl.
Eine deaktivierte Geräte-Prüfregel wurde nur im lokalen Entwurf angelegt,
neu ausgewählt und mit Kanal, Wert, Mindestdauer und Aktionsverzögerung geprüft.
Danach Originalzustand durch erneutes Laden wiederhergestellt. Keine Speicherung,
Aktivierung oder reale Schaltung. Empfänger bestätigt unveränderte Revision und
Prüfsumme, eine gespeicherte Regel und gestoppten Zustand ohne Fehler.
Debug bestätigt Auswahlwechsel, erfolgreiche Vorbelegung und Katalogzählung.

Die zusätzliche Installationsrückmeldung ist lokal implementiert, noch nicht
auf der Testinstallation eingespielt. PopupAlert wird nur beim Formularaufruf
(Notify=true) geöffnet. Kein Erfolg beim bloßen Absenden oder Upload-ACK;
erst nach korrelierter Empfängerprüfung. Offline-Integration prüft außerdem
bereinigte Installationsfehler, fehlende Bereitschaftsbestätigung/Timeout und
programmatisch unveränderte Standardaufrufe ohne Popup.

## Generischer Geräte- und Eigenschaftskatalog

Der native Zeilendialog für Auslöser und Aktionen trennt Gerät, Eigenschaft/Kanal
und Wert. Binär-/Enumwerte sind Auswahlfelder, Zahlen erhalten Grenzen und
Schrittprüfung aus Z2M-Exposes. Zustandsbedingungen besitzen ebenfalls getrennte
Eigenschafts- und Wertfelder. Numerische Bedingungen behalten Vergleich und Zahl.
Jeder Dialog wird frisch erzeugt; beim Geräte-/Eigenschaftswechsel werden alte
Werte verworfen. Vorhandene Regeln werden anhand ihrer gespeicherten Z2M-Identität
vorbelegt. Nicht unterstützte oder nicht mehr zulässige Regeln bleiben erhalten
und werden gegen unbeabsichtigtes Überschreiben geschützt.

Aktionsziele benötigen sowohl Z2M-Schreibrecht als auch eine vorhandene,
nicht gesperrte Symcon-Variable mit wirksamer Aktion. Zuordnung ausschließlich
über die Zigbee2MQTT-Geräteinstanz, IEEE, identisches MQTTBaseTopic und direktes
Variablen-Ident; keine Namens- oder Pfadheuristik. Die Eigenschaftsauswahl verwendet
die Variablen-ID. Doppelte Zuordnungen werden ausgeschlossen. CustomAction=1
sperrt; ein gültiges eigenes Aktionsskript hat Vorrang vor der Standardaktion.
Aktionen werden dabei weder aufgerufen noch Skripte ausgeführt. Lesbare Sensoren
bleiben unabhängig von Symcon-Aktionen für Auslöser und Bedingungen verfügbar.

Die YAML enthält weiterhin ausschließlich Z2M-Gerät, Eigenschaft und Wert.
Symcon-IDs und Formularfelder werden nicht übertragen. Bestehende Auswahl-Hashes
bleiben gültig; numerische Werte erhalten begrenzte, erneut validierte Tokens.
Ältere Katalogantworten ohne Metadaten werden konservativ aus den bisherigen
Auswahlwerten ergänzt. Neue Eigenschaften benötigen die aktualisierte Erweiterung.

Offline geprüft: echter Formularcode, numerischer Aktionsentwurf mit Verzögerung,
Validierung/Speicherung im JavaScript-Empfänger, erneutes Laden und Vorbelegung,
deaktivierte Aktionen, reine Anzeigevariablen, Schreib-/Leserechte, Zahlenschritte
und generische Endpoints. Keine Live-Installation oder reale Schaltung für diese
Änderung. Die Darstellung des neuen List-Zeilendialogs in der Testkonsole steht
noch zur gesonderten Praxisprüfung aus.

# Entwicklung

## Korrektur und Praxisprüfung des Mehrkanal-Katalogs

Ursache waren korrekt gelieferte explizite Kanal-Exposes, die im zweiten
Katalogdurchlauf hinter Sonderoptionen standen. Das globale 1024er-Budget konnte
vor diesen Eigenschaften erschöpft sein. Binäre Features mit Zustandsnamen oder
passender expliziter Endpoint-Eigenschaft erhalten jetzt denselben Vorrang wie
state. Übernommene Property-Namen bleiben unverändert; keine erfundenen Endpoints
oder umgeschriebenen MQTT-Eigenschaften. Grenzen und gültige bisherige Auswahlkeys
bleiben erhalten. PHP ergänzt verständliche Kanalbeschriftungen und die sichere
Anzahl übernommener Kanalzustände im Debug.

Ein zuerst roter Node-Regressionstest bildet ausgeschöpftes Auswahlbudget und
drei spätere Switch-Endpoints synthetisch ab. Die PHP-/Node-Integration erstellt
über echte Formularskripte drei Kanal-Auslöser und eine Kanalaktion, validiert
und speichert beim echten synthetischen Empfänger und lädt alle Auswahlwerte
und Dauern wieder. Vollständige Offline-Suite mit 38 Node-Tests erfolgreich.

Testinstallation: PHP und Übersetzung aktualisiert, generiertes Einzeldatei-
Artefakt per Modul installiert und vom Empfänger bestätigt. Artefakt-SHA-256:
710f41be297b317062f33af9e50792b53ea4dfb2a8f314fa0b95371cd990af21.
In der nativen Konsole alle drei Kanäle einzeln ausgewählt und mit Mindestdauern
1/2/3 Sekunden übernommen. Zielkanal L2 mit Wartezeit 4 Sekunden ausgewählt.
Erneute Regelauswahl stellte alle Zeilen, Zustände und Dauern vollständig wieder
her; Z2M validierte den Entwurf erfolgreich. Debug bestätigt Kanalzustände und
FelderVorbelegt=true. Keine Speicherung/Aktivierung der Testregel, keine Geräte
betätigt. Original-YAML durch Neuladen bytegenau wiederhergestellt; gespeicherte
Revision 18 unverändert, Engine gestoppt, Laufzeitfehler leer. Debug-Dateisitzung
beendet. Der zuvor beschriebene Mehrkanal-Katalogbefund ist damit behoben.


## Reale ODER-Prüfung mit Radar und Mehrkanaltaster

Auf ausdrücklichen Praxisauftrag löste der Anwender den beauftragten Radar und
den Wandtaster physisch aus. Eine temporäre aktivierte Regel verwendete zwei
ODER-Geräteauslöser und dieselbe Aktionsfolge: beauftragtes Flurlicht ein,
fünf Sekunden warten, aus. Der Radar meldete Anwesenheit; danach wurden Ein-
und Aus-Rückmeldungen des Zielgeräts erfasst. Nach dem zwischenzeitlichen Stopp
wurde neu aktiviert: Der Weg zum Taster verursachte zunächst einen weiteren
Radar-Lauf; anschließend startete die echte Kanalmeldung des Tasters eine eigene
Ein-/Aus-Folge. Beim Tasterlauf lagen die Ein-/Aus-Rückmeldungen rund fünf Sekunden
auseinander. Symcon-/Geräte-Debug-Dateien belegen Quellen und Zielzustände;
Empfängerstatus bestätigte aktive Testrevision und leeren Laufzeitfehler.

Beim ausgewählten dreikanaligen Taster erscheint im Klick-Katalog nur ein
allgemeiner Zustand, nicht der tatsächlich gemeldete einzelne Tastenkanal.
Deshalb wurde für diese Praxisprüfung ausdrücklich die passende Kanaleigenschaft
in der Test-YAML verwendet. Die ODER-Laufzeit ist damit geprüft; die vollständige
Kanalabbildung dieses Tasters im Klick-Katalog bleibt ein separates offenes Thema.
Keine Änderung an Produktionscode oder Geräteparametern in diesem Prüfschritt.

Testautomation anschließend gestoppt, ursprüngliche Regeln inhaltlich unverändert
mit neuer Speicherrevision 18 wiederhergestellt und erneut geladen. Endzustand:
Engine gestoppt, keine aktive Revision, kein Laufzeitfehler. Nach dem Stopp
meldete das Licht nochmals Ein bei weiterhin gestoppter Engine; abschließend
ausdrücklich ausgeschaltet und frische Aus-/Helligkeit-0-Rückmeldung bestätigt. Alle fünf Debug-Dateisitzungen beendet. Vorübergehend geänderte
Z2M-Logoptionen wurden auf info bzw. deaktivierte Debug-Veröffentlichung
zurückgesetzt. Die Debug-Veröffentlichungsoption meldete Neustartbedarf; kein
Z2M-Neustart durchgeführt. Die aufgezeichnete Diagnose enthält keine separaten
Z2M-internen Debug-Schrittlogs, sondern native Geräte-/Transportmeldungen und
den korrelierten Modulstatus.


## Mehrfachauslöser im Geräteeditor

Native List verwaltet 1 bis 8 Kombinationen aus Katalogauswahl und Mindestdauer.
Die vorhandene ODER-Semantik der Erweiterung und Schema 1 bleiben unverändert.
SaveScheduledAutomation erhält den optionalen letzten Parameter TriggerSteps;
ältere Aufrufe bleiben für Einzelregeln gültig, dürfen mehrere Auslöser jedoch
nicht auf einen reduzieren. Geräte-/Zeitmischungen bleiben Metadatenbearbeitung.
Die Liste erhält dieselbe Bereichszuordnung wie Aktionen und Bedingungen.

Die Integration führt reale Formularskripte mit nativen Listen-Iteratoren aus,
prüft Erstellung, Wiederladen, Daueränderung, Löschen, 8er-Grenze, ungültige
Typen/Werte und Legacy-Schutz. Zwei synthetische Geräte starten jeweils dieselbe
Aktion über die unveränderte Node-Erweiterung. Zwischen den Alternativen wird
neu aktiviert, um den bestehenden einsekündigen Cooldown getrennt zu prüfen.

## Praxisprüfung der Auslöserliste

Aktuellen Modulstand mit neuer Trigger-Trait, Formular und Übersetzungen auf die
Testinstallation übertragen, PHP-Syntax und Library-Reload ohne Fehler. In der
nativen Konsole wurde eine deaktivierte lokale Regel aus Bewegungsmelder und
Taster per Listendialog erstellt und nach Regelauswahl mit beiden Einträgen
wieder geladen. Mindestdauer über Zahnrad geändert, übernommen, Eintrag über
Papierkorb gelöscht und erneut übernommen. Die Spalten reservieren Platz für
Bedienelemente; Hinzufügen/Zahnrad/Papierkorb sind ohne seitliches Scrollen sichtbar.
Z2M validierte anschließend den Entwurf mit zwei Auslösern erfolgreich. Debug
bestätigt Auslöseranzahl, ODER-Verknüpfung und beim Löschen den Vorbestand.

Keine Live-Aktivierung oder Speicherung: Original-YAML durch Editor-Neuladen
wiederhergestellt und bytegenau verglichen. Z2M bleibt bei Revision 16 gestoppt,
keine Geräte geschaltet. Temporäre Testregeln entfernt, Debug-Dateisitzung beendet.
Die echten alternativen Schaltläufe wurden ausschließlich synthetisch geprüft.

## Praxisprüfung der Vorbelegung

Auf der Testinstallation wurden die identischen aktuellen Moduldateien per Hash
bestätigt und die Library ohne Fehler neu geladen. Die echte Konsolenauswahl lud
die deaktivierte Zeitregel vollständig: Name, 07:30, Europe/Berlin, Nachtfenster,
Wochentage, Temperaturvergleich und Aktion. Eine temporäre deaktivierte lokale
Geräteregel lud Quelle/Ereignis, Mindestdauer 4 Sekunden, dieselben Bedingungen
und zwei Aktionen mit Wartezeiten 5 und 7 Sekunden. Native Debug-Datei bestätigt
FelderVorbelegt=true für beide Auswahlen. Die Testregel wurde durch Neuladen des
gespeicherten Originals entfernt; exakter YAML-Abgleich bestanden. Z2M blieb
gestoppt bei Revision 16, keine Schaltbefehle oder Speicherung der Testregel.


## Korrektur: Vorbelegung nach Verlust des Transportcaches

Die Testinstallation zeigte erhaltene Editorregeln, aber extensionConfirmed=false
und editor_changed. Die gemeinsame Kontextprüfung verhinderte dadurch schon die
lokale Auswahl. SelectAutomation erlaubt nun ausschließlich für diese lesende
Vorbelegung fehlende Empfängerbestätigung. Scope-/YAML-Abgleich und bekannte
Empfängerkonflikte bleiben erhalten; mutierende Aktionen verwenden weiterhin die
vollständige Empfängerprüfung. UpdateEditorField reicht Eigenschaften des
internen EditorDetails nicht an den aktiven Bereich weiter, dessen eigene
Sichtbarkeit durch AutomationSectionFields verwaltet wird.

Regression: flüchtigen Transportcache im synthetischen Modul verwerfen, echte
Auswahl ausführen und tatsächlich gesendete Live-Feldwerte für Name, Zeit,
Zahlenbedingung und sichtbaren Zeitbereich prüfen. Löschen bleibt dabei gesperrt.
Die Korrektur ist nicht live installiert; reale Konsolenprüfung steht aus.


## Korrektur: sichtbare Bearbeitungsbereiche und eindeutiger Einstieg

Beide Editorüberschriften bleiben sichtbar; nur der aktive Bereich ist zunächst
aufgeklappt. Der native ExpansionPanel-onClick ruft OpenAutomationEditor auf und
aktiviert bei einem Bereichswechsel die Regelauswahl. Auf-/Zuklappen im bereits
aktiven Bereich verändert keine Formulareingaben. Redundante Neu-Buttons sind
entfernt: Die Auswahl enthält neue und bestehende Regeln. Panel-onClick wird
laut offizieller ExpansionPanel-Dokumentation seit Symcon 5.3 unterstützt.

Offline-Integration prüft beide echten Panel-Skripte, unveränderte Live-Felder
beim erneuten Klick, sichtbare Überschriften, fehlende Neu-Buttons und Ablehnung
falsch zugeordneter Regeln. Die vollständige PHP-/Node-Suite (37 Node-Tests),
Struktur-, JSON-Style- und PHP-Formatprüfungen bestehen. Die reale Konsolenabnahme
steht aus; keine Testinstallation in diesem Korrekturschritt geändert.


## Bearbeitung direkt in Geräte- und Zeitbereichen

Eigene statische Feldnamen für beide Bereiche ersetzen den gemeinsamen sichtbaren
Editor. Auslöserart bleibt verborgen und ist durch den jeweiligen Bereich
festgelegt. Die bisherige Bearbeitung bleibt nur für weitere Auslöser im
entsprechenden Bereich verfügbar; Metadatenänderungen erhalten deren Strukturen.
Gemeinsame Prüfung, Speicherung und Aktivierung/Stopp sind nicht dupliziert.

Die librarylokale AutomationEditorAreas ordnet kanonische Felder den aktiven
Bereichsfeldern zu. Es bleibt eine Regelauswahl aktiv; Auswahlkennung und
Entwurfsprüfsumme schützen vorhandene Regeln. Optionale Bereichsparameter an
SaveScheduledAutomation, DeleteAutomation, SelectEditorDevice,
ConfigureEditorSchedule und InspectEditorActions weisen verspätete Aufrufe aus
dem anderen Bereich zurück. Bisherige öffentliche Aufrufe bleiben kompatibel.
Vorhandener ConfigurationFormHelper ist unverändert; keine neue Runtime-API.

Offline-Integration führt die tatsächlichen Geräte-/Zeit-Formularskripte aus:
Erstellen, Auswählen, Wiederladen, Bearbeiten, Löschen, Bedingungen, Aktionsfolgen,
veraltete Bereichsaufrufe und gemeinsames Prüfen/Speichern gegen den echten
synthetischen Node-Empfänger. Die folgende Dokumentation beschreibt frühere
Entwicklungsstände. Dieser Stand ist noch nicht live installiert; visuelle
Prüfung auf schmaler und breiter Konsole steht aus.

Abschlussprüfung: `php tests/run.php` einschließlich 37 Node-Tests,
`php tests/validate_structure.php`, offizieller StylePHP-JSON-Check und
php-cs-fixer im Prüfmodus bestanden. Der StylePHP-Check lief über die eigenen
Quellen ohne node_modules; die Testabhängigkeiten wurden unverändert zurückgelegt.
Native Feldnamen und Übersetzungen sind eindeutig; Live-Updates werden in der
Integration zusätzlich gegen tatsächlich vorhandene Formularfelder geprüft.


## Native Formularbereiche

Geräte- und Zeitregeln erhalten gefilterte native ExpansionPanels mit eigenen
Übersichten, Auswahl und Neu-Buttons. Der gemeinsame Editor besitzt getrennte
Auslöserbereiche sowie optionale Bedingungen, Aktionen und Entwurfsübernahme.
Einrichtung und YAML sind eingeklappt; Ausführungsstatus und Stopp stehen oben.
Mehrere/andere Auslöser bleiben in einem bedarfsweise sichtbaren Bereich.

`SelectAutomation` erhält den optionalen letzten Parameter `NewType` (device/time);
alte Aufrufe bleiben kompatibel. Eine neue Auswahl setzt den Editor zurück,
bestehende Regeln behalten ID und Auswahlkonfliktschutz. Formularfelder werden
über Referenzen auf die statischen verschachtelten Items befüllt; Namen und
Property-Verträge bleiben stabil. ConfigurationFormHelper bleibt unverändert.

Offline-Integration prüft die echten Neu-/Auswahlskripte, getrennte Listen,
Zeit-/Gerätewechsel einschließlich sofortiger Listenaktualisierung, Wiederladen
von Bedingungen, Erhalt erweiterter Regeln und sichere deutsche Debug-Meldungen.
Bestehende Bearbeitungs-/Lösch-/Persistenz- und Aktionsfolgenprüfungen laufen
weiter über dieselben verschachtelten Felder. Keine Änderung an Erweiterung,
Schema oder Laufzeit und keine Live-Installation in diesem Arbeitsschritt.
Visuelle Prüfung auf schmaler/breiter nativer Konsole steht noch aus.

Abschlussprüfung: `php tests/run.php` (PHP-/MQTT-/Formularintegration und
37 Node-Tests), `php tests/validate_structure.php`, offizieller StylePHP-JSON-Check
und php-cs-fixer im Prüfmodus erfolgreich. Fremde node_modules wurden für den
StylePHP-Check vorübergehend aus dem Quellbaum gehalten und wiederhergestellt.
Verschachtelte Felder werden zusätzlich auf eindeutige Namen, Übersetzungen
und flexible Panelbreiten geprüft.

## Reale Aktionsfolge aus dem Klick-Editor

Beim Praxisauftrag am 28.10.2026 fehlte das freigegebene Testlicht im bisherigen
Katalog, obwohl Z2M dessen schaltbaren binären Zustand anbot. Viele Sonderoptionen
früher Geräte hatten das globale Auswahlbudget erschöpft. Nach ausdrücklicher
Korrekturfreigabe sammelt der Empfänger die begrenzte Gerätemenge und berücksichtigt
zuerst binäre state-Werte, danach weitere Eigenschaften. Die Limits von 256
Geräten, 1024 Auswahlwerten, 64 Werten je Art und Gerät sowie die begrenzte
Feature-Tiefe bleiben erhalten. Exposes werden pro Gerät einmal gelesen.
Regressionstests prüfen ein spätes Licht hinter vielen Sonderoptionen und den
Grenzfall mit 256 Lichtern; der ursprüngliche Stand bestand den neuen Test nicht.

Das neu erzeugte Erweiterungsartefakt wurde in der Testinstallation installiert
und bestätigt. Der frisch geladene Klick-Editor bot das Testlicht nun an. Ohne
YAML-Eingabe wurde eine Zeitregel mit zwei Aktionen erstellt: Ein ohne Wartezeit,
Aus nach fünf Sekunden. Die Formularbuttons prüften, speicherten und aktivierten
die Regel; Symcon bestätigte die aktive Revision mit genau einer aktivierten Regel.

Die native Z2M-Ausführung protokollierte Start, zwei Schaltübergaben mit rund
5,002 Sekunden Abstand und Ende. Frische Geräte-Rückmeldungen bestätigten Ein
und Aus mit rund 4,996 Sekunden Abstand; auch eine positive Helligkeit wurde
gemeldet. Der abschließend abgefragte Laufzeitfehler war leer. Anschließend wurde
über den Modulbutton gestoppt und der gestoppte Zustand von Z2M bestätigt.
Die Testregel wurde über die Oberfläche gelöscht, geprüft und gespeichert.
Frisches Laden bestätigte den ursprünglichen Regelinhalt mit neuer Revision;
das Testlicht war wieder aus mit der ursprünglichen Helligkeit 0. Temporäre
Diagnoseerweiterung und erhöhte Logstufe wurden zurückgenommen, die Debug-Dateisitzung
beendet. Die Aufzeichnung lief unabhängig vom Symcon-Verwaltungsmodul über MQTT;
dieser einzelne reale Ablauf ist keine vollständige Geräte-/Versionsabnahme.

## Aktionsfolgen im Klick-Editor

Eine native List mit add/delete/changeOrder erlaubt bis zu 16 diskrete Set-Aktionen
aus dem vorhandenen Katalog. Jede Zeile kombiniert Geräte-/Aktionsauswahl und eine
Wartezeit vor dem Schritt (0 bis 86400 ganze Sekunden). Die Formularskripte iterieren
alle List-Zeilen; der ausgewählte Eintrag alleine wäre kein vollständiger Ablauf.
Die Zeilen bleiben bis zur ausdrücklichen Übernahme Formularwerte. Prüfung,
Speicherung und Aktivierung verwenden die bestehenden getrennten Workflows.

`SaveScheduledAutomation` besitzt den optional angehängten Parameter `ActionSteps`.
Alte Einzelaktionsaufrufe bleiben kompatibel, dürfen jedoch keine mehrschrittige
Regel verkürzen. Auswahlkennung, Prüfsumme und Sitzung schützen weiter den Entwurf.
Vollständig darstellbare Folgen werden wieder geladen. Nicht darstellbare Aktionen
oder Auslöser bleiben bei Metadatenänderungen vollständig erhalten. Schema 1 und
das Erweiterungsartefakt bleiben unverändert; deren Laufzeit unterstützt die Folge
einschließlich Stopp und erneuter Bedingungsprüfung bereits.

Der PHP-/JavaScript-Integrationstest führt das echte Formularskript mit iterierbaren
List-Werten aus und speichert die erzeugte Folge im realen Empfängerartefakt.
Ein synthetisches Ereignis führt zwei Geräteaktionen geordnet mit einer Sekunde
Abstand aus. Weitere Nachweise: Wiederladen aller Schritte, Umordnen, Hinzufügen,
Löschen bis auf einen Schritt, Grenzwerte, ungültige Listen ohne Entwurfsänderung,
veraltete Auswahl, Schutz vor Verkürzung durch alte Aufrufe, Erhalt anderer Regeln
und erweiterter Strukturen sowie zentrale Debug-Ausgaben und ein gestörter Debugkanal.
Auf ausdrücklichen Praxisauftrag wurde der Stand am 28.10.2026 in der Testinstallation
abgeglichen und neu geladen: Modul-, Formular- und Editorhashes stimmen überein,
der Reload meldete keine Warnungen oder Fehler. In der Konsolenoberfläche wurde
ohne YAML-Eingabe eine deaktivierte Zeitregel mit zwei Aktionen (0 und 5 Sekunden)
erstellt, geprüft, gespeichert und erneut geladen. Die zweite Wartezeit wurde auf
12 Sekunden geändert und ein dritter Schritt mit anderem Ziel und 3 Sekunden
ergänzt; Prüfung und Speicherung bestätigten die Änderung.

Automatisierte Ziehversuche änderten die Liste nicht. Der Anwender führte das
Umsortieren manuell aus; Screenshot und Debug bestätigten die Reihenfolge 3, 0,
12 Sekunden. Der Agent übernahm diese Liste über den Desktop-Formularbutton,
prüfte und speicherte sie. Frisches Laden lieferte exakt dieselbe Aktionsfolge;
Wartezeiten und Zielzuordnung blieben erhalten. Danach wurde der erste Schritt
über den Zeilen-Löschbutton entfernt. Übernahme, Prüfung, Speicherung und erneutes
Laden bestätigten genau die verbleibenden zwei Schritte (0 und 12 Sekunden).

Die erste Praxisprüfung zeigte einen Diagnosefehler: Der native onDelete-Kontext liefert
bei der unmittelbaren Löschmeldung noch den Vorbestand. InspectEditorActions
protokollierte deshalb drei Aktionen und deren Wartezeiten, obwohl die Oberfläche
anschließend zwei Zeilen zeigte. Die spätere Übernahme und Speicherung verwenden
die richtige verbleibende Liste und protokollieren zwei Schritte. Der bisherige
ArrayIterator-Test bildete diesen nativen Ereigniszeitpunkt nicht ab.

Nach gesondertem Korrekturauftrag kennzeichnet die sofortige Löschmeldung den
Vorbestand ausdrücklich als AktionenVorLöschen. Sie listet die übergebenen alten
Schritte nicht mehr als vorbereitete Folge auf und verweist auf die Prüfung der
verbleibenden Liste beim Übernehmen. Der Regressionstest scheiterte vor der
Korrektur und prüft jetzt Vorbestände mit zwei bzw. einer Aktion, fehlende falsche
Schritt-/Anzahlausgaben sowie die bestehenden Start-/Ende- und Datenschutzverträge.
Die korrigierte Datei wurde in die Testinstallation übertragen und syntaktisch
bestätigt. Erneutes natives Löschen von zwei auf eine und von einer auf null
Formularzeilen lieferte AktionenVorLöschen 2 bzw. 1, ohne erneute Schrittauflistung
und ohne Fehler. Entwurfsbytes, gespeicherte Revision und Regeln blieben unverändert;
die Engine blieb gestoppt, die Debug-Dateisitzung wurde beendet.
In schmalen Ansichten sind die rechten Bearbeitungs-
und Löschbuttons durch horizontales Scrollen erreichbar.

Die Testregel wurde über den bestätigten Lösch-/Prüf-/Speicherablauf entfernt.
Eine frische Abfrage bestätigte die ursprünglichen Regeln inhaltlich unverändert
bei fortgeschriebener Revision. Die Engine blieb durchgehend gestoppt.
Geräteaktionen wurden in diesem Bedienprüfungslauf nicht ausgeführt. Die
Debug-Dateisitzung wurde beendet. Der spätere reale Ausführungsnachweis steht oben.

## Zeitsteuerungen und Bedingungen im Klick-Editor

Neue Formularfelder und die eigene Schedule-Logik erzeugen das unveränderte Schema 1:
ein Geräte- oder täglicher Uhrzeit-Auslöser, eine Geräteaktion, optional ein
Zeitfenster und eine Gerätebedingung. Mindestdauer und Aktionsverzögerung bleiben
auf 86400 Sekunden begrenzt. Mehrere gleichartige Bedingungen, mehrere Auslöser,
Gruppen und Aktionsfolgen werden nicht vereinfacht; ihre Metadaten bleiben bearbeitbar.
Der additive numerische Katalog enthält nur lesbare Eigenschaften und kurze Einheiten.

Offline-Nachweise verwenden den echten JavaScript-Empfänger: Uhrzeit-/Zahlenregel
mit Nachtfenster und Wochentag erzeugen, prüfen, speichern, neu laden und bearbeiten;
danach Wechsel zu Geräteereignis mit Zustandsbedingung, Dauer und Verzögerung.
Ungültige Uhrzeiten, Zeitzonen, leere Tagesauswahl, identische Fenstergrenzen,
unbekannte Eigenschaften und untypisierte Zahlen lassen den Entwurf unverändert.
Die vorhandenen Engine-Tests sichern strikte Vergleiche, unbekannte Zustände,
UND-Bedingungen, Mitternacht, Sommerzeit und verpasste Zeitpunkte ab.

Auf ausdrücklichen Praxisauftrag wurden Modul und Erweiterung in der Testinstallation
aktualisiert. Quell-/Artefakthashes stimmen mit dem lokalen Stand überein; Reload
und MQTT-Installation wurden fehlerfrei bestätigt. Eine alte offene Registerkarte
zeigte noch die vorige Formularstruktur und wurde für die Prüfung neu geöffnet.

In der nativen Konsole wurde ohne YAML-Eingabe eine deaktivierte Regel erstellt:
täglicher Uhrzeit-Auslöser mit Zeitzone, Nachtfenster, eingeschränkten Wochentagen
und einer Temperaturbedingung aus dem neuen numerischen Katalog. Uhrzeiten mit
Minuten wurden korrekt um Sekunden ergänzt. Formular-Prüf-/Speicherbuttons
übertrugen die Regel nach Z2M; erneutes Laden lieferte exakt dieselben Bytes.
Die Regelauswahl stellte Zeiten, Zeitzonen, Wochentage, Vergleich und Zahlenwert
wieder her. Uhrzeit und Temperaturgrenze wurden geändert, erneut geprüft und
gespeichert. Regel-ID, Fenster, Wochentage und Aktion blieben erhalten. Eine
weitere frische Abfrage bestätigte die bearbeitete Konfiguration bytegenau.

Ein absichtlicher Versuch mit ungültiger Uhrzeit wurde mit deutschem Debug-Hinweis
abgewiesen und ließ den Entwurf unverändert. Nach Korrektur bestanden Übernahme,
Prüfung und Speicherung. Debug protokollierte die Abschlüsse; weitere Fehler
wurden nicht beobachtet. Die Debug-Dateisitzung ist beendet. Die gespeicherte
Testregel ist deaktiviert, die Engine gestoppt; es wurde kein Gerät geschaltet.
Die tatsächliche zeitgesteuerte Auslösung, Tageswechsel und Sommerzeit wurden in
diesem Bedienprüfungslauf nicht live ausgelöst; dafür gelten die Offline-Nachweise.

## Bearbeiten und Löschen im Klick-Editor

Die Erweiterung des Editors wurde offline gegen denselben echten JavaScript-
Empfänger geprüft: Auswahl über stabile IDs auch bei gleichen Namen, Vorbelegen
der Felder, Ändern von Ereignis/Aktion/Name/Aktivierung, Erhalt anderer Regeln,
Löschen der letzten Regel und anschließende Prüfung/Speicherung einer leeren
Konfiguration. Erweiterte Regeln erlauben Metadatenänderungen ohne Verlust ihrer
Bedingungen und Schritte. Auswahlkennungen binden Aktionen an den Entwurf;
veraltete Formulare, fehlende IDs und ungespeicherte Expertentexte werden abgewiesen.
Alle dynamischen Optionen und Listenwerte bleiben SDK-konform JSON-codiert.

Die neuen Auswahl-/Ändern-/Löschbuttons wurden anschließend auf ausdrücklichen
Auftrag in der nativen Testkonsole praktisch geprüft. Die geladenen Modul-,
Formular- und Editorquellen stimmen per Hash mit dem lokalen Stand überein;
der Modul-Reload meldete keine Warnungen oder Fehler. Eine vorhandene gestoppte
Testregel wurde ausgewählt, umbenannt und deaktiviert. „Änderungen in den Entwurf
übernehmen“ erhielt ihre ID, Auslöser und Aktionen. Die Änderung wurde über die
Formularbuttons in Z2M geprüft, bestätigt gespeichert und erneut unverändert geladen.

Beim Löschen blieb nach Abbruch des Bestätigungsdialogs der Entwurf unverändert.
Nach Bestätigung verschwand die ausgewählte letzte Regel zunächst nur lokal;
Z2M behielt bis zum Speichern seine vorige Revision. Der leere Entwurf wurde
über die Formularbuttons erfolgreich geprüft und gespeichert. Eine zusätzliche
frische Editorabfrage lieferte null Regeln zurück; Liste und Auswahl sind leer,
der Löschbutton ist deaktiviert. Die Konfiguration bleibt gestoppt. Geräte wurden
nicht geschaltet, die Z2M-Erweiterung benötigte kein Update oder Neustart.
Nativer Debug belegt Auswahl, Änderung, Löschung und die abgeschlossenen Prüf-/
Speicheraufträge ohne Fehlermeldungen. Die Debug-Dateisitzung wurde beendet.
Native Änderungen an erweiterten Regeln oder weiteren Gerätefeldern waren nicht
Teil dieses Laufs; dafür bleiben die beschriebenen Offline-Nachweise maßgeblich.

## Native Praxisprüfung: Klick-Editor und Steuerung

Auf der ausdrücklich freigegebenen Testinstallation mit Z2M 2.14.2-dev wurde
der Editor im geöffneten nativen Konsolenformular bedient: Geräte laden,
Quelle/Ereignis/Ziel/Aktion auswählen, Regel ohne sichtbaren YAML-Editor erzeugen,
validieren und als nächste Revision speichern. Die dynamischen Select-Optionen
und List-Werte benötigen JSON-codierte UpdateFormField-Werte; der Regressionstest
prüft diesen SDK-Vertrag auch bei bereits geöffnetem Formular.

Aktivieren mit Bestätigungsdialog und Stoppen wurden mit anschließenden frischen
Z2M-Bestätigungen, Statusvariablen und nativen deutschen Debug-Ausgaben geprüft.
Der Neustart wurde direkt über MQTT mit frischer Antwort, Offline-/Onlinewechsel
und neuer Erweiterungssitzung nachgewiesen. Die gespeicherte Revision blieb erhalten
und war anschließend inaktiv. Der exakte Artefakthash wurde mit dem von Z2M
gemeldeten Erweiterungscode verglichen.

Ein eigener MQTT-Set-Befehl erreichte auf diesem MQTT-Weg den Z2M-Empfänger
auch mit skipReceive=false nicht. Der begrenzte Echo-Kontrolltest erhielt keine
Rückzustellung; die native EventBus-Übergabe schaltete dagegen dasselbe Testgerät.
Die Engine verwendet deshalb nun Z2Ms interne Befehlsverarbeitung (ADR 0007).
Nach Installation des korrigierten Artefakts wurde die gespeicherte Regel über
den Symcon-Aktivierungsbutton bestätigt und praktisch ausgelöst: Das eingeschaltete
Testlicht meldete anschließend Aus. Nach Betätigung des Stoppbuttons blieb es
beim erneuten Einschalten An. Aktive Revision, Regelanzahl, gestoppter Zustand
und leere Fehleranzeige wurden über Formular und Statusvariablen kontrolliert.
Der native Moduldebug belegt Start/Ende und abgeschlossene Steueraufträge.
Temporäre Diagnoseerweiterung und Debug-Dateisitzung wurden nach Prüfung beendet;
die gespeicherte Testregel bleibt gestoppt. Private Gerätedaten sind nicht Teil
dieses Berichts. Ein vollständiger Ausfall des Symcon-Dienstes wurde nicht erzeugt;
die Unabhängigkeit bei getrenntem Datenfluss bleibt zusätzlich offline abgesichert.

Es gelten AGENTS.md und die zentrale Basis in ../SymconDevelopment/.
Zielplattform: IP-Symcon 9.0 / PHP 8.5. Branch: dev.

## Abhängigkeiten

Nach dem Klonen: git submodule update --init --recursive.
.style stammt unverändert aus symcon/StylePHP, tests/stubs unverändert aus
symcon/SymconStubs. Die Gitlinks pinnen den jeweiligen Stand.
DataFlowHelper, ParentConnectionHelper, VariablePresentationHelper und ConfigurationFormHelper sind
unverändert aus dem kanonischen Consumer-Bundle eingebunden; Herkunft und Hashes
stehen in libs/helper/manifest.json. .helper-sync.json deklariert die Auswahl.
DebugFlowHelper und dessen DebugHelper-Abhängigkeit werden ebenfalls über das
kanonische Bundle bezogen. ModuleDiagnostics delegiert nur noch an diesen Ablauf.
Der zentrale Consumer-Eintrag und GitHub-App-Zugriff wurden hier nicht eingerichtet.
Node ab 22.2 und npm ci sind für die Erweiterungstests erforderlich. package-lock.json
fixiert js-yaml 5.4.2 und Ajv 8.20.0 als lokale Testabhängigkeiten. In Z2M werden
die vorhandenen Host-Abhängigkeiten verwendet, kein npm-Verzeichnis mitgeliefert.

## Lokale Prüfungen

- php .style/json-check.php
- php /path/to/php-cs-fixer.phar fix --config=.style/.php-cs-fixer.php --dry-run --diff --using-cache=no --allow-risky=yes
- npm ci --ignore-scripts --no-audit --no-fund
- npm run check:extension
- php tests/run.php
- php tests/validate_structure.php
- npm test (nur Erweiterungstests)

`tests/runtime.test.mjs` prüft die Engine über das erzeugte Artefakt mit
synthetischen Z2M-Geräten, virtueller Zeit und echtem temporärem YAML-Speicher.
Aktivierung, Abbruch, Mindestdauer, Zeitfenster, Sommerzeit, Sequenzen,
Transportfehler und Mengenbegrenzung sind eigene Verhaltensnachweise.

`tests/module-extension.php` verwendet den echten Node-Empfänger zusätzlich für
Gerätekatalog, Klick-Entwurf, Aktivierung und Stopp mit anschließender Verifikation.
Der Datenfluss wird während einer synthetischen Geräteaktion getrennt: Die
gespeicherte Regel muss unabhängig vom Symcon-Modul weiter ausgeführt werden.
Negative Fälle prüfen fremde Revision, retained ACK, Timeout, Stoppvorrang,
unveränderte vorhandene Regeln, ungespeicherte Expertentexte und defektes Debug.
Das Formular wird in den Stubs strukturell geprüft. Seine reale Bedienung in
der nativen Konsole ist noch praktisch zu prüfen; kein Live-Schalten durch diese Tests.

Die beiden ersten Befehle verwenden die offizielle StylePHP-Konfiguration.
Der unveränderte StylePHP-JSON-Checker durchsucht rekursiv auch node_modules;
seine Vollprüfung erfolgt deshalb in einem Checkout ohne installierte Node-
Abhängigkeiten. CI hält Style- und Testjob entsprechend getrennt.
CI nutzt Symcon_ModuleCI v1.0.0 für StylePHP und PHP-Tests. Zusätzlich läuft
php-cs-fixer explizit im Prüfmodus mit festgelegter Werkzeugversion.

tests/run.php umfasst Strukturprüfung, Helper-Integrität, Instanzerstellung,
wiederholtes ApplyChanges, MQTT-Integration mit synthetischen Gateways und
Erweiterungstests mit echten Dateien in temporären Testverzeichnissen.
Die PHP-Tests laden das produktive Modul gezielt über loadSingleModule, weil
der offizielle Stub-Lader node_modules sonst fälschlich als Modul behandelt.
Library-Metadaten prüft weiterhin tests/validate_structure.php.
Diese Nachweise ersetzen keine Symcon-Laufzeitprüfung.

tests/module-extension.php testet die produktive PHP-Integration gegen das echte
Erweiterungsartefakt in einem Node-Teilprozess. Der Peer simuliert ausschließlich
nativen MQTT-/extension-save-Rahmen in einem temporären Z2M-Verzeichnis. Geprüft
werden Installation mit Bereitschaftsprobe, Draft-Prüfung, Persistenz und
idempotentes Speichern. Weitere deterministische Fälle decken falsche/retained
und verspätete Antworten, Prüfsummen, Versionen, Revisionskonflikte, Sitzungs-
wechsel, Speicherlesemodus, Größen-/UTF-8-Grenzen, Kontextwechsel und Fehler ab.
Das Formular wird über den echten ConfigurationFormHelper geprüft; seine
Bestätigungsfelder und Übersetzungen sind Teil der Strukturprüfung.

## Symcon-Integration: Nachweis am 27.10.2026

Vor Implementierung scheiterte der neue Test an der fehlenden InstallExtension-
Methode. Der integrierte PHP-/JavaScript-Pfad besteht nun mit echten temporären
Dateien und der produktiven Erweiterung: Upload des exakten Artefakts, frische
Bereitschaftsprobe, Draft-Prüfung, Vorschau, bestätigte Speicherung, idempotente
Wiederholung und Neustart mit erneut erforderlicher Prüfung.

Die gesamte tests/run.php-Suite einschließlich der 13 Erweiterungstests besteht
unter PHP 8.5.10 mit Node 24.20.0 und zusätzlich über Node 22.2.0. Fehlerfalltests
prüfen auch das Widerrufen einer vorherigen Bereitschaft bei Protokollfehlern.
Struktur-/Helper-Integritätsprüfung, offizieller StylePHP-JSON-Check und
php-cs-fixer 3.95.27 im Prüfmodus sind erfolgreich. Der JSON-Check läuft wie
dokumentiert vorübergehend ohne node_modules im Quellbaum; diese Abhängigkeiten
werden anschließend wiederhergestellt. Keine Änderungen an offiziellen Quellen.

Nicht ausgeführt: GitHub-CI, native MQTT-/Z2M-Installation und Bedienprüfung des
Formulars in der echten Konsole. Das JSON-/Callback-/Bestätigungsformat wurde
gegen die offizielle Dokumentation und die Integrationstests geprüft.

## Erweiterungsartefakt

Nach Änderungen an libs/z2m/src/extension.mjs oder automations.schema.json:
npm run build:extension. Der Builder ersetzt ausschließlich den Schema-Import
durch das kanonische Schema und erzeugt libs/z2m/z2m-automations.mjs.
Kein Fremdcode wird gebündelt. npm test und tests/run.php prüfen vor Ausführung,
dass das Artefakt exakt zu den Quellen passt. Nur dieses Einzeldatei-Artefakt
gehört bei einer später beauftragten Installation nach data/external_extensions.

## Grenzen

Noch keine native MQTT- oder Z2M-Praxisprüfung. Keine Live-Installation,
Freischaltung, Neustarts oder Schaltbefehle ohne ausdrücklichen Praxisauftrag.
Commit und Push macht ausschließlich der Benutzer über GitHub Desktop.

## Nachweis des Grundgerüsts am 27.10.2026

- PHP 8.5.10: tests/run.php und tests/validate_structure.php erfolgreich.
- PHP-Syntaxprüfung aller vier eigenen PHP-Dateien erfolgreich.
- Offizieller StylePHP-JSON-Check erfolgreich.
- PHP CS Fixer 3.95.27 mit offizieller StylePHP-Konfiguration im Prüfmodus:
  keine Änderungen an den vier eigenen PHP-Dateien erforderlich.
- Eigene Textdateien als UTF-8 ohne BOM mit LF-Zeilenenden geprüft.
- Beide offiziellen Submodule unverändert und über Gitlinks festgelegt.
- Git-Diff-Prüfungen ohne Whitespace-Fehler.
- GitHub-Actions-Ausführung und echte Symcon-/Z2M-Laufzeit noch nicht geprüft.

## MQTT-Ausbaustufe

Der Fehltest vor Implementierung scheiterte an der noch fehlenden Active-Property.
Die Integrationstests verwenden offizielle Kernel-Stubs und synthetische MQTT-
Eltern für Client und Server. Die Testuhr wird über eine abgeleitete Modulklasse
ersetzt; Kernel-Nachrichten werden wegen der Stub-Grenzen explizit zugestellt.
Keine Änderungen an offiziellen Stubs oder Helper-Quellen.

Geprüft werden Strict-HEX, passende und unpassende Topics, Unicode und JSON-
Maskierung, normalisierter Cache ohne Geheimnisse, Transaktionszuordnung,
Retain-Antworten, verspätete und sofortige Antworten, fehlgeschlagener Versand,
Zeitlimit, Deaktivierung, Verbindungsabbruch, Topic-/Gateway-Wechsel und Kernelstart.
Nachweise für native MQTT-Gateways und das Konsolenformular bleiben offen.

Abschlussprüfung am 27.10.2026 unter PHP 8.5.10: tests/run.php,
tests/validate_structure.php, offizieller StylePHP-JSON-Check und PHP CS Fixer
3.95.27 mit offizieller Konfiguration im Prüfmodus erfolgreich. Alle acht eigenen
PHP-Dateien bestehen die Syntaxprüfung. Helper-Hashes stimmen mit dem Manifest;
offizielle Submodule sind unverändert. Eigene Textdateien verwenden LF ohne BOM,
git diff --check ist sauber. GitHub-CI wurde lokal nicht ausgeführt.

## Erweiterung: Nachweis am 27.10.2026

- Der erste Fehltest bestätigte das fehlende Einzeldatei-Artefakt vor Implementierung.
- 13 Node-Tests mit echten temporären Dateien bestanden unter Node 24.20.0 und
  zusätzlich unter der vorgesehenen Mindestversion Node 22.2.0.
- Geprüft: eigenes Beispieldokument, strikte Syntax-/Schema-/Semantikprüfung,
  Schreibfreiheit von validate, Konflikte, Idempotenz, Sitzung und Replay,
  serialisierte begrenzte Warteschlange, Open-/Write-/Sync-/Close-/Rename-Fehler,
  beschädigte Dateien, Sicherungslesemodus, externe Änderungen und MQTT-Ausfälle.
- Der Test einer kopierten Einzeldatei in synthetischem data/external_extensions
  prüft die tatsächliche Ableitung des persistenten Datenpfads ohne Test-Override.
- tests/run.php prüft zusätzlich alle bisherigen PHP-/MQTT-Tests und die
  reproduzierbare Übereinstimmung von Quellen, Schema und Artefakt.
- Offizieller StylePHP-JSON-Check und php-cs-fixer im Prüfmodus erfolgreich.
  node_modules wurde dafür vorübergehend außerhalb des Quellbaums gehalten und
  anschließend unverändert zurückgelegt. Offizielle Quellen bleiben unverändert.
- Noch keine reale Installation, kein Broker-Verbindungstest und keine Abnahme
  in Z2M; keine Ausführungsengine implementiert oder geprüft.

## Verständliche Anwendertexte

Alle 300 deutschen Übersetzungseinträge wurden auf Verständlichkeit geprüft.
Status- und Fehlermeldungen nennen zuerst das Ergebnis und anschließend eine
konkrete Handlung, sofern eine erforderlich ist. „Entwurf“ bezeichnet die noch
in Symcon bearbeitete Fassung, „Speichern“ die Übertragung nach Z2M und
„Aktivieren“ den Start der eingeschalteten Regeln. Unbekannter Status wird nicht
als bestätigter Fehler dargestellt. Fehlende Gerätedaten bleiben „Noch nicht
prüfbar“, abgewiesene Ereignisse „Nicht importierbar“.

Interne Begriffe wie Empfänger, Revision, Sitzungskennung oder Protokollversion
werden in Bedienmeldungen vermieden bzw. erklärt. Technische Einstellungen,
Variablennamen, Fehlercodes, Platzhalter und Debug-Strukturen bleiben stabil.
Für längere Meldungen verwenden Formular-Labels die verfügbare Breite.
Die Übersetzungsprüfung umfasst gültiges JSON und unveränderte sprintf-Argumente.
Die Fachlogik und die Z2M-Erweiterung wurden dabei nicht geändert.

## Präzise Importdiagnose

Die Live-Diagnose wurde ausschließlich lesend durchgeführt. Eine gültige
MQTT-Verbindung ist kein Nachweis für eine unterstützte einzelne Variable.
Die Bindungsprüfung meldet deshalb getrennt: fehlende Variable, fremdes Modul,
fremde Verbindung, anderes Topic, ungültige Gerätekennung, fehlende Gerätedaten,
nicht unterstützte Eigenschaft, fehlenden Lese-/Schreibzugriff und widersprüchliche
Variablenzuordnung. Lesbare Sensoren benötigen als Auslöser keine Schaltaktion.
Die vorhandenen Zulässigkeitsprüfungen werden dabei nicht gelockert.
Liste und Vorschau verwenden denselben lokalisierten Text mit getrennten
Formatargumenten für Variablenart und Anzeigenamen. Debug protokolliert nur den
stabilen Meldungsschlüssel; private Variablennamen bleiben aus den Debug-Daten.

Namenlose native Ereignisse erhalten erst in Darstellung und Entwurf einen
stabilen, bearbeitbaren Ersatznamen aus der Ereignisnummer. Ursprüngliche
Ereignisse bleiben unverändert. Synthetische Integrationstests prüfen diese
Fälle einschließlich nativer verschachtelter Tasteraktionen, fehlender
Eigenschaften am richtigen Gateway und tatsächlich fremder Verbindungen.
Keine produktiven Änderungen oder Schaltbefehle gehören zu diesem Nachweis.

## Gerätedaten ohne Entwurfsverlust nachladen

LoadImportDevices verwendet den vorhandenen Editor-Workflow mit preserveDraft.
Die vollständig validierte Antwort aktualisiert den Katalog nur, wenn Kontext,
gespeicherte Ausgangsrevision/-prüfsumme und die lokale Entwurfsprüfsumme noch
zusammenpassen. Bei erhaltenem Entwurf wird nur die Empfängersitzung erneuert;
YamlDraft, Regeln, Aktivierungsflags und Auswahl bleiben erhalten. Ein fehlender
Entwurf wird wie bisher initialisiert. LoadAutomationEditor behält ausdrücklich
seine nach Bestätigung ersetzende Funktion. Fehler geben keine Importfreigabe.

Der Katalog reserviert unter denselben Grenzen erst binäre Schaltkanäle, dann
enumerierte state-/action-Eigenschaften für Rollläden und Taster, erst danach
Sonderoptionen. Synthetische Tests reproduzieren späte Geräte hinter sehr
umfangreichen Sonderoptionen und prüfen bytegleichen Entwurfserhalt sowie die
Ablehnung widersprüchlicher manueller YAML-Änderungen. Die gebaute Erweiterung
bleibt aus den lokalen Quellen erzeugt und enthält keine fremde Implementierung.

Freigegebene Live-Prüfung: Modul und Erweiterung aktualisiert, Gerätedaten über
LoadImportDevices neu gelesen und der lokale Entwurf unmittelbar danach bytegleich
mit der Sicherung verglichen. Alle drei vorhandenen Regeln blieben unverändert.
Zuvor fehlende Taster- und Markisenfunktionen wurden anschließend erkannt und
vier weitere Regeln als deaktivierte Entwürfe übernommen. Die ursprünglichen
Symcon-Ereigniskonfigurationen blieben unverändert. Native Debug-Ausgaben belegten
Entwurfserhalt und jede Übernahme. Gespeicherte Z2M-Prüfsumme und Revision blieben
unverändert; die Ausführung blieb gestoppt. Private Geräte- und Ereignisdaten
wurden nicht in diesen Nachweis oder in Testfixtures übernommen.

## Detaillierte Validierungsrückmeldung

`ValidationFeedback` normalisiert ausschließlich bekannte Diagnosefelder und
formuliert sie für Formular und Fehler-Statusvariable. Namen werden aus der
geprüften Editor-Anfrage aufgelöst; sie erscheinen nicht im Debug oder öffentlichen
Transportstatus. Der Prüfungsbericht ist unabhängig von `lastError`: Start und
Abschluss einer Statusabfrage dürfen ihn weder löschen noch ausblenden. Erst ein
neuer tatsächlich angenommener Validierungsauftrag setzt ihn zurück. Andere
Vorgangsfehler erscheinen zusätzlich, damit die erhaltene Diagnose keine aktuelle
Störung verdeckt. Debug erklärt Erhalt und Ablösung ohne Regelinhalte.
Kein neuer zentraler Helper: Diagnosepfade und Ursachen sind
Teil des librarylokalen Automationsprotokolls. ConfigurationFormHelper und
DebugFlowHelper bleiben unverändert eingebunden.

Die synthetischen Tests prüfen 50 Regeln mit mehreren tatsächlichen Fehlern,
unterschiedliche Schema-Zweige, Semantik, Syntaxpositionen, Begrenzung auf 20,
ungültige Rückmeldungen, fehlende Details alter Empfänger, erneuten Formularabruf,
Live-Feldaktualisierung, bereinigte Debug-Ausgaben und Löschen nach erfolgreicher
Prüfung. Der Regressionstest reproduzierte zuerst die Löschung durch den echten
automatischen Timerpfad (Health und Erweiterungsstatus). Er prüft jetzt den
Erhalt bei automatischen, wartenden, fehlgeschlagenen und erfolgreichen manuellen
Abfragen sowie das Zurücksetzen vor einer neuen Validierungsantwort. Native
Live-Konsole und reale Installation sind für diese Korrektur noch
nicht geprüft; keine Produktivdaten oder echten Geräteaktionen in den Tests.

Nachweise: `php tests/run.php` einschließlich 67 JavaScript-Tests und der echten
PHP-/JS-Integration, Strukturprüfung, offizielle StylePHP-JSON-Prüfung,
php-cs-fixer 3.95.27 im Prüfmodus und aktuelle Erweiterungserzeugung erfolgreich.
## Statusabgleich nach Speicherung

Die partielle Speicherantwort enthielt bisher alte Mesh-/Wiederanlauffelder aus
dem Vorabstatus. Der nächste vollständige Broadcast entzog deshalb erneut die
Statusbestätigung und sperrte den Aktivierungsbutton. `verifyStorage` führt den
Speicherworkflow jetzt mit einer eigenen frischen Statusrückfrage fort. Der
gespeicherte Snapshot muss vollständig zur Antwort passen; Fehler ergeben
`stored_unconfirmed`. Die Vorschau behandelt einen exakten Prüfsummentreffer
als bereits gespeicherten Entwurf, ohne eine neue Speicherfreigabe zu erzeugen.

`store-confirmation.inc.php` reproduzierte zunächst die fehlende Rückfrage.
Es prüft frische gegenüber retained Antworten, geänderte Sitzungen, Revisionen,
Prüfsummen, Regelanzahlen, Speicher- und Laufzeitzustände, Timeout ohne erneuten
Schreibversuch, spätere manuelle Erholung und unverändert gespeicherte aktive
Stände. Die bestehende echte PHP-/JS-Integration prüft denselben Ablauf über
das erzeugte Artefakt. Nur Moduländerung; noch keine Installation oder native
Praxisprüfung der Korrektur.

## Eigener MQTT-Debugkanal (lokaler Stand)

`RuntimeDiagnostics` in der Erweiterung und der PHP-Trait `RuntimeDebug` bilden
einen eigenständigen begrenzten Aufzeichnungspfad. `RuntimeDebugContract` und
der beim Build eingebettete eigene `debug-events.json` begrenzen die Ausgabe
auf bekannte Meldungen, Positionen und sichere Laufbezüge. Passende zentrale
Helper wurden geprüft: DebugFlowHelper, DataFlowHelper und ConfigurationFormHelper
werden unverändert weiterverwendet. Kein weiterer zentraler Helper ist nötig.
Quellstand und Buildartefakt enthalten ausschließlich unsere Implementierung.

Die Bedienung befindet sich in „Einrichtung und Diagnose“ mit Dauerwahl und
Start/Stopp. Steuerantworten korrelieren Sitzung, Client, Transaktion, Dauer und
Zustand. Ohne Antwort bleibt die Aufzeichnung unbestätigt und endet lokal nach
dem Antwortzeitlimit. Ein eigener einmaliger Timer beendet die lokale Freigabe
auch ohne Folgepakete. Verbindungs-/Sitzungswechsel und ApplyChanges beenden
sie ebenfalls. Eine mögliche Remote-Freigabe endet unabhängig davon nach ihrer
ursprünglichen Frist. Andere Clients werden nicht beendet. Automationsstatus,
Entwurf, Persistenz und Gerätebefehle werden durch Aufzeichnen nicht verändert.

Frische Meldungen werden ohne erneutes SaveState pro Datensatz durch den
kanonischen DebugFlowHelper geführt. Die ursprünglichen lokalen Funktionspaare
bleiben ausgeglichen; Z2M-Start/Ende sind fachliche Meldungen mit eigener Phase.
Mesh übergibt nur einen optionalen geprüften Laufbezug in `prepare`, bewahrt ihn
in der Challenge und verwendet ihn bei `execute`. Regelnummer/Revision bezeichnen
auch am Empfänger den Ursprung, nicht einen gleich nummerierten lokalen Eintrag.

Neue Nachweise:

- `debug.test.mjs`: aus im Grundzustand, begrenzte unabhängige Freigaben,
  Sanitizing, Mengenlimit, sichtbare Verluste, hängender/fehlerhafter Publish,
  keine Wiederholung alter Meldungen und kein Versand nach Stopp.
- `runtime.test.mjs`: vollständige verzögerte Folge, erfüllte/unerfüllte
  Bedingungen, Mindestdauerabbruch, genauer Fehlerschritt, Zeittrigger und
  Stopp während Wartezeit; schaltender Pfad trotz hängendem Debug; keine
  automatische Aufzeichnung nach Neustart, auch bei wiederverwendetem Objekt.
- `mesh.test.mjs`: zwei echte Erweiterungen behalten einen gemeinsamen Lauf
  samt Schritt/Revision/Ursprung. Doppelte Zustellung bewirkt keine zweite Aktion.
- `runtime-debug-contract.php`: positive Grenzwerte und strikt abgewiesene
  unbekannte Felder, Typen, Sitzungen, Uhrzeiten und Meldungstexte.
- `runtime-debug.inc.php`: echter PHP-/JS-Datenfluss über das erzeugte Artefakt,
  native deutsche Ausgabe, Wiederöffnen, Statusabfrage, bestätigter Stopp,
  Duplikate, retained Daten/Antworten, unbekannte Texte, Zeitablauf, fehlende
  Bestätigung, erneuter Start, ApplyChanges und Sitzungswechsel. Nur synthetische
  Daten; Schaltbefehle bleiben an der synthetischen Z2M-Schnittstelle.

`php tests/run.php` besteht einschließlich 81 JavaScript-Tests, Kodierungs-,
Struktur- und Größenprüfung sowie echter PHP-/JS-Integration. Zusätzlich bestanden:
`php tests/validate_structure.php`, `npm run check:extension`, offizieller
StylePHP-JSON-Check in einer temporären Kopie der eigenen JSON-Dateien ohne
node_modules/Fremdquellen und PHP CS Fixer 3.95.27 im Prüfmodus.
Die umfangreiche Integration gibt ihre bereits vollständig geprüften
Debug-Mitschnitte vor der getrennten Negativtestphase frei, um unter dem
normalen PHP-Speicherlimit zu bleiben. Keine Änderung an Fremdquellen,
keine Installation und keine Änderungen am Live-System. Native Konsole und
reale Brokerzustellung dieses neuen Kanals sind noch praktisch zu prüfen.

## MQTT-Debugkanal: Praxisprüfung am 02.11.2026

Auf ausdrücklichen Auftrag Test-Symcon und seine beiden real genutzten
Z2M-Systeme geprüft. Das Testmodul samt Formular, Übersetzung, Vertrag,
Meldungskatalog und Uploadartefakt war bereits dateigenau auf dem geprüften
lokalen Stand; die neuen öffentlichen Methoden waren registriert. Kein
unnötiger Reload. Beide Z2M-Erweiterungen anschließend über den echten
Installationsauftrag des Moduls aktualisiert. Die vorherigen Erweiterungen,
YAML und Metadaten wurden ausschließlich außerhalb des Repositorys gesichert.

Die offizielle Uploadantwort und die nachfolgende Sitzungs-/Statusbestätigung
bestanden. Zusätzlich entsprach der von Z2M veröffentlichte Erweiterungscode
bytegenau dem lokalen Uploadartefakt. Beide Laufzeiten nahmen ihre bestehende
Betriebsfreigabe wieder auf; Mesh und automatischer Wiederanlauf waren bereit,
Ausführungsfehler leer. Kein Neustart der Z2M-Dienste erforderlich. Andere
installierte Erweiterungen blieben bytegleich.

Frische Statusanfragen erzeugten echte Meldungen im eigenen MQTT-Debugkanal.
Symcons native Debugdateien belegten die bestätigten Aufzeichnungsstarts,
feste deutsche Z2M-Ablaufmeldungen und getrennte korrekte Systembezüge. Wegen
des stark belegten gemeinsamen Debugpuffers wurde der Nachweis zusätzlich über
die gezielt gestarteten nativen Datei-Aufzeichnungen geführt. Keine allgemeinen
Z2M-Rohlogs oder Änderung des globalen Protokolllevels.

Geprüfte Abläufe:

- Über öffentliche Modulmethoden Start mit passenden Antworten auf beiden
  Systemen; Formulartext „Aufzeichnung läuft“ und freigegebener Stopp.
- Verkürzte erlaubte API-Testfrist von 20 Sekunden: echter nativer Timer endet
  ohne manuelles Auslösen, deutscher Ablaufhinweis und deaktivierter Stopp.
  Die längere Aufzeichnung am zweiten System blieb währenddessen aktiv.
- Vorzeitiges Beenden mit `seconds: 0`: frische Bestätigung, Formularmeldung
  über beendete eigene Aufzeichnung, anschließend keine weiteren fachlichen
  Z2M-Debugmeldungen in dieser Modulinstanz.
- Zwei unabhängige Clients an derselben Erweiterung: vor dem eigenen Stopp
  zwei Empfänger, danach nur der zweite Client. Dessen Aufzeichnung lief
  weiter, während die gestoppte Modulinstanz keine fachlichen Meldungen mehr
  annahm. Auch der zusätzliche Client wurde anschließend beendet.
- Zusätzlicher Acht-Sekunden-Test direkt am tatsächlichen MQTT-Ausgang:
  14 frische Ablaufpakete, letzte Ankunft nach rund sieben Sekunden; danach
  trotz vier weiterer Statusanfragen keine Pakete mehr. Damit ist die
  Frist auch auf Z2M-Seite und nicht nur durch die lokale Anzeige belegt.

Grenzen: Die Methoden wurden per MCP aufgerufen und die tatsächlichen
Formularzustände ausgelesen; kein Zeigerklick in der nativen Konsole.
Die Zuordnung jedes tatsächlichen MQTT-Kanals zum richtigen System ist
bestätigt. Der Nutzer konnte den vorgesehenen Taster nicht betätigen.
Deshalb wurde keine reale Geräteaktion ausgelöst; der gemeinsame Regellauf-
und Schrittbezug einer systemübergreifenden Schaltaktion bleibt praktisch
offen. Sein bisheriger Offline-Nachweis mit zwei echten Erweiterungsobjekten
wird dadurch nicht zu einem Hardware-Nachweis.

Aufräumen und Erhalt: alle Test-Aufzeichnungen beendet oder abgelaufen,
zusätzliche WebSocket-Verbindungen geschlossen, gezielt gestartete native
Debugsitzungen/Datei-Aufzeichnungen beendet und temporären Modulmonitor samt
seinen Variablen entfernt. Gespeicherte YAML beider Systeme, Revisionen und
Prüfsummen blieben unverändert. Der vorhandene lokale Formularentwurf blieb
bytegleich. Nach Entfernen des Monitors liefen beide bestehenden
Betriebsfreigaben weiterhin mit Mesh, Wiederanlauf und leerem Laufzeitfehler.
Keine Originalereignisse, Gerätezuordnungen oder MQTT-Brokerrechte geändert.
Keine Änderungen am Produktiv-Symcon-Modul; die gemeinsam genutzten realen
Z2M-Erweiterungen wurden wie beauftragt aktualisiert. Keine Änderung am
Funktionscode dieses Repositorys im Rahmen dieses Praxisauftrags.

## Sonnenauf- und Sonnenuntergang: lokale Implementierung

Der Zeitbereich bietet feste Uhrzeit, Sonnenaufgang und Sonnenuntergang mit
Standort und Versatz. AutomationEditorSolar verwendet die bestehenden Formular-
und Debug-Helper unverändert. Vorhandene Helper wurden geprüft: GeofenceHelper
behandelt Geofencing, nicht astronomische Zeitplanung; kein neuer zentraler
Helper ist erforderlich. Die fachliche Umsetzung bleibt librarylokal.

Die neue Auswahl blendet Standort und Zeitversatz statt Uhrzeit ein. Die
Optionsübergabe bleibt im bestehenden SaveScheduledAutomation-Vertrag.
Punkt/Komma sowie kleine gültige YAML-Zahlen bleiben nach Vorbelegung bearbeitbar.
Fehlende/ungültige Angaben ergeben eine verständliche Ablehnung ohne Änderung
des Entwurfs. Debug nennt Ereignis, Versatz und Ergebnis, keine Koordinaten.

Schema 1 erhält die additive sun-Variante. Die eigene reine Berechnung in
src/solar.mjs verwendet veröffentlichte NOAA-Gleichungen, keinen Code oder
interne Strukturen des fremden Automationsprojekts. Kein Internetdienst,
kein zusätzliches Paket und keine Symcon-Nachlieferung. Apparenthorizont
90,833 Grad, keine Höhen-/Gelände-/Wetterkorrektur, Rundung auf volle Minuten.
Es ist eine Näherung, insbesondere an polaren Übergängen.

Die Runtime hält pro Auslöser ein begrenztes Fenster einschließlich Nachbartagen
für Versätze über Mitternacht. Tagesbezug ist der lokale Tag des Sonnenereignisses
vor Anwendung des Versatzes. Auslösung an absoluter UTC-Sekunde und höchstens
einmal je Ereignistag. Kein Nachholen und keine Wiederholung durch Rücksprung
innerhalb der Aktivierung. Neustart lädt die gespeicherte Betriebsfreigabe und
berechnet neu. Ohne Ereignis wird ohne Ersatzzeit übersprungen. Bedingungen,
Verzögerungen und Geräte-/Szenen-/Mesh-Aktionen bleiben derselbe Ausführungspfad.

Nachweise: unabhängige PHP-date_sun_info-Referenzen für synthetische Koordinaten,
Jahreszeiten, Schaltjahr und beide Längengradvorzeichen (geprüfte normale Fälle
unter fünf Minuten Abweichung). Polartage, Zeitzone, Rundung, Versätze ±12 Stunden,
Mitternacht, verpasste Zeiten, Rücksprung, Bedingungen, Wiederanlauf und eine
Remote-Aktion mit zwei echten Erweiterungsobjekten sind synthetisch geprüft.
Die PHP-/Node-Integration führt die echten Formularskripte für Erstellung und
Bearbeitung aus: Prüfung, Speicherung, Wiederladen, Vorbelegung, Fehlerschutz
und Rückwechsel zur Uhrzeit. Diagnosepfade enthalten nur Feldpositionen/Grenzen.

Die lokale Implementierung allein enthält keinen Praxisnachweis. Die folgende
native Prüfung ergänzt den Editor-/Speichernachweis; reale Sonnenausführung
und der separat vereinbarte reale Debugtest werden dadurch nicht vorweggenommen.

Nachweise bestanden: `php tests/run.php` mit 88 JavaScript-Tests und echter
PHP-/JS-Integration, anschließend erneuter Integrationslauf mit dem tatsächlichen
Astro-Auswahl-Callback. Struktur- und Kodierungsprüfung, offizieller StylePHP-
JSON-Check eigener Projektdateien ohne Fremdquellen, PHP CS Fixer 3.95.27 im
Prüfmodus sowie `npm run check:extension` bestanden. Alle geänderten Textdateien
als UTF-8 mit LF geprüft. Keine neuen Abhängigkeiten oder Änderungen an Helpern.

## Astro-Klick-Editor: native Praxisprüfung

Auf ausdrücklichen Auftrag über Test-Symcon und dessen native Windows-Konsole
geprüft. Das angebundene Z2M wird gemeinsam mit der Produktivinstallation genutzt.
Modulquellen, Formular, Übersetzung, öffentliche Astro-Funktion und hochladbare
Erweiterung waren bereits dateigenau aktuell; kein unnötiges Überschreiben oder
Neuladen. Zwei temporäre Modulinstanzen erhielten den vorhandenen Partnerkontext,
damit der ursprüngliche lokale Formularentwurf unangetastet bleiben konnte.

Eine ausgeschaltete Testregel wurde per Klick-Editor mit Sonnenuntergang,
negativem Minutenversatz, synthetischen Koordinaten und einem nicht ausgeführten
Geräteaktionsschritt angelegt. Die Auswahl blendete die Uhrzeit aus und Standort
sowie Zeitversatz ein. Dezimalkomma wurde korrekt normalisiert. Ein Breitengrad
außerhalb des erlaubten Bereichs wurde verständlich abgewiesen; der Entwurf blieb
bytegleich. Nach gültiger Erstellung zeigte die Liste ausdrücklich „Nein“.

Die Regelauswahl belegte Name, ausgeschalteten Zustand, Sonnenereignis, Standort,
Zeitzone, Versatz und Aktionsliste korrekt vor. Änderung auf Sonnenaufgang und
positiven Versatz wurde über den nativen Übernahmebutton bestätigt. Anschließend
Z2M-Prüfung und Speicherung über die nativen Buttons erfolgreich. Z2M bestätigte
den gespeicherten Stand durch frischen Status; die Laufzeit blieb während der
Testspeicherung gestoppt. Ein zusätzlicher direkter lesender Abruf aus Z2M
bestätigte die gespeicherten Werte und den ausgeschalteten Regelzustand.
Wiederladen in den Editor und erneute native Regelauswahl belegten alle Werte
erneut korrekt vor.

Native Debug-Datei geprüft: Astro-Auswahl, ungültige Koordinaten, Vorbereitung
beider Sonnenereignisse mit Versatz, erfolgreiche Vorbelegung, Z2M-Prüfung und
Speicher-/Statusbestätigung sind nachvollziehbar. Die Testkoordinaten erschienen
nicht in den aufgezeichneten Debugmeldungen. Keine Testregel wurde aktiviert,
keine Geräteaktion oder Szene aufgerufen und kein Z2M-Neustart durchgeführt.

Aufräumen: ursprüngliches Dokument einschließlich Partnerfreigaben bis auf die
fortgeschriebene Revision identisch wiederhergestellt; ausschließlich dessen
vorherige Betriebsfreigabe erneut aktiviert. Ausführung, Mesh und Wiederanlauf
bestätigt, Laufzeitfehler leer. Das andere System blieb im bisherigen Stand
aktiv. Alle temporären Modulinstanzen entfernt, Debug-Dateiaufzeichnung beendet,
zusätzliche Leseverbindungen geschlossen. Der ursprüngliche lokale Formularentwurf
blieb bytegleich. Produktiv-Symcon-Modul, Originalereignisse, Gerätezuordnungen
und Brokerkonfiguration wurden nicht geändert. Keine Änderung am Funktionscode
für diesen Praxisauftrag; tatsächliche zeitliche Auslösung bleibt ungeprüft.

Abschließende Projektprüfungen erfolgreich: `php tests/run.php` einschließlich
88 JavaScript-Tests, PHP-/JS-Integration, Kodierungs- und Artefaktprüfung;
separate Strukturprüfung, offizieller StylePHP-JSON-Check einer temporären Kopie
der eigenen JSON-Dateien ohne Fremdquellen und PHP CS Fixer 3.95.27 im Prüfmodus
(keine Änderungen erforderlich). Geänderte Dokumentation als UTF-8/LF geprüft.

## Astro-Ausführung: realer Gerätetest

Gesonderter Praxisauftrag für tatsächliches Schalten über Test-Symcon an dem
gemeinsam genutzten Z2M. Vorher gespeicherte Regeln, Betriebsfreigaben,
Erweiterungsdateien, vorhandenen Formularentwurf und Geräteausgangszustand
gesichert. Keine Änderungen am Produktiv-Symcon-Modul, an Originalereignissen,
Brokerrechten oder Gerätezuordnungen. Der Funktionscode blieb unverändert.

Für den aktuellen lokalen Tag wurde mit dem hinterlegten Standort der
Sonnenaufgang berechnet. Ein negativer Versatz von 17 Minuten legte den Test
wenige Minuten in die Zukunft. Eine unabhängige PHP-date_sun_info-Abfrage wich
weniger als zwei Minuten ab und lag damit innerhalb der dokumentierten
Näherungsgrenze. Keine Änderung der Systemzeit, keine Zeitinjektion und kein
zusätzlicher fester Uhrzeit- oder Geräteauslöser. Die temporäre aktivierte Regel
enthielt ausschließlich einen sun-Auslöser und die Folge Einschalten mit
mittlerer Helligkeit, fünf Sekunden warten, ursprünglichen Aus-Zustand und
Helligkeit wiederherstellen. Vorhandene Regeln und Partnerfreigaben blieben
inhaltlich erhalten.

Nach erfolgreicher Z2M-Prüfung, Speicherung und Aktivierung meldete die Runtime
genau einen Astro-Start in der berechneten Zielsekunde. Native Symcon-Debugausgaben
enthielten Sonnenzeit einschließlich Versatz, gemeinsamen Regellauf, Regel- und
Schrittnummer, native Befehlsübergaben, Beginn und Ende der fünf Sekunden Wartezeit
sowie das Ende der Folge. Kein Laufzeitfehler und keine gemeldeten Debugverluste.
Ein lesender Beobachter bestätigte den Astro-Datensatz innerhalb einer Sekunde
nach dem Minutenbeginn und beide Befehlsübergaben im Abstand von fünf Sekunden.

Die Geräte-MQTT-Rückmeldungen zeigten Ein mit Testhelligkeit und anschließend
wieder Aus mit ursprünglicher Helligkeit. Eine zeitversetzte Leistungsrückmeldung
stieg deutlich über den Ruheverbrauch und kehrte anschließend auf diesen zurück.
Das Symcon-Archiv bestätigte den Ein-/Aus-Wechsel mit fünf Sekunden Abstand.
Damit liegt neben der Befehlsannahme eine tatsächliche Geräterückmeldung vor;
eine Sichtprüfung durch den Anwender wurde nicht angefordert.

Nebenbefund ohne Quellcodeänderung: Beim geänderten Speichern wurde die lokale
Debugfreigabe zurückgesetzt. Der Empfangsstatus war vorübergehend nicht mehr
bestätigt; SyncRuntimeDebug beendet dafür die lokale Zuordnung mit der Meldung
über geänderte Verbindung/Sitzung. Ein tatsächlicher Z2M-Neustart fand nicht statt.
Die Aufzeichnung wurde nach Aktivierung frisch gestartet und umfasste den
vollständigen Schaltversuch. Die zuvor auf Z2M begrenzt weiterlaufende und die
neu gestartete eigene Aufzeichnungsfreigabe wurden anhand ihrer nachgewiesenen
Clientidentitäten ausdrücklich beendet und beide Stopps bestätigt. Dieses
Speicher-/Debugverhalten ist nicht mit diesem Prüfauftrag korrigiert worden.

Aufräumen anschließend bestätigt: gespeichertes Ausgangsdokument bis auf die
fortgeschriebene Revision identisch, keine Testregel mehr vorhanden. Ursprüngliche
Ausführung, Mesh und Wiederanlauf wieder freigegeben, Laufzeitfehler leer.
Erweiterungsdateien unverändert; anderes Z2M-System unverändert aktiv. Lichtzustand
und Helligkeit entsprechen dem Ausgangszustand. Formularentwurf bytegleich,
temporäre Modulinstanzen entfernt, native Debug-Dateiaufzeichnungen beendet und
lesenden Beobachter samt Zusatzverbindungen geschlossen. Weitere Sonnenereignisse
oder saisonale Hardwaretests wurden nicht ausgeführt.

## Ablaufaufzeichnung beim Speichern erhalten

Den Nebenbefund des Astro-Gerätetests lokal reproduziert: eine korrekte
Speicherantwort setzt extensionConfirmed bis zur anschließenden Statusantwort
zurück. ObserveExtensionStatus kann dieselbe Wartephase bei verändertem Stand
auslösen. SyncRuntimeDebug behandelte dieses Bestätigungsmerkmal bisher wie
einen Verlust des MQTT-/Sitzungskontexts und löschte die vorhandene Clientfreigabe.
Ein Integrationstest mit echtem PHP-Modul, generierter JavaScript-Erweiterung
und zurückgehaltener finaler Statusantwort scheiterte vor der Korrektur gezielt
am Erhalt der bestehenden Freigabe.

Die lokale Bindung prüft jetzt weiterhin MQTT-Kontext, Verbindung, Z2M-Sitzung
und ursprüngliche Endzeit. Die separate Bestätigung eines Regelstands beeinflusst
sie nicht. Ein Stopauftrag darf eine vorhandene eigene Freigabe auch während
dieser Wartephase beenden; neue Starts und Verlängerungen benötigen unverändert
einen bestätigten Erweiterungsstatus. Die Aufzeichnung ersetzt keine Speicher-
oder Laufzeitbestätigung und gibt den Aktivierungsbutton nicht frei. Der native
Speicherablauf nennt den Erhalt der laufenden Aufzeichnung ausdrücklich. Eine
abgelehnte erneute Startanfrage zeigt die bestehende Aufzeichnung weiterhin an.

Die vorhandenen ParentConnectionHelper-, DataFlowHelper- und DebugFlowHelper-
Verträge wurden geprüft. Die Änderung betrifft ausschließlich das lokale Z2M-
Protokoll und verwendet diese zentralen Helper unverändert. Keine zusätzlichen
Maskierungen, Zähler, öffentlichen Methoden oder Erweiterungsänderungen.

Regressionen prüfen unveränderte und geänderte Speicherung, unveränderten Client
und Endzeit, verspätete Statusbestätigung, Empfang frischer Ablaufmeldungen
während der Wartephase, weiter gesperrte Automationsaktivierung, abgelehnte
Verlängerung, eigenen Stopp und Ablauf zur ursprünglichen Endzeit. Echte
synthetische Parent-Trennung, Einstellungsübernahme und Neustart des realen
JavaScript-Empfängers beenden die lokale Aufzeichnung weiterhin; eine spätere
Bestätigung stellt keine beendete Aufzeichnung wieder her. Alle Daten sind
synthetisch. Keine Installation oder Praxisänderung auf Test-/Produktivsystemen
für diese Korrektur; native Nachprüfung nach Modulupdate bleibt offen.

Abschließend erfolgreich: `php tests/run.php` einschließlich der neuen
Speicher-/Aufzeichnungsregressionen und 88 JavaScript-Tests, separate
Strukturprüfung, `npm run check:extension`, offizieller StylePHP-JSON-Check
eigener Dateien ohne Fremdquellen und PHP CS Fixer 3.95.27 im Prüfmodus.
Kodierungsprüfung und UTF-8/LF-Kontrolle bestanden. Der ursprüngliche
Regressionstest wurde vor der Korrektur gezielt fehlschlagend nachgewiesen.

## Ablaufaufzeichnung beim Speichern: Praxisprüfung

Auf gesonderten Auftrag den korrigierten Stand in Test-Symcon geprüft.
RuntimeDebug.php, ExtensionIntegration.php und Übersetzung waren bereits
bytegenau aktuell; erneutes Überschreiben oder ein Library-Reload waren nicht
nötig. Vorhandenen Formularentwurf und den tatsächlichen Z2M-Speicherstand samt
Betriebsstatus gesichert. Die Z2M-Erweiterung wurde nicht geändert.

Eine temporäre zusätzliche native MQTT-Verbindung lief durch ein ausschließlich
lokal erreichbares TCP-Relais. Dieses leitete die echten MQTT-Pakete weiter und
hielt gezielt nur die korrelierte abschließende Speicher-Statusantwort für den
Testclient zurück. Debugantworten und andere Verbindungen blieben unbeeinflusst.
Eine automatische Freigabe begrenzte die Verzögerung. Das Relais protokollierte
nur Zeitpunkt, Auftragsart und gekürzten Hash der Aufzeichnungsidentität; keine
Zugangsdaten oder Nutzdaten. Seine Schreibfreigabe umfasste nur Health-, Status-,
Prüfungs-, Speicher- und Debuganfragen; Geräteaktionen, Installationen sowie
Aktivierungs- oder Stoppbefehle für Automationen waren ausgeschlossen.

Zweimal wurde ausschließlich das bereits gespeicherte Original bytegleich
geprüft und erneut gespeichert. Die Erweiterung bestätigte beide Vorgänge ohne
Änderung des Regelstands oder Unterbrechung seiner Ausführung. Es wurden keine
Testregeln hinzugefügt, aktiviert oder Geräte geschaltet. Eine geänderte
Speicherung mit anschließendem Stopp der bisherigen Ausführung war nicht Teil
dieses Praxisauftrags; ihr Nachweis bleibt im synthetischen Integrationstest.

Erster Fall: 60 Sekunden Aufzeichnung angefordert und bestätigt. Rund 28 Sekunden
später erneut gespeichert und die Statusantwort zurückgehalten. Das Formular
zeigte weiterhin die laufende Aufzeichnung und einen bedienbaren eigenen
Stoppbutton; Automationsaktivierung blieb bis zur Statusbestätigung gesperrt.
Der native Debug meldete den Erhalt der bisherigen Endzeit. Nach Freigabe der
echten Antwort wurde der gespeicherte Stand bestätigt. Der echte Symcon-Timer
beendete die Aufzeichnung genau 60 Sekunden nach dem Start, nicht 60 Sekunden
nach dem Speichern. Das Relais sah keinen zusätzlichen Startauftrag oder
Clientwechsel; Formulartext und deaktivierter Stoppbutton bestätigten den Ablauf.

Zweiter Fall: neue Aufzeichnung für fünf Minuten gestartet und erneut mit
zurückgehaltener finaler Statusantwort gespeichert. Bei ausdrücklich weiterhin
unbestätigtem Speicher-Laufzeitstatus den öffentlichen eigenen Debug-Stopp
aufgerufen. Die echte Z2M-Antwort bestätigte das Ende mit derselben Clientidentität,
während die Speicherantwort noch zurückgehalten war. Formulartext, Stoppbutton
und native Debugausgaben waren korrekt. Erst die anschließend freigegebene
Statusantwort bestätigte den Speicherworkflow; die Aufzeichnung blieb beendet.
Die Prüfung nutzte die öffentlichen Modulmethoden und echten Formularzustände
über MCP, keine automatisierten Zeigerklicks in der nativen Konsole.

Aufräumen bestätigt: erste Aufzeichnung abgelaufen, zweite ausdrücklich beendet;
temporären Monitor, MQTT-Client und Socket entfernt, native Debug-Dateiaufzeichnung
beendet und Relais samt Zusatzverbindungen geschlossen. Der direkte abschließende
Z2M-Abruf bestätigte bytegleiche YAML, identische Revision/Prüfsumme und unveränderte
Erweiterungsdateien. Ausführung, Mesh und Wiederanlauf blieben freigegeben,
Laufzeitfehler leer; anderes Z2M-System unverändert aktiv. Ursprünglicher lokaler
Formularentwurf bytegleich, bestehende MQTT-Verbindung aktiv und unverändert.
Keine Änderung am Produktiv-Symcon-Modul oder am Funktionscode für diese Prüfung.

## Einzelstart und gezielter Stopp

Lokal implementiert gemäß ADR 0014. Neue Offline-Prüfungen verwenden denselben
gebauten Empfänger und synthetische Geräte: deaktivierte gespeicherte Regel
bei gestoppter Automatik, bekannte/unbekannte/geänderte Bedingungen, parallele
Läufe, gezielter Stopp, unveränderte Auslöser und Wiederanlaufabsicht, falsche
Sitzung/Revision/Prüfsumme, abgelaufene Einmalfreigabe, Fehler ohne Wiederholung
und manipulierte Datei. Zwei unabhängige Empfänger prüfen einen manuellen
Mesh-Aufruf ohne eingehende Freigabe am Ursprung und Stopp vor verspäteter
Challenge. Die PHP-/JS-Integration führt echte native Formularskripte aus;
Statusparser und Sequenzbindung verwerfen alte, retained und freie Fehlertexte.
Keine Installation oder reale Schaltprüfung im aktuellen Auftrag.

Nachweise am 02.11.2026: php tests/run.php erfolgreich, einschließlich 97
JavaScript-Tests und echter PHP-/JS-Integration. Nach der abschließenden
Absicherung der Kontrollantworten die betroffene Integration erneut geprüft.
Zusätzlich Struktur- und Kodierungsprüfung, npm run check:extension, offizieller
StylePHP-JSON-Check der 15 eigenen JSON-Dateien in einer temporären Kopie und
PHP CS Fixer 3.95.27 im Prüfmodus erfolgreich. Geänderte Textdateien sind UTF-8
ohne BOM mit LF. Keine fremden Helper oder Abhängigkeiten verändert. Sichtprüfung
in der nativen Konsole sowie reale Schalt- und Partnerprüfungen bleiben offen;
Test- und Live-Installationen wurden in diesem Auftrag nicht verändert.

## Einzelstart und gezielter Stopp: Praxisprüfung am 02.11.2026

Auf ausdrücklichen Auftrag die Testinstallation geprüft. Ihr Modulstand samt
Formular, Übersetzung, neuen öffentlichen Funktionen und Uploadartefakt war
bereits dateigenau aktuell. Nach ausdrücklicher Bestätigung das gemeinsam mit
der Produktion genutzte Z2M verwendet. Die Erweiterung wurde über den echten
Installationsauftrag aktualisiert; offizielle Antwort, neue Sitzung und
execution_version 1 bestätigt. Veröffentlichter Erweiterungscode entspricht
exakt dem Uploadartefakt. Andere Erweiterungen blieben bytegleich. Die bisherige
Betriebsfreigabe wurde nach dem Neuladen automatisch wieder aufgenommen.

Erweiterungen, gespeicherte YAML und Metadaten außerhalb des Repositorys
gesichert. Temporäre Automationsinstanzen hielten den ursprünglichen lokalen
Editorentwurf unangetastet. Dabei einen Formularfehler gefunden: SaveEditorDraft
formatierte YAML bei bereits identischen Partnerfreigaben erneut. Trotz
unverändertem Inhalt wich die Prüfsumme vom gespeicherten Text ab; die
Einzelstartfreigabe blieb deshalb gesperrt. Nach gesonderter Korrekturfreigabe
werden Text und Prüfsumme bei unveränderten Freigaben erhalten. Nur tatsächlich
geänderte Partnerfreigaben erzeugen weiterhin einen neuen Entwurf. Neue deutsche
Debugentscheidungen erklären beide Fälle. Kompaktes gespeichertes JSON mit
unveränderten Freigaben reproduzierte den Fehler im PHP-/JS-Regressionstest
vor der Korrektur und bestand danach. Korrigierte PHP-Datei auf Test-Symcon
übertragen, Syntax und Dateihash bestätigt. Anschließend stimmten geladener
Originaltext und gespeicherte Prüfsumme auch praktisch überein.

Zwei zusätzliche Testregeln blieben dauerhaft deaktiviert. Jede ihrer möglichen
Aktionen hatte 86400 Sekunden Wartezeit; keine normale Testauslösung wurde
freigegeben. Die vorhandenen Aktivierungskennzeichen blieben unverändert. Nach
dem zum Speichern nötigen Stopp ausschließlich den vorherigen Automatikbetrieb
wieder aktiviert. Start und Stopp wurden durch Ausführung der tatsächlichen
nativen Formularskripte per MCP geprüft, ohne Zeigerklick in der Konsole.

- Einmaliger Start mit widersprüchlichen Zeitbedingungen: Zustand skipped,
  feste Ursache conditions_not_met, verständlicher Formulartext, Stopp gesperrt.
  Der MQTT-Debug belegte die nicht erfüllte Bedingung; keine Wartezeit oder Aktion.
- Einmaliger Start mit erfüllter Zeitbedingung: Zustand running in Schritt 1,
  Startbutton gesperrt und gezielter Stopp freigegeben. Stopp bestätigte dieselbe
  Lauf-UUID als stopped, verwarf den wartenden Schritt und meldete das Ende.
  Der vorherige Automatikbetrieb blieb während des Einzeltests aktiv.
- Wiederholter Versuch mit längerem Warten: native automatische Statusabfragen
  im Fünfsekundentakt samt bestätigtem runs_ready, identischem laufendem Ablauf
  und anschließend erfolgreichem gezieltem Stopp.

Die eigene zeitlich begrenzte Z2M-Aufzeichnung und Symcons native Debugdatei
belegen Startanfrage, erfüllte und nicht erfüllte Bedingungen, Wartezeit,
konkreten Stopp und Ende mit zugehörigen Lauf-/Schrittbezügen. Für keinen der
Testläufe gab es eine native Geräte- oder Szenenübergabe. Die native Datei
enthielt weder rohe YAML noch Aktivierungsfreigaben. Die physische Wirkung
einer Aktion und die erneute Bedingungsprüfung nach einer abgelaufenen Wartezeit
waren ausdrücklich nicht Gegenstand dieses schaltfreien Praxislaufs.

Aufräumen: Testregeln entfernt und ursprüngliches Dokument bis auf die
fortgeschriebene Revision inhaltlich identisch zurückgeschrieben. Der erneute
Z2M-Abruf bestätigte Regeln, Aktivierungskennzeichen und Partnerfreigaben sowie
den exakten Wiederherstellungstext. Ausschließlich die bisherige Betriebsfreigabe
wieder aktiviert; Ausführung, Mesh und Wiederanlauf entsprechen dem Ausgangsstand,
Laufzeitfehler leer. Ursprünglicher lokaler Formularentwurf bytegleich. Temporäre
Instanzen samt Statusvariablen entfernt, eigene Aufzeichnung und native
Debugdateiaufzeichnung beendet und die zusätzliche lesende WebSocket-Verbindung
geschlossen. Keine Originalereignisse, Gerätezuordnungen, Brokerrechte oder
Produktiv-Symcon-Moduldateien verändert. Das Neuladen der Erweiterung und die
Speicherwechsel unterbrachen die betroffene Laufzeit kurzzeitig wie vorab
besprochen; eine unterbrechungsfreie Aktualisierung wurde nicht behauptet.

Nachweise: php tests/run.php vollständig erfolgreich, einschließlich 97
JavaScript-Tests und PHP-/JS-Integration. Struktur-, Kodierungs- und
Artefaktprüfung, offizieller StylePHP-JSON-Check der eigenen JSON-Dateien
in einer temporären Kopie sowie PHP CS Fixer 3.95.27 im Prüfmodus erfolgreich.
Fremdquellen und zentrale Helper unverändert; geänderte Texte UTF-8 ohne BOM/LF.


## Reale gemeinsame MQTT-Debugzuordnung am 02.11.2026

Nach ausdrücklicher Bereitschaftsbestätigung des Benutzers Aufzeichnungen auf
beiden Systemen gestartet und bestätigt. Der Benutzer betätigte den vorgesehenen
Wandtaster zweimal. Zwei eigenständige Läufe wurden auf Quelle und Ziel mit
jeweils identischem Laufbezug, Schritt 1 und Quellrevision erfasst. Jede
Zielausführung bestätigte genau eine native Befehlsübergabe; kein Laufzeitfehler.
Der Benutzer bestätigte die tatsächliche Lichtwirkung. Leere beziehungsweise
zurückgesetzte Aktionsmeldungen lösten keinen zusätzlichen Lauf aus.

Danach beide eigenen MQTT-Aufzeichnungen und die nativen Debugdateiaufzeichnungen
beendet. Dauerhaft aktive Regel, deaktiviertes Originalereignis, gespeicherte
Prüfsummen, Revisionen und Wiederanlauf unverändert. Kein Erweiterungsupdate,
Speichern, Neustart oder zusätzlicher Testbefehl in diesem Aufzeichnungslauf.
Der frühere offene praktische Nachweis der systemübergreifenden Zuordnung ist
hiermit abgeschlossen. Private Nachweise verbleiben außerhalb des Repositorys.

## Wiederverwendbare Aktionsfolgen: lokaler Stand am 02.11.2026

ReusableSequences verwaltet Definitionen im gemeinsamen nativen Formular und
verwendet bestehende Aktionsdialoge, Rechteprüfung und gebundene Bearbeitung.
SequencePlan und src/sequences.mjs lösen stabile Verweise im selben Dokument
auf. Grenzen und Laufzeitbesitz sind in ADR 0015 festgehalten. Keine neuen
Abhängigkeiten; vorhandene zentrale Helper bleiben unverändert.

Neue Offline-Nachweise verwenden ausschließlich synthetische Daten:

- Reale native Formularskripte mit erzeugtem JavaScript-Empfänger: Definition
  erstellen, Name/Schritte/Wartezeit vorbelegen, bearbeiten und umordnen; zwei
  Regeln verwenden dieselbe Definition. Regel mit erstem Folgeschritt bleibt
  vollständig bearbeitbar. Prüfung, Speicherung und Wiederladen erhalten Verweise.
- Verwendete Definition auch beim direkten Löschaufruf gesperrt; Änderung mit
  zu vielen aufgelösten Schritten hinterlässt den Entwurf bytegleich. Entzogene
  native Schaltrechte verhindern Verwendung. Unbenutzte Definition löschbar.
- Echte Erweiterung mit virtueller Uhr: additive erste Wartezeit, unveränderte
  gespeicherte Definition, erneute Bedingungsprüfung, Einzelstart, gezielter Stopp,
  unabhängige gleichzeitige Verwender, Szenenverfügbarkeit und Wiederanlauf ohne
  Fortsetzung unterbrochener Schritte. Fehlende, doppelte, verschachtelte oder
  zu große Definitionen und überlange summierte Wartezeit werden abgewiesen.
- Zwei synthetische Z2M-Erweiterungen: konkrete entfernte Schritte derselben Folge
  mit gemeinsamem Lauf und korrekter Folge-/Schrittzuordnung auf beiden Seiten.
  Der Empfänger akzeptiert weiterhin ausschließlich konkrete Aktionen. Keine
  Namen, Werte oder Definitionen im Debug. Bestehende präzise Fehlerpositionen
  und Datenschutzregressionen bleiben grün.

php tests/run.php vollständig erfolgreich, einschließlich 104 JavaScript-Tests
und PHP-/JS-Formularintegration. Struktur- und zentrale Kodierungsprüfung,
aktuelle Erweiterungsdatei, offizielle StylePHP-JSON-Prüfung der 15 eigenen
JSON-Dateien sowie PHP CS Fixer 3.95.27 im Prüfmodus erfolgreich. Fremdquellen
bleiben unverändert; geänderte Texte UTF-8 ohne BOM mit LF. Die Prüfung ersetzt
keine Bedienprüfung in der nativen Konsole oder reale Geräteausführung. In diesem Arbeitspaket wurden weder
Test- noch Produktivinstallationen verändert. Vor Praxisprüfung Modul und
Erweiterung aktualisieren; bei Mesh auch die ausführenden Partner für vollständige
zusätzliche Diagnosebezüge.


## Erweiterungsstand mit automatischer Buildkennung am 02.11.2026

Lokal implementiert gemäß ADR 0016. Der Generator bildet eine deterministische
SHA-256-Kennung des eigenen zusammengesetzten Artefakts mit festem Platzhalter
und bettet sie in genau diese Datei ein. Status meldet die Kennung des geladenen
Codes. ExtensionContract liest den lokalen Upload-Header und prüft empfangene
Kennungen. Drei native Labels unter Einrichtung und Diagnose zeigen installierten
Stand, mitgelieferten Stand und einen verständlichen Vergleichshinweis.

Offline-Nachweise ausschließlich mit synthetischen Daten:

- Zunächst fehlende Kennung im erzeugten Artefakt und fehlende Formularanzeige
  gezielt nachgewiesen; anschließend beide Prüfungen erfolgreich.
- Wiederholte Builds in isolierter Kopie sind bytegleich. Änderungen am Runtimecode,
  Schema und deutschen Debugkatalog ändern die Kennung. Check-Modus erkennt jeweils
  die veraltete Datei vor dem erneuten Build. Keine Uhrzeit oder Rechnerdaten.
- Echte Erweiterung beantwortet Status mit der eingebetteten Kennung und unveränderter
  Versionsnummer. PHP-/JavaScript-Installation lädt exakt das erzeugte Artefakt;
  die anschließende passende Statusantwort bestätigt denselben Stand und aktualisiert
  das offene Formular auf aktuell.
- Automatische und manuelle Statusabfrage, Formularneuladen, gleicher und abweichender
  Build, ältere Erweiterung ohne Kennung sowie ungültige Kennungen geprüft.
  Unbelegte retained Antworten ändern den Vergleich nicht. Kontextwechsel entfernt
  die alte Zuordnung. Formularaufbau sendet kein synchrones MQTT; verwendete
  Vergleichsaufträge bleiben lesend. Privater Entwurf bleibt unverändert und fehlt
  im Debug. Identische Vergleichsergebnisse werden nicht wiederholt protokolliert.

php tests/run.php erfolgreich einschließlich 106 JavaScript-Tests; anschließend
PHP-/JavaScript-Installation mit zusätzlicher expliziter Buildanzeige-Assertion
nochmals erfolgreich. Struktur- und zentrale Kodierungsprüfung, aktuelles erzeugtes
Artefakt, offizielle StylePHP-Prüfung der 15 eigenen JSON-Dateien in einer temporären
Kopie sowie PHP CS Fixer 3.95.27 im Prüfmodus erfolgreich. Fremdquellen und zentrale
Helper unverändert. Geänderte Texte UTF-8 ohne BOM mit LF.

Keine Test- oder Produktivinstallation aktualisiert, keine Automationen gespeichert
oder aktiviert. Die native Darstellung und eine tatsächliche Z2M-Installation dieses
Stands bleiben praktisch zu prüfen. Modul und mitgelieferte Erweiterung für einen
vollständigen Vergleich aktualisieren; ältere Empfänger bleiben weiterhin abfragbar.


## Nativer Button zur Verbindungsprüfung am 02.11.2026

Der zusätzliche PHP-Parameter RefreshExtension besitzt einen Standardwert. Der
native Symcon-Wrapper verlangt dennoch seine ausdrückliche Übergabe. Der
Formularbutton übergab nur die Instanz-ID und brach deshalb vor Eintritt in den
DebugFlowHelper mit einem ArgumentCountError ab. Er übergibt jetzt zusätzlich
false; die manuelle reine Health-Prüfung und die automatische Folgeabfrage mit
true behalten ihre bisherige Bedeutung. Die übrigen Diagnosebuttons übergeben
bereits sämtliche Parameter ihrer öffentlichen Methoden.

Der MQTT-Integrationstest führt das tatsächliche Formularskript über einen
Wrapper mit derselben verpflichtenden Parameteranzahl aus. Vor der Korrektur
ArgumentCountError, danach erfolgreicher asynchroner Health-Versand und passende
Antwort einschließlich vorhandener Debugprüfung. Automatische Statusfolge
separat erfolgreich. Kein neuer Laufzeitcode oder geändertes Z2M-Artefakt;
für die Korrektur genügt ein Modulupdate. Keine Test- oder Live-Systemänderungen.


## Ergebnis-Popup für die manuelle Verbindungsprüfung am 02.11.2026

Der native Button verwendet CheckConnection ohne optionale Kernelparameter.
CheckConnection und die bestehende CheckHealth-API teilen RequestHealthCheck;
CheckHealth behält seine bisherige Signatur und arbeitet weiterhin ohne Popup.
Der unveränderte automatische Statusworkflow verwendet weiterhin CheckHealth.
Vorhandene ConfigurationFormHelper- und DebugFlowHelper-Verträge sowie das
bestehende Installations-Popup wurden geprüft und unverändert weiterverwendet.

Nur die ausdrücklich manuelle Anfrage erhält eine Rückmeldungsmarkierung im
vorhandenen Transportzustand. Passende frische Antwort, Fehler, Timerablauf oder
Abbruch entfernen die Markierung und zeigen das native HealthResultPopup.
Eine manuelle Bedienung darf sich an eine bereits automatisch gestartete Prüfung
anhängen. Ein wiederholter Klick sendet keine zweite Anfrage. Ein gescheitertes
Popup verändert das Prüfergebnis nicht. Ein bereits vor dem Versand abgelaufener
Auftrag wird ohne Versand verworfen und dessen Zustand gespeichert, damit die
Rückmeldung bei späteren Zugriffen nicht wiederholt wird.

Neue health-feedback.php-Prüfung führt das tatsächliche native Formularskript
mit synthetischem MQTT-Elternmodul aus. Zunächst fehlendes Popup nachgewiesen,
anschließend Erfolg, ungesunder Z2M-Zustand, expliziter Fehler, ungültige/fremde/
retained/verspätete Antworten, Zeitüberschreitung, Sendefehler, Bridge-Offline,
Kontextwechsel und fehlende Verbindung geprüft. Automatische vollständige
Health-/Statusfolge und bisherige nichtinteraktive API bleiben ohne Popup.
Sofortige synchrone Antworten, mehrfache Klicks, manuelles Anhängen an laufende
Prüfung, UI-Ausfall und Ablauf vor Versand sind zusätzlich abgedeckt. Für den
letzten Fall zunächst doppelte Rückmeldung reproduziert, dann durch korrektes
Festschreiben des abgebrochenen Zustands behoben. Native deutsche Debugtexte
enthalten nur feste Ergebnisse; fremde Fehlerwerte werden nicht weitergegeben.

Keine Test- oder Live-Installation verändert. Das native Fenster wurde in diesem
Arbeitspaket nicht praktisch geöffnet. Ein Modulupdate genügt; keine Änderung
am Z2M-Artefakt oder seinen Buildkennungen.

Nachweise: php tests/run.php vollständig erfolgreich einschließlich 106
JavaScript-Tests und neuer Popup-/MQTT-Integration. Strukturprüfung, zentrale
Kodierungsprüfung, unverändertes aktuelles Erweiterungsartefakt, offizielle
StylePHP-Prüfung der 15 eigenen JSON-Dateien in isolierter Kopie sowie PHP CS
Fixer 3.95.27 im Prüfmodus erfolgreich. Fremdquellen und zentrale Helper
unverändert; geänderte Texte UTF-8 ohne BOM mit LF.

## Wiederverwendbare Aktionsfolgen: Praxisprüfung am 02.11.2026

Auf ausdrücklichen Auftrag über Test-Symcon an dem gemeinsam genutzten realen
Z2M-System geprüft. Module und Erweiterung waren bereits aktualisiert. Zwei
vorübergehende Testmodule bewahrten die vorhandenen lokalen Entwürfe; die
Partnerzuordnung erhielt die vorhandenen Mesh-Freigaben. Sicherungen und rohe
Nachweise blieben außerhalb des Repositorys.

- In der nativen Konsole eine Folge angelegt und einen vorhandenen Schritt
  mit korrekt vorbelegtem Gerät, Kanal, Wert und Wartezeit bearbeitet. Weitere
  Schritte und je ein deaktivierter Geräte- und Zeitverwender wurden über die
  tatsächlichen Formular-Callbacks angelegt. Nicht alle Bedienhandlungen
  erfolgten per Maus. Beide Verwender behielten dieselbe stabile Folgen-ID.
- Verwendung zweimal angezeigt; Löschen der verwendeten Folge sowohl im
  Formular gesperrt als auch beim direkten Aufruf abgelehnt, Entwurf bytegleich.
  Prüfung, Speicherung und frisches Laden erhielten Definition, Verweise und
  beide deaktivierten Regeln. Vorhandene Regeln blieben inhaltlich unverändert.
- Nach eigener Freigabe des Testlichts die Geräte-Regel einmalig gestartet:
  Statusmeldung Ein und exakt fünf Sekunden später Aus. Debug meldete beide
  nativen Befehlsübergaben mit demselben Regellauf sowie korrekter Folgen- und
  Schrittposition; Laufstatus anschließend abgeschlossen und ohne Fehler.
  Geräte-Rückmeldungen bestätigen den gemeldeten Schaltzustand; eine zusätzliche
  Sichtkontrolle durch den Anwender war nicht Teil dieses Nachweises.
- Für den gezielten Stopp erhielt nur der deaktivierte Zeitverwender eine
  Wartezeit von 60 Sekunden vor dem ersten Schritt. Einmalig gestartet und
  während dieser Wartezeit gestoppt: passender Laufstatus gestoppt, Debug meldet
  den abgebrochenen Schritt und keine Befehlsübergabe dieses Laufs. Der allgemeine
  Automationsbetrieb blieb aktiv. Ein zusätzlicher gemeldeter Wechsel des
  Lichtzustands wurde keinem Testbefehl zugeschrieben; abschließend wurde der
  gesicherte ursprüngliche Aus-Zustand wiederhergestellt und bestätigt.
- Danach ursprünglichen Regelinhalt ohne Testregeln oder Folgendefinition mit
  fortlaufender Revision gespeichert, Betrieb wieder aktiviert und frisch
  geladen. Vollständiger Inhaltsvergleich bis auf die Revision erfolgreich.
  Ursprüngliche Anzahl aktiver Regeln, Mesh und automatischer Wiederanlauf
  bestätigt; lokale Entwürfe bytegleich, vorhandene Ereigniszustände unverändert.
  Eigene MQTT-Aufzeichnung beendet, native Debugdatei gestoppt und beide
  vorübergehenden Testmodule samt eigenen Statusvariablen entfernt.

Der reale Aufruf einer wiederverwendbaren Folge über Mesh, parallele Verwender
und ein Neustart während einer Folge wurden hier nicht praktisch geprüft;
hierfür gelten weiterhin die synthetischen Laufzeitnachweise. Keine Änderung
an Funktionscode, zentralen Helpern oder Erweiterungsartefakt.

Erneute lokale Prüfungen erfolgreich: php tests/run.php einschließlich 106
JavaScript-Tests, Struktur- und zentrale Kodierungsprüfung, Artefaktvergleich,
offizielle StylePHP-Prüfung der 15 eigenen JSON-Dateien in isolierter Kopie
sowie PHP CS Fixer 3.95.27 im Prüfmodus. Nur drei Dokumentationsdateien geändert;
UTF-8 ohne BOM, LF und git diff --check bestätigt.

## Wiederverwendbare Aktionsfolgen über Mesh: Praxisprüfung am 02.11.2026

Über Test-Symcon und zwei bereits gekoppelte reale Z2M-Systeme geprüft. Der
Anwender gab das Testlicht ausdrücklich frei. Vorhandene Erweiterungen meldeten
Folgen-, Einzelstart- und Debugunterstützung; ein Erweiterungsupdate war nicht
erforderlich. Mit temporären Testmodulen blieben die vorhandenen lokalen Entwürfe
erhalten. Rohdaten, Sicherungen und Debugdateien blieben außerhalb des Repositorys.

Eine Folge mit zwei entfernten Geräteaktionen und fünf Sekunden Wartezeit vor
dem zweiten Schritt über die tatsächlichen Formular-Callbacks vorbereitet.
Der native Gerätekanal besaß Schaltrechte. Folgenschritte und deaktivierte
aufrufende Zeitregel wurden korrekt vorbelegt; die Prüfung der Quellerweiterung
war erfolgreich. Nur den Quellstand gespeichert, bestehende aktive Regel und
Mesh-Berechtigung anschließend wieder aktiviert. Der Zielregelstand wurde
weder gespeichert noch neu aktiviert.

Nach bestätigtem Start eigener Aufzeichnungen auf beiden Systemen die weiterhin
deaktivierte Testregel genau einmal gestartet. Der Gerätezustand meldete Ein und
fünf Sekunden später Aus. Quellstatus anschließend abgeschlossen, kein Laufzeit-
oder Übertragungsfehler. Die Debugdateien belegen dieselbe Laufkennung, dieselben
einsbasierten Folgen- und Schrittnummern sowie die Quellrevision auf beiden Seiten.
Das Ziel meldete exakt zwei native Befehlsübergaben. Die Geräte-Rückmeldungen
ergänzen diese Übergaben; eine unabhängige Sichtkontrolle wurde nicht erhoben.

Danach ursprünglichen Quellinhalt ohne Testregel und Folge mit fortlaufender
Revision wiederhergestellt, aktiviert und frisch geladen. Inhaltsvergleich bis
auf die Revision erfolgreich. Zielrevision und Zielprüfsumme blieben unverändert.
Beide ursprünglichen aktiven Regeln, Mesh und automatischer Wiederanlauf bestätigt;
lokale Test-/Produktiventwürfe bytegleich, bestehendes Originalereignis weiterhin
deaktiviert. Einen später zusätzlich gemeldeten Lichtzustand auf den gesicherten
ursprünglichen Aus-Zustand zurückgestellt und bestätigt. Eigene Aufzeichnungen
beendet, native Debugdateien gestoppt und Testmodule samt Statusvariablen entfernt.

Der gezielte synthetische Mesh-Test für gemeinsame Folgen- und Schrittbezüge
bestand erneut. Keine Änderung an Funktionscode, Helpern, Schema oder Artefakt.
Reale parallele Verwender, umgekehrte Richtung und Neustart während einer Folge
waren nicht Teil dieses Praxislaufs; bestehende Offline-Nachweise bleiben maßgeblich.

Abschlussprüfungen erneut erfolgreich: php tests/run.php einschließlich 106
JavaScript-Tests, Struktur- und zentrale Kodierungsprüfung, Artefaktvergleich,
offizielle StylePHP-Prüfung der 15 eigenen JSON-Dateien und PHP CS Fixer 3.95.27
im Prüfmodus. Nur drei Dokumentationsdateien geändert, UTF-8 ohne BOM mit LF;
git diff --check ohne Befund.

## Gemeinsame Folgen: Parallelbetrieb und Neustartprüfung am 02.11.2026

Praktischer Laufzeitnachweis über die öffentlichen MCP-/Modulfunktionen von
Test-Symcon am bereits freigegebenen realen Z2M-System. Vorhandene lokale Entwürfe
mit temporären Testmodulen erhalten; gespeicherten Originalstand gesichert.
Keine erneute native Formularbedienprüfung. Zwei deaktivierte Zeitregeln
verwenden dieselbe Definition: 30 Sekunden vor dem Einschalten warten, dann
fünf Sekunden bis zum Ausschalten. Definition und Verwender gemeinsam geprüft,
gespeichert und geladen; ursprünglichen aktiven Betrieb wiederhergestellt.

Beide Regeln mit zeitlicher Überlappung einmalig gestartet. Status bestätigte
zwei unterschiedliche laufende Laufkennungen bei identischer Definition. Lauf A
während der ersten Wartezeit gezielt gestoppt; Lauf B blieb laufend. Danach
meldete das Testlicht Ein und fünf Sekunden später Aus. Endstatus: A gestoppt,
B abgeschlossen, beide ohne Fehler. Bereinigte Debugauswertung: keine native
Befehlsübergabe für A, genau zwei für B. Beide enthalten korrekte Folgen- und
Schrittpositionen. Der allgemeine Automatikbetrieb blieb dabei aktiv.

Nach gesonderter ausdrücklicher Neustartfreigabe eine deaktivierte Regel erneut
einmalig gestartet und den wartenden ersten Schritt bestätigt. Neustart über die
vorhandene Zigbee2MQTT-Bridge und deren öffentlichen Restart-Aufruf; Ziel anhand
MQTT-Gateway und Basistopic geprüft. Dieser verwendet die
[offizielle Z2M-Neustartanforderung](https://www.zigbee2mqtt.io/guide/usage/mqtt_topics_and_messages.html#zigbee2mqtt-bridge-request-restart).
Annahme bestätigt, Offline-Übergang erfasst, anschließend neue Erweiterungssitzung
und automatischer Wiederanlauf gemeldet. Gespeicherte Revision und Prüfsumme,
ursprüngliche Anzahl aktiver Regeln und Mesh-Freigaben erhalten. Der frische
Status bestätigte den wiederaufgenommenen Automatikbetrieb ohne erneute Aktivierung.

Die neue Sitzung enthielt keine alten Einzelabläufe. Kernel-Traces der
Lichtvariable deckten das Neustartfenster einschließlich beider ursprünglicher
Schaltzeitpunkte vollständig ab und meldeten durchgehend Aus. Weitere Beobachtung
über die Wartezeit eines neu begonnenen Schritts hinaus ebenfalls ohne Änderung.
Die unterbrochene Folge wurde nicht fortgesetzt. Beim Sitzungswechsel endete die
alte MQTT-Aufzeichnung erwartungsgemäß; eine neue Aufzeichnung wurde anschließend
bestätigt und lief zeitlich begrenzt aus. Der Zeitraum dazwischen ist durch
Kernel-Traces und Statusnachweise, nicht durch durchgehende MQTT-Debugrecords belegt.

Abschließend ursprünglichen Zielinhalt ohne Testregeln und Definition mit
fortlaufender Revision gespeichert, aktiviert und frisch geladen. Inhaltsvergleich
bis auf die Revision erfolgreich; gespeicherter Partnerstand blieb unverändert.
Lokale Test-/Produktiventwürfe bytegleich, vorhandenes Originalereignis weiterhin
deaktiviert. Ursprünglicher Automationsbetrieb, Mesh und Wiederanlauf bestätigt;
Licht wieder im gesicherten Aus-Zustand. Native Debugdateien gestoppt, temporäre
Testmodule mit eigenen Statusvariablen entfernt. Rohdaten und Sicherungen liegen
außerhalb des Repositorys. Kein Funktionscode, Helper oder Artefakt geändert.

Die gezielten synthetischen Parallel-/Neustarttests und php tests/run.php
einschließlich 106 JavaScript-Tests bestanden erneut. Eine unabhängige Sichtkontrolle
der Lichtwirkung, gleichzeitige Hardwareauslöser und ein Betriebssystem- oder
Brokerneustart waren nicht Bestandteil dieses Nachweises.

Struktur- und zentrale Kodierungsprüfung, aktuelles Erweiterungsartefakt,
offizielle StylePHP-Prüfung der 15 eigenen JSON-Dateien in isolierter Kopie
sowie PHP CS Fixer 3.95.27 im Prüfmodus ebenfalls erfolgreich. Nur drei
Dokumentationsdateien geändert, UTF-8 ohne BOM mit LF; git diff --check sauber.
## Z2MConnection lokal geprüft am 04.11.2026

Schritt 2 aus ADR 0026: Eigenes IPSModuleStrict mit reservierter Identität und
Präfix Z2MC. Passende zentrale DataFlow-, ParentConnection-, Form-, Darstellungs-
und Debughelper geprüft und unverändert genutzt. Der PersistentJsonCacheHelper
wurde geprüft; der vorhandene librarylokale Scope-/Sitzungs-Katalogvertrag bleibt
mit seinem einzelnen Attribut bestehen. Keine neue generische Cache-API und
keine lokale Helperkopie. MQTTTransport, ExtensionTransport und DeviceCatalog
enthalten die aus dem Bestandsmodul herausgelösten gemeinsamen Abläufe.

tests/connection.php verwendet ausschließlich synthetische native MQTT-Fixtures:
acht Verbindungen an einem Client und anschließend an acht getrennten Parents,
darunter ein MQTT Server. Unabhängige Health-/Erweiterungsaufträge, falsche und
retained Antworten, Zeitablauf, verspätete Antworten, Gateway-/Generationswechsel,
Disconnect/Reconnect und ungültige Systemkennung geprüft. Gleiche Geräte-/Gruppen-
kennungen bleiben nach SystemID getrennt. Ein ungültiger Katalog erhält bisherige
Bytes, liefert sie aber nicht als bestätigt aus. Kanalrechte, reine Sensoren und
Szenen bleiben normalisiert erhalten; fremde YAML-Felder werden nicht gespeichert.

Offiziellen Installationspfad mit echtem mitgeliefertem Artefakt an der synthetischen
Parentgrenze geprüft. Erfolg benötigt eine neue bestätigte Empfängersitzung;
unveränderte Sitzung sowie bekannte fehlende JavaScript-Freigabe werden abgewiesen.
Keine reale Installation. Buildvergleich und Popup-Rückmeldungen bestätigt.
Debug-Start, Stopp, Frist und Erhalt der Endzeit bei identischen Einstellungen
geprüft. Alle öffentlichen Einstiege nutzen DebugFlowHelper; Rohkonfiguration,
Sitzungen und JavaScript-Code fehlen in den aufgezeichneten Debugmeldungen.
Anlegen, ApplyChanges, Formularöffnen und lokale Abfragen senden kein MQTT.

php tests/run.php einschließlich sämtlicher PHP-Integrationstests und 137
JavaScript-Tests bestanden. Struktur-/Kodierungsprüfung, offizielle StylePHP-
Prüfung der 18 eigenen JSON-Dateien in isolierter Kopie und PHP CS Fixer 3.95.27
im Prüfmodus erfolgreich. Änderungen in UTF-8 ohne BOM mit LF geprüft. Gemeinsame
Helper, deren Manifest/Abonnement, Fremdstubs, Z2M-Quellen und Erweiterungsartefakt
unverändert. Kein Erweiterungsupdate erforderlich. Test- und Live-Installation
nicht angefasst. Native Konsolenabnahme des neuen Bausteins sowie reale
Mehrbroker-/Acht-System-Praxis bleiben offen; keine Datenübernahme oder Änderung
vorhandener Modulrollen. Nächster Umsetzungsschritt ist der lokale zentrale
Arbeitsbereich, noch kein Installations- oder Migrationsauftrag.

## Verbindungsstatusanzeigen lokal korrigiert am 04.11.2026

Die gezielte Regression tests/connection-status.php scheiterte zunächst an
fehlendem deutschen Initialstatus. Nach Ergänzung der fehlenden Übersetzungen
scheiterte sie am weiterhin falschen Installationsstatus trotz passender
bestätigter Empfängerantwort. Im gemeinsamen MQTTTransport wird diese Bestätigung
nun beim Speichern der lokalen Anzeige als Installationsnachweis berücksichtigt.
GetTransportState liefert denselben normalisierten Zustand wie Cache und Variable,
auch beim Berichtigen bereits vorhandener Bestätigungen nach einem Modulupdate.
Es wird keine zusätzliche Anfrage zur Anzeigeberichtigung gesendet.

Die Regression verwendet den wirklichen deutschen Modulkatalog anstelle der
Identitätsübersetzung der Fremdstubs und prüft die tatsächlich geschriebenen
Statusvariablen: Initialzustand, laufende Prüfung, Erfolg, gemeldetes Problem,
Fehler, Zeitablauf und Abbruch. Retained, unpassende Transaktion und ungültiges
Protokoll bestätigen keine Installation. Explizite Entfernung und Topicwechsel
verwerfen die zugehörige Bestätigung; unverändertes Lesen wiederholt die
Korrekturmeldung nicht. Deutsche native Debugmeldungen für Prüfergebnis und
Installationserkennung geprüft. Zentrale Helper geprüft und unverändert genutzt.

php tests/run.php einschließlich der neuen Regression und 137 JavaScript-Tests,
Struktur und zentrale Kodierungsprüfung erfolgreich. Offizielle StylePHP-Prüfung
der 18 eigenen JSON-Dateien in isolierter Kopie, PHP CS Fixer 3.95.27 im Prüfmodus,
git diff --check sowie UTF-8 ohne BOM mit LF bestanden. Keine Änderung an Helpern,
Fremdstubs, Z2M-Quellen oder Erweiterungsartefakt. Test-/Live-System nicht angefasst;
native Praxisprüfung nach Modulupdate steht aus. Kein Erweiterungsupdate nötig.

## Zentraler Arbeitsbereich lokal geprüft am 04.11.2026

Schritt 3 nach ADR 0026 implementiert WorkspaceState und sieben SystemWorkspace-
Modulfunktionen in Z2MAutomations. Die Daten liegen in einem separaten versionierten
Attribut. Relevante zentrale Helper und bestehende lokale Bausteine vorab geprüft;
keine Helperänderung und keine Parallelimplementierung von Datenfluss oder Editor.
Die nativen Identitäts-/Rechteprüfungen werden mit dem Bestandseditor geteilt.
Keine Rollenumschaltung, Migration, Besitzerübertragung oder neue Editoroberfläche.

tests/workspace.php prüft 1/2/8 synthetische Systeme mit tatsächlichen nativen
MQTT-, Geräte- und Gruppenfixtures sowie den öffentlichen Verbindungssnapshots.
Gleiche Geräte-/Gruppen-/Regel-/Folgenkennungen bleiben getrennt. Roh-YAML samt
Kommentar und Umlauten, lokale Auswahl und offene Eingaben bleiben erhalten.
Einseitige Empfangsfreigaben ergeben keine Gegenrichtung oder automatische
Freigaben für weitere Systeme. Gewöhnliche Bearbeitung darf bestehende Freigaben
nicht entfernen; nur ausdrückliche Freigabenänderung ändert die lokale Planung.

Native Custom-Action-Sperre und umbenannter Ident schützen nur betroffene Ziele.
Sensoren bleiben als lokale Auslöser verfügbar. Scene-Metadaten und TOGGLE-Auswahl
nutzen dieselben Konverter wie bisher. Zwischen einem anderen Modulaufruf und
lokaler Übernahme eingebrachte Bearbeitung bleibt erhalten; kein übergreifender
Semaphore wird dabei gehalten. Stale-Kontext, falsche Generation, doppelte IDs/
Topics, neunte Verbindung, Entfernung, unbekannte Version und beschädigte
Persistenz werden ohne stilles Ersetzen abgewiesen. Replays liefern ihre
persistierte Quittung; andere Parameter mit derselben Operationskennung scheitern.
ApplyChanges erhält den Arbeitsbereich. Legacy-Konfiguration und Editorattribute
bleiben während sämtlicher Workspace-Aktionen unverändert; kein MQTT-Publish.

Acht Kataloge mit je 255 synthetischen Geräten lokal gespeichert. Die Übersicht
bleibt 4482 Bytes groß; eine Seite mit 100 detaillierten Sensoren hat 351026 Bytes.
Die letzte Seite meldet die verbleibenden 55 Einträge und hasMore false. Ein
zusätzlicher passender nativer Aktor nach 513 fremden Geräteinstanzen bleibt
auswählbar; der Workspace scannt nach Scope, ohne globales 512er-Abschneiden.
Der vorhandene 1-MiB-MQTT-Payloadvertrag bleibt unverändert. Rechte-/Verbindungsverlust
löscht keine Dokumente und sperrt keine unabhängigen Systeme.

Abschließendes php tests/run.php einschließlich aller PHP-Integrationstests und
137 JavaScript-Tests erfolgreich. Struktur, zentrale Kodierungsprüfung,
offizielle StylePHP-Prüfung der 18 eigenen JSON-Dateien in isolierter Kopie,
PHP CS Fixer 3.95.27 im Prüfmodus und git diff --check bestanden. Kanonische
Helper, Fremdstubs, Z2M-Quellen und Erweiterungsartefakt bleiben unverändert.
Kein Zugriff auf Test oder Live; reale Workspace-Abnahme bleibt nach den
weiteren Integrationsschritten offen. Als Nächstes: Sicherung, Übernahme,
Besitzerwechsel und Rückweg; danach gemeinsame Oberfläche.

## 04.11.2026: Lokale Sicherung, Übernahme und Rückweg (Schritt 4)

SystemMigration, MigrationSource, MigrationArchive und SystemOwnership ergänzen
explizite lokale Modulfunktionen. Private Quellarchive und Manifest sind
unveränderlich und prüfsummengebunden; das atomare Journal bleibt über
ApplyChanges und verlorene Laufzeitpuffer erhalten. Quellen werden erst nach
Vergleich und vor Veröffentlichung der vollständigen Zielstände gesperrt.
Eine zusätzliche lokale Besitzergeneration im Workspace-Fingerabdruck verhindert
alte Ansichten nach Rückweg und erneuter Übernahme. Routinggeneration und
SystemID vorhandener Verbindungen werden nicht still geändert.

ConnectionOwnership schützt exklusive Verbindungs-/Mesh-Topic-Zuordnung und
verlangt den passenden Journalzustand. Neue Empfängerfreigaben werden nicht aus
historischen Puffern abgeleitet. Der Rückweg sperrt zunächst zentrale Änderungen,
sichert deren aktuellen Stand zusätzlich und prüft die Quellen. Er stellt den
exakten zentralen Ausgangsstand und die Referenzen wieder her, bevor alte
Editoren freigegeben werden. Nach einer ersten Quellenfreigabe entstandene
Änderungen werden bei Wiederholung nicht zurückgesetzt. Ein Update führt keine
Migration aus; bestehende native Geräte-, Variablen- und MQTT-Zuordnungen bleiben
erhalten. Gemeinsame Oberfläche und zielgebundene Betriebsbefehle folgen noch.

tests/migration.php prüft private Dateien, 1/2/8 synthetische Systeme,
bytegleichen YAML-Erhalt, gleiche lokale Kennungen, Freigaben, ausgewählte Regeln,
saubere Eingaben, native Bindungen und Actions sowie zweite Verwaltungen und
Verbindungsaliase. Fehler an Vorbereitungs-, Quellen-, Verbindungs-, Referenz-,
Veröffentlichungs- und Rückgabeschritten werden mit frischen Stubobjekten,
persistierten Attributen und verlorenen Puffern wiederaufgenommen. Quelländerung,
unbekannter/beschädigter Stand und Archivprüfsummenfehler überschreiben keine
Daten. Alte Operationskennungen können keinen neuen Besitzerwechsel beginnen.

Ein zusätzlicher fehlschlagender Nebenläufigkeitstest zeigte, dass eine breite
öffentliche Eintrittssperre gleichzeitig eintreffende MQTT-Antworten verwerfen
würde. ReceiveData wird deshalb durch die vorhandene Transportsperre geschützt,
ohne an der Bedien-Eintrittssperre zu warten. Besitzer werden dort nochmals
geprüft. Noch laufende Erweiterungsübergaben werden begrenzt im flüchtigen
TransportDispatches geführt und verhindern eine Übergabe trotz bereits
abgeschlossener Antwort. Netzwerkversand bleibt außerhalb der Transportsperre.
Keine neue Befehlswarteschlange und keine eigene Debug-Vorgangszählung.

Tests sichern null MQTT-Ausgaben durch Migration/Rückweg und ausschließliche
Debug-Metadaten statt privater Testeingaben oder Archivinhalt ab. Native
Parent-Metadaten, Helper und Fremdquellen bleiben unverändert. Es gab keinen
Zugriff auf Test oder Live. Ein echter Kernelneustart, Dateirechte und der
vollständige native Rückweg sind spätere ausdrücklich beauftragte Praxisabnahmen.
Die Z2M-Erweiterung wurde nicht geändert; ein Erweiterungsupdate ist für diese
lokalen Abläufe nicht erforderlich.

Abschließende Prüfung dieses Stands: php tests/run.php einschließlich der neuen
Migrationsintegration und aller 137 JavaScript-Tests erfolgreich. Separate
Struktur- und zentrale Kodierungsprüfung, offizieller StylePHP-Check der
18 eigenen JSON-Dateien in isolierter Kopie, PHP CS Fixer 3.95.27 im Prüfmodus
(108 Dateien ohne Befund) und git diff --check bestanden. Änderungen bleiben
uncommittet auf dev; Commit und Push führt der Benutzer durch.

## 04.11.2026: Selbstsperre bei der Übernahmevorschau

Die native Prüfung zeigte einen getrennten Modulaufruf auf dieselbe Instanz:
PreviewSystemMigration hielt bereits ihre Eintrittssperre; der native
Z2MA_GetSystemMigrationSource-Rückaufruf besaß keinen lokalen Wiedereintrittskontext
und wurde als konkurrierender Zugriff abgewiesen. Die direkte Bestandsabfrage
funktionierte, die Vorschau meldete dagegen source_unavailable.

Die Vorschau ruft GetSystemMigrationSource für die eigene Instanz jetzt direkt
auf. Andere Quellen bleiben native Modulaufrufe. Eigentums- und Transportsperren
sowie der Metadatenvertrag wurden nicht aufgeweicht. Der bestehende
DebugFlowHelper protokolliert die direkte Eigenprüfung im selben Vorgang,
einschließlich ausgeglichener START-/ENDE-Paare; keine zusätzlichen Zähler.

tests/multisystem.php ersetzt ausschließlich in diesem isolierten Testprozess
den nativen Quellaufruf durch einen separaten Eintrittskontext: Die Stubinstanz
wird geklont und ihr lokales Eigentums-Eintrittsflag zurückgesetzt, während
Kernelzustand und Semaphoren gemeinsam bleiben. Vor der Korrektur scheitert
dadurch bereits die Ein-Quellen-Vorschau. Nach der Korrektur bestehen 1/2/8
Quellen, Eigenquelle an anderer Position, Fremd- und Eigeninstanz mit belegter
Sperre sowie erneute Prüfung nach deren Freigabe. Die vorhandenen Prüfungen
sichern unveränderte Quellbytes und null MQTT-Ausgaben ab. Zusätzlich werden
deutscher Ablauftext und gemeinsamer Debug-Vorgang geprüft.

Nur lokale Dateien geändert; keine Test-/Live-Aktualisierung, Übernahme oder
Z2M-Aktion. Ein Erweiterungsupdate ist nicht erforderlich. Die erneute native
Vorschauprüfung nach Modulupdate bleibt ein eigener Praxisnachweis.

Abschließendes php tests/run.php einschließlich 137 JavaScript-Tests erfolgreich.
Separate Struktur-/Kodierungsprüfung, offizieller StylePHP-Check der 18 eigenen
JSON-Dateien in isolierter Kopie, PHP CS Fixer 3.95.27 im Prüfmodus (108 Dateien
ohne Befund) und git diff --check bestanden. Kanonische Helper und Fremdstubs
bleiben unverändert; Entwicklung auf dev ohne Commit oder Push durch den Agenten.

## 04.11.2026: Gemeinsame native und Browser-Systemauswahl

Schritt 5 bindet die vorhandenen Editorvalidatoren über WorkspaceEditorContext
an getrennte Systemdokumente. Attribute und Puffer erhalten explizite
Speicherzugriffe; keine persistierten Bestandsattribute werden beim Wechsel
ausgetauscht. WorkspaceForm baut die native Systemliste und systemgebundene
Callbacks auf, WorkspaceDesigner begrenzt Browserzugriffe auf feste lokale
Routen und einen ausdrücklich benannten Entwurf. Gemeinsame Helper wurden vor
der Umsetzung geprüft und bleiben unverändert. Es entsteht keine zusätzliche
Rückwärtskompatibilitätsschicht für alte Einzelinstanzen.

tests/workspace-ui.php verwendet ausschließlich synthetische Kernel-/MQTT-Daten:
acht getrennte Dokumente, identische Regelkennungen, native generierte Callbacks
einschließlich mehrerer Aufrufe pro Klick, offene Eingaben, Wiederherstellung
nach Pufferverlust, veraltete Fenster, Browser-Erstellung/-Bearbeitung, Freigaben
und fehlende Verbindungen. tests/migration.php prüft zusätzlich die Oberflächen
eines tatsächlich übernommenen Besitzers. Alle UI-Aufträge bleiben ohne MQTT.
Der Browser-Pagingvertrag ist separat in workspace-designer.test.mjs geprüft.

Die Skalierungsprüfung liest 2048 Geräte-/Gruppeneinträge aus acht großen
Katalogen vollständig in begrenzten Seiten. Native Ausgabe: 717433 Bytes;
erste Browserseite: 208722 Bytes. Ein nur während des Aufrufs gemeinsam genutzter
Katalog verhindert wiederholte große JSON-Dekodierungen. Browserseiten prüfen
System- und Arbeitsbereichsstand, damit geänderte Freigaben keine Seiten
verschiedener Stände mischen.

Ein separater lokaler Browser mit künstlichen Snapshots bestätigt Auswahl,
Rückkehr zum ersten System und passende Vorbelegung. Mausradbedienung im
Bearbeitungsdialog erreicht die unteren Aktions- und Übernahmebuttons.
Diese UI-Vorschau ist kein echter Symcon-Kernel-/MQTT-Praxisnachweis. Native
Konsole, reale Übernahme und zentrale Z2M-Aufträge bleiben gesonderte Schritte.
Keine Test-/Live-Aktualisierung, Geräteaktion oder Änderung der Z2M-Erweiterung.

php tests/run.php einschließlich 141 JavaScript-Tests erfolgreich. Nach den
abschließenden Meldungs- und Katalogseitenänderungen wurden die betroffenen
Modul-, Designer-, Migrations- und Workspace-UI-Tests erneut ausgeführt.
Struktur, zentrale Kodierungsprüfung, StylePHP der 18 eigenen JSON-Dateien,
PHP CS Fixer 3.95.27 im Prüfmodus (112 Dateien) und git diff --check bestanden.

## 04.11.2026: Anlegeweg ohne eigene MQTT-Schnittstelle

Der native Assistent leitete für Z2MAutomations weiterhin einen MQTT-Parent
aus den bisherigen Datenflussmetadaten ab. Dadurch ließ sich die im lokalen
Stub bereits parentlos erzeugte Zentrale nicht regulär über die Konsole anlegen.
Die Stubs bilden diese native GetCompatibleParents-Heuristik nicht nach.

Das Modul überschreibt jetzt den offiziellen SDK-Einstieg GetCompatibleParents
und liefert {}. Der Instanzassistent erhält damit ausdrücklich keine eigene
Schnittstellenanforderung. Der Alias lautet „Zigbee2MQTT Automationszentrale“.
Modul-GUID, Typ, Präfix, Datenflussmetadaten und vorhandene Elternverbindungen
bleiben erhalten. Das ist keine automatische Übernahme oder Trennung; nur das
Verbindungsmodul fordert weiterhin im Assistenten einen MQTT-Parent.

Der Metadatengetter verwendet TraceModuleCall und den DebugFlowHelper. Als
reiner Getter ist er auch bei gesperrten Quellen, vorbereiteter Übernahme und
übernommener Verwaltung verfügbar. Er benötigt keine schreibende Besitzerfreigabe.

tests/module.php scheitert vor der Korrektur an der fehlenden ausdrücklichen
SDK-Implementierung. Die zusätzliche Deklarationsprüfung ist wegen des leeren
Stub-Standardwerts nötig; sie ersetzt keinen echten Konsolenassistenten.
Danach prüft der Test {} und die direkte Systemeinrichtung einer neuen
parentlosen Instanz. tests/migration.php prüft den Getter auch bei übernommenen
Verwaltungen und gesperrten Quellen; deren Daten und Verbindungen bleiben erhalten.

Keine Test-/Live-Installation oder Z2M-Aktion. Die native Assistentenprüfung
nach Modulupdate ist offen; ein Erweiterungsupdate ist nicht erforderlich.

php tests/run.php einschließlich 141 JavaScript-Tests erfolgreich. Zusätzlich
Struktur, zentrale Kodierungsprüfung, offizieller StylePHP-Check der 18 eigenen
JSON-Dateien in isolierter Kopie, PHP CS Fixer 3.95.27 im Prüfmodus (112 Dateien)
und git diff --check ohne Befund. Entwicklung auf dev, kein Commit oder Push
durch den Agenten; kanonische Helper und Fremdquellen unverändert.

## Vorhandene Verbindungen per Häkchen zuordnen am 04.11.2026

Das zentrale Formular ermittelt vorhandene Z2MConnection-Instanzen unabhängig
von ihrer Darstellung als Gerät. Es zeigt Namen und Instanz-ID, Basistopic und
MQTT-Zuordnung. Neue Systeme werden nur nach Häkchenauswahl und ausdrücklicher
Bestätigung zugeordnet. Vorhandene Zuordnungen bleiben angehakt und geschützt;
fehlende Verbindungen bleiben sichtbar. Fremde Besitzer und ungültige
Verbindungen werden verständlich gekennzeichnet und zur Auswahl gesperrt.

Die Bestätigung prüft die dargestellten Verbindungsidentitäten und Besitzer
erneut. Ein zwischenzeitlicher Wechsel verlangt ein erneutes Öffnen des
Formulars. Entwürfe, MQTT-Parents, Besitzer und Z2M bleiben unverändert. Beim
erneuten Bestätigen bereits übernommener Systeme bleibt auch deren ursprüngliche
Quellinstanz erhalten; andernfalls scheiterte die bisherige Bindungsprüfung.
Die vorhandenen zentralen Formular-/Parenthelper wurden vorab geprüft;
Entdeckung und Systemzuordnung bleiben wegen ihrer Fachlogik librarylokal.

tests/workspace-connections.php prüft mit synthetischen Instanzen automatische
Entdeckung, Namen/Topics/Parents, native Listenspeicherung und den tatsächlich
generierten Callback mit Objektzeilen. Hinzu kommen leere und zu große Auswahl,
Besitzerwechsel, veraltetes Formular, ungültige und gelöschte Verbindungen sowie
unveränderte Entwürfe und ausbleibender MQTT-Versand. tests/migration.php prüft
das erneute Bestätigen übernommener Systeme einschließlich Herkunft und Besitz.
Deutsche Debugmeldungen erklären Entdeckung, Auswahl und Ablehnung ohne
Gerätenamen, Dokumentinhalt oder Zugangsdaten auszugeben.

php tests/run.php einschließlich 141 JavaScript-Tests erfolgreich. Zusätzlich
Struktur, zentrale Kodierungsprüfung, offizieller StylePHP-Check der 18 eigenen
JSON-Dateien in isolierter Kopie, PHP CS Fixer im Prüfmodus (113 Dateien) und
git diff --check ohne Befund. Kanonische Helper und Fremdquellen unverändert.
Keine Installation aktualisiert und keine Z2M-Aktion ausgeführt. Die Darstellung
und Bedienung in der nativen Konsole bleiben nach Modulupdate und erneutem
Öffnen des Formulars zu prüfen; ein Erweiterungsupdate ist nicht erforderlich.

## Browserstart und sichtbarer Systemwechsel am 04.11.2026

Die sichtbare Testabnahme fand einen Fehler vor dem Datenabruf: Mehrere
gleichzeitig angeforderte ES-Moduldateien wurden durch die sofort ablehnende
Besitzersperre mit HTTP 409 abgewiesen. Sequentielle Einzelabfragen waren
erfolgreich und belegten diesen Browserstart deshalb nicht. Ein isolierter
Modulimport und parallele echte HTTP-Abfragen grenzten den Fehler ein; die
Anzahl der Anfragen beziehungsweise Katalogdaten war nicht die Ursache.

Nur GET-Dateien der festen Designer-Positivliste warten nach belegter Sperre
höchstens 5000 ms. Danach folgen dieselben Besitzer- und Übernahmeprüfungen.
Datenabfragen, Änderungen, fremde Pfade und HTTP-Methoden erhalten keine neue
Warteberechtigung. Der kleine klassische Startloader importiert reader.js und
zeigt bei einem Importfehler eine deutsche Meldung mit ausdrücklichem Neuladen.
Er gibt keine Browserfehler, URLs oder Tokens aus und startet keine Wiederholung
von Schreibaufträgen. Debug erklärt Wartezeit und erfolgreiche Freigabe über
den kanonischen DebugFlowHelper. HTTP-/Asset-/Debughelper vor Umsetzung geprüft
und unverändert verwendet; die fachliche Besitzersperre bleibt librarylokal.

tests/designer.php bildet konkurrierende Eintrittskontexte über eine lokale
Semaphore-Fixture nach. Die neue Regression schlug vor der Korrektur mit HTTP
409 beim Laden von index.html fehl. Danach bestehen feste Dateiliste,
begrenztes Warten, permanente Belegung, unveränderte sofortige Daten-/Schreib-
sperren und unveränderte Entwurfs-/Laufzeitdaten. tests/designer-startup.test.mjs
prüft Modulimport, gesperrte alte Laufzeitbuttons, verständlichen Fehler,
Geheimnisschutz und Neuladen ausschließlich nach Bedienaktion.

Auf der Testinstallation mit Symcon 9.1 unter Windows nur die beiden betroffenen
PHP-Traits und startup.js nach Dateisicherung aktualisiert. Kein Bibliotheksreload,
Erweiterungsupdate oder Neustart. Sechs gleichzeitig geladene echte Dateien
lieferten danach HTTP 200 und exakt die lokalen Dateiinhalte. Die sichtbare
Browserprüfung erfolgte direkt am Symcon-WebHook, ohne Diagnoserelay: vollständiger
Start, Systemwechsel in beide Richtungen, passende Geräte und Gruppen,
Vorbelegung von Gerät/Kanal/Wert und ausgeschalteter Regel, Namensänderungen
in zwei getrennten lokalen Entwürfen und Erhalt nach erneutem Wechsel.
Native Debug-Ausgaben bestätigen jeweils den richtigen Systembezug.

Vorherige Entwurfsdaten und Auswahl gesichert und nach der Prüfung exakt
wiederhergestellt. Bindungen, gespeicherte und aktive Z2M-Revisionen sowie
bestätigte Laufzustände sind unverändert. Kein Schalten, Speichern oder
Aktivieren in Z2M. Diagnoserelay beendet, temporäre Prüfansichten geschlossen
und Debug-Dateiaufzeichnung beendet. Kein Produktionszugriff. Native Abnahme
unter Symcon 9.0 wird durch die Prüfung unter 9.1 nicht ersetzt.

php tests/run.php einschließlich 143 JavaScript-Tests erfolgreich. Separat
Struktur und zentrale Kodierungsprüfung, offizieller StylePHP-Check der 18
eigenen JSON-Dateien in isolierter Kopie, PHP CS Fixer im Prüfmodus (114 Dateien)
und git diff --check erfolgreich. Fremdquellen und Erweiterungsartefakt
unverändert; Entwicklung auf dev, kein Commit oder Push durch den Agenten.

## Zentrale Entwurfsprüfung und Übertragung am 04.11.2026

Schritt 6 ergänzt ausschließlich validate/store je Z2M-System. Keine Aktivierung,
kein Stopp, kein Einzellauf und kein neuer Erweiterungsbuild. Native Formular-
buttons und Designer verwenden RequestWorkspaceTransfer mit UUID, Generation,
Dokumentfingerabdruck, neuer Auftragskennung und aktuellem Übertragungskontext.
Voraussetzung ist eine abgeschlossene gesicherte Übernahme; Häkchenauswahl allein
erteilt keine Schreibrechte. Die vorhandenen ParentConnection-, DataFlow- und
DebugFlow-Verträge wurden geprüft und unverändert verwendet. Journal und
fachlicher Protokollablauf bleiben librarylokal.

Vor der Delegation vermerkt die Zentrale den Auftrag dauerhaft. Die Verbindung
prüft Eigentümerschaft und aktuellen Entwurf erneut vor jedem MQTT-Schritt.
Die reine Freigabeabfrage der Zentrale besitzt eine begrenzte Ausnahme von der
Eintrittssperre für synchrone Antwortketten. Es gibt keine zweite Editorablage
und keine Freigabe durch frei übergebene Besitzerwerte. Gemeinsam genutzte reine
Session-/Revisions-/Prüfsummenprüfungen erhalten den bisherigen Einzelbetrieb.

Speicherung benötigt aktuellen Prüfbeleg, unveränderte Revisionsbasis, bestätigten
Katalog sowie erneut geprüfte native Aktionsbindungen und Partnerfreigaben.
Annahme der Datei allein bedeutet noch keinen Erfolg: Eine frische Statusantwort
bestätigt Sitzung, Revision, Prüfsumme, Anzahl und Laufzustand. Danach wird nur
bei unverändertem Dokument dessen Bearbeitungsbasis fortgeschrieben. Roh-YAML,
Auswahl und offene Eingaben bleiben erhalten. Bei einem währenddessen bearbeiteten
Dokument wird kein falscher neuer Basisstand eingetragen.

Verlorene Antworten, Zeitablauf, Gatewaywechsel und Neustart führen nicht zu
stillen Wiederholungen. Fehlerdetails bleiben bis zur neuen Prüfung sichtbar.
Ein Gatewaywechsel beendet auch die zentrale Warteanzeige verständlich, ohne
eine Rückmeldung des neuen Ziels zu verwenden. Vor einem möglichen store-Versand
entsteht ein persistenter Vermerk; die Zentrale sperrt danach den rein lokalen
Übernahme-Rückweg. Er könnte den externen Stand nicht wiederherstellen.

Tests/workspace-transfer.php nutzt tatsächlich übernommene Besitzer, synthetische
MQTT-Parents und die öffentlichen Modulfunktionen für 1/2/8 Systeme. Gemeinsame und
getrennte Broker sowie identische Geräte- und Regel-IDs geprüft. Abgedeckt sind
positive Prüfung/Speicherbestätigung, Einzelsystemfehler bei weiter nutzbarem
Nachbarsystem, detaillierte Ablehnung und Erhalt nach Statusabfrage, offene
Eingaben, Dokumentänderung, verlorene native Schaltbindung, alte Sitzung, falsche
Transaktion/Phase, Retain, inkompatibles Protokoll, Fristablauf, fehlende finale
Bestätigung, verlorener Workflow und Gatewaywechsel. Tatsächlich generierte
native Callbacks und authentifizierte HTTP-Einstiege verwenden denselben Weg.
Doppelte Aufträge senden nicht erneut; Debug enthält keine privaten Testnamen.

Tests/workspace-transfer.test.mjs prüft die Browserheader, explizites POST statt
GET, System-/Generationszuordnung, deutsche Fehler ohne Browserrohdaten und
fehlende automatische Schreibwiederholungen. DOM-Fixtures prüfen gesperrten
Speicherbutton, Bestätigungsabbruch und Verwerfen einer verspäteten Antwort nach
Systemwechsel. CSS lässt die neuen Buttons umbrechen und Fehlertexte vollständig
anzeigen. Eine sichtbare native/browsergestützte Praxisabnahme dieses Schritts
auf Symcon steht nach Update noch aus. Keine Kachel-/IPSView-Ausgabe ergänzt.

Keine Test- oder Produktivinstallation angesprochen oder aktualisiert. Keine
realen MQTT-Nachrichten, Dateiübertragung, Aktivierung, Neustarts oder Schaltungen.
Fremdquellen, kanonische Helper und Erweiterungsartefakt unverändert. Entwicklung
auf dev; Commit und Push verbleiben beim Benutzer über GitHub Desktop.

Prüfnachweis: php tests/run.php einschließlich 147 JavaScript-Tests erfolgreich.
Zusätzlich betroffene Workspace-Integration nach den abschließenden Korrekturen,
Struktur und zentrale Kodierungsprüfung bestanden. Offizieller StylePHP-Check
der 18 eigenen JSON-Dateien in isolierter Kopie erfolgreich; Fremdquellen werden
nicht hineinkopiert. PHP CS Fixer 3.95.27 im Prüfmodus für 118 Dateien ohne Befund. Alle 32
geänderten eigenen Dateien sind UTF-8 mit LF; git diff --check ist ebenfalls
ohne Befund. Sichtbare Symcon-Abnahme bleibt ausdrücklich offen.

## Direkte Zentraleinrichtung ohne alte Einzelinstanzen am 04.11.2026

Die Abnahme der zentralen Übertragung zeigte eine fehlende Voraussetzung:
Eine neu angelegte Zentrale besitzt keine alten Quellinstanzen. Der bisherige
Migrationsablauf konnte deshalb keine Entwürfe oder Verbindungsclaims liefern.
Der neue ausdrücklich beauftragte Einrichtungsweg liest den gespeicherten
Z2M-Stand direkt. Er baut keine künstlichen Einzelinstanzen und beansprucht
keinen Besitzer beim bloßen Zuordnen, Anhaken oder Öffnen einer Ansicht.

WorkspaceSetup/ConnectionWorkspaceSetup verwenden den vorhandenen editor/status-
Vertrag. Zunächst werden gespeicherte Automationen und Katalog nur gelesen,
korreliert geprüft und privat mit dem vorherigen lokalen Stand gesichert.
Die native Vorschau zeigt Topic, Revision, Anzahlen, Freigaben und Betriebszustand.
Erst der getrennte Bestätigungsbutton fragt eine frische Statusantwort ab,
beansprucht lokal die Verbindung und veröffentlicht das Dokument. Die YAML
bleibt bytegleich; Namen, IDs, enabled und mesh_peers bleiben unverändert.
Ein bestätigter leerer Speicher ergibt nur einen neuen lokalen Entwurf mit
Revision 1/Basis 0. Gesperrte oder widersprüchliche Speicher werden abgewiesen.
Vorhandene lokale Dokumente und offene Eingaben werden nicht ersetzt.

Die bisherige Claimprüfung wird für direkte Einrichtung und Legacy-Übernahme
geteilt. Nur die jeweilige journalgebundene Autorität erlaubt den Claim.
Der gemeinsame Adapter für native Variablenbindungen wird ebenfalls verwendet.
Private MigrationArchive-Dateien sichern Vorschau und späteren Rückgabestand.
Das begrenzte WorkspaceSetup-Journal hält dauerhaft die Commit-/Rückgabephase;
andere Änderungen bleiben bei einem halben Übergang gesperrt. Lokale Schritte
lassen sich ohne neue MQTT-Anfrage fortsetzen. Der Rückweg betrifft nur den
einen Systemplatz und sichert neue lokale Änderungen zusätzlich. Nach möglichem
Z2M-Schreiben bleibt er wie bei der bisherigen Migration gesperrt.

Direkte Systeme nutzen anschließend denselben Prüf-/Speicherpfad. Dessen
Integrationstest deckt insbesondere den tatsächlich im zentralen Editor
verwendeten normalisierten Katalog ab: Direkt gelesene Geräte und Schaltrechte
müssen im Designer verfügbar sein und die folgende Speicherung freigeben.
Eine bloß angezeigte erfolgreiche Übernahme reicht als Nachweis nicht aus.

Tests/workspace-setup.php prüft 1/2/8 Systeme an gemeinsamen/getrennten Brokern,
identische Geräte-/Regelkennungen, unveränderte aktive Empfänger, exakte YAML,
leeres Z2M, fehlende Autorität, doppelte Bestätigung, geschützte vorhandene
Dokumente, Timeout, sofortige Freigabe eines verworfenen Leseauftrags, falsche
Prüfsumme und Neustart des Empfängers zwischen Vorschau und Bestätigung.
Neue Stubobjekte mit echten persistierten Attributen statt alten Puffern prüfen
Vorschaufortsetzung und Abbruch nach Claim, Besitzerwechsel und Veröffentlichung.
Die Rückgabe wird nach Datenwiederherstellung und Claimfreigabe unterbrochen und
fortgesetzt. Native generierte Callbacks werden tatsächlich ausgeführt. Danach
folgen eine echte synthetische validate/store-Antwortkette für eine unveränderte
Datei sowie die gesperrte lokale Rückgabe nach möglichem externem Schreiben.
Debug bleibt deutsch und enthält keine privaten Testnamen oder YAML-Inhalte.
Der Einrichtungsablauf selbst sendet ausschließlich status/editor und nie
store, activate, deactivate, run, restart oder Gerätebefehle.

Zentrale ParentConnection-, DataFlow-, Form- und DebugFlow-Helper vor Umsetzung
geprüft und unverändert eingebunden. Keine Fremdquellen oder Änderungen am
Z2M-Schema, Runtime-Protokoll oder generierten Erweiterungsartefakt. Die neue
Bedienung liegt im nativen Formular; der Designer verweist bei fehlendem Entwurf
auf Direkt aus Z2M einrichten und übernimmt anschließend die eingerichteten
Geräte und Regeln. Sichtbare Symcon-/Browser-Praxisabnahme nach Modulupdate ist
noch offen. Keine Test- oder Produktivinstallation angesprochen oder aktualisiert;
kein realer MQTT-Befehl, Neustart oder Schaltversuch. Commit/Push verbleiben beim
Benutzer. ADR 0027 und Benutzeranleitungen dokumentieren den Einrichtungsweg.

Abschließender Prüfnachweis: php tests/run.php vollständig erfolgreich,
einschließlich neuer direkter Setup-Integration, bisheriger Übernahme-/Transfer-
Tests und 147 JavaScript-Tests. Zusätzliche Struktur- und zentrale Kodierungsprüfung
bestanden. Offizieller StylePHP-Check der 18 eigenen JSON-Dateien in isolierter
Kopie erfolgreich; kanonische Helper, Stubs und andere Fremdquellen nicht
hineinkopiert oder verändert. PHP CS Fixer 3.95.27 im Prüfmodus: 0 von 122 Dateien
mit Änderungen. Alle 27 geänderten eigenen Dateien UTF-8 ohne BOM mit LF;
git diff --check ohne Befund. Native sichtbare Bedienung und echte Z2M-
Ersteinrichtung bleiben nach einem ausdrücklich beauftragten Testupdate offen.

## Formularneuaufbau nach direkter Übernahme am 04.11.2026

Die Testabnahme zeigte nach erfolgreicher Übernahme eine falsche Besitzersperre
im automatisch aufgebauten Formular. ReloadForm wurde noch innerhalb der
Besitzersperre des schreibenden Aufrufs ausgelöst. Der getrennte Kernelabruf
GetConfigurationForm konnte diese Sperre nicht betreten. Ein späteres Öffnen
funktionierte, weil der erste Aufruf inzwischen beendet war.

RequestWorkspaceFormReload merkt zentrale Aktualisierungen nur im aktuellen
PHP-Eintritt vor. Erst der äußerste Aufruf gibt die Instanzsperre frei und
übergibt genau einen Neuaufbau an Symcon. Setup, lokaler Rückweg, Systemwahl,
Katalogübernahme und Prüfstatus nutzen denselben Weg. Kein neuer Timer, kein
persistenter Auftrag, keine Ausnahme von Besitzer- oder Schreibprüfungen.
GetConfigurationForm kann einen unterbrochenen lokalen Commit fortsetzen und
den fertigen Stand direkt liefern; dabei entfällt ein rekursiver Neuaufbau.
Fehler der Anzeigeaktualisierung werden bereinigt erklärt und verändern weder
den erfolgreichen Rückgabewert noch den bereits gespeicherten lokalen Stand.

Die vorhandene Setup-Integration bildet ReloadForm jetzt durch einen getrennten
Stubkontext mit tatsächlichem Besitzersemaphor und aktuellem persistiertem Stand
nach. Der Regressionstest schlug vor der Korrektur wegen der gesperrten Anzeige
fehl. Geprüft sind 1/2/8 Systeme, synchrone Bestätigung und asynchrone Antwort mit
Statustimer, lokaler Rückweg, Wiederaufnahme beim Formularöffnen ohne Reloadschleife
sowie ein gestörter nativer Reload. Daten und MQTT-Aufträge bleiben erhalten;
die Anzeige erzeugt keine weiteren Z2M-Anfragen. Fremdquellen und zentrale
Helper bleiben unverändert. Reale erneute Abnahme nach Modulupdate noch offen;
keine Test-/Produktivinstallation oder Z2M-Erweiterung in diesem Auftrag geändert.

Abschließender Prüfnachweis: php tests/run.php erfolgreich, einschließlich der
Setup-Regression und 147 JavaScript-Tests. Struktur und zentrale Kodierungsprüfung
bestanden. Offizieller StylePHP-Check der 18 eigenen JSON-Dateien in isolierter
Kopie erfolgreich; Fremdquellen unverändert ausgeschlossen. PHP CS Fixer 3.95.27
im Prüfmodus ohne Befund für 122 Dateien. Alle neun geänderten eigenen Dateien
UTF-8 ohne BOM mit LF; git diff --check ohne Befund. Erneute native Testabnahme
nach Modulupdate bleibt offen; ein Erweiterungsupdate ist nicht erforderlich.

## Zentrale Betriebssteuerung lokal geprüft am 04.11.2026

ADR 0028 ergänzt systemgebundene Aktivierung, globalen Stopp, Einzelstart und
gezielten Laufstopp im nativen Formular und Designer. Vorhandene Parent-,
Datenfluss-, Formular- und Debughelper geprüft und weiterverwendet. Protokoll,
Auftragsjournal und Z2M-Freigabe bleiben librarylokal. Keine neue Engine,
Verbindung, Maskierungslogik oder Erweiterungsdatei. Die reine Autoritätsabfrage
ruft die bereits gesperrte Verbindung nicht erneut auf.

tests/workspace-runtime.php prüft Kernel-Fixtures und MQTT-Workflows für
1/2/8 Systeme mit gemeinsamen/getrennten Brokern, unveränderten Dokumenten,
gleichen Regelkennungen, frischem Abschlussstatus, verlorenem Workflow,
geänderter Sitzung, Doppelaufruf und veraltetem gezieltem Stopp. Bestätigungen
und reine Cacheabfragen können keinen Auftrag erneut senden. Generierte native
Callback-Skripte und die authentifizierten Browserrouten werden ausgeführt.
Reine Statusabfrage erhält den lokalen Rückweg; möglicher Betriebseingriff
sperrt ihn. Native Statusvariablen folgen dem ausgewählten System.

Der vorhandene Node-Peer verwendet zusätzlich das tatsächliche unveränderte
Erweiterungsartefakt mit synthetischen Geräten. Bestätigt sind Aktivierung samt
Wiederanlaufeinstellung, globaler Stopp, Einzelstart einer ausgeschalteten Regel,
Abbruch vor einem verzögerten Gerätebefehl und Ablehnung wegen einer Bedingung.
Keine Gerätebefehle ausgeführt. Browserprüfungen decken Zielbindung, ausdrückliche
Bestätigung, lesendes Polling, sichere Namensanzeige und verspätete Antworten ab.

Reale Abnahme nach Modulupdate in nativer Testkonsole und Designer bleibt offen.
Keine Test-/Produktivinstallation, Besitzer, reale Regel oder Z2M-Erweiterung
wurde für diese Implementierung verändert. Die bisherigen Nachweise tatsächlich
geprüfter Z2M-Versionen werden durch synthetische Tests nicht erweitert.

Prüfnachweis: Gesamtsuite php tests/run.php erfolgreich, einschließlich 150
JavaScript-Tests und realem Erweiterungs-Peer. Abschließende Runtime- und
Setup-Regression ebenfalls erfolgreich. Struktur und zentrale Kodierungsprüfung
bestanden. Offizieller StylePHP-Check aller 18 eigenen JSON-Dateien in isolierter
Kopie erfolgreich. PHP CS Fixer 3.95.27 im Prüfmodus ohne Befund für 128 Dateien.
npm run check:extension bestätigt das unveränderte Artefakt. Fremdquellen
unverändert, eigene Änderungen UTF-8 ohne BOM/LF; git diff --check ohne Befund.
