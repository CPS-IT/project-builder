#!/usr/bin/env bash
# shellcheck disable=SC2155
set -e

readonly root_path="$(cd -- "$(dirname "$0")/../.." >/dev/null 2>&1; pwd -P)"
readonly image_name="project-builder-test"
readonly expected="This command cannot be run in non-interactive mode."

# Check verbose mode
if [ "-v" = "$1" ] || [ "--verbose" = "$1" ]; then
    readonly verbose=1
fi

# Build and tag new image
printf "Building image... "
docker build -q -t "$image_name" "$root_path" >/dev/null
printf "\033[0;32mDone\033[0m\n"

# Run image
printf "Running image... "
set +e; readonly output="$(docker run --rm "$image_name" 2>&1)"; set -e
printf "\033[0;32mDone\033[0m\n"

# Remove image
printf "Removing image... "
docker rmi "$image_name" >/dev/null
printf "\033[0;32mDone\033[0m\n"

# Print output
if [ "$verbose" = 1 ]; then
    echo -e "\n============================================================\n"
    echo "$output"
    echo -e "\n============================================================"
fi

# Check output
if [[ $output != *"$expected"* ]]; then
    >&2 echo -e "\n🚨 Failed."
    exit 1
fi

echo -e "\n✅ Success."
