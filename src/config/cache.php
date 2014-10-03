<?php
/**
 * Configuration for TLD fetcher cache
 */

return [

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
];

?>
