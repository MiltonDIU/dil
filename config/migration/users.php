<?php

use App\Models\User;

return [
    'table' => 'users',
    'source' => 'a0cbr_users',
    'key' => 'id',
    'model' => User::class,

    // legacy column => target attribute
    'map' => [
        'name' => 'name',
        'username' => 'username',
        'email' => 'email',
        'password' => 'password',
        'block' => 'blocked',
        'lastvisitDate' => 'last_login_at',
    ],

    'casts' => [
        'blocked' => 'bool',
        'last_login_at' => 'datetime',
        // password is intentionally NOT cast: both legacy hashes are bcrypt
        // ($2y$, 60 chars) and Laravel's `hashed` cast passes them through.
    ],

    'defaults' => [
        'is_admin' => true,   // both legacy users are Super Users / Administrator
    ],
];
