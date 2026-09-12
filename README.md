# dyndns-php
A simple PHP-based self-hosted DynDNS solution that updates DNS records via hosting provider APIs

## Files

- `index.php` renders a bcrypt password hash generator.
- `ddns.php` is the shared DynDNS endpoint.
- `config.dist.php` contains the unified multi-provider example config.

## DynDNS request

Example request:

```text
/ddns.php?username=alice
&password=secret
&hostname=home.example.com
&ip=203.0.113.10
```

`hostname` must be the full FQDN. The matching domain entry in `config.php` or `config.dist.php` decides which provider implementation is used.

Required input for an update request:

- `hostname` as request parameter (FQDN, for example `sub.example.com`)
- `ip` as request parameter (IPv4 address)
- `username` and `password` either as request parameters or via HTTP Basic Auth

## Configuration

Copy `config.dist.php` to `config.php` and fill in your provider credentials.

Supported providers:

- `hetzner`
- `autodns` / `internetx`
