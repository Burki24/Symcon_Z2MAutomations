# ADR 0026: Zentrale Verwaltung und Z2M-Verbindungen

- Datum: 04.11.2026
- Status: Zielarchitektur beschlossen; lokale Vertrags-/Vorschaugrundlage vorhanden,
  Gesamtumbau und Migration ausstehend
- Präzisiert die künftige Aufteilung aus ADR 0002 und 0009; bestehendes Verhalten
  bleibt bis zur geprüften Umsetzung gültig.

## Entscheidung

Eine Z2MAutomations-Verwaltung führt bis zu acht Z2M-Systeme in einem Arbeitsbereich
zusammen. Ein neues Z2MConnection-Modul je System kapselt dessen vorhandene native
MQTT-Verbindung, Basistopic, Transportaufträge und bestätigten Zustand. Die
Verwaltung referenziert die Verbindungsinstanzen ausdrücklich und ist kein
mehrfach verbundener MQTT-Splitter. Mehrere Systeme können denselben Client/Broker
verwenden. Es wird keine eigene MQTT-Implementierung in PHP eingeführt.

Jedes System besitzt weiterhin ein eigenes ausführbares Dokument, Revision,
Prüfsumme, Betriebsfreigabe und eigene Z2M-Laufzeit. Zentrale Ansicht bedeutet
keine atomische Mehrsystemübertragung und keine Laufzeit in Symcon. Gerätequellen
und Bedingungen bleiben lokal; Aktionen können freigegebene andere Systeme
ansprechen. Folgen bleiben zunächst je System definiert. PeerModuleID wird
durch explizite Systemzuordnungen und gerichtete Empfangsfreigaben abgelöst.

Stabile Verwaltungs-SystemIDs trennen gleiche Geräte-/Gruppen-/Regelkennungen.
Auf der Leitung bleibt zunächst der vorhandene Mesh-1-Vertrag mit Basistopic als
Systemidentität erhalten. Gleiche Basistopics im gemeinsamen Mesh-Verbund werden
als Konflikt abgewiesen; eine neue Leitungsidentität ist kein Teil dieses Umbaus.
Acht Systeme sind ein neues Prüfziel, noch keine belegte Betriebsfreigabe.

## Übernahme und Grenzen

Eine ausdrückliche Übernahme übernimmt Entwürfe und Bindungen systemweise;
unterschiedliche lokale und gespeicherte Fassungen werden nicht automatisch
ersetzt oder zusammengeführt. Roh-YAML, lokale Kennungen und vorhandene
Partnerfreigaben bleiben erhalten. Private Sicherung, Vorschau, Fingerabdrücke,
persistentes Journal, ein schreibender Besitzer und Rückweg sind Pflicht.
Altinstanzen und native MQTT-/Geräteinstanzen werden nicht automatisch gelöscht,
umgehängt oder durch ein bloßes Update konvertiert. Offene Eingaben bleiben
gesichert und blockieren die Umschaltung, solange ihre Übernahme ungeklärt ist.

Historische Betriebswerte sind keine frische Status- oder Aktivierungsfreigabe.
Sitzungen, Tokens und laufende Aufträge werden nicht migriert. Die reine lokale
Übernahme sendet keine Speicher-, Aktivierungs-, Stopp-, Neustart- oder
Gerätebefehle an Z2M. Bereits laufende Regeln bleiben dort unabhängig bestehen.
Öffentliche Bestandsaufrufe werden während des Übergangs eindeutig an ihr
ursprüngliches System gebunden und in diesem Schritt nicht entfernt.

## Folgen

Die bestehende Fachlogik wird schrittweise herausgelöst. Zentrale Helper bleiben
kanonisch eingebunden; Transport-/Automationslogik bleibt librarylokal. UI und
fachliches Projektformat erhalten eine Adaptergrenze für eine spätere native
Windows-Anwendung. Symcon-IDs und Zugangsdaten sind kein Teil ihres Projektformats.

Der [Umsetzungs- und Übernahmeplan](../MULTISYSTEM_PLAN.md) legt Datenbesitz,
Fehlerfälle, Rückweg, Arbeitsfolge und Prüfmatrix fest. Zuerst werden lokale
Verträge und eine read-only Übernahmevorschau geprüft, danach Verbindungen,
Arbeitsbereich, Migration und Oberfläche. Test- und Live-Umstellungen sowie
Erweiterungsupdates bleiben getrennte ausdrückliche Aufträge.
