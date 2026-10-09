{{--
    Media Library grid (FR-MEDIA-01): square picture cards of equal size, one-line
    name, actions as large icons centred over the picture on hover (always shown on touch screens).
    Scoped to .uwms-media-grid (set by ListMediaItems in grid view only).
--}}
<style>
    .uwms-media-grid .fi-ta-record { position: relative; }
    .uwms-media-grid .fi-ta-record-content-ctn,
    .uwms-media-grid .fi-ta-record-content { min-width: 0; }
    .uwms-media-grid .fi-ta-record-content { padding: .5rem !important; }

    /* Picture fills the card width, always square */
    .uwms-media-grid .uwms-media-thumb,
    .uwms-media-grid .uwms-media-thumb > div { width: 100%; }
    .uwms-media-grid .uwms-media-thumb img {
        display: block; width: 100% !important; height: auto !important; aspect-ratio: 1 / 1;
        object-fit: cover; border-radius: .5rem; background: rgba(148, 163, 184, .14);
    }
    .uwms-media-grid .uwms-media-thumb img.uwms-contain { object-fit: contain; padding: .75rem; }

    /* One line of name, one line of facts: every card the same height */
    .uwms-media-grid .uwms-media-line,
    .uwms-media-grid .uwms-media-line * {
        display: block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }

    /* Actions: centred over the picture on a dim layer, on hover / keyboard focus.
       The layer has the picture's size (card padding .5rem, square); clicks outside
       the icons go through to the card, which opens the details. */
    .uwms-media-grid .fi-ta-record .fi-ta-actions {
        position: absolute; top: .5rem; inset-inline: .5rem; aspect-ratio: 1 / 1; z-index: 2;
        display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: .65rem !important;
        padding: .5rem; border-radius: .5rem; background: rgba(15, 23, 42, .45);
        pointer-events: none; opacity: 0; transition: opacity .15s ease;
    }
    .uwms-media-grid .fi-ta-record .fi-ta-actions > * { pointer-events: auto; }
    .uwms-media-grid .fi-ta-record .fi-ta-actions .fi-icon-btn {
        width: 2.25rem; height: 2.25rem; margin: 0 !important; flex-shrink: 0; border-radius: 9999px;
        background: rgba(255, 255, 255, .95); box-shadow: 0 2px 6px rgba(15, 23, 42, .3);
    }
    .uwms-media-grid .fi-ta-record .fi-ta-actions .fi-icon-btn:hover { background: #fff; transform: scale(1.06); }
    .uwms-media-grid .fi-ta-record .fi-ta-actions .fi-icon-btn svg { width: 1.15rem; height: 1.15rem; }
    .dark .uwms-media-grid .fi-ta-record .fi-ta-actions .fi-icon-btn { background: rgba(31, 41, 55, .95); }
    .uwms-media-grid .fi-ta-record:hover .fi-ta-actions,
    .uwms-media-grid .fi-ta-record:focus-within .fi-ta-actions { opacity: 1; }

    /* Selection box over the picture's other corner */
    .uwms-media-grid .fi-ta-record-checkbox {
        position: absolute; top: 1rem; inset-inline-start: 1rem; z-index: 3;
        opacity: 0; transition: opacity .15s ease;
    }
    .uwms-media-grid .fi-ta-record:hover .fi-ta-record-checkbox,
    .uwms-media-grid .fi-ta-record-checkbox:checked { opacity: 1; }

    /* Touch screens have no hover: icons always visible, along the picture's bottom, no dim layer */
    @media (hover: none) {
        .uwms-media-grid .fi-ta-record .fi-ta-actions { opacity: 1; background: none; align-items: flex-end; }
        .uwms-media-grid .fi-ta-record-checkbox { opacity: 1; }
    }
</style>
