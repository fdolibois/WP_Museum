# Code- und Sicherheitsanalyse — Virtuelles Museum Plugin
**Version:** 2.0.0  
**Analysedatum:** 2026-03-31  
**Analyseumfang:** 21 Quellcode-Dateien (PHP, JavaScript, CSS)  
**Analysierte Bereiche:** Codequalität · Code-Aktualität · OWASP Top 10 · Allgemeine Sicherheit

---

## Kapitel 1: Zusammenfassung für Nicht-Techniker

### Was wurde untersucht?

Das WordPress-Plugin „Virtuelles Museum" wurde einer vollständigen Prüfung unterzogen. Dabei wurde der Quellcode auf Sicherheitslücken, veraltete Programmiertechniken und die allgemeine Qualität des Codes untersucht — ähnlich wie eine technische Inspektion oder ein TÜV für Software.

### Das Wichtigste in Kürze

Das Plugin ist **grundsätzlich solide gebaut** und folgt in weiten Teilen den Empfehlungen für sicheres WordPress-Entwickeln. Die eingesetzten Technologien sind modern und zeitgemäß. Es wurden jedoch **drei sicherheitsrelevante Probleme** gefunden, die behoben werden sollten, bevor das Plugin auf einer öffentlich zugänglichen Website eingesetzt wird.

### Ampelbewertung

| Bereich | Bewertung | Erläuterung |
|---|---|---|
| Allgemeine Sicherheit | 🟡 Gelb | 3 ernste Probleme vorhanden, behebbar |
| Schutz vor Hackerangriffen | 🟡 Gelb | Eine Schutzfunktion ist lückenhaft |
| Datenschutz & Datensicherheit | 🟢 Grün | Keine Datenlecks gefunden |
| Codequalität | 🟢 Grün | Gut strukturiert, wartungsfreundlich |
| Modernität des Codes | 🟢 Grün | Aktuelle Technologien eingesetzt |
| Zugriffsschutz (Admin-Bereich) | 🟢 Grün | Berechtigungen korrekt implementiert |

### Die drei dringendsten Probleme — verständlich erklärt

**Problem 1: Eine Sicherheitssperre kann umgangen werden**  
Beim Laden von Museumsobjekten auf der Website gibt es eine Sicherheitskennung (ähnlich einem Stempel), die prüft, ob die Anfrage wirklich von der eigenen Website kommt. Diese Prüfung lässt sich aktuell umgehen, indem der Stempel einfach weggelassen wird. Ein Angreifer könnte so Daten abfragen, ohne die normale Websitenutzung zu durchlaufen. Die Behebung erfordert eine einzeilige Codeänderung.

**Problem 2: Beim Bildimport könnte der Server missbraucht werden**  
Wenn Bilder per CSV-Datei importiert werden, lädt das Plugin die Bilder von den angegebenen Web-Adressen herunter. Aktuell wird nicht ausreichend geprüft, ob diese Adressen seriös sind. Ein Administrator, der eine manipulierte CSV-Datei importiert, könnte das System dazu bringen, Daten von internen Servern oder sensiblen Netzwerkadressen abzurufen. Betroffen wären nur Benutzer mit Administrator-Rechten.

**Problem 3: Suchergebnisse könnten schädlichen Code enthalten**  
Das Live-Suchfeld der Museumsseite zeigt Ergebnisse an, ohne die Texte vorher ausreichend zu bereinigen. Wenn ein Objekt-Titel spezielle Zeichen enthält, könnten diese vom Browser als Programmbefehle interpretiert werden (sog. „Cross-Site-Scripting"). In der Praxis ist das Risiko gering, da die Daten aus der eigenen Datenbank stammen — aber es sollte trotzdem behoben werden.

### Was ist gut?

- **Alle Administrator-Formulare** sind gegen gefälschte Klicks geschützt (CSRF-Schutz).
- **Datenbankabfragen** sind durchgängig gegen SQL-Injection gesichert.
- **Alle Ausgaben** in den PHP-Templates werden korrekt bereinigt, bevor sie dem Benutzer angezeigt werden.
- **Der Code ist modern** und nutzt aktuelle PHP 8.1-Funktionen korrekt.
- Das Plugin ist **klar strukturiert** und gut wartbar.

### Empfehlung

Die drei dringenden Probleme sollten vor dem Produktiveinsatz behoben werden. Der Aufwand ist überschaubar — alle Korrekturen sind gezielte Eingriffe in bestehenden Code, keine Neuarchitektur.

---

## Kapitel 2: Technische Gesamtübersicht

### Befunde nach Schweregrad

| Schweregrad | Anzahl |
|---|---|
| 🔴 Kritisch | 3 |
| 🟠 Hoch | 6 |
| 🟡 Mittel | 11 |
| 🔵 Niedrig | 10 |
| ℹ️ Info | 5 |
| **Gesamt** | **35** |

### OWASP Top 10 — Mapping

| OWASP-Kategorie | Befunde |
|---|---|
| A01 Broken Access Control | B003, B006, B010 |
| A03 Injection (XSS / SQLi) | B004, B009, B026 |
| A04 Insecure Design | B005, B007, B008, B012 |
| A05 Security Misconfiguration | B002, B013, B016 |
| A07 Auth Failures | B001 |
| A10 SSRF | B002 |

---

## Kapitel 3: Kritische Befunde (sofortiger Handlungsbedarf)

---

### B001 — Nonce-Prüfung umgehbar (CSRF-Lücke)
**Datei:** `includes/class-vm-ajax.php`, Zeile 237–241  
**Schweregrad:** 🔴 Kritisch  
**OWASP:** A07 — Identification and Authentication Failures  

**Problem:**  
Die öffentliche AJAX-Aktion `vm_load_objects_page` prüft den Sicherheits-Nonce nur dann, wenn er mitgeschickt wird:
```php
// Aktuell (fehlerhaft):
if ( $nonce && ! wp_verify_nonce( $nonce, 'vm_lazy_load' ) ) {
    wp_send_json_error( [], 403 );
}
```
Wenn kein `nonce`-Feld gesendet wird, ist `$nonce` ein leerer String — die `if`-Bedingung ist falsch, die Prüfung wird übersprungen. Jede Anfrage ohne Nonce wird akzeptiert.

**Behebung:**
```php
// Korrekt:
if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'vm_lazy_load' ) ) {
    wp_send_json_error( [], 403 );
}
// Oder noch besser — WordPress-Standard:
check_ajax_referer( 'vm_lazy_load', 'nonce' );
```

---

### B002 — SSRF beim Bild-Sideloading
**Datei:** `includes/class-vm-bulk-import.php`, Zeile 147–163  
**Schweregrad:** 🔴 Kritisch  
**OWASP:** A05 Security Misconfiguration, A10 SSRF  

**Problem:**  
`sideload_featured_image()` lädt beliebige URLs aus CSV-Dateien herunter. `esc_url_raw()` verhindert keine SSRF-Angriffe — lokale Adressen (`http://127.0.0.1/`, `http://169.254.169.254/` AWS Metadata, `file://`-Protokoll) werden nicht blockiert.

**Behebung:**
```php
private static function is_safe_url( string $url ): bool {
    $parsed = wp_parse_url( $url );
    if ( ! in_array( $parsed['scheme'] ?? '', [ 'http', 'https' ], true ) ) return false;
    if ( ! wp_http_validate_url( $url ) ) return false;
    // Private IP-Ranges blockieren
    $host = gethostbyname( $parsed['host'] ?? '' );
    return ! filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false;
}
```
Vor dem Download: `if ( ! self::is_safe_url( $url ) ) return false;`

---

### B003 — Fehlende Zugriffskontrolle in öffentlichen AJAX-Endpunkten
**Datei:** `includes/class-vm-ajax.php`, Zeilen 163–235  
**Schweregrad:** 🔴 Kritisch  
**OWASP:** A01 — Broken Access Control  

**Problem:**  
Die fünf öffentlichen AJAX-Aktionen (`get_room_contents`, `get_vitrine_contents`, `get_gallery_objects`, `get_object_contexts`, `search`) enthalten kein Rate-Limiting. Massenhafte automatisierte Abfragen (Data Harvesting) sind möglich. Zudem gibt `format_post()` Copyright-Metadaten an nicht authentifizierte Nutzer zurück.

**Behebung:**  
- Transient-basiertes Rate-Limiting pro IP implementieren.  
- In `format_post()`: Copyright-Felder nur für angemeldete Nutzer zurückgeben.  
- Mindestsuchlänge server-seitig erzwingen: `if ( strlen( $query ) < 2 ) { wp_send_json_success(['results'=>[]]); }`

---

## Kapitel 4: Hohe Schweregrade

---

### B004 — XSS in Live-Suche (JavaScript)
**Datei:** `public/assets/js/vm-search.js`, Zeilen 39–47  
**Schweregrad:** 🟠 Hoch | OWASP A03  

Suchergebnisse werden über `innerHTML` in den DOM geschrieben, ohne dass `item.title`, `item.url` oder `item.thumb` client-seitig escaped werden. Ein `javascript:`-URL als `item.url` wäre ein klassischer XSS-Vektor.

**Behebung:** DOM-API statt String-Konkatenation verwenden:
```javascript
const a = document.createElement('a');
a.href = item.url; // Browser validiert URL-Schema
a.textContent = item.title; // automatisch escaped
```

---

### B005 — Fehlerbehandlung in Datenbank-Transaktionen
**Datei:** `includes/class-vm-relations.php`, Zeile 92–130  
**Schweregrad:** 🟠 Hoch | OWASP A04  

`set_children()` verwendet MySQL-Transaktionen direkt. Einzelne `$wpdb->insert()`-Fehler werden nicht geprüft — bei einem fehlgeschlagenen Insert wird die Schleife still fortgesetzt und anschließend ein `COMMIT` ausgeführt, was zu inkonsistenten Datenbankzuständen führt.

**Behebung:** Rückgabewert jedes `$wpdb->insert()` prüfen; bei `false` Exception werfen und `ROLLBACK` auslösen. `$wpdb->last_error` nach kritischen Operationen prüfen.

---

### B006 — Fehlende Post-spezifische Berechtigungsprüfung
**Datei:** `includes/class-vm-ajax.php`, Zeilen 30, 63, 105  
**Schweregrad:** 🟠 Hoch | OWASP A01  

In `add_relation()`, `remove_relation()` und `reorder_relations()` wird nur `current_user_can('edit_posts')` geprüft, nicht aber `current_user_can('edit_post', $post_id)` für die spezifischen Post-IDs. Ein Redakteur ohne Schreibrechte auf bestimmte Posts könnte deren Relationen manipulieren.

**Behebung:**
```php
if ( ! current_user_can( 'edit_post', $parent_id ) || ! current_user_can( 'edit_post', $child_id ) ) {
    wp_send_json_error( [ 'message' => 'Keine Berechtigung.' ], 403 );
}
```

---

### B007 — CSV-Import ohne Mengenbeschränkung (DoS-Risiko)
**Datei:** `includes/class-vm-bulk-import.php`, Zeilen 27–44  
**Schweregrad:** 🟠 Hoch | OWASP A04  

`parse_csv()` liest die gesamte CSV ohne Zeilenlimit in ein PHP-Array. Bei 100.000 Zeilen: 100.000 `wp_insert_post()`-Aufrufe, Speichererschöpfung, Server-Timeout.

**Behebung:**
```php
// Dateigrößencheck vor dem Öffnen
if ( filesize( $file ) > 5 * 1024 * 1024 ) {
    return [ 'status' => 'error', 'message' => 'Datei zu groß (max. 5 MB).' ];
}
// fgetcsv mit explizitem Längenlimit und Zeilenzähler
$line_count = 0;
while ( ( $line = fgetcsv( $handle, 2000, ',', '"', '\\' ) ) !== false ) {
    if ( ++$line_count > 2000 ) break; // Max. 2000 Zeilen
    ...
}
```

---

### B008 — Fehlende MIME-Typ-Prüfung beim Datei-Upload
**Datei:** `admin/views/page-import.php`, Zeile 8  
**Schweregrad:** 🟠 Hoch | OWASP A04  

`$_FILES['import_file']['tmp_name']` wird ohne Prüfung per `is_uploaded_file()` verarbeitet. Das HTML `accept=".csv"` ist kein Sicherheitsmerkmal.

**Behebung:**
```php
if ( ! is_uploaded_file( $_FILES['import_file']['tmp_name'] ) ) {
    wp_die( 'Ungültige Datei.' );
}
$file_type = wp_check_filetype( $_FILES['import_file']['name'] );
if ( ! in_array( $file_type['ext'], [ 'csv', 'txt' ], true ) ) {
    wp_die( 'Nur CSV-Dateien erlaubt.' );
}
```

---

### B009 — Template Include im AJAX-Kontext ohne Scope-Isolation
**Datei:** `includes/class-vm-ajax.php`, Zeilen 272–280  
**Schweregrad:** 🟠 Hoch | OWASP A03  

`include $template` wird mit manipuliertem globalem `$post` ausgeführt. Bei Fehlern im Template wird `wp_reset_postdata()` nie aufgerufen, was den globalen Post-Kontext dauerhaft beschädigt.

**Behebung:** `try/finally` sicherstellen:
```php
try {
    global $post;
    foreach ( $posts as $post ) {
        setup_postdata( $post );
        ob_start();
        include $template;
        $html .= ob_get_clean();
    }
} finally {
    wp_reset_postdata();
}
```

---

## Kapitel 5: Mittlere Befunde

| ID | Datei | Problem | Empfehlung |
|---|---|---|---|
| B010 | `class-vm-rest-api.php:18` | REST-API ohne Pagination-Limit: `numberposts: -1` | `per_page`-Parameter mit Max. 100 einführen |
| B011 | `class-vm-ajax.php:207` | Suche ohne Server-seitiges Mindestzeichen-Limit | `if (strlen($q) < 2) return` vor WP_Query |
| B012 | `admin/class-vm-admin.php:75` | `save_settings()`: Enum-Felder nur `sanitize_text_field()`, keine Allowlist-Prüfung | `in_array($raw, ['sections','flat','tabs'], true)` wie in `save_meta_boxes()` |
| B013 | `class-vm-activator.php:60` | `SMALLINT` für Jahreszahlen: Bereich -32768..32767 (archäologische Objekte v.Chr. grenzwertig) | `INT` oder `MEDIUMINT` verwenden |
| B014 | `class-vm-relations.php:408` | Zirkularitätsprüfung nur für direkten Selbstverweis, keine echte Zykelerkennung | Kommentar mit Begründung ergänzen; Rekursionstiefe begrenzen |
| B015 | `single-museum-room.php:17` | `$context_param` aus `$_GET` wird gelesen aber im Template nicht verwendet | Entweder vollständig implementieren oder entfernen |
| B016 | `class-vm-widgets.php:52` | `$args['before_widget']` wird ohne Escaping ausgegeben | WordPress-Konvention; dokumentieren als trusted HTML |
| B017 | `class-vm-ajax.php:343` | `get_pending_image_ids(0)` lädt alle IDs für Count-Operation | Separate `COUNT`-Query mit `found_posts` statt Array laden |
| B018 | `admin/views/page-import.php:86` | JavaScript inline im PHP-Template statt in externer `.js`-Datei | In `vm-import.js` auslagern, per `wp_enqueue_script()` laden |
| B019 | `virtual-museum.php:4` | Plugin URI enthält typografisches Komma statt `/` | `https://yourinsight.digital/` korrigieren |
| B020 | `class-vm-ajax.php:296` | Variablenname `$count` enthält Array, nicht Integer | Separate COUNT-Query oder `found_posts` verwenden |

---

## Kapitel 6: Niedrige Befunde und Info

| ID | Datei | Kategorie | Problem |
|---|---|---|---|
| B021 | `class-vm-plugin.php:45` | Performance | `create_museum_page()` läuft bei jedem Request — Object-Cache nutzen |
| B022 | `class-vm-relations.php:419` | Performance | 50 Cache-Delete-Operationen pro Relation-Änderung — Cache-Group-Flush oder Versionskey |
| B023 | `class-vm-blocks.php:249` | Code-Duplizierung | `render_cards()` und AJAX-Loop identisch — gemeinsame Hilfsmethode |
| B024 | `class-vm-widgets.php:454` | Logikfehler | `$show_rooms = !empty(...) ? 1 : 1` — immer 1, Checkbox nie speicherbar als deaktiviert |
| B025 | `page-museum-entrance.php:76` | Logikfehler | `!empty(...) !== false` ist immer `true` — Bedingung ist totes Prüfmuster |
| B026 | `vm-lightbox.js:15` | XSS (gering) | `innerHTML` mit Template-Literal für i18n-Text — DOM-API bevorzugen |
| B027 | `class-vm-shortcodes.php:135` | Logik | `is_numeric("1.5")` gibt `true` zurück — `ctype_digit()` stattdessen |
| B028 | `class-vm-meta-boxes.php:291` | Stil | Emojis direkt echo ohne `esc_html()` — inkonsistent |
| B029 | `single-museum-room.php:159` | Stil | `echo count($vitrines)` ohne `esc_html()` — inkonsistent |
| B030 | `class-vm-blocks.php:278` | Stil | Inline-CSS aus Attributen ohne `esc_attr()` — fragiles Pattern |
| B031 | `class-vm-widgets.php` (alle) | Aktualität | `WP_Widget`-API seit WP 5.8 durch Block Widgets ersetzt; dupliziert Block-Funktionalität |
| B032 | `class-vm-ajax.php:343` | Aktualität | `meta_query` in alter Array-Syntax — neuere WP_Meta_Query-Syntax bevorzugen |
| B033 | gesamtes Plugin | Positiv | PHP 8.1-Features korrekt eingesetzt (`match`, Union Types, Arrow Functions) |
| B034 | `class-vm-rest-api.php:150` | Aktualität | `post_date` als MySQL-String statt ISO 8601 — `mysql2date(DATE_ATOM, ...)` |
| B035 | `vm-lazy.js:9` | Aktualität | Kein JS-Bundler — für Wachstum `wp-scripts` einführen |

---

## Kapitel 7: Positive Befunde

Das Plugin zeigt in vielen Bereichen vorbildliche Implementierungen:

✅ **SQL-Injection vollständig verhindert** — alle `$wpdb`-Abfragen nutzen `$wpdb->prepare()` mit Platzhaltern  
✅ **Konsequentes Output-Escaping** — `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` durchgängig in PHP-Templates  
✅ **CSRF-Schutz in Admin-Formularen** — alle Admin-AJAX-Aktionen nutzen `check_ajax_referer()`; alle Formulare `wp_nonce_field()`  
✅ **Berechtigungsprüfungen** — `current_user_can()` konsequent vor allen schreibenden Operationen  
✅ **Allowlist-Validierung** — Enum-Felder in `save_meta_boxes()` werden gegen definierte Wertemengen geprüft  
✅ **Moderne PHP 8.1-Nutzung** — `match`-Expressions, Union Types, Arrow Functions, Typed Properties korrekt eingesetzt  
✅ **Singleton-Pattern** — `VM_Plugin` korrekt implementiert, keine doppelten Initialisierungen  
✅ **Klare Architektur** — saubere Trennung von Admin/Public/Core/Templates  
✅ **Object Cache** — Relations-Caching mit `wp_cache_get/set` und automatischer Invalidierung implementiert  
✅ **dbDelta für Schemamigrationen** — korrekte WordPress-Methode für Datenbank-Upgrades  

---

## Kapitel 8: Priorisierter Maßnahmenplan

### Sofort (vor Produktiveinsatz)

| # | Befund | Aufwand | Wirkung |
|---|---|---|---|
| 1 | B001: Nonce-Prüfung reparieren | 5 min | Schließt CSRF-Lücke in öffentlichem AJAX |
| 2 | B008: `is_uploaded_file()` + MIME-Check | 15 min | Absicherung CSV-Upload |
| 3 | B006: Post-spezifische Capabilities | 30 min | Verhindert unautorisierten Relationen-Zugriff |
| 4 | B004: XSS in Suche beheben | 1 h | Eliminiert XSS-Vektor in vm-search.js |
| 5 | B024: Widget-Formular-Logikfehler | 5 min | Korrekte Checkbox-Speicherung |

### Kurzfristig (innerhalb einer Woche)

| # | Befund | Aufwand | Wirkung |
|---|---|---|---|
| 6 | B002: SSRF-Schutz beim Bild-Download | 2 h | Verhindert Missbrauch des Import-Tools |
| 7 | B007: CSV-Import mit Mengenlimit | 1 h | Verhindert DoS durch große Dateien |
| 8 | B009: try/finally in AJAX-Template-Loop | 30 min | Stabiler Post-Kontext bei Fehlern |
| 9 | B012: Allowlist in `save_settings()` | 30 min | Konsistente Eingabevalidierung |
| 10 | B005: Transaktions-Fehlerbehandlung | 1 h | Verhindert inkonsistente DB-Zustände |

### Mittelfristig (nächstes Release)

- B010: REST-API Pagination-Limit  
- B011: Mindest-Suchlänge server-seitig  
- B018: Inline-JavaScript auslagern (CSP-Kompatibilität)  
- B013: `SMALLINT` zu `INT` für Jahreszahlen  
- B021/B022: Performance-Optimierungen Cache  

---

*Bericht erstellt durch statische Code-Analyse · Virtuelles Museum v2.0.0 · 2026-03-31*