# First Visit Contact Popup

A lightweight WordPress plugin that shows a contact popup to first-time visitors, saves every lead in your dashboard and emails you each one.

- Popup shows once per visitor (you set the delay and how many days before it can appear again)
- Fields: name, email, optional message
- Leads saved under **Popup Leads** in wp-admin, with CSV export
- Email notification for every lead
- Customisable heading, text, button label, thank-you message and accent colour
- Spam protection: honeypot field, minimum fill time, per-IP rate limit
- No external libraries, no third-party services, no tracking

## Requirements

- WordPress 5.8+
- PHP 7.4+ (tested on WordPress 7.1 / PHP 8.5)

## Installation

1. Download this repository as a ZIP, or clone it into `wp-content/plugins/first-visit-contact-popup`.
2. Activate **First Visit Contact Popup** in Plugins.
3. Go to **Popup Leads > Settings**, set your text and notification email.
4. Click **Preview popup on your site** to test it.

If you download the ZIP from GitHub, the folder is named `first-visit-contact-popup-main`. Rename it to `first-visit-contact-popup` before uploading.

## Privacy

Stores name, email, message and page URL in your site's database. IP addresses are not stored (a hashed IP is kept in a transient for up to one hour for rate limiting). Sets one first-party cookie, `fvcp_seen`. No data leaves your site.

## Development

```
first-visit-contact-popup.php    plugin bootstrap, activation, options
includes/class-fvcp-frontend.php popup markup, assets, AJAX submit handler
includes/class-fvcp-admin.php    settings page, leads list, CSV export
assets/popup.js, popup.css       front-end behaviour and styles
uninstall.php                    removes table and options on uninstall
```

Before releasing, run the [Plugin Check](https://wordpress.org/plugins/plugin-check/) plugin and keep `readme.txt` (WordPress.org format) in sync with the plugin header version.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
