<?php

return [
    'title' => 'Users',
    'never' => 'Never',

    'form' => [
        'account' => 'Account',
        'access' => 'Access',
        'password' => 'Password',
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'username' => 'Username',
        'phone' => 'Phone',
        'locale' => 'Admin language',
        'active' => 'Active',
        'roles' => 'Roles',
        'scope' => 'Works on',
        'send_invitation' => 'Send invitation',
        'password' => 'Password',
        'new_password' => 'New password',
        'last_login' => 'Last login',
    ],

    'help' => [
        'active' => 'Inactive users cannot sign in; an open session ends at once.',
        'access' => 'Roles decide what the user can do; "Works on" decides where.',
        'scope' => 'Leave empty to work on everything. A faculty includes its departments and programs.',
        'send_invitation' => 'The user receives an email and sets their own password.',
    ],

    'locales' => [
        'en' => 'English',
        'bn' => 'বাংলা',
    ],

    'scope' => [
        'all' => 'Everything',
        'count' => ':count entity|:count entities',
    ],

    'actions' => [
        'effective' => 'Effective permissions',
        'resend' => 'Resend invitation',
        'close' => 'Close',
    ],

    'effective' => [
        'super_admin' => 'Super Admin: can do everything, everywhere.',
        'where' => 'Where',
        'what' => 'Permissions',
        'none' => 'No permissions yet. Give the user a role.',
    ],

    'errors' => [
        'super_admin_role' => 'Only a Super Admin can give the Super Admin role.',
    ],

    'login' => [
        'locked' => 'Too many wrong passwords. Try again in :minutes minutes.',
        'inactive' => 'This account is inactive. Please contact the administrator.',
    ],

    'invitation' => [
        'subject' => 'You are invited to :app',
        'greeting' => 'Hello :name,',
        'line' => 'An account has been created for you on :app. Set your password to sign in.',
        'action' => 'Set my password',
        'expiry' => 'This link expires in :hours hours. Ask the administrator for a new one if it has expired.',
        'sent' => 'Invitation sent to :email',
    ],
];
