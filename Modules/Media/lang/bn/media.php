<?php

return [
    'title' => 'Media Library',
    'item' => 'File',
    'folders' => 'Folder',
    'no_folder' => 'কোনো folder নয়',
    'main_site' => 'Main site',
    'unused' => 'অব্যবহৃত',
    'per_row' => '[1,*] প্রতি সারিতে :countটি',
    'not_used' => 'এখনো কোথাও ব্যবহার হয়নি।',

    'kinds' => [
        'image' => 'ছবি', 'document' => 'Document', 'video' => 'ভিডিও',
        'audio' => 'অডিও', 'archive' => 'Archive', 'other' => 'অন্যান্য',
    ],

    'sections' => ['file' => 'File', 'usage' => 'যেখানে ব্যবহৃত'],

    'fields' => [
        'files' => 'File',
        'file' => 'File',
        'title' => 'শিরোনাম',
        'alt_text' => 'বিকল্প লেখা (alt)',
        'caption' => 'Caption',
        'description' => 'বিবরণ',
        'folder' => 'Folder',
        'parent' => 'যে folder-এর ভেতরে',
        'name' => 'নাম',
        'shared' => 'সবার সাথে শেয়ার',
        'private' => 'Private',
        'active' => 'চালু',
        'kind' => 'ধরন',
        'size' => 'আকার',
        'used' => 'ব্যবহার',
        'uploader' => 'যিনি upload করেছেন',
        'uploaded' => 'Upload',
        'details' => 'বিস্তারিত',
        'link' => 'Link',
        'scope' => 'যার',
        'from' => 'থেকে',
        'until' => 'পর্যন্ত',
    ],

    'browse' => [
        'all_files' => 'সব file',
        'searching_everywhere' => 'সব folder-এ খোঁজা হচ্ছে',
        'files_here' => '{0} এখানে কোনো file নেই|[1,*] এখানে :countটি file',
        'files_count' => '{0} খালি|[1,*] :countটি file',
        'folders_count' => '[1,*] :countটি folder',
    ],

    'filters' => ['unused' => 'অব্যবহৃত file'],

    'help' => [
        'allowed' => 'অনুমোদিত: :types। প্রতিটি সর্বোচ্চ :max MB, একসাথে ৫০টি।',
        'shared' => 'শেয়ার করা file সব department ও office ব্যবহার করতে পারবে।',
        'private' => 'শুধু মেয়াদি link দিয়ে খোলা যাবে, public ঠিকানা নেই।',
        'alt_text' => 'Screen reader ও search engine-এর জন্য ছবির বর্ণনা।',
        'alt_text_upload' => 'ঐচ্ছিক। Screen reader ও search engine-এর জন্য ছবির বর্ণনা; এই upload-এর সব file-এ বসবে, পরে প্রতিটি আলাদা করে বদলানো যাবে।',
        'replace' => 'নতুন file পুরনোটির জায়গায় সব জায়গায় বসবে; link একই থাকবে।',
        'delete' => 'File trash-এ যাবে।',
        'delete_used' => 'এই file :countটি জায়গায় ব্যবহৃত। সেখান থেকে সরে যাবে:',
    ],

    'actions' => [
        'upload' => 'Upload',
        'more' => 'আরও',
        'details' => 'বিস্তারিত',
        'download' => 'Download',
        'replace' => 'File বদলান',
        'move' => 'Folder-এ সরান',
        'new_folder' => 'নতুন folder',
        'rename_folder' => 'Folder-এর নাম বদলান',
        'delete_folder' => 'Folder মুছুন',
        'by_folder' => 'Folder অনুযায়ী',
        'list_view' => 'তালিকা',
        'grid_view' => 'Grid',
    ],

    'notifications' => [
        'uploaded' => ':countটি file upload হয়েছে',
        'rejected' => ':countটি file নেওয়া হয়নি',
        'duplicates' => 'Library-তে আগে থেকেই আছে',
        'replaced' => 'File বদলানো হয়েছে',
        'folder_saved' => 'Folder সংরক্ষিত',
        'folder_deleted' => 'Folder মুছে ফেলা হয়েছে',
        'folder_not_empty' => 'আগে ভেতরের file ও folder সরান বা মুছুন।',
    ],
];
