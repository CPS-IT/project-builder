# Contribution workflow

[![Maintainability](https://api.codeclimate.com/v1/badges/a84923d4d61c50561186/maintainability)][1]

Thanks for considering contributing to this project! Each contribution is
highly appreciated. In order to maintain a high code quality, please follow
all steps below.

## Requirements

- Composer >= 2.1
- PHP >= 8.1
- Docker

## Preparation

```bash
# Clone repository
git clone https://github.com/CPS-IT/project-builder.git
cd project-builder

# Install dependencies
composer install
```

## Code style

[![CGL](https://github.com/CPS-IT/project-builder/actions/workflows/cgl.yaml/badge.svg)][3]

Please run all code analysis tools below to follow our CGL and maintain a
consistent code style.

### Run linters

```bash
# All linters
composer lint

# Specific linters
composer lint:composer
composer lint:editorconfig
composer lint:json
composer lint:php

# Fix all CGL issues
composer fix

# Fix specific CGL issues
composer fix:composer
composer fix:editorconfig
composer fix:php
```

### Run static code analysis

```bash
# All static code analyzers
composer sca

# Specific static code analyzers
composer sca:php
```

## Tests

[![Tests](https://github.com/CPS-IT/project-builder/actions/workflows/tests.yaml/badge.svg)][4]

Please run all tests and make sure they are passing. If new code is added,
it should be covered by appropriate test cases.

### Run tests

```bash
# All tests
composer test

# Docker tests
composer test:docker

# Unit tests
composer test:unit

# Unit tests with code coverage
composer test:unit:coverage
```

### View coverage report

[![Coverage](https://img.shields.io/coverallsCoverage/github/CPS-IT/project-builder?logo=coveralls)][5]

Code coverage reports are written to `.build/coverage`. You can open the
last HTML report like follows:

```bash
open .build/coverage/html/index.html
```

## Validate JSON schema

```bash
composer validate-schema
```

## Simulate `composer create-project` behavior

The `composer create-project` behavior can be simulated to test whether the
current project state works as expected.

```bash
composer simulate
```

This Composer script wraps the default behavior of `composer create-project`
into a simulated directory, which is normally something like
`.build/simulate_6299c0dda8600`. The simulated directory will be shown after
simulation has finished.

```{tip}
An environment variable `PROJECT_BUILDER_SIMULATE_VERSION` can be used to
override the project builder version during simulation. This is especially
useful when testing template packages that require a specific version of the
project builder.
```

## Documentation

[![Documentation](https://github.com/CPS-IT/project-builder/actions/workflows/documentation.yaml/badge.svg)][6]

```bash
# Build and serve documentation
composer docs

# Build documentation
composer docs:build

# Serve documentation
composer docs:serve

# Open rendered documentation
composer docs:open
```

When serving documentation, all changes to documentation files cause an immediate
re-rending. You can view the documentation at <http://127.0.0.1:8080>.

## Submit a pull request

```{note}
This project follows [Semantic Versioning][2].
```

Once you have finished your work, please **submit a pull request** and describe
what you've done. Ideally, your PR references an issue describing the problem
you're trying to solve.

All described code quality tools are automatically executed on each pull request
for all currently supported PHP versions and Composer versions. Take a look at
the appropriate [workflows][7] to get a detailed overview.

[1]: https://codeclimate.com/github/CPS-IT/project-builder/maintainability
[2]: https://semver.org/
[3]: https://github.com/CPS-IT/project-builder/actions/workflows/cgl.yaml
[4]: https://github.com/CPS-IT/project-builder/actions/workflows/tests.yaml
[5]: https://coveralls.io/github/CPS-IT/project-builder
[6]: https://github.com/CPS-IT/project-builder/actions/workflows/documentation.yaml
[7]: https://github.com/CPS-IT/project-builder/tree/main/.github/workflows
