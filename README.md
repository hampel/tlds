Top Level Domain Fetcher for Laravel
====================================

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hampel/tlds.svg?style=flat-square)](https://packagist.org/packages/hampel/tlds)
[![Total Downloads](https://img.shields.io/packagist/dt/hampel/tlds.svg?style=flat-square)](https://packagist.org/packages/hampel/tlds)
[![Open Issues](https://img.shields.io/github/issues-raw/hampel/tlds.svg?style=flat-square)](https://github.com/hampel/tlds/issues)
[![License](https://img.shields.io/packagist/l/hampel/tlds.svg?style=flat-square)](https://packagist.org/packages/hampel/tlds)

This package provides a mechanism for retrieving a list of the current Top Level Domains (TLDs) managed by IANA.
It also provides several Laravel classes to validate domain names and TLDs.

By [Simon Hampel](mailto:simon@hampelgroup.com)

Installation
------------

To install using composer, run the following command:

`composer require hampel/tlds`

If you want to change the default Tlds configuration, first publish it using the command:

```bash
$ php artisan vendor:publish --provider="Hampel\Tlds\TldServiceProvider"
```

The config files can then be found in `config/tlds.php`.

Configuration
-------------

Refer to the configuration file  for more details about configuration options.

__tlds.cache.expiry__ - sets the cache expiry time in seconds

__tlds.cache.key__ - sets the key used to store the TLD data in the cache

__tlds.source__ - set this to 'url' to retrieve the data from a website (eg IANA), set it to 'filesystem' to retrieve
the data from a local source (you'll need to configure a Laravel filesystem 'disk' to make this work).

__tlds.url__ - if source is set to 'url', enter the URL to retrieve the data from. By default this is set to the
IANA source file

__tlds.disk__ - if source is set to 'filesystem', enter the name of the Laravel filesystem disk you have
configured in the 'filesystems.disks' configuration option

__tlds.path__ - if source is set to 'filesystem', enter the path to the data file relative to the root path
configured for the disk in the 'fileystems.disks' configuration option (eg. 'tlds/tlds-alpha-by-domain.txt')

Usage
-----

The Tlds package provides a simple mechanism for reading a data file containing a list of Top Level Domains, one per
line and returning an array of data. This data may optionally be cached for performance.

The data file can be retrieved directly from the Internet Assigned Numbers Authority (IANA) website, using Guzzle, or
if you have a different source or prefer to fetch the data file yourself and then read it from a local source you can
reconfigure the Tlds package to read the data file from any Laravel supported filesystem (using Flysystem).

There is also an artisan command available which can be used to fetch the latest data file to refresh the cache. This
is ideal for automating the retrieval of data using a cron job or similar.

The simplest way to call the package is using the Facade:

```php
// get a "fresh" copy of the TLD list
$tld_array = Tlds::fresh();

// or if you prefer to not use Facades:
$tld_array = $app->make(Hampel\Tlds\Tlds::class)->fresh();
```

This returns a "fresh" copy of the data (bypassing the cache) as an array of TLDs.

To fetch the TLD array from the cache or have it update automatically if the cached data has expired

```php
// get the TLD list from the cache (or update the cache if it has expired)
$tld_array = Tlds::get();

// if you prefer to manage the cache yourself, you can do this all manually, for example:
if (Cache::has(Config::get('tlds.cache.key'))
{
    $tld_array = Cache::get(Config::get('tlds.cache.key'));
}
else
{
    Cache::put(Config::get('tlds.cache.key'), Tlds::fresh(), Config::get('tlds.cache.expiry'));
}
```

To run the artisan console command to update the cache:

```bash
$ php artisan tld:update
Added 725 TLDs to the TLD Cache
```

Validators
----------

This package adds additional validators for Laravel - refer to
[Laravel Documentation - Validation](http://laravel.com/docs/validation) for general usage instructions.

__domain__

The field under validation must be a valid domain name. The Top Level Domain (TLD) is checked against a list of all
acceptable TLDs, including internationalised domains in punycode notation

**domain_in:_com,net,..._**

The field under validation must be a valid domain with a TLD from one of the specified options

__tld__

The field under validation must end in a valid Top Level Domain (TLD). The TLD is checked against a list of all
acceptable TLDs, including internationalised domains in punycode notation

If no dots are contained in the supplied value, it will be assumed to be only a TLD.

If the value contains dots, only the part after the last dot will be validated.

**tld_in:_com,net,..._**

The field under validation must end in a TLD from one of the specified options

If no dots are contained in the supplied value, it will be assumed to be only a TLD.

If the value contains dots, only the part after the last dot will be validated.
