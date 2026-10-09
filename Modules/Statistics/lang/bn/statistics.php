<?php

return [
    'title' => 'পরিসংখ্যান',
    'set' => 'Counter set',
    'default_set' => 'Default',
    'empty' => 'এখানে এখনো কোনো counter নেই। Website section লুকাবে অথবা theme-এর default দেখাবে।',
    'preview' => 'এখন :value → দেখাবে ":display"',
    'sources' => [
        'manual' => 'হাতে লেখা',
        'metric' => 'স্বয়ংক্রিয়',
    ],
    'roundings' => [
        'none' => '1,234',
        'hundred' => '1,200+',
        'thousand' => '1,000+',
        'plus' => '1,234+',
    ],
    'fields' => [
        'key' => 'Set key',
        'name' => 'নাম',
        'items' => 'Counter',
        'label' => 'লেখা',
        'source' => 'মান',
        'metric' => 'যা গণনা হবে',
        'value' => 'সংখ্যা',
        'override' => 'নির্দিষ্ট সংখ্যা',
        'rounding' => 'যেভাবে দেখাবে',
        'prefix' => 'আগে',
        'suffix' => 'পরে',
        'icon' => 'Icon',
        'as_of' => 'তারিখ অনুযায়ী',
    ],
    'help' => [
        'override' => 'ঐচ্ছিক: গণনা করা সংখ্যার বদলে এটি দেখাবে।',
    ],
    'problems' => [
        'inactive' => 'বন্ধ',
        'module_off' => 'module চালু নেই, লুকানো',
        'no_value' => 'সংখ্যা নেই, লুকানো',
    ],
    'actions' => [
        'create' => 'Counter তৈরি',
        'new_set' => 'নতুন set',
        'add_item' => 'Counter যোগ',
        'save' => 'সংরক্ষণ',
        'delete' => 'Set মুছুন',
    ],
    'notifications' => [
        'created' => 'Counter set তৈরি হয়েছে',
        'saved' => 'Counter সংরক্ষিত',
        'deleted' => 'Counter set মুছে ফেলা হয়েছে',
    ],
];
