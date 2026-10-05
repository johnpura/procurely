<?php

return [
    'organization' => 'City of Example',
    'department' => 'Procurement',
    'contact' => [
        'name' => 'Procurement Division',
        'email' => 'procurement@example.gov',
        'phone' => '(555) 555-0100',
        'address' => 'City Hall, Room 000, 1 Main Street, Example, ST 00000',
    ],
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@procurely.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],
    // enforce | report | off. Use "report" to trial a stricter policy without breaking pages.
    'csp' => env('CSP_MODE', 'enforce'),
];
