# Docker

```{button-link} https://hub.docker.com/r/cpsit/project-builder
:color: primary
:outline:

{octicon}`link-external;1em;sd-mr-1` View image on Docker Hub
```

## Requirements

* [Docker][1]

## Basic usage

As an alternative to the usage with Composer, there's also a ready-to-use
[Docker image][2]:

```bash
docker run --rm -it -v <target-dir>:/app cpsit/project-builder
```

Replace `<target-dir>` with an absolute or relative path to the directory
where to install and set up your new project. Make sure to always mount
the volume to `/app`.

```{note}
In the entrypoint, `composer create-project` is executed. It already
contains all [recommended command options](composer.md#recommended-usage).
```

## Available image tags

The following image tags are currently available:

| Tag name    | Description                                                                                 |
|-------------|---------------------------------------------------------------------------------------------|
| `<version>` | The appropriate project version, e.g. <code class="literal">{{ env.config.release }}</code> |
| `latest`    | The latest project version                                                                  |

[1]: https://www.docker.com/
[2]: https://hub.docker.com/r/cpsit/project-builder
