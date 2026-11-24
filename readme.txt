=== First Visit Contact Popup ===
Contributors: dineshchennu123
Tags: popup, contact form, lead capture, email, leads
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show a contact popup to first-time visitors. Collect name, email and message, save every lead in your dashboard, and get an email for each one.

== Description ==

First Visit Contact Popup greets new visitors with a simple contact form. It appears once per visitor, so returning visitors are not annoyed.

**Features**

* Popup shown once per visitor (you choose the delay and how many days before it can appear again)
* Fields: name, email and an optional message
* Every lead is saved in your WordPress dashboard under "Popup Leads"
* An email notification is sent to the address you choose
* Export your leads to a CSV file
* Change the heading, text, button label, thank-you message and accent colour
* Spam protection: hidden honeypot field, minimum fill time and a per-visitor rate limit
* Lightweight: no external libraries, no third-party services, no tracking

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it from the Plugins screen.
2. Activate the plugin.
3. Go to Popup Leads > Settings to set your text and the email that should receive leads.
4. Click "Preview popup on your site" to see it.

== Frequently Asked Questions ==

= Why does the popup not show for me again after I close it? =

It is shown once per visitor. Use the "Preview popup on your site" button in the settings, or clear the `fvcp_seen` cookie.

= I do not receive the notification emails. =

Many hosts block the default WordPress mail function. Install an SMTP plugin so your site can send email reliably. Leads are always saved in the dashboard even if email fails.

= Where are the leads stored? =

In a table in your own WordPress database. Nothing is sent to external services.

== Privacy ==

This plugin stores the name, email address, optional message and the page URL submitted through the popup in your site's database. It does not store IP addresses. A hashed visitor IP is kept in a temporary WordPress transient for up to one hour to limit spam. The plugin sets one first-party cookie, `fvcp_seen`, to remember that the popup was already shown. No data is sent to third parties. If you collect leads from visitors in regions covered by privacy laws, mention this in your privacy policy.

== Changelog ==

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 1.0.0 =
First release.
