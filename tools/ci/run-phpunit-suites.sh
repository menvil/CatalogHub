#!/usr/bin/env bash

set -uo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$repository_root" || exit 1

php artisan config:clear --ansi

result_directory=""
if [[ -n "${PHPUNIT_RESULT_DIR:-}" ]]; then
    if [[ "$PHPUNIT_RESULT_DIR" = /* ]]; then
        result_directory="$PHPUNIT_RESULT_DIR"
    else
        result_directory="$repository_root/$PHPUNIT_RESULT_DIR"
    fi
    mkdir -p "$result_directory"
fi

log_directory="$(mktemp -d "${TMPDIR:-/tmp}/cataloghub-phpunit.XXXXXX")"
trap 'rm -rf "$log_directory"' EXIT

suite_keys=("unit" "legacy-unit" "feature" "browser-contract")
suite_names=("Unit" "Legacy Unit" "Feature" "Browser")
process_ids=()

run_suite() {
    local suite_key="$1"
    local suite_name="$2"
    shift 2

    local command=(php vendor/bin/phpunit --testsuite "$suite_name" --do-not-cache-result)
    if [[ -n "$result_directory" ]]; then
        command+=(--log-junit "$result_directory/$suite_key.xml")
    fi
    command+=("$@")

    "${command[@]}"
}

for index in "${!suite_keys[@]}"; do
    run_suite "${suite_keys[$index]}" "${suite_names[$index]}" "$@" \
        > "$log_directory/${suite_keys[$index]}.log" 2>&1 &
    process_ids+=("$!")
done

exit_code=0
for index in "${!process_ids[@]}"; do
    if ! wait "${process_ids[$index]}"; then
        exit_code=1
    fi

    printf '\n[%s]\n' "${suite_names[$index]}"
    sed 's/^/  /' "$log_directory/${suite_keys[$index]}.log"
done

exit "$exit_code"
