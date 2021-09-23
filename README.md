# class-library
A library of useful classes I am using often in different projects

[![License](https://poser.pugx.org/buonzz/laravel-4-freegeoip/license.svg)](https://packagist.org/packages/buonzz/laravel-4-freegeoip)

Requirements
============

* PHP >= 7.4
* cURL Extension
* json Extension
* soap Extension
* gd  Extension
* mbstring  Extension
* ctype Extension

Installation
============

    composer require drechsler-development\class-library

Just install the package and it is ready to use!

Usage
=====

Formats a date into german format with long year

    $date = "2012-12-31";
    $formattedDate = Date::FormatDateToFormat ($date, Date::DATE_FORMAT_GERMAN_DATE_LONG_YEAR); //will return 31.12.2021

Credits
=======

* Peter Dragicevic for the DHL library => https://github.com/Petschko/dhl-php-sdk
* Markus Poerschke for the ICAL class  => https://github.com/markuspoerschke/iCal
