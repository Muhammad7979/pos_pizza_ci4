<?php

/*
 * Currency locale
 */

use App\Models\Appconfig;
use Config\Services;

function currency_side()
{
    $appData = new Appconfig();
    $number_locale = $appData->get('number_locale');
    $currency_symbol = $appData->get('currency_symbol');
    $fmt = new \NumberFormatter($number_locale, \NumberFormatter::CURRENCY);
    $fmt->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, $currency_symbol);
    return !preg_match('/^¤/', $fmt->getPattern());
}

function quantity_decimals()
{
    $appData = new Appconfig();
    return $appData->get('quantity_decimals') ? $appData->get('quantity_decimals') : 0;
}

function totals_decimals()
{
    $appData = new Appconfig();
	// $config = get_instance()->config;
	return $appData->get('currency_decimals') ? $appData->get('currency_decimals') : 0;
}

function to_currency($number)
{
    return to_decimals($number, 'currency_decimals', \NumberFormatter::CURRENCY);
}

function to_currency_no_money($number)
{
    return to_decimals($number, 'currency_decimals');
}

function to_tax_decimals($number)
{
	// taxes that are NULL, '' or 0 don't need to be displayed
	// NOTE: do not remove this line otherwise the items edit form will show a tax with 0 and it will save it
    if(empty($number))
    {
        return $number;
    }
	
    return to_decimals($number, 'tax_decimals');
}

function to_quantity_decimals($number)
{
    return to_decimals($number, 'quantity_decimals');
}

function to_decimals($number, $decimals, $type=\NumberFormatter::DECIMAL)
{
	// ignore empty strings and return
	// NOTE: do not change it to empty otherwise tables will show a 0 with no decimal nor currency symbol
    if(!isset($number))
    {
        return $number;
    }
    $appData = new Appconfig();
	
    $fmt = new \NumberFormatter($appData->get('number_locale'), $type);
    $fmt->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $appData->get('currency_decimals'));
    $fmt->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $appData->get('currency_decimals'));
    if (empty($appData->get('thousands_separator')))
    {
        $fmt->setAttribute(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, \NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }
    $fmt->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, $appData->get('currency_symbol'));
    return $fmt->format((float) $number);
}

function parse_decimals($number)
{
    // ignore empty strings and return
    if(empty($number))
    {
        return $number;
    }

    $appData = new Appconfig();
    $fmt = new \NumberFormatter( $appData->get('number_locale'), \NumberFormatter::DECIMAL );
    if (empty($appData->get('thousands_separator')))
    {
        $fmt->setAttribute(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, '');
    }
    return $fmt->parse($number);
}

/*
 * Time locale conversion utility
 */

function dateformat_momentjs($php_format)
{
    $SYMBOLS_MATCHING = array(
        'd' => 'DD',
        'D' => 'ddd',
        'j' => 'D',
        'l' => 'dddd',
        'N' => 'E',
        'S' => 'o',
        'w' => 'e',
        'z' => 'DDD',
        'W' => 'W',
        'F' => 'MMMM',
        'm' => 'MM',
        'M' => 'MMM',
        'n' => 'M',
        't' => '', // no equivalent
        'L' => '', // no equivalent
        'o' => 'YYYY',
        'Y' => 'YYYY',
        'y' => 'YY',
        'a' => 'a',
        'A' => 'A',
        'B' => '', // no equivalent
        'g' => 'h',
        'G' => 'H',
        'h' => 'hh',
        'H' => 'HH',
        'i' => 'mm',
        's' => 'ss',
        'u' => 'SSS',
        'e' => 'zz', // deprecated since version $1.6.0 of moment.js
        'I' => '', // no equivalent
        'O' => '', // no equivalent
        'P' => '', // no equivalent
        'T' => '', // no equivalent
        'Z' => '', // no equivalent
        'c' => '', // no equivalent
        'r' => '', // no equivalent
        'U' => 'X'
    );

    return strtr($php_format, $SYMBOLS_MATCHING);
}

function dateformat_bootstrap($php_format)
{
    $SYMBOLS_MATCHING = array(
        // Day
        'd' => 'dd',
        'D' => 'd',
        'j' => 'd',
        'l' => 'dd',
        'N' => '',
        'S' => '',
        'w' => '',
        'z' => '',
        // Week
        'W' => '',
        // Month
        'F' => 'MM',
        'm' => 'mm',
        'M' => 'M',
        'n' => 'm',
        't' => '',
        // Year
        'L' => '',
        'o' => '',
        'Y' => 'yyyy',
        'y' => 'yy',
        // Time
        'a' => 'p',
        'A' => 'P',
        'B' => '',
        'g' => 'H',
        'G' => 'h',
        'h' => 'HH',
        'H' => 'hh',
        'i' => 'ii',
        's' => 'ss',
        'u' => ''
    );

    return strtr($php_format, $SYMBOLS_MATCHING);
}

?>