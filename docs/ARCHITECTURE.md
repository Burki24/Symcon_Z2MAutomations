# Architektur

## Startbedingungen

Die optionale Gerätebedingungsangabe `start_only` wird als StartOnly in der
vorhandenen nativen Bedingungszeile geführt. Speicherung, Vorbelegung und
EditorWorkInput verwenden den bestehenden Pfad. Die Runtime unterscheidet
Neuanfang von laufenden Schritten und Verlängerung; Zeit-/Kalenderbedingungen
bleiben unverändert. Keine neue MQTT-Operation, Laufzeit oder zentrale Helper-
Abstraktion. Details: [ADR 0020](adr/0020-start-only-device-conditions.md).

## Gemeinsame Gerätebefehle und native Farbauswahl

EditorCommands ergänzt die bestehende Aktionszeile um eine begrenzte,
kanonische Zusammenstellung von bis zu acht Eigenschaften. Das native Formular
verwendet weiterhin Choice/Delay und speichert einen einzigen set-Schritt mit
mehreren values. Geräteeigenschaften bleiben an die tatsächlichen Symcon-
Variablen-IDs und deren aktuelle Schaltrechte gebunden, auch beim Partnerexport.
Erweiterte oder nicht vollständig erlaubte Befehle werden nie teilweise in eine
vereinfachte Zeile umgewandelt. Alte Einzelwertaufrufe dürfen solche Schritte
nicht verkürzen. Wiederverwendbare Folgen nutzen denselben Pfad.

Der Z2M-Katalog erkennt gezielt schreibbare color_xy/color_hs-Composites und
exportiert deren Elterneigenschaft als Farbmetadatum. Kinder von Composites
werden nicht als eigenständige MQTT-Eigenschaften angeboten. Native SelectColor-
Ganzzahlen werden als verschachteltes hex-Objekt übertragen; keine eigene
Farbraumumrechnung. Schema, Runtime und Mesh transportieren bereits solche
Objekte und bleiben unverändert. Details und Grenzen in
[ADR 0019](adr/0019-combined-device-commands-and-colors.md).

## Erneute Auslösung

Die optionale Regelangabe retrigger wird im bestehenden Schedule-Optionsvertrag
erzeugt und durch EditorWorkInput geschützt. Die vorhandene Runtime entscheidet
bei einer belegten Regel zwischen Ignorieren, einem serialisierten Neustart
und Erneuern der aktuellen Wartezeit. Mesh-Übergaben bleiben bis zu ihrem
Ergebnis unangetastet; Fehler, Stopp und Generationswechsel löschen vorgemerkte
Neustarts. Keine eigene Queue, Timerengine oder MQTT-Steuerschnittstelle.
Siehe [ADR 0018](adr/0018-retrigger-running-automations.md).

## Kalendertage und mehrere Uhrzeiten

AutomationEditorCalendar ergänzt die vorhandene Schedule-Bearbeitung um eine
begrenzte native Uhrzeitenliste und unabhängige Wochentagsfelder. Mehrfachzeiten
verwenden die vorhandenen time-Auslöser mit gemeinsamem Editor-Zeitzonenfeld.
EditorWorkInput schützt auch diese Listen und Felder. Die neue Schema-1-Klausel
weekday prüft in Z2M den aktuellen Kalendertag ihrer Zeitzone; Zeitfenster behalten
ihren bisherigen Starttag. Beide gelten als eigenständige UND-Bedingungen.
Keine zusätzliche Symcon-Laufzeit und kein zweiter Zeitplaner. Details und
Updategrenzen stehen in [ADR 0017](adr/0017-independent-weekdays-and-clock-list.md).

## Inhaltlicher Erweiterungsstand

Der Generator bindet eine deterministische Inhaltskennung in das eigene Artefakt
ein. Die laufende Erweiterung meldet sie additiv im Status. ExtensionContract
liest den lokalen Upload-Header und prüft empfangene Kennungen; der bestehende
Statusworkflow aktualisiert drei native Labels und bereinigte Debugentscheidungen.
Keine zusätzliche Datei, Versionsänderung oder Laufzeitsteuerung. Details und
Grenzen stehen in [ADR 0016](adr/0016-content-based-extension-build.md).

## Wiederverwendbare Aktionsfolgen

Der gemeinsame native Editor verwaltet benannte Definitionen und stabile
Verweise in Regelaktionen. ReusableSequences verwendet die bestehenden
Aktionsdialoge, Schaltrechte und die gebundene Entwurfsbearbeitung. SequencePlan
prüft die Schrittgrenzen aller verwendenden Regeln vor einer lokalen Änderung.
Die Z2M-Erweiterung prüft das vollständige Dokument und bildet mit sequences.mjs
einen begrenzten Ausführungsplan im vorhandenen Regellauf. Speicherung,
Wiederanlauf, Bedingungen und Stopp behalten denselben Besitzer. Über Mesh werden
nur konkrete Aktionen übergeben. Siehe [ADR 0015](adr/0015-reusable-action-sequences.md).

## Vorhandene Szenen als Aktionen

Die eigene Z2M-Erweiterung liest Szenen ausschließlich aus den vorhandenen
Endpoint-Metadaten. Der begrenzte Editor-Katalog enthält Geräte-/Gruppenidentität,
Szenen-ID, optionalen numerischen Geräteendpoint, Anzeigename und den zugehörigen
Schaltkanal. EditorScenes ergänzt daraus auswählbare Aktionswerte. Die Berechtigung
bleibt an die native Symcon-Aktion dieses Kanals gebunden, auch beim Partnerexport.
Das YAML speichert eine eigene `scene`-Aktion ohne Namen oder Symcon-IDs.
Schema, Prüfung, Aktivierung, Laufzeit und Mesh-Empfänger prüfen die passenden
Grenzen; die tatsächliche Szenenverfügbarkeit wird am ausführenden System geprüft.
Siehe [ADR 0011](adr/0011-recall-existing-z2m-scenes.md).

## Zwei eigenständige Z2M-Systeme

`RemoteAutomationTargets` erweitert nur Aktionsziele um den ausdrücklich
gewählten Partner mit anderem Basistopic. Beide Module dürfen dasselbe
MQTT-Gateway verwenden. Die Zuordnung prüft immer Gateway und Basistopic
zusammen; die Gateway-Gleichheit allein macht ein Ziel nicht lokal oder fremd.
`GetActionCatalog` exportiert dessen
lokale, nach Symcon-Schaltrechten gefilterten Geräte und Gruppen. Ein Export
enthält nie erneut fremde Ziele. Der Katalog wird je öffentlichem Aufruf gelesen;
die Variablenrechte werden vor jeder Verwendung erneut geprüft. Eigene Quellen
und Ereignissuche bleiben an das eigene Gateway gebunden. YAML verwendet keine
Symcon-IDs. Der Import unterstützt lokale Quellen mit gezielt gewählten
entfernten Aktionszielen. [ADR 0009](adr/0009-cross-system-actions-over-mqtt-bridge.md)
definiert Einmalfreigaben, Fehlergrenzen und den separaten Mesh-Bereich.

## Symcon-Ereignisimport

Ein lesender Import übersetzt ausdrücklich unterstützte Kernel-Ereignisse über
Variablen-IDs in lokale Regeln. `Z2MInstanceDiscovery` bindet die Zuordnung an
die eigene MQTT-ConnectionID und das ausgewählte Basistopic; pro Z2M-System wird
eine eigene Automationsinstanz verwendet. Fremde Elternobjekte werden vor
Instanzzugriffen ausgeschlossen. Die Ereignissuche ist vom geladenen Katalog
unabhängig, die Importfreigabe erfordert weiterhin dessen passenden Kontext. Ein direkter
bestätigter Ladevorgang verwendet denselben Editor-Workflow und prüft nach dessen
Abschluss die vorgemerkte Ereignisauswahl erneut. Er führt keinen Quellcode aus und verändert weder
Originalereignisse noch Z2M. Quelle und Ziel müssen zur geladenen Geräte-/Gruppen-
Identität passen; unbekannte Semantik wird vollständig abgewiesen. Vorschau,
Konfliktprüfung und deterministische Import-ID sichern die Übernahme ab.
Der bestehende Klick-Editor verwaltet importierte Regeln einschließlich Gruppen.
Umschalten besitzt eine eigene Schema-1-Aktionsvariante und liest den aktuellen
Z2M-Zielzustand unmittelbar vor der nativen Set-Übergabe. Siehe ADR 0008.


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

## Zuständigkeiten

Der Geräteeditor bildet ODER-Auslöser mit individuellen Vergleichen und
Mindestdauern ab. Geräte- und Zeiteditor teilen die AND-Bedingungsliste für
lesbare skalare Eigenschaften plus optionales Zeitfenster. Die Liste bleibt
librarylokal in AutomationEditorConditions; die Z2M-Engine führt die vorhandenen
Schema-1-Klauseln ohne Symcon-Abhängigkeit aus. Der native Dialog verwendet
Gerät, Eigenschaft, Vergleich und typisierten Wert. Aktionsrechte und deren
Gerätegrenzen bleiben davon getrennt.

Die nativen Geräte- und Zeitbereiche besitzen eigene statische Formularfelder
und darin eingebettete Bearbeitungen. `AutomationEditorAreas` ordnet gemeinsame
Befüllung und Live-Updates den jeweiligen lokalen Feldern zu und schützt
bereichsgebundene Formularaufrufe. Eine Regelauswahl bleibt aktiv; Validierung,
Speicherung und Laufzeitsteuerung bleiben gemeinsame Backend-Funktionen.
Mehrfachauslöser verwenden weiterhin die begrenzte Metadatenbearbeitung im
Bereich weiterer Automationen. Bestehende öffentliche Aufrufe bleiben durch
optionale letzte Bereichsparameter kompatibel. Keine Änderung am YAML-Schema.

Symcon ist der Editor und Verwalter. Zigbee2MQTT ist die Laufzeit.
Beide Teile erhalten eigenen Implementierungscode in diesem Repository:

- Z2MAutomations/: Symcon-Modul mit Formularen und künftig MQTT-Übertragung.
- libs/z2m/: eigene Erweiterungsquellen, kanonisches YAML-Schema und hochladbare
  Einzeldatei mit eingebettetem Schema.
- libs/Z2MProtocol.php: librarylokaler MQTT-/Z2M-Vertrag.
- libs/ExtensionContract.php und ExtensionIntegration.php: normalisierte
  Erweiterungsantworten und korrelierte Installations-/YAML-Workflows.
- libs/helper/: unverändertes, manifestiertes Bundle aus Symcon_ModuleHelper.
- libs/: librarylokale Logik, sobald benötigt.
- tests/: Offline-Tests mit synthetischen Daten.

Ein einzelnes Verwaltungsmodul ist der Einstieg. Weitere Module, Verbindungen
und öffentliche Datenfluss-GUIDs werden erst bei konkretem Bedarf eingeführt.
Das Modul verbindet sich direkt mit einem vorhandenen nativen Symcon MQTT Client
oder MQTT Server. Es gibt keinen eigenen Broker-Client und keinen Zwischenweg
über die separate Zigbee2MQTT-Gerätelibrary. Siehe
[ADR 0002](adr/0002-use-native-symcon-mqtt.md) und [MQTT-Vertrag](MQTT.md).

## Geplanter Übertragungsweg

1. Z2M-Version und externe JavaScript-Freischaltung prüfen.
2. Eigene .mjs über <base>/bridge/request/extension/save übertragen.
3. Offizielle Antwort und eigene Bereitschaftsmeldung der Erweiterung auswerten.
4. YAML als String in JSON über ein eigenes MQTT-Topic übertragen.
5. Erweiterung validiert, speichert und aktiviert die Regeln.
6. Symcon zeigt erst nach Bestätigung die aktive Revision an.

Die MQTT-Grundlage mit konfigurierbarem Basistopic, Bridge-Status und manuellem
health_check ist implementiert. Die Z2M-Seite empfängt und speichert inzwischen
versionierte YAML über eigene Topics; siehe [Erweiterungsprotokoll](EXTENSION_PROTOCOL.md)
und [YAML-Vertrag](YAML_SCHEMA.md). Die Symcon-Seite installiert das feste Artefakt
auf ausdrückliche Bedienung, prüft den Empfänger und überträgt nach Vorschau und
Bestätigung validierte YAML. Die eigene Engine verarbeitet eine ausdrücklich
aktivierte Speicherrevision; siehe [ADR 0005](adr/0005-explicit-execution.md).
Aktivierungs-/Stoppbuttons im Modul verwenden eine frische Abfrage, den
korrelierten Steuerauftrag und eine zusätzliche Statusverifikation.
Der erste Klick-Editor liest begrenzte diskrete Exposes und die gespeicherte
Konfiguration aus der Erweiterung. Neue Regeln werden lokal ergänzt; Prüfung,
Speicherung und Aktivierung bleiben ausdrücklich getrennte Bedienaktionen.
Regeln werden über stabile IDs ausgewählt. Bearbeiten und Löschen erzeugen nur
einen lokalen Entwurf. Eine Auswahlkennung bindet die Bedienung an den aktuellen
Entwurf und verhindert Änderungen aus veralteten Formularen. Einfache Regeln
werden vollständig bearbeitet; bei erweiterten Regeln bleiben die Strukturen
für sichere Namens-/Aktivierungsänderungen erhalten.
Die native Aktionsliste verwaltet bis zu 16 geordnete Geräteaktionen mit Wartezeit
vor jedem Schritt. Alle List-Zeilen werden atomar mit den Auslöser-/Bedingungsfeldern
in den Entwurf übernommen. Nur vollständig im Katalog darstellbare Folgen werden
bearbeitet; nicht unterstützte Strukturen werden nicht teilweise vereinfacht.
Geräteaktionen werden innerhalb Z2Ms an `eventBus.emitMQTTMessage` übergeben;
sie benötigen keine Rückzustellung eigener Set-Veröffentlichungen vom Broker.
Siehe [ADR 0007](adr/0007-native-command-dispatch.md).

## Persistenz und Fehlerfälle

Die Erweiterung hält die letzte gültige YAML im eigenen persistenten Unterordner
und lädt sie beim Neustart. Revisionskontrolle, Prüfsumme, Größenlimits,
idempotente Wiederholung und Sicherung der Vorversion sind implementiert.
Offline-Tests decken echte Dateischreibvorgänge, Schreibfehler und Wiederanlauf ab.
Die Engine verwirft bei Stopp Verzögerungen und wird vor dem Speichern einer
geänderten Revision deaktiviert. Eine getrennte persistente Betriebsfreigabe
erlaubt nach Z2M- oder Erweiterungsneustart ausschließlich denselben ausdrücklich
aktivierten Regelstand. Ohne Freigabe oder nach bestätigtem Stoppen bleibt sie aus.
Siehe [ADR 0010](adr/0010-persist-explicit-execution-intent.md).

Code-Updates und Regeländerungen sind getrennte Vorgänge. Der offizielle Upload
schreibt JavaScript vor dem Laden und garantiert keinen automatischen Rollback.
Ein eigenes Updateverfahren muss diesen Fehlerfall berücksichtigen.

Keine Übertragungsbefehle mit MQTT-Retain speichern. Broker-Berechtigungen müssen
Code-Upload und Konfigurationsänderungen auf berechtigte Clients begrenzen.

## Externe Verträge

- [Externe Erweiterungen](https://www.zigbee2mqtt.io/advanced/more/external_extensions.html)
- [MQTT-API](https://www.zigbee2mqtt.io/guide/usage/mqtt_topics_and_messages.html)
- [JavaScript-Freischaltung](https://www.zigbee2mqtt.io/guide/configuration/all-settings.html#enable_external_js)
- [Upload-Implementierung](https://github.com/Koenkk/zigbee2mqtt/blob/master/lib/extension/externalJS.ts)

Quellenprüfung: 27.10.2026. Bei neuen Installationen ab Z2M 2.11.0 sind externe
Erweiterungen zunächst deaktiviert; die Freischaltung benötigt einen Neustart.
Die unterstützte Z2M-Mindestversion wird erst nach Verifikation festgelegt.
YAML-Upload ist eine eigene Erweiterungsfunktion; der offizielle Erweiterungsupload
akzeptiert nur JavaScript-Dateien.

## Unabhängige Laufzeitdiagnose

`RuntimeDiagnostics` veröffentlicht auf ausdrücklichen zeitlich begrenzten
Auftrag nur bekannte Ereigniscodes und sicheren Ablaufkontext. `RuntimeDebug`
empfängt sie im eigenen Basistopic und nutzt den zentralen DebugFlowHelper.
Der eigene Meldungskatalog wird beim Build eingebettet und von PHP gelesen.
Aufzeichnungssteuerung liegt außerhalb von Speicher-/Aktivierungsworkflows;
begrenzte Warteschlange und Publish-Timeout entkoppeln sie von der Runtime.
Mesh erhält lediglich geprüften zusätzlichen Ablaufkontext, keine neuen Rechte.
Siehe [ADR 0012](adr/0012-timed-runtime-diagnostics.md).

## Standortbezogene Sonnenzeiten

AutomationEditorSolar ergänzt die Zeitfelder um Ereignis, Standort und Versatz.
Schema 1 erhält einen separaten sun-Auslöser; keine Umdeutung fester Uhrzeiten.
Die reine eigene solar.mjs-Berechnung wird in das Einzeldatei-Artefakt eingebettet.
Bei Fälligkeit verwendet der begrenzte Tagesplan den bestehenden Start-,
Bedingungs- und Aktionspfad. Kein zusätzlicher Standortdienst und keine
Koordinaten im Debug. Siehe [ADR 0013](adr/0013-local-solar-triggers.md).

## Einzelne gespeicherte Automationen ausführen

RuleExecution und ExecutionContract ergänzen die bestehenden korrelierten
Workflows um einmaligen Start, gezielten Stopp und begrenzte Laufzustände.
Die bestehende Z2M-Engine besitzt manuelle und automatische Läufe gemeinsam;
Bedingungen, Aktionspfade und Limits bleiben an dieser Stelle. Keine
YAML-Änderung und kein zweiter Runtime-Besitzer. Einzellaufsteuerung ändert
weder Automatikbetrieb noch Wiederanlaufwunsch. Siehe ADR 0014.
