# Changelog

All notable changes to Lock will be documented in this file. This file follows the *[Keep a CHANGELOG](http://keepachangelog.com/)* standards.

## [Unreleased]
- Added all method, this feature returns the permissions collection for a caller.

## 0.2.0 - 2015-09-16

### Added

- Added the ability to clear permissions

### Changed

- Switched from Scrutinizer to Code Climate for analysis

## 0.1.0 - 2014-12-17

### Changed

- Renamed the `createFromArray` method on the `PermissionFactory` to `createFromData`

### Fixed

- Fixed a bug where only array data could be passed to the `PermissionFactory`
- Fixed a bug where a action alias got denied but was still accepted

## 0.1.0-alpha.2 - 2014-12-05

### Added

- Scrutinizer config for code quality and code coverage
- Changelog
- Contributers file
- `.gitattributes` file
- Added section to readme about testing your driver
- The `Manager` class now has methods to set the lock instance for objects which implement the `LockAware` trait
- `Lock` instances can now call a list of resource ids to which they are allowed or denied to perform the given action on

### Changed

- Massive refactor of the `Lock` and `Manager` classes
- Moved interfaces to their own namespaces
- Refactored most of the testing suite
- Renamed the `AbstractPermission` class
- ArrayDriverTest moved to the `Drivers` namespace
- Split the `LockTestCase` into `PersistentDriverTestCase` and `StaticDriverTestCase`
- The `assert` method of conditions now get more parameters
- Conditions can now be used as a callback

### Removed

- Git ignored the `composer.lock` file

### Fixed

- Require `PHP >=5.4` in the `composer.json` file
- Various documentation fixes in `readme.md`

## 0.1.0-alpha.1 - 2014-11-21

First public alpha release.
