<div align="center">

![Logo](docs/_static/img/header.svg)

# Project Builder

[![Coverage](https://img.shields.io/coverallsCoverage/github/CPS-IT/project-builder?logo=coveralls)](https://coveralls.io/github/CPS-IT/project-builder)
[![CGL](https://img.shields.io/github/actions/workflow/status/CPS-IT/project-builder/cgl.yaml?label=cgl&logo=github)](https://github.com/CPS-IT/project-builder/actions/workflows/cgl.yaml)
[![Docker deploy](https://img.shields.io/github/actions/workflow/status/CPS-IT/project-builder/docker.yaml?label=docker&logo=github)](https://github.com/CPS-IT/project-builder/actions/workflows/docker.yaml)
[![Tests](https://img.shields.io/github/actions/workflow/status/CPS-IT/project-builder/tests.yaml?label=tests&logo=github)](https://github.com/CPS-IT/project-builder/actions/workflows/tests.yaml)
[![Supported PHP Versions](https://img.shields.io/packagist/dependency-v/cpsit/project-builder/php?logo=php)](https://packagist.org/packages/cpsit/project-builder)

📙&nbsp;[Documentation](https://project-builder.cps-it.de/) |
📦&nbsp;[Packagist](https://packagist.org/packages/cpsit/project-builder) |
💾&nbsp;[Repository](https://github.com/CPS-IT/project-builder) |
🐛&nbsp;[Issue tracker](https://github.com/CPS-IT/project-builder/issues)

</div>

A Composer package used to **create new projects** based on various **project templates**.
All project templates are distributed as separate Composer packages. It comes with a
powerful configuration and templating system that allows to develop new project templates
in a very flexible way.

➡️ Read more in the [official documentation][1].

## ⚡ Quickstart

### Composer

[![Packagist](https://img.shields.io/packagist/v/cpsit/project-builder?label=version&logo=packagist)](https://packagist.org/packages/cpsit/project-builder)
[![Packagist Downloads](https://img.shields.io/packagist/dt/cpsit/project-builder?color=brightgreen)](https://packagist.org/packages/cpsit/project-builder)

```bash
composer create-project cpsit/project-builder <projectname>
```

### Docker

[![Docker](https://img.shields.io/docker/v/cpsit/project-builder?label=version&logo=docker&sort=semver)](https://hub.docker.com/r/cpsit/project-builder)
[![GHCR Pulls](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fipitio.github.io%2Fbackage%2FCPS-IT%2Fproject-builder%2Fproject-builder.json&query=%24.downloads&label=GHCR%20pulls&color=brightgreen)](https://github.com/CPS-IT/project-builder/pkgs/container/project-builder)
[![Docker Pulls](https://img.shields.io/docker/pulls/cpsit/project-builder?color=brightgreen)](https://hub.docker.com/r/cpsit/project-builder)

```bash
docker run --rm -it -v <target-dir>:/app cpsit/project-builder
```

You can also use the image from [GitHub Container Registry][2]:

```bash
docker run --rm -it -v <target-dir>:/app ghcr.io/cps-it/project-builder
```

## 📙 Documentation

Please have a look at the [official documentation][1].

## 🧑‍💻 Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## ⭐ License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE).

[1]: https://project-builder.cps-it.de/
[2]: https://github.com/CPS-IT/project-builder/pkgs/container/project-builder
