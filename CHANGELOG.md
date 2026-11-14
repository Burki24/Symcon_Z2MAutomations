# Unveröffentlicht

Vorbereiteter Umfang der ersten Beta. Versionsnummer und Veröffentlichungsdatum
werden erst dem bestätigten Veröffentlichungscommit zugeordnet; die automatische
Versionierung der Library bleibt maßgeblich.

## Neu

- Im Browserdesigner und in der Kachel den lokalen Entwurf in Schritt 6 direkt
  aus dem ausgewählten Z2M-System aktualisieren. Eigene Änderungen bleiben bis
  zur ausdrücklichen Ersetzungsbestätigung erhalten; eine private Sicherung
  ermöglicht die lokale Wiederherstellung. Die Übermittlung nach Z2M bleibt separat.

- Nummerierter Arbeitsplatz im Browserdesigner und in der Kachel: Einrichtung,
  Entwurf, Prüfung, bestätigte Übermittlung und Betrieb haben eigene Bereiche.
  Fehler lassen sich an der benannten Stelle korrigieren; Freigabeschalter sind
  direkt unter Betrieb erreichbar. Der Übermittlungsablauf führt nach Bestätigung
  durch Statusprüfung, nötigen Stopp, erneute Prüfung und bestätigtes Speichern.
  Der Start bleibt separat. Sprache wird zentral gewählt, YAML bei Bedarf in
  Schritt 5 exportiert. Vorhandene Symcon-Verbindungen und Entwürfe bleiben erhalten.

- The shared Z2M extension supports device-value conditions on explicitly
  permitted partner systems. Each check asks the owning extension for a fresh
  decision; missing values, stopped receivers and timeouts prevent the action.
  The desktop app exposes this selection; the Symcon forms remain unchanged.

- Im Browserdesigner und in der Kachel Automationen in einem Formular erstellen
  und den vollständigen Systementwurf optional als YAML herunterladen. Neue
  Regeln beginnen ausgeschaltet. Die Übernahme nach Zigbee2MQTT bleibt separat.

- Eigene, automatisch steigende Erweiterungsversion ab 0.2.0. Geänderte
  Erweiterungsquellen erhöhen beim Bauen die letzte Versionszahl; unveränderte
  Builds behalten ihre Version. Verbindung, Zentrale und Webdesigner zeigen
  Versionsnummer und Buildkennung gemeinsam an.

- Je Automation wählen, ob ein wartender Lauf nach einem Z2M-Neustart fortgesetzt
  wird. Bei bereits abgelaufener Wartezeit lässt sich die ausstehende Aktion
  nachholen oder der Lauf beenden. Bereits erledigte Aktionen werden nicht
  wiederholt. Standard bleibt der Abbruch des unterbrochenen Laufs.
- Eine Automationszentrale verwaltet bis zu acht Z2M-Systeme mit getrennten
  Entwürfen und vorhandenen Symcon-MQTT-Verbindungen. Geräte und Kanäle können
  im Browserdesigner gesucht und ohne YAML-Kenntnisse ausgewählt werden.
- Geräteereignisse, Messwerte, mehrere Uhrzeiten und Sonnenzeiten mit Zeitversatz
  als Auslöser. Mehrere Geräte können dieselbe Regel unabhängig starten.
- Bedingungen für Gerätewerte, Wochentage und Zeitfenster; Geräte und Gruppen
  schalten, Helligkeit und Farbe setzen sowie vorhandene Z2M-Szenen aufrufen.
- Aktionsschritte mit Wartezeiten und wiederverwendbare Aktionsfolgen.
  Reihenfolge per Drag & Drop oder Pfeilbuttons ändern.
- Regeln erstellen, bearbeiten, kopieren, löschen und filtern. Neue Regeln und
  Kopien beginnen ausgeschaltet. Unterstützte Symcon-Ereignisse lassen sich nach
  Vorschau als ausgeschaltete Entwürfe übernehmen; Originalereignisse bleiben erhalten.
- Gerichtete Partnerfreigaben für Aktionen zwischen Z2M-Systemen. Bestehende
  Freigaben bleiben beim Bearbeiten erhalten; vor einem Entzug werden betroffene
  Regeln angezeigt. Getrennte Broker benötigen eine passende MQTT-Bridge.
- Entwürfe ausdrücklich prüfen und speichern, gespeicherte Regeln aktivieren
  oder stoppen und einzelne Abläufe mit Bedingungsprüfung einmal starten.
  Bei erneuter Auslösung ignorieren, neu starten oder die Wartezeit verlängern.
- Automatischer Wiederanlauf nach Z2M-Neustart ist einstellbar. Aktivierte Regeln
  laufen in der eigenen Z2M-Erweiterung und benötigen kein laufendes Symcon.
- Nach einem Z2M-Neustart wird der Erweiterungs- und Betriebsstatus automatisch
  bestätigt. Falls die Erweiterung noch startet, wird nur die lesende Statusabfrage
  höchstens dreimal versucht. Manuelle Aufträge haben Vorrang; Schalt- und
  Speicheraufträge werden nicht wiederholt.
- Erweiterungsinstallation je System mit Bestätigung, Buildvergleich und
  anschließendem Geräteabruf. Zeitbegrenzte Debug-Aufzeichnungen erklären
  Abläufe und systemübergreifende Aktionen.
- Gemeinsamer Browserdesigner auf Deutsch und Englisch, mit Hell-/Dunkelansicht,
  responsivem Layout und Sprachwechsel ohne Verlust offener Eingaben.
  Derselbe Designer ist zusätzlich in der nativen Symcon-Kachel verfügbar.
- Vorhandene Z2M-Automationen sicher einlesen und bisherige Einzelinstanzen
  mit privaten Sicherungen und lokalem Rückweg übernehmen. Die ausführliche
  Einrichtung mehrerer Mosquitto-Broker steht in der Verbindungsanleitung.

## Fixes

- Hinweise zu fehlenden oder unbestätigten Gerätedaten nennen im Browserdesigner
  und in der Kachel Schritt 4 und die tatsächliche Aktualisierungsschaltfläche.
  Die hervorgehobene Schaltfläche steht direkt im Warnhinweis unter der
  Systemauswahl und öffnet den Bereich. In Schritt 4 entfällt der zusätzliche
  Navigationshinweis; dort ist „Gerätedaten aktualisieren“ hervorgehoben.
  Andere Hinweise und Fehler bleiben sichtbar. Der Abruf startet erst über
  die ausdrücklich gewählte Aktualisierung.

- Zentral verwaltete Z2M-Verbindungen zeigen anstelle des gesperrten
  Installationsbuttons die zuständige Automationszentrale mit Name und Instanz-ID.
  Dadurch erscheint keine wirkungslose Sicherheitsabfrage mehr. Eigenständige
  Verbindungen behalten die Installation mit ausdrücklicher Bestätigung.

- Entwürfe, Auswahl und offene Eingaben bleiben bei Systemwechseln, Geräteabruf
  und Zugangserneuerung erhalten. Veraltete oder parallele Aufrufe werden
  verständlich abgewiesen, ohne unbestätigte Schreibbefehle erneut zu senden.
- Detaillierte Prüfungsfehler nennen die betroffene Regel und Angabe und bleiben
  bei Statusabfragen erhalten. Entwurf, gespeicherter Stand und Automatikbetrieb
  werden in der Oberfläche getrennt angezeigt.
- Mausradbedienung an verschachtelten Scrollgrenzen sowie Darstellung und
  Texttransport in der nativen Kachel korrigiert.
- Gateway-/Topicauswahl, einzelne Gerätekanäle, Vorbelegung vorhandener Regeln
  und die Bedienung des Designers über HTTP im lokalen Netzwerk korrigiert.

Die unveränderte bisherige Entwicklungsübersicht steht im
[Entwicklungsverlauf](docs/history/CHANGELOG_DEVELOPMENT.md).
