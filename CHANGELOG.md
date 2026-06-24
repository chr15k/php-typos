# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [v0.2.2] - 2026-06-24

### Added

- Add missing flags to README (--words, --identifers, --files)

## [v0.2.1] - 2026-06-24

### Changed

- Code cleanup
- Nicer outputs

## [v0.2.0] - 2026-06-23

### Added

- Symfony `^7.4|^8.0` compatibility for use in Laravel 12+ projects

## [v0.1.0] - 2026-06-23

### Added

- Initial release
- Automatic platform binary resolution for macOS (x86_64, ARM64), Linux (x86_64, ARM64), and Windows (x86_64)
- `--init` flag to scaffold a `_typos.toml` configuration file in the project root
- `--write` flag to automatically correct typos in place
- `--diff` flag to preview proposed corrections as a unified diff
- `--format` flag with `long`, `brief`, and `json` output modes
- `--config` flag to specify a custom configuration file path
- Multi-path argument support for targeted scanning