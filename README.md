# Scabbia2 PHP Framework

[Scabbia2][scabbia-repositories-url] is a set of open source PHP components. And [this repository][scabbia-scabbiafw-repository-url] contains a framework forged with Scabbia2 components.

[![Build Status][scabbia-scabbiafw-travis-image]][scabbia-scabbiafw-travis-url]
[![Scrutinizer Code Quality][scabbia-scabbiafw-scrutinizer-image]][scabbia-scabbiafw-scrutinizer-url]
[![Total Downloads][scabbia-scabbiafw-downloads-image]][scabbia-scabbiafw-scrutinizer-url]
[![Latest Stable Version][scabbia-scabbiafw-stable-image]][scabbia-scabbiafw-stable-url]
[![Latest Unstable Version][scabbia-scabbiafw-unstable-image]][scabbia-scabbiafw-unstable-url]
[![Documentation Status][scabbia-scabbiafw-documentation-image]][scabbia-scabbiafw-documentation-url]

## History

This project derived from [Scabbia PHP Framework (1.x branch)][scabbia-1x-url] with taken advantage of modern software tools. Since we don't have active GitHub users and online continous integration tools for free when we started in 2008, we think it's time to create a new branch of the existing framework idea/brand for a reset.

1.x versions had been under development by [Eser Ozvataf][eserozvataf-homepage-url] for 2 years and reached version 1.5 on stable branch. You can take a look to the repository of [Scabbia 1.x][scabbia-1x-url]. It's active development is frozen but small bugfixes will be available in time.


## Installation
Please make sure that you can access php command line tool via `php` command. Further commands will be executed on Terminal or Command Prompt:

**Step 1:**
Download and install composer dependency manager.

``` bash
php -r "readfile('https://getcomposer.org/installer');" | php
```

**Step 2:**
Create a new scabbia2-fw project under the directory named `project`.

``` bash
php composer.phar create-project eserozvataf/scabbia2-fw:dev-master project
```

**Step 3:**
Make `project/var` directory writable.

``` bash
cd project
chmod 0777 -R var
```


## Requirements
* PHP 5.6.0+ (http://www.php.net/)
* Composer Dependency Manager (http://getcomposer.org/)


## Links
- [List of All Scabbia2 Components][scabbia-repositories-url]
- [Documentation][scabbia-scabbiafw-documentation-url]
- [Twitter][eserozvataf-twitter-url]
- [Contributor List][scabbia-scabbiafw-contributors-url]
- [License Information][scabbia-scabbiafw-license-url]


## Contributing
It is publicly open for any contribution. Bugfixes, new features and extra modules are welcome. All contributions should be filed on the [eserozvataf/scabbia2-fw][scabbia-scabbiafw-repository-url] repository.

* To contribute to code: Fork the repo, push your changes to your fork, and submit a pull request.
* To report a bug: If something does not work, please report it using GitHub issues.
* To support, donate the current lead maintainer: [![Donate][eserozvataf-gratipay-image]][eserozvataf-gratipay-url]

[scabbia-repositories-url]: https://github.com/eserozvataf/scabbia2
[scabbia-scabbiafw-contributors-url]: contributors.md
[scabbia-scabbiafw-license-url]: LICENSE
[scabbia-scabbiafw-repository-url]: https://github.com/eserozvataf/scabbia2-fw
[scabbia-scabbiafw-travis-image]: https://travis-ci.org/eserozvataf/scabbia2-fw.png?branch=master
[scabbia-scabbiafw-travis-url]: https://travis-ci.org/eserozvataf/scabbia2-fw
[scabbia-scabbiafw-scrutinizer-image]: https://scrutinizer-ci.com/g/eserozvataf/scabbia2-fw/badges/quality-score.png?b=master
[scabbia-scabbiafw-scrutinizer-url]: https://scrutinizer-ci.com/g/eserozvataf/scabbia2-fw/?branch=master
[scabbia-scabbiafw-downloads-image]: https://poser.pugx.org/eserozvataf/scabbia2-fw/downloads.png
[scabbia-scabbiafw-downloads-url]: https://packagist.org/packages/eserozvataf/scabbia2-fw
[scabbia-scabbiafw-stable-image]: https://poser.pugx.org/eserozvataf/scabbia2-fw/v/stable
[scabbia-scabbiafw-stable-url]: https://packagist.org/packages/eserozvataf/scabbia2-fw
[scabbia-scabbiafw-unstable-image]: https://poser.pugx.org/eserozvataf/scabbia2-fw/v/unstable
[scabbia-scabbiafw-unstable-url]: https://packagist.org/packages/eserozvataf/scabbia2-fw
[scabbia-scabbiafw-documentation-image]: https://readthedocs.org/projects/scabbia2-documentation/badge/?version=latest
[scabbia-scabbiafw-documentation-url]: https://readthedocs.org/projects/scabbia2-documentation
[scabbia-1x-url]: https://github.com/eserozvataf/scabbia1
[eserozvataf-homepage-url]: http://eser.ozvataf.com/
[eserozvataf-gratipay-image]: https://img.shields.io/gratipay/eserozvataf.svg
[eserozvataf-gratipay-url]: https://gratipay.com/eserozvataf/
[eserozvataf-twitter-url]: https://twitter.com/eserozvataf
