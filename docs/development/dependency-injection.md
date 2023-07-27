# Dependency injection

All services are bundled in a service container. This container is built during
bootstrapping. It depends on the following resources:

* Default service configuration, located at [`config`](https://github.com/CPS-IT/project-builder/tree/main/config) of this package
* [Project type specific](#extending-service-configuration) service configuration,
  located at `config` within an external template repository

## Tagged services

Some special services exist that are tagged during container build-time:

| Tag name                       | Resource                                                                                                                                   |
|--------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------|
| `builder.writer`               | [`Builder\Writer\WriterInterface`](https://github.com/CPS-IT/project-builder/blob/main/src/Builder/Writer/WriterInterface.php)             |
| `event.listener`               | –                                                                                                                                          |
| `expression_language.provider` | `Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface`                                                                 |
| `generator.step`               | [`Builder\Generator\Step\StepInterface`](https://github.com/CPS-IT/project-builder/blob/main/src/Builder/Generator/Step/StepInterface.php) |
| `io.validator`                 | [`IO\Validator\ValidatorInterface`](https://github.com/CPS-IT/project-builder/blob/main/src/IO/Validator/ValidatorInterface.php)           |
| `twig.filter`                  | [`Twig\Filter\TwigFilterInterface`](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Filter/TwigFilterInterface.php)           |
| `twig.function`                | [`Twig\Func\TwigFunctionInterface`](https://github.com/CPS-IT/project-builder/blob/main/src/Twig/Func/TwigFunctionInterface.php)           |

## Synthetic services

The following services are configured to be synthetic. They are added to the
compiled container during bootstrapping.

| Service id      | Class name                                                                                                   |
|-----------------|--------------------------------------------------------------------------------------------------------------|
| `app.config`    | [`Builder\Config\Config`](https://github.com/CPS-IT/project-builder/blob/main/src/Builder/Config/Config.php) |
| `app.messenger` | [`IO\Messenger`](https://github.com/CPS-IT/project-builder/blob/main/src/IO/Messenger.php)                   |

## Extending service configuration

Service configuration can be extended by each project type. Once a user selects
a project type during bootstrapping, the project type's template directory is being
searched for the following resources:

* `config/services.php`
* `config/services.yaml`
* `config/services.yml`

```{tip}
You can define more than one service configuration per project type.
```
