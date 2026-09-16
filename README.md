# dyndns-php

`dyndns-php` is a small self-hosted Dynamic DNS endpoint for routers and home internet connections. Your router calls `ddns.php`, and the script updates the DNS record at your DNS provider so your domain always points to your current public IP address.

## Supported DNS providers

- Hetzner DNS
- InternetX
- SchlundTech

## How it works

Safest option: let your router send the username and password with HTTP Basic Auth, and keep only hostname/IP in the URL:

```bash
curl -u alice:secret "https://example.com/ddns.php?hostname=home.example.com&ip=203.0.113.10"
```

For routers that can only send everything in one URL, this compatibility format is also supported:

```text
https://your-domain.com/ddns.php?username=USER&password=PASS&hostname=FQDN&ip=IP
```

Use that full URL format only if your router has no separate username/password fields, because URLs are often logged by routers, proxies, and web servers.

- `username` and `password` must match an account in `config.php`
- `hostname` must be the full host name, for example `home.example.com`
- `ip` is the public IPv4 or IPv6 address that should be written to DNS

The script updates an `A` record for IPv4 and an `AAAA` record for IPv6.

## Installation and setup

1. Copy the project files to a PHP-enabled web server.
2. Copy `config.dist.php` to `config.php`.
3. Edit `config.php` and add your provider credentials, domains, and router login accounts.
4. Point your router's Dynamic DNS settings to your public `ddns.php` URL.
5. Test once from a browser or with `curl`.

## Provider credentials

### Hetzner DNS

1. Sign in at `https://console.hetzner.cloud/`
2. Open the Hetzner Cloud Console settings and create an API token
3. Paste that token into `api_token`

Example:

```php
'provider' => 'hetzner',
'api_token' => 'YOUR_HETZNER_API_TOKEN',
```

### InternetX

InternetX uses API username/password authentication. In this project, `api_token` must contain:

```text
base64_encode('API_USERNAME:API_PASSWORD')
```

Example:

```php
'provider' => 'internetx',
'api_token' => 'BASE64_ENCODED_USERNAME_PASSWORD',
```

### SchlundTech

SchlundTech uses the same API style as InternetX. You still enter the provider as `schlundtech`, and the project chooses the correct API context automatically.

```php
'provider' => 'schlundtech',
'api_token' => 'BASE64_ENCODED_USERNAME_PASSWORD',
```

## Configuring domains and accounts

Each top-level entry inside `domains` is one DNS zone. Inside that zone you define:

- `provider`: `hetzner`, `internetx`, or `schlundtech`
- `api_token`: your provider credential
- `accounts`: router usernames that are allowed to update selected hostnames

Example:

```php
'domains' => array(
    'example.com' => array(
        'provider' => 'internetx',
        'api_token' => 'BASE64_ENCODED_USERNAME_PASSWORD',
        'accounts' => array(
            'router1' => array(
                'password_hash' => '$2y$10$REPLACE_WITH_HASH',
                'hostnames' => array('home', 'office', '@'),
            ),
        ),
    ),
),
```

`hostnames` may contain:

- `@` for the root domain (`example.com`)
- a short host name like `home`
- a full host name like `home.example.com`
- `*` to allow every host inside that domain entry

## Generate the password hash

The password stored in `config.php` must be a PHP `password_hash()` value, not plain text.

Fastest option:

```bash
php -r "echo password_hash('YOUR_ROUTER_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
```

You can also temporarily enable the built-in hash generator in `config.php`:

```php
'hash_generator' => array(
    'enabled' => true,
),
```

Then open `index.php` in a browser on the server itself. Even when enabled, the page only works from localhost. If you only have shell access, use the `php -r` command above instead. After that, copy the hash into `config.php` and disable the generator again.

## Router configuration

### Generic Dynamic DNS setup

Most routers have a “custom Dynamic DNS” or “user-defined provider” option. Use these values:

- Update URL:

```text
https://your-domain.com/ddns.php?hostname=FQDN&ip=IP
```

- Username: `USER`
- Password: `PASS`
- Hostname: full host name, for example `home.example.com`

If your router supports HTTP Basic Auth, you can also send:

```bash
curl -u USER:PASS "https://your-domain.com/ddns.php?hostname=FQDN&ip=IP"
```

If a router sends both query parameters and HTTP Basic Auth, the query parameters win.

If your router cannot send username/password separately, it may use this fallback URL:

```text
https://your-domain.com/ddns.php?username=USER&password=PASS&hostname=FQDN&ip=IP
```

Use that fallback only when necessary because query strings may be logged.

### UniFi Controller example

Create a custom Dynamic DNS profile and use:

- Service: `custom`
- Hostname: `home.example.com`
- Username: `alice`
- Password: your plain router password
- Server: `https://your-domain.com/ddns.php?hostname=%h&ip=%i`
- Leave the password out of the URL so it stays in UniFi's dedicated password field

### Fritz!Box example

In **Internet > Permit Access > Dynamic DNS**, choose **Custom** and use:

- Update URL:

```text
https://your-domain.com/ddns.php?hostname=<domain>&ip=<ipaddr>
```

- Domain name: the full host name, for example `home.example.com`
- Username: the account name from `config.php`
- Password: the plain router password

## Troubleshooting

### I get `401 FAIL`

The username or password is wrong. Check the router credentials and confirm that `password_hash` was generated from the same plain password.

### I get `403 FAIL`

The account is valid, but that account is not allowed to update the requested hostname. Check the `hostnames` list for that user.

### I get `404 FAIL`

The requested hostname does not match any configured domain entry. Check the full FQDN in your router and the domain keys in `config.php`.

### I get `502 FAIL`

The update reached the provider step, but the provider update did not finish successfully. Common reasons are a missing DNS record, provider-side API problems, or an unexpected upstream response. Create the record first if needed, then check your provider status and credentials.

### I left out the `ip` parameter

That only works if you explicitly enable `ip_fallback.allow_remote_addr` and your web server passes the real client IP in `REMOTE_ADDR`. Leave it disabled unless you are sure your proxy/server setup is correct.
