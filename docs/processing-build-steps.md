# Processing build steps

Project generation is described through a set of so-called "build steps". Each step is
configured by the `step` property (see [`Configuration#Structure`](configuration.md#structure)).

## Procedure

1. Once the user selected a project type, a new [`Generator`](../src/Builder/Generator/Generator.php)
   is created
2. The `Generator` runs through all configured steps
   1. Step is created by using the [`StepFactory`](../src/Builder/Generator/Step/StepFactory.php)
   2. Step is executed by calling `$step->run($result)`
   3. On step failure:
      * All applied steps are [reverted](#reverting-failed-steps) by calling
        `$step->revert($result)`
      * Further execution is terminated
   4. [`BuildResult`](../src/Builder/BuildResult.php) is returned

## Reverting failed steps

When steps are executed, they may perform I/O operations on the generated project or other
directories. If any of those operations fail, it should be possible to revert them. For
this, steps are required to implement a `revert()` method. It is called for all previously
applied steps once any step in the project generation process fails.

## Applying processed steps

Each successfully processed step should itself apply to the current build result. This is
the only way for the application to recognize that the step was executed. If the step is
not applied to the build result, it cannot be reverted.

Example:

```php
use CPSIT\ProjectBuilder\Builder;

final class MyStep implements Builder\Generator\Step\StepInterface
{
    public function run(Builder\BuildResult $buildResult): bool
    {
        // Do anything...

        if ($successful) {
            $buildResult->applyStep($this);
        }

        return $successful;
    }

    // ...
}
```

## Variants

Each step must implement [`StepInterface`](../src/Builder/Generator/Step/StepInterface.php).
However, some variants for steps exist giving them additional possibilities in the
project generation lifecycle:

* If a step is actively **processing** files, it should implement
  [`ProcessingStepInterface`](../src/Builder/Generator/Step/ProcessingStepInterface.php).
  This way, it is possible to provide information about which files were processed after
  successful project generation.
* Steps may also be able to stop further processing, thus being **stoppable**. For this,
  [`StoppableStepInterface`](../src/Builder/Generator/Step/StoppableStepInterface.php)
  can be implemented. The method `isStopped()` is then called after the step is processed
  to determine whether further steps should be processed.

## Available steps

The following steps are currently available:

### Collect build instructions

* Identifier: **`collectBuildInstructions`**
* Implementation: [`Builder\Generator\Step\CollectBuildInstructionsStep`](../src/Builder/Generator/Step/CollectBuildInstructionsStep.php)

This step walks through all configured properties. It uses the `InputReader` to collect
build instructions for property. All collected build instructions result in a list of
template variables. Those can be used in other steps when rendering Twig templates or
taking decisions based on user-provided data.

### Install Composer dependencies

* Identifier: **`installComposerDependencies`**
* Implementation: [`Builder\Generator\Step\InstallComposerDependenciesStep`](../src/Builder/Generator/Step/InstallComposerDependenciesStep.php)

By using this step, it is possible to install additional Composer dependencies. Those
dependencies should provide shared source files. The appropriate `composer.json`
must be in the project type's template folder.

> ➡️ Read more at [`Configuration`](configuration.md).

### Mirror processed files

* Identifier: **`mirrorProcessedFiles`**
* Implementation: [`Builder\Generator\Step\MirrorProcessedFilesStep`](../src/Builder/Generator/Step/MirrorProcessedFilesStep.php)
* Variants: _processing_, _stoppable_

This is typically one of the **last configured steps**. It asks for confirmation to
mirror all previously processed source files and shared source files to the target
project directory. It also takes care of cleaning up the target directory as
well as removing the previously generated temporary directory.

### Process source files

* Identifier: **`processSourceFiles`**
* Implementation: [`Builder\Generator\Step\ProcessSourceFilesStep`](../src/Builder/Generator/Step/ProcessSourceFilesStep.php)
* Variants: _processing_

Copies all files in the project type's `templates/src` folder to a temporary directory.
If a file has the `.twig` extension, it is first processed by the Twig renderer. All
previously collected build instructions are passed as template variables to the
renderer.

In the project template config, it is possible to provide a set of file conditions.
Each file condition applies to a given path. The specified condition is then used
in the step to decide whether to include or exclude the appropriate file. All other
files will always be processed.

> ➡️ Read more at [`Configuration#Source files`](configuration.md#source-files)
> and [`Architecture#Template rendering`](architecture.md#template-rendering).

### Process shared source files

* Identifier: **`processSharedSourceFiles`**
* Implementation: [`Builder\Generator\Step\ProcessSharedSourceFilesStep`](../src/Builder/Generator/Step/ProcessSharedSourceFilesStep.php)
* Variants: _processing_

The behavior of this step is similar to the [`processSourceFiles`](#process-source-files)
step. However, this step processes shared source files inside the `shared`
folder of the project type's `templates` directory. These files are normally added
during runtime by the [`installComposerDependencies`](#install-composer-dependencies)
step.

> ➡️ Read more at [`Configuration#Shared source files`](configuration.md#shared-source-files).

### Show next steps

* Identifier: **`showNextSteps`**
* Implementation: [`Builder\Generator\Step\ShowNextStepsStep`](../src/Builder/Generator/Step/ShowNextStepsStep.php)

This step should always be configured as **last step**. It shows the user how to
manually continue the build process after successful project generation. This
can be some sort of "Getting started with the new project". The steps are
provided by a Twig template file, configured via the `templateFile` option.
