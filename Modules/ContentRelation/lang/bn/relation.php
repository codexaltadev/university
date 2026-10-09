<?php

return [
    'days' => 'গত :count দিন',

    'fields' => [
        'content' => 'Content',
        'type' => 'ধরন',
        'shown_in' => 'যেখানে দেখায়',
        'added' => 'যোগ হয়েছে',
        'requested' => 'অনুরোধ',
        'period' => 'সময়',
    ],

    'queue' => [
        'title' => 'Main Site Queue',
        'subheading' => 'Faculty, department ও office-এ দেখানো কিন্তু main site-এ নেই এমন content। অনুরোধগুলো আগে।',
        'empty' => 'কিছু অপেক্ষায় নেই',
        'requested_by' => ':name অনুরোধ করেছেন',
        'dismiss_help' => 'যেখানে দেখাচ্ছে সেখানেই থাকবে, শুধু এই তালিকা থেকে সরে যাবে।',
    ],

    'highlights' => [
        'title' => 'Highlights',
        'place' => 'জায়গা',
        'empty' => 'এখানে কিছু highlight করা নেই',
        'empty_help' => 'Content-এর edit form-এর Highlight panel থেকে highlight করুন।',
        'unhighlight_help' => 'এখানে দেখাতে থাকবে, শুধু আগে নয়।',
    ],

    'actions' => [
        'publish' => 'Main site-এ প্রকাশ',
        'dismiss' => 'বাদ দিন',
        'unhighlight' => 'Highlight সরান',
    ],

    'notifications' => [
        'publish' => ':countটি content main site-এ প্রকাশ হয়েছে',
        'dismiss' => ':countটি বাদ দেওয়া হয়েছে',
        'unhighlighted' => 'Highlight সরানো হয়েছে',
    ],
];
