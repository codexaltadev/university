<?php

return [
    'module' => 'মডিউল',
    'none' => 'নেই',
    'no_readme' => 'এই মডিউলের README নেই।',

    'tabs' => ['all' => 'সব', 'active' => 'চালু', 'inactive' => 'বন্ধ', 'core' => 'Core'],
    'type' => ['core' => 'Core', 'installable' => 'Installable'],
    'status' => ['active' => 'চালু', 'inactive' => 'বন্ধ', 'uninstalled' => 'Uninstall করা'],

    'fields' => [
        'name' => 'মডিউল',
        'slug' => 'Slug',
        'version' => 'Version',
        'type' => 'ধরন',
        'status' => 'অবস্থা',
        'relations' => 'নির্ভর করে ← / → ব্যবহার করে',
        'activated' => 'চালু হয়েছে',
        'path' => 'Folder',
        'author' => 'নির্মাতা',
        'depends_on' => 'নির্ভর করে',
        'used_by' => 'ব্যবহার করে',
        'from' => 'আগের',
        'when' => 'কখন',
        'operation' => 'কাজ',
        'error' => 'ত্রুটি',
        'package' => 'মডিউল ZIP',
        'data' => 'মডিউলের data',
        'confirm' => 'নিশ্চিত করতে ":slug" লিখুন',
    ],

    'sections' => [
        'about' => 'পরিচিতি',
        'facts' => 'তথ্য',
        'permissions' => 'অনুমতি',
        'versions' => 'Version ইতিহাস',
        'operations' => 'Install / চালুর log',
    ],

    'actions' => [
        'install' => 'ZIP থেকে install',
        'activate' => 'চালু করুন',
        'deactivate' => 'বন্ধ করুন',
        'uninstall' => 'Uninstall',
        'details' => 'বিস্তারিত',
    ],

    'help' => [
        'install' => 'কিছু install করার আগে package যাচাই হয়। নতুন মডিউল বন্ধ অবস্থায় শুরু হয়। নতুন মডিউল আগে staging site-এ পরীক্ষা করুন।',
        'deactivate' => 'মডিউলটি admin ও website থেকে লুকাবে। এর data, অনুমতি ও setting থেকে যাবে।',
        'uninstall' => 'Data রাখুন: code মুছে যাবে, data থাকবে এবং আবার install করলে ফিরে আসবে। Data মুছুন: table ও অনুমতি চিরতরে মুছে যাবে।',
    ],

    'uninstall' => [
        'keep' => 'Data রেখে দিন',
        'delete' => 'Data চিরতরে মুছে ফেলুন',
    ],

    'done' => [
        'installed' => ':name :version install হয়েছে (বন্ধ অবস্থায়)',
        'activated' => ':name চালু হয়েছে',
        'deactivated' => ':name বন্ধ হয়েছে',
        'uninstalled' => ':name uninstall হয়েছে',
    ],

    'errors' => [
        'refused' => 'বাতিল — কিছুই পরিবর্তন হয়নি',
        'confirm' => 'Data মুছতে হুবহু ":slug" লিখুন।',
    ],
];
