# Lokaler zentraler Arbeitsbereich

Schritt 3 von [ADR 0026](adr/0026-central-manager-and-system-connections.md).
Diese Stufe stellt Datenhaltung und lokale Modulfunktionen bereit. Schritt 5
bindet inzwischen native Oberfläche und Browserdesigner an diese Daten an. Schritt 4 ergänzt
inzwischen die ausdrückliche [Sicherung, Übernahme und den Rückweg](MIGRATION.md).
Eine neue zentrale Z2M-Steuerung ist noch nicht implementiert.

## Gemeinsame Bearbeitung

Das native Formular ermittelt vorhandene Verbindungsinstanzen ausschließlich
anhand der Z2MConnection-Modul-GUID. Es verwendet keine Splitter-/Parent-Suche
und keinen Objektpicker. Die Liste zeigt Name/ID, Topic und native MQTT-Zuordnung.
Nur die Checkbox ist bearbeitbar; bereits konfigurierte Zeilen bleiben geschützt
angehakt. Fehlende gespeicherte Instanzen bleiben als gesperrte Zeilen sichtbar.
Fremde Besitzer, ungültige Zuordnungen und unlesbare Metadaten sperren nur die
betroffene Auswahlzeile. Das Anzeigen oder Anhaken löst keine Registrierung aus.

ApplyWorkspaceConnections erhält ausgewählte IDs, Workspace-Fingerabdruck und
einen Fingerabdruck der erkannten Identitäten und Besitzer. Dieser wird vor
dem Übernehmen frisch geprüft; flüchtige Laufzeitwerte und Anzeigenamen sind
kein Teil davon. Eine Änderung verwirft den Auftrag mit verständlichem Hinweis.
Danach prüft ConfigureWorkspace weiterhin Modultyp, Elterninstanz, Topic,
Generationen, Besitzer und acht-Systeme-Grenze. Die ursprüngliche sourceInstance
eines migrierten Eintrags wird bei Bestätigung derselben Verbindung erhalten.
Es findet keine neue Besitzerübernahme oder implizite Löschung statt.

WorkspaceForm stellt die native Systemliste und den lokalen Editor bereit.
WorkspaceDesigner ergänzt die Systemwahl im Browser. WorkspaceEditorContext
verwendet die vorhandenen Editorvalidatoren mit expliziten Speicherzugriffen
auf genau einen Systemeintrag. Die bisherigen Instanzattribute werden nicht
beim Umschalten ausgetauscht. Eine zusätzliche Rückwärtskompatibilitätsschicht
ist nicht vorgesehen.

Native Aufträge nennen SystemID, Generation und Dokumentfingerabdruck. Eine
feste Positivliste erlaubt ausschließlich lokale Editoroperationen. Auch
dynamische Listenformulare sind daran gebunden. Die generierten eigenen
Callbacktexte werden ohne eval in systemgebundene Aufrufe übersetzt. Mehrere
Aufrufe desselben Klicks übernehmen den jeweils bestätigten Fingerabdruck.
Eine inzwischen geänderte Systemauswahl weist alte native Formulare zurück.

Der Browser sendet dieselben Systemangaben in separaten Headern. Datenabfragen
mit einer Systemkennung ändern die Auswahl nicht; select-system wechselt sie
ausdrücklich und prüft offene native Eingaben. CRUD-Aufträge ändern nur den
benannten Entwurf. Quittungen bleiben je System und Besitzergeneration getrennt.
Empfängerfreigaben, Revision und andere Systeme werden nicht stillschweigend
ersetzt. Es gibt keinen allgemeinen PHP- oder MQTT-Aufruf aus dem Browser.

WorkspaceEditorViews enthält nur kurzlebige Auswahltoken und Editoransichten.
Kataloge werden nicht zusätzlich darin gespeichert. Offene Eingaben werden im
persistenten data.work gehalten und nach Verlust dieser Puffer wieder vorgelegt.
Der Browser liest Gerätemetadaten in Seiten von höchstens 20 Einträgen und
zeigt den Ladefortschritt. Ein unvollständiger Abruf ersetzt die vorige Ansicht
nicht. Native Aktionsrechte werden weiterhin anhand der Variablen-ID geprüft.

Eine neue Zuordnung initialisiert keinen Entwurf über unbekannten Z2M-Dateien.
Vorhandene Dokumente müssen ausdrücklich über den gesicherten Übernahmeablauf
zugeordnet werden. Diese Stufe ergänzt keine zentralen Betriebsaufträge;
Laufzeit und MQTT-Verbindungen werden durch Editorzugriffe nicht verändert.

## Zuständigkeiten und Daten

`SystemWorkspace` verwendet ein eigenes persistentes Attribut `SystemWorkspace`
der vorhandenen Z2MAutomations-Klasse. Registriert wird zunächst ein leerer Text.
Lesen erzeugt einen leeren versionierten Stand, schreibt ihn aber nicht zurück.
Create, ApplyChanges und Bestandseditor ändern diesen Arbeitsbereich nicht.
Nur die ausdrücklichen Workspace-Funktionen schreiben ihn.

Die Wurzel enthält `version`, `selectedSystem`, `systems` und `receipts`.
`systems` ist nach stabiler UUID indiziert. Jeder Eintrag hat diese Bereiche:

| Bereich | Inhalt |
| --- | --- |
| binding | SystemContract v1 mit Verbindung, Parent, Topic, Generation und lokaler Besitzer-ID; sourceInstance ist vor der Übernahme 0, danach die Quellinstanz |
| name | Anzeigename der ausgewählten Verbindungsinstanz |
| data | Getrenntes Dokument, Auswahl und offene Eingaben dieses Systems |
| catalog | Explizit eingelesener, bestätigter Katalog der Verbindung |
| nativeBindings | Native Variablen-IDs, Elterninstanzen und Anzeigenamen; getrennt von Dokument und Gerätemetadaten |
| ownershipEpoch | Optional bei übernommenen Einträgen: Kennung dieses Besitzerwechsels. Geht in den Bearbeitungsfingerabdruck ein und sperrt alte Ansichten nach einem Rückweg. Bestehende vorbereitende v1-Einträge ohne dieses Feld bleiben gültig. |

`data` enthält genau `document`, `selection` und `work`:

```json
{
    "document": null,
    "selection": {"rule": "", "sequence": ""},
    "work": {"rule": null, "sequence": null}
}
```

Ein Dokument enthält den **exakten** `yaml`-Text sowie `editorDraft` mit
`configuration`, `baseRevision`, `baseChecksum` und `draftChecksum`. Sitzung,
Transport-Scope und Aktivierungstoken sind kein Teil dieses Dokuments.
`draftChecksum` muss dem SHA-256 der YAML-Bytes entsprechen. Die lokale Prüfung
kontrolliert Struktur, Kennungen, Größen und Folgenverweise. Sie parst keine
beliebige YAML und ersetzt die Z2M-Validierung nicht. Jede Ausgabe eines Dokuments
meldet deshalb `requiresValidation: true`.

`selection` enthält Regel-/Folgenkennungen oder leere Strings. `work` enthält
jeweils null oder `{dirty: bool, input: array}`. Offene Eingaben bleiben getrennt
pro System erhalten. Bei dirty-Eingaben blockiert ein Wechsel des ausgewählten
Systems, bis der Aufrufer sie durch ausdrückliches lokales Übernehmen oder
Verwerfen aufgelöst hat. Es wird nichts automatisch verworfen.

Das Dokumentformat ist eine lokale Vorstufe. Ein portables Windows-Projektformat
ist davon getrennt. Private Sicherungs-/Übernahmearchive beschreibt MIGRATION.md.

## Zuordnung und Besitzergrenze

Die Systemliste liest ausschließlich ausdrücklich übergebene Z2MConnection-IDs.
Modultyp, native MQTT-Parent-ID, Basistopic, Systemkennung und Generation müssen
passen. Die Instanz-ID und das Topic bestimmen das Ziel, nicht dessen Anzeigename.
RegistryError aus SystemContract schützt die Grenze von acht Systemen sowie
doppelte Systemkennungen, Verbindungseinträge und Mesh-Basistopics.

Vor einer Übernahme nennt `ownerInstance` zunächst die Z2MAutomations-Instanz,
in der der vorbereitende Arbeitsbereich liegt. Erst die ausdrückliche Migration
registriert exklusive Verbindungseigentümerschaft. Danach schützt die gemeinsame
Besitzersperre auch den abschließenden lokalen Schreibvorgang gegen andere
Verwaltungen. Rollenmarker, alter Editor und Rückweg werden gemeinsam behandelt;
eine reine ConfigureWorkspace-Zuordnung ist keine Migration. Aus diesem
Arbeitsbereich gibt es noch keinen Pfad zum Speichern oder Aktivieren in Z2M.

Neue Zuordnungen erzeugen leere Dokumentplätze und erteilen keine mesh_peers.
Vorhandene Systeme werden durch erneutes Konfigurieren nicht gelöscht. Entfernen
bleibt bis zum Sicherungs-/Verweisworkflow gesperrt. Eine geänderte physische
Zuordnung wird nur bei ausdrücklich neu konfigurierter höherer Generation
übernommen. Daten und alte Katalogbytes bleiben dabei erhalten, alte Kataloge
werden aber für das neue Ziel nicht mehr angeboten. Dirty-Eingaben blockieren
die Neuzuordnung. Eine unbekannte Version oder beschädigte Persistenz wird
gemeldet und niemals durch einen leeren Stand ersetzt.

## Kataloge, Identitäten und Rechte

`RefreshWorkspaceCatalog` liest GetConnectionInfo und GetDeviceCatalog der
zugeordneten Verbindung. Beide sind lokale Snapshot-Aufrufe ohne MQTT. Fehlen dort
frisch bestätigte Gerätedaten, bleibt der vorhandene Workspace-Katalog erhalten.
Die Verbindung muss zunächst ausdrücklich aktualisiert werden. Ein Katalogabruf
überschreibt weder Dokument noch Auswahl oder offene Eingaben.

Andere Module werden außerhalb der Workspace-Semaphore aufgerufen. Vor der
Übernahme werden Generation, Fingerabdruck und tatsächliche Zuordnung erneut
geprüft. Eine zwischenzeitliche Bearbeitung oder ein Gatewaywechsel verhindert
die Übernahme des alten Ergebnisses.

Native Gerätezuordnung und Schaltrechte verwenden die mit dem Bestandseditor
geteilten Adapter `Z2MInstanceIdentity` und `NativeVariableBindings`.
Der Workspace filtert zuerst nach Parent und Topic; eine hohe Zahl fremder
Instanzen darf spätere passende Geräte nicht durch ein globales 512er-Limit
verdecken. Gleiche Geräte-/Gruppenkennungen verschiedener Systeme werden durch
SystemContract.EntityKey getrennt. Namenssuche ersetzt keine native Variablen-ID.

Geräte-/Gruppen-/Szenenmetadaten und Eigenschaftenauswahl verwenden die bestehenden
Editorbausteine. Der optionale Binding-Resolver in EditorProperties erlaubt ihre
Verwendung mit einem bestimmten Workspace-System, ohne den Legacy-Parent oder
PeerModuleID umzubiegen. Der alte Editor behält seinen bisherigen Resolver.

Auslöser und Bedingungen erhalten lesbare Eigenschaften und bleiben lokal.
Aktionen benötigen schreibbare Z2M-Eigenschaften und eine tatsächlich schaltbare
native Variable. Bei jeder Auswahl werden ID, Parent, Ident, Gateway, Topic,
Sperrzustand und Custom-/Standardaktion neu geprüft. Szenen verwenden die native
Schaltvariable ihrer jeweiligen Steuerungseigenschaft. Fehlende, doppelte oder
geänderte Zuordnungen sperren nur die betroffene Auswahl; bestehende Regeln und
deren Roh-YAML bleiben unverändert.

Eine fremde Aktionsauswahl setzt die **gerichtete lokale Empfangsfreigabe** im
Zielentwurf voraus: dessen mesh_peers muss das Basistopic des Quellsystems
enthalten. Sichtbarkeit eines Systems erteilt keine Gegenrichtung und keinen
Vollverbund. Fehlendes und leeres mesh_peers bleiben verschieden und unverändert.
Normales UpdateWorkspaceSystem darf vorhandene Freigaben nicht verändern; dafür
ist `ChangePermissions: true` erforderlich. Das ist nur eine geplante Freigabe
im lokalen Dokument. Es bestätigt keine gespeicherte oder aktive Berechtigung
in Z2M; deren spätere Übertragung muss erneut geprüft werden.

## Kontexte, Wiederholung und Grenzen

Die Übersicht liefert einen Fingerabdruck für Registry und Systemauswahl.
Jedes System liefert daneben einen eigenen Fingerabdruck einschließlich Daten,
Zuordnung und Katalog. Die Änderung eines anderen Systems entwertet dessen
Bearbeitungskontext nicht. Abweichender Fingerabdruck oder falsche Generation
verhindern Überschreiben durch einen alten Tab.

Schreibaufrufe benötigen eine 32-stellige kleingeschriebene Hex-Operationskennung.
Die letzten 32 erfolgreichen Aufträge behalten eine persistente Quittung aus
Anfragehash und begrenzter Antwort. Gleiche Kennung und identische Parameter
liefern die ursprüngliche Antwort, ohne eine zweite Änderung auszuführen.
Andere Parameter mit derselben Kennung werden abgewiesen. Quittung und Daten
werden in einem Attribut zusammen geschrieben; das ist kein Migrationsjournal.
Quittungen gehören nicht zum Bearbeitungsfingerabdruck und erweitern keine
Z2M-Berechtigung. Nach Verdrängung einer alten Quittung gelten die normalen
Kontextprüfungen; es gibt keine unbegrenzte Wiederholungshistorie.

| Grenze | Wert |
| --- | --- |
| Systeme | 1 bis 8, leere Übersicht vor Einrichtung |
| YAML je Dokument | 128 KiB, UTF-8 |
| Gesamte Bearbeitungsdaten je System | 512 KiB |
| Offene Eingaben je Regel-/Folgenbearbeitung | 64 KiB |
| Persistenter Arbeitsbereich | 20 MiB |
| Einzelantwort | 2 MiB, kein stilles Abschneiden |
| Geräteauswahl pro Seite | 1 bis 100, Standard 50 |
| Quittungen | letzte 32 erfolgreiche lokale Operationen |

Die Übersicht enthält keine vollständigen Kataloge oder Dokumente. Systemdetails
werden einzeln gelesen; die Auswahl liefert Offset, total und hasMore. Zu große
Antworten werden verständlich abgewiesen. Für einzelne Erweiterungsantworten
gilt zusätzlich unverändert die vorhandene MQTT-Nutzdatengrenze von 1 MiB.

Die verwendeten zentralen DataFlow-, ParentConnection- und DebugFlow-Verträge
wurden geprüft. Sie bleiben unverändert. PersistentJsonCacheHelper wurde ebenfalls
geprüft; dieser versionierte Benutzerarbeitsbereich mit Konfliktprüfung ist kein
verwerfbarer API-Cache. Seine fachliche Persistenz bleibt beim lokalen Modell;
es wird kein generischer Ersatzhelper und keine unverteilte Helperkopie eingeführt.

## Prüfung und Übertragung pro System

`RequestWorkspaceTransfer(SystemID, Generation, Fingerprint, Action, OperationID,
TransferContext)` unterstützt ausschließlich `validate` und `store`. Das native
Formular und die authentifizierten Designer-Endpunkte verwenden denselben Weg.
Die vorangehende lokale Übernahme muss abgeschlossen und die Verbindung exklusiv
an diese Zentrale gebunden sein. Zuordnung allein beansprucht keinen Besitzer.

`GetWorkspaceTransferStatus(SystemID)` und `RefreshWorkspaceTransfers` lesen die
lokalen Verbindungsresultate. Sie senden keine MQTT-Anfrage. Der kurze Timer läuft
nur bei ausstehenden Aufträgen. Der Designer liest währenddessen begrenzt den
lokalen Status; nach Ablauf kann der Benutzer ihn ausdrücklich erneut lesen.
Aktivierung, Stopp und Einzellauf sind hier nicht implementiert.

Die Zentrale hält je System das letzte Ergebnis in `WorkspaceTransfers` und die
letzten 32 Auftragsquittungen. Die Verbindung hält die letzten 32 Ergebnisse in
`ConnectionDraftTransfers`. Beide Journale enthalten Metadaten und begrenzte
Validierungsdetails, keine YAML oder wiederverwendbare Empfängerfreigabe. Die
Zentrale ergänzt für die Anzeige lokale Regelnamen, ohne sie zu debuggen.
Journalgrenzen: Zentrale 1 MiB, Verbindung 256 KiB; beschädigte Einträge werden
abgewiesen und nicht durch einen leeren Stand ersetzt.

Vor dem Delegieren wird der Auftrag dauerhaft vermerkt. Die Verbindung prüft
Besitzer, Übernahmeepoch, UUID, Generation, MQTT-Parent, Topic und YAML-Prüfsumme.
Vor jedem weiteren MQTT-Schritt liest sie die aktuelle reine Freigabe der Zentrale
über `GetWorkspaceTransferAuthority`. Nur dieser eng begrenzte lesende Aufruf
umgeht die Eintrittssemaphore, um synchrone MQTT-Antworten ohne Selbstsperre zu
ermöglichen. Er beansprucht keinen Besitzer und erteilt keine neue Berechtigung.
Geänderte Dokumente verhindern den nächsten Schreibschritt. Eine bereits
begonnene Speicherung darf weiter ausschließlich lesend bestätigt werden.

Das vorhandene Status-/Prüf-/Speicherprotokoll bleibt unverändert. Geteilte reine
Prüfungen in `DraftTransferProtocol` gelten auch für den bisherigen Einzelbetrieb.
Session, Basisrevision, vorherige Prüfsumme und positive Validierungsquittung
müssen zusammenpassen. Der identische bereits gespeicherte Stand ist zulässig;
eine anderweitig verwendete Revision wird nicht überschrieben. Aktuelle native
Aktionsbindungen und gerichtete Partnerfreigaben werden vor `store` geprüft.
Erst Speicherantwort plus frische passende Statusantwort ergeben `stored`.
Nur dann wird bei unveränderter YAML die Bearbeitungsbasis dieses Dokuments
fortgeschrieben. Andere Dokumente und zwischenzeitliche Eingaben bleiben erhalten.

Bei verlorenen Antworten, Fristablauf, Neustart oder Besitzerwechsel erfolgt keine
automatische Wiederholung. Eine quittierte Datei ohne abschließenden Status heißt
`stored_unconfirmed`; ein verlorener flüchtiger Workflow heißt `interrupted`.
Prüfbelege sind flüchtig und geben nach einem Neustart keine Speicherung frei.
Fehlerdetails überleben unabhängige Status-/Katalogabfragen bis zur neuen Prüfung.
Ein fehlgeschlagenes System löst weder Sammelrollback noch Wiederholungen anderer
Systeme aus. Nach möglichem externem Schreiben sperrt ein dauerhafter Epochmarker
den rein lokalen Übernahme-Rückweg; siehe [Migration](MIGRATION.md).

Designer: GET `transfer-status`, POST `transfer` mit gleicher Herkunft,
JSON-Inhaltstyp, gültigem zeitlich begrenztem Designerzugang und exakten
Systemheadern. Der Body enthält ausschließlich Aktion, Anfrage-UUID und
Übertragungskontext. Ein verspätetes Ergebnis ersetzt keine inzwischen gewechselte
Systemansicht. Speicherung verlangt eine sichtbare Bestätigung des Zieltopics
und weist auf das Stoppen des bisherigen Betriebs hin.
