# Usage

## Requirements

* Composer >= 2.1
* PHP >= 7.4

## Basic usage

This project should always be used together with the [`create-project`][1]
Composer command:

```bash
composer create-project cpsit/project-builder <projectname>
```

Replace `<projectname>` with the actual name of your new project. This will
be the folder name where to install and set up your new project.

:bulb: For more command options, refer to the documentation of the
[`create-project`][1] command.

## Recommended usage

We recommend using the command like follows:

```bash
composer create-project cpsit/project-builder \
  --prefer-dist \
  --no-dev \
  --remove-vcs \
  <projectname>
```

This implies usage of the following options:

* **`--prefer-dist`** ensures that only distributed files are installed,
  skipping files that are only relevant for development of this repository
* **`--no-dev`** speeds up the installation process by skipping
  dev-requirements (those are only required for development purposes)
* **`--remove-vcs`** removes potentially generated VCS directories. _You
  can also skip this option and decide on your own how to handle VCS
  directories._

:bulb: Tip: Add the `-v` (or `--verbose`) command option to get a verbose
output of processing steps.

## Next steps

The installation script will guide you through all necessary project
build steps. Once done, you should validate the generated project files
and make additional adaptions, if necessary.

Switch to the project directory and install Composer dependencies:

```bash
cd <projectname>
composer install
```

Now open the project in your favorite IDE, for example:

```bash
# PhpStorm
pstorm .

# VSCode
code .
```

:bulb: Normally, each project builder will show you the next steps
after successful project creation. Those steps highly depend on the
project type. Read more at [Processing build steps#Show next steps](processing-build-steps.md#show-next-steps).

[1]: https://getcomposer.org/doc/03-cli.md#create-project
