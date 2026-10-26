<?php

return [
    'title' => 'সচরাচর জিজ্ঞাসা',
    'search' => 'প্রশ্ন খুঁজুন…',
    'all' => 'সব',
    'other' => 'অন্যান্য প্রশ্ন',
    'no_match' => 'আপনার খোঁজার সাথে কোনো প্রশ্ন মেলেনি।',

    'section_label' => 'বাছাই করা প্রশ্ন',
    'metric_count' => 'FAQ-এর সংখ্যা',

    'fields' => [
        'question' => 'প্রশ্ন',
        'answer' => 'উত্তর',
        'category' => 'Category',
        'updated' => 'হালনাগাদ',
        'helpful' => 'কাজে লেগেছে',
    ],

    'helpful' => [
        'question' => 'এটি কি কাজে লেগেছে?',
        'yes' => 'হ্যাঁ',
        'no' => 'না',
        'thanks' => 'আপনার মতামতের জন্য ধন্যবাদ।',
    ],

    'block' => [
        'categories_help' => 'কিছু না বাছলে সব category।',
        'faqs' => 'অথবা এই প্রশ্নগুলো',
        'faqs_help' => 'বাছাই করা প্রশ্ন এই ক্রমে আসে, category-র বদলে।',
        'limit' => 'সর্বোচ্চ',
    ],

    'import' => [
        'action' => 'Import',
        'help' => 'Excel (.xlsx) বা CSV, প্রথম row-এ column-এর নাম: Question, Answer, Category (ঐচ্ছিক; নতুন নাম হলে নতুন category)।',
        'file' => 'File',
        'check_only' => 'শুধু যাচাই (save হবে না)',
        'bad_header' => 'প্রথম row-এ Question ও Answer (ও Category) column-এর নাম থাকতে হবে।',
        'line' => 'Line :line: প্রশ্ন বা উত্তর নেই',
        'checked' => 'যাচাই: :total row-এর :good টি import করা যাবে।',
        'done' => ':total row-এর :good টি import হয়েছে।',
    ],

    'settings' => [
        'title' => 'Page-এর শিরোনাম',
        'title_help' => 'শেষ শব্দটি রঙিন দেখায় (DIU: Frequently Asked Questions)।',
        'text' => 'শিরোনামের নিচের লাইন',
        'per_page' => 'প্রতি page-এ প্রশ্ন',
        'show_helpful_buttons' => '"এটি কি কাজে লেগেছে?" দেখাবে',
    ],
    'page' => [
        'categories' => 'Category',
        'questions' => ':countটি প্রশ্ন',
    ],
];
