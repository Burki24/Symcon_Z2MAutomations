# Erste Beta: Einrichtung und Rückmeldungen

Diese Anleitung bereitet die erste Beta vor. Sie ist noch keine Veröffentlichung.
Den aktuellen Freigabestand nennt die [Beta-Abnahme](BETA_ABNAHME.md).

## Belegte Umgebung und Grenzen

- Native Praxisnachweise liegen für Symcon 9.1 mit PHP 8.5 vor. Der Benutzer
  hat zusätzlich die erfolgreiche Live-Praxis unter Symcon 9.0 bestätigt.
- Z2M 2.14.2-dev ist praktisch dokumentiert; der Benutzer bestätigt außerdem
  die erfolgreiche Live-Praxis mit der stabilen Version 2.14.2. Die genauen
  Git-Revisionen sind nicht zusätzlich dokumentiert.
- Zwei reale Z2M-Systeme sind geprüft. Drei und acht Erweiterungsinstanzen
  wurden zusätzlich synthetisch geprüft. Acht reale Broker sind damit nicht
  allgemein freigegeben.
- Browserdesigner und native Symcon-Kachel dienen der Administration. Eine
  eigene IPSView-Administrationsansicht wird nicht angeboten.

Einzelheiten stehen in [COMPATIBILITY.md](COMPATIBILITY.md). Die Beta darf nicht
als allgemeine Versions- oder Gerätefreigabe beschrieben werden.

## Einrichten

1. **Ausgangsstand sichern.** Die Symcon-Konfiguration und den Z2M-Datenordner
   einschließlich der bestehenden Automationen sichern. Notieren, welche Regeln
   bereits in Symcon oder Z2M laufen. Eine Test-Symcon-Installation am selben
   Broker arbeitet mit demselben Z2M-Regelspeicher wie die Produktion.
2. **MQTT-Verbindung bereitstellen.** Einen vorhandenen Symcon MQTT Client oder
   MQTT Server verwenden. Je Z2M-System das genaue Basistopic und die benötigten
   Abonnements prüfen. Mehrere Systeme dürfen denselben Broker nutzen.
3. **Z2M-Verbindungen anlegen.** Je System eine Instanz „Zigbee2MQTT Verbindung“
   erstellen, dem passenden MQTT-Gateway zuordnen, das Basistopic eintragen,
   die MQTT-Anbindung einschalten und Einstellungen übernehmen. Mit
   „Zigbee2MQTT-Verbindung prüfen“ die Antwort bestätigen lassen.
4. **Erweiterungsstand prüfen.** Den Erweiterungsstatus abfragen. Eine fehlende
   oder abweichende Erweiterung ausdrücklich installieren beziehungsweise
   aktualisieren. Dafür muss externes JavaScript in Z2M bereits freigeschaltet
   sein. Die Installation kann laufende Abläufe kurz unterbrechen. Erfolg und
   Geräteabruf abwarten; ein unbekannter Stand ist keine Updatebestätigung.
5. **Zentrale einrichten.** Eine Instanz „Zigbee2MQTT Automationszentrale“ ohne
   eigene MQTT-Schnittstelle anlegen. Die gewünschten Verbindungen unter
   „Verwenden“ anhaken und „Systemzuordnung übernehmen“ wählen. Höchstens acht
   Systeme zuordnen. Die Zuordnung allein importiert noch keine Entwürfe.
6. **Vorhandene Regeln einlesen.** Je System „Z2M-Automationen sicher einlesen“
   wählen, die Vorschau kontrollieren und die Verbindung ausdrücklich
   übernehmen. Vorhandene lokale Dokumente werden nicht stillschweigend ersetzt.
   Alte Einzelinstanzen bei Bedarf über den gesicherten
   [Übernahmeablauf](MIGRATION.md) behandeln.
7. **Designer öffnen.** Im nativen Formular den Browserdesigner öffnen oder die
   Zentrale in die native Symcon-Kachel aufnehmen. Zuerst das gewünschte System
   auswählen, dann Geräte, Entwurf und bestätigten Betrieb vergleichen. Fehlen
   Gerätedaten, diese für genau dieses System aktualisieren.

Die [Modulanleitung](../Z2MAutomations/README.md) beschreibt die einzelnen
Bedienelemente. Für getrennte Broker gilt zusätzlich die
[Mosquitto-Schrittfolge](../README.md#bis-zu-acht-mqtt-broker-miteinander-verbinden).
Die Library installiert und konfiguriert keine Broker.

## Erste Regel vorbereiten

„Neue Automation“ erzeugt zunächst eine ausgeschaltete Regel im lokalen
Symcon-Entwurf. Gerät, Eigenschaft beziehungsweise Kanal und Wert auswählen
oder einen Uhrzeit-/Sonnenauslöser einstellen. Bedingungen und Aktionen ergänzen.
Mit dem Übernehmen-Button des Editors werden diese Eingaben zum Entwurf;
der orangefarbene Symcon-Button übernimmt dagegen Moduleinstellungen.

Die weiteren Schritte sind getrennt:

1. **Prüfen:** Den Entwurf im ausgewählten Z2M-System validieren lassen.
2. **Speichern:** Den geprüften Entwurf nach eigener Kontrolle übertragen.
   Dies ersetzt dessen Automationsdatei und stoppt bei Änderungen die bisherige
   Ausführung dieses Systems. Gespeicherte Regeln sind dadurch nicht gestartet.
3. **Aktivieren:** Nur die gewünschten Regeln im Entwurf einschalten, den
   geänderten Stand erneut prüfen/speichern und den Automatikbetrieb ausdrücklich
   aktivieren. Zuvor eventuell konkurrierende Symcon-Ereignisse abgleichen.

Ein Einzelstart kann auch eine ausgeschaltete gespeicherte Regel ausführen und
Geräte schalten; er ist kein reiner Prüflauf. Bei systemübergreifenden Aktionen
müssen MQTT-Verbindung, gerichtete Partnerfreigabe und aktiver Empfangsbetrieb
auf dem Zielsystem zusammenpassen.

## Bei einem Fehler

- Eine abgelehnte Entwurfsprüfung verändert die gespeicherte Z2M-Datei nicht.
  Die konkrete Regel und das beanstandete Feld in der Fehlermeldung beachten.
- Bei unklarer Speicher- oder Ausführungsantwort zuerst den Status abfragen.
  Einen möglichen Schaltbefehl nicht blind wiederholen.
- Änderungen vor der Übertragung betreffen nur den lokalen Entwurf. Abbrechen
  verwirft offene Dialogeingaben; es setzt keine bereits übertragenen Regeln zurück.
- Der lokale Übernahme-Rückweg stellt keine alte Z2M-Datei wieder her. Nach einem
  möglichen zentralen Speicher-/Betriebsauftrag ist dieser Rückweg gesperrt;
  dann den tatsächlichen Z2M-Stand sichern und den betrieblichen Rückweg planen.
- Für eine gezielte Ablaufprüfung „Systeme und Diagnose“ öffnen und eine
  begrenzte Aufzeichnung starten. Die Meldungen stehen im nativen Debugfenster
  der genannten Z2M-Verbindung. Bei Mesh auf beiden Seiten aufzeichnen.

## Fehler melden

Bitte Symcon-/PHP-Version, genaue Z2M-Version und nach Möglichkeit Git-Revision,
installierten und mitgelieferten Erweiterungsbuild sowie den Bedienweg nennen.
Angeben, ob der Fehler im nativen Formular, Browserdesigner oder in der Kachel
auftritt, welches Ergebnis erwartet wurde und was tatsächlich passiert ist.

Nur die dazugehörigen bereinigten Debugzeilen weitergeben. Passwörter,
Designerzugänge, vollständige Sicherungen und private Gerätekennungen gehören
nicht in öffentliche Fehlerberichte. Ein hilfreicher Bericht beschreibt auch,
ob gespeicherte Regeln oder der Betrieb betroffen sind und ob der Fehler mit
einem ausgeschalteten Entwurf reproduzierbar ist.
