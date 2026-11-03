# ADR 0018: Erneute Auslösung einer laufenden Automation

Status: lokal umgesetzt; native und reale Praxisprüfung nach Update ausstehend.
Datum: 03.11.2026

## Entscheidung

Die optionale Regelangabe `retrigger` verwendet `ignore`, `restart` oder
`extend`. Ohne Angabe bleibt das bisherige Ignorieren bestehen. Schema 1 bleibt
erhalten; Modul und Erweiterung müssen für die neue Angabe gemeinsam aktualisiert
werden. Eine alte Erweiterung weist das neue Feld ab. Keine automatische
Migration oder Änderung bereits gespeicherter Regeln.

Die Auswahl steht in Geräte- und Zeitbereichen einschließlich Astro bereit.
Sie gehört zum geschützten Formularstand und zum gespeicherten Regelinhalt.
Kopien erhalten dieselbe Auswahl; Aufrufer ohne neue Option erhalten eine
vorhandene Auswahl. Nicht darstellbare Regeln bleiben metadatenbearbeitbar,
ohne ihre Laufzeitoption zu überschreiben.

`ignore` erhält den aktuellen Lauf. `extend` setzt nur eine gerade aktive
Wartezeit vor einem aufgelösten Aktionsschritt auf ihre volle Dauer zurück.
Laufkennung und Schritt bleiben erhalten; erledigte Aktionen werden nicht
wiederholt. Ohne aktive Wartezeit wird die Auslösung ignoriert. Die Wartezeit
eines Folgenaufrufs und die erste enthaltene Wartezeit sind wie bisher addiert.
Wiederholte gültige Auslösungen dürfen die Wartezeit wiederholt zurücksetzen.
Es entsteht weder ein zweiter Timer noch eine Warteschlange von Auslösungen.

`restart` verwirft den noch nicht ausgeführten Rest und beginnt bei der ersten
Aktion mit neuer Laufkennung. Eine bereits begonnene lokale oder entfernte
Befehlsübergabe wird zunächst abgeschlossen. Währenddessen wird höchstens ein
Neustart vorgemerkt. Fehler oder unbestätigte Übergaben verwerfen ihn; keine
automatische Wiederholung eines unsicheren Befehls. Bereits übergebene Aktionen
werden nicht zurückgenommen und können bei einer erfolgreichen neuen Folge
erneut vorkommen, etwa beim Umschalten.

## Grenzen und Zuständigkeit

Eine erneute Auslösung muss die normale Auslöserprüfung einschließlich
Wertänderung/Mindestdauer bestehen. Bedingungen und die bestehende Sperrzeit
von einer Sekunde gelten vor jeder angenommenen Erneuerung. Nicht erfüllte
Bedingungen verändern den bisherigen Lauf nicht. Beim tatsächlichen Neustart
und vor jedem Schritt werden Bedingungen erneut geprüft. Eine längere Folge
darf während eines Zeit-/Kalendertagswechsels deshalb abbrechen.

Der Einzelstart ersetzt keinen laufenden Ablauf und wird auch nicht automatisch
von Geräteauslösern ersetzt oder verlängert. Gezielt/global stoppen, geändertes
Speichern, Deaktivierung und Erweiterungsneustart verwerfen vorgemerkte Neustarts
und erneuerte Wartezeiten. Wiederanlauf stellt nur den bisherigen Automatikbetrieb
her und setzt keine unterbrochene Folge fort.

Die bestehende Runtime besitzt Ausführung, Timer und Stopp; Mesh besitzt weiter
seine Einmalfreigaben. Keine neue MQTT-Schnittstelle, Symcon-Laufzeit oder
persistente Ablaufwarteschlange. Diagnoseereignisse verwenden den festen
bereinigten Katalog und denselben MQTT-Debugkanal. Formulardarstellung und
DebugFlowHelper aus dem zentralen Helperbestand bleiben unverändert.

## Nachweis

Synthetische native PHP-Callbacks prüfen Auswahl, offene Eingaben, Vorbelegung,
Kopie und atomare Ablehnung. Die PHP-/JavaScript-Speicherintegration prüft
Erstellen, Prüfen, Speichern, Wiederladen und geänderte Auswahl. Deterministische
Laufzeittests prüfen Timerfristen, getrennte Läufe, Bedingungen, Sperrzeit,
laufende Übergaben, Stopp und Fehler. Grenzen und frische Gesamtprüfungen stehen
in DEVELOPMENT.md. Kein Zugriff auf Test-/Live-System und keine Geräteaktion.
