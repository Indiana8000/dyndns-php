<?php

return array(
    'log_file' => __DIR__ . '/ddns.log',
    'hash_generator' => array(
        'enabled' => false,
    ),
    'ip_fallback' => array(
        'allow_remote_addr' => false,
    ),
    'domains' => array(
        'example.com' => array(
            'provider' => 'schlundtech',
            'api_token' => 'BASE64_ENCODED_USERNAME_PASSWORD',
            'accounts' => array(
                'user1' => array(
                    'password_hash' => '$2y$10$REPLACE_WITH_GENERATED_HASH',
                    'hostnames' => array('sub1', 'sub2', '@'),
                ),
            ),
        ),
        'another.com' => array(
            'provider' => 'internetx',
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
