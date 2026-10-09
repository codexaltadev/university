<?php

return [
    'days' => 'Last :count days',

    'fields' => [
        'content' => 'Content',
        'type' => 'Type',
        'shown_in' => 'Shown in',
        'added' => 'Added',
        'requested' => 'Requested',
        'period' => 'Period',
    ],

    'queue' => [
        'title' => 'Main Site Queue',
        'subheading' => 'Content shown in faculties, departments and offices but not on the main site. Requests come first.',
        'empty' => 'Nothing is waiting',
        'requested_by' => 'Requested by :name',
        'dismiss_help' => 'It stays where it is shown now and leaves this queue.',
    ],

    'highlights' => [
        'title' => 'Highlights',
        'place' => 'Place',
        'empty' => 'Nothing is highlighted here',
        'empty_help' => 'Highlight content from its edit form, in the Highlight panel.',
        'unhighlight_help' => 'It stays shown here, just not first.',
    ],

    'actions' => [
        'publish' => 'Publish to main site',
        'dismiss' => 'Dismiss',
        'unhighlight' => 'Remove highlight',
    ],

    'notifications' => [
        'publish' => ':count item(s) published to the main site',
        'dismiss' => ':count item(s) dismissed',
        'unhighlighted' => 'Highlight removed',
    ],
];
