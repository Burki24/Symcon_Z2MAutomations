# Debug und MQTT-Diagnose

## Systemgebundene Gerätedatenaktualisierung

RequestWorkspaceDeviceCatalog, GetWorkspaceDeviceCatalogStatus und der Timer
RefreshWorkspaceDeviceCatalogs verwenden TraceModuleCall und den kanonischen
DebugFlowHelper. Meldungen erklären Verbindung prüfen, frischen Katalog
anfordern, Daten samt nativen Variablenzuordnungen übernehmen und den Abschluss.
Ablehnungen nennen Systembezug und einen festen Fehlercode. Kataloginhalte,
Namen, Transaktionen, Entwürfe und Browserzugänge werden nicht protokolliert.

Wiederholungsquittungen bestätigen nur den bestehenden Auftrag. GET-Status und
Designeröffnung senden keine neue MQTT-Anfrage. Nur der ausdrücklich gestartete
Ablauf darf nach seiner passenden Health-Antwort den Geräteabruf auslösen.
Die letzte Katalogübernahme erfolgt über RefreshWorkspaceCatalog und erklärt
separat, dass dieser lokale Schritt keine weitere MQTT-Abfrage versendet.
Modulupdate verwirft die flüchtigen Aufträge; bestehende Entwürfe und Kataloge
bleiben erhalten. Es gibt keinen automatischen Wiederanlauf eines Ladeauftrags.

## Browser-Regelverwaltung

Die feste POST-manage-Route erklärt Kopieren, Löschen und Statusfilter mit dem
kanonischen DebugFlowHelper des Hook-Aufrufs. Kopien sind ausgeschaltet; nur
lokale Entwürfe werden über CommitEditorChange übernommen. Die Meldungen nennen
Aktion, Anzahl der Regeln/Folgen und den Schutz noch verwendeter Aktionsfolgen.
Namen abhängiger Regeln werden nur im geschützten Browserdialog angezeigt,
nicht protokolliert. Ablehnungen durch fehlenden Zugang, veralteten Kontext,
offene native Eingaben oder Regelgrenzen erhalten Entwurf und Betrieb.
Wiederholungsquittungen verhindern ein zweites Kopieren oder Löschen. Der Filter
beschreibt ausdrücklich den lokalen Regelschalter. Keine MQTT-Ausgabe.

## Parallele Aufrufe und Besitzerkonflikte

Ist die instanzeigene Eintrittssperre belegt, nennt die deutsche Fehlermeldung
den noch laufenden Vorgang und die nicht ausgeführte Aktion. Der Bezug enthält
Aufruf und Grund instance_busy. Es wird kein Besitzer ausgelesen oder als
fälschlich zuständige Fremdverwaltung genannt. Native Formulare und HTTP-409-
Antworten erklären denselben Fall; String-Rückgaben enthalten den Fehlercode,
Boolean-/Void-Verträge bleiben erhalten. Auch Timerdurchläufe werden nicht als
Besitzerwechsel fehlgedeutet. Kein angeforderter Callback, MQTT-Auftrag oder
automatischer Wiederholungsversand wird aus dieser Ablehnung gestartet.

Nur erlaubte statische Designerdateien warten weiterhin höchstens fünf Sekunden.
Ihr Fristablauf verwendet ebenfalls instance_busy. Nach erfolgreichem Warten
folgt unverändert die Besitzerprüfung. Tatsächlich fremde, beschädigte oder
gesperrte Besitzzuordnungen bleiben ownership_conflict; Debug nennt dabei die
vorhandene sichere Erklärung. Beide Gründe nutzen den kanonischen DebugFlowHelper
mit ausgeglichenen START-/ENDE-Meldungen. Tokens und Anfrageinhalte fehlen.

## Automatischer Formularneuaufbau der Zentrale

Formularaktualisierungen erklären zunächst ihre Vormerkung im laufenden Aufruf.
Nach Freigabe der Instanzsperre folgt die Übergabe an Symcon. Mehrere Vormerkungen
werden zusammengefasst; ein bereits laufender Formularaufbau liefert den aktuellen
Stand direkt. Besitzer- und Schreibschutz werden dafür nicht umgangen. Schlägt
nur die Anzeigeaktualisierung fehl, nennt Debug das erneute Öffnen als nächsten
Schritt. Der abgeschlossene Vorgang bleibt erhalten; keine automatische
Wiederholung, weitere Z2M-Anfrage oder Ausgabe beliebiger Exception-Texte.

## Browserstart

Der Browser lädt HTML, CSS und JavaScript parallel. Nur GET-Aufrufe aus der
festen Dateiliste dürfen nach einer belegten Besitzersperre höchstens fünf
Sekunden warten. Debug erklärt Beginn der Wartezeit, erfolgreiche Freigabe und
Fristablauf. Anschließend bleiben Besitzer- und Übernahmeprüfung unverändert.
Datenabfragen, Schreibaufträge, fremde Pfade und andere HTTP-Methoden erhalten
keine neue Wartefreigabe. Der kanonische DebugFlowHelper protokolliert weiterhin
START und ENDE. Browserfehler und Zugriffstoken werden nicht an Debug übergeben.
Der kleine Startloader meldet einen fehlgeschlagenen Modulimport verständlich
und bietet ein ausdrückliches Neuladen statt einer automatischen Wiederholung.

## Sicherung und Besitzerwechsel

Die Übernahmevorschau erklärt, ob sie den Bestand dieser Instanz direkt liest
oder eine andere ausgewählte Instanz abfragt. Die direkte Eigenprüfung bleibt
im selben kanonischen Debug-Vorgang; beide öffentlichen Aufrufe haben START und
ENDE. Eine andere Instanz wird weiterhin über ihre geschützte Modulschnittstelle
gelesen. Eine tatsächlich belegte Zugriffssperre wird nicht umgangen.

Sicherungs-, Vorbereitungs-, Übernahme- und Rückwegaufrufe beider Module verwenden
TraceModuleCall und den kanonischen DebugFlowHelper. Deutsche Meldungen erklären
geprüfte private Sicherung, blockierte offene Eingaben, veränderte Quellen,
Quellensperre, exklusive Verbindungszuordnung, vollständige Zielveröffentlichung
und Wiederaufnahme nach einem Abbruch. Beim Rückweg wird die zusätzliche Sicherung
zentraler Änderungen vor der Quellenfreigabe sichtbar gemeldet. Ein alter Editor
nennt bei Ablehnung die zuständige Verwaltungsinstanz.

Es werden nur Instanzbezüge, Anzahl der Quellen/Systeme, feste Fehlercodes und
Journalphasen ausgegeben. Sicherungsinhalte, Dateiinhalte, Operationskennungen,
Tokens, Formulareingaben und beliebige Exception-Texte gehen nicht an den Debug.
Der private Kernel-Rohdatenexport hat keinen Browserzugang; sein Funktionsende
meldet nur den Textumfang. Die Meldung „Z2M wurde nicht verändert“ bezieht sich
auf den lokalen Ablauf und bestätigt keinen aktuellen Gerätebetrieb.

## Zentraler lokaler Arbeitsbereich

Alle sieben Workspace-Einstiege nutzen TraceModuleCall und den unveränderten
DebugFlowHelper. Meldungen erklären Systemzuordnung, getrennte lokale Übernahme,
erhaltene offene Eingaben, geschützte Freigaben, aktuelle Schaltrechte und
abgewiesene veraltete Kontexte. Wiederholungen melden „Bereits abgeschlossenen
lokalen Auftrag erneut bestätigt; keine Daten ein zweites Mal übernommen.“
Bezüge enthalten nur System-UUIDs, Zähler, Nutzungsart und sichere Fehlercodes.
Weder Operationskennungen, Fingerabdrücke, Namen, Eingabewerte noch Roh-YAML
werden protokolliert. Das Funktionsende beschreibt JSON nur nach Umfang.

Katalogübernahme benennt ausdrücklich den lokalen Snapshot und die ausgebliebene
MQTT-Anfrage. Bindings werden bei jeder Geräteauswahl erneut geprüft; ein
Rechteverlust löscht keine Regel. Fehler nennen einen verständlichen nächsten
Schritt und verwerfen keine gespeicherten Entwürfe. Diese Meldungen bestätigen
keine Speicherung oder Freigabe im laufenden Z2M. Es gibt in dieser Stufe keinen
Workspace-Befehl zum Schalten, Speichern, Aktivieren oder Stoppen in Z2M.

## Z2M-Verbindungsmodul

Z2MConnection verwendet den unveränderten kanonischen DebugFlowHelper an allen
öffentlichen Einstiegen und den gemeinsamen MQTTTransport/ExtensionTransport.
Verbindungsaufträge melden Vorbereitung, tatsächliches Senden, passende Antwort,
Abschluss oder sicheren Fehlercode. Katalogmeldungen nennen nur Geräte-, Gruppen-
und Szenenzahlen. Kein Code, keine Rohkonfiguration, YAML oder Empfängersitzung.
Systembezug ist die erzeugte UUID, Transportbezug der native Parent und das
Basistopic; die tatsächlichen Nutzdaten bleiben geschützt. Fehler durch falsches
Topic, unpassende Transaktion, Retain und abgelaufene Fristen bestätigen nichts.

Der gemeinsame MQTT-Transport berichtigt „Erweiterung installiert“, wenn ein
bestätigter bereiter Empfänger vorliegt. Er meldet einmalig „Bestätigter
Empfängerstatus belegt die installierte Z2M-Erweiterung; Installationsanzeige
berichtigt.“ Das gilt auch für bereits bestätigten lokalen Zustand nach einem
Modulupdate und sendet keine weitere Anfrage. Reine Statusbeobachtungen ohne
passende Antwort reichen nicht aus. Entfernung und Zuordnungswechsel verwerfen
weiterhin die zugehörige Bestätigung. Die lokale Statusabfrage liefert denselben
berichtigten Zustand wie Cache und Anzeigevariablen.

FinishHealthCheck erklärt Erfolg, gemeldetes Problem, fehlende Antwort, Abbruch
und Fehler in festen deutschen Meldungen. Technische Ergebniswerte wie healthy
bleiben im Protokoll erhalten; die Statusvariable verwendet die Modulübersetzung,
bei Deutsch insbesondere „Funktionsfähig“. Keine neuen Diagnoseanfragen.

Die zeitlich begrenzte Ablaufaufzeichnung nutzt denselben RuntimeDebug-Vertrag
wie bisher und besitzt je Verbindung eine eigene Lease. Bei mehreren Systemen
deren Debugfenster getrennt öffnen; Regellauf und Aktionsschritt dienen weiterhin
der systemübergreifenden Zuordnung. Identisches ApplyChanges und Formularöffnen
behalten die ursprüngliche Endzeit. Verbindung-/Sitzungswechsel beendet die Lease.
Ein Update oder Katalogabruf aktiviert keine Automation.

## Lesende Mehrsystem-Vorschau

GetSystemMigrationSource und PreviewSystemMigration verwenden TraceModuleCall
und damit den kanonischen DebugFlowHelper. Die Quellen werden einzeln und ohne
übergreifende Transportsperre gelesen. Meldungen erklären Nur-Leseprüfung,
Quellanzahl, Regel-/Folgenanzahl und konkrete Konflikte wie offene Eingaben,
abweichende YAML oder doppelte Topics. Als Bezug erscheinen nur die ausgewählte
Quellinstanz und sichere Zählwerte; keine Namen, Topics, Roh-YAML, Arbeitskopien,
Auswahl-/Empfängertoken oder vollständigen Antworten. Ergebnisse werden durch den
Helper lediglich als geschützter Text mit Byteanzahl beschrieben. Es werden
keine Empfängeranfragen, Statusaktualisierungen oder Migrationen ausgelöst.

## Automationsdesigner

Bei POST-create nennt der Erfolg die Auslöserart Geräteereignis, Uhrzeit oder
Sonnenzeit und die tatsächliche Zahl der Auslöser. Für Sonnenzeiten kommen
Sonnenaufgang/Sonnenuntergang und der ganzzahlige ZeitversatzMinuten hinzu:
negative Werte bedeuten früher, positive später. Koordinaten, Uhrzeiten,
Zeitzonen, Namen, Zielwerte und Zugriff bleiben geschützt. Fehler bei Uhrzeiten,
Standort oder Versatz nennen einen sicheren Hinweis; der Entwurf bleibt erhalten.
Die bloße Auswahl einer Art oder das Tippen sendet keine Debug-Anfrage.

POST-edit verwendet ebenfalls ProcessHookData und DebugFlowHelper. Erfolg
meldet „Browserbearbeitung im lokalen Entwurf übernommen“ mit Regelposition,
Eintragszahlen und Regelschalter. Eine inhaltlich unveränderte Übernahme sowie
ein wiederholter Auftrag werden gesondert erklärt. Konflikte, fehlende Rechte
und ungültige Angaben nennen einen sicheren deutschen Hinweis und HTTP-Status.
Namen, Schaltwerte, genaue Sonnenkoordinaten, Topics und Zugang bleiben geschützt.
Im Browser erfolgen beim bloßen Auswählen oder Tippen keine Debug-Schreibaufrufe.

Bei lokaler Entwurfsbearbeitung ohne verfügbares Partnermodul meldet
SaveEditorDraft: „Vorhandene Partnerfreigaben bleiben im lokalen Entwurf
erhalten; Z2M wurde nicht verändert.“ Der Bezug nennt nur die Anzahl der
Freigaben. Topicnamen und Entwurfsinhalt werden nicht protokolliert. Eine
verfügbare ausdrückliche Partnerauswahl behält den bisherigen Synchronisierungspfad.

Beim Laden gespeicherter Regeln bestätigt der Debug die vollständige lokale
Übernahme einschließlich Partnerfreigaben und unverändertem YAML-Text. Er nennt
nur Regelzahl, Revision und Anzahl der Freigaben. Das Laden synchronisiert keine
lokale Partnerauswahl und verändert weder Speicherung noch Ausführung in Z2M.

Die Inhaltsprüfung berücksichtigt HTTP_CONTENT_TYPE von Symcon und CONTENT_TYPE
von üblichen PHP-Testservern. Nur application/json ist erlaubt, einschließlich
einer optionalen Zeichensatzangabe. Widersprüchliche oder fremde Medientypen
bleiben abgewiesen. SymconHeaderVorhanden, StandardHeaderVorhanden und JSONErkannt
sind boolesche Diagnoseangaben; weder Headerwerte noch Anfrageinhalt werden
ausgegeben. Eine spätere Ablehnung nennt weiterhin den vorhandenen HTTP-Status.

Der neue POST-create-Auftrag bleibt im bestehenden ProcessHookData-Debugfluss.
Ablehnungen nennen einen sicheren deutschen Hinweis und HTTP-Status; Erfolg
nennt Regel-/Schrittzahlen und die ausschließlich lokale Entwurfsänderung.
Wiederholte Aufträge melden ausdrücklich, dass keine zweite Regel angelegt wurde.
Der gemeinsame CommitEditorChange protokolliert die native Entwurfsübernahme.
Browserauswahlen senden keine zusätzlichen Anfragen nur für Debugzwecke.
Namen, Gerätekennungen, Schaltwerte, Anfragekörper, Zugang und Herkunftsheader
werden nicht in diese Meldungen kopiert. Der Prüfzugang bleibt DebugFlowHelper.

RefreshDeviceCatalog protokolliert den reinen Geräteabruf, korrelierte Phasen,
Erfolg mit Geräte-/Gruppen-/Szenenzahlen sowie Ablehnungen. Meldungen erklären,
dass Entwürfe, offene Eingaben und Betrieb erhalten bleiben. Der erfolgreiche
Zeitstempel wird erst mit dem vollständig geprüften Katalog gespeichert;
Fehler aktualisieren ihn nicht. Keine Gerätenamen oder Nutzdaten im Debug.

OpenAutomationDesigner, GetDesignerData und ProcessHookData verwenden den
bestehenden DebugFlowHelper mit Start und Ende. Deutsche Ablaufmeldungen
erklären den befristeten Zugang, Mengen vorhandener Geräte/Regeln/Folgen,
fehlende Entwurfsdaten und beantwortete HTTP-Abrufe. Ablehnungen nennen den
HTTP-Status. Weder Zugang, Link, HTTP-Header, Regelnamen noch Schaltwerte oder
Entwurfsinhalte werden protokolliert. Auch Aktualisieren verändert weder
Entwurf noch Z2M. Es startet keine MQTT-Aufzeichnung oder Statusabfrage.

Während einer Modulinitialisierung eingehende MQTT-Nachrichten werden bis zur
Registrierung der Einstellungen mit einer erklärenden Ablaufmeldung verworfen,
statt nicht vorhandene Properties zu lesen. ApplyChanges setzt danach wieder
den vorhandenen Topicfilter und die verzögerte Statusprüfung.

## Startbedingungen

InspectEditorConditions und SaveScheduledAutomation nennen zusätzlich die
Anzahl „NurBeimStart“. Die Runtime protokolliert mit Regel-/Lauf-/Bedingungsbezug,
wenn eine nur für den Start vorgesehene Bedingung im laufenden Ablauf nicht
erneut geprüft wird. Neue Starts und Neustarts verwenden die bisherige
Bedingungsprüfung. Werte, Namen und Gerätereferenzen werden nicht zusätzlich
ausgegeben. Der feste MQTT-Debugkatalog und DebugFlowHelper bleiben der
vorhandene Übertragungs- und Bereinigungspfad.

## Z2M-Abläufe im Debugfenster verfolgen

Unter „Einrichtung und Diagnose“ das Debugfenster dieser Modulinstanz öffnen
und dessen Aufzeichnung starten. Danach im Formular „Z2M-Aufzeichnung starten“
wählen. Die Dauer beträgt wahlweise 5, 10 oder 15 Minuten. Der Formulartext
bestätigt den Start erst nach der Antwort von Z2M. Erscheint keine Bestätigung,
den Erweiterungsstatus abfragen und gegebenenfalls die mitgelieferte Erweiterung
aktualisieren. Der allgemeine Z2M-Protokolllevel muss dafür nicht geändert werden.

Die Meldungen erklären Auslöser, erfüllte oder nicht erfüllte Bedingungen,
Mindestdauer, Wartezeiten, Aktionsschritte und Abbruchgründe. Regelnummer und
Revision bezeichnen die Position in den gespeicherten Automationen; ein
„Regellauf“ verbindet zusammengehörige Meldungen. Die Annahme eines Befehls
bestätigt seine Übergabe an Z2M, nicht die Wirkung am Gerät.

Bei einer Aktion zwischen zwei Z2M-Systemen die Aufzeichnung in beiden
Automationsmodulen starten. Derselbe Regellauf und Aktionsschritt erscheinen
auf beiden Seiten. Systembezug und Ursprungssystem erlauben die Zuordnung
auch bei gleichlautenden Regelnummern. Es werden keine Gerätenamen, Messwerte,
Schaltwerte, YAML oder Zugangsdaten in diese Ablaufmeldungen aufgenommen.

„Eigene Z2M-Aufzeichnung beenden“ beendet nur die Aufzeichnung dieser Instanz.
Das Speichern von Automationen unterbricht eine laufende Aufzeichnung nicht,
solange MQTT-Verbindung und Z2M-Sitzung gleich bleiben. Ihre gewählte Endzeit
bleibt unverändert. Auch während der anschließenden Statusbestätigung kann
die eigene Aufzeichnung beendet werden. Ein neuer Start benötigt weiterhin
einen bestätigten Erweiterungsstatus.

Andere Symcon-Installationen können unabhängig aufzeichnen. Nach Ablauf der
gewählten Zeit endet die Übertragung auch ohne laufendes Symcon. Nach
Einstellungsübernahme, Verbindungswechsel oder Z2M-Neustart bei Bedarf erneut
starten. Automationen werden dadurch weder gestartet noch gestoppt. Bei
Überlast oder Übertragungsfehlern weist Debug auf Lücken hin; ausgefallene
Ablaufmeldungen werden nicht nachträglich wiederholt.

Programmierbare Aufrufe: `Z2MA_StartRuntimeDebug(InstanceID, Seconds)` mit
1–900 Sekunden, `Z2MA_StopRuntimeDebug(InstanceID)`; der Timer-Einstieg
`Z2MA_ExpireRuntimeDebug(InstanceID)` wird normalerweise automatisch verwendet.

## Gemeinsame Gerätebefehle und Farben

GetEditorItemForm, ConfigureEditorItem, ConfigureSequenceCall und
ChangeEditorCommand verwenden die vorhandenen zentralen Start-/Ende-Abläufe.
Feste deutsche Meldungen erklären Vorbelegung, gemeinsame Befehlsauswahl,
Hinzufügen/Ersetzen/Entfernen, ungültige Werte und die rein lokale Änderung.
Sie nennen die Anzahl der Eigenschaften und die gebundene Symcon-Variablen-ID.
Weder Farbcodes noch Werte, kodierte Choice-Inhalte, Geräte- oder Folgennamen
werden ausgegeben. Jede Übernahme prüft erneut die Schaltrechte aller Werte;
ein verlorenes Recht verwirft den ganzen Schritt statt eines Teilbefehls.

DebugEditorActionSteps nennt zusätzlich die Eigenschaftsanzahl eines Schritts.
Die Ausführung bleibt eine einzelne Übergabe über den vorhandenen lokalen oder
systemübergreifenden Lauf. Regel-/Lauf-/Schrittzuordnung und zeitlich begrenzter
MQTT-Debugkanal bleiben unverändert; kein automatischer Aufzeichnungsstart.

## Erneute Auslösung

SaveScheduledAutomation erklärt die übernommene Erneuerungsart mit festen
deutschen Texten, ohne Regelinhalt oder Namen. Die neuen Formularfelder werden
wie bisher über den unveränderten DebugFlowHelper im öffentlichen Einstieg
und durch EditorWorkInput erfasst.

Die Runtime meldet Ignorieren, abgelehnte Bedingungen, fehlende Wartezeit,
erneuerte Wartezeit, vorgemerkten Neustart und dessen Abbruch bei unsicherer
Übergabe. Erneuern behält den Lauf; Neustart erhält eine neue Laufkennung.
Beide gehören weiter zu derselben Regel und Quellrevision. Der feste gemeinsame
Katalog macht diese Meldungen auch in Symcon sichtbar, wenn die vorhandene
zeitlich begrenzte Aufzeichnung läuft. Keine neue Freigabe oder Aufzeichnung
allein durch die Einstellung. Der bestehende Diagnosekanal bleibt begrenzt.

## Uhrzeiten und unabhängige Wochentage

ConfigureEditorWeekdays und InspectEditorTimes verwenden den kanonischen
DebugFlowHelper wie die vorhandene SaveScheduledAutomation. Deutsche Meldungen
erklären sichtbare Wochentagsfelder, Anzahl der Uhrzeiten, noch nicht übernommene
Listenänderungen, ungültige Angaben und die rein lokale Übernahme. Ein Löschen
nennt ausdrücklich die Anzahl vor dem Löschen. Native Prüfungen und Übernahme
ändern keine Z2M-Laufzeit. Zeiten, Zeitzonen, Namen und komplette Formularzeilen
werden nicht ins Debug geschrieben.

Die Erweiterung meldet „Kalendertag gegen erlaubte Wochentage geprüft.“ über
den bestehenden begrenzten MQTT-Debugkanal. Die Meldung enthält Regel-, Lauf-
und Bedingungsbezug sowie das boolesche Prüfergebnis. Sie gilt ebenso beim
Einzelstart und bei der erneuten Prüfung vor einem verzögerten Schritt. Keine
neue Aufzeichnung oder zusätzliche Statusabfrage allein für Diagnose.

## Automationsübersicht und Kopieren

FilterAutomations und CopyAutomation verwenden die vorhandenen zentralen
Start-/Ende-Abläufe. Filtermeldungen nennen Bereich, gesetzte Suche als Ja/Nein,
Regelschalterfilter und Trefferzahl. Suchtext, Geräte- und Automationsnamen
werden nicht übergeben. Kopiermeldungen erklären die rein lokale Änderung,
ausgeschalteten Regelschalter, zurückgesetzten Bereichsfilter sowie Anzahl der
Regeln, Auslöser, Bedingungen und Aktionen. Kennungen und Regelinhalte bleiben
geschützt. Abgelehnte Aufrufe nennen ungültige Filter, offene Eingaben,
überholte Auswahl oder die Grenze von 100 Regeln. Kein zusätzlicher MQTT-Versand.

## Formulareingaben und Entwurf unterscheiden

Abgelehnte Wechselbestätigungen nennen nun einen festen deutschen Grund:
fehlender oder überholter Bestätigungswert, andere Auswahl, geänderter Entwurf
oder geänderte Formulareingaben. Bei einer abweichenden Auswahlbindung werden
nur Vorhandensein und Übereinstimmung als Ja/Nein-Werte protokolliert.
Keine Auswahl- oder Bestätigungstokenwerte und keine Formularnamen oder -werte.
Die native Praxisprüfung bestätigt Meldungen für den angehaltenen Wechsel,
ausdrückliches Verwerfen, Prüfungsschutz und die rein lokale Entwurfsübernahme.

TrackEditorInput, NavigateEditor, ResolveEditorNavigation und EditorWorkReady
verwenden den kanonischen DebugFlowHelper. Feste deutsche Meldungen erklären
neue offene Eingaben, die Rückkehr zum Entwurfsstand, den angehaltenen Wechsel,
das ausdrücklich bestätigte Verwerfen und abgelehnte veraltete Bestätigungen.
Der Bezug benennt nur Automation oder Aktionsfolge. Weder Eingabewerte, Namen,
Auswahltoken noch der Formularschnappschuss gelangen in Debug-Ausgaben.
Eine normale Statusabfrage verändert den Bearbeitungsstand nicht. Die drei
Formularanzeigen erklären Eingaben, Entwurfsstand und tatsächlichen Betrieb.

## Rückmeldung der manuellen Verbindungsprüfung

CheckConnection verwendet den bestehenden DebugFlowHelper und denselben
Health-Transport wie CheckHealth. Der Anfragezustand merkt sich ausschließlich
für eine ausdrücklich angeforderte manuelle Rückmeldung ein notify-Kennzeichen.
Die passende Antwort, Timeout, Transportfehler oder Kontextabbruch beendet
diese Anfrage. Die Rückmeldung protokolliert Ergebnis und Erfolgskennzeichen
mit festem deutschem Text. Keine fremden Fehlertexte oder Nutzdaten im Popup
oder Debug. Ein Darstellungsfehler verändert das tatsächliche Prüfergebnis
nicht. Automatische Prüfungen und die bisherige CheckHealth-API bleiben still.

## Erweiterungsstand vergleichen

Eine geänderte Anzeige erklärt mit festem deutschem Text, ob die Erweiterung
aktuell ist, abweicht, keine Kennung meldet oder noch nicht bestätigt ist. Bei
unlesbarer mitgelieferter Buildkennung folgt ein Fehlerhinweis zu den Moduldateien.
Der Bezug enthält Vergleichszustand, Bestätigung und kurze Buildkennungen.
Keine fremden Texte, Codeinhalte, YAML, Freigaben oder Gerätebezüge. Unveränderte
Ergebnisse werden nicht erneut protokolliert. Die vorhandenen öffentlichen
Status- und Formulareinstiege behalten ihre zentralen Start-/Ende-Abläufe.

## Wiederverwendbare Aktionsfolgen

Auch der Browserpfad verwendet den vorhandenen DebugFlowHelper über den
Modul-Hook und CommitEditorChange. Er meldet neue oder bearbeitete Definition,
unveränderte Bestätigung, wiederholten Auftrag sowie abgewiesene Eingaben oder
Kontextkonflikte mit deutschen festen Texten. Bei Erfolg nennt der Bezug nur
Neuanlage, Schrittzahl und Anzahl verwendender Automationen. Die Meldung
bestätigt ausdrücklich den lokalen Entwurf und den Erhalt der Regeln und
Partnerfreigaben. Keine Namen, Kanäle, Werte, Definitionen oder Zugangstoken.

Auswahl, Übernahme, Schrittbearbeitung und Löschsperre verwenden die zentralen
Start-/Ende-Abläufe und feste deutsche Fachmeldungen. Protokolliert werden
Schritt- und Verwendungsanzahl sowie das Ergebnis der Berechtigungsprüfung.
Die Runtime meldet den Beginn einer enthaltenen Folge; deren Schritte behalten
denselben Regellauf. Optional ergänzen `Aktionsfolge` und `SchrittInAktionsfolge`
die aufgelöste Schrittposition. Diese numerischen Bezüge werden auch auf dem
Mesh-Ziel angezeigt. Namen, Definitionen, Auswahlbindungen und Aktionswerte
bleiben geschützt. Bestehende Zeitbegrenzung und Versandgrenzen gelten weiter.

## Eigener zeitlich begrenzter MQTT-Debugkanal

`request/debug` startet eine eigene flüchtige Aufzeichnungsfreigabe; ausschließlich
frische, passende Antworten bestätigen sie. Die Ablaufmeldungen kommen über
`<Basistopic>/symcon/automations/debug` und werden mit dem unveränderten zentralen
DebugFlowHelper in den nativen Instanzdebug geschrieben. Kein `bridge/logging`,
kein globaler Z2M-Loglevel und kein Rohdatenmitschnitt. Z2M und PHP verwenden
einen gemeinsamen festen Meldungskatalog. Fremde Texte, unbekannte Felder,
retained Meldungen, alte Sitzungen und doppelte Datensätze werden verworfen.

Der lokale kanonische `Vorgang`/`Aufruf` bleibt erhalten. `Regellauf` bezeichnet
den zusätzlichen Z2M-Lauf; `Z2MPhase` beschreibt dessen Start/Ende als fachliche
Meldung innerhalb des lokalen Empfangsaufrufs. Regel-, Auslöser-, Bedingungs-
und Aktionsnummern sind einsbasiert. Bei Remote-Aktionen gelten Regelnummer und
Revision für das Ursprungssystem. `Systembezug` und `Ursprungssystem` sind die
ersten 16 Hexzeichen von SHA-256 des jeweiligen Basistopics. Der Ablaufbezug
wird in der bestehenden Einmalfreigabe weitergegeben, ohne deren Rechte zu erweitern.
Die empfangende Erweiterung übernimmt keine frei formulierten Debugtexte.

Eine Freigabe gilt höchstens 900 Sekunden. Maximal acht getrennte Clients
können aufzeichnen; Beenden betrifft nur den eigenen Client. Keine Persistenz,
kein automatischer Wiederanlauf der Aufzeichnung, keine Speicherung im YAML.
Der native Ablauf nennt Bestätigung, Zeitablauf, Abbruch und Übertragungsfehler.
Die Bindung einer laufenden Freigabe hängt an MQTT-Kontext und Z2M-Sitzung,
nicht an der Bestätigung des gerade gespeicherten Regelstands. Speicherung
und anschließende Statusprüfung erhalten Client und ursprüngliche Endzeit;
der eigene Stopp bleibt möglich. Ein neuer Start oder eine Verlängerung
benötigen weiterhin den bestätigten Erweiterungsstatus. Der Speicherablauf
meldet den Erhalt einer laufenden Aufzeichnung ausdrücklich im Debug.
Bei fehlender Symcon-Verbindung läuft nur die ursprüngliche begrenzte Frist aus;
es werden keine Meldungen für eine spätere Zustellung gespeichert.

Die Erweiterung begrenzt auf 80 neue Meldungen pro gleitender Sekunde und
128 wartende Meldungen. Nur ein Publish wird gleichzeitig abgewartet, maximal
zwei Sekunden; Automation und Mesh warten nie darauf. Fehler verwerfen den
Rückstau ohne Wiederholung. `dropped` meldet bekannte Verluste mit der nächsten
übertragenen Meldung, begrenzt auf eine Million. QoS 0 kann weitere unbemerkte
Verluste haben; ohne folgende Meldung kann auch der Lückenhinweis ausbleiben.
PHP begrenzt ebenfalls auf 80 angenommene Datensätze pro Sekunde und hält
höchstens 128 IDs zur Duplikaterkennung. Der Empfang prüft maximal 8 KiB Payload
und eine Zeitabweichung von höchstens 120 Sekunden; beide Z2M-Rechner und
Symcon benötigen eine passende Uhrzeit. Ungültige/überzählige Meldungen werden
höchstens einmal pro Sekunde als Lücke gemeldet. Kein Roh-Payload oder
Exception-Text geht in die Ausgabe. Aktualisierung von Modul und Erweiterung
ist für die Einführung des Kanals erforderlich. Start, Zeitablauf, Stopp und
Erhalt beim Speichern einschließlich verzögerter Statusbestätigung wurden über
Test-Symcon praktisch geprüft. Die Korrektur für den Erhalt benötigt nur ein
Modulupdate. Der gemeinsame Laufbezug einer realen systemübergreifenden
Geräteaktion bleibt als gesonderter Praxistest offen.

## Szenenaufrufe

Der Katalogempfang nennt die Szenenanzahl. Aktionsschritte unterscheiden einen
vorbereiteten Szenenaufruf von gewöhnlichen Schaltaktionen und nennen Schritt,
Wartezeit und die Verwendung des Partnersystems. Z2M protokolliert die erneute
Szenenprüfung sowie die native Übergabe. Fehlende Szenen erhalten den festen
Code `scene_unavailable`, bei Entwurfsprüfung zusätzlich die genaue Regel-/Schrittposition.
Szenennamen, Szenenwerte, Geräteidentitäten und Metadaten werden nicht zusätzlich
in den Debug übernommen. Die Oberflächenauswahl zeigt Namen ausschließlich zur Bedienung.

## Status nach Speicherung

Debug unterscheidet die bestätigte Speicherung, die automatische Statusrückfrage
und den passend bestätigten vollständigen Laufzeitstatus. Er nennt Revision und
Ausführungszustand, ohne YAML, Prüfsummen oder Sitzungen zusätzlich auszugeben.
Ein Abbruch erklärt, dass die Speicherung bereits bestätigt wurde und nur der
Status fehlt: keine erneute Speicherung senden, sondern Erweiterungsstatus
abfragen. Die Rückfrage aktiviert keine Automation und sendet keine Schaltbefehle.

## Abgelehnte Entwurfsprüfung

Z2M und Symcon protokollieren für jeden der höchstens 20 Diagnoseeinträge eine
feste Ursache sowie die Position, etwa `/automations/11/actions/1/delay_seconds`.
Der native Symcon-Debug ergänzt einen übersetzten verständlichen Hinweis und
gegebenenfalls Grenze, Zeile und Spalte. Namen werden nur im Formular und der
zugehörigen Fehler-Statusvariable aus dem geprüften Klick-Editor-Entwurf ergänzt;
der Transportstatus-Export entfernt diese Namenszuordnung. Keine Rohantworten,
Ajv-/Parsertexte, YAML-Ausschnitte, dynamischen Wertschlüssel oder Schaltwerte
werden in den Debug übernommen. Statusabfragen protokollieren ausdrücklich den
Erhalt der Diagnose; eine neue Entwurfsprüfung meldet deren Ablösung. Erfolgreiche
andere Aufträge löschen die Diagnose nicht. Ein zusätzlicher Vorgangsfehler bleibt
neben dem Prüfungsbericht sichtbar, ohne die bereinigte Debug-Ausgabe zu erweitern.

## Automatischer Wiederanlauf

Die Aktivierung protokolliert die übertragene Option NachNeustartFortsetzen.
Statuswechsel melden die von Z2M bestätigte Freigabe, den erfolgreichen
Wiederanlauf oder einen festen Abbruchgrund. Die Erweiterung nennt Revision und
Regelanzahl nach erfolgreicher Wiederherstellung; YAML, Tokens, Aktionswerte und
Dateipfade erscheinen nicht im Debug. Ein Speicherfehler bestätigt keinen
permanenten Stopp und wird mit Hinweis auf den bisher gespeicherten Zustand angezeigt.

## Gateway- und Topic-Wechsel

Nach Übernahme oder Verbindungswechsel meldet die native Diagnose das tatsächliche
Gateway und die Anzahl neu ermittelter Topics. Sie erklärt, dass Topic und Entwurf
erhalten bleiben. Eine ausgesetzte automatische Prüfung nennt das nicht passende
gespeicherte Topic als Ursache und fordert Auswahl/Eingabe mit anschließender
Übernahme. Keine Topics oder Entwürfe werden dafür ungefragt umgeschrieben.

## Aktionen im zweiten Z2M-System

Die Katalogzuordnung meldet `GemeinsamesMQTTGateway` und die Zielanzahl. Ein
gemeinsamer MQTT-Client wird ausdrücklich erkannt; Gateway und Basistopic werden
gemeinsam geprüft. Gerätewerte, Namen und Transportkennungen bleiben geschützt.

`GetActionCatalog` verwendet den zentralen START-/ENDE-Ablauf und meldet die
Anzahl schaltbarer Ziele. Fehlende Partnerdaten werden mit dem nächsten
Bedienungsschritt erklärt. Variablenbindungen werden erneut geprüft; keine
Kataloginhalte, Gerätenamen oder Aktionswerte werden zusätzlich protokolliert.
Die Erweiterung meldet Freigabeanforderung, Empfang der einmalig freigegebenen
Aktion, native Übergabe und deren Bestätigung. Fehler nennen feste Codes und
keine Netzwerk-/Exception-Inhalte. Kennungen, Tokens und Fingerabdrücke werden
nicht geloggt. Bestätigte Übergabe und Gerätewirkung bleiben sprachlich getrennt.

## Wertänderung als Import-Auslöser

Die native Auslöserdiagnose zählt `NurBeiWertänderung`. Die Z2M-Runtime erklärt
passende, aber unveränderte Meldungen mit „Wert unverändert; Auslöser verlangt
eine Wertänderung“. Regelkennung und Auslösernummer bleiben nachvollziehbar;
Gerätewerte und Namen werden nicht zusätzlich protokolliert.


## Import-Gerätedaten laden

`LoadImportDevices` verwendet den kanonischen START-/ENDE-Ablauf. Deutsche
Meldungen erklären fehlende Bestätigung, Anfrage am eigenen Gateway/Topic,
Abschluss mit erneuter Importprüfung oder einen festen Fehlergrund. Die
Gerätenamen und Instanz-IDs aus der Übersicht bleiben außerhalb dieser zusätzlichen
Ausgaben. Das Absenden einer Anfrage wird nicht als Importfreigabe ausgegeben.


## Suchbereich der Ereignissuche

Die Suche nennt eigenes Gateway, konfiguriertes Basistopic, Anzahl geprüfter
Ereignisse und zugeordneter Quellen. Der Formularaufbau meldet die Anzahl lokal
gefundener Topics am eigenen Gateway. Fremde Variablen werden vor dem Lesen von
Instanzkonfigurationen ausgeschlossen; ihre Namen und Inhalte werden nicht geloggt.


## Ereignisimport

`ScanEventImports`, `PreviewEventImport` und `ImportSymconEvent` verwenden den
kanonischen START-/ENDE-Ablauf. Deutsche Meldungen nennen geprüfte bzw.
zugeordnete Anzahlen, die Importfähigkeit und feste Ablehnungsgründe. Ereignisnamen,
Auslösewerte, Skriptinhalte und Vorschautokens werden nicht protokolliert.
Die Übernahme bestätigt ausdrücklich die deaktivierte lokale Regel und die
unveränderten Originale. Der Katalog-Debug zählt zusätzlich bestätigte Gruppen.
Die Z2M-Laufzeit meldet, wenn ein Umschaltziel aus dem Cache ermittelt wurde;
`state_unavailable` erklärt fehlende Zustände ohne Gerätedaten im Protokoll.


## Automatische Statusprüfung

`RefreshStatus` ist der öffentliche Timer-Einstieg mit nativem Debug-Ablauf.
Die deutschen Ausgaben erklären Anlass, Zusammenfassung bzw. Auslassen,
Start der Health-Anfrage und die anschließende Erweiterungsabfrage.
Transportfehler und Antwortzeitüberschreitungen nutzen die vorhandene Diagnose.
YAML-Inhalte und Transaktionskennungen werden dabei nicht zusätzlich ausgegeben.

Beim Laden des Gerätekatalogs meldet `Kanalzustände` die Anzahl expliziter
Schaltkanal-Eigenschaften über die berücksichtigten Geräte. Mehrfaches Auftreten
als Auslöser und Aktion wird je Gerät nur einmal gezählt. Namen, IEEE-Adressen
und Kanalinhalte werden nicht an die Debug-Ausgabe übergeben. Die Regelauswahl
bestätigt weiterhin `FelderVorbelegt`, ohne die Regel zu protokollieren.

`InspectEditorTriggers` meldet Hinzufügen/Bearbeiten/Löschen der ODER-Auslöserliste.
Beim Löschen beschreibt `AuslöserVorLöschen` nur den nativen Vorbestand. Ungültige
Listen erhalten einen festen deutschen Hinweis. SaveScheduledAutomation nennt
Auslöseranzahl und ODER-Verknüpfung. Die öffentlichen Abläufe verwenden zentrale
START-/ENDE-Ausgaben; Gerätenamen, Auswahlwerte und Listeninhalte bleiben geschützt.

Regelauswahl meldet `FelderVorbelegt` und `EmpfängerBestätigt` getrennt. Ein
gültiger lokaler Entwurf kann nach Verlust der flüchtigen Z2M-Bestätigung
weiter zur Bearbeitungsansicht geladen werden. Änderungen bleiben bis zur
Bestätigung gesperrt. Namen, Uhrzeiten und Bedingungswerte werden nicht geloggt.

`OpenAutomationEditor` erklärt das Öffnen bzw. Zuklappen des aktiven Bereichs
und erhält dabei dessen Eingaben. Ein Wechsel delegiert an die instrumentierte
Regelauswahl. Falsche Bereiche werden sicher abgewiesen; Debug nennt nur feste
Bereichsnamen. Der öffentliche Einstieg besitzt zentrale START-/ENDE-Ausgaben.

Die direkt eingebetteten Geräte-/Zeitbearbeitungen nennen bei Geräteauswahl,
Aktionslisten und Entwurfsübernahme ihren festen deutschen Automationsbereich.
Optionale Bereichsparameter schützen Formularaktionen vor verspäteten Aufrufen
aus einem inzwischen verlassenen Bereich; diese werden als FEHLER erklärt und
ändern weder den Entwurf noch Z2M. Öffentliche Einstiege verwenden unverändert
den zentralen Start-/Ende-Ablauf. Namen, Gerätekennungen und Regeln bleiben geschützt.

Der kompatible Aufruf `ShowYamlEditor` klappt jetzt den YAML-Bereich auf oder zu
und protokolliert diesen Zustand. Der Editor selbst bleibt innerhalb des Bereichs
verfügbar. Formularaktionen prüfen den aktuellen YAML-Inhalt unabhängig vom
Aufklappzustand; manuelle Änderungen werden beim grafischen Bearbeiten geschützt.

`SelectAutomation` protokolliert beim Öffnen der Bearbeitung den festen deutschen
Bereich „Geräteausgelöst“, „Zeitgesteuert“ oder „Weitere Auslöser“ sowie die
Unterscheidung zwischen neuer und bestehender Regel. `ConfigureEditorSchedule`
nennt den gewählten Bereich und die optionalen Bedingungsarten. Beide verwenden
den zentralen Start-/Ende-Ablauf; Auswahl, Namen, Zeitwerte und Regeln bleiben
geschützt. Bereichswechsel bearbeiten nur den lokalen Editor und senden nichts
an Z2M. Ungültige Bereiche und veraltete Entwürfe werden nachvollziehbar abgelehnt.

`InspectEditorActions` erklärt Hinzufügen, Bearbeiten, Löschen und Umordnen der
Aktionsliste. Beim nativen Löschen enthält der Callback noch den Vorbestand:
Die Meldung kennzeichnet deshalb ausschließlich `AktionenVorLöschen` und gibt
keine alten Schritte als verbleibende Folge aus. Erst beim Übernehmen wird die
verbleibende Liste geprüft und mit ihren Schritten protokolliert.
Andere gültige Listen melden Anzahl, Schrittnummer und Wartezeit in Sekunden;
unvollständige Listen erhalten einen festen deutschen Korrekturhinweis. Es werden
keine Gerätekennungen, Namen, Auswahlwerte oder Listeninhalte ausgegeben. Die
Formularliste wird erst durch `SaveScheduledAutomation` in den lokalen Entwurf
übernommen; auch dieser Ablauf meldet die vorbereiteten Schritte. Alle öffentlichen
Einstiege verwenden den zentralen DebugFlowHelper mit Start/Ende, auch bei Ablehnung.

`ConfigureEditorSchedule` protokolliert die gewählte Auslöser-/Bedingungsart und
das Ein-/Ausschalten des Zeitfensters. `SaveScheduledAutomation` protokolliert
Start/Ende, feste Ablehnungsgründe, Auslöserart und Anzahl der Bedingungen.
Regelnamen, Gerätekennungen, Zeitpläne, Vergleichswerte und Options-JSON bleiben
aus dem Debug ausgeschlossen. Die eigentliche Auswertung läuft unverändert in
Z2M und verwendet dessen bereits instrumentierte Engine.

Regelauswahl, Bearbeiten und Löschen (`SelectAutomation`, `UpdateAutomation`,
`DeleteAutomation`) verwenden ebenfalls zentrale Start-/Ende-Abläufe. Debug
enthält nur feste Fehlercodes, Änderungsart und Regelanzahl; keine Namen,
Geräte-IDs, Auswahltokens oder YAML. Die Offline-Integration prüft Ablehnungen
veralteter Auswahlen und den Erhalt erweiterter Regeln über den echten Empfänger.

Die JavaScript-Engine protokolliert Aktivierung, Stopp und Aktionsfolgen über den
nativen Z2M-Logger mit sicheren deutschen Meldungen. Die PHP-Helper gelten für
das Symcon-Modul, nicht als JavaScript-Laufzeitabhängigkeit. Einzelheiten und
die geprüften Geheimnisschutz-/Loggerfehlerfälle stehen in [RUNTIME.md](RUNTIME.md).

Im Symcon-Debugfenster die Instanz Z2MAutomations auswählen und anschließend
„Zigbee2MQTT-Verbindung prüfen“ ausführen. START/ENDE kennzeichnen öffentliche
Aufrufe, SENDEN die Übergabe an das Gateway, EMPFANG eingehende Modulnachrichten,
AKTION die Auswertung und FEHLER Ablehnungen oder Zeitlimits. Übergabe bedeutet
noch keine Bestätigung durch Z2M. Vorgang und Aufruf verbinden zusammengehörige
Meldungen; asynchrone Antworten erhalten einen eigenen Vorgang und werden über
einen gehashten Anfragebezug zugeordnet.

Die Kategorie enthält Modul, Ereignis, Funktion und Quellzeile. Die Nutzdaten
enthalten Instanz, Vorgang, Aufruf, Meldung und Bezug und sind auf 4096 Bytes
begrenzt. YAML, Erweiterungscode, gesamte MQTT-Bodies, Sitzungskennungen und
beliebige fremde Fehlermeldungen werden nicht ausgegeben. Der unveränderte
zentrale DebugFlowHelper (mit DebugHelper) besitzt den Ablauf und bereinigt
zusätzlich bekannte Geheimnisse. ModuleDiagnostics ist nur noch ein dünner
Adapter zur bestehenden Fachinstrumentierung, kein eigener Ablaufzähler. Ein defekter
Debugkanal darf den Datenfluss nicht verändern.

Aktivierung, Stopp, Editor-Laden, Geräteauswahl, Entwurfserzeugung und dessen
Prüfung/Speicherung besitzen START/ENDE über den zentralen Helper. Tokens und
Editor-/YAML-Inhalte werden vor der Debugschnittstelle ausgeschlossen. Die
PHP-/JavaScript-Integration prüft Erfolg, Ablehnung, Timeout, verschachtelte
Aufrufe, Geheimnisschutz und einen absichtlich defekten Debugkanal.

MQTT Client und MQTT Server werden unterstützt. Der MQTT Client verbindet sich
über seinen Client Socket mit dem Broker, an dem auch Z2M angemeldet ist.
Das Gateway muss die Topics `<Basistopic>/bridge/#` und
`<Basistopic>/symcon/automations/#` abonnieren. Empfangsfilter im Modul ersetzen
keine Broker-Abonnements. Es werden keine gemeinsamen Gatewayeinstellungen geändert.

Status 201 bedeutet fehlende/inaktive kompatible MQTT-Anbindung. Status 202
bedeutet, dass Z2M offline ist oder noch kein Online-Nachweis empfangen wurde.
Eine frische, zur laufenden Anfrage gehörende Health-Antwort bestätigt jetzt
auch ohne vorheriges bridge/state die Erreichbarkeit. `healthy: false` bleibt
als eigener Gesundheitsfehler sichtbar. Retained oder fremde Antworten dürfen
diesen Nachweis nicht liefern. Eine spätere Offline-Meldung setzt ihn zurück.

Keine Live-Installation oder Prüfung am echten Broker im Rahmen der Offline-Tests.
Die Darstellung im nativen Debugfenster ist noch praktisch zu prüfen.

Der native Empfangsfilter umfasst den vollständigen JSON-Umschlag (`.*….*`).
Deaktivierte oder ungültige Konfigurationen verwenden `^$`, das keine gültige
JSON-Nachricht annimmt. Lookarounds wie `(?!)` sind in der eingesetzten nativen
Regex-Engine nicht unterstützt. Die Offline-Prüfung deckt vollständige
Umschläge und JSON-Escaping ab; sie ersetzt keinen Test der nativen Engine.

## Getrennte Eigenschaftsauswahl

`GetEditorItemForm` protokolliert das Öffnen und die erfolgreiche Vorbelegung des
Zeilendialogs. `ConfigureEditorItem` meldet Geräte-/Eigenschafts-/Wertwechsel und
ob die Auswahl zulässig ist. `SelectEditorConditionProperty` erklärt das
Zurücksetzen der abhängigen Wertauswahl. Katalogempfang meldet Geräteanzahl,
lesbare Eigenschaften und anhand Symcon geprüfte schaltbare Eigenschaften.
Keine Variablennamen, IEEE-Adressen, Regelwerte oder Auswahltokens im Debug.
Alle öffentlichen Einstiege verwenden zentrale Start-/Ende-Abläufe.

Die Installation protokolliert zusätzlich die Übergabe der Popup-Rückmeldung
mit Erfolgskennzeichen und festem Fehlercode. Fremde Fehlermeldungen gelangen
weder ins Popup noch ins Debug. Ein Darstellungsfehler verändert das
Installationsergebnis nicht.

Ist die Freigabe für externes JavaScript unbekannt, erklärt eine Fachmeldung,
dass der Auftrag über die offizielle Z2M-Schnittstelle geprüft wird. Unbekannt
ist kein Nachweis für eine deaktivierte Einstellung. Ein ausdrücklich gemeldetes
false blockiert weiterhin vor dem Versand.

## Messwertvergleiche und Bedingungslisten

InspectEditorTriggers meldet die Häufigkeit der gewählten Vergleichsoperatoren.
InspectEditorConditions erklärt Hinzufügen, Bearbeiten und den Vorbestand beim
Löschen; alle Bedingungen gelten gemeinsam (UND). SaveScheduledAutomation meldet
die gesamte Bedingungsanzahl und die UND-Verknüpfung. SelectAutomation meldet die
Anzahl vorbelegter Bedingungen. Keine Gerätenamen, IEEE-Adressen oder Werte
werden dafür protokolliert. Neue öffentliche Listenaktionen folgen denselben
zentralen Start-/Ende-Abläufen wie die bestehenden Editoraktionen.

## Astro-Auslöser

Der Klick-Editor nennt Auswahl, Versatz, Annahme oder ungültige Angaben.
Die Runtime meldet pro neuem Tagesplan Prüfung, gegebenenfalls fehlendes
Sonnenereignis und bei Fälligkeit den Start mit Regel-/Auslöser-/Laufbezug.
Meldungskatalog und eigener MQTT-Debugkanal verwenden dieselben festen Texte.
Keine Breiten-/Längengrade oder vollständigen Auslöserobjekte im Debug.
Fehlende Ereignisse erzeugen keinen Fehler pro Sekunde. Ein Debugausfall
verändert weder Berechnung noch Schaltpfad.

## Einzelne Regelläufe

SelectExecutionRule, RunAutomationOnce, StopAutomationRun und
RefreshExecutionStatus verwenden den kanonischen DebugFlowHelper. Deutsche
Entscheidungen nennen Startablehnung bei abweichendem Entwurf, einmalige
Übergabe, konkrete Stoppanforderung und den bestätigten Laufzustand mit
Schrittposition und fester Ursache. Regel-IDs erscheinen nur als Hashbezug;
Namen, YAML und Aktivierungsfreigaben werden nicht protokolliert. Der feste
Z2M-Katalog ergänzt Anfrage, einmaligen Start und gezielten Stopp. Die bestehende
zeitlich begrenzte Aufzeichnung liefert Bedingungen, Wartezeiten und Schritte
mit denselben Laufbezügen auch über Mesh. Laufstatus allein enthält keine
vollständige Ablaufhistorie und bestätigt keine physische Gerätewirkung.

## Gemeinsame Systemauswahl

Die automatische Verbindungsliste meldet Anzahl gefundener, bereits zugeordneter
und nicht verfügbarer Verbindungen. Die ausdrückliche Übernahme nennt Anzahl
und IDs der angehakten Instanzen. Nicht auswählbare Verbindungen und geänderte
Auswahldaten führen zu erklärten Abbrüchen. Anzeigenamen, Topics, MQTT-Konfiguration
und Entwurfsinhalte werden dabei nicht ausgegeben. Es wird keine MQTT-Anfrage
gesendet. Ein erneutes Öffnen liest die aktuelle lokale Instanzliste.

GetCompatibleParents meldet den Anlegeweg ohne eigene MQTT-Schnittstelle und
verweist auf die spätere Zuordnung der Z2M-Verbindungsinstanzen. Dieser reine
SDK-Metadatenaufruf bleibt auch während einer Übernahmesperre verfügbar. Er
ändert weder Elternverbindung noch Besitzer oder Entwürfe und verwendet
ebenfalls TraceModuleCall mit dem kanonischen DebugFlowHelper.

ApplyWorkspaceConnections, ChooseWorkspaceSystem, ReadWorkspaceCatalog und
WorkspaceEditorAction verwenden den kanonischen DebugFlowHelper über
TraceModuleCall. Lesende Übersicht, native Bearbeitung und Browserzugriffe
nennen Systembezug, Generation und Art des Zugriffs. Eine tatsächliche Änderung
im Systementwurf wird von einem Aufruf ohne Entwurfsänderung unterschieden.
Konflikte erklären, dass die Ansicht neu geladen werden muss und vorhandene
Eingaben nicht in ein anderes System geschrieben werden. Systemwahl und
Kataloglesen senden keine MQTT-Nachrichten.

Protokolliert werden keine YAML-Inhalte, Namen, Formularwerte, Browserzugänge
oder Auswahltoken. Lokale Sichtbarkeit und Änderungsbestätigung bedeuten keine
Übertragung oder Aktivierung in Z2M. Die zentrale Laufzeitsteuerung wird erst im
nächsten Ausbauschritt angebunden.

## Zentrale Prüfung und Übertragung

`RequestWorkspaceTransfer`, `GetWorkspaceTransferStatus`,
`RefreshWorkspaceTransfers` und die eng begrenzte lesende
`GetWorkspaceTransferAuthority` verwenden `TraceModuleCall`. Das Verbindungsmodul
führt `RequestDraftTransfer` und `GetDraftTransferResult` ebenso über den
kanonischen DebugFlowHelper. Entscheidungen nennen System-UUID, Verbindung,
Auftragsart, Revision, Größen und feste Ursachen. Namen, YAML, Eingabewerte,
Sitzungsfreigaben und Browserzugänge werden nicht protokolliert.

Die Meldungen unterscheiden ausdrücklich Vorbereitung, MQTT-Übergabe,
Validierung, Annahme der Datei und abschließende Statusbestätigung. Fehlerpfade
erklären Fristablauf, verlorenen Workflow, geänderten Entwurf/Empfänger und
fehlende Schaltrechte. Validierungsdetails nennen im Debug ausschließlich den
normalisierten Feldpfad und einen festen Ursachencode. Die verständliche Anzeige
im Formular/Designer ergänzt Regelnamen und Nummern, ohne diese zu debuggen.
Andere Systementwürfe und Abläufe werden nicht als erfolgreich mitgemeldet.

Die readonly Variablen `TransferSystem`, `TransferStatus` und `TransferError`
zeigen den ausgewählten Systembezug, Auftragszustand und die detaillierte
Ablehnung. Sie sind nicht schaltbar. Eine Rückmeldung bedeutet ausschließlich
Prüfung oder bestätigte Speicherung, niemals Aktivierung oder Gerätewirkung.

## Direkte Einrichtung aus Z2M

Alle öffentlichen Einstiege in WorkspaceSetup und ConnectionWorkspaceSetup
verwenden TraceModuleCall und den kanonischen DebugFlowHelper. Deutsche Meldungen
unterscheiden ausdrücklich Lesen, bereitgestellte Vorschau/private Sicherung,
frische Bestätigung des gespeicherten Stands, lokalen Claim und Veröffentlichung.
Sie nennen ausschließlich System-UUID, Revision, Anzahl und feste Ursachen.
YAML, Namen, Standortdaten, Kataloginhalte und Empfängersitzung bleiben geschützt.

Abbruchmeldungen erklären vorhandene Entwürfe, fremde Besitzer, Zeitablauf,
fehlende Antwort, veränderten Stand, gesperrten Speicher und falsches Format.
CancelWorkspaceSnapshot beendet nur den passenden eigenen Leseauftrag; die
Meldung grenzt dies vom Stopp laufender Automationen ab. Commit und Rückweg
melden ausschließlich lokale Wirkung. Kein Schritt sendet store, activate,
deactivate, Neustarts oder Gerätebefehle.

## Vollständiger Browsereditor und Aktionsreihenfolge

Erstellen und Bearbeiten verwenden dieselben Feldprüfungen. Die bestehenden
DebugFlowHelper-Eintritte erklären begrenzte Eintragszahlen, eingeschalteten
Regelschalter und Ablehnungsgrund. Neue Regeln werden immer ausgeschaltet
übernommen. Keine Namen, Koordinaten, Aktionswerte oder Browserzugänge loggen.

Beim Übernehmen einer geänderten Aktionsliste wird zwischen reiner Umsortierung
und geänderten Schritten unterschieden. Bei einer Umsortierung nennen die
Meldungen die bisherigen Schrittnummern in ihrer neuen Reihenfolge, etwa
[3, 1, 2]. Wartezeiten bleiben beim Schritt. Dies gilt auch für Definitionen
wiederverwendbarer Folgen. Ziehen oder Abbrechen erzeugt keinen Modulauftrag;
die sichtbare Browsermeldung erklärt den noch nicht gespeicherten Zwischenstand.
Die native Debugmeldung entsteht erst bei der lokalen Übernahme, ohne MQTT.

## Zentrale Betriebssteuerung

Alle öffentlichen Runtime-Einstiege beider Module verwenden TraceModuleCall und
DebugFlowHelper. Deutsche Meldungen erklären System-UUID, Verbindung, Auftrag,
ausstehende Bestätigung, Abschluss und feste Fehlerursache. Einzelregeln erscheinen
im Debug nur mit gehashtem Bezug. Weder Regelnamen noch YAML, Aktionswerte,
Aktivierungstoken oder Empfängersitzung werden ausgegeben.

Die Verbindung erklärt zuerst die reine Statusprüfung, dann den ausdrücklichen
Befehl und die erforderliche Abschlussbestätigung. Laufmeldungen nennen begrenzte
Anzahl und Folgenummer. Timeout, geänderte Zuordnung, Neustart und Verlust des
flüchtigen Workflows lösen keinen erneuten Versand aus. Der Benutzer wird zur
Statusprüfung aufgefordert. Statusvariablen erklären separat Automatikbetrieb
und Einzelstart; ein abgeschlossener Lauf behauptet keine bestätigte Gerätewirkung.
