<?php

namespace Hampel\Tlds\Tests\Fakes;

use Hampel\Tlds\Exceptions\FetchException;
use Hampel\Tlds\Fetcher\TldFetcher;

/**
 * A hand-written double rather than a Mockery mock.
 *
 * Mockery's shouldReceive() returns ExpectationInterface|HigherOrderMessage, and
 * ->once() is on neither half of that union, so every expectation is a PHPStan error
 * on any analyser not clever enough to narrow it - which the --prefer-lowest corner
 * resolves. Counting the calls here says the same thing and says it to every version.
 */
class FakeTldFetcher implements TldFetcher
{
    public int $calls = 0;

    public function __construct(
        protected string $data = '',
        protected ?FetchException $throw = null
    ) {
    }

    public function fetchTlds()
    {
        $this->calls++;

        if ($this->throw !== null) {
            throw $this->throw;
        }

        return $this->data;
    }
}
