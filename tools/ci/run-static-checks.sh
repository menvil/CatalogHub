#!/usr/bin/env bash

set -uo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$repository_root" || exit 1

log_directory="$(mktemp -d "${TMPDIR:-/tmp}/cataloghub-static.XXXXXX")"
trap 'rm -rf "$log_directory"' EXIT

composer test:architecture:contracts > "$log_directory/architecture.log" 2>&1 &
architecture_process="$!"

composer analyse -- --no-progress > "$log_directory/phpstan.log" 2>&1 &
phpstan_process="$!"

exit_code=0
if ! wait "$architecture_process"; then
    exit_code=1
fi
if ! wait "$phpstan_process"; then
    exit_code=1
fi

printf '\n[Architecture contracts]\n'
sed 's/^/  /' "$log_directory/architecture.log"
printf '\n[PHPStan]\n'
sed 's/^/  /' "$log_directory/phpstan.log"

exit "$exit_code"
