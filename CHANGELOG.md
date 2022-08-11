CHANGELOG
=========

1.10.0 (2022-08-11)
------------------

* works with Laravel 9.x

1.9.1 (2022-08-11)
------------------

* change tests to use PHPUnit expectExceptionMessage
* change from Guzzle 6.5 => 7.x functions in unit tests: GuzzleHttp\Psr7\parse_response => GuzzleHttp\Psr7\Message::parseResponse

1.9.0 (2020-09-17)
------------------

* works with Laravel 8.x

1.8.0 (2020-06-17)
------------------

* works with Laravel 7.x

1.7.1 (2019-10-14)
------------------

* fixed wrong namespace for Str class

1.7.0 (2019-10-08)
------------------

* updates to support Laravel v6.x

1.6.1 (2019-03-27)
------------------

* restrict framework version to 5.8 because of cache config changes

1.6.0 (2019-03-27)
------------------

* updates to support Laravel 5.8
* cache expiry config values are now specified in seconds rather than minutes, in line with changes to Laravel 5.8

1.5.1 (2019-03-27)
------------------

* explictly support up to Laravel v5.7

1.5.0 (2019-01-23)
------------------

* updates to support Laravel 5.7
* support for PHPUnit v7
* refactored service provider to make more use of automatic dependency injection
* removed string based container identifiers in favour of class based
* refactored Tlds class to split fetcher into separate classes
* rewrote tests for PHPUnit v7
* changed some config options to remove unnecessary nested arrays to make overriding them easier

1.4.0 (2018-08-25)
------------------

* updates to support Laravel 5.6
* implemented Laravel package discovery in composer.json

1.3.0 (2016-08-16)
------------------

* Convert bindShared to singleton to support Laravel 5.2. (thanks Josh Foskett <me@joshfoskett.com>)
* updated to Laravel 5.2 and Guzzle 6.x

1.2.1 (2015-05-22)
------------------

* removed redundant closing php tags

1.2.0 (2015-02-14)
------------------

* updated to be fully Laravel 5.0 (release) compatible
* updated unit tests

1.1.2 (2014-10-03)
------------------

* fix autoload-dev in composer.json, should be pointing to tests/
* tidy up Service Provider - use bindShared function
* clean up old unit tests in ValidatorExtensionsTest

1.1.1 (2014-10-03)
------------------

* update package dependency for hampel/validate to ~2.2

1.1.0 (2014-10-03)
------------------

* added validation extensions to validate TLDs and domain names against the TLD list

1.0.0 (2014-10-03)
------------------

* initial release
