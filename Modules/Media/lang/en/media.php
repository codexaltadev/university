<?php

return [
    'title' => 'Media Library',
    'item' => 'File',
    'folders' => 'Folders',
    'no_folder' => 'No folder',
    'main_site' => 'Main site',
    'unused' => 'unused',
    'per_row' => '{1} :count per row|[2,*] :count per row',
    'not_used' => 'Not used anywhere yet.',

    'kinds' => [
        'image' => 'Image', 'document' => 'Document', 'video' => 'Video',
        'audio' => 'Audio', 'archive' => 'Archive', 'other' => 'Other',
    ],

    'sections' => ['file' => 'File', 'usage' => 'Used in'],

    'fields' => [
        'files' => 'Files',
        'file' => 'File',
        'title' => 'Title',
        'alt_text' => 'Alternative text',
        'caption' => 'Caption',
        'description' => 'Description',
        'folder' => 'Folder',
        'parent' => 'Inside folder',
        'name' => 'Name',
        'shared' => 'Shared with everyone',
        'private' => 'Private',
        'active' => 'Active',
        'kind' => 'Type',
        'size' => 'Size',
        'used' => 'Used',
        'uploader' => 'Uploaded by',
        'uploaded' => 'Uploaded',
        'details' => 'Details',
        'link' => 'Link',
        'scope' => 'Belongs to',
        'from' => 'From',
        'until' => 'Until',
    ],

    'browse' => [
        'all_files' => 'All files',
        'searching_everywhere' => 'Searching in every folder',
        'files_here' => '{0} No files here|{1} 1 file here|[2,*] :count files here',
        'files_count' => '{0} empty|{1} 1 file|[2,*] :count files',
        'folders_count' => '{1} 1 folder|[2,*] :count folders',
    ],

    'filters' => ['unused' => 'Unused files'],

    'help' => [
        'allowed' => 'Allowed: :types. Up to :max MB each, 50 files at a time.',
        'shared' => 'Shared files can be used by every department and office.',
        'private' => 'Only reachable through an expiring link, never by a public address.',
        'alt_text' => 'Describes the picture for screen readers and search engines.',
        'alt_text_upload' => 'Optional. Describes the pictures for screen readers and search engines; used for every file of this upload and can be changed per file later.',
        'replace' => 'The new file takes the place of the old one everywhere it is used; the link stays the same.',
        'delete' => 'The file moves to the trash.',
        'delete_used' => 'This file is used in :count place(s). It will disappear from them:',
    ],

    'actions' => [
        'upload' => 'Upload',
        'more' => 'More',
        'details' => 'Details',
        'download' => 'Download',
        'replace' => 'Replace file',
        'move' => 'Move to folder',
        'new_folder' => 'New folder',
        'rename_folder' => 'Rename folder',
        'delete_folder' => 'Delete folder',
        'by_folder' => 'By folder',
        'list_view' => 'List view',
        'grid_view' => 'Grid view',
    ],

    'notifications' => [
        'uploaded' => ':count file(s) uploaded',
        'rejected' => ':count file(s) refused',
        'duplicates' => 'Already in the library',
        'replaced' => 'File replaced',
        'folder_saved' => 'Folder saved',
        'folder_deleted' => 'Folder deleted',
        'folder_not_empty' => 'Move or delete the files and folders inside first.',
    ],
];
