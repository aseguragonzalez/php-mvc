# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-04-25

### Added

- MVC web layer: `Controller`, `RequestHandler`, `Router`, middleware pipeline
- DDD building blocks: `BackgroundTasks`, `Migrations`, `Security` (sign-up, sign-in, password reset, sessions)
- CLI scaffolding tool (`bin/mvc`) with commands for apps, migrations, background tasks and authentication
- PSR-7 / PSR-15 compliant request/response handling (PSR interfaces only; bring your own implementation)
- PSR-11 dependency injection container via `MutableContainerInterface`
- File utilities, view engine settings, language settings
- PHP 8.3 and 8.4 support

[Unreleased]: https://github.com/aseguragonzalez/php-mvc/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/aseguragonzalez/php-mvc/releases/tag/v0.1.0
