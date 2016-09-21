Description
-----------
This module intercepts all outgoing emails from a Drupal site and
reroutes them to a predefined configurable email address.

This is useful in case where you do not want email sent from a Drupal
site to reach the users. For example, if you copy a live site to a test
site for the purpose of development, and you do not want any email sent
to real users of the original site. Or you want to check the emails sent
for uniform formatting, footers, ...etc.

Installation
------------
Install and enable as you would install any module.

Configuration
-------------

Go to Admin -> Configuration -> Development -> Reroute email, and enable r
erouting and enter an email address to route all email to. If the
"Email Addresses" field is empty, all attempts to send mail will be aborted
and the failure noted in the log along with a full dump of the email
variables, which could provide an additional debugging method.

Tips and Tricks
---------------
This module is *not* intended to be enabled at all on a production site.

It does provide configuration that you can override in the settings.php or
settings.local.php file of a site. This is useful for moving sites from live to
test and vice versa.

To override configuration add these lines to settings.local.php or settings.php
in the dev/test environment:

$config['reroute_email.settings']['reroute_email_enable'] = TRUE;
$config['reroute_email.settings']['reroute_email_address'] = 'your.email@example.com';

And for the production or other site, you set it as follows:

$config['reroute_email.settings']['reroute_email_enable'] = FALSE;

Configuration and all the settings variables can be overridden in the
settings.php file by copying and pasting the code snippet below and changing
the values:

/**
 * Reroute Email module:
 *
 * To override specific variables and ensure that email rerouting is enabled or
 * disabled, change the values below accordingly for your site.
 */
// Enable email rerouting.
$config['reroute_email.settings']['reroute_email_enable'] = TRUE;
// Space, comma, or semicolon-delimited list of email addresses to pass
// through. Every destination email address which is not on this list will be
// rerouted to the first address on the list.
$config['reroute_email.settings']['reroute_email_address'] = 'your.email@example.com';
// Enable inserting a message into the email body when the mail is being
// rerouted.
$conf['reroute_email.settings']['reroute_email_enable_message'] = 1;


Test Email Form
---------------
Reroute Email also provides a form for testing email sending or
rerouting. After enabling the module, a test email form is accessible under:
Admin -> Configuration -> Development -> Reroute email -> Test email form

This form allows sending an email upon submission to the recipients entered in
the fields To, Cc and Bcc, which is very practical for testing if emails are
correctly rerouted to the configured addresses.

Bugs/Features/Patches
---------------------
If you want to report bugs, feature requests, or submit a patch, please do so
at the project page on the Drupal web site.
http://drupal.org/project/reroute_email

Original Author
---------------
Khalid Baheyeldin (http://baheyeldin.com/khalid and http://2bits.com)

Current Maintainers
-------------------
DYdave (http://drupal.org/user/467284)
rfay (http://drupal.org/user/30906)

If you use this module, find it useful, and want to send the author a thank you
note, then use the Feedback/Contact page at the URL above.

The maintainers can also be contacted for paid customizations of this and other
modules.
