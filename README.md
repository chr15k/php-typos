# Typos for PHP

A blistering fast, zero-dependency source code spellchecker for PHP and Laravel projects.

This package serves as a seamless PHP wrapper **powered by [typos](https://github.com/crate-ci/typos) Rust CLI**. It automatically detects your operating system and architecture, provisions the correct pre-compiled binary, and hooks it directly into your Composer environment with zero manual setup.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/chr15k/typos.svg?style=flat-square)](https://packagist.org/packages/chr15k/typos)
[![Total Downloads](https://img.shields.io/packagist/dt/chr15k/typos.svg?style=flat-square)](https://packagist.org/packages/chr15k/typos)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

---

## Why this package?

The underlying `typos` CLI is arguably the fastest source code spellchecker available, but managing global binary installations across diverse developer teams and headless CI environments can be a headache.

This package changes that by turning spellchecking into a project-wide standard:
* **Zero Dependencies:** No need to install Rust, Cargo, Homebrew, or global binaries.
* **Team Alignment:** One configuration file locks the rules for everyone on the team.
* **CI/CD Ready:** Works out of the box on GitHub Actions, GitLab CI, or any automated runner via standard Composer caching.

## Installation

You can install the package via Composer as a development dependency:

```bash
composer require chr15k/typos --dev