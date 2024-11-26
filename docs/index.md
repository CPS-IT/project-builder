---
hide-toc: true
og:description: A Composer package for creating new projects based on various specific project
                templates. Start now by running 'composer create-project cpsit/project-builder'.
---

# Project Builder

[![Latest Stable Version](https://img.shields.io/packagist/v/cpsit/project-builder?label=version)][1]
[![Packagist Downloads](https://img.shields.io/packagist/dt/cpsit/project-builder?label=packagist+downloads&color=brightgreen)][2]
[![GHCR Pulls](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fipitio.github.io%2Fbackage%2FCPS-IT%2Fproject-builder%2Fproject-builder.json&query=%24.downloads&label=GHCR%20pulls&color=brightgreen)][3]
[![Docker Pulls](https://img.shields.io/docker/pulls/cpsit/project-builder?label=docker+pulls&color=brightgreen)][4]

A Composer package to **create new projects** based on various, specific **project templates**.
All project templates are distributed as separate Composer packages.

It comes with a powerful **configuration and templating system** that allows to develop
new project templates in a very flexible way.

![Screenshot](_static/img/header.png)

## ⚡ Quickstart

```bash
composer create-project cpsit/project-builder <projectname>
```

Read more at [Getting started](getting-started.md).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](contributing/license.md).

```{toctree}
:hidden:

getting-started
```

```{toctree}
:hidden:
:caption: Usage

usage/composer
usage/docker
```

```{toctree}
:hidden:
:caption: Template development

development/architecture/index
development/configuration
development/build-steps
development/dependency-injection
```

```{toctree}
:hidden:
:caption: Contributing

contributing/workflow
contributing/license
```

[1]: https://github.com/CPS-IT/project-builder/releases/latest
[2]: https://packagist.org/packages/cpsit/project-builder
[3]: https://github.com/CPS-IT/project-builder/pkgs/container/project-builder
[4]: https://hub.docker.com/r/cpsit/project-builder
