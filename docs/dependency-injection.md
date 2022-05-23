# Dependency injection

All services are bundled in a service container. This container is built during
bootstrapping. It depends on the following resources:

* Default service configuration, located at [`config`](../config)
* [Project type specific](#extending-service-configuration) service configuration,
  located at `templates/*/config`

## Caching

Each container is dumped and cached in the system's temporary directory. This
avoids container rebuilding on each project generation request. Since containers
depend on the selected project type, the related resources are used to generate
a unique container filename hash.

:bulb: See [`ContainerFactory::createCache()`](../src/DependencyInjection/ContainerFactory.php)
for deeper insights into container caching.

## Tagged services

Some special services exist that are tagged during container build-time:

| Tag name         | Interface                                                                                 |
|------------------|-------------------------------------------------------------------------------------------|
| `builder.writer` | [`Builder\Writer\WriterInterface`](../src/Builder/Writer/WriterInterface.php)             |
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

Example: `templates/my-fancy-project/config/services.yaml`

:bulb: You can define more than one service configuration per project type.

## Debugging

Debugging for the container cache can be enabled by running the `composer create-project`
in debug mode:

```bash
composer create-project cpsit/project-builder -vvv [...]
```
