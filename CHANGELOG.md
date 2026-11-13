# Changelog

## [Unreleased]

## [1.0.0] - 2026-11-13

The first stable version. It requires `omnisense/contracts` `^1.0`, `omnisense/omnipulse` `^1.0` with no stability flag, so `composer require omnipulse/bing-webmaster` installs it in a project with Composer's default minimum-stability; the 1.x series follows semantic versioning. A tag `vX.Y.Z` is released by `.github/workflows/release.yml` once the package's CI passes on it.

### Added
- Bing Webmaster Tools's usage policy declared as data: `policy.json` (omnisense/contracts' schema), its terms
  read on 2026-11-10, named in `composer.json` (`extra.omnisense.policy`, and
  `omnisense/contracts` required). The provider implements `DeclaresUsagePolicyInterface` and gives
  the policy to its Capabilities; the factory hands the provider's Backoff to its HTTP calls, so
  that a 429 and its `Retry-After` hold every next call. `Tests/PolicyTest.php`.

### Changed
- `omnireach/bing-webmaster` is now `omnipulse/bing-webmaster` (2026-11-10: the omnireach family is renamed omnipulse,
  this repository's history kept): the namespace `Omnipulse\BingWebmaster`, requiring `omnisense/omnipulse`;
  `composer.json` replaces `omnireach/bing-webmaster`, so that a project never installs both.

### Fixed
- The API key, which Bing takes in the query string, no longer appears in an
  `UnreachableException`'s message (the fix is omnisense/omnipulse's `JsonApi`; a test here keeps
  it so).
