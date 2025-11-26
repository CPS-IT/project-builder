#!/usr/bin/env sh
set -e

RED="\033[0;31m"
YELLOW="\033[0;33m"
CYAN="\033[1;36m"
NC="\033[0m"

readonly targetDir="/app"

if ! cat /proc/mounts | grep "$targetDir" >/dev/null 2>&1; then
    printf "${YELLOW}%s${NC}\n" "It seems like the ${targetDir} directory is not mounted to your host device."
    printf "${YELLOW}%s${NC}\n" "Make sure to pass the -v flag to your \`docker run\` command."
    printf "${YELLOW}%s${NC}\n" "You probably won't be able to receive the generated project files otherwise."
    printf "Continue anyway? [y/${CYAN}N${NC}] "

    if ! read -r answer; then
        exit 1
    fi

    case $answer in
        y*|Y*) printf "\n";;
        *) printf "${RED}Cancelled.${NC}\n"; exit 1;;
    esac
fi

composer project:create "$targetDir" "$@"
