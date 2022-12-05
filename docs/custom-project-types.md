# Custom project types

Next to the available template packages, you are free to create your
own project type that meets your requirements. Follow the next steps
to create and publish a new template package with a custom project type.

## 1. Initialize the package

At first, create a new Composer package with a `composer.json` and the
type `project-builder-template`:

```json
{
    "name": "vendor/my-fancy-project-template",
    "description": "Project builder template for new fancy projects",
    "type": "project-builder-template",
    "require-dev": {
        "cpsit/project-builder": "^1.0"
    }
}
```

> :bulb: You can omit the `cpsit/project-builder` requirement if you're
> not going to add PHP sources that depend on classes provided by the
> Project Builder.

> :arrow_right: Read more at [`Configuration#External template packages`](configuration.md#external-template-packages).

## 2. Configure the project type

### Config file

Add a `config.yaml` file and give your project type a meaningful name:

```yaml
# config.yaml

name: My fancy project
```

> :arrow_right: Read more at [`Configuration#Config file`](configuration.md#config-file).

### Build steps

You are now required to provide a list of [build steps](processing-build-steps.md)
to be processed while building a new project. For standard project types,
the build steps are as follows:

1. Collect build instructions
2. Process source files
3. Mirror processed files
4. Show next steps

```diff
# config.yaml

name: My fancy project

+steps:
+  - type: collectBuildInstructions
+  - type: processSourceFiles
+  - type: mirrorProcessedFiles
+  - type: showNextSteps
+    options:
+      templateFile: templates/next-steps.html.twig
```

For extended project types, you may also install shared sources files or
restrict some files, depending on the user input:

```diff
# config.yaml

name: My fancy project

steps:
+ - type: installComposerDependencies
  - type: collectBuildInstructions
  - type: processSourceFiles
+   options:
+     fileConditions:
+       - path: 'foo/*'
+         condition: 'some["condition"] == "foo"'
+       - path: 'baz.twig'
+         condition: 'other_condition in baz'
+ - type: processSharedSourceFiles
  - type: mirrorProcessedFiles
  - type: showNextSteps
    options:
      templateFile: templates/next-steps.html.twig
```

> :arrow_right: Read more at [`Processing build steps#Available steps`](processing-build-steps.md#available-steps).

### Template properties

During the first step, `collectBuildInstructions`, a set of properties must be
constructed, resulting in concrete build instructions for the new project. Those
properties must be configured in the config file.

Properties are split into [`Property`](../src/Builder/Config/ValueObject/Property.php)
and [`SubProperty`](../src/Builder/Config/ValueObject/SubProperty.php). Properties
must either contain sub-properties or define a single value. They can be seen as a
kind of "category", whereas sub-properties add explicit configuration for it.

An example set of properties may look like this:

```yaml
# config.yaml

# ...

properties:
  # Project
  - identifier: project
    name: Project
    properties:
      # Sub-property "project name"
      - identifier: name
        name: Project name
        type: staticValue
        validators:
          - type: notEmpty
      # Sub-property "vendor name"
      - identifier: vendor
        name: Vendor name
        type: staticValue
        validators:
          - type: notEmpty
      # Sub-property "package name"
      - identifier: package_name
        name: Composer package name
        type: staticValue
        defaultValue: "{{ project.vendor|slugify }}/{{ project.name|slugify }}"
        validators:
          - type: notEmpty

  # Features
  - identifier: features
    name: Features
    properties:
      - identifier: my-cool-feature
        name: Enable <comment>my cool feature</comment>?
        type: question
      - identifier: my-other-feature
        name: Enable <comment>my other feature</comment>?
        type: question
```

As you can see, it's also possible to reference other properties in various
property configurations, e.g. `defaultValue`. Internally, the Symfony Expression
Language is used to render those values.

> :arrow_right: Have a look at the [configuration schema](../resources/config.schema.json)
> to get an overview about all available configuration options.

## 3. Add source files

Once your project type is properly configured, you might add the necessary source
files. All source files must be added to the `templates/src` directory. You can
add generic files as well as Twig templates.

Example structure:

```
my-fancy-project
├── composer.json
├── config.yaml
└── templates
    └── src
        ├── .editorconfig
        ├── composer.json.twig
        └── phpunit.xml
```

> :arrow_right: Read more at [`Configuration#Source files`](configuration.md#source-files).

## 4. Describe next steps

You may have noticed the `templateFile` configuration for the `showNextSteps` step
as described in the configuration file within step 2. This configuration points to
a Twig template where you describe the next steps necessary to be done once the user
finished the project build process.

Add the file as configured (in our example it's located at `templates/next-steps.html.twig`)
and write some helpful messages to guide the user through all necessary manual steps.

You can also omit this build step if you don't have the need to show next steps. But
keep in mind that it might be necessary for the user to get a project kickstarted as
easy as possible.

> :arrow_right: Read more at [`Processing build steps#Show next steps`](processing-build-steps.md#show-next-steps).

## 5. Publish the package

Last but not least, your new project type must be published in order to be usable
with the project builder. You can either submit the template package on Packagist.org
or add it to your self-hosted Composer registry (e.g. self-hosted [Satis][1] instance).

> :arrow_right: Read more at [`Configuration#External template packages`](configuration.md#external-template-packages).

## 6. Create a new project

Now you should be able to create a new project by running the following command:

```bash
composer create-project cpsit/project-builder
```

> :arrow_right: Read more at [`Usage`](usage.md).

Now, relax and enjoy a cup of tea. Well done! :tea:

[1]: https://github.com/composer/satis
