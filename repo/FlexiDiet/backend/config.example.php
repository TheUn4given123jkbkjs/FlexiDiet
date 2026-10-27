<?php
/** Copy to config.local.php; do not commit passwords to Git. */
return [
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'flexidiet',
    'db_user' => 'root',
    'db_pass' => '',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => '587',
    'smtp_security' => 'tls',
    'smtp_user' => 'flexidiet.giamcancungminh@gmail.com',
    'smtp_pass' => 'PASTE_GMAIL_APP_PASSWORD_HERE',
    'smtp_from_email' => 'flexidiet.giamcancungminh@gmail.com',
    'smtp_from_name' => 'FlexiDiet',
];
