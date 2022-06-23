# Usage

There are two ways this project can be used:

* [Usage with Composer](#usage-with-composer) is the preferred way. It
  requires a global Composer installation.
* [Usage with Docker](#usage-with-docker) is an alternative way. The only
  requirement with this method is a local Docker installation.

## Usage with Composer

### Requirements

* Composer >= 2.1
* PHP >= 7.4

### Basic usage

This project should always be used together with the [`create-project`][1]
Composer command:

```bash
composer create-project cpsit/project-builder <projectname>
```

Replace `<projectname>` with the actual name of your new project. This will
be the folder name where to install and set up your new project.

:bulb: For more command options, refer to the documentation of the
[`create-project`][1] command.

### Recommended usage

We recommend using the command like follows:

```bash
composer create-project cpsit/project-builder \
  --prefer-dist \
  --no-dev \
  <projectname>
```

This implies usage of the following options:

* **`--prefer-dist`** ensures that only distributed files are installed,
  skipping files that are only relevant for development of this repository
* **`--no-dev`** speeds up the installation process by skipping
  dev-requirements (those are only required for development purposes)

:bulb: Tip: Add the `-v` (or `--verbose`) command option to get a verbose
output of processing steps.

## Usage with Docker

### Requirements

* Docker

### Basic usage

As an alternative to the usage with Composer, there's also a ready-to-use
[Docker image][2]:

```bash
docker run --rm -it -v <target-dir>:/app cpsit/project-builder
```

Replace `<target-dir>` with an absolute or relative path to the directory
where to install and set up your new project. Make sure to always mount
the volume to `/app`.

:bulb: In the entrypoint, `composer create-project` is executed. It already
contains all [recommended command options](#recommended-usage).

### Available image tags

The following image tags are currently available:

| Tag name    | Description                                   |
|-------------|-----------------------------------------------|
| `<version>` | The appropriate project version, e.g. `0.1.0` |
| `latest`    | The latest project version                    |

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
[2]: https://hub.docker.com/r/cpsit/project-builder
