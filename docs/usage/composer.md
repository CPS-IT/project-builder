# Composer

```{button-link} https://packagist.org/packages/cpsit/project-builder
:color: primary
:outline:

{octicon}`link-external;1em;sd-mr-1` View package on Packagist
```

## Requirements

* [Composer][1] >= 2.1
* [PHP][2] >= 8.1

## Basic usage

This project should always be used together with the [`create-project`][3]
Composer command:

```bash
composer create-project cpsit/project-builder <projectname>
```

Replace `<projectname>` with the actual name of your new project. This will
be the folder name where to install and set up your project.

```{seealso}
For more command options, refer to the documentation of the
[`create-project`][3] command.
```

## Recommended usage

We recommend using the command like follows:

```bash
composer create-project cpsit/project-builder \
  --prefer-dist \
  --no-dev \
  <projectname>
```

This implies usage of the following options:

* **`--prefer-dist`** ensures that only distributed files are installed
  skipping files that are only relevant for development of this repository
* **`--no-dev`** speeds up the installation process by skipping
  dev-requirements (those are only required for development purposes)

```{tip}
Add the `-v` (or `--verbose`) command option to get a verbose
output of processing steps.
```

[1]: https://getcomposer.org/
[2]: https://www.php.net/
[3]: https://getcomposer.org/doc/03-cli.md#create-project
