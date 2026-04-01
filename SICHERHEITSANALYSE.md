# Sicherheits- und Codequalitätsanalyse — Virtuelles Museum v2.0

**Datum:** 2026-04-01  
**Analysiert:** WordPress-Plugin `virtual-museum/`  
**PHP-Mindestversion:** 8.1 | **WordPress-Mindestversion:** 6.4

---

## Kapitel 1 — Für nicht-technische Leserinnen und Leser

Dieses Kapitel erklärt den Sicherheitszustand des Plugins in einfacher Sprache, ohne technische Fachbegriffe.

### Was ist das Virtuelle Museum?

Das Virtuelle Museum ist ein Erweiterungspaket für WordPress, mit dem Museumsinhalte — Räume, Vitrinen, Galerien und Objekte — strukturiert verwaltet und auf einer Website präsentiert werden können. Das Plugin verarbeitet Museumsdaten, erlaubt das Hochladen von Bildern und bietet Besuchern eine Suchfunktion.

### Wie wurde geprüft?

Der gesamte Programmcode wurde manuell durchgesehen und auf bekannte Angriffsmuster, technische Fehler und Qualitätsmängel untersucht. Die Prüfung orientiert sich an der international anerkannten OWASP-Liste der zehn häufigsten Webangriffe.

### Wie sicher ist das Plugin?

Der aktuelle Sicherheitszustand ist **gut**. Es wurden keine kritischen, hohen oder mittleren Schwachstellen gefunden. Die zwei verbleibenden Hinweise sind keine aktiven Sicherheitslücken, sondern technische Stilfragen ohne praktisches Angriffsrisiko.

Das Plugin setzt moderne Techniken ein, arbeitet korrekt mit dem WordPress-Sicherheitssystem zusammen und gibt alle Nutzereingaben konsequent bereinigt aus. Für ein Museumsprojekt mit einem überschaubaren Benutzerkreis besteht kein erhöhtes Risiko.

### Was ist generell zu beachten?

- Das Plugin sollte regelmäßig aktualisiert werden, sobald neue Versionen erscheinen.
- Der CSV-Import sollte nur von vertrauenswürdigen Personen mit Administrator-Rechten genutzt werden.
- Die optionale REST-API (in den Einstellungen deaktivierbar) macht Museumsinhalte öffentlich über eine maschinenlesbare Schnittstelle verfügbar. Das ist für eine Museumspräsentation erwünscht, sollte aber bewusst eingeschaltet werden.

---

## Kapitel 2 — Analysegrundlage

### Abdeckung

| Bereich | Dateien |
|---------|---------|
| Plugin-Einstieg & Bootstrap | `virtual-museum.php`, `class-vm-plugin.php`, `class-vm-activator.php` |
| Datenbankschicht | `class-vm-relations.php`, `class-vm-search-index.php` |
| AJAX-Handler | `class-vm-ajax.php` |
| Massenimport | `class-vm-bulk-import.php` |
| REST-API | `class-vm-rest-api.php` |
| Admin-Oberfläche | `admin/class-vm-admin.php`, alle `admin/views/page-*.php` |
| Öffentliche Templates | alle `public/templates/**/*.php` |
| JavaScript | `public/assets/js/vm-search.js`, `admin/assets/vm-blocks.js` |
| Gutenberg-Blöcke | `class-vm-blocks.php` |
| Widgets | `class-vm-widgets.php` |
| Shortcodes | `class-vm-shortcodes.php` |
| Deinstallation | `uninstall.php` |

### Prüfmethodik

- Manuelle statische Codeanalyse aller PHP- und JavaScript-Dateien
- Mapping auf OWASP Top 10 (2021)
- WordPress-spezifische Prüfpunkte: Nonce-Verifizierung, Capability-Checks, `$wpdb->prepare()`, Output-Escaping
- Bewertung der Codequalität anhand aktueller PHP- und WordPress-Standards

---

## Kapitel 3 — Gesamtbewertung

| Kategorie | Bewertung |
|-----------|-----------|
| Kritische Schwachstellen | ✅ Keine |
| Hohe Schwachstellen | ✅ Keine |
| Mittlere Befunde | ✅ Keine |
| Niedrige Hinweise | ⚠️ 2 (kein aktives Risiko) |
| Codequalität | ✅ Gut — PHP 8.1, WP 6.4, konsequentes Escaping |
| OWASP Top 10 Abdeckung | ✅ Alle relevanten Kategorien adressiert |

---

## Kapitel 4 — Aktuelle Hinweise

### C001 — REST-API-Suche ohne Mindestlänge (NIEDRIG)

**Datei:** `includes/class-vm-rest-api.php`, Zeile 115  
**Beschreibung:** Der AJAX-Suchendpunkt erzwingt serverseitig eine Mindestlänge von 2 Zeichen. Der REST-API-Endpunkt `GET /vm/v1/search?q=x` hat diese Prüfung nicht. Eine sehr kurze Suchanfrage löst eine vollständige `WP_Query`-Volltextsuche aus.

```php
public function search( WP_REST_Request $request ): WP_REST_Response {
    $q = $request->get_param( 'q' ) ?? '';
    // Keine Min-Längen-Prüfung
    $wp_query = new WP_Query( [ 's' => $q, ... ] );
```

**Risiko:** Sehr gering — die REST-API ist standardmäßig deaktiviert und muss in den Plugin-Einstellungen explizit aktiviert werden. WordPress-eigene REST-Suchendpunkte haben dieselbe Eigenschaft.  
**Empfehlung:** Bei nächster Überarbeitung eine Längenprüfung ergänzen und bei zu kurzer Anfrage eine leere Ergebnisliste zurückgeben.

---

### C002 — Statistikabfragen ohne `$wpdb->prepare()` (INFO)

**Datei:** `admin/views/page-statistics.php`, Zeilen 7–8  
**Beschreibung:** Zwei Datenbankabfragen verwenden statisches SQL ohne `$wpdb->prepare()`:

```php
$relations   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}vm_relations" );
$top_objects = $wpdb->get_results( "SELECT child_id, COUNT(*) ... FROM {$wpdb->prefix}vm_relations ..." );
```

Da keine benutzergesteuerten Variablen interpoliert werden, besteht **kein SQL-Injection-Risiko**. `$wpdb->prefix` ist ein vom WordPress-Framework kontrollierter Wert.  
**Risiko:** Keines — reine Stilabweichung von den WordPress Coding Standards.  
**Empfehlung:** Konsistenzhalber auf `$wpdb->prepare()` umstellen, wenn die Datei ohnehin überarbeitet wird.

---

## Kapitel 5 — OWASP Top 10 (2021) Bewertung

| # | Kategorie | Status | Bemerkung |
|---|-----------|--------|-----------|
| A01 | Broken Access Control | ✅ Adressiert | Nonce + Capability-Checks (`edit_posts`, `edit_post($id)`, `manage_options`, `upload_files`) in allen AJAX- und Admin-Handlern |
| A02 | Cryptographic Failures | ✅ Nicht anwendbar | Kein Passwort-Hashing oder Verschlüsselung im Plugin — wird durch WordPress/Hosting übernommen |
| A03 | Injection | ✅ Adressiert | `$wpdb->prepare()` in allen parametrisierten Abfragen; `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` in allen Templates; C002 ohne Risiko |
| A04 | Insecure Design | ✅ Adressiert | SSRF-Schutz beim Bildimport, Upload-Validierung (Typ, Größe, Herkunft), CSV-Limits, Allowlist-Validierung für Einstellungen |
| A05 | Security Misconfiguration | ✅ Adressiert | REST-API standardmäßig deaktiviert; Nonce-Prüfung lückenlos erzwungen |
| A06 | Vulnerable Components | ✅ Kein Risiko | Keine externen Composer-Abhängigkeiten; ausschließlich WordPress Core-APIs |
| A07 | Auth & Access Failures | ✅ Adressiert | `check_ajax_referer()` / `wp_verify_nonce()` in allen Admin-AJAX-Handlern; `current_user_can()` vor allen schreibenden Operationen |
| A08 | Software & Data Integrity | ✅ Kein Risiko | Kein automatischer Code-Download; keine `unserialize()`-Aufrufe auf Nutzerdaten |
| A09 | Security Logging & Monitoring | ⚠️ Teilweise | Fehler werden als `WP_Error` zurückgegeben; kein dediziertes Audit-Log — für ein Museums-Plugin akzeptabel |
| A10 | SSRF | ✅ Adressiert | `is_safe_image_url()` blockiert private IP-Ranges, reservierte Adressen und unsichere URL-Schemata vor jedem Bild-Download |

---

## Kapitel 6 — Codequalität und Aktualität

### PHP-Standard und Aktualität

| Kriterium | Bewertung |
|-----------|-----------|
| PHP-Version | ✅ PHP 8.1+ — `match`, Union Types, `readonly`, Enums |
| WordPress-Version | ✅ WP 6.4+ — Block Editor API, `register_block_type()` aktuell |
| Typisierung | ✅ Typed Properties, Return Types und Parameter Types konsistent eingesetzt |
| Fehlerbehandlung | ✅ `WP_Error` durchgängig; `try/catch/finally` in DB-Transaktionen |
| Datenbankzugriff | ✅ Prepared Statements; WP Object Cache für Relation-Queries mit gezielter Invalidierung |
| Output-Escaping | ✅ `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` in allen Templates konsequent |
| Internationalisierung | ✅ Alle Strings durch `__()` / `esc_html_e()` übersetzbar |

### Architektur

| Bereich | Bewertung |
|---------|-----------|
| Datentrennung | ✅ Klare Schichten: CPTs / Relations / Search Index / AJAX / REST |
| Caching | ✅ WP Object Cache in `VM_Relations` mit gezielten Invalidierungen bei Änderungen |
| Transaktionssicherheit | ✅ `START TRANSACTION / COMMIT / ROLLBACK` mit Rückgabewert-Prüfung bei jedem Insert |
| Template-Sicherheit | ✅ `setup_postdata` / `wp_reset_postdata` in `try/finally` — läuft garantiert |
| Block-Integration | ✅ 10 Dynamic Gutenberg Blocks mit Server-Side Rendering, kein Build-Schritt |
| Widget-Integration | ✅ 5 Classic Widgets (`WP_Widget`) — funktional; mittelfristig durch Block Patterns ersetzbar |
| REST-API | ✅ Versionierter Namespace `vm/v1`, Read-only, per Einstellung steuerbar |
| Deinstallation | ✅ `uninstall.php` prüft `WP_UNINSTALL_PLUGIN` und User-Einstellung vor Datenlöschung |
| CSV-Import | ✅ Dateiupload-Validierung, SSRF-Schutz, Zeilen- und Zellgrößenlimits |

### Verbesserungspotenzial (nicht sicherheitsrelevant)

| Punkt | Einschätzung |
|-------|-------------|
| `WP_Widget` | Mittelfristig durch Block Patterns ersetzbar; kein akuter Handlungsbedarf |
| REST-Suche ohne Min-Länge | Sehr geringes Risiko; bei nächster Überarbeitung angleichen (C001) |
| Statistikseite ohne `prepare()` | Reine Stilfrage; keine Sicherheitsrelevanz (C002) |
| Kein Audit-Log | Für aktuellen Nutzungsumfang akzeptabel |

---

## Anhang — Schweregrad-Definitionen

| Stufe | Bedeutung |
|-------|-----------|
| **Kritisch** | Aktiv ausnutzbar ohne besondere Voraussetzungen; sofortiger Handlungsbedarf |
| **Hoch** | Erhebliches Risiko, erfordert Angreiferprivileg oder spezielle Umstände |
| **Mittel** | Eingeschränktes Risiko; sollte im nächsten Release behoben werden |
| **Niedrig** | Kein direktes Angriffsrisiko; Stilproblem oder Härtungsmaßnahme |
| **Info** | Keine Sicherheitsrelevanz; Hinweis auf Codingstandards |

---

*Bericht erstellt durch Claude Code (Anthropic) — Analysestand: 2026-04-01*
