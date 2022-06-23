<div align="center">

![Logo](docs/assets/header.png)

# CPS Project Builder

[![Coverage](https://codecov.io/gh/CPS-IT/Project-Builder/branch/develop/graph/badge.svg?token=u5Clk9nd9Q)](https://codecov.io/gh/CPS-IT/Project-Builder)
[![Maintainability](https://api.codeclimate.com/v1/badges/a84923d4d61c50561186/maintainability)](https://codeclimate.com/github/CPS-IT/project-builder/maintainability)
[![Tests](https://github.com/CPS-IT/project-builder/actions/workflows/tests.yaml/badge.svg)](https://github.com/CPS-IT/project-builder/actions/workflows/tests.yaml)
[![CGL](https://github.com/CPS-IT/project-builder/actions/workflows/cgl.yaml/badge.svg)](https://github.com/CPS-IT/project-builder/actions/workflows/cgl.yaml)
[![Docker deploy](https://github.com/CPS-IT/project-builder/actions/workflows/docker.yaml/badge.svg)](https://github.com/CPS-IT/project-builder/actions/workflows/docker.yaml)
[![Latest Stable Version](http://poser.pugx.org/cpsit/project-builder/v)](https://packagist.org/packages/cpsit/project-builder)
[![Total Downloads](http://poser.pugx.org/cpsit/project-builder/downloads)](https://packagist.org/packages/cpsit/project-builder)
[![Docker](https://img.shields.io/docker/v/cpsit/project-builder?label=docker&sort=semver)](https://hub.docker.com/r/cpsit/project-builder)
[![License](http://poser.pugx.org/cpsit/project-builder/license)](LICENSE)

:package:&nbsp;[Packagist](https://packagist.org/packages/cpsit/project-builder) |
:floppy_disk:&nbsp;[Repository](https://github.com/CPS-IT/project-builder) |
:bug:&nbsp;[Issue tracker](https://github.com/CPS-IT/project-builder/issues)

</div>

A template repository with templates for various project types developed at
[coding. powerful. systems. CPS GmbH][1]. The project comes with a powerful
configuration and templating system that allows to kickstart new project in
seconds. By using the Composer command [`create-project`][2], it was never
easier to create new project repositories from command line.

## :rocket: Features

* Kickstarter package for new projects
* Modern configuration and templating system
* Support for templating of external dependencies
* Easy extensible for new project types

## :zap: Usage

Usage with [Composer][3]:

```bash
composer create-project cpsit/project-builder <projectname>
```

Alternative usage with Docker:

```bash
docker run --rm -it -v <target-dir>:/app cpsit/project-builder
```

Please have a look at [`Usage`](docs/usage.md) for an extended overview.

## :open_file_folder: Configuration

Please have a look at [`Configuration`](docs/configuration.md).

## :roller_coaster: Architecture

Please have a look at [`Architecture`](docs/architecture.md) which explains
core concepts and lists all available components.

## :technologist: Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## :star: License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE).

[1]: https://www.cps-it.de
[2]: https://getcomposer.org/doc/03-cli.md#create-project
[3]: https://getcomposer.org/
