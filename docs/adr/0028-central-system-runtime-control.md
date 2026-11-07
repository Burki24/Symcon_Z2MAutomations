# ADR 0028: Betriebssteuerung je zentral verwaltetem Z2M-System

- Status: angenommen
- Datum: 2026-11-04
- Ergänzt ADR 0026 und ADR 0027

## Anlass

Die Zentrale kann Systementwürfe prüfen und speichern. Die bisherigen
Laufzeitbuttons gehören dagegen zum MQTT-gebundenen Einzelmodul. Ein
vorübergehender Austausch seiner Attribute oder des MQTT-Parents könnte
Bestätigungen und Aufträge anderer Systeme vermischen.

## Entscheidung

WorkspaceRuntime und WorkspaceRuntimeForm verwenden ausdrückliche Aufträge
für status, activate, deactivate, run_once und stop_rule. Native Oberfläche
und Browser nutzen denselben Einstieg mit System-ID, Generation, Dokument-
Fingerabdruck und aktuellem Betriebskontext. Keine Parent-Umschaltung und keine
zweite Ausführungsengine. ConnectionRuntimeControl verwendet die vorhandenen
MQTT-Frames, Erweiterungsbefehle und ExecutionContract-Snapshots.

Ein begrenztes dauerhaftes Journal wird vor Delegation geschrieben. Die
Verbindung erhält nur Besitzer und Auftragskennung; ihre reine Rückfrage bei
der Zentrale bestätigt genaue Bindung, Besitzer-Epoche und Dokumentstand.
Die Rückfrage liest keine Verbindung erneut, weil diese ihren Transport bereits
gesperrt hält. Aktionsrechte werden vor Annahme des Startauftrags mit dem
bestehenden Katalog-/Variablenprüfer geprüft. Systembindung und Dokument werden
vor jeder MQTT-Phase erneut geprüft.

Aktivierung und Einzelstart verlangen einen unveränderten, vollständig
übernommenen gespeicherten Entwurf. Frischer Empfängerstatus bestätigt Session,
Revision und Prüfsumme vor dem Befehl. Einmaltoken bleiben im Verbindungsworkflow;
die Zentrale erhält nur den Sitzungsvergleichswert. Aktivierung und globaler
Stopp werden erst nach einer weiteren passenden Statusantwort bestätigt.
Die Wiederanlaufoption wird ausdrücklich beim Aktivieren übergeben.

Globales Stoppen benötigt keinen passenden lokalen Entwurf und kann einen
ausstehenden Auftrag verdrängen. Einzelstopp verlangt zusätzlich die genaue
laufende UUID aus einer frischen Ausführungssicht. Einzelstart ändert keine
Regelfreigabe und lässt den Automatikbetrieb unverändert; Bedingungen prüft
weiterhin ausschließlich die vorhandene Z2M-Engine. Neuere sequenzierte
Laufmeldungen verdrängen ältere, niemals umgekehrt.

Browser-POSTs benötigen einen aktuellen Zugang mit Betriebsfreigabe sowie
gleichen Ursprung und JSON-Inhaltstyp. GET runtime-status liest nur den Cache.
Automatisches Nachlesen bleibt zeitlich begrenzt und sendet keinen neuen Befehl.
Späte Antworten eines anderen Systems werden verworfen. Native Buttons tragen
ebenfalls die System-ID und bei Einzelstopp die genaue Laufkennung.

## Abbruch und Rückweg

Neustart, Zuordnungswechsel und Fristablauf führen zu sichtbaren unsicheren oder
unterbrochenen Ergebnissen. Kein persistenter Auftrag wird erneut gesendet.
Ein ausdrücklicher frischer Status ist auch ohne vorher empfangenes bridge/state
möglich. Erst seine passende Antwort erlaubt weitere Befehle.

Bereits die persistierte Annahme eines möglichen Betriebswechsels sperrt den
rein lokalen Übernahme-Rückweg dieser Besitzer-Epoche. Ein verlorener Versand
oder eine geänderte Wiederanlaufdatei kann damit nicht durch eine lokale
Rücknahme verschleiert werden. Eine reine Statusabfrage sperrt den Rückweg nicht.
Entwürfe, Arbeitskopien, Auswahl und Partnerfreigaben bleiben unangetastet.

## Nachweis und Grenzen

Offline-Integration mit einem, zwei und acht Systemen, gemeinsamen/getrennten
Brokern, gleichen Regelkennungen, nativen Callback-Skripten und Browserrouten.
Fehlerfälle: verspätete/falsche Antworten, Neustart, verlorener Workflow,
Operation-Wiederholung, geänderte Session, gezielter Stopp eines ersetzten Laufs,
offene Eingaben und fehlende Browserberechtigung.

Zusätzlich wird das tatsächliche Erweiterungsartefakt über den vorhandenen
synthetischen Node-Peer verwendet: Aktivierung, Wiederanlaufeinstellung,
globaler Stopp, Einzelstart, Abbruch einer verzögerten Aktion und abgewiesene
Bedingung. Es wird kein synthetischer Gerätebefehl ausgeführt. Reale Abnahme in
nativer Testkonsole und Designer nach Modulupdate bleibt gesondert erforderlich.
Keine Installation und kein Erweiterungsartefakt werden hier geändert.
