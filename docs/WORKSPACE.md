# Lokaler zentraler Arbeitsbereich

Schritt 3 von [ADR 0026](adr/0026-central-manager-and-system-connections.md).
Diese Stufe stellt Datenhaltung und lokale Modulfunktionen bereit. Die native
und die Browseroberfläche bleiben noch am bisherigen Editor. Schritt 4 ergänzt
inzwischen die ausdrückliche [Sicherung, Übernahme und den Rückweg](MIGRATION.md).
Eine neue zentrale Z2M-Steuerung ist noch nicht implementiert.

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
