<?php

    // Report all errors except E_NOTICE.
    // To reduce log file size.
error_reporting (E_ALL ^ (E_NOTICE | E_DEPRECATED));


// This file defines the function for validating email address as in
// RFC 2822, 2821, etc.

define ("_CHECK_MX_", true);
define ("_NO_CHECK_MX_", false);

function _ValidEmailAddr_ ( $email, $flag=_NO_CHECK_MX_ )
// Return true if $email is valid, else return false.
{

    if ( filter_var($email, FILTER_VALIDATE_EMAIL) )
        return TRUE;
    else
        return FALSE;


/*
    // First, we check that there's one @ symbol,
    // and that the lengths are right.
    if ( ! ereg ("[^@]{1,64}@[^@]{1,255}", $email) ) {
    // Email invalid because wrong number of characters in one section,
    // or wrong number of @ symbols.
        return false;
    }

    // Split it into sections to make life easier
    $email_array = explode ("@", $email);
    $local_array = explode (".", $email_array[0]);
    for ( $i = 0; $i < sizeof ($local_array); $i++ ) {
        if ( ! ereg ("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i]) ) {
            return false;
        }
    }

    if ( ! ereg ("^\[?[0-9\.]+\]?$", $email_array[1]) ) {
        // Check if domain is IP. If not, it should be valid domain name.
        $domain_array = explode(".", $email_array[1]);
        if ( sizeof ($domain_array) < 2 ) {
        // Not enough parts to domain
            return false;
        }

        for ( $i = 0; $i < sizeof ($domain_array); $i++ ) {
            if ( ! ereg ("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i]) ) {
                return false;
            }
        }
    }

    if ( _CHECK_MX_ == $flag )
        // Check if mail exchange exists.
        if ( ! getmxrr ($email_array[1], $mxrecords) )
            return false;

    return true;
*/
}

function _ValidEmailDomain_ ( $email_addr )
// Return true if domain of $email has a MX record, else return false.
// $mail_addr is either a complete email address, or just domain.
{
    $domain = array_pop (explode ("@", $email_addr));

    if ( ! getmxrr ($domain, $mxrecords) )
        return false;

    return true;
}

?>
