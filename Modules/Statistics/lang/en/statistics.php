<?php

return [
    'title' => 'Statistics',
    'set' => 'Counter set',
    'default_set' => 'Default',
    'empty' => 'No counters here yet. The website hides the section, or shows the theme\'s default.',
    'preview' => 'Now :value → shows ":display"',
    'sources' => [
        'manual' => 'Manual',
        'metric' => 'Automatic',
    ],
    'roundings' => [
        'none' => '1,234',
        'hundred' => '1,200+',
        'thousand' => '1,000+',
        'plus' => '1,234+',
    ],
    'fields' => [
        'key' => 'Set key',
        'name' => 'Name',
        'items' => 'Counters',
        'label' => 'Label',
        'source' => 'Value',
        'metric' => 'Counts',
        'value' => 'Number',
        'override' => 'Fixed number',
        'rounding' => 'Shown as',
        'prefix' => 'Before',
        'suffix' => 'After',
        'icon' => 'Icon',
        'as_of' => 'As of',
    ],
    'help' => [
        'override' => 'Optional: shown instead of the counted number.',
    ],
    'problems' => [
        'inactive' => 'switched off',
        'module_off' => 'its module is not active, hidden',
        'no_value' => 'no number yet, hidden',
    ],
    'actions' => [
        'create' => 'Create counters',
        'new_set' => 'New set',
        'add_item' => 'Add counter',
        'save' => 'Save',
        'delete' => 'Delete set',
    ],
    'notifications' => [
        'created' => 'Counter set created',
        'saved' => 'Counters saved',
        'deleted' => 'Counter set deleted',
    ],
];
