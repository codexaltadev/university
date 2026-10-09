<?php

return [
    'language' => 'Language',
    'languages' => 'Languages',
    'default' => 'Default',

    'fields' => [
        'code' => 'Code',
        'name' => 'Name (English)',
        'native_name' => 'Name',
        'direction' => 'Direction',
        'active' => 'Active',
        'native_digits' => 'Native digits',
    ],

    'direction' => ['ltr' => 'Left to right', 'rtl' => 'Right to left'],

    'help' => [
        'code' => 'Two-letter language code (en, bn, ar). Used in website addresses: /bn/…. Cannot be changed later.',
        'inactive' => 'Inactive: the website stops offering this language; existing translations are kept.',
        'native_digits' => 'Show numbers and dates on the website in this language\'s digits (e.g. ১২৩).',
        'make_default' => 'Content without a translation falls back to the default language.',
        'delete' => 'Translations already written in this language stay in the database.',
    ],

    'actions' => ['make_default' => 'Make default'],
    'notifications' => ['default_changed' => 'Default language changed'],

    'texts' => [
        'title' => 'Interface texts',
        'subheading' => 'Buttons and labels on the website, such as "Read more". Your changes replace the built-in text.',
        'key' => 'Key',
        'changed' => 'Changed',
        'missing' => 'Missing',
        'edit' => 'Edit',
        'reset' => 'Reset',
        'saved' => 'Text saved',
        'empty_uses_default' => 'Leave empty to use the built-in text.',
    ],
];
