<?php

return [
    'title' => 'Important Links',
    'singular' => 'link',
    'plural' => 'Important Links',
    'all' => 'All',
    'other' => 'Other links',
    'none' => 'No links here yet.',
    'new_tab' => '(opens in a new tab)',
    'check_now' => 'Check now',
    'view_site' => 'View on the website',
    'layouts' => [
        'tiles' => 'Tiles',
        'list' => 'List',
    ],
    'section' => [
        'label' => 'Important Links',
        'display' => 'Show as',
        'count' => 'How many',
    ],
    'footer' => [
        'label' => 'Important Links',
    ],
    'fields' => [
        'title' => 'Title',
        'description' => 'Description',
        'url' => 'Address',
        'open_in_new_tab' => 'Open in a new tab',
        'icon' => 'Icon or logo',
        'group' => 'Group',
        'status' => 'Last check',
    ],
    'help' => [
        'url' => 'https://… for another site, or /… for a page of this site.',
        'icon' => 'A small logo from the Media Library, e.g. the UGC logo.',
    ],
    'status' => [
        'ok' => 'Works',
        'broken' => 'Broken (:code)',
        'broken_filter' => 'Broken',
        'unchecked' => 'Not checked yet',
        'internal' => 'This site',
    ],
    'notice' => [
        'label' => 'Important link broken',
        'subject' => 'A link does not work: {title}',
        'body' => 'The weekly check could not open "{title}" ({url}) — answer {status}. Please correct or remove it.',
    ],
    'errors' => [
        'url' => 'Give an address starting with https://, http:// or / (or mailto: / tel:).',
    ],
];
