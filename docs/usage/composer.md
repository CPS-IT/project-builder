# Composer

```{button-link} https://packagist.org/packages/cpsit/project-builder
:color: primary
:outline:

{octicon}`link-external;1em;sd-mr-1` View package on Packagist
```

## Requirements

* [Composer][1] >= 2.1
* [PHP][2] >= 8.1

## Global usage

For a quick start, the project can be used together with Composer's
built-in [`create-project`][3] command:

```bash
composer create-project cpsit/project-builder --no-dev <projectname>
```

Replace `<projectname>` with the actual name of your new project. This will
be the folder name where to install and set up your project.

```{tip}
Add the `-v` (or `--verbose`) command option to get a verbose
output of processing steps.
```

## Project-level usage

The project can also be integrated into an existing project as a Composer
plugin:

```bash
composer require --dev cpsit/project-builder
```

Once installed, an additional Composer command `project:create` is available:

```bash
composer project:create <target-directory> [-f|--force] [--no-cache]
```

The following command parameters are available:

**`target-directory`**
: Path to a directory where to create the new project

**`-f`, `--force`**
: Force project creation even if target directory is not empty

**`--no-cache`**
: Disable template source cache during package listing

[1]: https://getcomposer.org/
[2]: https://www.php.net/
[3]: https://getcomposer.org/doc/03-cli.md#create-project
