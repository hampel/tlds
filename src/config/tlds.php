 <?php
/**
 * Configuration for TLD fetcher source
 *
 * File format of source data is assumed to be:
 * - one TLD per line
 * - either upper or lower case
 * - lines beginning with # are ignored
 * - internationalized domains allowed using punycode notation
 *
 */

return [

	'source' => [
		/*
		|--------------------------------------------------------------------------
		| TLD Source Type
		|--------------------------------------------------------------------------
		|
		| Specify the type of source we will use to retrieve the latest TLD list
		|
		| Supported: "url", "filesystem"
		|
		| Default: 'url'
		|
		*/

		'type' => 'url',

		/*
		|--------------------------------------------------------------------------
		| Source URL
		|--------------------------------------------------------------------------
		|
		| If 'type' is set to 'url', set this to the URL to retrieve the source data
		| from.
		|
		| Default: 'http://data.iana.org/TLD/tlds-alpha-by-domain.txt'
		|
		*/

		'url' => 'http://data.iana.org/TLD/tlds-alpha-by-domain.txt',

		/*
		|--------------------------------------------------------------------------
		| Source Disk
		|--------------------------------------------------------------------------
		|
		| If 'type' is set to 'filesystem', set this to the name of the Laravel
		| filesystem (using Flysystem) which has been configured as the location
		| the source data is stored in
		|
		| Default: 'default'
		|
		*/

		'disk' => 'default',

		/*
		|--------------------------------------------------------------------------
		| Source Disk
		|--------------------------------------------------------------------------
		|
		| If 'type' is set to 'filesystem', set this to the path of the source data
		| file on the filesystem specified by the disk parameter
		|
		| Default: ''
		|
		*/

		'path' => '',
	],

	'cache' => [

		/*
		|--------------------------------------------------------------------------
		| Cache Expiry
		|--------------------------------------------------------------------------
		|
		| How long should the application cache TLD data - in minutes
		| Default: 1440 minutes = 1 day
		|
		*/

		'expiry' => 1440,

		/*
		|--------------------------------------------------------------------------
		| Cache Keys
		|--------------------------------------------------------------------------
		|
		| Key to cache TLD information, only need to change this in the case of
		| conflicts with other packages or code
		|
		| Default: 'tlds'
		|
		*/

		'key' => 'tlds',

	],
];
