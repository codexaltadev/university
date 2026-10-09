<?php

return [
    'module' => 'Module',
    'none' => 'None',
    'no_readme' => 'This module has no README.',

    'tabs' => ['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive', 'core' => 'Core'],
    'type' => ['core' => 'Core', 'installable' => 'Installable'],
    'status' => ['active' => 'Active', 'inactive' => 'Inactive', 'uninstalled' => 'Uninstalled'],

    'fields' => [
        'name' => 'Module',
        'slug' => 'Slug',
        'version' => 'Version',
        'type' => 'Type',
        'status' => 'Status',
        'relations' => 'Needs ← / → Used by',
        'activated' => 'Activated',
        'path' => 'Folder',
        'author' => 'Author',
        'depends_on' => 'Needs',
        'used_by' => 'Used by',
        'from' => 'From',
        'when' => 'When',
        'operation' => 'Operation',
        'error' => 'Error',
        'package' => 'Module ZIP',
        'data' => 'Module data',
        'confirm' => 'Type ":slug" to confirm',
    ],

    'sections' => [
        'about' => 'About',
        'facts' => 'Details',
        'permissions' => 'Permissions',
        'versions' => 'Version history',
        'operations' => 'Install / activate log',
    ],

    'actions' => [
        'install' => 'Install from ZIP',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'uninstall' => 'Uninstall',
        'details' => 'Details',
    ],

    'help' => [
        'install' => 'The package is checked before anything is installed. A new module starts inactive. Try new modules on the staging site first.',
        'deactivate' => 'The module disappears from the admin and the website. Its data, permissions and settings are kept.',
        'uninstall' => 'Keep data: the code is removed, the data stays and comes back if the module is installed again. Delete data: its tables and permissions are removed for good.',
    ],

    'uninstall' => [
        'keep' => 'Keep the data',
        'delete' => 'Delete the data permanently',
    ],

    'done' => [
        'installed' => ':name :version installed (inactive)',
        'activated' => ':name activated',
        'deactivated' => ':name deactivated',
        'uninstalled' => ':name uninstalled',
    ],

    'errors' => [
        'refused' => 'Refused — nothing was changed',
        'confirm' => 'Type ":slug" exactly to delete the data.',
    ],
];
