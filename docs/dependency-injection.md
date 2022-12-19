# Dependency injection

All services are bundled in a service container. This container is built during
bootstrapping. It depends on the following resources:

* Default service configuration, located at the [`config`](../config) directory
  of this package
* [Project type specific](#extending-service-configuration) service configuration,
  located at the `config` directory within an external template package

## Tagged services

Some special services exist that are tagged during container build-time:

| Tag name         | Resource                                                                                  |
|------------------|-------------------------------------------------------------------------------------------|
| `builder.writer` | [`Builder\Writer\WriterInterface`](../src/Builder/Writer/WriterInterface.php)             |
| `event.listener` | _any class_                                                                               |
| `generator.step` | [`Builder\Generator\Step\StepInterface`](../src/Builder/Generator/Step/StepInterface.php) |
| `io.validator`   | [`IO\Validator\ValidatorInterface`](../src/IO/Validator/ValidatorInterface.php)           |
| `twig.filter`    | [`Twig\Filter\TwigFilterInterface`](../src/Twig/Filter/TwigFilterInterface.php)           |
| `twig.function`  | [`Twig\Func\TwigFunctionInterface`](../src/Twig/Func/TwigFunctionInterface.php)           |

## Synthetic services

The following services are configured to be synthetic. They are added to the
compiled container during bootstrapping.

| Service id      | Class name                                                  |
|-----------------|-------------------------------------------------------------|
| `app.config`    | [`Builder\Config\Config`](../src/Builder/Config/Config.php) |
| `app.messenger` | [`IO\Messenger`](../src/IO/Messenger.php)                   |

## Extending service configuration

Service configuration can be extended by each project type. Once a user selects
a project type during bootstrapping, the project type's template directory is being
searched for the following resources:

* `config/services.php`
* `config/services.yaml`
* `config/services.yml`

> 💡 You can define more than one service configuration per project type.
