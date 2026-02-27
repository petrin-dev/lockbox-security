*Must build yourself: can be turned on/off/configured*

- Disable file editing (add constant via filter)
- Hide WordPress version from frontend
- Disable XML-RPC (or gate it)
- Remove WordPress generator meta tags
- Disable user enumeration (block ?author= queries)
- Set security headers via PHP (X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
- Force logout after X minutes of inactivity
- Enforce strong passwords for certain user roles
- Change default login error messages (don't reveal if username exists)
- Limit login attempts with temporary IP blocks
- Admin area IP whitelist (optional, configurable)

- **2FA/MFA** - Use Wordfence Login Security (free, focused 2FA plugin) or WP 2FA. Building this properly is complex and security-critical.
    - Reverse engineer this — https://wordpress.org/plugins/magic-login-mail/

- **Activity logging** - Use WP Activity Log or Simple History. Building comprehensive activity logging is a rabbit hole - you need to hook into everything WordPress does.
    - Reverse engineer one of the free versions of these