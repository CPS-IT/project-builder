# Configuration

This project provides a generic way to create a various set of project
types. For each project type, an appropriate build configuration exists.
The configuration describes how to build a project, e.g. which steps are
necessary and what properties are required when rendering project
templates.

## External template packages

Project templates are distributed through **external Composer packages**.
Each Composer package must be of the type `project-builder-template`.

Example `composer.json`:

```json
{
    "name": "cpsit/project-builder-template-my-fancy-project",
    "type": "project-builder-template",
    // ...
}
```

Additionally, the packages must be installable via Composer. There are
three ways to make a template package available to the project builder:

1. Either register it on [Packagist](https://packagist.org/),
2. Use any other Composer registry (e.g. self-hosted [Satis][4] instance),
or
3. Host the project template on a VCS repository (such as GitHub) to make your package
available to the project builder.

Once you use the project builder to create a new project, you can
select the appropriate provider that hosts your template package.

## Structure

Within the external Composer template package, the following file
structure must exist:

```
my-fancy-project
├── composer.json
├── config.yml
├── config
│   └── services.yaml
├── src
│   ├── ...
│   └── Twig
│       └── Function
│           └── MyCustomTwigFunction.php
└── templates
    ├── shared
    │   ├── ...
    │   └── my-fancy-shared-resource
    │       ├── composer.json
    │       └── templates
    │           └── src
    │               ├── ...
    │               └── .gitlab-ci.yml.twig
    └── src
        ├── ...
        └── composer.json.twig
```

In this example, the project type `my-fancy-project` is configured and
distributed through the package `cpsit/project-builder-template-my-fancy-project`.
It contains the following files and directories:

* **`composer.json`** (optional) defines additional template dependencies.
  Those are installed by the build step `installComposerDependencies`.
  Read more at [`Processing build steps#Install Composer dependencies`](processing-build-steps.md#install-composer-dependencies).
* **`config.yml`** is the main configuration file. It contains all
  instructions on how to build new projects of this project type. Read more
  at [`Config file`](#config-file).
* **`config`** (optional) contains additional service configuration files,
  e.g. `services.yaml` or `services.php`. Read more at
  [`Dependency injection#Extending service configuration`](dependency-injection.md#extending-service-configuration).
* **`src`** (optional) may contain additional PHP classes. Normally, these
  require an additional service configuration as described before.
* **`templates`** (optional) contains various project source files. The
  following sub-folders are supported:
  - **`shared`** (optional) should contain shared source files. Those are
    normally created when installing Composer dependencies defined by
    `composer.json`. Read more at [`Shared source files`](#shared-source-files).
  - **`src`** (optional) contains all project source files. Those can be
    either generic files to be copied to the generated project or Twig
    template files. Twig files are processed before copying them to the
    generated project. Read more at [`Source files`](#source-files).

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
`templates/src` folder.

Currently, the following file variants are supported:

* **Generic files** can be any files other than Twig files. They will be copied
  as-is to the generated project. Example: `composer.json`
* **Twig template files** are pre-processed by the Twig renderer before they are
  copied to the generated project. The configured properties are used as template
  variables. Read more at [`Architecture#Template rendering`](architecture.md#template-rendering).
  Example: `composer.json.twig`

## Shared source files

In case multiple project types share the same source files, it might be useful
to outsource them to an external Composer package. This allows better maintenance
of those shared source files. Per convention, external shared Composer packages
should be of the type `project-builder-shared`.

Example `composer.json`:

```json
{
    "name": "cpsit/project-builder-shared-my-fancy-shared-resource",
    "type": "project-builder-shared",
    // ...
}
```

### Integration into the template package

The shared source file packages must be required in the `composer.json` file of
each project type that requires the shared source files. As a consequence, the
package must be installable via Composer. In order to make it installable, either
submit it on Packagist or add it to our Satis configuration at
<https://composer.321.works>.

The project builder expects shared source files to be installed within the
project type's `templates/shared/<package-name>/templates/src` folder. For this,
it is useful to use the Composer package [`oomphinc/composer-installers-extender`][3]
and define the installation paths of each shared source file package.

Example `composer.json`:

```json
{
    "name": "cpsit/project-builder-template-my-fancy-project",
    "type": "project-builder-template",
    "require": {
        "cpsit/project-builder-shared-my-fancy-shared-resource": "^1.0",
        "oomphinc/composer-installers-extender": "^2.0"
    },
    "extra": {
        "installer-paths": {
            "templates/shared/{$name}/": [
                "type:project-builder-shared"
            ]
        },
        "installer-types": [
            "project-builder-shared"
        ]
    },
    // ...
}
```

The shared source file package must then provide the following folder structure:

```
my-fancy-shared-resource
├── composer.json
└── templates
    └── src
        ├── ...
        └── some-shared-file.json.twig
```

[1]: https://github.com/CuyZ/Valinor
[2]: https://github.com/justinrainbow/json-schema
[3]: https://packagist.org/packages/oomphinc/composer-installers-extender
[4]: https://github.com/composer/satis
