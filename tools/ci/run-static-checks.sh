#!/usr/bin/env bash

set -euo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$repository_root" || exit 1

log_directory="$(mktemp -d "${TMPDIR:-/tmp}/cataloghub-static.XXXXXX")"
trap 'rm -rf "$log_directory"' EXIT

check_keys=("architecture" "phpstan")
check_names=("Architecture contracts" "PHPStan")
check_commands=("composer test:architecture:contracts" "composer analyse -- --no-progress")
process_ids=()
reported_checks=(0 0)

for index in "${!check_keys[@]}"; do
    (
        check_exit_code=0
        bash -c "${check_commands[$index]}" > "$log_directory/${check_keys[$index]}.log" 2>&1 || check_exit_code="$?"
        printf '%s\n' "$check_exit_code" > "$log_directory/${check_keys[$index]}.status.tmp"
        mv "$log_directory/${check_keys[$index]}.status.tmp" "$log_directory/${check_keys[$index]}.status"
        exit "$check_exit_code"
    ) &
    process_ids+=("$!")
done

exit_code=0
remaining_checks="${#process_ids[@]}"
while (( remaining_checks > 0 )); do
    completion_observed=0

    for index in "${!process_ids[@]}"; do
        status_file="$log_directory/${check_keys[$index]}.status"
        if (( reported_checks[index] == 1 )); then
            continue
        fi

        if [[ ! -f "$status_file" ]]; then
            if kill -0 "${process_ids[$index]}" 2>/dev/null; then
                continue
            fi

            wait "${process_ids[$index]}" || true
            reported_checks[index]=1
            remaining_checks=$((remaining_checks - 1))
            completion_observed=1
            exit_code=1
            printf '\n[%s: FAIL]\n' "${check_names[$index]}"
            printf '  Worker exited without publishing its status.\n'
            if [[ -f "$log_directory/${check_keys[$index]}.log" ]]; then
                sed 's/^/  /' "$log_directory/${check_keys[$index]}.log"
            fi
            continue
        fi

        check_exit_code="$(<"$status_file")"
        wait "${process_ids[$index]}" || true
        reported_checks[index]=1
        remaining_checks=$((remaining_checks - 1))
        completion_observed=1

        printf '\n[%s: %s]\n' "${check_names[$index]}" "$([[ "$check_exit_code" -eq 0 ]] && printf PASS || printf FAIL)"
        sed 's/^/  /' "$log_directory/${check_keys[$index]}.log"

        if (( check_exit_code != 0 )); then
            exit_code=1
        fi
    done

    if (( completion_observed == 0 && remaining_checks > 0 )); then
        sleep 0.1
    fi
done

exit "$exit_code"
