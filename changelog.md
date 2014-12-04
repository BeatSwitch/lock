# Changelog

All notable changes to Lock will be documented in this file. This file follows the *[Keep a CHANGELOG](http://keepachangelog.com/)* standards.

## Unreleased

### Added

- Scrutinizer config for code quality and code coverage
- Changelog
- Contributers file
- `.gitattributes` file
- Added section to readme about testing your driver
- The `Manager` class now has methods to set the lock instance for objects which implement the `LockAware` trait

### Changed

- Massive refactor of the `Lock` and `Manager` classes
- Moved interfaces to their own namespaces
- Refactored most of the testing suite
- Renamed the `AbstractPermission` class
- ArrayDriverTest moved to the `Drivers` namespace
- Split the `LockTestCase` into `PersistentDriverTestCase` and `StaticDriverTestCase`

### Removed

- Git ignored the `composer.lock` file

### Fixed

- Require `PHP >=5.4` in the `composer.json` file
- Various documentation fixes in `readme.md`

## 1.0.0-alpha.1 - 2014-11-21

First public alpha release.
