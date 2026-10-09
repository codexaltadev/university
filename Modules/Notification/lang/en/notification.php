<?php

return [
    'view' => 'View',
    'greeting' => 'Hello :name,',
    'channels' => [
        'database' => 'In the admin panel (bell)',
        'mail' => 'By email',
    ],
    'my' => [
        'title' => 'My notifications',
        'help' => 'Choose how you hear about each kind of event. Nothing ticked = you are not told.',
        'none' => 'No notification types yet.',
    ],
    'templates' => [
        'title' => 'Notification templates',
        'type' => 'Notification',
        'subject' => 'Subject',
        'body' => 'Text',
        'active' => 'Use this wording',
        'active_help' => 'Off = the built-in text is sent.',
        'placeholders' => 'You can use: :list',
    ],
    'types' => [
        'module_installed' => 'Module installed or updated',
        'module_uninstalled' => 'Module uninstalled',
        'user_created' => 'New admin user',
    ],
    'texts' => [
        'module_installed' => [
            'subject' => 'Module {module} {version} installed',
            'body' => 'The module {module} (version {version}) was installed or updated.',
        ],
        'module_uninstalled' => [
            'subject' => 'Module {module} uninstalled',
            'body' => 'The module {module} (version {version}) was uninstalled.',
        ],
        'user_created' => [
            'subject' => 'New admin user: {name}',
            'body' => '{by} created an admin account for {name} ({email}).',
        ],
    ],
    'actions' => [
        'save' => 'Save',
        'use_default' => 'Use the built-in text',
    ],
    'notifications' => [
        'saved' => 'Saved.',
        'default_restored' => 'The built-in text is used again.',
    ],
];
