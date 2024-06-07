<?php


    // Report all errors except E_NOTICE.
    // To reduce log file size.
error_reporting (E_ALL ^ E_NOTICE);

/* UNLV Libraries, web form emailing script.
 * Developer: Hong Zhang
 * $Id: gsendmail.php, v 1.1 $
 */

/* Modifications:
 * 2009-06-26: Hong Zhang
 *      # Added code to extract extra email addresses that will receive
 *        the same email as $mailto. The key is 'extra_email' (same as form
 *        field name, like 'extra_email[]'), as array element.
 *        $extra_emails is appended to Cc: addresses.
 *        Search for 'extra_email' for added codes.
 *        Example: http://www.library.unlv.edu/webforms/hr/furlough_dc_class.html
 *      # If return_address exists, then it receives copy in Cc: address.
 *
 * 2007-02-26: by Hong Zhang
 *      # Added code to extract patron email address and used it in email
 *        "From: " field, so recepient can directly mail back, rather than
 *        copy and paste.
 *
 * 2006-02-06: by Hong Zhang
 *      # mailto address is split first, then validate each of them, if any,
 *        so that email can be sent to multiple addresses.
 *
 * 2005-09-29: by Hong Zhang
 *      # Disabled all security checking, including referer and host name,
 *        due to reports of not able to send email.
 */


include_once ("headfootinc.inc");
include_once ("_ValidEmail_.inc");
include_once ("sysutils.inc");

// Added 6-13-2005.
//define ("UNLV_LIBRARY_HOST", ".library.unlv.edu");

// Clean up environment for taint mode before calling sendmail.
// Set path to our own sendmail path.
// Under safe mode, these may not be required.
$PATH = "/usr/wwwbin";
$_SERVER["PATH"] = "/usr/wwwbin";
$_ENV["PATH"] = "/usr/wwwbin";
unset ($_ENV["SHELL"]);
unset ($_SERVER["SHELL"]);


    // Define blocked referer urls.
$referer_blocked = array (
    "http://www.library.unlv.edu/comments.html",
    "http://library.nevada.edu/comments.html",
    "http://www.library.nevada.edu/php/gsendmail.php",
    "http://library.nevada.edu/php/gsendmail.php",
    "http://library.nevada.edu/find/rush.html",
    "http://library.nevada.edu/arch/rsrce/stern1.html",
    "http://www.library.nevada.edu/arch/rsrce/stern1.html",
    "http://library.nevada.edu/arch/rsrce/stern3.html",
    "http://www.library.nevada.edu/arch/rsrce/stern3.html",
    "http://www.library.unlv.edu/cml/faq/faqjournal.html"
);


// >>> ADDITION: * 2009-06-26: Hong Zhang
// If from these webpages, then send a copy of email to return_address,
// if it exists.
$copyemail_pages = array (
    "http://www.library.unlv.edu/webforms/hr/furlough_dc_class.html",
    "http://go.library.unlv.edu/webforms/hr/furlough_dc_class.html",
    "http://www.library.unlv.edu/webforms/hr/furlough_dc_class.php",
    "http://go.library.unlv.edu/webforms/hr/furlough_dc_class.php",
    "http://www.library.unlv.edu/webforms/hr/furlough_request_fac.html",
    "http://go.library.unlv.edu/webforms/hr/furlough_request_fac.html",
    "http://www.library.unlv.edu/webforms/hr/furlough_request_fac.php",
    "http://go.library.unlv.edu/webforms/hr/furlough_request_fac.php",
);
// <<< ADDITION: * 2009-06-26: Hong Zhang

// Set variables.

// ********************** TEMPORARILY DISABLED *********************
$referer = $_SERVER["HTTP_REFERER"];
//$hostname = $_SERVER["HTTP_HOST"];
// ********************** TEMPORARILY DISABLED *********************

$issorted = false;
$sort = array ();
$confpage = '';
$mailto = '';
$message = array ();

// Create hash to hold all message parameters and data.
// Make sure all fieldnames are lowercased.
// Assume all forms use POST method.
$tmp_mymsgbody = '';            // Storage for every field sent back to me.
foreach ( $_POST as $key => $value ) {
    $key = strtolower ($key);

    // Preserve each field here, since some will be destroyed.
    $tmp_mymsgbody .= "$key = $value ___\n";

    if ( ! is_array ($value) )
        $message["$key"] = trim (stripslashes ($value));
    else
    {
        $tmparr = array ();
        foreach ( $value as $tmpval )
            $tmparr[] = trim (stripslashes ($tmpval));

        $message["$key"] = $tmparr;
    }
}

// >>> ADDITION: * 2009-06-26: Hong Zhang
    // Extract extra email addresses that will receive this email,
    // in addition to mailto addresses.
    // Do not validate extra email addr here, assuming they're valid.
$extra_emails = '';
if ( array_key_exists ('extra_email', $message) )
{
    if ( ! is_array ($message['extra_email']) )
    {
        $tmp = $message['extra_email'];
        $message['extra_email'] = array ($tmp);
    }

    $tmparr = array ();
    foreach ( $message['extra_email'] as $val )
    {
        $val = trim ($val);
        if ( '' != $val )
            $tmparr[] = $val;
    }
    unset ($message['extra_email']);

    if ( count ($tmparr) > 0 )
        $extra_emails = implode (', ', $tmparr);
}
// <<< ADDITION: * 2009-06-26: Hong Zhang

// Assign address of the confirmation page to a separate variable, so that
// it doesn't show up in the body of the message.
$confpage = $message["confirmation"];
unset ($message["confirmation"]);

// Validate mailto address and remove taint.
$arr_mailto = explode (',', $message["mailto"]);
foreach ($arr_mailto as $key => $val)
    $arr_mailto[$key] = trim ($val);

foreach ($arr_mailto as $val)
{
    $mailtoStat = _ValidEmailAddr_ ($val);
    if ( ! $mailtoStat )
        break;
}

$mailto = $message["mailto"];
unset ($message["mailto"]);

// ********************** TEMPORARILY DISABLED *********************
/*
// Check referer against hostname to make sure the data is coming from one
// of our scripts.
$hostpos = strpos ($referer, "http://$hostname/");
if ( empty ($referer) || false === $hostpos || $hostpos > 0 )
    errormsg ("This form $hostpos: $referer $hostname is not authorized to send email using this script.");
*/
// ********************** TEMPORARILY DISABLED *********************

// Added 5-13-2005.
/*
if ( FALSE === stristr ($hostname, UNLV_LIBRARY_HOST) )
    errormsg ("This form: $hostname is not authorized to send email using this script.");
*/

// Check for required message parameters, including confirmation page,
// mailto address, etc.
if ( empty ($confpage) )
    errormsg ("The author of the form did not supply a confirmation page");

if ( false == $mailtoStat )
    errormsg ("The author of the form did not supply a mailto address, or mailto address is invalid: $mailto");


// Check for required Fields. This is another hidden field option, the
// name is required and the value is a comma separated list of fieldnames
// that the user is required to fill out.
//
// example:
// <INPUT TYPE="hidden" NAME="required" VALUE="phone,name,address">
if ( ! empty ($message["required"]) ) {
    $required = explode (',', $message["required"]);
    unset ($message["required"]); 

    foreach ( $required as $name ) {
        if ( empty ($message["$name"]) ) {
            errormsg ("You did not fill out the $name field. Please go back to the form and fill out the $name field.");
        };
    };
}



// >>> ADDITION: * 2009-06-26: Hong Zhang
    // Extract extra email addresses that will receive this email,
    // in addition to mailto addresses.
    // Do not validate extra email addr here, assuming they're valid.
// MOD 2012-12-10, Hong Zhang:
// Now this might become a required field, so run required fields check first,
// then back up this field, and remove it from $message.
$extra_emails = '';
if ( array_key_exists ('extra_email', $message) )
{
    if ( ! is_array ($message['extra_email']) )
    {
        $tmp = $message['extra_email'];
        $message['extra_email'] = array ($tmp);
    }

    $tmparr = array ();
    foreach ( $message['extra_email'] as $val )
    {
        $val = trim ($val);
        if ( '' != $val )
            $tmparr[] = $val;
    }
    unset ($message['extra_email']);

    if ( count ($tmparr) > 0 )
        $extra_emails = implode (', ', $tmparr);
}
// <<< ADDITION: * 2009-06-26: Hong Zhang



// Create list for sorting, if required. This is invoked using a hidden
// field with a value of a comma separated list of all the names of the
// fields  to be sorted, in the order in which they should appear in the
// body of the message.
//
// for example:
// <INPUT NAME="sort" VALUE="email,subject,body,extension" TYPE="hidden">
if ( ! empty ($message["sort"]) ) {
    $issorted = true;
    $sort = explode (',', $message["sort"]);
    unset ($message["sort"]); 
}

// Create hash for verification, if required. Verification is also
// invoked using a hidden field named "validate", with a value of a comma
// separated list of fieldnames and datatypes, separated by the equals
// sign.  This does *not* require that fields be filled out-you do that
// with the "required" option, below.
//
// Datatypes that can be verified include:
// phone 
// verifies that the data is a phone number in the form 345-4567 (it must
// include the dash)
// extension
// verifies that the data is a four-digit number
// email
// verifies that the data is in the form of an email address
// zip
// verifies that the data is either a five digit number or a five digit
// number with a dash and four digit extension
// areacode
// the data must be a three digit number followed by a dash, another three
// digit number, another dash, and a four-digit number. 702-895-2130
//
// example:
// <INPUT TYPE="hidden" NAME="verify" VALUE="ext=extension,num=phone">
if ( ! empty ($message["verify"]) )
    Verify ();

unset ($message["verify"]);

// Set the subject and reply to address, if it doesn't already exist.
if ( empty ($message["subject"]) )
    $message["subject"] = "No Subject";

$subject = $message["subject"];
unset ($message["subject"]);

if ( ! empty ($message["submit"]) )
    unset ($message["submit"]);


// Start constructing the email message.
$msgbody = '';
$bgcode = "#cbcbcb";
$msgbody_html = "<html><body><table width=\"90%\" cellpadding=\"5px\" border=0><tr bgcolor=\"$bgcode\"><td align=left width=\"20%\"><h2>Form field</h2></td><td align=left width=\"80%\"><h2>Filled info</h2></td></tr>";     // Holding html equivalent message.

$color_cnt = 0;  // Signal to switch bgcolor.

// This is the tricky part-I need the program to check which fields need
// to be sorted, sort and print them first, and then print the rest of the
// message. I do this by using the array of sorted names and deleting
// them after they are printed from the hash, then printing the rest of
// the hash. Note that if a fieldname is incorrect in the sorted list, it's
// just ignored.
if ( $issorted )
{
    foreach ( $sort as $name )
    {
        if ( $message["$name"] )
        {
            if ( -1 == $color_cnt )
            {
                $msgbody_html .= "<tr bgcolor=\"$bgcode\">";
                $color_cnt += 1;
            }
            else
            {
                $msgbody_html .= "<tr>";
                $color_cnt -= 1;
            }

            $msgbody_html .= "<td><h4>" . htmlentities ($name, ENT_QUOTES) . "</h4></td>";
            if ( ! is_array ($message["$name"]) )
            {
                $msgbody .= "$name = $message[$name]\n\n";
                $msgbody_html .= "<td>" . str_replace ("\n", "<BR>", htmlentities ($message[$name], ENT_QUOTES)) . "</td>\n";
            }
            else
            {
                $msgbody_html .= "<td>";
                foreach ( $message["$name"] as $val )
                {
                    $msgbody .= "$name = $val\n\n";
                    $msgbody_html .= str_replace ("\n", "<BR>", htmlentities ($val, ENT_QUOTES)) . "<BR>";
                }
                $msgbody_html .= "</td>\n";
            }
            $msgbody_html .= "</tr>\n";

            unset ($message[$name]);
        };
    };
};


$auto_num = $message[NAME_FORM_NUMBER_IMG];
$manual_num = $message[NAME_FORM_NUMBER];
unset ($message[NAME_FORM_NUMBER_IMG]);
unset ($message[NAME_FORM_NUMBER]);


    // Prepare mail message.
foreach ( $message as $key => $value )
{
    if ( -1 == $color_cnt )
    {
        $msgbody_html .= "<tr bgcolor=\"$bgcode\">";
        $color_cnt += 1;
    }
    else
    {
        $msgbody_html .= "<tr>";
        $color_cnt -= 1;
    }

    $msgbody_html .= "<td><h4>" . htmlentities ($key, ENT_QUOTES) . "</h4></td>";
    if ( ! is_array ($value) )
    {
        $msgbody .= "$key = $value\n\n";
        $msgbody_html .= "<td>" . str_replace ("\n", "<BR>", htmlentities ($value, ENT_QUOTES)) . "</td>\n";
    }
    else
    {
        $msgbody_html .= "<td>";
        foreach ( $value as $val )
        {
            $msgbody .= "$key = $val\n\n";
            $msgbody_html .= str_replace ("\n", "<BR>", htmlentities ($val, ENT_QUOTES)) . "<BR>";
        }
        $msgbody_html .= "</td>\n";
    }
    $msgbody_html .= "</tr>\n";
}

# Added 2006-07-31. Send a copy to myself, plus extra info for analysis.
$mymsgbody = $tmp_mymsgbody . "\n\n";
//$mymsgbody .= NAME_FORM_NUMBER_IMG . " = $auto_num \n";
//$mymsgbody .= NAME_FORM_NUMBER . " = $manual_num \n\n";
foreach ( $_SERVER as $key => $val )
{
    $mymsgbody .= "$key = $val \n";
}

    // Block spams.
if ( in_array ($referer, $referer_blocked) ||
     0 == strcasecmp ("LINKS", $subject) ||
     0 == strcasecmp ('hello', $subject) )
{
//    mail ("hong.zhang@unlv.edu", "[SPAM WARNING] $subject", $mymsgbody);
    header ("Location: $confpage");
    exit;
}


    // Check confirmation numbers.
if ( ! empty ($auto_num) && $auto_num != $manual_num )
{
//    mail ("hong.zhang@unlv.edu", "[SPAM WARNING] $subject", $mymsgbody);
    errormsg ("You did not fill out the correct confirmation string. Please go back to the form and correct it.");
}


    // Prepare final message to include both plain text and html version.
$msgbody_html .= "</table></body></html>\n";
$mime_boundary = "==Multipart_Boundary_x".md5(mt_rand())."x";

$headers = "MIME-Version: 1.0\r\n" .
        "Content-Type: multipart/alternative;\n" .
        " boundary=\"{$mime_boundary}\"\r\n";

$finalmsg =
        "--{$mime_boundary}\n" .
        "Content-Type: text/plain; charset=\"iso-8859-1\"\n" .
        "Content-Transfer-Encoding: 7bit\n\n" .
        $msgbody . "\n" .
        "--{$mime_boundary}\n" .
        "Content-Type: text/html; charset=\"iso-8859-1\"\n" .
        "Content-Transfer-Encoding: 7bit\n\n" .
        $msgbody_html .
        "--{$mime_boundary}--\n";



$cc_mails = array ();
// >>> Added 2007-02-26, Hong Zhang
// Add this to let email show the "From: " field, so recepient can directly
// mail back, rather than copy and paste.
if ( $message["email"] != '' &&
     _ValidEmailAddr_ ($message["email"]) )
    $headers = "From: " . $message["email"] . "\r\n" . $headers;
else if ( $message["return_address"] != '' &&
     _ValidEmailAddr_ ($message["return_address"]) )
{
// >>> ADDITION: * 2009-06-26: Hong Zhang
    if ( in_array ($referer, $copyemail_pages) )
        $cc_mails[] = $message["return_address"];
// <<< ADDITION: * 2009-06-26: Hong Zhang

    $headers = "From: " . $message["return_address"] . "\r\n" . $headers;
}
else
    $headers = "From: patron@web.library.unlv.edu" . "\r\n" . $headers;
// <<< Added 2007-02-26, Hong Zhang


// >>> ADDITION: * 2009-06-26: Hong Zhang
    // Append extra email addr & return_address to CC address.
if ( '' != $extra_emails )
    $cc_mails[] = $extra_emails;

if ( count ($cc_mails) > 0 )
{
    $ccmails = implode (', ', $cc_mails);
    $headers = "Cc: " . $ccmails . "\r\n" . $headers;
}
// <<< ADDITION: * 2009-06-26: Hong Zhang



if ( ! mail ($mailto, $subject, $finalmsg, $headers) ) {
    echo "Error sending mail!\n";
    exit (0);
}

    // Send a copy to myself.
//mail ("hong.zhang@unlv.edu", $subject, $mymsgbody);

// Redirect the user to the confirmation page.
header ("Location: $confpage");

exit;


//=========================================================================
// Subroutine for the verification function. Creates a hash of fieldnames
// and datatypes, then compares the fieldnames in the verify hash against
// fieldnames in the message, when they match, it calls a compare
// subroutine with compares the actual data against the kind of data it's
// supposed to be.
function Verify ()
{
    global $message;

    $temp = explode (',', $message["verify"]);
    $tempname = '';
    $tempval = '';
    $verify = array ();
    $value = '';
    $istrue = false;

    foreach ( $temp as $name ) {
        list ($tempname, $tempval) = explode ('=', $name);
        $verify["$tempname"] = $tempval;
    };  

    $pattern = array ( "phone"      => "/^\d{3}-\d{4}$/",
                       "extension"  => "/^\d{4}$/",
                       "zip"        => "/^\d{5}([-]{1}[0-9]{4})?$/",
                       "areacode"   => "/^\d{3}-\d{3}-\d{4}$/"
                     );

    foreach ( $verify as $key => $value ) {
        if ( "phone" == $value && ! empty ($message["$key"]) )
            $istrue = (preg_match($pattern["phone"], $message["$key"])) ? true : false;
        else if ( "extension" == $value && ! empty ($message["$key"]) )
            $istrue = (preg_match($pattern["extension"], $message["$key"])) ? true : false;
        else if ( "zip" == $value && ! empty ($message["$key"]) )
            $istrue = (preg_match($pattern["zip"], $message["$key"])) ? true : false;
        else if ( "areacode" == $value && ! empty ($message["$key"]) )
            $istrue = (preg_match($pattern["areacode"], $message["$key"])) ? true : false;
        else if ( "email" == $value && ! empty ($message["$key"]) )
            $istrue = (_ValidEmailAddr_ ($message["$key"])) ? true : false;
        else
            errormsg ("You specified a form of verification that does not exist");

        if ( ! $istrue )
            errormsg ("You did not fill out the $value field correctly. Please go back to the form and try again.");
    };
}

//=========================================================================
function errormsg ( $msg )
{
echo <<<ENDOFHTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/strict.dtd">

<HTML>

<head>
<!--Begin title and metadata-->
<title>Error sending your message</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta name="description" content="Error sending patron message.">


<!--these two tags are used to allow content providers and page editors to pull 
together lists of their own pages using the verity search engine.  "author" is 
the content provider,  "editor" is the page editor-->
<meta name="author" content="choi, kee">
<meta name="editor" content="choi, kee">
<!--End title and metadata-->


<!--the external javascript file contains javascriopts required across the site.  Do not alter or remove-->
<script type="text/javascript" language="JavaScript" src="/main.js">

</script>

<!--style sheets.  We use the @import directive 
because some parts of the site, notably the branch pages, will need to load more than one style sheet-->

<link rel="stylesheet" href="/main.css" type="text/css" title="resources">

</head>
<body lang="en" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<!--Begin include for top menu bar-->
ENDOFHTML;

/*
<!--#include virtual="/topbar.html" -->
*/
_IncludeHeadFoot_ (_SERVERINC_HEADER_);

/*
<!--#include virtual="/menu.html"-->
*/
_IncludeHeadFoot_ (_SERVERINC_SIDEMENU_);


echo <<<ENDOFHTML
          <div ID="contentarea">
        <div ID="breadcrumbs">
        <a href="/">UNLV Libraries Main Page</a>
        </div>
        
        <!--Begin page content-->
    <h3><span class="font-red">Your message was not sent</span></h3>

Your message was not sent because:<P>
$msg
</P>
        <!--end page content-->
        
        <!--begin page footer-->
        
        <div ID="footer"> 
        
        <!--Bottom menu choices-these may be removed if you like-->
      <P></P>
ENDOFHTML;


/*
<!--#include virtual="/botmenu.html"-->
*/
_IncludeHeadFoot_ (_SERVERINC_BOTMENU_);


    echo "      <p>Updated:\n";
 

/*
          <!--#echo var="LAST_MODIFIED"-->
*/
$thisfilename = $_SERVER["SCRIPT_FILENAME"];
if (file_exists($thisfilename))
{
    echo date("D, M-d-Y H:i:s", filectime($thisfilename));
}

echo <<<ENDOFHTML
          <br>
                  <!--At least one of these needs to be an email link-->
                  
          Page Editor: <A href="/about/staff/libstafinfo.php?style=other&personid=139">Choi, Kee</a>

          <!--Page editor Name-->
          <br>
ENDOFHTML;


/*
          <!--#include virtual="/mainfoot.html" -->
*/
_IncludeHeadFoot_ (_SERVERINC_FOOTER_);


echo <<<ENDOFHTML
        </p>
      </div>
</div>
        
<!--end page footer-->        

</body>
</html>
ENDOFHTML;

    exit;
}

?>
