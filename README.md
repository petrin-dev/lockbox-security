# Lockbox Security

Modular WordPress security hardening. Enable only what you need.

Each feature is an independent module with its own toggle. No bloat, no telemetry, no upsells — just the security controls site owners actually reach for.

---

## Modules

### Login & Authentication

| Module | What it does |
|---|---|
| **Limit Login Attempts** | Temporarily blocks an IP after a configurable number of failed logins. Lockout duration and attempt threshold are both configurable. |
| **Inactivity Logout** | Automatically logs out idle users after a set number of minutes. Uses a JS heartbeat to track activity accurately. Redirects back to the original page after re-login. |
| **Generic Login Errors** | Replaces WordPress's specific "incorrect username" / "incorrect password" messages with a single vague error, preventing username discovery via the login form. |
| **Strong Passwords by Role** | Removes the "confirm use of weak password" bypass for selected roles. Enforced server-side — not bypassable by disabling JavaScript. |

### WordPress Hardening

| Module | What it does |
|---|---|
| **Disable File Editing** | Removes the Theme/Plugin Editor from the dashboard. Equivalent to `define( 'DISALLOW_FILE_EDIT', true )` in `wp-config.php`, without requiring a file edit. |
| **Hide WordPress Version** | Removes the WP version from meta tags, RSS feeds, asset query strings (`?ver=`), and blocks direct access to `readme.html` and `license.txt`. |
| **Disable XML-RPC** | Completely disables the XML-RPC endpoint, removes the `X-Pingback` header, and strips RSD/wlwmanifest links from the page head. Note: disabling XML-RPC will break Jetpack and the WordPress mobile apps. |
| **Remove Generator Meta Tags** | Strips `<meta name="generator">` tags from page HTML, including those added by themes and plugins. |
| **Block User Enumeration** | Blocks two enumeration vectors: the `?author=N` query string redirect and the unauthenticated `/wp-json/wp/v2/users` REST API endpoint. |

### Admin Protection

| Module | What it does |
|---|---|
| **Admin IP Whitelist** | Restricts access to `/wp-admin` and `wp-login.php` to a list of allowed IP addresses. Supports IPv4 and IPv6. See recovery instructions below. |
| **Security Headers** | Adds `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, and `Referrer-Policy: strict-origin-when-cross-origin` to all responses. CSP is intentionally omitted — configure that at the server or CDN level. |

### Info Panel

A read-only **Security Header Inspector** on the settings page fetches your site's front-page headers and reports which security headers are present or missing, without modifying anything.

Two dismissible notices recommend [Passwordless Login](https://wordpress.org/plugins/passwordless-login/) for magic-link authentication and [WP Security Audit Log](https://wordpress.org/plugins/wp-security-audit-log/) for activity logging — features deliberately outside Lockbox's scope.

---

## Requirements

- **PHP:** 8.2 or higher
- **WordPress:** 6.0 or higher

---

## Installation

1. Download or clone this repository
2. Copy the `lockbox-security` folder into your site's `wp-content/plugins/` directory
3. Activate the plugin from **Plugins → Installed Plugins**
4. Navigate to **Tools → Lockbox Security** to configure

---

## Admin IP Whitelist — Lockout Recovery

If you enable the IP Whitelist and lock yourself out, there is no web-based recovery by design. Use one of these methods:

**WP-CLI** (fastest):
```bash
wp option delete lockbox_security_settings
```

**Database** (phpMyAdmin, Adminer, etc.):
Find the `lockbox_security_settings` row in `wp_options` and delete it.

**FTP/SFTP**:
Delete or rename the `lockbox-security` plugin folder. The plugin will stop loading and access will be restored immediately.

All three options reset all Lockbox settings, not just the whitelist. Re-configure after regaining access.

---

## Multisite

Lockbox Security supports WordPress Multisite with the following limitations:

- Settings are network-wide — there is no per-site configuration
- Super Admins are exempt from all restrictions
- The settings page is accessible from individual site dashboards (not Network Admin)

---

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)
