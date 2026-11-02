# ADR 0015: Wiederverwendbare Aktionsfolgen im gemeinsamen Regelstand

## Status

Angenommen und lokal implementiert. Offline mit synthetischen Daten geprüft;
Installation und Bedienprüfung in einer nativen Symcon-Konsole stehen aus.

## Entscheidung

Das optionale Schema-1-Dokumentfeld `sequences` enthält höchstens 32 benannte
Aktionsfolgen mit stabiler ID und jeweils 1–16 Geräte-, Gruppen- oder Szenenaktionen.
Automationen verweisen mit `type: sequence` und `sequence_id` auf eine Definition.
Eine Definition enthält ausschließlich konkrete Aktionen. Verschachtelte Aufrufe
und damit Zyklen sind ausgeschlossen. Der Status meldet `sequence_version: 1`.

Definitionen und verwendende Regeln werden zusammen geprüft, atomar gespeichert
und an denselben aktivierten Snapshot gebunden. Eine Umbenennung erhält die ID.
Änderungen gelten nach Übertragung und Aktivierung für alle verwendenden Regeln.
Ein fehlender Verweis wird mit genauer Position abgewiesen. Ungenutzte Definitionen
dürfen vorhanden sein; verwendete Definitionen lassen sich im Klick-Editor erst
nach Entfernen sämtlicher Verwendungen löschen, auch bei deaktivierten Regeln.

Der native gemeinsame Formularbereich verwaltet Definitionen. Die vorhandenen
Aktionsdialoge wählen zwischen Geräteaktion und wiederverwendbarer Folge.
Geräte, Eigenschaften, Werte, Szenen und Wartezeiten verwenden dieselben Dialoge
und Schaltrechte wie bisher. Rechte werden auch für enthaltene Aktionen erneut
geprüft. Erweiterte oder aktuell nicht verfügbare Folgen bleiben unverändert.
Auswahlbindung und Entwurfsprüfsumme verhindern das Überschreiben veralteter
Bearbeitungen. Eine Änderung prüft die Schrittgrenze aller verwendenden Regeln.

## Laufzeit und Grenzen

Die vorhandene Engine bildet für jeden Lauf einen begrenzten Ausführungsplan.
Nach Auflösung aller Verweise darf eine Regel insgesamt höchstens 16 konkrete
Aktionen ausführen. Die Wartezeit am Aufruf addiert sich zur Wartezeit vor dem
ersten enthaltenen Schritt; zusammen höchstens 86400 Sekunden. Weitere Schritte
behalten ihre eigenen Wartezeiten. Die gespeicherte Definition wird nicht verändert.

Auslöser, Bedingungen, Sperrzeit, Laufzeitlimits, Einzelstart, gezielter und globaler
Stopp gehören weiterhin zur aufrufenden Regel. Bedingungen werden vor jedem
konkreten Schritt geprüft. Das Stoppen eines Laufs beendet dessen verbleibende
Schritte; andere Verwender derselben Definition bleiben unabhängig. Wiederanlauf
aktiviert den gespeicherten Regelstand einschließlich Definitionen, setzt aber
keine unterbrochenen Abläufe fort. Es entsteht kein zweiter Laufzeitbesitzer.

Entfernte konkrete Aktionen verwenden unverändert die Mesh-Freigaben und
Einmal-Challenges. Über Mesh werden ausschließlich einzelne Geräte-, Gruppen-
oder Szenenaktionen gesendet. Die Gegenseite erhält keine auszuführende Definition.

## Diagnose und Zuständigkeiten

Laufstatus verwendet die Position im aufgelösten Plan. Begrenzte Debugmeldungen
ergänzen die einsbasierte Definitionsposition `sequence` und den enthaltenen
Schritt `sequence_step`; gemeinsame Lauf-, Schritt- und Revisionsbezüge werden
auch über Mesh erhalten. Namen, Aktionswerte und Definitionstexte erscheinen
nicht im Debug. Die bestehende zeitliche Aufzeichnungsfreigabe bleibt maßgeblich.

Schema, Planauflösung, Editor und Tests bleiben librarylokal. Vorhandene zentrale
Formular-, DebugFlow- und Datenflusshelper werden unverändert verwendet.
Keine neue Abhängigkeit und keine zusätzliche Datei auf dem Z2M-Host.
