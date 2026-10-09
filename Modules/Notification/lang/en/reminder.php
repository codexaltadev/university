<?php

return [
    'rule' => 'content reminder',
    'rules' => 'Content reminders',
    'messages' => 'Messages',
    'days_suffix' => 'days',
    'days' => '{1} 1 day|[2,*] :count days',
    'module_off' => 'Module is off — not checked',
    'no_responsible' => 'no responsible user',
    'fields' => [
        'module' => 'Content',
        'entity_type' => 'For each',
        'max_days' => 'Must be updated within',
        'every_days' => 'Remind again every',
        'check_mode' => 'Check',
        'mandatory' => 'Must have content',
        'empty_message' => 'Message when there is no content',
        'stale_message' => 'Message when content is old',
        'admin_empty_message' => 'Note in the admin summary (no content)',
        'admin_stale_message' => 'Note in the admin summary (old content)',
    ],
    'modes' => [
        'latest' => 'Latest update',
        'each_record' => 'Each record',
    ],
    'help' => [
        'module' => 'Only modules that support reminders are listed.',
        'mandatory' => 'On: no content at all is reported as "empty".',
        'messages' => 'Optional. You can use {entity}, {module} and {days}. Empty = the built-in text.',
    ],
    'status' => [
        'empty' => 'no content',
        'stale' => 'not updated',
    ],
    'types' => [
        'content_reminder' => 'Content update reminder',
        'summary' => 'Weekly content reminder summary',
    ],
    'texts' => [
        'subject' => '{entity}: please update the {module}',
        'body' => "{message}\n\n{records}",
        'default_empty_message' => 'There is no :module yet. Please add some.',
        'default_stale_message' => 'The :module were last updated :days days ago. Please add or update them.',
        'summary_subject' => '{count} content items need an update',
        'summary_body' => "These need an update:\n\n{lines}",
    ],
    'report' => [
        'title' => 'Content freshness',
        'no_rules' => 'No content reminders yet. Add one under Site › Content reminders.',
        'not_checked' => 'Not checked yet — use "Check now".',
        'checked' => ':checked checked, :reminded reminders sent.',
        'reminders' => ':count reminders sent',
        'old_records' => '{1} 1 old record|[2,*] :count old records',
    ],
    'actions' => [
        'check' => 'Check now',
        'export' => 'Export CSV',
    ],
    'widget' => [
        'heading' => 'Content to update',
        'empty' => ':entity has no :module yet.',
        'stale' => ':entity: :module not updated for :days days.',
    ],
];
