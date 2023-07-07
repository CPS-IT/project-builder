# Additional components

Next to the previously mentioned main components, some additional components exist.
Those are mainly used while processing the various build steps.

## Composer interaction

The [**`Resource\Local\Composer`**](https://github.com/CPS-IT/project-builder/blob/main/src/Resource/Local/Composer.php) component
provides a way to install **Composer dependencies**, described by an existing
`composer.json` file:

```{code-block} php
:linenos:

$installer = new \CPSIT\ProjectBuilder\Resource\Local\Composer();
$exitCode = $installer->install('/path/to/composer.json', output: $output);
```

## Error handling

During bootstrapping, the [**`Error\ErrorHandler`**](https://github.com/CPS-IT/project-builder/blob/main/src/Error/ErrorHandler.php)
component is initialized. It is responsible for **handling exceptions** that are
thrown by the application. On verbose output, the exception is passed through the
Composer application.

## I/O handling

Additionally, there exist two core components that are responsible for **I/O handling**:

1. The [**`IO\Messenger`**](https://github.com/CPS-IT/project-builder/blob/main/src/IO/Messenger.php) component takes care of every
   message that is passed to the user. Additionally, it provides some helper methods
   to interact with the user or provide a styled output for e.g. new sections or
   user selections.
2. While the `Messenger` mainly targets the application's output behavior, a second
   component [**`IO\InputReader`**](https://github.com/CPS-IT/project-builder/blob/main/src/IO/InputReader.php) handles user input.
   It's mainly used when processing interactive build steps to fetch and apply user
   input. The `InputReader` depends on an active [`IO`][1] object, which is only available
   to the `Messenger`, thus it implicitly depends on it.

Example:

```{code-block} php
:linenos:

/** @var \CPSIT\ProjectBuilder\IO\InputReader $inputReader */

$phpVersion = $inputReader->choices('Which PHP version should be used?', ['8.1', '8.0']);
$name = $inputReader->staticValue('What\'s your name?');
$password = $inputReader->hiddenValue('What\'s your password?');

if ($inputReader->ask('Confirm project generation?', default: false)) {
    // Project generation confirmed, continue...
}
```

As an additional I/O component, some **validators** exist, ready to be used by the
`InputReader`:

| Type       | Class                                                                                                             | Description                                          |
|------------|-------------------------------------------------------------------------------------------------------------------|------------------------------------------------------|
| `callback` | [`CallbackValidator`](https://github.com/CPS-IT/project-builder/blob/main/src/IO/Validator/CallbackValidator.php) | User input is validated by a given callback          |
| `email`    | [`EmailValidator`](https://github.com/CPS-IT/project-builder/blob/main/src/IO/Validator/EmailValidator.php)       | User input must be a valid e-mail address            |
| `notEmpty` | [`NotEmptyValidator`](https://github.com/CPS-IT/project-builder/blob/main/src/IO/Validator/NotEmptyValidator.php) | User input must not be empty (strict mode available) |
| `regex`    | [`RegexValidator`](https://github.com/CPS-IT/project-builder/blob/main/src/IO/Validator/RegexValidator.php)       | User input must match a given regular expression     |
| `url`      | [`UrlValidator`](https://github.com/CPS-IT/project-builder/blob/main/src/IO/Validator/UrlValidator.php)           | User input must be a valid URL                       |

Each validator implements [`ValidatorInterface`](https://github.com/CPS-IT/project-builder/blob/main/src/IO/Validator/ValidatorInterface.php).

```{note}
Not all validators can be used for each interaction with the `InputReader`.
```

## Naming

With the [**`Naming\NameVariantBuilder`**](https://github.com/CPS-IT/project-builder/blob/main/src/Naming/NameVariantBuilder.php)
component, it is possible to **create variants of project and customer names**. For
this, the current build instructions are used. All available name variants are
described in the [**`Naming\NameVariant`**](https://github.com/CPS-IT/project-builder/blob/main/src/Naming/NameVariant.php) class.

## Template rendering

Project templates must be written as [Twig][2] template files.

Each template file is **rendered** by the [**`Twig\Renderer`**](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Renderer.php)
component. The `Renderer` internally heavily makes use of a custom Twig extension, the
[**`Twig\Extension\ProjectBuilderExtension`**](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Extension/ProjectBuilderExtension.php)
component. Within this extension, a prebuilt set of Twig filters and functions is
registered.

### Twig filters

The following Twig filters currently exist:

| Name           | Class                                                                                                            | Description                                                                                                       |
|----------------|------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------|
| `convert_case` | [`ConvertCaseFilter`](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Filter/ConvertCaseFilter.php) | Convert string to a given [`string case`](https://github.com/CPS-IT/project-builder/blob/main/src/StringCase.php) |
| `slugify`      | [`SlugifyFilter`](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Filter/SlugifyFilter.php)         | Generate slug from given string by using [`cocur/slugify`][3]                                                     |

Each Twig filter implements [`TwigFilterInterface`](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Filter/TwigFilterInterface.php).

### Twig functions

The following Twig functions currently exist:

| Name                            | Class                                                                                                                            | Description                                                                                                                                                                                                   |
|---------------------------------|----------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `is_default_project_name`       | [`DefaultProjectNameFunction`](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Func/DefaultProjectNameFunction.php) | Check if given project name is the default project name as described by [`NameVariantBuilder::isDefaultProjectName()`](https://github.com/CPS-IT/project-builder/blob/main/src/Naming/NameVariantBuilder.php) |
| `get_default_author_email`      | [`DefaultAuthorEmailFunction`](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Func/DefaultAuthorEmailFunction.php) | Read default author e-mail address from global Git config                                                                                                                                                     |
| `get_default_author_name`       | [`DefaultAuthorNameFunction`](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Func/DefaultAuthorNameFunction.php)   | Read default author name from global Git config                                                                                                                                                               |
| `get_latest_stable_php_version` | [`PhpVersionFunction`](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Func/PhpVersionFunction.php)                 | Fetch the latest stable PHP version from PHP REST API (response is cached)                                                                                                                                    |
| `name_variant`                  | [`NameVariantFunction`](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Func/NameVariantFunction.php)               | Create name variant with [`NameVariantBuilder::createVariant()`](https://github.com/CPS-IT/project-builder/blob/main/src/Naming/NameVariantBuilder.php)                                                       |
| `resolve_ip`                    | [`ResolveIpFunction`](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Func/ResolveIpFunction.php)                   | Resolve IP address for a given hostname or URL                                                                                                                                                                |

Each Twig function implements [`TwigFunctionInterface`](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Func/TwigFunctionInterface.php).

[1]: https://github.com/composer/composer/blob/main/src/Composer/IO/IOInterface.php
[2]: https://twig.symfony.com/
[3]: https://github.com/cocur/slugify
