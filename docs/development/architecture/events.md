# Events

The project build process is a complex construct. It is meant to match as many
needs as possible. Therefore, it provides convenient ways for customization.
However, some use cases may not always be configurable, for example if an interaction
should be possible on failed build steps only.

For this, the project builder provides an event-driven interaction system. On
several points in the project build lifecycle custom events are dispatched. They
contain information about the current process and thus enable its modification.

## Dispatched events

The following events are currently dispatched:

* [**`ProjectBuildStartedEvent`**](https://github.com/CPS-IT/project-builder/blob/main/src/Event/ProjectBuildStartedEvent.php)
  is dispatched after the user selected a project type to be generated. The
  event provides all necessary build instructions.
* [**`ProjectBuildFinishedEvent`**](https://github.com/CPS-IT/project-builder/blob/main/src/Event/ProjectBuildFinishedEvent.php)
  is dispatched once the whole project build process is finished. It provides
  the build result containing all processed build steps and the final result.
* [**`BuildInstructionCollectedEvent`**](https://github.com/CPS-IT/project-builder/blob/main/src/Event/BuildInstructionCollectedEvent.php)
  is dispatched when a collected build instruction is about to be applied to
  the build result. It allows to modify the instructed value by calling
  `setValue()`.
* [**`BuildStepProcessedEvent`**](https://github.com/CPS-IT/project-builder/blob/main/src/Event/BuildStepProcessedEvent.php) is
  dispatched once a configured step is processed, either successfully or failing.
  The event provides information about the processed step and its result and
  process state.
* [**`BuildStepRevertedEvent`**](https://github.com/CPS-IT/project-builder/blob/main/src/Event/BuildStepRevertedEvent.php) is
  dispatched if a previously applied step is reverted.
* [**`BeforeTemplateRenderedEvent`**](https://github.com/CPS-IT/project-builder/blob/main/src/Event/BeforeTemplateRenderedEvent.php)
  is dispatched once a Twig template rendering is requested. The event provides
  the current Twig environment as well as build instructions and prepared
  template variables. The latter can be modified by calling `setVariables()`.

## Event listeners

Once an event is dispatched, all registered event listeners will be called. An
event listener can then perform the required interactions, based on the given
event.

You can register your own event listener via service configuration. Tag the
appropriate service with `event.listener` and provide additional metadata:

```{code-block} yaml
:linenos:
:caption: config/services.yaml

services:
  Vendor\Extension\Event\Listener\MyEventListener:
    tags:
      - name: event.listener
        method: onBuildStepProcessed
        event: CPSIT\ProjectBuilder\Event\BuildStepProcessedEvent
```

The appropriate listener class looks like the follows:

```{code-block} php
:linenos:
:caption: src/Event/Listener/MyEventListener.php

namespace Vendor\Extension\Event\Listener;

use CPSIT\ProjectBuilder\Event;

final class MyEventListener
{
    public function onBuildStepProcessed(Event\BuildStepProcessedEvent $event): void
    {
        // Do something...
    }
}
```

:::{tip}
You can also omit the `method` and `event` configuration if your event listener
contains an `__invoke` method with the event type-hinted as first parameter:

```{code-block} yaml
:linenos:
:caption: config/services.yaml

services:
  Vendor\Extension\Event\Listener\MyEventListener:
    tags: ['event.listener']
```

```{code-block} php
:linenos:
:caption: src/Event/Listener/MyEventListener.php

namespace Vendor\Extension\Event\Listener;

use CPSIT\ProjectBuilder\Event;

final class MyEventListener
{
    public function __invoke(Event\BuildStepProcessedEvent $event): void
    {
        // Do something...
    }
}
```
:::
