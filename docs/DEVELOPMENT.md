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
