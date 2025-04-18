<?php
/**
 * Test fixture for user data
 * Returns an array of test users that can be used in tests
 */

return [
    [
        'id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'techkid',
        'status' => 'active',
        'created_at' => '2023-01-01 00:00:00'
    ],
    [
        'id' => 2,
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin',
        'status' => 'active',
        'created_at' => '2023-01-01 00:00:00'
    ],
    [
        'id' => 3,
        'name' => 'Inactive User',
        'email' => 'inactive@example.com',
        'password' => password_hash('inactive123', PASSWORD_DEFAULT),
        'role' => 'techkid',
        'status' => 'inactive',
        'created_at' => '2023-01-01 00:00:00'
    ],
    [
        'id' => 4,
        'name' => 'Tech Guru',
        'email' => 'guru@example.com',
        'password' => password_hash('guru123', PASSWORD_DEFAULT),
        'role' => 'techguru',
        'status' => 'active',
        'created_at' => '2023-01-01 00:00:00'
    ]
];