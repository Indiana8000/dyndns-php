<?php

return array(
    'log_file' => __DIR__ . '/ddns.log',
    'hash_generator' => array(
        'enabled' => false,
    ),
    'domains' => array(
        'example.com' => array(
            'provider' => 'hetzner',
            'api_token' => 'YOUR_HETZNER_API_TOKEN',
            'accounts' => array(
                'user1' => array(
                    'password_hash' => '$2y$10$REPLACE_WITH_GENERATED_HASH',
                    'hostnames' => array('sub1.example.com', 'sub2', '@'),
                ),
            ),
        ),
        'another.com' => array(
            'provider' => 'autodns',
            'api_token' => 'BASE64_ENCODED_USERNAME_PASSWORD',
            'context' => 10,
            'accounts' => array(
                'user2' => array(
                    'password_hash' => '$2y$10$REPLACE_WITH_GENERATED_HASH',
                    'hostnames' => array('home.another.com', 'office'),
                ),
            ),
        ),
    ),
);
