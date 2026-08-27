# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this package is

`hampel/tlds` — fetches IANA's list of top-level domains, caches it, and exposes it to a Laravel
application through a facade, an artisan command, and four validation rules.

The rules are a thin wrapper: the matching itself lives in
[`hampel/validate`](https://packagist.org/packages/hampel/validate) (`isDomain()`, `isTld()`), a
plain PHP library with no service provider of its own. This package supplies the *TLD list* those
functions take as a second argument.

**The validators are the product; fetch-and-parse is the implementation underneath.** Strip the
Laravel integration and what remains is a URL, one regex and a parse loop — too little to stand as a
library of its own. That is why this is a Laravel package rather than a plain core with a thin
wrapper over it, which is the usual shape here.

## Commands

```bash
composer install
composer check                                    # lint, analyse, test - what CI runs
composer lint / format / analyse / test           # the same steps individually
vendor/bin/phpunit --filter testProcessReturnsFilteredData   # one test (methods are camelCase)
vendor/bin/phpunit tests/Validation               # one directory

# the corners CI covers
composer update --with="illuminate/console:^12.0" --with="illuminate/contracts:^12.0" \
  --with="illuminate/http:^12.0" --with="illuminate/support:^12.0" \
  --with="orchestra/testbench:^10.0" --prefer-lowest
composer update --with="illuminate/console:^13.0" --with="illuminate/contracts:^13.0" \
  --with="illuminate/http:^13.0" --with="illuminate/support:^13.0" \
  --with="orchestra/testbench:^11.0"
```

PHPStan runs at **level 10** with larastan, over `src` and `tests`.

**`composer check` is not the whole of CI.** The `dependencies` job has no local equivalent in that
script — see *Declared dependencies* below. Run it by hand before proposing a release.

**Testbench majors track Laravel majors** — Testbench 10 is Laravel 12, Testbench 11 is Laravel 13 —
so both are pinned together in the matrix. Letting Composer choose Testbench would quietly test a
different Laravel than the job name claims.

## Architecture

### The service provider is the only Laravel-aware file

Everything else takes its collaborators. `TldManager` is constructed with a
`Illuminate\Contracts\Cache\Repository`, a cache key and an expiry; `UrlTldFetcher` with an
`Illuminate\Http\Client\Factory` and a URL. Nothing below the provider reads configuration or calls
a facade.

That is not tidiness for its own sake — it is what keeps `require` to four `illuminate/*`
components, and it is why most of the suite needs no booted application. **Reaching for `Config::`
or `Cache::` inside `TldManager` or a fetcher would undo both.** Depending on the cache *contract*
rather than the facade is also what lets an application choose the store (`tlds.cache.store`);
the previous version silently took the default and offered no way to say otherwise.

The two facades that remain are in the validation rules, and they have to be: an application writes
`new Domain` in a rules array, so there is no constructor to inject through.

### One shipped fetcher, and an interface that outranks it

`TldFetcher` has a single method returning the raw file as a string. The provider **binds a default**
rather than dispatching on a config string, so an application that wants a local file, an internal
mirror or an S3 object binds its own implementation and the default gives way. There is deliberately
no `tlds.source` setting — a container binding says the same thing without this package having to
enumerate the options in advance.

`TldManager` is bound as a **singleton**; `TldFetcher` is not, so a rebinding in an application
provider is picked up.

### `TldManager` owns parsing and the cache; the fetchers own neither

`fetch()` → raw string. `process()` → filtered array. `fresh()` composes the two and throws
`NoDataException` when nothing survives. `get()` is `remember()` around `fresh()`. `put()`/`forget()`
exist so `tld:update` can clear-then-repopulate rather than let a failed fetch leave a stale entry
in place.

`process()` is the only place the data is validated, against `/^(?:[a-z]{2,63}|xn--[a-z0-9\-]+)$/i`.
Anything failing it is **recorded and skipped, not fatal** — one malformed line in a 1500-line file
must not cost the application its TLD list. The rejected lines come back from `skipped()` and
`tld:update` prints them.

**`tld:update` reports them on both the success and the failure path, deliberately.** `fresh()`
throws when *every* line is rejected, which is exactly when the caller most needs to know which ones.

**The package does not log.** An earlier version called `Log::warning()` per rejected line, which put
its diagnostics in the application's log at a level the application never chose. Diagnostics go where
a human is looking — the console — and a failed run is already loud through a non-zero exit and
stderr.

### Exceptions are grouped by a marker interface

`Exceptions\Exception` is empty; `RuntimeException` extends the SPL class and implements it;
`FetchException` extends that, and `HttpException` and `NoDataException` extend `FetchException`.
`UpdateTlds` catches `FetchException` alone — so **anything a fetcher or `fresh()` can throw must sit
under `FetchException`**, including in a custom `TldFetcher`, or the command turns it into an
unhandled error instead of an exit code 1.

## Version support

`php: >=8.3` per the Tier A support policy, with PHPStan analysing the whole 8.3–8.5 range in one
pass. Keep `phpVersion` in `phpstan.neon` in step with the `php` constraint.

`illuminate/*` spans **only currently supported Laravel majors**. Laravel gives 18 months of bug
fixes and 24 of security fixes, which is narrower than instinct suggests — check before widening.

**`phpstan/phpstan` must be a version that accepts the `phpVersion.max` in `phpstan.neon`.** It
validates that against a range baked into the release, so naming a PHP version newer than the tool
knows about is a configuration error, not a degraded analysis. `max: 80500` needs `^2.1.22`.

### What the `phpVersion` range does not do

Measured on this package, because both are easy to assume and both are false:

- **It does not gate function availability.** `str_contains()` at `phpVersion: 70400` analyses clean.
  A genuinely undefined function *is* caught, so the check runs — it simply ignores the version.
- **A range reports the intersection, not the union.** An implicitly-nullable parameter, deprecated
  in 8.4, is an error at `phpVersion: 80400` and **silent** at `min: 80300, max: 80500`. Anything
  arriving above the floor is invisible while the floor is still in the range.

**So the PHP 8.5 CI job is not confirming what PHPStan already found — it is the only thing that
finds it.** Do not remove it on the reasoning that the range covers the PHP axis.

## Declared dependencies

`require` names four components — `support` (ServiceProvider, the `App` and `Facade` facades),
`contracts` (`ValidationRule`, `Cache\Repository`, `Cache\Factory`), `console` (`Command`) and
`http` (`Client\Factory`). Guzzle arrives with `illuminate/http`, which requires `^7.8.2|^8.0`;
**do not pin it in `require-dev`**, which caps the suite below what a Laravel 13 application
actually resolves.

This is easy to get wrong and hard to notice, because every real consumer is a Laravel application
that already has `laravel/framework` — and the framework `replace`s every `illuminate/*`, so the
split packages are never what gets installed. A version of this package that declared only
`illuminate/support` fatalled on `Class "Illuminate\Console\Command" not found` in a project that
required it alone, and nothing in the test suite noticed.

**`composer-require-checker` is the check that catches it**, and it is the only one that catches an
undeclared `ext-*`. The `dependencies` CI job runs it against a `--no-dev` tree, alongside a dev-free
PHPStan pass over `src` alone.

**Know what each half cannot see.** The dev-free PHPStan run catches a `src` reference to something
only a *dev* dependency supplies, and nothing else: `--no-dev` removes `require-dev` and leaves every
transitive package installed, and on a Laravel package `replace` means the framework satisfies every
`Illuminate\*` symbol whether declared or not. `league/flysystem` was reached from a `src` catch
block while `illuminate/filesystem` only *suggests* it — that is the shape only the checker finds.

Its whitelist in `.github/composer-require-checker.json` is not a silencer. Every entry is an
`Illuminate\*` symbol whose component **is** in `require`, unresolvable only because there is no
`vendor/illuminate/` to attribute it to. A **new** `Illuminate` symbol appearing there means check
its component is in `require`, not extend the list; a **non-Illuminate** symbol appearing there is a
real finding.

Reproduce locally in a throwaway clone — this deletes `vendor/` as it goes:

```bash
composer install --no-dev
mkdir -p /tmp/crc && composer -d /tmp/crc require maglnet/composer-require-checker
/tmp/crc/vendor/bin/composer-require-checker check \
  --config-file=.github/composer-require-checker.json composer.json
composer install
```

## Tests

Orchestra Testbench boots a minimal application from inside the suite, so the Laravel version comes
from Composer resolution rather than from a framework installed on the box. **Never install a
framework to test this package.**

**Most tests do not use it.** `TldManagerTest` and `UrlTldFetcherTest` extend PHPUnit's `TestCase`
directly and construct their subjects — a real `Illuminate\Cache\Repository` over an `ArrayStore`, a
real HTTP client with fakes. That is a consequence of the injection above, and worth preserving:
they run in hundredths of a second and assert on behaviour rather than on facade expectations.
Testbench is for what genuinely needs a container — the artisan command and the validation rules.

`tests/Fakes/FakeTldFetcher` is a hand-written double rather than a Mockery mock, and that is
deliberate: Mockery's `shouldReceive()` returns `ExpectationInterface|HigherOrderMessage` and
`->once()` is on neither half of that union, so every expectation is a PHPStan error under the older
larastan the `--prefer-lowest` job resolves. Counting calls says the same thing to every version.

`tests/mock/` holds three fixtures with distinct jobs: `tlds-alpha-by-domain.txt` is a real IANA
file, `bad-tlds.txt` contains only lines that must be rejected (so `fresh()` throws), and
`filter-test.txt` mixes valid and invalid lines to pin exactly which survive.

### `failOnDeprecation` behaves three different ways here

`phpunit.xml` fails on risky, warning, **deprecation** and notice. Setting the flag is not the same
as it working — **probe with `trigger_error(..., E_USER_DEPRECATED)` and read the exit status, not
the summary**, since a run that correctly fails this way still prints `OK, but there were issues!`
in yellow.

| where the deprecation is | behaviour |
|---|---|
| plain PHPUnit tests | caught — no application boots, so PHPUnit keeps its error handler |
| Testbench tests | caught **only** because of `withoutDeprecationHandling()` in `UnitTestCase::setUp()` |
| the service provider's `register()`/`boot()` | caught **only** by `ProviderRegistrationTest` |

The second row is Laravel's `HandleExceptions` bootstrapper: it calls `set_error_handler()` when the
application boots and then discards deprecations outright while running unit tests. Note the fix
converts them to *errors*, so that row goes red where the others go yellow.

The third is the gap the fix leaves. `withoutDeprecationHandling()` runs on the line after
`parent::setUp()`, and Testbench boots the application *inside* that call — so the provider has
already run under the swallowing handler. `ProviderRegistrationTest` builds an
`Illuminate\Foundation\Application` in the test body; constructed directly it runs no bootstrappers,
so `HandleExceptions` never fires.

**That test earns its place twice over.** The application it builds carries a config repository and a
cache factory and nothing else, so it is also the only assertion that this package's wiring resolves
without the rest of the framework present — which is exactly what the four-component `require` block
claims.

## Releases

`CHANGELOG.md` is hand-maintained, newest first, and updated in its own commit before tagging. It
uses **setext headings** — a title underlined with `=`, then `x.y.z (YYYY-MM-DD)` underlined with
`-`, with `*` bullets under each. Simon does his own pushes and tagging.

The README's *Compatibility* list maps Laravel majors to package majors and is part of the release,
not documentation drift.

**A new dev-only file at the repo root needs an `export-ignore` line in `.gitattributes`**, which is
what keeps it out of the Packagist dist archive. `CLAUDE.md` is on that list already. Verify with
`git archive HEAD | tar t`, and note `export-ignore` only affects tags cut *after* the commit that
adds it.

`composer.lock` is gitignored — this is a library, so every consumer and every CI job resolves fresh.

**`* text=auto eol=lf` in `.gitattributes` is load-bearing on WSL.** Opening this project in an IDE
that writes CRLF once produced a commit-ready diff touching every tracked file with no content
change in it at all. `.editorconfig` covers the same ground for the editor.
