[![Build Status](https://img.shields.io/travis/hiromi2424/cakephp-changelog/master.svg?style=flat-square)](https://travis-ci.org/hiromi2424/cakephp-changelog)
[![Coverage Status](https://img.shields.io/codecov/c/github/hiromi2424/cakephp-changelog.svg?style=flat-square)](https://codecov.io/github/hiromi2424/cakephp-changelog)
[![Total Downloads](https://img.shields.io/packagist/dt/hiromi2424/cakephp-changelog.svg?style=flat-square)](https://packagist.org/packages/hiromi2424/cakephp-changelog)
[![Latest Stable Version](https://img.shields.io/packagist/v/hiromi2424/cakephp-changelog.svg?style=flat-square)](https://packagist.org/packages/hiromi2424/cakephp-changelog)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/hiromi2424/cakephp-changelog.svg)](https://scrutinizer-ci.com/g/hiromi2424/cakephp-changelog/)

## What is this?

This is CakePHP plugin to provide saving changelogs for database records.

## Installation

```
composer require hiromi2424/cakephp-changelog
```

## Requirements

* CakePHP 3.x
* PHP 5.5+

## Usage

### Configuration

This provides `Changelog` Behavior.

    `$this->loadBehavior('Changelog', [
        // ... your options
    ])`

- TODO: define options

