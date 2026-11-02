# ADR 0013: Unabhängig berechnete Sonnenereignisse

Status: angenommen, lokal implementiert; noch nicht installiert.

## Anlass

Eine Automation soll bei Sonnenauf- oder Sonnenuntergang mit Minutenversatz
starten können und anschließend dauerhaft ohne Symcon laufen. Vorberechnete
Zeiten oder tägliche Nachlieferung aus Symcon würden diese Anforderung verfehlen.

## Entscheidung

Schema 1 erhält die additive sun-Variante mit Ereignis, explizitem Standort,
Zeitzone und Versatz. Bestehende time-Auslöser behalten ihre Semantik.
Im Zeitbereich wählt der Anwender Uhrzeit oder Sonnenereignis. Leere
Koordinaten sind keine gültige Konfiguration; keine automatische Ortung.

Eine eigene reine Implementierung der veröffentlichten allgemeinen
[NOAA-Sonnengleichungen](https://gml.noaa.gov/grad/solcalc/solareqns.PDF) wird
in das vorhandene Uploadartefakt eingebettet. Keine neue Fremdbibliothek und
keine Übernahme von Code oder Struktur des fremden Automationsprojekts.
Vorhandene zentrale Helper bleiben unverändert; GeofenceHelper ist kein
astronomischer Zeitplaner. Berechnung und Regelverhalten bleiben librarylokal.

Der lokale Ereignistag bezeichnet das Sonnenereignis vor Anwendung des Versatzes.
Seine UTC-Zeit wird auf die Minute gerundet und um höchstens zwölf Stunden
verschoben. Der begrenzte Tagesplan berücksichtigt Nachbartage und behandelt
polare Tage ohne Ereignis ohne Ersatzschaltzeit. Bereits bearbeitete Tage
werden innerhalb der Aktivierung nicht erneut ausgelöst. Kein Nachholen
verpasster Zeiten; Neustart berechnet neue Zeitpunkte.

## Grenzen und Nachweise

Die Näherung verwendet den Apparenthorizont von 90,833 Grad ohne Gelände,
Höhe oder Wetter. Keine Zusage sekundengenauer Beobachtung, besonders nicht
an polaren Übergängen. Unabhängige PHP-date_sun_info-Referenzen mit synthetischen
Koordinaten, Jahreszeiten, Schaltjahr und beiden Längengradvorzeichen prüfen
normale Fälle innerhalb fünf Minuten. Polartage werden ohne Ereignis geprüft.

Die echte PHP-/JS-Integration prüft Formularskripte für Anlegen, Ändern,
Vorbelegung, Prüfung, Speicherung und Rückwechsel auf Uhrzeit. Runtime-Tests
decken Zeitzonen, Mitternacht, Versatz, Rücksprung, Bedingungen, verpasste
Ereignisse, Wiederanlauf und Mesh ab. Debug enthält keine Koordinaten.
Installation und reale Geräteaktionen benötigen weiterhin einen Praxisauftrag.
