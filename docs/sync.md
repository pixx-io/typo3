# pixx.io Sync-Prozess

Dieser Prozess synchronisiert Mediendateien zwischen pixx.io (Ihrer Digital Asset Management Plattform) und TYPO3 (Ihrem Content Management System).

## Voraussetzungen

Bevor der Sync gestartet werden kann, muss **mindestens eine** der folgenden Einstellungen in der Extension-Konfiguration aktiviert sein:

- **Update**: Erlaubt das Aktualisieren von Dateien auf neue Versionen
- **Delete**: Erlaubt das Löschen von Dateien, die in pixx.io nicht mehr existieren

Sie können auch beide Optionen gleichzeitig aktivieren. Wenn jedoch **beide Optionen deaktiviert** sind, wird der Sync mit einer entsprechenden Meldung abgebrochen, da keine Aktionen durchgeführt werden können.

**Empfehlung:**

- Für reine Metadaten-Synchronisation: **Update** aktivieren, **Delete** deaktivieren
- Für vollständige Synchronisation: Beide Optionen aktivieren (mit Vorsicht bei Delete!)

## Ablauf des Sync-Prozesses

### 1. Dateien aus der Datenbank laden

Der Prozess startet damit, dass alle Dateien aus TYPO3 geladen werden, die bereits mit pixx.io verknüpft sind. Dabei werden maximal so viele Dateien pro Durchlauf verarbeitet, wie in der Extension-Konfiguration unter "limit" definiert ist (Standard: 20, Maximum: 500). Die Verarbeitung beginnt mit den ältesten synchronisierten Dateien.

**Was wird geladen:**

- TYPO3 Datei-ID (UID)
- pixx.io Datei-ID
- Datei-Identifier (Pfad zur Datei)
- Letzte Synchronisations-Zeit

**Ausgabe:** "Got files from database"

Anschließend werden die Datei-IDs für die weitere Verarbeitung aufbereitet.

**Ausgabe:** "Mapped files from database to pixx.io IDs"

### 2. Prüfung auf vorhandene Dateien

Falls keine Dateien mit pixx.io-Verknüpfung gefunden werden, wird der Sync erfolgreich beendet.

**Ausgabe:** "No pixx.io files found"

### 3. Authentifizierung bei pixx.io

Mit dem in der Extension-Konfiguration hinterlegten Refresh-Token wird ein temporärer Access-Token von pixx.io angefordert. Dieser wird für alle folgenden API-Aufrufe verwendet.

**Ausgaben:**

- "Authenticate to pixx.io"
- "Authenticated"

**Mögliche Fehler:**

- Fehlende pixx.io-URL in der Konfiguration
- Fehlender oder ungültiger Refresh-Token
- Netzwerkprobleme

### 4. Existenz-Prüfung bei pixx.io

Für alle gefundenen Dateien wird bei pixx.io überprüft:

- **Existiert die Datei noch?** → Falls nein, wird sie zur Löschung vorgemerkt
- **Gibt es eine neuere Version?** → Falls ja, wird sie zur Aktualisierung vorgemerkt
- **Ist die Datei unverändert?** → Die Metadaten werden in jedem Fall später aktualisiert (siehe Schritt 9)

**Ausgabe:** "Check Existence and Version of X files on pixx.io" (wobei X die Anzahl der zu prüfenden Dateien ist)
**Tabellen-Ausgabe:** Es wird eine formatierte Tabelle mit folgenden Spalten angezeigt:
- TYPO3 UID
- pixx.io ID
- Identifier (Dateipfad)

Diese Tabelle gibt einen Überblick über alle Dateien, die im aktuellen Sync-Durchlauf verarbeitet werden.
**Wichtig:** Bei dieser Prüfung werden **nur die Versionsnummern** verglichen, nicht die Metadaten. Metadaten werden unabhängig vom Versionsstatus immer am Ende des Sync-Prozesses aktualisiert.

**Ergebnis:**

- Liste der zu löschenden Dateien (Dateien, die in pixx.io nicht mehr existieren)
- Liste der zu aktualisierenden Dateien (Dateien, bei denen eine neue **Datei-Version** vorliegt - neue Metadaten lösen keine Aktualisierung aus)

### 5. Übersicht der geplanten Änderungen

Vor der Durchführung wird eine Übersicht angezeigt:

**Ausgaben:**

- "Files to delete: X" (Anzahl der zu löschenden Dateien)
- "Files with a new version: X" (Anzahl der Dateien mit neuer Version)

### 6. Dateien löschen (falls aktiviert)

Wenn in der Extension-Konfiguration "Delete" aktiviert ist:

**Für jede zu löschende Datei:**

1. Datei wird aus dem TYPO3 File Storage gelöscht
2. Datei wird aus der Verarbeitungsliste entfernt
3. Die pixx.io-ID wird aus der Liste der zu verarbeitenden IDs entfernt

**Ausgabe pro Datei:** "File deleted: /pfad/zur/datei.jpg"

**Falls "Delete" deaktiviert ist:**

- **Ausgabe:** "File which should be deleted, but extension configuration is set to not delete files: [pixx.io-ID]"
- Die Datei bleibt erhalten

### 7. Dateien aktualisieren (falls aktiviert)

Wenn in der Extension-Konfiguration "Update" aktiviert ist:

**Für jede zu aktualisierende Datei:**

1. Die neue Version wird von pixx.io heruntergeladen
2. Die alte Datei in TYPO3 wird durch die neue ersetzt (gleicher Pfad, gleicher Name)
3. Die pixx.io-ID wird aktualisiert (falls sich diese geändert hat)
4. Die ID wird in der Verarbeitungsliste aktualisiert

**Ausgabe pro Datei:** "File updated: /pfad/zur/datei.jpg"

**Falls "Update" deaktiviert ist:**

- **Ausgabe:** "File which should be updated, but extension configuration is set to not update files: /pfad/zur/datei.jpg"
- Die alte Version bleibt erhalten

### 8. Metadaten abrufen

Für alle verbliebenen Dateien (die nicht gelöscht wurden) werden die aktuellen Dateiinformationen von pixx.io abgerufen.

**Ausgabe:** "Start to sync metadata: [kommagetrennte Liste der pixx.io-IDs]"

### 9. Metadaten synchronisieren

Für jede Datei werden die Metadaten von pixx.io in TYPO3 übertragen und gespeichert.

**Standard-Metadaten (immer):**

- Titel
- Beschreibung
- Alt-Text (für Barrierefreiheit, basierend auf konfiguriertem Feld)
- pixx.io Datei-ID
- Zeitstempel der letzten Synchronisation

**Erweiterte Metadaten (wenn Extension "filemetadata" installiert ist):**

- **Standort:** Stadt, Land, Region
- **GPS-Koordinaten:** Längen-/Breitengrad
- **Copyright-Informationen:** Copyright-Hinweis, Quelle
- **Ersteller-Informationen:** Fotograf/Ersteller, Erstellungsdatum, Änderungsdatum
- **Technische Daten:** Farbprofil, Einheit (px)
- **Weitere Informationen:** Bewertung/Ranking, Schlagwörter/Keywords, Herausgeber, Modell
- **Beschreibungen:** Caption (aus Beschreibung), Download-Name (aus Titel)

**Ausgabe pro Datei:** "Update metadata for [pixx.io-ID]"

### 10. Abschluss

Der Sync-Prozess ist abgeschlossen. Alle Änderungen wurden vorgenommen.

## Zeitpunkt der Ausführung

Der Sync kann auf zwei Arten ausgeführt werden:

### 1. Manuell über die Kommandozeile

```bash
php bin/typo3 pixxio:sync
```

Sie sehen alle Ausgaben in Echtzeit im Terminal.

### 2. Automatisch über den TYPO3 Scheduler

- Empfohlen: Täglich oder stündlich
- Verarbeitet jeweils die konfigurierten ältesten Dateien (Standard: 20)
- Läuft im Hintergrund ohne angemeldeten Benutzer
- Logs werden in die TYPO3-Log-Dateien geschrieben

**Wichtig:** Bei Scheduler-Ausführung gibt es keinen angemeldeten Backend-Benutzer, daher werden User-Informationen in den Logs als "0" oder "CLI/Scheduler" vermerkt.

## Verarbeitungslogik

### Wie viele Dateien werden pro Durchlauf verarbeitet?

Um die Server-Last zu begrenzen und Timeouts zu vermeiden, ist die Anzahl der pro Durchlauf synchronisierten Dateien konfigurierbar:

- **Standard:** 20 Dateien pro Durchlauf
- **Minimum:** 1 Datei
- **Maximum:** 500 Dateien
- **Konfiguration:** In der Extension-Konfiguration unter "limit" einstellbar

Bei regelmäßiger Ausführung (z.B. stündlich) stellt dies sicher, dass alle Dateien zeitnah aktuell gehalten werden.

**Beispiel:**

- Sie haben 100 Dateien mit pixx.io-Verknüpfung
- Bei stündlicher Ausführung mit Standard-Limit (20) sind alle Dateien nach ca. 5 Stunden einmal durchlaufen
- Dateien, die älter synchronisiert wurden, werden priorisiert

### Sortierung

Die Dateien werden nach dem Zeitstempel der letzten Synchronisation sortiert, wobei die ältesten zuerst verarbeitet werden. So wird sichergestellt, dass alle Dateien regelmäßig aktualisiert werden.

## Sicherheitsaspekte

### Schutz vor Datenverlust

- Dateien werden nur gelöscht, wenn dies explizit in der Konfiguration aktiviert wurde

### Schutz vor Überschreiben

- Dateien werden nur aktualisiert, wenn dies explizit aktiviert wurde
- Die alte Version wird durch die neue ersetzt (kein Versionsverlauf in TYPO3)
- Wenn Sie einen Versionsverlauf benötigen, sollten Sie ein zusätzliches Backup-System verwenden

### Bei Problemen

Wenn während des Sync-Prozesses ein Fehler auftritt:

- Der Prozess wird abgebrochen
- Bereits durchgeführte Änderungen bleiben bestehen
- Nicht verarbeitete Dateien werden beim nächsten Durchlauf erneut geprüft

## Häufige Probleme und Lösungen

### "Authentication to pixx.io failed"

**Ursache:** Der Refresh-Token ist ungültig oder abgelaufen, oder die pixx.io-URL ist falsch.

**Lösung:**

1. Überprüfen Sie die pixx.io-URL in der Extension-Konfiguration
2. Generieren Sie einen neuen Refresh-Token in pixx.io
3. Hinterlegen Sie den neuen Token in der Extension-Konfiguration

### "Please update extension configuration"

**Ursache:** Weder "Update" noch "Delete" ist in der Extension-Konfiguration aktiviert.

**Lösung:** Aktivieren Sie mindestens eine der beiden Optionen:

- **Update** für automatische Aktualisierungen
- **Delete** für automatisches Löschen (mit Vorsicht!)

### "File which should be deleted, but..."

**Ursache:** Eine Datei existiert nicht mehr in pixx.io, aber "Delete" ist deaktiviert.

**Bedeutung:** Dies ist eine Warnung, keine Fehler. Die Datei bleibt erhalten.

**Lösung:** Entscheiden Sie, ob Sie:

- Die Option "Delete" aktivieren möchten
- Die Datei manuell in TYPO3 löschen möchten
- Die Datei behalten möchten (dann können Sie die Warnung ignorieren)

### "File which should be updated, but..."

**Ursache:** Eine Datei hat eine neue Version in pixx.io, aber "Update" ist deaktiviert.

**Bedeutung:** Dies ist eine Warnung, keine Fehler. Die alte Version bleibt erhalten.

**Lösung:** Entscheiden Sie, ob Sie:

- Die Option "Update" aktivieren möchten
- Die Datei manuell aktualisieren möchten
- Die alte Version behalten möchten (dann können Sie die Warnung ignorieren)

## Empfohlene Einstellungen

### Für den produktiven Einsatz

**Extension-Konfiguration:**

- ✅ **Update**: Aktiviert (damit Dateien immer aktuell bleiben)
- ⚠️ **Delete**: Mit Vorsicht aktivieren (nur wenn Sie sicher sind, dass gelöschte Dateien nicht mehr benötigt werden)
- **Subfolder**: Definieren Sie einen Unterordner für pixx.io-Dateien (z.B. "pixxio/")
- **Alt Text Field**: Konfigurieren Sie das Feld für Alt-Texte (z.B. "Alt Text (Accessibility)")

**Scheduler-Einstellungen:**

- **Häufigkeit**: Täglich oder stündlich (je nach Anzahl der Dateien)
- **Zeitpunkt**: Außerhalb der Hauptnutzungszeiten (z.B. nachts um 2 Uhr)
- **Priorität**: Normal (nicht zeitkritisch)

**Monitoring:**

- Richten Sie ein Monitoring für die Log-Dateien ein
- Prüfen Sie regelmäßig auf Fehler oder Warnungen
- Überwachen Sie die Anzahl der synchronisierten Dateien

### Für Test-/Entwicklungsumgebungen

- **Delete**: Deaktiviert (Sicherheit beim Testen)
- **Update**: Aktiviert (aber bewusst testen)
- Führen Sie Sync manuell aus, um Ausgaben zu sehen

## Technische Details

| Parameter             | Wert                  | Beschreibung                               |
| --------------------- | --------------------- | ------------------------------------------ |
| **Verarbeitungsrate** | 20 Dateien (Standard) | Pro Sync-Durchlauf, konfigurierbar (1-500) |
| **Sortierung**        | Nach Sync-Zeitstempel | Älteste zuerst                             |
| **Timeout**           | Variabel              | Abhängig von Dateigröße und Netzwerk       |
| **API-Limits**        | Vertragabhängig       | Siehe pixx.io-Vertrag                      |
| **Storage**           | Konfigurierbar        | Standard: Storage ID 1                     |
| **Metadaten-Mapping** | Fest definiert        | Siehe Abschnitt 9                          |

## Metadaten-Mapping im Detail

Die folgende Tabelle zeigt, welche pixx.io-Felder auf welche TYPO3-Felder gemappt werden:

| pixx.io-Feld    | TYPO3-Feld (filemetadata) | Datentyp              |
| --------------- | ------------------------- | --------------------- |
| City            | location_city             | Text                  |
| Country         | location_country          | Text                  |
| Region          | location_region           | Text                  |
| latitude        | latitude                  | Dezimalzahl           |
| longitude       | longitude                 | Dezimalzahl           |
| CopyrightNotice | copyright                 | Text                  |
| Model           | creator_tool              | Text                  |
| Source          | source                    | Text                  |
| ColorSpace      | color_space               | Text                  |
| Publisher       | publisher                 | Text                  |
| creator         | creator                   | Text                  |
| keywords        | keywords                  | Komma-getrennte Liste |
| rating          | ranking                   | Zahl                  |
| createDate      | content_creation_date     | Unix-Timestamp        |
| modifyDate      | content_modification_date | Unix-Timestamp        |
| subject         | title, download_name      | Text                  |
| description     | description, caption      | Text                  |

**Hinweis:** Die erweiterten Metadaten werden nur synchronisiert, wenn die TYPO3-Extension "filemetadata" installiert ist.

## Support und Troubleshooting

### Log-Dateien prüfen

**PHP Error Log:**

- Speicherort variiert je nach Server-Konfiguration
- Enthält Fehler und API-Responses im Fehlerfall

**TYPO3 System Log:**

- Pfad: `var/log/typo3_*.log`
- Enthält detaillierte Informationen zum Sync-Prozess

### Debug-Modus

Für detaillierte Fehleranalysen können Sie den Sync manuell ausführen:

```bash
# Im Terminal ausführen
cd /pfad/zu/ihrem/typo3
php bin/typo3 pixxio:sync
```

Sie sehen dann alle Ausgaben in Echtzeit.
