#!/usr/bin/env bash

set -euo pipefail

project_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$project_root"

install_schema=0
if [[ "${1:-}" == "--install-schema" ]]; then
    install_schema=1
    shift
fi
if [[ "$#" -eq 0 ]]; then
    printf 'Usage: bash scripts/ci/with-category-mysql.sh [--install-schema] command [args...]\n' >&2
    exit 2
fi
docker compose version >/dev/null
docker info >/dev/null
php -v >/dev/null

CATEGORY_MYSQL_PASSWORD="$(php -r 'echo bin2hex(random_bytes(16));')"
CATEGORY_MYSQL_ROOT_PASSWORD="$(php -r 'echo bin2hex(random_bytes(16));')"
export CATEGORY_MYSQL_PASSWORD CATEGORY_MYSQL_ROOT_PASSWORD
project="category-$(php -r 'echo bin2hex(random_bytes(8));')"
compose=(docker compose --file "$project_root/tests/compose.integration.yaml" --project-name "$project")

cleanup() {
    local status=$?
    trap - EXIT
    if ! "${compose[@]}" down --volumes --remove-orphans; then
        printf 'Failed to remove disposable MySQL project %s.\n' "$project" >&2
        if [[ "$status" -eq 0 ]]; then
            status=1
        fi
    fi
    exit "$status"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

"${compose[@]}" up --detach --wait --wait-timeout 120 mysql
endpoint="$("${compose[@]}" port mysql 3306)"
if [[ ! "$endpoint" =~ ^127\.0\.0\.1:([0-9]+)$ ]]; then
    printf 'Could not identify the disposable MySQL loopback endpoint.\n' >&2
    exit 1
fi

export CATEGORY_TEST_DSN="mysql:host=127.0.0.1;port=${BASH_REMATCH[1]};dbname=category_verification;charset=utf8mb4"
export CATEGORY_TEST_COMPOSE_PROJECT="$project"
export CATEGORY_TEST_DB_USER=category_test
export CATEGORY_TEST_DB_PASSWORD="$CATEGORY_MYSQL_PASSWORD"
export CATEGORY_STANDALONE_DSN="$CATEGORY_TEST_DSN"
export CATEGORY_STANDALONE_DB_USER="$CATEGORY_TEST_DB_USER"
export CATEGORY_STANDALONE_DB_PASSWORD="$CATEGORY_TEST_DB_PASSWORD"

if [[ "$install_schema" -eq 1 ]]; then
    "${compose[@]}" exec -T mysql sh -ec \
        'MYSQL_PWD="$MYSQL_PASSWORD" mysql --protocol=TCP -h 127.0.0.1 -u "$MYSQL_USER" "$MYSQL_DATABASE"' \
        < "$project_root/schema/category.sql"
fi

"$@"
