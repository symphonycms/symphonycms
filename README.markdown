# Symphony 2

[![Join the chat at https://gitter.im/symphonycms/symphony-2](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/symphonycms/symphony-2?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg?style=flat-square)](https://php.net/)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/symphonycms/symphony-2/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/symphonycms/symphony-2/?branch=master)
[![Build Status](https://travis-ci.org/symphonycms/symphony-2.svg?branch=master)](https://travis-ci.org/symphonycms/symphony-2)
![Build status](https://ci.appveyor.com/api/projects/status/1mx5r9befuode1e9?svg=true)
[![Code coverage](https://codecov.io/gh/symphonycms/symphony-2/branch/master/graph/badge.svg)](https://codecov.io/gh/symphonycms/symphony-2)

- Version: 2.7.10
- Date: 8th April 2019
- [Release notes](https://www.getsymphony.com/download/releases/version/2.7.10/)
- [Github repository](https://github.com/symphonycms/symphony-2/tree/2.7.10)
- [MIT Licence](https://github.com/symphonycms/symphony-2/blob/master/LICENCE)

## Contents

* [Overview](#overview)
* [Server requirements](#server-requirements)
* [Responsible Security Disclosure](#responsible-security-disclosure)

## Quick links

* [Installing](.docs/dev/INSTALLING.md)
* [Updating from LTS](.docs/dev/UPDATING.md)
* [Contributing](.docs/dev/CONTRIBUTING.md)
* [Documentation TOC](.docs/TOC.md)

## Overview

Symphony is a `PHP` & `MySQL` based CMS that utilises `XML` and `XSLT` as its core technologies. This repository represents version `2.7.10` and is considered stable.

Useful places:

- [The Symphony website](https://www.getsymphony.com/)
- [The Symphony forum](https://www.getsymphony.com/discuss/)
- [Symphony Extensions](http://symphonyextensions.com/)
- [Contributing to Symphony](https://github.com/symphonycms/symphony-2/wiki/Contributing-to-Symphony)

## Server requirements

- PHP 5.6 or 7.0-7.3
- PHP’s LibXML module, with the XSLT extension enabled (`--with-xsl`)
- MySQL 5.7 or above is recommended
- A webserver (known to be used with Apache, Litespeed, Nginx and Hiawatha)
- Apache’s `mod_rewrite` module or equivalent
- PHP’s built in `json` functions, which are enabled by default in PHP 5.2 and above; if they are missing, ensure PHP wasn’t compiled with `--disable-json`
- PHP’s `zlib` module
- PHP’s `pdo_mysql` module

## Responsible Security Disclosure

Please follow [the guideline for security bug disclosure](https://github.com/symphonycms/symphony-2/wiki/Security-Bug-Disclosure).
