#!/usr/bin/env bash
# shellcheck disable=SC2155
set -e

readonly root_path="$(cd -- "$(dirname "$0")/../.." >/dev/null 2>&1; pwd -P)"
readonly image_name="project-builder-test"
readonly expected="This command cannot be run in non-interactive mode."

# Resolve parameters
cache=0
verbose=0
while [[ $# -gt 0 ]]; do
    key="$1"
    case ${key} in
    -c | --cache)
        cache=1
        shift
        ;;
    -v | --verbose)
        verbose=1
        shift
        ;;
    esac
done

# Build and tag new image
if [ "$cache" -eq 1 ]; then
    printf "Building image from cache... "
    docker build --cache-from "$image_name" -q -t "$image_name" "$root_path" >/dev/null
else
    printf "Building image... "
    docker build -q -t "$image_name" "$root_path" >/dev/null
fi
printf "\033[0;32mDone\033[0m\n"

# Run image
printf "Running image... "
set +e
readonly output="$(docker run --rm "$image_name" 2>&1)"
set -e
printf "\033[0;32mDone\033[0m\n"

# Print output
if [ "$verbose" = 1 ]; then
    echo -e "\n============================================================\n"
    echo "$output"
    echo -e "\n============================================================"
fi

# Check output
if [[ $output != *"$expected"* ]]; then
    echo >&2 -e "\n🚨 Failed."
    exit 1
fi

echo -e "\n✅ Success."
