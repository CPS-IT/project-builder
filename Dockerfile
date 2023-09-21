FROM composer:2.6 AS composer
LABEL maintainer="Elias Häußler <e.haeussler@familie-redlich.de>"

FROM php:8.2-alpine
COPY --from=composer /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PROJECT_BUILDER_EXECUTOR=docker

ADD . /project-builder
WORKDIR /project-builder

# Install Git and php-zip extension
RUN apk update \
    && apk add git libzip-dev zip \
    && docker-php-ext-install zip

# Build project-builder artifact for later use in entrypoint
ARG PROJECT_BUILDER_VERSION=0.0.0
RUN composer config version "$PROJECT_BUILDER_VERSION" \
    && composer update --prefer-dist --no-dev --no-install --ignore-platform-req=ext-sockets \
    && git add -f composer.lock \
    && mkdir artifacts \
    && git stash \
    && git archive --format=tar --output="artifacts/project-builder-$PROJECT_BUILDER_VERSION.tar" "stash@{0}" \
    && git stash pop

WORKDIR /app
ENTRYPOINT ["/project-builder/docker-entrypoint.sh"]
