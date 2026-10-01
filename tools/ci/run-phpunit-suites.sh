#!/usr/bin/env bash

set -euo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$repository_root" || exit 1

php artisan config:clear --ansi

config_cache_path="${APP_CONFIG_CACHE:-$repository_root/bootstrap/cache/config.php}"
if [[ "$config_cache_path" != /* ]]; then
    config_cache_path="$repository_root/$config_cache_path"
fi
if [[ -e "$config_cache_path" ]]; then
    printf 'Configuration cache still exists after config:clear: %s\n' "$config_cache_path" >&2
    exit 1
fi

if (( $# > 0 )); then
    printf 'Ignoring additional arguments; composer test always runs the four canonical suites.\n' >&2
fi

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
reported_suites=(0 0 0 0)

run_suite() {
    local suite_key="$1"
    local suite_name="$2"
    local command=(php vendor/bin/phpunit --testsuite "$suite_name" --do-not-cache-result)
    if [[ -n "$result_directory" ]]; then
        command+=(--log-junit "$result_directory/$suite_key.xml")
    fi

    local compiled_view_directory="$log_directory/views/$suite_key"
    mkdir -p "$compiled_view_directory"

    VIEW_COMPILED_PATH="$compiled_view_directory" "${command[@]}"
}

for index in "${!suite_keys[@]}"; do
    (
        suite_exit_code=0
        run_suite "${suite_keys[$index]}" "${suite_names[$index]}" \
            > "$log_directory/${suite_keys[$index]}.log" 2>&1 || suite_exit_code="$?"
        printf '%s\n' "$suite_exit_code" > "$log_directory/${suite_keys[$index]}.status.tmp"
        mv "$log_directory/${suite_keys[$index]}.status.tmp" "$log_directory/${suite_keys[$index]}.status"
        exit "$suite_exit_code"
    ) &
    process_ids+=("$!")
done

exit_code=0
remaining_suites="${#process_ids[@]}"
while (( remaining_suites > 0 )); do
    completion_observed=0

    for index in "${!process_ids[@]}"; do
        status_file="$log_directory/${suite_keys[$index]}.status"
        if (( reported_suites[index] == 1 )); then
            continue
        fi

        if [[ ! -f "$status_file" ]]; then
            if kill -0 "${process_ids[$index]}" 2>/dev/null; then
                continue
            fi

            wait "${process_ids[$index]}" || true
            reported_suites[index]=1
            remaining_suites=$((remaining_suites - 1))
            completion_observed=1
            exit_code=1
            printf '\n[FAIL] %s\n' "${suite_names[$index]}"
            printf '  Worker exited without publishing its status.\n'
            if [[ -f "$log_directory/${suite_keys[$index]}.log" ]]; then
                sed 's/^/  /' "$log_directory/${suite_keys[$index]}.log"
            fi
            continue
        fi

        suite_exit_code="$(<"$status_file")"
        wait "${process_ids[$index]}" || true
        reported_suites[index]=1
        remaining_suites=$((remaining_suites - 1))
        completion_observed=1

        if (( suite_exit_code == 0 )); then
            printf '[PASS] %s\n' "${suite_names[$index]}"
        else
            printf '\n[FAIL] %s\n' "${suite_names[$index]}"
            sed 's/^/  /' "$log_directory/${suite_keys[$index]}.log"
            exit_code=1
        fi
    done

    if (( completion_observed == 0 && remaining_suites > 0 )); then
        sleep 0.1
    fi
done

exit "$exit_code"
