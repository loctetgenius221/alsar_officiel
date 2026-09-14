<?php

return [
    // Bootstrap du premier compte admin (AdminUserSeeder) — cf. plan Phase 3, lot 1.
    'name' => env('ADMIN_NAME', 'Super Admin'),
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
];
