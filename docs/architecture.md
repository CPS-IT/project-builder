# Architecture

## Core concept

This project serves as template repository for new projects. It is built on top of
these three concepts:

* Each project type is described by a [**template configuration**](#configuration) file
* Config files contains [**build steps**](#project-generation) to be processed
* Project templates can be defined as a set of
  - Generic files
  - [Twig template files](#template-rendering)
  - External files, included as Composer dependencies

## Lifecycle

### Bootstrapping

Each project build is initialized by the [`Bootstrap`](../src/Bootstrap.php) class.
This class triggers a new build and handles the build result. When a user executes
the `composer create-project` command, the bootstrapping process is triggered.

> :bulb: See [`Bootstrap::createProject()`](../src/Bootstrap.php) which is defined as
> `post-create-project-cmd` script in [`composer.json`](../composer.json).

### Configuration

For each project type an appropriate template configuration exists. Project templates
are stored in the [`templates`](../templates) directory. The configuration file
describes how the project is being built and which (optional) properties are required
to successfully run the build.

Once the user selected a project type, the appropriate configuration is parsed and
validated against a [schema](../resources/config.schema.json). If the config file is
valid, it gets hydrated on a [`Config`](../src/Builder/Config/Config.php) object.

> :arrow_right: Read more at [`Configuration`](configuration.md).

### Container

Based on the loaded configuration, a new service container is built. Its
configuration can be extended by each project type and contains all
relevant services to successfully run project generation.

> :arrow_right: Read more a [`Dependency injection`](dependency-injection.md)

### Project generation

The whole project generation process is described by various build steps. Each step
implements [`StepInterface`](../src/Builder/Generator/Step/StepInterface.php) and
provides two main methods:

* **`run(Builder\BuildResult $buildResult): bool`** executes the step with the
  appropriate step configuration. It is responsible for performing I/O operations
  and finally applying the step to the build result.
* **`revert(Builder\BuildResult $buildResult): void`** is used once a step fails to
  be processed. It should provide a way to revert any changes made while processing
  the step, e.g. generating temporary files.

With the hydrated [`Config`](../src/Builder/Config/Config.php) object, a new
[`Generator`](../src/Builder/Generator/Generator.php) is created. It is responsible for
running and reverting steps. If a processed step either returns `false` or throws
an exception, all previously processed steps get reverted.

> :arrow_right: Read more at [`Processing build steps`](processing-build-steps.md).

### Cleanup

Once all project build steps are successfully processed, the generated project is
cleaned up. This is done by the [`CleanUpStep`](../src/Builder/Generator/Step/CleanUpStep.php)
which must not be referenced anywhere else than in the bootstrapping process. It is
responsible for assuring a clean project state. For this, all protected library
files that were necessary to successfully execute the project generation are now
removed.

The cleanup step is the last part in the whole project generation lifecycle. Thus,
it's considered final and cannot be reverted. If an error occurs during clean up,
it's recommended to re-run project generation.

## Additional components

Next to the previously mentioned main components, some additional components exist.
Those are mainly used while processing the various build steps.

### Composer interaction

The [**`Composer\ComposerInstaller`**](../src/Composer/ComposerInstaller.php) component provides a
way to install **Composer dependencies**, described by an existing `composer.json`
file:

```php
$installer = new \CPSIT\ProjectBuilder\Composer\ComposerInstaller();
$exitCode = $installer->install('/path/to/composer.json', $output);
```

### Error handling

During bootstrapping, the [**`Error\ErrorHandler`**](../src/Error/ErrorHandler.php)
component is initialized. It is responsible for **handling exceptions** that are
thrown by the application. On verbose output, the exception is passed through the
Composer application.

### I/O handling

Additionally, there exist two core components that are responsible for **I/O handling**:

1. The [**`IO\Messenger`**](../src/IO/Messenger.php) component takes care of every
   message that is passed to the user. Additionally, it provides some helper methods
   to interact with the user or provide a styled output for e.g. new sections or
   user selections.
2. While the `Messenger` mainly targets the application's output behavior, a second
   component [**`IO\InputReader`**](../src/IO/InputReader.php) handles user input.
   It's mainly used when processing interactive build steps to fetch and apply user
   input. The `InputReader` depends on an active [`IO`][1] object, which is only available
   to the `Messenger`, thus it implicitly depends on it.

Example:

```php
/** @var \CPSIT\ProjectBuilder\IO\InputReader $inputReader */

$phpVersion = $inputReader->choices('Which PHP version should be used?', ['8.1', '8.0', '7.4']);
$name = $inputReader->staticValue('What\'s your name?');

if ($inputReader->ask('Confirm project generation?', default: false)) {
    // Project generation confirmed, continue...
}
```

As an additional I/O component, some **validators** exist, ready to be used by the
`InputReader`:

| Type       | Class                                                            | Description                                          |
|------------|------------------------------------------------------------------|------------------------------------------------------|
| `email`    | [`EmailValidator`](../src/IO/Validator/EmailValidator.php)       | User input must be a valid e-mail address            |
| `notEmpty` | [`NotEmptyValidator`](../src/IO/Validator/NotEmptyValidator.php) | User input must not be empty (strict mode available) |
| `url`      | [`UrlValidator`](../src/IO/Validator/UrlValidator.php)           | User input must be a valid URL                       |

Each validator implements [`ValidatorInterface`](../src/IO/Validator/ValidatorInterface.php).

:bulb: Not all validators can be used for each interaction with the `InputReader`.

### Naming

With the [**`Naming\NameVariantBuilder`**](../src/Naming/NameVariantBuilder.php)
component, it is possible to **create variants of project and customer names**. For
this, the current build instructions are used. All available name variants are
described in the [**`Naming\NameVariant`**](../src/Naming/NameVariant.php) class.

### Template rendering

Project templates must be written as [Twig][2] template files.

Each template file is **rendered** by the [**`Twig\Renderer`**](../src/Twig/Renderer.php)
component. The `Renderer` internally heavily makes use of a custom Twig extension, the
[**`Twig\Extension\ProjectBuilderExtension`**](../src/Twig/Extension/ProjectBuilderExtension.php)
component. Within this extension, a prebuilt set of Twig filters and functions is
registered.

#### Twig filters

The following Twig filters currently exist:

| Name           | Class                                                           | Description                                                      |
|----------------|-----------------------------------------------------------------|------------------------------------------------------------------|
| `convert_case` | [`ConvertCaseFilter`](../src/Twig/Filter/ConvertCaseFilter.php) | Convert string to a given [`string case`](../src/StringCase.php) |
| `slugify`      | [`SlugifyFilter`](../src/Twig/Filter/SlugifyFilter.php)         | Generate slug from given string by using [`cocur/slugify`][3]    |

Each Twig filter implements [`TwigFilterInterface`](../src/Twig/Filter/TwigFilterInterface.php).

#### Twig functions

The following Twig functions currently exist:

| Name                            | Class                                                                           | Description                                                                                                                                                  |
|---------------------------------|---------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `is_default_project_name`       | [`DefaultProjectNameFunction`](../src/Twig/Func/DefaultProjectNameFunction.php) | Check if given project name is the default project name as described by [`NameVariantBuilder::isDefaultProjectName()`](../src/Naming/NameVariantBuilder.php) |
| `get_default_author_email`      | [`DefaultAuthorEmailFunction`](../src/Twig/Func/DefaultAuthorEmailFunction.php) | Read default author e-mail address from global Git config                                                                                                    |
| `get_default_author_name`       | [`DefaultAuthorNameFunction`](../src/Twig/Func/DefaultAuthorNameFunction.php)   | Read default author name from global Git config                                                                                                              |
| `get_latest_stable_php_version` | [`PhpVersionFunction`](../src/Twig/Func/PhpVersionFunction.php)                 | Fetch the latest stable PHP version from PHP REST API (response is cached)                                                                                   |
| `name_variant`                  | [`NameVariantFunction`](../src/Twig/Func/NameVariantFunction.php)               | Create name variant with [`NameVariantBuilder::createVariant()`](../src/Naming/NameVariantBuilder.php)                                                       |
| `resolve_ip`                    | [`ResolveIpFunction`](../src/Twig/Func/ResolveIpFunction.php)                   | Resolve IP address for a given hostname or URL                                                                                                               |

Each Twig function implements [`TwigFunctionInterface`](../src/Twig/Func/TwigFunctionInterface.php).

[1]: https://github.com/composer/composer/blob/main/src/Composer/IO/IOInterface.php
[2]: https://twig.symfony.com/
[3]: https://github.com/cocur/slugify
