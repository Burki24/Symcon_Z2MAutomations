# ADR 0023: Vorhandene Regeln im Browser bearbeiten

- Status: angenommen
- Datum: 03.11.2026
- Erweitert: ADR 0021 und 0022; GET bleibt lesend

## Anlass

Der Designer soll vorhandene Regeln ohne YAML-Kenntnisse bearbeiten. Bereits
enthaltene Angaben, andere Regeln und Freigaben dürfen dabei nicht verloren
gehen. Browser und natives Formular können gleichzeitig geöffnet sein.

## Entscheidung

editor.js ergänzt einen Dialog mit Name, lokalem Regelschalter, Verhalten bei
erneuter Auslösung und getrennten Bereichen für Auslöser, Bedingungen und Aktionen.
Geräteereignisse, mehrere Uhrzeiten, Sonnenzeiten, Gerätebedingungen, Wochentage,
Zeitfenster, gemeinsame Set-Befehle, Toggle, Szenen und Folgenaufrufe werden
vorbelegt. Geräte-/Gruppen-/Systemidentitäten und Werttypen bleiben exakt erhalten,
auch false, Nullwerte und Zahlenwert 0. Verzögerungen und Reihenfolge bleiben erhalten.

Nur die feste Route edit akzeptiert eine vorhandene Regel-ID und ausdrücklich
geänderte Felder. Sie verlangt eine neue befristete Sitzung mit edit-Recht,
dieselben Origin-/Host-/JSON-Prüfungen wie create und einen höchstens 64 KiB
großen Körper. Der Token bleibt im Fragment und Header. Keine freie PHP-Methode,
YAML-Datei, Gesamtentwurfsübernahme oder Laufzeitsteuerung.

Unter der vorhandenen Transportsperre prüft der Handler den Kontext über
Entwurf, Katalog, Auswahl und native Eingabepuffer. Veraltete Ansichten und
offene native Änderungen werden abgewiesen. Ein verlorener Antwortversuch kann
mit derselben Auftrags-ID wiederholt werden; eine begrenzte, getrennt nach
create/edit geprüfte Quittung verhindert doppelte Änderungen. Das verlängert
nicht den Zugang. Eine Änderung bleibt auch bei 100 vorhandenen Regeln möglich.

Die ursprüngliche Regel wird kopiert. Nur übergebene Felder werden ersetzt.
Aktuelle Katalog- und native Rechteprüfungen werden wiederverwendet. Nicht
vollständig darstellbare oder nicht mehr berechtigte Bereiche bleiben sowohl
im Dialog als auch serverseitig geschützt; Metadaten können separat geändert
werden. Keine stillen Defaultwerte für Bestandsdaten. Ungültige Angaben nennen
Bereich und Position, ohne Werte offenzulegen.

CommitEditorChange/SaveEditorDraft bleiben der gemeinsame lokale Speicherpfad.
Andere Regeln, Folgen und Partnerfreigaben bleiben erhalten; eine ausdrückliche
verfügbare native Partnerauswahl behält ihre vorhandene Synchronisierungswirkung.
Ohne Änderungen bleibt der Entwurf unverändert. Auch eingeschaltete Entwürfe
werden nicht an Z2M gesendet oder aktiviert.

## Grenzen und Nachweise

Neue Zeitregeln und Folgenänderungen bleiben im nativen Formular. Ebenso Prüfung,
Übertragung, Aktivierung, Löschen und Laufzeitsteuerung. Der Dialog bearbeitet
eine Regel; er ist kein zweiter YAML-Speicher und keine Windows-Anwendung.

Synthetische PHP-Tests durch den echten Hook prüfen Erhaltung, Rechte, Kontext,
Wiederholungen, Grenzen und sichere Debug-Ausgaben. JavaScript-Tests prüfen
verlustfreie Differenzen sowie exakte Identitäten und typisierte Vorbelegung.
Die lokale Browserprüfung verwendet ausschließlich synthetische Modul-/Katalogdaten.
Eine reale Symcon-Abnahme nach dem Modulupdate bleibt separat beauftragt.
