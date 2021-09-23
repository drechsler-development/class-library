# class-library
A library of useful classes I am using often in different projects

[![License](https://poser.pugx.org/buonzz/laravel-4-freegeoip/license.svg)](https://packagist.org/packages/buonzz/laravel-4-freegeoip)

## Installation

Quickly describe how to install your project and how to get it running

1. Install Composer dependencies

    composer install

Laravel 4 Library for calling http://freegeoip.net/ API.

In contrary to all other packages wherein it requires that you have the geoip database in your filesystem, this library calls a free service
So you dont really have to worry about downloading and maintaining geoip data from Maxmind in your own server.

Just install the package, add the config and it is ready to use!


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

Usage
=====

Get country of the visitor

    GeoIP::getCountry();  // returns "United States"
    
Get country code of the visitor

    GeoIP::getCountryCode();  // returns "US"

Get region of the visitor


Credits
=======

* Peter Dragicevic for the DHL library => https://github.com/Petschko/dhl-php-sdk
* Markus Poerschke for the ICAL class  => https://github.com/markuspoerschke/iCal
