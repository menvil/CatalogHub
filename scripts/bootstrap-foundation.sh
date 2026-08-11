#!/usr/bin/env bash

set -euo pipefail

script_directory="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
project_directory="$(dirname "$script_directory")"

cd "$project_directory"

reject_environment() {
    printf '%s\n' 'Foundation bootstrap refuses to run outside local or testing.' >&2
    exit 1
}

# Reject an explicitly unsafe environment before the missing .env bootstrap.
case "${APP_ENV:-}" in
    ''|local|testing) ;;
    *) reject_environment ;;
esac

if [[ ! -f .env ]]; then
    cp .env.example .env
fi

environment_output="$(php artisan env --no-ansi)"

case "$environment_output" in
    *"[local]"*|*"[testing]"*) ;;
    *) reject_environment ;;
esac

if [[ -z "${APP_KEY:-}" ]] && ! grep -Eq '^APP_KEY=.+$' .env; then
    php artisan key:generate --force --no-interaction
fi
php artisan migrate:fresh --force --no-interaction --seeder='Database\Seeders\FoundationDemoSeeder'
php artisan storage:link --force --no-interaction
npm ci
npm run build
php artisan foundation:verify --no-interaction
route_output="$(php artisan route:list --path=admin --except-vendor --no-ansi)"

if [[ "$route_output" == *"doesn't have any routes"* ]]; then
    printf '%s\n' 'Foundation bootstrap could not find admin routes.' >&2
    exit 1
fi

printf '%s\n' 'Foundation bootstrap completed successfully.'
