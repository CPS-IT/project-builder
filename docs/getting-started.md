---
hide-toc: true
---

# Getting started

## 🚀 Installation methods

There are two ways this project can be used. Please select one of the
following installation methods:

:::{card}
:class-card: sd-bg-primary sd-text-white

```{image} _static/img/logos/composer.png
:width: 80
:align: right
```

**Composer (recommended)**

This is the most convenient and best performing way.

It requires Composer and PHP to be installed on your computer. If you
don't have them installed, you can still use the alternative method with
Docker.

```{button-ref} usage/composer
:ref-type: myst
:color: light
:expand:
:click-parent:

Start using Composer
```
:::

:::{card}

```{image} _static/img/logos/docker.png
:width: 80
:align: right
:class: only-light
```

```{image} _static/img/logos/docker-white.png
:width: 80
:align: right
:class: only-dark
```

**Docker**

Use this method if you cannot meet all requirements of the recommended way
using Composer. This might be the case if you don't have Composer or PHP
installed or if any of the installed versions are outdated.

The only requirement with this method is a local Docker installation.

```{button-ref} usage/docker
:ref-type: myst
:color: primary
:outline:
:expand:
:click-parent:

Start using Docker
```
:::

## 📦 Project templates

Every project is created from a project template. Templates are distributed
as Composer packages. They can be published in various ways:

* Public Composer package on [Packagist][1]
* Composer package on other Composer registries, e.g. self-hosted [Satis][2]
* VCS repository, e.g. GitHub or GitLab

Every package contains a various set of project template files. During project
creation, these templates are filled with information from the generation process.

### Available packages

The following public project templates are currently available:

:::{card}
:link: https://github.com/CPS-IT/typo3-project-template

```{image} _static/img/logos/typo3.svg
:width: 100
:align: right
:class: only-light
```

```{image} _static/img/logos/typo3-white.svg
:width: 100
:align: right
:class: only-dark
```

**TYPO3 CMS project**

A complete template for new TYPO3 CMS projects, including a ready-to-use
DDEV configuration and basic configuration for deployment with Deployer.

_Package name: `cpsit/typo3-project-template`_
:::

```{seealso}
Explore all publicly available project templates on [Packagist][1].
```

```{tip}
If you want your project template to be listed here, feel free to submit a
[pull request][3].
```

[1]: https://packagist.org/?type=project-builder-template
[2]: https://github.com/composer/satis
[3]: https://github.com/CPS-IT/project-builder/pulls
