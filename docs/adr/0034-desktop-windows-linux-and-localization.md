# ADR 0034: Gemeinsamer Designer und Desktop-App für Windows und Linux

- Datum: 07.11.2026
- Status: Ziel und parallele Plattformarbeit vom Benutzer beauftragt;
  technische Grundlage geplant, noch nicht implementiert.
- Konkretisiert die Desktop-Adaptergrenze aus [ADR 0026](0026-central-manager-and-system-connections.md).

## Entscheidung

Eine eigenständige Anwendung **Z2M-Automations** wird in einem eigenen
Repository **Z2M-Automations** geplant. Windows x64 und Ubuntu LTS x64
werden aus einer gemeinsamen Codebasis parallel umgesetzt und geprüft.
Die Ausgabe nennt ihr Betriebssystem ausdrücklich. macOS und ARM64 sind
keine zugesagte erste Plattform.

Namensentscheidung vom 07.11.2026: Die Symcon-Library wird im Repository
`Symcon_Z2MAutomations` geführt; die unabhängige App und ihr Repository heißen
`Z2M-Automations`. Die [Umbenennungsvorbereitung](../REPOSITORY_RENAME.md)
ändert keine Modulkennungen und benennt das GitHub-Repository noch nicht um.

Der Plan verwendet Electron, einen TypeScript-Unterbau, Electron Forge und
MQTT.js. Der vorhandene Designer erhält eine Adaptergrenze und gemeinsame
deutsche/englische Sprachkataloge mit lokal eingebundenem i18next. Tauri wurde
wegen der zusätzlichen Rust-/WebView-Unterschiede für diesen ersten Weg nicht
gewählt. Die technische Wahl benötigt den frühen Startnachweis auf beiden OS.

Symcon- und Desktop-Adapter verwenden denselben Designer, aber ihre jeweils
eigenen Verwaltungs-/Transportdienste. Symcon-IDs, PHP-Zugänge und native
Variablenaktionen sind keine Voraussetzung der Desktop-Anwendung. Die Desktop-
Aktionsauswahl prüft Z2M-Fähigkeiten; die bisherige zusätzliche Prüfung nativer
Schaltvariablen in Symcon bleibt erhalten.

Die Laufzeit bleibt in der eigenen Z2M-Erweiterung. Ein gemeinsamer Broker oder
vorhandene Bridges und gerichtete Freigaben ermöglichen Partneraktionen.
Die App installiert keine Broker. Das Schließen der App stoppt nicht die
erfolgreich aktivierten Z2M-Regeln.

## Zuständigkeiten

Bis zur tatsächlichen Extraktion bleiben die UI-Quellen in der Symcon-Library
kanonisch. Danach liefert das neue Repository einen versionierten gemeinsamen
Designer; Symcon bindet einen genau bezeichneten Artefaktstand ein. Kein
unabhängig fortgeschriebener UI-Fork und kein zusätzliches Helper-Repository.
Die eigene Erweiterung und ihr Schema bleiben zunächst in dieser Library
kanonisch und werden für die App als geprüftes Artefakt gebunden.

Zugangsdaten bleiben im Desktop-Unterbau und außerhalb portabler Projekte.
Lokale Entwürfe und Z2M-Stände werden vor Schreiben verglichen. Die bisherige
Symcon-Besitzerzuordnung wird nicht als verteilter Schreibschutz ausgegeben.
Symcon-Ereignisimport bleibt eine Symcon-spezifische Funktion.

## Konsequenzen und Umfang

Die Symcon-Beta und ihre vorhandenen Praxisnachweise bleiben getrennt von
Desktop-Builds und Desktop-Freigaben. Mehrsprachigkeit wird zuerst am bestehenden
Designer umgesetzt; danach beginnen beide App-Ziele gemeinsam. Eine Aufgabe
gilt erst nach Prüfung beider Plattformen als plattformübergreifend erledigt.

Dieser Auftrag erstellt Plan und Entscheidung, keine Anwendung, Remote-Repos,
Installationen oder Releases. Vorhandene Entwürfe und Live-Systeme bleiben
unverändert. Arbeitspakete, Abnahmeziele, Risiken und Primärquellen stehen im
[Umsetzungsplan](../DESKTOP_PLAN.md).
