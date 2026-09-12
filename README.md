# dyndns-php
A simple PHP-based self-hosted DynDNS solution that updates DNS records via hosting provider APIs

## Files

- `index.php` renders a bcrypt password hash generator.
- `ddns.php` is the shared DynDNS endpoint.
- `config.dist.php` contains the unified multi-provider example config.

`index.php` is restricted to localhost/CLI access and is disabled by default via `hash_generator.enabled` so the hash generator is not exposed publicly.

## DynDNS request

Example request:

```bash
curl -u alice:secret "https://example.com/ddns.php?hostname=home.example.com&ip=203.0.113.10"
```

`hostname` must be the full FQDN. The matching domain entry in `config.php` or `config.dist.php` decides which provider implementation is used.

Required input for an update request:

- `hostname` as request parameter (FQDN, for example `sub.example.com`)
- `ip` as request parameter (IPv4 or IPv6, optional only when `ip_fallback.allow_remote_addr` is enabled because the endpoint then substitutes `REMOTE_ADDR` before validation)
- `username` and `password` either as request parameters or via HTTP Basic Auth

If both request parameters and HTTP Basic Auth are sent, the request parameters take precedence.
If `ip` is omitted, PHP must receive the real client IPv4 address in `REMOTE_ADDR` from trusted server/proxy configuration. Do not enable this fallback behind reverse proxies or load balancers unless they rewrite `REMOTE_ADDR` safely.
The endpoint updates `A` or `AAAA` records based on the IP version that is provided.

## Configuration

Copy `config.dist.php` to `config.php` and fill in your provider credentials.
Configured accounts are expected to use `password_hash` values.

Supported providers:

- `hetzner`
- `autodns` / `internetx`
