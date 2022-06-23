#!/usr/bin/env sh
set -e

composer create-project "$@" \
    --repository='{"type":"artifact","url":"/project-builder/artifacts"}' \
    --prefer-dist \
    --no-dev \
    cpsit/project-builder \
    /app
