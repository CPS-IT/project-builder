FROM composer:2.6 AS composer
LABEL maintainer="Elias Häußler <e.haeussler@familie-redlich.de>"

FROM php:8.3-alpine
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
    && composer global config repositories.project-builder path /project-builder \
    && composer global config allow-plugins.cpsit/project-builder true \
    && composer global require cpsit/project-builder:$PROJECT_BUILDER_VERSION

WORKDIR /app
ENTRYPOINT ["/project-builder/docker-entrypoint.sh"]
