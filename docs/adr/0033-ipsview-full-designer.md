# 0033 – IPSView zur Administration verworfen

Status: Auf Benutzerwunsch am 06.11.2026 zurückgenommen.

Die zwischenzeitlich erstellte IPSView-Administrationsansicht wird nicht weiter
verwendet. IPSView dient der Darstellung; die Verwaltung der Automationen
bleibt im Webfrontend und in der nativen Symcon-Kachel.

Eigener HTMLBox-Loader, IPSView-Zugang und Endpunkte, optionale Formularfelder
sowie die spezifischen Tests wurden entfernt. Die Framing-Ausnahme entfällt.
Frühere IPSView-Sitzungen werden nicht in normale Browserzugänge umgedeutet.
Bestehende Ausgabevariablen werden nicht automatisch gelöscht, damit der
Benutzer vorher vorhandene Platzierungen und Verknüpfungen entfernen kann.

Die regulär verteilten zentralen Helper bleiben unverändert; das spezielle
Abonnement wird beendet. Diese Entscheidung ersetzt die frühere Umsetzung,
ändert aber weder den nativen Designer noch die korrigierte Gerätedatenanzeige.
