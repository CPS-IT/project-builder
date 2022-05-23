# Configuration

This project provides a generic way to create a various set of project
types. For each project type, an appropriate build configuration exists.
The configuration describes how to build a project, e.g. which steps are
necessary and what properties are required when rendering project
templates.

## Structure

Project templates are stored in the [`templates`](../templates) directory
of this repository. For each project type, a subdirectory with the project
identifier exists which contains all necessary config and source files.

Imagine the following file structure:

```
templates
└── my-fancy-project
    ├── composer.json
    ├── config.yml
    ├── shared
    │   ├── ...
    │   └── .gitlab-ci.yml
    └── src
        ├── ...
        └── composer.json.twig
```

In this example, the project type `my-fancy-project` is configured. It
contains the following files and directories:

* **`composer.json`** (optional) defines additional template dependencies.
  Those are installed by the build step `installComposerDependencies`.
  Read more at [`Processing build steps#Install Composer dependencies`](processing-build-steps.md#install-composer-dependencies).
* **`config.yml`** is the main configuration file. It contains all
  instructions on how to build new projects of this project type. Read more
  at [`Config file`](#config-file).
* **`shared`** (optional) should contain shared source files. Those are
  normally created when installing Composer dependencies defined by
  `composer.json`. Read more at [`Shared source files`](#shared-source-files).
* **`src`** (optional) contains all project source files. Those can be either
  generic files to be copied to the generated project or Twig template files.
  Twig files are processed before copying them to the generated project. Read
  more at [`Source files`](#source-files).

## Config file

Each project type requires a configuration file. It describes how to build
a new project of this type and is located in the template directory of the
associated project type.

The following filename variants are supported:

1. `config.yml`
2. `config.yaml`
3. `config.json`

:bulb: See [`ConfigReader::FILE_VARIANTS`](../src/Builder/Config/ConfigReader.php)
for an overview of supported filenames.

### Structure

Each config file should at least contain the following properties:

* **`identifier`** describes the project type. It is used internally to handle
  project generation while processing the required build steps.
* **`name`** is kind of a label for the configured project type. It is mainly
  used for communication with the user, keeping the actual project type internal.
* **`steps`** defines a list of necessary build steps. Those steps are processed
  once a new project of the associated project type is generated. Read more at
  [`Processing build steps`](processing-build-steps.md).

Usually, it is also necessary to collect some more information from the user, e.g.
to be able to prepare template files such as `README.md.twig` or `composer.json.twig`.
For this, a set of `properties` can be defined. Those properties are then used to
collect information in form of build instructions from the user. Read more at
[`Processing build steps#Collect build instructions`](processing-build-steps.md#collect-build-instructions).

Example:

```yaml
identifier: my-fancy-project
name: My fancy project

steps:
  - type: installComposerDependencies
  - type: collectBuildInstructions
  - type: processSourceFiles
  - type: processSharedSourceFiles
    options:
      fileConditions:
        - path: composer.json
          if: 'false'
  - type: mirrorProcessedFiles
  - type: showNextSteps
    options:
      templateFile: templates/next-steps.html.twig

properties:
  # Project
  - identifier: project
    name: Project
    properties:
      - identifier: customer_name
        name: Customer name
        validators:
          - type: notEmpty
      - identifier: project_name
        name: Project name
        defaultValue: basic
        validators:
          - type: notEmpty

  # Author
  - identifier: author
    name: About you
    properties:
      - identifier: name
        name: Your name
        validators:
          - type: notEmpty
      - identifier: email
        name: Your e-mail address
        validators:
          - type: notEmpty
          - type: email
```

### Mapping and hydration

Config files are located by the [`ConfigReader`](../src/Builder/Config/ConfigReader.php)
and parsed by the internal [`ConfigFactory`](../src/Builder/Config/ConfigFactory.php).
With the help of the fantastic external library [`cuyz/valinor`][1], the parsed
config file is mapped to an object structure of value objects. The final configuration
ends up in an instance of [`Builder\Config\Config`](../src/Builder/Config/Config.php):

```php
$configReader = \CPSIT\ProjectBuilder\Builder\Config\ConfigReader::create();
$config = $configReader->readConfig('my-fancy-project');

echo $config->getIdentifier(); // my-fancy-project
echo $config->getName(); // My fancy project
```

Each configured property in the config file is now accessible from the
`Config` object:

| Property     | Accessor                   | Type                                                                                          |
|--------------|----------------------------|-----------------------------------------------------------------------------------------------|
| `identifier` | `$config->getIdentifier()` | `string`                                                                                      |
| `name`       | `$config->getName()`       | `string`                                                                                      |
| `steps`      | `$config->getSteps()`      | [`list<Builder\Config\ValueObject\Step>`](../src/Builder/Config/ValueObject/Step.php)         |
| `properties` | `$config->getProperties()` | [`list<Builder\Config\ValueObject\Property>`](../src/Builder/Config/ValueObject/Property.php) |

:bulb: All hydrated value objects can be found at
[`Builder\Config\ValueObject`](../src/Builder/Config/ValueObject).

### Validation

Config files are validated against a JSON schema. The schema file is located
at [`resources/config.schema.json`](../resources/config.schema.json). Schema
validation is handled by [`ConfigFactory::isValidConfig()`](../src/Builder/Config/ConfigFactory.php)
with the help of the great external library [`justinrainbow/json-schema`][2].

:warning: If a config file does not match the required schema, project generation
will fail immediately.

## Source files

Each project type may provide several source files. They must be stored in a
folder `src` within the project type's template folder.

Currently, the following file variants are supported:

* **Generic files** can be any files other than Twig files. They will be copied
  as-is to the generated project. Example: `composer.json`
* **Twig template files** are pre-processed by the Twig renderer before they are
  copied to the generated project. The configured properties are used as template
  variables. Read more at [`Architecture#Template rendering`](architecture.md#template-rendering).
  Example: `composer.json.twig`

## Shared source files

The shared source files are basically the same as the project source files. Both
generic files and Twig template files are processed. The only difference is that
shared source files must exist in the `shared` folder within the project's
template folder.

[1]: https://github.com/CuyZ/Valinor
[2]: https://github.com/justinrainbow/json-schema
