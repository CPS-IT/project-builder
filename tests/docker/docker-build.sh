#!/usr/bin/env bash
# shellcheck disable=SC2155
set -e

GREEN="\033[0;32m"
NC="\033[0m"

readonly root_path="$(cd -- "$(dirname "$0")/../.." >/dev/null 2>&1; pwd -P)"
readonly image_name="project-builder-test"

# Resolve parameters
cache=0
verbose=0
root_version=""
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
    *)
        root_version="$1"
        shift
        ;;
    esac
done

# Determine root version
if [ -z "$root_version" ]; then
    root_version="$(git describe --tags --abbrev=0)"
fi

# Build and tag new image
if [ "$cache" -eq 1 ]; then
    printf "Building image from cache... "
    docker build --cache-from "$image_name" -q -t "$image_name" --build-arg "PROJECT_BUILDER_VERSION=$root_version" "$root_path" >/dev/null
else
    printf "Building image... "
    docker build -q -t "$image_name" --build-arg "PROJECT_BUILDER_VERSION=$root_version" "$root_path" >/dev/null
fi
printf "${GREEN}Done${NC}\n"

# Run image
printf "Running image (with mounted volume)... "
set +e
tmpDir="$(mktemp)"
rm "$tmpDir"
output="$(docker run --rm -v "$tmpDir":/app "$image_name" 2>&1)"
set -e
printf "${GREEN}Done${NC}\n"

# Print output
if [ "$verbose" = 1 ]; then
    echo -e "\n============================================================\n"
    echo "$output"
    echo -e "\n============================================================"
fi

# Check output
expected="This command cannot be run in non-interactive mode."
if [[ $output != *"$expected"* ]]; then
    echo >&2 -e "\n🚨 Failed."
    exit 1
fi

# Run image
printf "Running image (without mounted volume)... "
set +e
output="$(docker run --rm "$image_name" 2>&1)"
set -e
printf "${GREEN}Done${NC}\n"

# Print output
if [ "$verbose" = 1 ]; then
    echo -e "\n============================================================\n"
    echo "$output"
    echo -e "\n============================================================"
fi

# Check output
expected="It seems like the /app directory is not mounted to your host device."
if [[ $output != *"$expected"* ]]; then
    echo >&2 -e "\n🚨 Failed."
    exit 1
fi

echo -e "\n✅ Success."
