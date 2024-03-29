# Lifecycle

## Bootstrapping

Each project build is initialized by the [`Bootstrap`](https://github.com/CPS-IT/project-builder/blob/main/src/Bootstrap.php) class.
This class constructs the [`Application`](https://github.com/CPS-IT/project-builder/blob/main/src/Console/Application.php) that
triggers a new build and handles the build result. When a user executes the
`composer create-project` command, the bootstrapping process is triggered.

```{seealso}
See [`Bootstrap::createProject()`](https://github.com/CPS-IT/project-builder/blob/main/src/Bootstrap.php) which is defined as
`post-create-project-cmd` script in [`composer.json`](../../../composer.json).
```

## Configuration

For each project type an appropriate template configuration exists. Project templates
are distributed through external Composer packages. The configuration file describes
how the project is being built and which (optional) properties are required to
successfully run the build.

Once the user selected a project type, the appropriate configuration is parsed and
validated against a [schema](https://github.com/CPS-IT/project-builder/blob/main/resources/config.schema.json). If the config file is
valid, it gets hydrated on a [`Config`](https://github.com/CPS-IT/project-builder/blob/main/src/Builder/Config/Config.php) object.

```{seealso}
Read more at [`Configuration`](../configuration.md).
```

## Container

Based on the loaded configuration, a new service container is built. Its
configuration can be extended by each project type and contains all
relevant services to successfully run the project generation.

```{seealso}
Read more at [`Dependency injection`](../dependency-injection.md)
```

## Project generation

The whole project generation process is described by various build steps. Each step
implements [`StepInterface`](https://github.com/CPS-IT/project-builder/blob/main/src/Builder/Generator/Step/StepInterface.php) and
provides two main methods:

* **`run(Builder\BuildResult $buildResult): bool`** executes the step with the
  appropriate step configuration. It is responsible for performing I/O operations
  and finally applying the step to the build result.
* **`revert(Builder\BuildResult $buildResult): void`** is used once a step fails to
  be processed. It should provide a way to revert any changes made while processing
  the step, e.g. generating temporary files.

With the hydrated [`Config`](https://github.com/CPS-IT/project-builder/blob/main/src/Builder/Config/Config.php) object, a new
[`Generator`](https://github.com/CPS-IT/project-builder/blob/main/src/Builder/Generator/Generator.php) is created. It is responsible for
running and reverting steps. If a processed step either returns `false` or throws
an exception, all previously processed steps get reverted.

```{seealso}
Read more at [`Processing build steps`](../build-steps.md).
```

## Cleanup

Once all project build steps are successfully processed, the generated project is
cleaned up. This is done by the [`CleanUpStep`](https://github.com/CPS-IT/project-builder/blob/main/src/Builder/Generator/Step/CleanUpStep.php)
which must not be referenced anywhere else than in the bootstrapping process. It is
responsible for assuring a clean project state. For this, all protected library
files necessary to successfully execute the project generation are now removed.

The cleanup step is the second last part in the whole project generation lifecycle.
Thus, it is considered final and cannot be reverted. If an error occurs during clean
up, it is recommended to re-run the project generation.

## Build artifact

A template package can be configured to create a build artifact during project
generation. Build artifacts are JSON files that contain various information about
the project generation progress:

* Used template package and provider
* Generator package data
* Final build properties
* Applied build steps
* List of processed files

Build artifacts are versioned. Depending on which version of the project builder was
used, the artifact file structure might be different.

The complete JSON schema can be found at [`resources/build-artifact.schema.json`](https://github.com/CPS-IT/project-builder/blob/main/resources/build-artifact.schema.json).

```{important}
Build artifacts are not generated by default. Instead, template packages must be
explicitly configured to create such files. This can be done by including the
[`GenerateBuildArtifactStep`](../build-steps.md#generate-build-artifact) in the
template configuration.
```
