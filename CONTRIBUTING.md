# Contributing

Thanks for considering contributing to this project! Each contribution is
highly appreciated. In order to maintain a high code quality, please follow
all steps below.

## Requirements

- Composer >= 2.1
- PHP >= 8.0

## Preparation

```bash
# Clone repository
git clone https://github.com/CPS-IT/project-builder.git
cd project-builder

# Install dependencies
composer install
```

## Run linters

```bash
# All linters
composer lint

# Specific linters
composer lint:composer
composer lint:editorconfig
composer lint:json
composer lint:php
composer lint:yaml
```

## Run static code analysis

```bash
# All static code analyzers
composer sca

# Specific static code analyzers
composer sca:php
```

## Run tests

```bash
# All tests
composer test

# All tests with code coverage
composer test:coverage
```

### Test reports

Code coverage reports are written to `.build/coverage`. You can open the
last HTML report like follows:

```bash
open .build/coverage/html/index.html
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

## Submit a pull request

Once you have finished your work, please **submit a pull request** and describe
what you've done. Ideally, your PR references an issue describing the problem
you're trying to solve.

All described code quality tools are automatically executed on each pull request
for all currently supported PHP versions and Composer versions. Take a look at
the appropriate [workflows][3] to get a detailed overview.

[1]: https://ddev.readthedocs.io/en/stable/
[2]: .ddev/config.yaml
[3]: .github/workflows
