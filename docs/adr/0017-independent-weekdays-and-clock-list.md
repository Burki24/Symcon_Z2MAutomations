# ADR 0017: Unabhängige Wochentage und mehrere Uhrzeiten

## Status

Angenommen und lokal implementiert. Synthetische Prüfungen; native Konsole und
reale Z2M-Installation werden erst nach gesondertem Praxisauftrag aktualisiert.

## Entscheidung

Mehrere Uhrzeiten verwenden die vorhandenen 1–8 `time`-Auslöser je Regel. Der
Klick-Editor verwaltet eine Liste mit `At`-Zeilen und einer gemeinsamen Zeitzone.
HH:MM wird zu HH:MM:SS normalisiert; ungültige, leere, doppelte und mehr als acht
Einträge werden vor einer Entwurfsänderung abgewiesen. Derselbe vorhandene
Laufzeitpfad verarbeitet ODER-Verknüpfung, Tagesmarken, Sperrzeiten, Stopp und
Wiederanlauf. Es gibt keinen zweiten Zeitplaner und keine Symcon-Zeitausführung.

Eine neue Schema-1-Bedingung `weekday` enthält genau `type`, `weekdays` und
`timezone`. Sie prüft den aktuellen Kalendertag ihrer Zeitzone und gilt für
Geräte-, Uhrzeit-, Astro-Auslöser und Einzelstarts. Die Bedingung wird auch vor
verzögerten Aktionen erneut ausgewertet. Sie zählt zur bestehenden Grenze von
16 Bedingungen und wird über die vorhandene YAML-Prüfung und Persistenz verteilt.

`time_window.weekdays` bleibt unverändert: Bei einem Fenster über Mitternacht
bezeichnet es den Starttag. Es wird nicht auf die neue Auswahl migriert, da
dies Nachtfenster inhaltlich verändern würde. Der Editor zeigt beide Auswahlen
mit unterscheidbaren Bezeichnungen. Ohne aktivierte unabhängige Auswahl wird
keine weekday-Klausel erzeugt. Mehrere weekday-Klauseln, gemischte Auslöser und
individuelle Zeitzonen je Uhrzeit werden nicht vereinfacht; vorhandene erweiterte
Regeln bleiben für reine Metadatenänderungen vollständig erhalten.

Die Änderung ist eine additive Schema-1-Variante. Alte Erweiterungen lehnen sie
ab. Modul und Erweiterung müssen gemeinsam aktualisiert werden; die vorhandene
Buildkennung macht die Abweichung sichtbar. Kein automatischer Upload und keine
automatische Aktivierung. Bestehende Einzelzeit-Aufrufe bleiben lesbar; ein
veralteter Aufruf darf keine Mehrfachzeiten auf eine Uhrzeit reduzieren.

## Einbindung und Diagnose

AutomationEditorCalendar besitzt nur die librarylokale Zeitlisten- und
Wochentagslogik. ConfigurationFormHelper und DebugFlowHelper bleiben kanonisch
unverändert. DateHelper wurde geprüft; seine tolerante Formatierung beliebiger
Datumswerte ersetzt keine strikte Uhrzeit- oder Zeitzonenprüfung und wird hierfür
nicht eingebunden. Keine fremden Implementierungen oder neuen Abhängigkeiten.

Die nativen Felder verwenden die vorhandene Auswahlbindung und EditorWork-
Erfassung einschließlich pre-delete-Kontext. Deutsche Debugmeldungen erklären
Bearbeitung, Anzahl, Ablehnung und lokale Übernahme ohne Namen, Standorte oder
Zeileninhalte. Die Erweiterung meldet das Wochentagsergebnis über den bestehenden
bereinigten MQTT-Debugkanal mit Regel-/Bedingungsbezug und `passed`.
