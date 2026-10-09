<?php

return [
    'view' => 'দেখুন',
    'greeting' => 'প্রিয় :name,',
    'channels' => [
        'database' => 'Admin panel-এ (bell)',
        'mail' => 'Email-এ',
    ],
    'my' => [
        'title' => 'আমার notification',
        'help' => 'প্রতিটি ঘটনা কীভাবে জানতে চান বেছে নিন। কিছু টিক না দিলে জানানো হবে না।',
        'none' => 'এখনো কোনো notification-এর ধরন নেই।',
    ],
    'templates' => [
        'title' => 'Notification template',
        'type' => 'Notification',
        'subject' => 'Subject',
        'body' => 'লেখা',
        'active' => 'এই লেখা ব্যবহার করুন',
        'active_help' => 'বন্ধ = built-in লেখা পাঠানো হবে।',
        'placeholders' => 'ব্যবহার করা যাবে: :list',
    ],
    'types' => [
        'module_installed' => 'Module install বা update',
        'module_uninstalled' => 'Module uninstall',
        'user_created' => 'নতুন admin user',
    ],
    'texts' => [
        'module_installed' => [
            'subject' => 'Module {module} {version} install হয়েছে',
            'body' => '{module} module (version {version}) install বা update হয়েছে।',
        ],
        'module_uninstalled' => [
            'subject' => 'Module {module} uninstall হয়েছে',
            'body' => '{module} module (version {version}) uninstall হয়েছে।',
        ],
        'user_created' => [
            'subject' => 'নতুন admin user: {name}',
            'body' => '{by} {name} ({email})-এর জন্য admin account তৈরি করেছেন।',
        ],
    ],
    'actions' => [
        'save' => 'Save',
        'use_default' => 'Built-in লেখা ব্যবহার করুন',
    ],
    'notifications' => [
        'saved' => 'Save হয়েছে।',
        'default_restored' => 'আবার built-in লেখা ব্যবহার হচ্ছে।',
    ],
];
