# Entwicklungsagenda

Mit dem Anwender am 02.11.2026 festgelegte Reihenfolge. Die Umsetzung erfolgt
jeweils zuerst lokal; Änderungen an laufenden Installationen brauchen einen
Praxisauftrag. Die Agenda ist keine Freigabe für Geräteaktionen.

1. **Bearbeitungsstand verständlich machen.** Formulareingaben, lokaler Entwurf,
   gespeicherter Z2M-Stand und tatsächlicher Betrieb getrennt anzeigen. Noch
   nicht übernommene Änderungen beim Wechsel von Regel oder Bearbeitungsbereich
   schützen. Umgesetzt und am 02.11.2026 in der nativen Testkonsole geprüft:
   Vorbelegung, Weiterbearbeiten, Verwerfen, Bereichswechsel, Statusabfrage,
   Schutz vor Prüfung offener Eingaben und Übernahme in einen lokalen Prüfentwurf.
   Aufgedeckte Klassenkontext- und Popup-Einbindungsfehler sind korrigiert.
   Umfang und weitere Prüflimits stehen in DEVELOPMENT.md.
2. **Viele Automationen übersichtlich verwalten.** Zehn sichtbare Listeneinträge,
   Suche und Regelschalterfilter je Bereich sowie ausgeschaltete Kopien lokal
   umgesetzt und mit 50/100 synthetischen Regeln geprüft. Auswahl und offene
   Eingaben bleiben erhalten. Am 02.11.2026 in der nativen Testkonsole bestätigt:
   Suche, kombinierter Filter, erhaltene Bearbeitung und ausgeschaltete Kopien
   für Geräte- und Zeitregeln. Dabei Spaltenausrichtung korrigiert; vorhandene
   Entwürfe und Z2M-Betrieb unverändert. Prüflimits stehen in DEVELOPMENT.md.
3. **Zeitsteuerung vervollständigen.** Wochentage unabhängig von Zeitfenstern
   und mehrere Uhrzeiten im Klick-Editor. Lokal implementiert: eine bis acht
   Uhrzeiten, eigene Kalendertagsbedingung, Vorbelegung, sichere Bearbeitung
   und deutsche Debug-Ausgaben. Native Praxisprüfung nach Modul- und
   Erweiterungsupdate offen; keine laufende Installation geändert.
4. **Erneute Auslösung festlegen.** Wahlweise ignorieren, neu starten oder
   verlängern, wenn eine Automation bereits läuft. Geplant.
5. **Weitere Aktionen ohne YAML erstellen.** Zusammengesetzte Gerätebefehle
   und Farben im Klick-Editor. Geplant.
6. **Abschluss und Dokumentation.** Benutzerführung und Dokumentation bereinigen,
   unterstützte Z2M-Versionen mit tatsächlichen Nachweisen benennen. Geplant.

Die Bestandsfunktionen und bisherige Praxisnachweise stehen in
[PROJECT_CONTEXT.md](../PROJECT_CONTEXT.md). Ausführung und Datenmodell verbleiben
in der eigenen Z2M-Erweiterung; Symcon wird zur Laufzeit nicht benötigt.
