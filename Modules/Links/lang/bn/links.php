<?php

return [
    'title' => 'গুরুত্বপূর্ণ লিংক',
    'singular' => 'লিংক',
    'plural' => 'গুরুত্বপূর্ণ লিংক',
    'all' => 'সব',
    'other' => 'অন্যান্য লিংক',
    'none' => 'এখানে এখনো কোনো লিংক নেই।',
    'new_tab' => '(নতুন tab-এ খুলবে)',
    'check_now' => 'এখনই যাচাই',
    'view_site' => 'ওয়েবসাইটে দেখুন',
    'layouts' => [
        'tiles' => 'টাইল',
        'list' => 'তালিকা',
    ],
    'section' => [
        'label' => 'গুরুত্বপূর্ণ লিংক',
        'display' => 'দেখাবে',
        'count' => 'কয়টি',
    ],
    'footer' => [
        'label' => 'গুরুত্বপূর্ণ লিংক',
    ],
    'fields' => [
        'title' => 'শিরোনাম',
        'description' => 'বিবরণ',
        'url' => 'ঠিকানা',
        'open_in_new_tab' => 'নতুন tab-এ খুলবে',
        'icon' => 'আইকন বা লোগো',
        'group' => 'গ্রুপ',
        'status' => 'শেষ যাচাই',
    ],
    'help' => [
        'url' => 'অন্য site হলে https://…, এই site-এর page হলে /…',
        'icon' => 'Media Library থেকে ছোট লোগো, যেমন UGC-র লোগো।',
    ],
    'status' => [
        'ok' => 'কাজ করছে',
        'broken' => 'ভাঙা (:code)',
        'broken_filter' => 'ভাঙা',
        'unchecked' => 'এখনো যাচাই হয়নি',
        'internal' => 'এই site',
    ],
    'notice' => [
        'label' => 'গুরুত্বপূর্ণ লিংক ভাঙা',
        'subject' => 'একটি লিংক কাজ করছে না: {title}',
        'body' => 'সাপ্তাহিক যাচাইয়ে "{title}" ({url}) খোলা যায়নি — উত্তর {status}। ঠিক করুন বা সরিয়ে দিন।',
    ],
    'errors' => [
        'url' => 'https://, http:// বা / দিয়ে শুরু ঠিকানা দিন (অথবা mailto: / tel:)।',
    ],
];
