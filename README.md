<div align="center">

![Logo](docs/assets/header.svg)

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

📦&nbsp;[Packagist](https://packagist.org/packages/cpsit/project-builder) ·
💾&nbsp;[Repository](https://github.com/CPS-IT/project-builder) ·
🐛&nbsp;[Issue tracker](https://github.com/CPS-IT/project-builder/issues)

</div>

The CPS Project Builder is a Composer package that serves as a generic solution to kickstart
new projects in seconds. It comes with a powerful configuration and templating system that
can even be used to create custom project types to meet your own requirements for new projects.

By simply using the Composer command [`create-project`][1] it was never easier to create
new project repositories from command line.

## 🚀 Features

* Kickstarter package for new projects
* Modern configuration and templating system
* Support for templating of external dependencies
* Easily extensible for new project types

## ⚡ Usage

Usage with [Composer][2]:

```bash
composer create-project cpsit/project-builder <projectname>
```

Alternative usage with Docker:

```bash
docker run --rm -it -v <target-dir>:/app cpsit/project-builder
```

Please have a look at [`Usage`](docs/usage.md) for an extended overview.

## 📦 Ready-to-use packages

Currently, the following template packages are available and actively maintained:

* [TYPO3 CMS project template][3] (`cpsit/typo3-project-template`)

You can explore all publicly available template packages on [Packagist][4].

> 💡 If you would like your package to be listed here, feel free to create a
> [pull request][5].

## 🧙 Customized Templates
Learn how to create your own template package in [`Custom project types`](docs/custom-project-types.md).

## 📂 Configuration

The configuration describes how to build a project, e.g. which steps are necessary and what
properties are required when rendering project templates.

Explore configuration details and examples in [`Configuration`](docs/configuration.md).

## 🏗️ Architecture

Please have a look at [`Architecture`](docs/architecture.md) which explains
core concepts and lists all available components.

## 👩‍💻👨‍💻 Contributing

We welcome contributions! 💛 If you're considering to contribute to this project please do have a look
at [`CONTRIBUTING.md`](CONTRIBUTING.md) explaining our attempts to achieve a high quality.

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE).

[1]: https://getcomposer.org/doc/03-cli.md#create-project
[2]: https://getcomposer.org/
[3]: https://github.com/CPS-IT/typo3-project-template
[4]: https://packagist.org/?type=project-builder-template
[5]: https://github.com/CPS-IT/project-builder/pulls
