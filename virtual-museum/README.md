# Virtuelles Museum — WordPress Plugin v2.0

Vollständige Verwaltung eines virtuellen Heimatmuseums mit einem 4-stufigen relationalen Inhaltsmodell: **Räume → Vitrinen → Galerien → Objekte**.

---

## Inhaltsverzeichnis

- [Systemanforderungen](#systemanforderungen)
- [Installation](#installation)
- [Inhaltsmodell](#inhaltsmodell)
- [Datenbank-Schema](#datenbank-schema)
- [Admin-Bereich](#admin-bereich)
- [Einstellungen](#einstellungen)
- [Shortcodes](#shortcodes)
- [REST API](#rest-api)
- [AJAX-Endpunkte](#ajax-endpunkte)
- [Lazy Loading](#lazy-loading)
- [Frontend-Templates](#frontend-templates)
- [CSS Design System](#css-design-system)
- [Bulk-Import (CSV)](#bulk-import-csv)
- [Architektur & Dateistruktur](#architektur--dateistruktur)
- [Deinstallation](#deinstallation)

---

## Systemanforderungen

| Anforderung     | Minimum         |
|-----------------|-----------------|
| WordPress       | 6.4+            |
| PHP             | 8.1+            |
| MySQL / MariaDB | 5.7+ / 10.3+    |
| Browser (Admin) | Aktuelle Version (Chrome, Firefox, Safari, Edge) |

---

## Installation

1. Plugin-Ordner `virtual-museum/` in `/wp-content/plugins/` kopieren.
2. Im WordPress-Backend unter **Plugins → Installierte Plugins** das Plugin **Virtuelles Museum** aktivieren.
3. Bei der Aktivierung werden automatisch:
   - 2 Datenbanktabellen angelegt (`wp_vm_relations`, `wp_vm_search_index`)
   - Standard-Einstellungen gespeichert
   - Rewrite-Regeln geflusht (Permalinks werden registriert)
4. Unter **Einstellungen → Permalinks** einmal auf „Änderungen speichern" klicken, falls die Museum-URLs nicht funktionieren.

---

## Inhaltsmodell

Das Plugin verwaltet vier Custom Post Types (CPTs) in einer Many-to-Many-Hierarchie:

```
Raum (museum_room)
 ├── Vitrine (museum_vitrine)
 │    ├── Galerie (museum_gallery)
 │    │    └── Objekt (museum_object)
 │    └── Objekt (museum_object)
 ├── Galerie (museum_gallery)
 │    └── Objekt (museum_object)
 └── Objekt (museum_object)
```

### Erlaubte Beziehungen

| Eltern-Typ | Erlaubte Kind-Typen             |
|------------|---------------------------------|
| `room`     | `object`, `gallery`, `vitrine`  |
| `vitrine`  | `object`, `gallery`             |
| `gallery`  | `object`                        |

Ein Objekt kann in **mehreren Räumen, Galerien und Vitrinen** gleichzeitig erscheinen (Many-to-Many). Kreisreferenzen werden beim Hinzufügen einer Beziehung automatisch erkannt und verhindert.

### Custom Post Types

| CPT              | Slug                    | URL-Prefix         |
|------------------|-------------------------|--------------------|
| `museum_room`    | `museum-raum`           | `/museum/raum/`    |
| `museum_gallery` | `museum-galerie`        | `/museum/galerie/` |
| `museum_vitrine` | `museum-vitrine`        | `/museum/vitrine/` |
| `museum_object`  | `museum-objekt`         | `/museum/objekt/`  |

### Taxonomie

**`museum_era`** (Epoche) — wird von allen 4 CPTs geteilt. Ermöglicht filterbare Epochen-Navigation.

### Post-Meta-Felder

**Raum (`museum_room`)**
- `vm_room_color` — Akzentfarbe (HEX, z. B. `#8B4513`)
- `vm_room_era` — Freitext-Epoche
- `vm_room_display_order` — Anzeigereihenfolge

**Objekt (`museum_object`)**
- `vm_media_type` — `image` | `audio` | `video` | `360` | `document` | `nopics`
- `vm_year` — Jahreszahl
- `vm_copyright` — Copyright-Hinweis
- `vm_description` — Ausführliche Beschreibung

**Vitrine (`museum_vitrine`)**
- `vm_vitrine_layout` — `showcase` | `grid` | `shelf` | `spotlight`
- `vm_vitrine_theme` — `glass` | `wood` | `light` | `dark`

---

## Datenbank-Schema

### `wp_vm_relations`

Speichert alle Many-to-Many-Beziehungen zwischen den Inhaltstypen.

| Spalte        | Typ                              | Beschreibung                          |
|---------------|----------------------------------|---------------------------------------|
| `id`          | BIGINT UNSIGNED AUTO_INCREMENT   | Primärschlüssel                       |
| `parent_type` | ENUM(`room`,`vitrine`,`gallery`) | Typ des Eltern-Eintrags               |
| `parent_id`   | BIGINT UNSIGNED                  | WordPress Post-ID des Eltern-Eintrags |
| `child_type`  | ENUM(`object`,`gallery`,`vitrine`)| Typ des Kind-Eintrags                |
| `child_id`    | BIGINT UNSIGNED                  | WordPress Post-ID des Kind-Eintrags   |
| `position`    | SMALLINT UNSIGNED                | Sortier-Position (Drag & Drop)        |
| `added_by`    | BIGINT UNSIGNED                  | WordPress User-ID                     |
| `added_at`    | DATETIME                         | Zeitstempel der Erstellung            |

**Indizes:** UNIQUE auf `(parent_type, parent_id, child_type, child_id)`, Index auf `(parent_type, parent_id, position)`, Index auf `(child_type, child_id)`

### `wp_vm_search_index`

Denormalisierter Volltext-Suchindex für schnelle Frontend-Suche.

| Spalte        | Typ          | Beschreibung                             |
|---------------|--------------|------------------------------------------|
| `post_id`     | BIGINT       | WordPress Post-ID (UNIQUE)               |
| `post_type`   | ENUM         | `room` \| `gallery` \| `vitrine` \| `object` |
| `title`       | VARCHAR(400) | Titel für Schnellsuche                   |
| `search_text` | LONGTEXT     | Volltext (Titel + Beschreibung + Meta)   |
| `era_slug`    | VARCHAR(200) | Epochen-Slug für Filter                  |
| `year_start`  | SMALLINT     | Jahreszahl Beginn                        |
| `year_end`    | SMALLINT     | Jahreszahl Ende                          |
| `media_type`  | VARCHAR(50)  | Medientyp für Filter                     |
| `room_ids`    | TEXT         | Komma-separierte Raum-IDs                |
| `gallery_ids` | TEXT         | Komma-separierte Galerie-IDs             |
| `vitrine_ids` | TEXT         | Komma-separierte Vitrinen-IDs            |

**Indizes:** FULLTEXT auf `(title, search_text)` für MySQL-Volltextsuche

---

## Admin-Bereich

Das Plugin fügt ein eigenes Menü **„Virtuelles Museum"** mit Museum-Icon in die WordPress-Admin-Sidebar ein. Folgende Unterseiten stehen zur Verfügung:

| Seite              | Beschreibung                                              |
|--------------------|-----------------------------------------------------------|
| **Dashboard**      | Statistiken (Anzahl Räume, Galerien, Vitrinen, Objekte) + Schnelllinks |
| **Räume**          | WordPress-Post-Liste für `museum_room`                    |
| **Vitrinen**       | WordPress-Post-Liste für `museum_vitrine`                 |
| **Galerien**       | WordPress-Post-Liste für `museum_gallery`                 |
| **Objekte**        | WordPress-Post-Liste für `museum_object`                  |
| **Beziehungen**    | Übersicht aller aktiven Relationen mit Filterung          |
| **Beziehungskarte**| Interaktive `<details>`/`<summary>`-Baumstruktur aller Räume → Inhalte |
| **Statistiken**    | Detaillierte Nutzungsstatistiken pro Inhaltstyp           |
| **Import**         | CSV-Massenimport mit Vorschau und Ergebnisbericht         |
| **Einstellungen**  | Plugin-Konfiguration                                      |

### Relation Editor (Meta-Boxen)

Jeder CPT hat zwei Meta-Boxen in der Bearbeitungsansicht:

1. **Details** (Sidebar) — Typ-spezifische Felder (Farbe, Medientyp, Layout, etc.)
2. **Beziehungen** (Normal) — Drag-&-Drop-Editor für verknüpfte Inhalte

Der Relation Editor ermöglicht:
- **Drag & Drop** zur Umsortierung (native HTML5-API, Position wird per AJAX gespeichert)
- **Suche & Verknüpfung** über ein Modal-Fenster mit Echtzeit-Suche (Debounce 250 ms)
- **Entfernen** von Verknüpfungen per 🗑️-Button
- **Reverse-Lookup** — Objekte zeigen an, in welchen Räumen/Galerien/Vitrinen sie enthalten sind

---

## Einstellungen

Unter **Virtuelles Museum → Einstellungen** (`vm_settings` Option in der WordPress-DB):

| Option                    | Standard     | Beschreibung                                    |
|---------------------------|--------------|-------------------------------------------------|
| `archive_per_page`        | `24`         | Objekte pro Seite im Archiv                     |
| `lazy_per_page`           | `12`         | Objekte pro Lazy-Loading-Batch                  |
| `default_room_layout`     | `sections`   | Standard-Layout für Raum-Seiten (`sections`, `flat`, `tabs`) |
| `default_gallery_mode`    | `slider`     | Standard-Modus für Galerien (`slider`, `masonry`, `grid`, `filmstrip`) |
| `default_vitrine_layout`  | `showcase`   | Standard-Layout für Vitrinen                    |
| `default_vitrine_theme`   | `light`      | Standard-Theme für Vitrinen                     |
| `enable_lightbox`         | `true`       | Lightbox-Feature für Bilder                     |
| `enable_360`              | `true`       | 360°-Panorama-Viewer (Pannellum)                |
| `enable_breadcrumb`       | `true`       | Kontext-Breadcrumb im Frontend                  |
| `show_relation_badge`     | `true`       | „Auch zu finden in"-Badge auf Objekt-Seiten     |
| `enable_rest_api`         | `true`       | REST-API-Endpunkte aktivieren                   |
| `uninstall_delete_data`   | `false`      | Alle Daten bei Deinstallation unwiderruflich löschen |

---

## Shortcodes

### `[vm_room]`

Rendert eine vollständige Raum-Ansicht mit allen verknüpften Inhalten.

```
[vm_room id="42" show_vitrines="yes" show_galleries="yes" show_objects="yes" layout="sections" depth="2"]
```

| Parameter        | Standard    | Werte                        |
|------------------|-------------|------------------------------|
| `id`             | —           | Post-ID oder Slug (Pflicht)  |
| `show_vitrines`  | `yes`       | `yes` / `no`                 |
| `show_galleries` | `yes`       | `yes` / `no`                 |
| `show_objects`   | `yes`       | `yes` / `no`                 |
| `layout`         | `sections`  | `sections` / `flat` / `tabs` |
| `depth`          | `2`         | Tiefe der Verschachtelung    |

---

### `[vm_vitrine]`

Rendert eine Vitrine mit ihren Objekten und Galerien.

```
[vm_vitrine id="15" layout="showcase" theme="glass" show_context="yes"]
```

| Parameter        | Standard    | Werte                                        |
|------------------|-------------|----------------------------------------------|
| `id`             | —           | Post-ID oder Slug (Pflicht)                  |
| `layout`         | `showcase`  | `showcase` / `grid` / `shelf` / `spotlight`  |
| `theme`          | `light`     | `glass` / `wood` / `light` / `dark`          |
| `show_galleries` | `yes`       | `yes` / `no`                                 |
| `show_objects`   | `yes`       | `yes` / `no`                                 |
| `show_context`   | `yes`       | `yes` / `no` — zeigt Eltern-Raum             |

---

### `[vm_gallery]`

Rendert eine Bildergalerie.

```
[vm_gallery id="8" mode="slider" lightbox="yes" autoplay="no" caption="below"]
```

| Parameter      | Standard  | Werte                                       |
|----------------|-----------|---------------------------------------------|
| `id`           | —         | Post-ID oder Slug (Pflicht)                 |
| `mode`         | `slider`  | `slider` / `masonry` / `grid` / `filmstrip` |
| `lightbox`     | `yes`     | `yes` / `no`                                |
| `autoplay`     | `no`      | `yes` / `no`                                |
| `caption`      | `below`   | `below` / `overlay` / `none`                |
| `show_context` | `yes`     | `yes` / `no`                                |

---

### `[vm_museum_archive]`

Rendert ein durchsuchbares, filterbares Objekt-Archiv mit Lazy Loading.

```
[vm_museum_archive type="objects" layout="grid" per_page="24" show_filter="yes" show_search="yes"]
```

| Parameter      | Standard | Werte                                            |
|----------------|----------|--------------------------------------------------|
| `type`         | `all`    | `objects` / `rooms` / `galleries` / `vitrines`   |
| `layout`       | `grid`   | `grid` / `masonry` / `list`                      |
| `per_page`     | `24`     | Anzahl Einträge pro initialem Lade-Batch         |
| `show_filter`  | `yes`    | `yes` / `no` — Medientyp-Filter anzeigen         |
| `show_search`  | `yes`    | `yes` / `no` — Livesuchfeld anzeigen             |
| `show_nav`     | `yes`    | `yes` / `no`                                     |

---

### `[vm_room_grid]`

Rendert ein Kachel-Grid aller veröffentlichten Räume.

```
[vm_room_grid columns="4" show_count="yes" show_vitrines="yes" style="card"]
```

| Parameter       | Standard | Werte                  |
|-----------------|----------|------------------------|
| `columns`       | `4`      | `2` / `3` / `4`        |
| `show_count`    | `yes`    | `yes` / `no`           |
| `show_vitrines` | `yes`    | `yes` / `no`           |
| `style`         | `card`   | `card` / `minimal`     |

---

### `[vm_object_contexts]`

Zeigt alle Verknüpfungen eines Objekts (in welchen Räumen/Galerien/Vitrinen es enthalten ist).

```
[vm_object_contexts id="77" style="badges"]
```

| Parameter | Standard  | Werte                  |
|-----------|-----------|------------------------|
| `id`      | —         | Post-ID (Pflicht)      |
| `style`   | `badges`  | `badges` / `list`      |

---

## REST API

Die REST API muss in den **Einstellungen** aktiviert werden (`enable_rest_api`).

**Basis-URL:** `https://ihre-domain.de/wp-json/vm/v1/`

### Endpunkte

| Methode | Endpunkt                          | Beschreibung                                    |
|---------|-----------------------------------|-------------------------------------------------|
| GET     | `/rooms`                          | Alle Räume (sortiert nach `menu_order`)         |
| GET     | `/rooms/{id}`                     | Einzelner Raum inkl. Meta und `_links`          |
| GET     | `/rooms/{id}/contents`            | Alle Inhalte eines Raums (gemischte Typen)      |
| GET     | `/rooms/{id}/vitrines`            | Nur Vitrinen eines Raums                        |
| GET     | `/rooms/{id}/galleries`           | Nur Galerien eines Raums                        |
| GET     | `/rooms/{id}/objects`             | Nur direkt verknüpfte Objekte eines Raums       |
| GET     | `/vitrines/{id}`                  | Einzelne Vitrine                                |
| GET     | `/vitrines/{id}/contents`         | Galerien + Objekte einer Vitrine                |
| GET     | `/galleries/{id}`                 | Einzelne Galerie                                |
| GET     | `/galleries/{id}/objects`         | Objekte einer Galerie                           |
| GET     | `/objects/{id}`                   | Einzelnes Objekt                                |
| GET     | `/objects/{id}/contexts`          | Räume, Galerien und Vitrinen, die das Objekt enthalten |
| GET     | `/search?q={query}&type={type}`   | Volltextsuche (type: `all`,`rooms`,`galleries`,`vitrines`,`objects`) |

### Antwortformat

Alle Antworten enthalten mindestens:

```json
{
  "id": 42,
  "type": "room",
  "title": "Mittelalterlicher Marktplatz",
  "excerpt": "...",
  "url": "https://ihre-domain.de/museum/raum/mittelalterlicher-marktplatz/",
  "thumb": "https://ihre-domain.de/wp-content/uploads/...",
  "date": "2024-01-15 10:30:00"
}
```

Räume enthalten zusätzlich ein `meta`-Objekt und `_links` mit HATEOAS-Verweisen auf Unterressourcen.

---

## AJAX-Endpunkte

Alle AJAX-Anfragen gehen an `wp-admin/admin-ajax.php`.

### Öffentliche Endpunkte (kein Login erforderlich)

| Action                    | Methode | Parameter                                  | Beschreibung                            |
|---------------------------|---------|--------------------------------------------|-----------------------------------------|
| `vm_get_room_contents`    | GET     | `room_id`, `type` (all/vitrines/galleries/objects) | Raum-Inhalte als JSON              |
| `vm_get_vitrine_contents` | GET     | `vitrine_id`                               | Vitrinen-Inhalte als JSON               |
| `vm_get_gallery_objects`  | GET     | `gallery_id`                               | Galerie-Objekte als JSON                |
| `vm_get_object_contexts`  | GET     | `object_id`                                | Alle Kontexte eines Objekts             |
| `vm_search`               | GET     | `q`, `type`, `page`                        | Live-Suche (Suchergebnisse)             |
| `vm_load_objects_page`    | POST    | `nonce`, `page`, `per_page`, `parent_type`, `parent_id`, `child_type`, `meta_only` | Lazy Loading: gerenderte Karten-HTML + Paginierungsmetadaten |

### Admin-Endpunkte (erfordert `edit_posts` + Nonce `vm_admin_nonce`)

| Action                  | Parameter                                                  | Beschreibung                         |
|-------------------------|------------------------------------------------------------|--------------------------------------|
| `vm_add_relation`       | `parent_type`, `parent_id`, `child_type`, `child_id`       | Neue Verknüpfung hinzufügen          |
| `vm_remove_relation`    | `parent_type`, `parent_id`, `child_type`, `child_id` oder `relation_id` | Verknüpfung entfernen |
| `vm_reorder_relations`  | `parent_type`, `parent_id`, `ordered_relation_ids[]`       | Reihenfolge speichern                |
| `vm_search_linkable`    | `search`, `child_type`, `parent_type`, `parent_id`         | Verknüpfbare Einträge suchen         |

---

## Lazy Loading

Das Plugin implementiert ein mehrstufiges Lazy-Loading-Konzept zur Optimierung bei vielen Objekten.

### Strategie

| Bereich                     | Methode                                                               |
|-----------------------------|-----------------------------------------------------------------------|
| **Bilder**                  | Natives `loading="lazy"` auf allen `<img>`-Tags in Karten-Templates  |
| **Archiv-Grid**             | Erste Seite wird server-seitig gerendert; weitere Seiten via `IntersectionObserver` automatisch nachgeladen (Infinite Scroll) |
| **Raum — Objekt-Sektion**   | Erste `lazy_per_page` Objekte server-seitig; weitere Batches werden beim Scrollen in den Viewport nachgeladen |
| **Skeleton-Karten**         | Während des Ladens werden Platzhalter mit Shimmer-Animation angezeigt |

### Konfiguration

- **`lazy_per_page`** (Einstellungen): Anzahl Objekte pro Batch (Standard: 12)
- Infinite Scroll wird ausgelöst, wenn der Sentinel-Div 300 px vor dem Viewport erscheint
- Fallback „Mehr laden"-Button für Browser ohne `IntersectionObserver`

### JavaScript-Datei

`public/assets/js/vm-lazy.js` — zuständig für:
- Infinite Scroll auf `[data-vm-lazy-grid]`-Containern
- Deferred Loading auf `[data-vm-lazy-section]`-Sektionen
- Skeleton-Rendering und -Entfernung
- AJAX-Aufruf von `vm_load_objects_page`

---

## Frontend-Templates

### Template-Hierarchie

Das Plugin überschreibt WordPress-Templates via `template_include`-Filter:

| WordPress-Selektor                     | Plugin-Template                                      |
|----------------------------------------|------------------------------------------------------|
| `is_singular('museum_room')`           | `public/templates/single-museum-room.php`            |
| `is_singular('museum_vitrine')`        | `public/templates/single-museum-vitrine.php`         |
| `is_singular('museum_gallery')`        | `public/templates/single-museum-gallery.php`         |
| `is_singular('museum_object')`         | `public/templates/single-museum-object.php`          |
| `is_post_type_archive('museum_object')`| `public/templates/archive-museum-object.php`         |

### Partial-Templates

| Datei                          | Beschreibung                                                       |
|--------------------------------|--------------------------------------------------------------------|
| `partials/card-room.php`       | Kachel-Karte für einen Raum                                        |
| `partials/card-gallery.php`    | Kachel-Karte für eine Galerie                                      |
| `partials/card-vitrine.php`    | Kachel-Karte für eine Vitrine                                      |
| `partials/card-object.php`     | Kachel-Karte für ein Objekt (mit `loading="lazy"`)                 |
| `partials/breadcrumb-context.php` | Kontext-Breadcrumb via `?vm_context=room_42,vitrine_7` URL-Parameter |
| `partials/relation-badge.php`  | „Auch zu finden in"-Badge für Objekt-Seiten                        |
| `partials/filter-bar.php`      | Medientyp-Filter-Leiste für Archiv-Seiten                          |

### Kontext-Breadcrumb

Das Breadcrumb-System verfolgt den Navigationspfad eines Besuchers durch das Museum:

- Der Pfad wird als `?vm_context=room_42,vitrine_7,gallery_15` in der URL kodiert
- `vm-breadcrumb.js` speichert den Kontext in `sessionStorage` und reichert alle internen `/museum/`-Links damit an
- Beim Verlassen des Museums wird der `sessionStorage`-Eintrag automatisch gelöscht

---

## CSS Design System

### Custom Properties (Design Tokens)

```css
--vm-color-accent:       #8B4513   /* Hauptakzent */
--vm-color-surface:      #fff
--vm-color-bg:           #f9f9f9
--vm-color-border:       #e0e0e0
--vm-color-text:         #1d2327
--vm-color-text-muted:   #646970
--vm-color-shadow:       rgba(0,0,0,0.1)
--vm-font-base:          system-ui, sans-serif
--vm-spacing-xs:         4px
--vm-spacing-sm:         8px
--vm-spacing-md:         16px
--vm-spacing-lg:         24px
--vm-radius:             4px
--vm-transition:         0.2s ease
--vm-room-color:         /* Pro Raum überschreibbar */
```

### CSS-Dateien

| Datei                  | Inhalt                                                  |
|------------------------|---------------------------------------------------------|
| `vm-main.css`          | Custom Properties, globale Basis-Stile, Breadcrumb, Badge |
| `vm-grid.css`          | Grid-Layouts, Karten-Komponenten, Skeleton-Animation    |
| `vm-vitrine.css`       | Vitrinen-Layouts (showcase/grid/shelf/spotlight) + Themes |
| `vm-gallery.css`       | Galerie-Modi (slider/masonry/filmstrip)                 |
| `vm-lightbox.css`      | Lightbox-Overlay                                        |
| `vm-responsive.css`    | Media Queries für Mobile/Tablet                         |

### Vitrine-Themes

| Theme   | Beschreibung                              |
|---------|-------------------------------------------|
| `glass` | Glasmorphism-Effekt mit Blur              |
| `wood`  | Holz-Optik, warme Brauntöne               |
| `light` | Heller, cleaner Look (Standard)           |
| `dark`  | Dunkles Theme                             |

---

## Bulk-Import (CSV)

Unter **Virtuelles Museum → Import** können Inhalte per CSV massenimportiert werden.

### CSV-Format

```csv
type,title,description,media_type,year,era,in_rooms,in_galleries,in_vitrines
room,Mittelalterlicher Marktplatz,Beschreibung,,, mittelalter,,,
object,Stadtsiegel,Originales Siegel,image,1350,mittelalter,Mittelalterlicher Marktplatz,,
gallery,Handwerksbilder,Bilder des Handwerks,,,,Mittelalterlicher Marktplatz,,
```

### Pflichtfelder

- `type` — `room` | `vitrine` | `gallery` | `object`
- `title` — Titel des Eintrags

### Optionale Felder

- `description` — Beschreibungstext
- `media_type` — Medientyp (nur für `object`)
- `year` — Jahreszahl (nur für `object`)
- `era` — Epochen-Slug für die `museum_era`-Taxonomie
- `in_rooms` — Pipe-separierte Raum-Titel für automatische Verknüpfung (z. B. `Raum A|Raum B`)
- `in_galleries` — Pipe-separierte Galerie-Titel
- `in_vitrines` — Pipe-separierte Vitrinen-Titel

### Import-Phasen

1. **Parse** — CSV einlesen und validieren
2. **Posts erstellen** — In der Reihenfolge: Räume → Vitrinen → Galerien → Objekte
3. **Relationen erstellen** — Verknüpfungen anhand der `in_*`-Felder aufbauen
4. **Suchindex** — `wp_vm_search_index` vollständig neu aufbauen
5. **Bericht** — Ergebnisbericht mit erstellten Einträgen und Fehlern

---

## Architektur & Dateistruktur

```
virtual-museum/
├── virtual-museum.php              # Plugin-Header, Konstanten, Autoloader, Bootstrap
├── uninstall.php                   # Datenbereinigung bei Deinstallation
│
├── includes/
│   ├── class-vm-plugin.php         # Singleton-Bootstrapper, bindet alle Komponenten
│   ├── class-vm-activator.php      # Aktivierung: DB-Tabellen, Default-Optionen
│   ├── class-vm-post-types.php     # Registrierung der 4 CPTs + Taxonomie
│   ├── class-vm-relations.php      # Kern-Service: alle CRUD-Operationen + Cache
│   ├── class-vm-meta-boxes.php     # Admin-Meta-Boxen (Details + Relation Editor)
│   ├── class-vm-ajax.php           # Alle AJAX-Handler (admin + public)
│   ├── class-vm-rest-api.php       # REST API (Namespace vm/v1, 13 Routen)
│   ├── class-vm-shortcodes.php     # 6 Shortcodes
│   ├── class-vm-search-index.php   # Suchindex-Verwaltung
│   └── class-vm-bulk-import.php    # CSV-Massenimport
│
├── admin/
│   ├── class-vm-admin.php          # Admin-Menü, Asset-Enqueue, Einstellungen speichern
│   ├── assets/
│   │   ├── admin.css               # Admin-Stile (Relation Editor, Modal, Stats)
│   │   ├── admin.js                # jQuery: Relation hinzufügen/entfernen, Suchmodal
│   │   └── relation-editor.js      # Drag & Drop (native HTML5 API), Reihenfolge speichern
│   └── views/
│       ├── page-dashboard.php      # Stat-Cards + Schnelllinks
│       ├── page-relation-map.php   # Beziehungsbaum (<details>/<summary>)
│       ├── page-relations.php      # Relationen-Übersicht mit Filter
│       ├── page-statistics.php     # Nutzungsstatistiken
│       ├── page-settings.php       # Einstellungsformular
│       └── page-import.php         # CSV-Import-Formular + Ergebnis
│
└── public/
    ├── class-vm-public.php         # Asset-Enqueue, Template-Filter, vmPublic-Lokalisierung
    ├── templates/
    │   ├── single-museum-room.php
    │   ├── single-museum-vitrine.php
    │   ├── single-museum-gallery.php
    │   ├── single-museum-object.php
    │   ├── archive-museum-object.php
    │   └── partials/
    │       ├── card-room.php
    │       ├── card-vitrine.php
    │       ├── card-gallery.php
    │       ├── card-object.php
    │       ├── breadcrumb-context.php
    │       ├── relation-badge.php
    │       └── filter-bar.php
    └── assets/
        ├── css/
        │   ├── vm-main.css
        │   ├── vm-grid.css
        │   ├── vm-vitrine.css
        │   ├── vm-gallery.css
        │   ├── vm-lightbox.css
        │   └── vm-responsive.css
        └── js/
            ├── vm-main.js          # Sektion-Toggle, allgemeine Interaktionen
            ├── vm-lightbox.js      # Lightbox-Implementierung
            ├── vm-filter.js        # Client-seitiger Karten-Filter
            ├── vm-search.js        # Live-Suche mit Debounce
            ├── vm-breadcrumb.js    # Kontext-URL-Management via sessionStorage
            └── vm-lazy.js          # Lazy Loading (IntersectionObserver + Skeletons)
```

### Klassen-Übersicht

| Klasse               | Typ       | Beschreibung                                         |
|----------------------|-----------|------------------------------------------------------|
| `VM_Plugin`          | Singleton | Bootstrap, verbindet alle Komponenten                |
| `VM_Activator`       | Static    | Aktivierung/Deaktivierung                            |
| `VM_Post_Types`      | Instance  | CPT- und Taxonomie-Registrierung                     |
| `VM_Relations`       | Static    | Alle Beziehungsoperationen + WordPress Object Cache  |
| `VM_Meta_Boxes`      | Instance  | Admin Meta-Boxen + Speicherlogik                     |
| `VM_Ajax`            | Instance  | AJAX-Handler-Registrierung und -Implementierung      |
| `VM_Rest_Api`        | Instance  | REST-Routen                                          |
| `VM_Shortcodes`      | Instance  | Shortcode-Registrierung                              |
| `VM_Search_Index`    | Static    | Suchindex-CRUD + Rebuild                             |
| `VM_Bulk_Import`     | Instance  | CSV-Import-Logik                                     |
| `VM_Admin`           | Instance  | Admin-Menü, Enqueue, Einstellungen                   |
| `VM_Public`          | Instance  | Frontend-Assets, Template-Loading                    |

### Caching

`VM_Relations` nutzt **WordPress Object Cache** mit der Gruppe `vm_relations`:

- **Cache-Key:** `vm_children_{parent_type}_{parent_id}_{child_type}`
- **TTL:** 3600 Sekunden (1 Stunde)
- **Invalidierung:** Automatisch bei `save_post` und `before_delete_post` via `VM_Relations::flush_cache()`

---

## Deinstallation

1. Plugin im WordPress-Backend deaktivieren und löschen.
2. **Standardmäßig** werden **keine Daten gelöscht** (CPT-Posts und DB-Tabellen bleiben erhalten).
3. Um alle Daten zu entfernen, muss in den Einstellungen die Option **„Alle Daten bei Deinstallation löschen"** aktiviert sein, bevor das Plugin deinstalliert wird.

Bei aktivierter Option löscht `uninstall.php`:
- Alle Posts der Typen `museum_room`, `museum_gallery`, `museum_vitrine`, `museum_object`
- Die Tabellen `wp_vm_relations` und `wp_vm_search_index`
- Den WordPress-Options-Eintrag `vm_settings`

---

## Lizenz

GPL v2 or later — https://www.gnu.org/licenses/gpl-2.0.html
