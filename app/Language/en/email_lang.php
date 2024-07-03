<?php

/**
 * Email Language File
 */
return [
    'email_must_be_array' => 'The email validation method must be passed an array.',
    'email_invalid_address' => 'Invalid email address: {0}',
    'email_attachment_missing' => 'Unable to locate the following email attachment: {0}',
    'email_attachment_unreadable' => 'Unable to open this attachment: {0}',
    'email_no_from' => 'Cannot send mail with no "From" header.',
    'email_no_recipients' => 'You must include recipients: To, Cc, or Bcc',
    'email_send_failure_phpmail' => 'Unable to send email using PHP mail(). Your server might not be configured to send mail using this method.',
    'email_send_failure_sendmail' => 'Unable to send email using PHP Sendmail. Your server might not be configured to send mail using this method.',
    'email_send_failure_smtp' => 'Unable to send email using PHP SMTP. Your server might not be configured to send mail using this method.',
    'email_sent' => 'Your message has been successfully sent using the following protocol: {0}',
    'email_no_socket' => 'Unable to open a socket to Sendmail. Please check settings.',
    'email_no_hostname' => 'You did not specify a SMTP hostname.',
    'email_smtp_error' => 'The following SMTP error was encountered: {0}',
    'email_no_smtp_unpw' => 'Error: You must assign a SMTP username and password.',
    'email_failed_smtp_login' => 'Failed to send AUTH LOGIN command. Error: {0}',
    'email_smtp_auth_un' => 'Failed to authenticate username. Error: {0}',
    'email_smtp_auth_pw' => 'Failed to authenticate password. Error: {0}',
    'email_smtp_data_failure' => 'Unable to send data: {0}',
    'email_exit_status' => 'Exit status code: {0}',
];
