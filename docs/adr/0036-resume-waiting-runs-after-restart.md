# ADR 0036: Wartende Automatikläufe nach Z2M-Neustart fortsetzen

Datum: 08.11.2026. Status: angenommen durch Umsetzungsauftrag.

## Entscheidung

restart_policy erlaubt pro Regel abort, resume und resume_expired. Fehlend
bedeutet abort. Der systemweite bestätigte Wiederanlauf bleibt zusätzlich
erforderlich. Native Oberfläche und gemeinsamer DE/EN-Designer verwenden
dasselbe optionale Schemafeld; neue Regeln bleiben ausgeschaltet.

Der Wartepunkt enthält nur Regel-/Laufkennung, expandierten Schritt, ursprüngliche
Wartedauer, Speicherzeit und absolute Frist. Basistopic, Revision und Prüfsumme
binden ihn an genau einen Regelstand. Die begrenzte Datei wird über den
bestehenden atomaren Speicherschreiber aktualisiert. Keine weitere Datenbank,
Bibliothek, MQTT-Verbindung oder Symcon-Abhängigkeit.

## Ausführung und Fehlergrenzen

Ausfallzeit zählt zur Frist. Eine bereits verstrichene Frist beendet resume;
resume_expired erlaubt den anstehenden Schritt nach Bedingungsprüfung. Weitere
Wartezeiten beginnen regulär. start_only gilt nur beim ursprünglichen Start;
andere Gerätebedingungen benötigen neue Beobachtungen.

Vor jeder Übergabe wird der Wartepunkt dauerhaft entnommen. Eine unbestätigte
Geräte-/Mesh-Übergabe wird nicht wiederholt. Ein Absturz unmittelbar nach Entnahme
kann deshalb eine Aktion auslassen; exakt einmalige Gerätewirkung wird nicht
zugesichert. Ein Abbruch nimmt bereits erfolgte Schaltungen nicht zurück.

Geordnetes Beenden erhält Wartepunkte. Bestätigter Stopp und geänderte Speicherung
verwerfen sie. Fehlerhafte Bindungen/Dateien oder zurückgestellte Uhr berechtigen
nicht zur Fortsetzung. Speicherfehler verhindern weitere Befehle des Laufs.
Einzelstarts und Auslöser-Mindestdauern bleiben flüchtig.

## Rückweg und Nachweise

abort je Regel oder ausgeschalteter systemweiter Wiederanlauf deaktivieren die
Fortsetzung. Vorhandene Regeln ohne Feld ändern ihr Verhalten nicht.
Synthetische Tests prüfen Fristen, Bedingungen, Verlängerung, Folgenposition,
Neustarts, Stopp und ungültige Speicherstände. Am 08.11.2026 auf installiertem
Z2M mit Restfrist und abgelaufener Frist praktisch bestätigt; Testbedingungen
verhinderten Gerätebefehle. Umfang und verbleibende Grenzen: N1 in der
[Prüfliste](../PRUEFLISTE.md).
