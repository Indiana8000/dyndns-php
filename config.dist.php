<?php

return array(
    'log_file' => __DIR__ . '/ddns.log',
    'hash_generator' => array(
        // Enable temporarily if you want to open index.php from the server itself
        // and generate password_hash() values in your browser.
        'enabled' => false,
    ),
    'ip_fallback' => array(
        // Keep this disabled unless your web server passes the real client IP
        // directly as REMOTE_ADDR. Wrong proxy settings can update the wrong IP.
        'allow_remote_addr' => false,
    ),
    'domains' => array(
        'example.com' => array(
            'provider' => 'schlundtech',
            // SchlundTech uses the InternetX API with the correct context selected automatically.
            // Enter base64_encode('API_USERNAME:API_PASSWORD').
            'api_token' => 'BASE64_ENCODED_USERNAME_PASSWORD',
            'accounts' => array(
                'user1' => array(
                    // Generate with password_hash('your-password', PASSWORD_DEFAULT).
                    'password_hash' => '$2y$10$REPLACE_WITH_GENERATED_HASH',
                    // Use '@' for the root record or subdomain names without example.com.
                    'hostnames' => array('sub1', 'sub2', '@'),
                ),
            ),
        ),
        'another.com' => array(
            'provider' => 'internetx',
            // Enter base64_encode('API_USERNAME:API_PASSWORD').
            'api_token' => 'BASE64_ENCODED_USERNAME_PASSWORD',
            'accounts' => array(
                'user2' => array(
                    'password_hash' => '$2y$10$REPLACE_WITH_GENERATED_HASH',
                    'hostnames' => array('home', 'office'),
                ),
            ),
        ),
        'hetzner.com' => array(
            'provider' => 'hetzner',
            // Create an API token in the Hetzner DNS console and paste it here.
            'api_token' => 'YOUR_HETZNER_API_TOKEN',
            'accounts' => array(
                'user3' => array(
                    'password_hash' => '$2y$10$REPLACE_WITH_GENERATED_HASH',
                    'hostnames' => array('vpn', '@'),
                ),
            ),
        ),
    ),
);
