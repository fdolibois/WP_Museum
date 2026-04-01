/**
 * Virtual Museum — Gutenberg Block Definitions
 * No build step required — uses wp.* globals.
 */
(function () {
    'use strict';

    var blocks          = wp.blocks;
    var blockEditor     = wp.blockEditor;
    var components      = wp.components;
    var el              = wp.element.createElement;
    var Fragment        = wp.element.Fragment;
    var ServerSideRender = wp.serverSideRender;
    var __              = wp.i18n.__;

    var InspectorControls = blockEditor.InspectorControls;
    var useBlockProps     = blockEditor.useBlockProps;
    var PanelBody         = components.PanelBody;
    var RangeControl      = components.RangeControl;
    var ToggleControl     = components.ToggleControl;
    var SelectControl     = components.SelectControl;
    var TextControl       = components.TextControl;
    var Placeholder       = components.Placeholder;
    var Spinner           = components.Spinner;

    // Post options from PHP
    var data = window.vmBlocksData || { rooms: [], vitrines: [], galleries: [] };

    // =========================================================
    // Helper: Editor preview using ServerSideRender
    // =========================================================
    function makePreview(blockName, attributes) {
        return el(ServerSideRender, {
            block:      'vm/' + blockName,
            attributes: attributes,
        });
    }

    // Helper: Icon SVG (museum building)
    var museumIcon = el('svg', { xmlns: 'http://www.w3.org/2000/svg', viewBox: '0 0 20 20' },
        el('path', { d: 'M10 1L1 6v1h18V6L10 1zM2 8v9h2V8H2zm4 0v9h2V8H6zm4 0v9h2V8h-2zm4 0v9h2V8h-2zM1 18v1h18v-1H1z' })
    );

    // =========================================================
    // Block: vm/museum-entrance
    // =========================================================
    blocks.registerBlockType('vm/museum-entrance', {
        title:       __('VM: Museumseingang', 'vmuseum'),
        description: __('Vollständige Museums-Startseite mit Statistiken, Suche und Raumübersicht.', 'vmuseum'),
        category:    'virtual-museum',
        icon:        museumIcon,
        attributes:  {},
        edit: function (props) {
            return el(Fragment, null,
                el('div', useBlockProps(),
                    makePreview('museum-entrance', {})
                )
            );
        },
        save: function () { return null; },
    });

    // =========================================================
    // Block: vm/museum-stats
    // =========================================================
    blocks.registerBlockType('vm/museum-stats', {
        title:       __('VM: Statistiken', 'vmuseum'),
        description: __('Zeigt Anzahl der Räume, Vitrinen, Galerien und Objekte.', 'vmuseum'),
        category:    'virtual-museum',
        icon:        'chart-bar',
        attributes: {
            show_rooms:     { type: 'boolean', default: true },
            show_vitrines:  { type: 'boolean', default: true },
            show_galleries: { type: 'boolean', default: true },
            show_objects:   { type: 'boolean', default: true },
        },
        edit: function (props) {
            var attr = props.attributes;
            var set  = props.setAttributes;
            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Sichtbare Elemente', 'vmuseum'), initialOpen: true },
                        el(ToggleControl, { label: __('Räume', 'vmuseum'),    checked: attr.show_rooms,     onChange: function(v) { set({ show_rooms: v }); } }),
                        el(ToggleControl, { label: __('Vitrinen', 'vmuseum'), checked: attr.show_vitrines,  onChange: function(v) { set({ show_vitrines: v }); } }),
                        el(ToggleControl, { label: __('Galerien', 'vmuseum'), checked: attr.show_galleries, onChange: function(v) { set({ show_galleries: v }); } }),
                        el(ToggleControl, { label: __('Objekte', 'vmuseum'),  checked: attr.show_objects,   onChange: function(v) { set({ show_objects: v }); } })
                    )
                ),
                el('div', useBlockProps(), makePreview('museum-stats', attr))
            );
        },
        save: function () { return null; },
    });

    // =========================================================
    // Block: vm/museum-search
    // =========================================================
    blocks.registerBlockType('vm/museum-search', {
        title:       __('VM: Suche', 'vmuseum'),
        description: __('Live-Suchfeld für das Museum.', 'vmuseum'),
        category:    'virtual-museum',
        icon:        'search',
        attributes: {
            placeholder: { type: 'string', default: '' },
        },
        edit: function (props) {
            var attr = props.attributes;
            var set  = props.setAttributes;
            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Einstellungen', 'vmuseum'), initialOpen: true },
                        el(TextControl, { label: __('Platzhaltertext', 'vmuseum'), value: attr.placeholder, onChange: function(v) { set({ placeholder: v }); } })
                    )
                ),
                el('div', useBlockProps(), makePreview('museum-search', attr))
            );
        },
        save: function () { return null; },
    });

    // =========================================================
    // Block: vm/museum-nav
    // =========================================================
    blocks.registerBlockType('vm/museum-nav', {
        title:       __('VM: Raumnavigation', 'vmuseum'),
        description: __('Liste aller Räume als Navigation mit optionaler Objektanzahl.', 'vmuseum'),
        category:    'virtual-museum',
        icon:        'list-view',
        attributes: {
            show_count: { type: 'boolean', default: true },
            show_sub:   { type: 'boolean', default: true },
        },
        edit: function (props) {
            var attr = props.attributes;
            var set  = props.setAttributes;
            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Einstellungen', 'vmuseum'), initialOpen: true },
                        el(ToggleControl, { label: __('Objektanzahl anzeigen', 'vmuseum'), checked: attr.show_count, onChange: function(v) { set({ show_count: v }); } }),
                        el(ToggleControl, { label: __('Unterelemente im aktiven Raum', 'vmuseum'), checked: attr.show_sub, onChange: function(v) { set({ show_sub: v }); } })
                    )
                ),
                el('div', useBlockProps(), makePreview('museum-nav', attr))
            );
        },
        save: function () { return null; },
    });

    // =========================================================
    // Block: vm/room-grid
    // =========================================================
    blocks.registerBlockType('vm/room-grid', {
        title:       __('VM: Raumübersicht', 'vmuseum'),
        description: __('Zeigt alle Museumsräume als Kachelraster.', 'vmuseum'),
        category:    'virtual-museum',
        icon:        'grid-view',
        attributes: {
            columns:    { type: 'number',  default: 3 },
            show_count: { type: 'boolean', default: true },
            limit:      { type: 'number',  default: 0 },
        },
        edit: function (props) {
            var attr = props.attributes;
            var set  = props.setAttributes;
            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Einstellungen', 'vmuseum'), initialOpen: true },
                        el(RangeControl, { label: __('Spalten', 'vmuseum'), value: attr.columns, min: 1, max: 4, onChange: function(v) { set({ columns: v }); } }),
                        el(ToggleControl, { label: __('Objektanzahl anzeigen', 'vmuseum'), checked: attr.show_count, onChange: function(v) { set({ show_count: v }); } }),
                        el(RangeControl, { label: __('Max. Räume (0 = alle)', 'vmuseum'), value: attr.limit, min: 0, max: 50, onChange: function(v) { set({ limit: v }); } })
                    )
                ),
                el('div', useBlockProps(), makePreview('room-grid', attr))
            );
        },
        save: function () { return null; },
    });

    // =========================================================
    // Block: vm/single-room
    // =========================================================
    blocks.registerBlockType('vm/single-room', {
        title:       __('VM: Einzelner Raum', 'vmuseum'),
        description: __('Zeigt eine einzelne Raumkarte.', 'vmuseum'),
        category:    'virtual-museum',
        icon:        'admin-home',
        attributes: {
            room_id: { type: 'number', default: 0 },
        },
        edit: function (props) {
            var attr = props.attributes;
            var set  = props.setAttributes;
            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Raum auswählen', 'vmuseum'), initialOpen: true },
                        el(SelectControl, { label: __('Raum', 'vmuseum'), value: attr.room_id, options: data.rooms, onChange: function(v) { set({ room_id: parseInt(v, 10) }); } })
                    )
                ),
                el('div', useBlockProps(),
                    attr.room_id
                        ? makePreview('single-room', attr)
                        : el(Placeholder, { icon: 'admin-home', label: __('VM: Einzelner Raum', 'vmuseum'), instructions: __('Bitte einen Raum in der Seitenleiste auswählen.', 'vmuseum') })
                )
            );
        },
        save: function () { return null; },
    });

    // =========================================================
    // Block: vm/object-grid
    // =========================================================
    var orderings = [
        { value: 'date',       label: __('Neueste zuerst', 'vmuseum') },
        { value: 'title',      label: __('Alphabetisch', 'vmuseum') },
        { value: 'menu_order', label: __('Manuelle Reihenfolge', 'vmuseum') },
    ];
    var parentTypes = [
        { value: '',        label: __('Alle Objekte', 'vmuseum') },
        { value: 'room',    label: __('Raum', 'vmuseum') },
        { value: 'vitrine', label: __('Vitrine', 'vmuseum') },
        { value: 'gallery', label: __('Galerie', 'vmuseum') },
    ];

    blocks.registerBlockType('vm/object-grid', {
        title:       __('VM: Objektraster', 'vmuseum'),
        description: __('Raster mit Lazy Loading – optional auf Raum, Vitrine oder Galerie gefiltert.', 'vmuseum'),
        category:    'virtual-museum',
        icon:        'format-gallery',
        attributes: {
            parent_type: { type: 'string', default: '' },
            parent_id:   { type: 'number', default: 0 },
            per_page:    { type: 'number', default: 12 },
            orderby:     { type: 'string', default: 'date' },
            columns:     { type: 'number', default: 4 },
        },
        edit: function (props) {
            var attr = props.attributes;
            var set  = props.setAttributes;

            // Dynamic parent options based on parent_type
            var parentOptions = attr.parent_type === 'room'    ? data.rooms    :
                                attr.parent_type === 'vitrine' ? data.vitrines :
                                attr.parent_type === 'gallery' ? data.galleries : [];

            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Filter', 'vmuseum'), initialOpen: true },
                        el(SelectControl, { label: __('Quelle', 'vmuseum'), value: attr.parent_type, options: parentTypes,
                            onChange: function(v) { set({ parent_type: v, parent_id: 0 }); } }),
                        attr.parent_type && el(SelectControl, { label: __('Auswahl', 'vmuseum'), value: attr.parent_id, options: parentOptions,
                            onChange: function(v) { set({ parent_id: parseInt(v, 10) }); } })
                    ),
                    el(PanelBody, { title: __('Darstellung', 'vmuseum'), initialOpen: false },
                        el(SelectControl, { label: __('Sortierung', 'vmuseum'), value: attr.orderby, options: orderings, onChange: function(v) { set({ orderby: v }); } }),
                        el(RangeControl, { label: __('Objekte pro Seite', 'vmuseum'), value: attr.per_page, min: 4, max: 48, onChange: function(v) { set({ per_page: v }); } }),
                        el(RangeControl, { label: __('Spalten', 'vmuseum'), value: attr.columns, min: 2, max: 6, onChange: function(v) { set({ columns: v }); } })
                    )
                ),
                el('div', useBlockProps(), makePreview('object-grid', attr))
            );
        },
        save: function () { return null; },
    });

    // =========================================================
    // Block: vm/recent-objects
    // =========================================================
    blocks.registerBlockType('vm/recent-objects', {
        title:       __('VM: Neueste Objekte', 'vmuseum'),
        description: __('Zeigt die zuletzt hinzugefügten Museumsobjekte.', 'vmuseum'),
        category:    'virtual-museum',
        icon:        'clock',
        attributes: {
            count:   { type: 'number', default: 8 },
            columns: { type: 'number', default: 4 },
        },
        edit: function (props) {
            var attr = props.attributes;
            var set  = props.setAttributes;
            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Einstellungen', 'vmuseum'), initialOpen: true },
                        el(RangeControl, { label: __('Anzahl Objekte', 'vmuseum'), value: attr.count,   min: 1, max: 32, onChange: function(v) { set({ count: v }); } }),
                        el(RangeControl, { label: __('Spalten', 'vmuseum'),         value: attr.columns, min: 2, max: 6,  onChange: function(v) { set({ columns: v }); } })
                    )
                ),
                el('div', useBlockProps(), makePreview('recent-objects', attr))
            );
        },
        save: function () { return null; },
    });

    // =========================================================
    // Block: vm/vitrine-objects
    // =========================================================
    blocks.registerBlockType('vm/vitrine-objects', {
        title:       __('VM: Vitrine', 'vmuseum'),
        description: __('Zeigt alle Objekte einer bestimmten Vitrine.', 'vmuseum'),
        category:    'virtual-museum',
        icon:        'archive',
        attributes: {
            vitrine_id: { type: 'number', default: 0 },
            per_page:   { type: 'number', default: 12 },
        },
        edit: function (props) {
            var attr = props.attributes;
            var set  = props.setAttributes;
            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Vitrine auswählen', 'vmuseum'), initialOpen: true },
                        el(SelectControl, { label: __('Vitrine', 'vmuseum'), value: attr.vitrine_id, options: data.vitrines, onChange: function(v) { set({ vitrine_id: parseInt(v, 10) }); } }),
                        el(RangeControl, { label: __('Objekte pro Seite', 'vmuseum'), value: attr.per_page, min: 4, max: 48, onChange: function(v) { set({ per_page: v }); } })
                    )
                ),
                el('div', useBlockProps(),
                    attr.vitrine_id
                        ? makePreview('vitrine-objects', attr)
                        : el(Placeholder, { icon: 'archive', label: __('VM: Vitrine', 'vmuseum'), instructions: __('Bitte eine Vitrine in der Seitenleiste auswählen.', 'vmuseum') })
                )
            );
        },
        save: function () { return null; },
    });

    // =========================================================
    // Block: vm/gallery-objects
    // =========================================================
    blocks.registerBlockType('vm/gallery-objects', {
        title:       __('VM: Galerie', 'vmuseum'),
        description: __('Zeigt alle Objekte einer bestimmten Galerie.', 'vmuseum'),
        category:    'virtual-museum',
        icon:        'images-alt2',
        attributes: {
            gallery_id: { type: 'number', default: 0 },
            per_page:   { type: 'number', default: 12 },
        },
        edit: function (props) {
            var attr = props.attributes;
            var set  = props.setAttributes;
            return el(Fragment, null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Galerie auswählen', 'vmuseum'), initialOpen: true },
                        el(SelectControl, { label: __('Galerie', 'vmuseum'), value: attr.gallery_id, options: data.galleries, onChange: function(v) { set({ gallery_id: parseInt(v, 10) }); } }),
                        el(RangeControl, { label: __('Objekte pro Seite', 'vmuseum'), value: attr.per_page, min: 4, max: 48, onChange: function(v) { set({ per_page: v }); } })
                    )
                ),
                el('div', useBlockProps(),
                    attr.gallery_id
                        ? makePreview('gallery-objects', attr)
                        : el(Placeholder, { icon: 'images-alt2', label: __('VM: Galerie', 'vmuseum'), instructions: __('Bitte eine Galerie in der Seitenleiste auswählen.', 'vmuseum') })
                )
            );
        },
        save: function () { return null; },
    });

})();
