<?php

return [
    'settings' => [
        'tab' => 'SEO',
        'title_pattern' => 'Page title-এর ধাঁচ',
        'title_pattern_help' => '{title} ও {site_name} ব্যবহার করুন, যেমন "{title} | {site_name}"।',
        'organization_type' => 'প্রতিষ্ঠানের ধরন (structured data)',
        'default_description' => 'Default বর্ণনা',
        'og_image' => 'Share করার default ছবি',
        'og_image_help' => 'Page-এর নিজের ছবি না থাকলে ব্যবহার হবে। 1200 × 630 সবচেয়ে ভালো।',
        'google_verification' => 'Google Search Console code',
        'bing_verification' => 'Bing Webmaster code',
        'robots_help' => 'খালি = admin panel ছাড়া সব crawl করা যাবে। Sitemap-এর লাইন নিজে থেকে যোগ হয়।',
    ],
    'redirects' => [
        'label' => 'redirect',
        'plural' => 'Redirect',
        'missing_target' => 'record মুছে গেছে বা লুকানো',
    ],
    'not_found' => [
        'label' => '404 address',
        'plural' => '404 log',
    ],
    'fields' => [
        'source_path' => 'পুরনো address',
        'kind' => 'ভিজিটরকে পাঠাবে',
        'destination_url' => 'নতুন address',
        'destination' => 'যেখানে যায়',
        'target' => 'Page বা record',
        'status_code' => 'ধরন',
        'hits' => 'ভিজিট',
        'last_hit_at' => 'শেষ ব্যবহার',
        'source' => 'কে তৈরি করেছে',
        'csv' => 'CSV file',
        'path' => 'Address',
        'last_seen_at' => 'শেষ দেখা',
        'first_seen_at' => 'প্রথম দেখা',
        'last_referrer' => 'কোথা থেকে এসেছে',
        'is_resolved' => 'Redirect হয়েছে',
    ],
    'kinds' => [
        'url' => 'একটি address',
        'record' => 'এই website-এর একটি page বা record',
    ],
    'status' => [
        '301' => '301 — স্থায়ীভাবে সরানো',
        '302' => '302 — আপাতত',
    ],
    'sources' => [
        'manual' => 'Admin',
        'import' => 'CSV import',
        '404' => '404 log',
        'slug' => 'Address পরিবর্তন',
    ],
    'help' => [
        'source_path' => 'Domain ছাড়া এই website-এর address, যেমন /admission/old-notice। ছোট-বড় হাতের অক্ষর আলাদা ধরা হয় না।',
        'destination_url' => 'এই website-এর path (/about) বা পূর্ণ address (https://…)।',
        'target' => 'পরে record-এর address বদলালেও এটি সঠিক জায়গায় পাঠাবে।',
        'import' => 'Column: পুরনো address, নতুন address, ঐচ্ছিকভাবে 301 বা 302। প্রথম সারিতে heading থাকলে বাদ যাবে; যে পুরনো address-এর redirect আগেই আছে সেটি update হবে।',
    ],
    'actions' => [
        'import' => 'CSV import',
        'create_redirect' => 'Redirect',
    ],
    'import' => [
        'done' => ':created যোগ, :updated update, :skipped বাদ',
        'line' => 'লাইন :line: :reason',
    ],
    'errors' => [
        'reserved' => 'এই address redirect করা যাবে না।',
        'taken' => 'এই পুরনো address-এর redirect আগেই আছে।',
        'loop' => 'এতে ভিজিটর ঘুরপাক খাবে (loop)।',
        'no_destination' => 'ভিজিটর কোথায় যাবে?',
        'bad_destination' => '/ দিয়ে শুরু path অথবা http(s):// দিয়ে শুরু পূর্ণ address দিন।',
    ],
    'notifications' => [
        'redirect_created' => 'Redirect তৈরি হয়েছে।',
    ],
];
