# Changelog

## Unreleased (1.x)

### Changed
- `omnireach/bing-webmaster` is now `omnipulse/bing-webmaster` (2026-11-10: the omnireach family is renamed omnipulse,
  this repository's history kept): the namespace `Omnipulse\BingWebmaster`, requiring `omnisense/omnipulse`;
  `composer.json` replaces `omnireach/bing-webmaster`, so that a project never installs both.


### Fixed
- The API key, which Bing takes in the query string, no longer appears in an
  `UnreachableException`'s message (the fix is omnisense/omnipulse's `JsonApi`; a test here keeps
  it so).
