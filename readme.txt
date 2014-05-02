=== WP True Typed ===
Contributors: dbmartin
Tags: spam, anti-spam, antispam, comments, comment, secure, comment-spam, 
Requires at least: 2.7.1
Tested up to: 3.8
Stable tag: 1.5.1
License: GPLv2

WP True Typed is an anti-spam plugin to protect your site from comment spam.

== Description ==

WP True Typed dynamically creates an anti-spam challenge question based on the post or page the
comment form is on, and prevents the comment from being processed if answered incorrectly.  Works as
a first line of defense in battling comment spam.

Features:

* Checks for registered users or pre-approved comment authors
* No cookies needed
* No javascript needed
* No images needed
* Dynamic, changes based on post or page
* Section 508 and WAI accessible
* Valid HTML
* Works with any comment form
* Included CSS style sheet for customization

== Installation ==

1. Upload the `wp-true-typed` folder to the your plugins directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Does it work with any comment form? =

Yes, if that form has the comment action hook: `do_action('comment_form', $post->ID);`

= Does it work with other anti-spam plugins? =

It will work with Akismet, but it is intended to replace any captcha plugins or other first-line
anti-spam plugins.

= Do you provide support for the plugin? =

Yes!  Just drop a line on the plugin site in the comments section.

== Changelog ==
= 1.5.1 =
* fixed error where there is no `$post` object on 404 pages

= 1.5 =
* encapsulate code within `wpTrueTyped` Class
* properly register/enqueue stylesheet
* fixed validation form field `label`

== Update Notice ==
* fixed error where there is no `$post` object on 404 pages



