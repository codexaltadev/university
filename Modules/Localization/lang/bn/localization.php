<?php

return [
    'language' => 'ভাষা',
    'languages' => 'ভাষা',
    'default' => 'Default',

    'fields' => [
        'code' => 'Code',
        'name' => 'নাম (English)',
        'native_name' => 'নাম',
        'direction' => 'লেখার দিক',
        'active' => 'চালু',
        'native_digits' => 'নিজস্ব অঙ্ক',
    ],

    'direction' => ['ltr' => 'বাম থেকে ডানে', 'rtl' => 'ডান থেকে বামে'],

    'help' => [
        'code' => 'দুই অক্ষরের ভাষা code (en, bn, ar)। Website-এর ঠিকানায় ব্যবহার হয়: /bn/…। পরে বদলানো যাবে না।',
        'inactive' => 'বন্ধ করলে website-এ এই ভাষা দেখাবে না; লেখা অনুবাদ থেকে যাবে।',
        'native_digits' => 'Website-এ সংখ্যা ও তারিখ এই ভাষার অঙ্কে দেখাবে (যেমন ১২৩)।',
        'make_default' => 'অনুবাদ না থাকলে content default ভাষায় দেখাবে।',
        'delete' => 'এই ভাষায় লেখা অনুবাদ database-এ থেকে যাবে।',
    ],

    'actions' => ['make_default' => 'Default করুন'],
    'notifications' => ['default_changed' => 'Default ভাষা বদলানো হয়েছে'],

    'texts' => [
        'title' => 'Interface লেখা',
        'subheading' => 'Website-এর button ও label, যেমন "আরও পড়ুন"। আপনার পরিবর্তন built-in লেখার জায়গায় দেখাবে।',
        'key' => 'Key',
        'changed' => 'পরিবর্তিত',
        'missing' => 'নেই',
        'edit' => 'সম্পাদনা',
        'reset' => 'আগের মতো',
        'saved' => 'লেখা সংরক্ষিত হয়েছে',
        'empty_uses_default' => 'খালি রাখলে built-in লেখা দেখাবে।',
    ],
];
