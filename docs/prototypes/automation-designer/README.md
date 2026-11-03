# Automationsdesigner: klickbare Vorschau

Dieser Offline-Modus zeigt die eigene Konfigurationsoberfläche mit Navigation,
Ablauf und Eigenschaften. Alle Geräte, Regeln, Kanäle und Szenen sind erfundene
Beispiele. Die kanonischen Dateien liegen jetzt unter
Z2MAutomations/visualization. Der getrennte lesende Modulzugang lädt reader.js;
diese Offline-Vorschau lädt weiterhin designer.js mit Beispielen.

## Öffnen

[index.html](../../../Z2MAutomations/visualization/index.html) direkt im Browser öffnen. Alternativ aus dem
Repository-Verzeichnis einen lokalen Vorschau-Server starten:

```powershell
python -m http.server 7405 --bind 127.0.0.1 --directory Z2MAutomations/visualization
```

Danach `http://127.0.0.1:7405/` aufrufen. Es sind keine zusätzlichen Pakete nötig.
HTML, CSS, JavaScript und SVG-Symbole gehören zum Projekt; keine externen
Schriftarten, Skripte oder Dienste werden geladen.

## Ausprobieren

1. Links Geräteautomationen, Zeit & Astro oder Aktionsfolgen auswählen.
2. Einen Eintrag öffnen und in der Mitte einen Baustein anklicken.
3. Rechts Gerät, Eigenschaft beziehungsweise Kanal und Wert bearbeiten.
   Aktionen bieten nur beschreibbare Beispieleigenschaften an; Sensorwerte
   bleiben als Auslöser und Bedingungen verfügbar.
4. Aktionen hinzufügen und am gepunkteten Griff verschieben. Die Pfeilbuttons
   sind eine zusätzliche Möglichkeit. Die Wartezeit gehört immer zum Schritt.
5. Mit dem Drei-Punkte-Button neben dem Namen die gesamte Regel bearbeiten.
   Kopieren erzeugt einen ausgeschalteten Entwurf; Löschen fragt nach.
6. Oben prüfen, für dieses Fenster speichern und den Beispielbetrieb aktivieren
   oder stoppen. Fehlerhinweise nennen die Regel und öffnen deren Bearbeitung.

Zeitregeln zeigen mehrere Uhrzeiten oder Sonnenzeiten mit Zeitversatz und
Wochentagen. Aktionen unterstützen Beispielgeräte, Szenen und den Aufruf einer
wiederverwendbaren Folge. Verwendete Folgen können erst nach Entfernen ihrer
Aufrufe gelöscht werden. „Beispielstand zurücksetzen“ verwirft alle Änderungen.

## Grenzen dieses Schritts

- Änderungen und gespeicherte Vorschau liegen ausschließlich im Arbeitsspeicher
  des geöffneten Fensters. Neuladen stellt die mitgelieferten Beispiele wieder her.
- Prüfen kontrolliert nur die Eingaben dieses Entwurfs. Es ersetzt weder das
  tatsächliche YAML-Schema noch die Prüfung durch die Z2M-Erweiterung.
- Aktivieren und Stoppen ändern nur die sichtbare Betriebsanzeige. Es gibt keine
  laufenden Aktionen, Zeitgeber, Geräteabfragen oder Schaltbefehle.
- Keine Symcon-, MQTT-, WebHook- oder Dateispeicheranbindung. Ausgehende
  Verbindungen sind zusätzlich über die Content Security Policy gesperrt.
- „Hinweise zur Vorschau“ zeigt höchstens 30 Bedienmeldungen. Es ist kein
  Symcon-Debugfenster und keine Aufzeichnung der Z2M-Ausführung.
- Noch kein vollständiger Ersatz des nativen Editors: unter anderem fehlen
  Standortwahl, Zeitfenster, gemeinsame Gerätebefehle/Farben, Import,
  Partnerauswahl und Einzelsteuerung. Der lokale Zustand ist ein Ansichtsmodell,
  kein neuer öffentlicher YAML-Vertrag.
- Kein Kachelprojekt und keine IPSView-Integration. Ein eigener Tab innerhalb
  der nativen Symcon-Konsole wurde nicht als öffentliche Schnittstelle bestätigt.

Eine spätere Anbindung muss die vorhandenen Modulmethoden, Rechte und
Validierungen wiederverwenden. Der Server muss jede Eingabe erneut prüfen;
Browserzustand und Beispielprüfung sind keine Berechtigung. Dafür sind die
zentralen Asset-/HTML-Helper erneut zu bewerten und öffentliche Einstiege über
den vorhandenen DebugFlowHelper nachvollziehbar zu machen. Dieser Entwurf
führt bewusst keinen zweiten Automationsspeicher oder MQTT-Vertrag ein.

## Geprüft am 03.11.2026

Im lokalen Browser: Bearbeiten und erneute Vorbelegung, Geräte-/Kanal-/Wertwahl,
Szenen- und Folgenwahl, Astro/Wochentage, Anlegen, Kopieren, Löschbestätigung,
Fehlerführung, Prüf-/Speicher-/Betriebsanzeige. Tatsächliches Mausziehen und
Pfeilbedienung erhalten die Schrittwartezeit. Mausrad in Liste, Ablauf und
Eigenschaften jeweils abwärts/aufwärts ohne Bewegung der Nachbarbereiche.
Sichtkontrolle bei 1280, 1000, 760 und 390 Pixel Browserbreite, ohne seitlichen
Seitenüberlauf. Keine Browserwarnungen oder JavaScriptfehler im Prüflauf.
Die direkte Dateiöffnung, Touchbedienung, andere Browser sowie die Einbettung
in eine Symcon-Konsole sind noch nicht praktisch geprüft.
