<?php

return [
    'rule' => 'content reminder',
    'rules' => 'Content reminder',
    'messages' => 'বার্তা',
    'days_suffix' => 'দিন',
    'days' => '[1,*] :count দিন',
    'module_off' => 'Module বন্ধ — যাচাই হচ্ছে না',
    'no_responsible' => 'কোনো দায়িত্বপ্রাপ্ত user নেই',
    'fields' => [
        'module' => 'Content',
        'entity_type' => 'প্রতিটি',
        'max_days' => 'যত দিনের মধ্যে update করতে হবে',
        'every_days' => 'আবার মনে করাবে প্রতি',
        'check_mode' => 'যাচাই',
        'mandatory' => 'Content থাকতেই হবে',
        'empty_message' => 'Content না থাকলে বার্তা',
        'stale_message' => 'Content পুরনো হলে বার্তা',
        'admin_empty_message' => 'Admin সারাংশে নোট (content নেই)',
        'admin_stale_message' => 'Admin সারাংশে নোট (content পুরনো)',
    ],
    'modes' => [
        'latest' => 'সর্বশেষ update',
        'each_record' => 'প্রতিটি record',
    ],
    'help' => [
        'module' => 'শুধু reminder সমর্থন করে এমন module দেখাচ্ছে।',
        'mandatory' => 'চালু: কোনো content না থাকলে "খালি" হিসেবে জানানো হবে।',
        'messages' => 'ঐচ্ছিক। {entity}, {module} ও {days} ব্যবহার করা যাবে। খালি = built-in লেখা।',
    ],
    'status' => [
        'empty' => 'content নেই',
        'stale' => 'update হয়নি',
    ],
    'types' => [
        'content_reminder' => 'Content update reminder',
        'summary' => 'Content reminder-এর সাপ্তাহিক সারাংশ',
    ],
    'texts' => [
        'subject' => '{entity}: {module} update করুন',
        'body' => "{message}\n\n{records}",
        'default_empty_message' => 'এখনো কোনো :module নেই। অনুগ্রহ করে যোগ করুন।',
        'default_stale_message' => ':module সর্বশেষ :days দিন আগে update হয়েছে। অনুগ্রহ করে যোগ বা update করুন।',
        'summary_subject' => '{count}টি content update প্রয়োজন',
        'summary_body' => "এগুলো update প্রয়োজন:\n\n{lines}",
    ],
    'report' => [
        'title' => 'Content freshness',
        'no_rules' => 'এখনো কোনো content reminder নেই। Site › Content reminder-এ যোগ করুন।',
        'not_checked' => 'এখনো যাচাই হয়নি — "এখন যাচাই করুন" ব্যবহার করুন।',
        'checked' => ':checked টি যাচাই, :reminded টি reminder পাঠানো হয়েছে।',
        'reminders' => ':count টি reminder পাঠানো হয়েছে',
        'old_records' => '[1,*] :count টি পুরনো record',
    ],
    'actions' => [
        'check' => 'এখন যাচাই করুন',
        'export' => 'CSV export',
    ],
    'widget' => [
        'heading' => 'Update করার মতো content',
        'empty' => ':entity-এ এখনো কোনো :module নেই।',
        'stale' => ':entity: :module :days দিন update হয়নি।',
    ],
];
