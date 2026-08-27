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

   /*
    |--------------------------------------------------------------------------
    | Source URL
    |--------------------------------------------------------------------------
    |
    | The URL to retrieve the source data from.
    |
    | To read the list from somewhere else entirely - a local file, an internal
    | mirror - bind your own Hampel\Tlds\Fetcher\TldFetcher implementation in
    | your application's service provider; this setting is then unused.
    |
    | Default: 'http://data.iana.org/TLD/tlds-alpha-by-domain.txt'
    |
    */

    'url' => 'http://data.iana.org/TLD/tlds-alpha-by-domain.txt',

    'cache' => [

        /*
        |--------------------------------------------------------------------------
        | Cache Expiry
        |--------------------------------------------------------------------------
        |
        | How long should the application cache TLD data - in seconds
        | Default: 86400 seconds = 1 day
        |
        */

        'expiry' => 86400,

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

        /*
        |--------------------------------------------------------------------------
        | Cache Store
        |--------------------------------------------------------------------------
        |
        | Which of the application's configured cache stores to keep the TLD data
        | in. Specify null to use the application's default store.
        |
        | Default: null
        |
        */

        'store' => null,

    ],
];
