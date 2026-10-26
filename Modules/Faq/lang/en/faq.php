<?php

return [
    'title' => 'FAQ',
    'search' => 'Search the questions…',
    'all' => 'All',
    'other' => 'Other questions',
    'no_match' => 'No question matches your search.',

    'section_label' => 'Selected questions',
    'metric_count' => 'Number of FAQs',

    'fields' => [
        'question' => 'Question',
        'answer' => 'Answer',
        'category' => 'Category',
        'updated' => 'Updated',
        'helpful' => 'Helpful',
    ],

    'helpful' => [
        'question' => 'Was this helpful?',
        'yes' => 'Yes',
        'no' => 'No',
        'thanks' => 'Thank you for your feedback.',
    ],

    'block' => [
        'categories_help' => 'None = all categories.',
        'faqs' => 'Or these questions',
        'faqs_help' => 'Picked questions come in this order, in place of the categories.',
        'limit' => 'At most',
    ],

    'import' => [
        'action' => 'Import',
        'help' => 'Excel (.xlsx) or CSV with a header row: Question, Answer, Category (optional; a new name becomes a category).',
        'file' => 'File',
        'check_only' => 'Check only (save nothing)',
        'bad_header' => 'The first row must name the columns Question and Answer (and Category).',
        'line' => 'Line :line: no question or answer',
        'checked' => 'Checked: :good of :total rows can be imported.',
        'done' => 'Imported :good of :total rows.',
    ],

    'settings' => [
        'title' => 'Page title',
        'title_help' => 'Its last word shows in colour (DIU: Frequently Asked Questions).',
        'text' => 'Line under the title',
        'per_page' => 'Questions per page',
        'show_helpful_buttons' => 'Show "Was this helpful?"',
    ],
    'page' => [
        'categories' => 'Categories',
        'questions' => '{1} :count question|[2,*] :count questions',
    ],
];
