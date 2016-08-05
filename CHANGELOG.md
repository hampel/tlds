CHANGELOG
=========

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
