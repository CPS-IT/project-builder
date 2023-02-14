# Architecture

```{attention}
This section is only relevant for **contributors and developers** maintaining
custom project templates. If you're a user of the project builder, you probably
want to see how to [get started](../../getting-started.md) on creating new projects
instead.
```

On the following pages, the concepts and architecture of the project builder are
written down. If you are a template developer, make sure to make yourself familiar
with the concept before you start working on the project builder or develop custom
project templates.

## Core concept

This project serves as kickstarter package for new projects. It is built on top of
these three concepts:

* Project templates are distributed and [configured](../configuration.md) as separate
  Composer packages
* Each template configuration contains [build steps](lifecycle.md#project-generation)
  to be processed
* Template files can either be static files or [Twig templates](components.md#template-rendering)

## Read more

You can find a detailed overview about the architectural concept on the following
pages:

```{toctree}
:maxdepth: 2

lifecycle
events
components
```
