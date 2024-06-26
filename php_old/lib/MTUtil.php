<?php
# Copyright 2001-2006 Six Apart. This code cannot be redistributed without
# permission from www.sixapart.com.  For more information, consult your
# Movable Type license.
#
# $Id: MTUtil.php 654 2006-08-04 21:24:35Z bchoate $

function start_end_ts($ts) {
    if ($ts) {
        if (strlen($ts) == 4) {
            $ts_start = $ts . '0101';
            $ts_end = $ts . '1231';
        } elseif (strlen($ts) == 6) {
            $ts_start = $ts . '01';
            $ts_end = $ts . sprintf("%02d", days_in(substr($ts, 4, 2), substr($ts, 0, 4)));
        } else {
            $ts_start = $ts;
            $ts_end = $ts;
        }
    }
    return array($ts_start . '000000', $ts_end . '235959');
}

function start_end_month($ts) {
    $y = substr($ts, 0, 4);
    $mo = substr($ts, 4, 2);
    $start = sprintf("%04d%02d01000000", $y, $mo);
    $end = sprintf("%04d%02d%02d235959", $y, $mo, days_in($mo, $y));
    return array($start, $end);
}

function days_in($m, $y) {
    return date('t', mktime(0, 0, 0, $m, 1, $y));
}

function start_end_day($ts) {
    $day = substr($ts, 0, 8);
    return array($day . "000000", $day . "235959");
}

function start_end_year($ts) {
    $year = substr($ts, 0, 4);
    return array($year . "0101000000", $year . "1231235959");
}

function start_end_week($ts) {
    $y = substr($ts, 0, 4);
    $mo = substr($ts, 4, 2);
    $d = substr($ts, 6, 2);
    $h = substr($ts, 8, 2);
    $s = substr($ts, 10, 2);
    $wday = wday_from_ts($y, $mo, $d);
    list($sd, $sm, $sy) = array($d - $wday, $mo, $y);
    if ($sd < 1) {
        $sm--;
        if ($sm < 1) {
            $sm = 12; $sy--;
        }
        $sd += days_in($sm, $sy);
    }
    $start = sprintf("%04d%02d%02d%s", $sy, $sm, $sd, "000000");
    list($ed, $em, $ey) = array($d + 6 - $wday, $mo, $y);
    if ($ed > days_in($em, $ey)) {
        $ed -= days_in($em, $ey);
        $em++;
        if ($em > 12) {
            $em = 1; $ey++;
        }
    }
    $end = sprintf("%04d%02d%02d%s", $ey, $em, $ed, "235959");
    return array($start, $end);
}

global $In_Year;
$In_Year = array(
    array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334, 365 ),
    array( 0, 31, 60, 91, 121, 152, 182, 213, 244, 274, 305, 335, 366 ),
);
function week2ymd($y, $week) {
    $jan_one_dow_m1 = (ymd2rd($y, 1, 1) + 6) % 7;

    if ($jan_one_dow_m1 < 4) $week--;
    $day_of_year = $week * 7 - $jan_one_dow_m1;
    $leap_year = is_leap_year($y);
    if ($day_of_year < 1) {
        $y--;
        $day_of_year = ($leap_year ? 366 : 365) + $day_of_year;
    }
    if ($leap_year) {
        $ref = array(0, 31, 60, 91, 121, 152, 182, 213, 244, 274, 305, 335);
    } else {
        $ref = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
    }
    $m = 0;
    for ($i = count($ref); $i > 0; $i--) {
        if ($day_of_year > $ref[$i-1]) {
            $m = $i;
            break;
        }
    }
    return array($y, $m, $day_of_year - $ref[$m-1]);
}

function is_leap_year($y) {
    return (!($y % 4) && ($y % 100)) || !($y % 400) ? true : false;
}

function ymd2rd($y,$m,$d) {
    # make month in range 3..14 (treat Jan & Feb as months 13..14 of
    # prev year)
    if ( $m <= 2 ) {
        $adj = (int)(( 14 - $m ) / 12);
        $y -= $adj;
        $m += 12 * $adj;
    }
    elseif ( $m > 14 )
    {
        $adj = (int)(( $m - 3 ) / 12);
        $y += $adj;
        $m -= 12 * $adj;
    }

    # make year positive (oh, for a use integer 'sane_div'!)
    if ( $y < 0 )
    {
        $adj = (int)(( 399 - $y ) / 400);
        $d -= 146097 * $adj; 
        $y += 400 * $adj;
    }

    # add: day of month, days of previous 0-11 month period that began
    # w/March, days of previous 0-399 year period that began w/March
    # of a 400-multiple year), days of any 400-year periods before
    # that, and 306 days to adjust from Mar 1, year 0-relative to Jan
    # 1, year 1-relative (whew)
    $d += (int)(( $m * 367 - 1094 ) / 12) + (int)((($y % 100) * 1461) / 4) +
          ( (int)($y / 100) * 36524 + (int)($y / 400) ) - 306;
    return $d;
}

function wday_from_ts($y, $m, $d) {
    global $In_Year;
    $leap = $y % 4 == 0 && ($y % 100 != 0 || $y % 400 == 0) ? 1 : 0;
    $y--;

    ## Copied from Date::Calc.
    $days = $y * 365;
    $days += $y >>= 2;
    $days -= intval($y /= 25);
    $days += $y >> 2;
    $days += $In_Year[$leap][$m-1] + $d;
    return $days % 7;
}

function yday_from_ts($y, $m, $d) {
    global $In_Year;
    $leap = $y % 4 == 0 && ($y % 100 != 0 || $y % 400 == 0) ? 1 : 0;
    return $In_Year[$leap][$m-1] + $d;
}

function substr_wref($str, $start, $length) {
    if (preg_match_all('/(&[^;]*;|.)/', $str, $character_entities)) {
        return implode('', array_slice($character_entities[0], $start, $length));
    } else {
        return '';
    }
}

function format_ts($format, $ts, $blog, $lang = null) {
    global $Languages;
    if (!isset($lang) || empty($lang)) { 
        global $mt;
        $lang = ($blog && $blog['blog_language'] ? $blog['blog_language'] : 
                     $mt->config['DefaultLanguage']);
    }
    if ($lang == 'jp') {
        $lang = 'ja';
    }
    $lang = strtolower(substr($lang, 0, 2));
    if (!isset($format) || empty($format)) {
        if (count($Languages[$lang]) >= 4)
            $format = $Languages[$lang][3];
        $format or $format = "%B %e, %Y %l:%M %p";
    }
    global $_format_ts_cache;
    if (!isset($_format_ts_cache)) {
        $_format_ts_cache = array();
    }   
    if (isset($_format_ts_cache[$ts.$lang])) {
        $f = $_format_ts_cache[$ts.$lang];
    } else {
        $L = $Languages[$lang];
        $tsa = array(substr($ts, 0, 4), substr($ts, 4, 2), substr($ts, 6, 2),
                     substr($ts, 8, 2), substr($ts, 10, 2), substr($ts, 12, 2));
        list($f['Y'], $f['m'], $f['d'], $f['H'], $f['M'], $f['S']) = $tsa;
        $f['w'] = wday_from_ts($tsa[0],$tsa[1],$tsa[2]);
        $f['j'] = yday_from_ts($tsa[0],$tsa[1],$tsa[2]);
        $f['y'] = substr($f['Y'], 2);
        $f['b'] = substr_wref($L[1][$f['m']-1], 0, 3);
        $f['B'] = $L[1][$f['m']-1];
        if ($lang == 'ja') {
            $f['a'] = substr($L[0][$f['w']], 0, 8);
        } else {
            $f['a'] = substr_wref($L[0][$f['w']], 0, 3);
        }
        $f['A'] = $L[0][$f['w']];
        $f['e'] = $f['d'];
        $f['e'] = preg_replace('!^0!', ' ', $f['e']);
        $f['I'] = $f['H'];
        if ($f['I'] > 12) {
            $f['I'] -= 12;
            $f['p'] = $L[2][1];
        } elseif ($f['I'] == 0) {
            $f['I'] = 12;
            $f['p'] = $L[2][0];
        } elseif ($f['I'] == 12) {
            $f['p'] = $L[2][1];
        } else {
            $f['p'] = $L[2][0];
        }
        $f['I'] = sprintf("%02d", $f['I']);
        $f['k'] = $f['H'];
        $f['k'] = preg_replace('!^0!', ' ', $f['k']);
        $f['l'] = $f['I'];
        $f['l'] = preg_replace('!^0!', ' ', $f['l']);
        $f['j'] = sprintf("%03d", $f['j']);
        $f['Z'] = '';
        $_format_ts_cache[$ts . $lang] = $f;
    }
    $date_format = null;
    if (count($Languages[$lang]) >= 5)
        $date_format = $Languages[$lang][4];
    $date_format or $date_format = "%B %e, %Y";
    $time_format = null;
    if (count($Languages[$lang]) >= 6)
        $time_format = $Languages[$lang][5];
    $time_format or $time_format = "%l:%M %p";
    $format = preg_replace('!%x!', $date_format, $format);
    $format = preg_replace('!%X!', $time_format, $format);
    ## This is a dreadful hack. I can't think of a good format specifier
    ## for "%B %Y" (which is used for monthly archives, for example) so
    ## I'll just hardcode this, for Japanese dates.
    if ($lang == 'ja') {
        if (count($Languages[$lang]) >= 7)
            $format = preg_replace('!%B %Y!', $Languages[$lang][6], $format);
    }
    if (isset($format)) {
        $format = preg_replace('!%(\w)!e', '\$f[\'\1\']', $format);
    }
    return $format;
}

function dirify($s, $sep = '_') {
    global $mt;
    $charset = $mt->config['PublishCharset'];
    if (preg_match('/utf-?8/i', $charset)) {
        return utf8_dirify($s, $sep);
    } else {
        return iso_dirify($s, $sep);
    }
}

function utf8_dirify($s, $sep = '_') {
    if ($sep == '1') $sep = '_';
    $s = xliterate_utf8($s);  ## convert high-ASCII chars to 7bit.
    $s = strtolower($s);                   ## lower-case.
    $s = strip_tags($s);          ## remove HTML tags.
    $s = preg_replace('!&[^;\s]+;!', '', $s); ## remove HTML entities.
    $s = preg_replace('![^\w\s]!', '', $s);   ## remove non-word/space chars.
    $s = preg_replace('/\s+/',$sep,$s);         ## change space chars to underscores.
    return($s);
}

global $Utf8_ASCII;
$Utf8_ASCII = array(
    "\xc3\x80" => 'A',    # A`
    "\xc3\xa0" => 'a',    # a`
    "\xc3\x81" => 'A',    # A'
    "\xc3\xa1" => 'a',    # a'
    "\xc3\x82" => 'A',    # A^
    "\xc3\xa2" => 'a',    # a^
    "\xc4\x82" => 'A',    # latin capital letter a with breve
    "\xc4\x83" => 'a',    # latin small letter a with breve
    "\xc3\x86" => 'AE',   # latin capital letter AE
    "\xc3\xa6" => 'ae',   # latin small letter ae
    "\xc3\x85" => 'A',    # latin capital letter a with ring above
    "\xc3\xa5" => 'a',    # latin small letter a with ring above
    "\xc4\x80" => 'A',    # latin capital letter a with macron
    "\xc4\x81" => 'a',    # latin small letter a with macron
    "\xc4\x84" => 'A',    # latin capital letter a with ogonek
    "\xc4\x85" => 'a',    # latin small letter a with ogonek
    "\xc3\x84" => 'A',    # A:
    "\xc3\xa4" => 'a',    # a:
    "\xc3\x83" => 'A',    # A~
    "\xc3\xa3" => 'a',    # a~
    "\xc3\x88" => 'E',    # E`
    "\xc3\xa8" => 'e',    # e`
    "\xc3\x89" => 'E',    # E'
    "\xc3\xa9" => 'e',    # e'
    "\xc3\x8a" => 'E',    # E^
    "\xc3\xaa" => 'e',    # e^
    "\xc3\x8b" => 'E',    # E:
    "\xc3\xab" => 'e',    # e:
    "\xc4\x92" => 'E',    # latin capital letter e with macron
    "\xc4\x93" => 'e',    # latin small letter e with macron
    "\xc4\x98" => 'E',    # latin capital letter e with ogonek
    "\xc4\x99" => 'e',    # latin small letter e with ogonek
    "\xc4\x9a" => 'E',    # latin capital letter e with caron
    "\xc4\x9b" => 'e',    # latin small letter e with caron
    "\xc4\x94" => 'E',    # latin capital letter e with breve
    "\xc4\x95" => 'e',    # latin small letter e with breve
    "\xc4\x96" => 'E',    # latin capital letter e with dot above
    "\xc4\x97" => 'e',    # latin small letter e with dot above
    "\xc3\x8c" => 'I',    # I`
    "\xc3\xac" => 'i',    # i`
    "\xc3\x8d" => 'I',    # I'
    "\xc3\xad" => 'i',    # i'
    "\xc3\x8e" => 'I',    # I^
    "\xc3\xae" => 'i',    # i^
    "\xc3\x8f" => 'I',    # I:
    "\xc3\xaf" => 'i',    # i:
    "\xc4\xaa" => 'I',    # latin capital letter i with macron
    "\xc4\xab" => 'i',    # latin small letter i with macron
    "\xc4\xa8" => 'I',    # latin capital letter i with tilde
    "\xc4\xa9" => 'i',    # latin small letter i with tilde
    "\xc4\xac" => 'I',    # latin capital letter i with breve
    "\xc4\xad" => 'i',    # latin small letter i with breve
    "\xc4\xae" => 'I',    # latin capital letter i with ogonek
    "\xc4\xaf" => 'i',    # latin small letter i with ogonek
    "\xc4\xb0" => 'I',    # latin capital letter with dot above
    "\xc4\xb1" => 'i',    # latin small letter dotless i
    "\xc4\xb2" => 'IJ',   # latin capital ligature ij
    "\xc4\xb3" => 'ij',   # latin small ligature ij
    "\xc4\xb4" => 'J',    # latin capital letter j with circumflex
    "\xc4\xb5" => 'j',    # latin small letter j with circumflex
    "\xc4\xb6" => 'K',    # latin capital letter k with cedilla
    "\xc4\xb7" => 'k',    # latin small letter k with cedilla
    "\xc4\xb8" => 'k',    # latin small letter kra
    "\xc5\x81" => 'L',    # latin capital letter l with stroke
    "\xc5\x82" => 'l',    # latin small letter l with stroke
    "\xc4\xbd" => 'L',    # latin capital letter l with caron
    "\xc4\xbe" => 'l',    # latin small letter l with caron
    "\xc4\xb9" => 'L',    # latin capital letter l with acute
    "\xc4\xba" => 'l',    # latin small letter l with acute
    "\xc4\xbb" => 'L',    # latin capital letter l with cedilla
    "\xc4\xbc" => 'l',    # latin small letter l with cedilla
    "\xc4\xbf" => 'l',    # latin capital letter l with middle dot
    "\xc5\x80" => 'l',    # latin small letter l with middle dot
    "\xc3\x92" => 'O',    # O`
    "\xc3\xb2" => 'o',    # o`
    "\xc3\x93" => 'O',    # O'
    "\xc3\xb3" => 'o',    # o'
    "\xc3\x94" => 'O',    # O^
    "\xc3\xb4" => 'o',    # o^
    "\xc3\x96" => 'O',    # O:
    "\xc3\xb6" => 'o',    # o:
    "\xc3\x95" => 'O',    # O~
    "\xc3\xb5" => 'o',    # o~
    "\xc3\x98" => 'O',    # O/
    "\xc3\xb8" => 'o',    # o/
    "\xc5\x8c" => 'O',    # latin capital letter o with macron
    "\xc5\x8d" => 'o',    # latin small letter o with macron
    "\xc5\x90" => 'O',    # latin capital letter o with double acute
    "\xc5\x91" => 'o',    # latin small letter o with double acute
    "\xc5\x8e" => 'O',    # latin capital letter o with breve
    "\xc5\x8f" => 'o',    # latin small letter o with breve
    "\xc5\x92" => 'OE',   # latin capital ligature oe
    "\xc5\x93" => 'oe',   # latin small ligature oe
    "\xc5\x94" => 'R',    # latin capital letter r with acute
    "\xc5\x95" => 'r',    # latin small letter r with acute
    "\xc5\x98" => 'R',    # latin capital letter r with caron
    "\xc5\x99" => 'r',    # latin small letter r with caron
    "\xc5\x96" => 'R',    # latin capital letter r with cedilla
    "\xc5\x97" => 'r',    # latin small letter r with cedilla
    "\xc3\x99" => 'U',    # U`
    "\xc3\xb9" => 'u',    # u`
    "\xc3\x9a" => 'U',    # U'
    "\xc3\xba" => 'u',    # u'
    "\xc3\x9b" => 'U',    # U^
    "\xc3\xbb" => 'u',    # u^
    "\xc3\x9c" => 'U',    # U:
    "\xc3\xbc" => 'u',    # u:
    "\xc5\xaa" => 'U',    # latin capital letter u with macron
    "\xc5\xab" => 'u',    # latin small letter u with macron
    "\xc5\xae" => 'U',    # latin capital letter u with ring above
    "\xc5\xaf" => 'u',    # latin small letter u with ring above
    "\xc5\xb0" => 'U',    # latin capital letter u with double acute
    "\xc5\xb1" => 'u',    # latin small letter u with double acute
    "\xc5\xac" => 'U',    # latin capital letter u with breve
    "\xc5\xad" => 'u',    # latin small letter u with breve
    "\xc5\xa8" => 'U',    # latin capital letter u with tilde
    "\xc5\xa9" => 'u',    # latin small letter u with tilde
    "\xc5\xb2" => 'U',    # latin capital letter u with ogonek
    "\xc5\xb3" => 'u',    # latin small letter u with ogonek
    "\xc3\x87" => 'C',    # ,C
    "\xc3\xa7" => 'c',    # ,c
    "\xc4\x86" => 'C',    # latin capital letter c with acute
    "\xc4\x87" => 'c',    # latin small letter c with acute
    "\xc4\x8c" => 'C',    # latin capital letter c with caron
    "\xc4\x8d" => 'c',    # latin small letter c with caron
    "\xc4\x88" => 'C',    # latin capital letter c with circumflex
    "\xc4\x89" => 'c',    # latin small letter c with circumflex
    "\xc4\x8a" => 'C',    # latin capital letter c with dot above
    "\xc4\x8b" => 'c',    # latin small letter c with dot above
    "\xc4\x8e" => 'D',    # latin capital letter d with caron
    "\xc4\x8f" => 'd',    # latin small letter d with caron
    "\xc4\x90" => 'D',    # latin capital letter d with stroke
    "\xc4\x91" => 'd',    # latin small letter d with stroke
    "\xc3\x91" => 'N',    # N~
    "\xc3\xb1" => 'n',    # n~
    "\xc5\x83" => 'N',    # latin capital letter n with acute
    "\xc5\x84" => 'n',    # latin small letter n with acute
    "\xc5\x87" => 'N',    # latin capital letter n with caron
    "\xc5\x88" => 'n',    # latin small letter n with caron
    "\xc5\x85" => 'N',    # latin capital letter n with cedilla
    "\xc5\x86" => 'n',    # latin small letter n with cedilla
    "\xc5\x89" => 'n',    # latin small letter n preceded by apostrophe
    "\xc5\x8a" => 'N',    # latin capital letter eng
    "\xc5\x8b" => 'n',    # latin small letter eng
    "\xc3\x9f" => 'ss',   # double-s
    "\xc5\x9a" => 'S',    # latin capital letter s with acute
    "\xc5\x9b" => 's',    # latin small letter s with acute
    "\xc5\xa0" => 'S',    # latin capital letter s with caron
    "\xc5\xa1" => 's',    # latin small letter s with caron
    "\xc5\x9e" => 'S',    # latin capital letter s with cedilla
    "\xc5\x9f" => 's',    # latin small letter s with cedilla
    "\xc5\x9c" => 'S',    # latin capital letter s with circumflex
    "\xc5\x9d" => 's',    # latin small letter s with circumflex
    "\xc8\x98" => 'S',    # latin capital letter s with comma below
    "\xc8\x99" => 's',    # latin small letter s with comma below
    "\xc5\xa4" => 'T',    # latin capital letter t with caron
    "\xc5\xa5" => 't',    # latin small letter t with caron
    "\xc5\xa2" => 'T',    # latin capital letter t with cedilla
    "\xc5\xa3" => 't',    # latin small letter t with cedilla
    "\xc5\xa6" => 'T',    # latin capital letter t with stroke
    "\xc5\xa7" => 't',    # latin small letter t with stroke
    "\xc8\x9a" => 'T',    # latin capital letter t with comma below
    "\xc8\x9b" => 't',    # latin small letter t with comma below
    "\xc6\x92" => 'f',    # latin small letter f with hook
    "\xc4\x9c" => 'G',    # latin capital letter g with circumflex
    "\xc4\x9d" => 'g',    # latin small letter g with circumflex
    "\xc4\x9e" => 'G',    # latin capital letter g with breve
    "\xc4\x9f" => 'g',    # latin small letter g with breve
    "\xc4\xa0" => 'G',    # latin capital letter g with dot above
    "\xc4\xa1" => 'g',    # latin small letter g with dot above
    "\xc4\xa2" => 'G',    # latin capital letter g with cedilla
    "\xc4\xa3" => 'g',    # latin small letter g with cedilla
    "\xc4\xa4" => 'H',    # latin capital letter h with circumflex
    "\xc4\xa5" => 'h',    # latin small letter h with circumflex
    "\xc4\xa6" => 'H',    # latin capital letter h with stroke
    "\xc4\xa7" => 'h',    # latin small letter h with stroke
    "\xc5\xb4" => 'W',    # latin capital letter w with circumflex
    "\xc5\xb5" => 'w',    # latin small letter w with circumflex
    "\xc3\x9d" => 'Y',    # latin capital letter y with acute
    "\xc3\xbd" => 'y',    # latin small letter y with acute
    "\xc5\xb8" => 'Y',    # latin capital letter y with diaeresis
    "\xc3\xbf" => 'y',    # latin small letter y with diaeresis
    "\xc5\xb6" => 'Y',    # latin capital letter y with circumflex
    "\xc5\xb7" => 'y',    # latin small letter y with circumflex
    "\xc5\xbd" => 'Z',    # latin capital letter z with caron
    "\xc5\xbe" => 'z',    # latin small letter z with caron
    "\xc5\xbb" => 'Z',    # latin capital letter z with dot above
    "\xc5\xbc" => 'z',    # latin small letter z with dot above
    "\xc5\xb9" => 'Z',    # latin capital letter z with acute
    "\xc5\xba" => 'z',    # latin small letter z with acute
);
function xliterate_utf8($s) {
    global $Utf8_ASCII;
    return strtr($s, $Utf8_ASCII);
}

function iso_dirify($s, $sep = '_') {
    if ($sep == '1') $sep = '_';
    $s = convert_high_ascii($s);  ## convert high-ASCII chars to 7bit.
    $s = strtolower($s);                   ## lower-case.
    $s = strip_tags($s);          ## remove HTML tags.
    $s = preg_replace('!&[^;\s]+;!', '', $s); ## remove HTML entities.
    $s = preg_replace('![^\w\s]!', '', $s);   ## remove non-word/space chars.
    $s = preg_replace('/\s+/',$sep,$s);         ## change space chars to underscores.
    return($s);
}

global $Latin1_ASCII;
$Latin1_ASCII = array(
    "\xc0" => 'A',    # A`
    "\xe0" => 'a',    # a`
    "\xc1" => 'A',    # A'
    "\xe1" => 'a',    # a'
    "\xc2" => 'A',    # A^
    "\xe2" => 'a',    # a^
    "\xc4" => 'A',    # A:
    "\xe4" => 'a',    # a:
    "\xc5" => 'A',    # Aring
    "\xe5" => 'a',    # aring
    "\xc6" => 'AE',   # AE
    "\xe6" => 'ae',   # ae
    "\xc3" => 'A',    # A~
    "\xe3" => 'a',    # a~
    "\xc8" => 'E',    # E`
    "\xe8" => 'e',    # e`
    "\xc9" => 'E',    # E'
    "\xe9" => 'e',    # e'
    "\xca" => 'E',    # E^
    "\xea" => 'e',    # e^
    "\xcb" => 'E',    # E:
    "\xeb" => 'e',    # e:
    "\xcc" => 'I',    # I`
    "\xec" => 'i',    # i`
    "\xcd" => 'I',    # I'
    "\xed" => 'i',    # i'
    "\xce" => 'I',    # I^
    "\xee" => 'i',    # i^
    "\xcf" => 'I',    # I:
    "\xef" => 'i',    # i:
    "\xd2" => 'O',    # O`
    "\xf2" => 'o',    # o`
    "\xd3" => 'O',    # O'
    "\xf3" => 'o',    # o'
    "\xd4" => 'O',    # O^
    "\xf4" => 'o',    # o^
    "\xd6" => 'O',    # O:
    "\xf6" => 'o',    # o:
    "\xd5" => 'O',    # O~
    "\xf5" => 'o',    # o~
    "\xd8" => 'O',    # O/
    "\xf8" => 'o',    # o/
    "\xd9" => 'U',    # U`
    "\xf9" => 'u',    # u`
    "\xda" => 'U',    # U'
    "\xfa" => 'u',    # u'
    "\xdb" => 'U',    # U^
    "\xfb" => 'u',    # u^
    "\xdc" => 'U',    # U:
    "\xfc" => 'u',    # u:
    "\xc7" => 'C',    # ,C
    "\xe7" => 'c',    # ,c
    "\xd1" => 'N',    # N~
    "\xf1" => 'n',    # n~
    "\xdd" => 'Y',    # Yacute
    "\xfd" => 'y',    # yacute
    "\xdf" => 'ss',   # szlig
    "\xff" => 'y'     # yuml
);
function convert_high_ascii($s) {
    global $mt;
    $lang = $mt->config['DefaultLanguage'];
    if ($lang == 'ja') {
        $s = mb_convert_encoding($s, 'UTF-8');
        return $s;
    }
    global $Latin1_ASCII;
    return strtr($s, $Latin1_ASCII);
}

global $Languages;
$Languages = array(
    'en' => array(
            array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
            array('January','February','March','April','May','June',
                  'July','August','September','October','November','December'),
            array('AM','PM'),
          ),

    'fr' => array(
            array('dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi' ),
            array('janvier', "f&#xe9;vrier", 'mars', 'avril', 'mai', 'juin',
               'juillet', "ao&#xfb;t", 'septembre', 'octobre', 'novembre',
               "d&#xe9;cembre"),
            array('AM','PM'),
          ),

    'es' => array(
            array('Domingo', 'Lunes', 'Martes', "Mi&#xe9;rcoles", 'Jueves',
               'Viernes', "S&#xe1;bado"),
            array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto',
                  'Septiembre','Octubre','Noviembre','Diciembre'),
            array('AM','PM'),
          ),

    'pt' => array(
            array('domingo', 'segunda-feira', "ter&#xe7;a-feira", 'quarta-feira',
               'quinta-feira', 'sexta-feira', "s&#xe1;bado"),
            array('janeiro', 'fevereiro', "mar&#xe7;o", 'abril', 'maio', 'junho',
               'julho', 'agosto', 'setembro', 'outubro', 'novembro',
               'dezembro' ),
            array('AM','PM'),
          ),

    'nl' => array(
            array('zondag','maandag','dinsdag','woensdag','donderdag','vrijdag',
                  'zaterdag'),
            array('januari','februari','maart','april','mei','juni','juli','augustus',
                  'september','oktober','november','december'),
            array('am','pm'),
             "%d %B %Y %H:%M",
             "%d %B %Y"
          ),

    'dk' => array(
            array("s&#xf8;ndag", 'mandag', 'tirsdag', 'onsdag', 'torsdag',
               'fredag', "l&#xf8;rdag"),
            array('januar','februar','marts','april','maj','juni','juli','august',
                  'september','oktober','november','december'),
            array('am','pm'),
            "%d.%m.%Y %H:%M",
            "%d.%m.%Y",
            "%H:%M",
          ),

    'se' => array(
            array("s&#xf6;ndag", "m&#xe5;ndag", 'tisdag', 'onsdag', 'torsdag',
               'fredag', "l&#xf6;rdag"),
            array('januari','februari','mars','april','maj','juni','juli','augusti',
                  'september','oktober','november','december'),
            array('FM','EM'),
          ),

    'no' => array(
            array("S&#xf8;ndag", "Mandag", 'Tirsdag', 'Onsdag', 'Torsdag',
               'Fredag', "L&#xf8;rdag"),
            array('Januar','Februar','Mars','April','Mai','Juni','Juli','August',
                  'September','Oktober','November','Desember'),
            array('FM','EM'),
          ),

    'de' => array(
            array('Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag',
                  'Samstag'),
            array('Januar', 'Februar', "M&#xe4;rz", 'April', 'Mai', 'Juni',
               'Juli', 'August', 'September', 'Oktober', 'November',
               'Dezember'),
            array('FM','EM'),
            "%d.%m.%y %H:%M",
            "%d.%m.%y",
            "%H:%M",
          ),

    'it' => array(
            array('Domenica', "Luned&#xec;", "Marted&#xec;", "Mercoled&#xec;",
               "Gioved&#xec;", "Venerd&#xec;", 'Sabato'),
            array('Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio',
                  'Agosto','Settembre','Ottobre','Novembre','Dicembre'),
            array('AM','PM'),
            "%d.%m.%y %H:%M",
            "%d.%m.%y",
            "%H:%M",
          ),

    'pl' => array(
            array('niedziela', "poniedzia&#322;ek", 'wtorek', "&#347;roda",
               'czwartek', "pi&#261;tek", 'sobota'),
            array('stycznia', 'lutego', 'marca', 'kwietnia', 'maja', 'czerwca',
               'lipca', 'sierpnia', "wrze&#347;nia", "pa&#378;dziernika",
               'listopada', 'grudnia'),
            array('AM','PM'),
            "%e %B %Y %k:%M",
            "%e %B %Y",
            "%k:%M",
          ),
            
    'fi' => array(
            array('sunnuntai','maanantai','tiistai','keskiviikko','torstai','perjantai',
                  'lauantai'),
            array('tammikuu', 'helmikuu', 'maaliskuu', 'huhtikuu', 'toukokuu',
               "kes&#xe4;kuu", "hein&#xe4;kuu", 'elokuu', 'syyskuu', 'lokakuu',
               'marraskuu', 'joulukuu'),
            array('AM','PM'),
            "%d.%m.%y %H:%M",
          ),
            
    'is' => array(
            array('Sunnudagur', "M&#xe1;nudagur", "&#xde;ri&#xf0;judagur",
               "Mi&#xf0;vikudagur", 'Fimmtudagur', "F&#xf6;studagur",
               'Laugardagur'),
            array("jan&#xfa;ar", "febr&#xfa;ar", 'mars', "apr&#xed;l", "ma&#xed;",
               "j&#xfa;n&#xed;", "j&#xfa;l&#xed;", "&#xe1;g&#xfa;st", 'september',             
               "okt&#xf3;ber", "n&#xf3;vember", 'desember'),
            array('FH','EH'),
            "%d.%m.%y %H:%M",
          ),
            
    'si' => array(
            array('nedelja', 'ponedeljek', 'torek', 'sreda', "&#xe3;etrtek",
               'petek', 'sobota'),
            array('januar','februar','marec','april','maj','junij','julij','avgust',
                  'september','oktober','november','december'),
            array('AM','PM'),
            "%d.%m.%y %H:%M",
          ),
            
    'cz' => array(
            array('Ned&#283;le', 'Pond&#283;l&#237;', '&#218;ter&#253;',
               'St&#345;eda', '&#268;tvrtek', 'P&#225;tek', 'Sobota'),
            array('Leden', '&#218;nor', 'B&#345;ezen', 'Duben', 'Kv&#283;ten',
               '&#268;erven', '&#268;ervenec', 'Srpen', 'Z&#225;&#345;&#237;',
               '&#216;&#237;jen', 'Listopad', 'Prosinec'),
            array('AM','PM'),
            "%e. %B %Y %k:%M",
            "%e. %B %Y",
            "%k:%M",
          ),
            
    'sk' => array(
            array('nede&#318;a', 'pondelok', 'utorok', 'streda',
               '&#353;tvrtok', 'piatok', 'sobota'),
            array('janu&#225;r', 'febru&#225;r', 'marec', 'apr&#237;l',
               'm&#225;j', 'j&#250;n', 'j&#250;l', 'august', 'september',
               'okt&#243;ber', 'november', 'december'),
            array('AM','PM'),
            "%e. %B %Y %k:%M",
            "%e. %B %Y",
            "%k:%M",
          ),

    'jp' => array(
            array('&#26085;&#26332;&#26085;', '&#26376;&#26332;&#26085;',
              '&#28779;&#26332;&#26085;', '&#27700;&#26332;&#26085;',
              '&#26408;&#26332;&#26085;', '&#37329;&#26332;&#26085;',
              '&#22303;&#26332;&#26085;'),
            array('1','2','3','4','5','6','7','8','9','10','11','12'),
            array('AM','PM'),
            "%Y&#24180;%m&#26376;%d&#26085; %H:%M",
            "%Y&#24180;%m&#26376;%d&#26085;",
            "%H:%M",
            "%Y&#24180;%m&#26376;",
          ),

    'ja' => array(
            array('&#26085;&#26332;&#26085;', '&#26376;&#26332;&#26085;',
              '&#28779;&#26332;&#26085;', '&#27700;&#26332;&#26085;',
              '&#26408;&#26332;&#26085;', '&#37329;&#26332;&#26085;',
              '&#22303;&#26332;&#26085;'),
            array('1','2','3','4','5','6','7','8','9','10','11','12'),
            array('AM','PM'),
            "%Y&#24180;%m&#26376;%d&#26085; %H:%M",
            "%Y&#24180;%m&#26376;%d&#26085;",
            "%H:%M",
            "%Y&#24180;%m&#26376;",
          ),

    'et' => array(
            array('ip&uuml;hap&auml;ev','esmasp&auml;ev','teisip&auml;ev',
                  'kolmap&auml;ev','neljap&auml;ev','reede','laup&auml;ev'),
            array('jaanuar', 'veebruar', 'm&auml;rts', 'aprill', 'mai',
               'juuni', 'juuli', 'august', 'september', 'oktoober',
              'november', 'detsember'),
            array('AM','PM'),
            "%m.%d.%y %H:%M",
            "%e. %B %Y",
            "%H:%M",
          ),
);

global $_encode_xml_Map;
$_encode_xml_Map = array('&' => '&amp;', '"' => '&quot;',
                         '<' => '&lt;', '>' => '&gt;',
                         '\'' => '&apos;');

function encode_xml($str, $nocdata = 0) {
    global $mt, $_encode_xml_Map;
    $nocdata or (isset($mt->config['NoCDATA']) and $nocdata = $mt->config['NoCDATA']);
    if ((!$nocdata) && (preg_match('/
        <[^>]+>  ## HTML markup
        |        ## or
        &(?:(?!(\#([0-9]+)|\#x([0-9a-fA-F]+))).*?);
                 ## something that looks like an HTML entity.
        /x', $str))) {
        ## If ]]> exists in the string, encode the > to &gt;.
        $str = preg_replace('/]]>/', ']]&gt;', $str);
        $str = '<![CDATA[' . $str . ']]>';
    } else {
        $str = strtr($str, $_encode_xml_Map);
    }
    return $str;
}

function decode_xml($str) {
    if (preg_match('/<!\[CDATA\[(.*?)]]>/', $str)) {
        $str = preg_replace('/<!\[CDATA\[(.*?)]]>/', '\1', $str);
        ## Decode encoded ]]&gt;
        $str = preg_replace('/]]&(gt|#62);/', ']]>', $str);
    } else {
        global $_encode_xml_Map;
        $str = strtr($str, array_flip($_encode_xml_Map));
    }
    return $str;
}

function encode_js($str) {
    if (!isset($str)) return '';
    $str = preg_replace('!\\\\!', '\\\\', $str);
    $str = preg_replace('!\'!', '\\\'', $str);
    $str = preg_replace('!"!', '\\"', $str);
    $str = preg_replace('!\n!', '\\n', $str);
    $str = preg_replace('!\f!', '\\f', $str);
    $str = preg_replace('!\r!', '\\r', $str);
    $str = preg_replace('!\t!', '\\t', $str);
    return $str;
}

function gmtime($ts = null) {
    if (!isset($ts)) {
        $ts = time();
    }
    $offset = date('Z', $ts);
    $ts -= $offset;
    $tsa = localtime($ts);
    $tsa[8] = 0;
    return $tsa;
}

function offset_time_list($ts, $blog = null, $dir = null) {
    return gmtime(offset_time($ts, $blog, $dir));
}

function strip_hyphen($s) {
    return preg_replace('/-+/', '-', $s);
}

function first_n_words($text, $n) {
    $text = strip_tags($text);
    $words = preg_split('/\s+/', $text);
    $max = count($words) > $n ? $n : count($words);
    return join(' ', array_slice($words, 0, $max));
}

function html_text_transform($str = '') {
    $paras = preg_split('/\r?\n\r?\n/', $str);
    if ($str == '') {
        return '';
    }
    foreach ($paras as $k => $p) {
        if (!preg_match('/^<\/?(?:h1|h2|h3|h4|h5|h6|table|ol|dl|ul|menu|dir|p|pre|center|form|select|fieldset|blockquote|address|div|hr)/', $p)) {
            $p = preg_replace('/\r?\n/', "<br />\n", $p);
            $p = "<p>$p</p>";
            $paras[$k] = $p;
        }
    }
    return implode("\n\n", $paras);
}

function encode_html($str, $quote_style = ENT_COMPAT) {
    if (!isset($str)) return '';
    $trans_table = get_html_translation_table(HTML_SPECIALCHARS, $quote_style);
    if( $trans_table["'"] != '&#039;' ) { # some versions of PHP match single quotes to &#39;
        $trans_table["'"] = '&#039;';
    }
    return (strtr($str, $trans_table));
}

function decode_html($str, $quote_style = ENT_COMPAT) {
    if (!isset($str)) return '';
    $trans_table = get_html_translation_table(HTML_SPECIALCHARS, $quote_style);
    if( $trans_table["'"] != '&#039;' ) { # some versions of PHP match single quotes to &#39;
        $trans_table["'"] = '&#039;';
    }
    return (strtr($str, array_flip($trans_table)));
}

function get_category_context(&$ctx) {
    # Get our hands on the category for the current context
    # Either in MTCategories, a Category Archive Template
    # Or the category for the current entry
    $cat = $ctx->stash('category') or
           $ctx->stash('archive_category');

    if (!isset($cat)) {
        # No category found so far, test the entry
        if ($ctx->stash('entry')) {
            $entry = $ctx->stash('entry');
            $cat = $ctx->mt->db->fetch_category($entry['placement_category_id']);
  
            # Return empty string if entry has no category
            # as the tag has been used in the correct context
            # but there is no category to work with
            if (!isset($cat)) {
                return null;
            }
        } else {
            $tag = $ctx->this_tag();
            return $ctx->error("MT$tag must be used in a category context");
        }
    }
    return $cat;
}

function munge_comment($text, $blog) {
    if (!$blog['blog_allow_comment_html']) {
        $text = strip_tags($text);
    }
    if ($blog['blog_autolink_urls']) {
        $text = preg_replace('!(^|\s|>)(https?://[^\s<]+)!s', '$1<a href="$2">$2</a>', $text);
    }
    return $text;
}

function length_text($text) {
    if (!extension_loaded('mbstring')) {
        $len = strlen($text);
    } else {
        $len = mb_strlen($text);
    }
    return $len;
}

function substr_text($text, $startpos, $length) {
    if (!extension_loaded('mbstring')) {
        $text = substr($text, $startpos, $length);
    } else {
        $text = mb_substr($text, $startpos, $length);
    }
    return $text;
}

function first_n_text($text, $n) {
    if (!isset($lang) || empty($lang)) { 
        global $mt;
        $lang = ($blog && $blog['blog_language'] ? $blog['blog_language'] : 
                     $mt->config['DefaultLanguage']);
    }
    if ($lang == 'jp') {
        $lang = 'ja';
    }

    if ($lang == 'ja') {
        $text = strip_tags($text);
        $text = preg_replace('/\r?\n/', " ", $text);
        return substr_text($text, 0, $n);
    }else{
        return first_n_words($text, $n);
    }
}

function tag_split($str) {
    $list = preg_split('/[,;]/', $str);
    $tags = array();
    foreach ($list as $tag) {
        $tag = preg_replace('/(^\s+|\s+$)/s', '', $tag);
        $tag = preg_replace('/\s+/s', ' ', $tag);
        if ($tag == '') continue;
        $tag = preg_replace('/[;,]+$/', '', $tag);
        $tag = preg_replace('/^"(.+?)"$/', '$1', $tag);
        $tag = preg_replace("/^'(.+?)'\$/", '$1', $tag);
        if ($tag != '') $tags[] = $tag;
    }
    return $tags;
}

# sorts by length of category label, from longest to shortest
function catarray_length_sort($a, $b) {
	$al = strlen($a['category_label']);
	$bl = strlen($b['category_label']);
	$al == $bl ? 0 : $al < $bl ? 1 : -1;
}

function create_expr_exception($m) {
    if ($m[2])
        return '(0)';
    else
        return $m[1];
}

function create_cat_expr_function($expr, &$cats, $param) {
    global $mt;
    $cats_used = array();

    $include_children = $param['children'] ? 1 : 0;

    # handle path category expressions too?
	usort($cats, 'catarray_length_sort');
    foreach ($cats as $cat) {
        $catl = $cat['category_label'];
        $catid = $cat['category_id'];
        $oldexpr = $expr;
        $catre = preg_quote($catl, "/");
        if (!preg_match("/(?:\[$catre\]|$catre|#$catid)/", $expr))
            continue;
        if ($include_children) {
            $kids = array($cat);
            $child_cats = array();
            while ($c = array_shift($kids)) {
                $child_cats[$c['category_id']] = $c;
                $children = $mt->db->fetch_categories(array('category_id' => $c['category_id'], 'children' => 1, 'show_empty' => 1));
                if ($children)
                    $kids = array_merge($kids, $children);
            }

            $repl = '';
            foreach ($child_cats as $ccid => $cc) {
                $repl .= '||#' . $ccid;
            }
            if (strlen($repl)) $repl = substr($repl, 2);
            $repl = '(' . $repl . ')';
        } else {
            $repl = "(#$catid)";
        }
	    $expr = preg_replace("/(?:\[$catre\]|$catre|#$catid)/", $repl,
            $expr);
        if ($oldexpr != $expr) {
            if ($include_children) {
                foreach ($child_cats as $ccid => $cc)
                    $cats_used[$ccid] = $cc;
            } else {
                $cats_used[$catid] = $cat;
            }
        }
    }
    $expr = preg_replace('/\bAND\b/i', '&&', $expr);
    $expr = preg_replace('/\bOR\b/i', '||', $expr);
    $expr = preg_replace('/\bNOT\b/i', '!', $expr);
    $expr = preg_replace_callback('/( |#\d+|&&|\|\||!|\(|\))|([^#0-9&|!()]+)/', 'create_expr_exception', $expr);

    # strip out all the 'ok' stuff. if anything is left, we have
    # some invalid data in our expression:
    $test_expr = preg_replace('/!|&&|\|\||\(0\)|\(|\)|\s|#\d+/', '', $expr);
    if ($test_expr != '') {
        print("Invalid category filter: [$expr]");
        return;
    }

	if (!preg_match('/!/', $expr))
	    $cats = array_values($cats_used);

    $expr = preg_replace('/#(\d+)/', "array_key_exists('\\1', \$c['p'][\$e['entry_id']])", $expr);
    $expr = 'if (!array_key_exists($e["entry_id"], $c["p"])) return FALSE; return (' . $expr . ');';
    $fn = create_function('&$e,&$c', $expr);
    if ($fn === FALSE) {
        print("Invalid category filter: $expr");
        return;
    }
    return $fn;
}

function cat_path_to_category($path, $blog_id = 0) {
    global $mt;
    if (!$blog_id)
        $blog_id = $mt->blog_id;
    $mtdb =& $mt->db;
    if (preg_match_all('/(\[[^]]+?\]|[^]\/]+)/', $path, $matches)) {
        # split on slashes, fields quoted by []
        $cat_path = $matches[1];
        for ($i = 0; $i < count($cat_path); $i++) {
            $cat_path[$i] = preg_replace('/^\[(.*)\]$/', '\1', $cat_path[$i]);       # remove any []
        }
        $last_cat_id = 0;
        foreach ($cat_path as $label) {
            $cats = $mtdb->fetch_categories(array('label' => $label, 'parent' => $last_cat_id, 'blog_id' => $blog_id, 'show_empty' => 1));
            if (!$cats || (count($cats) != 1))
                break;
            $cat = $cats[0];
            $last_cat_id = $cat['category_id'];
        }
    }
    if (!$cat && $path) {
        $cats = $mtdb->fetch_categories(array('label' => $path, 'blog_id' => $blog_id, 'show_empty' => 1));
        if ($cats && (count($cats) == 1))
            return $cats[0];
    }
    return $cat;
}

# sorts by length of tag name, from longest to shortest
function tagarray_length_sort($a, $b) {
	$al = strlen($a['tag_name']);
	$bl = strlen($b['tag_name']);
	$al == $bl ? 0 : $al < $bl ? 1 : -1;
}

function create_tag_expr_function($expr, &$tags) {
    $tags_used = array();

	usort($tags, 'tagarray_length_sort');
    foreach ($tags as $tag) {
        $tagn = $tag['tag_name'];
        $tagid = $tag['tag_id'];
        $oldexpr = $expr;
	    $expr = preg_replace("/(?:\Q[$tagn]\E|\Q$tagn\E)/", "#$tagid",
	        $expr);
	    if ($oldexpr != $expr)
	        $tags_used[$tagid] = $tag;
	}
    $expr = preg_replace('/\bAND\b/i', '&&', $expr);
    $expr = preg_replace('/\bOR\b/i', '||', $expr);
    $expr = preg_replace('/\bNOT\b/i', '!', $expr);
    $expr = preg_replace_callback('/( |#\d+|&&|\|\||!|\(|\))|([^#0-9&|!()]+)/', 'create_expr_exception', $expr);

    # strip out all the 'ok' stuff. if anything is left, we have
    # some invalid data in our expression:
    $test_expr = preg_replace('/!|&&|\|\||\(0\)|\(|\)|\s|#\d+/', '', $expr);
    if ($test_expr != '')
        die("bad expression in tag filter: [$expr]");

	if (!preg_match('/!/', $expr))
	    $tags = array_values($tags_used);

    $expr = preg_replace('/#(\d+)/', "array_key_exists('\\1', \$c['t'][\$e['entry_id']])", $expr);
    $expr = 'return array_key_exists($e["entry_id"], $c["t"])?' . $expr .
        ':FALSE;';
    $fn = create_function('&$e,&$c', $expr);
    if ($fn === FALSE) {
        die("bad expression in tag filter: $expr");
    }
    return $fn;
}
?>
