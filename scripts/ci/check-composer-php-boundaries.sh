#!/usr/bin/env bash

set -euo pipefail

project_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
probe_dir="$(mktemp -d)"
trap 'rm -rf -- "$probe_dir"' EXIT

probe_php_version() {
    local version="$1"
    local expected="$2"
    local output
    local result

    php -r '
        $manifest = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
        $constraint = $manifest["require"]["php"] ?? null;
        if (!is_string($constraint) || $constraint === "") {
            fwrite(STDERR, "No valid require.php constraint in package composer.json.\n");
            exit(1);
        }

        echo json_encode([
            "name" => "maatify/category-php-boundary-probe",
            "version" => "1.0.0",
            "require" => ["php" => $constraint],
            "repositories" => ["packagist.org" => false],
            "config" => ["platform" => ["php" => $argv[2]]],
        ], JSON_THROW_ON_ERROR);
    ' "$project_root/composer.json" "$version" > "$probe_dir/composer.json"

    if output="$(composer --working-dir="$probe_dir" update --dry-run --no-interaction --no-progress --no-plugins --no-scripts --no-audit 2>&1)"; then
        result=accepted
    elif [[ "$output" == *"Root composer.json requires php "*"your php version ($version"* ]]; then
        result=rejected
    else
        printf '%s\n' "$output" >&2
        return 1
    fi

    printf 'PHP %s: %s by Composer\n' "$version" "$result"
    if [[ "$result" != "$expected" ]]; then
        printf 'Expected %s under the existing published PHP constraint.\n' "$expected" >&2
        return 1
    fi
}

printf '%s\n' 'Checking the existing published Composer PHP compatibility contract.'
probe_php_version 8.3.0 rejected
probe_php_version 8.4.26 accepted
probe_php_version 8.4.27 accepted
probe_php_version 8.5.11 accepted
probe_php_version 8.5.12 accepted
probe_php_version 8.6.0 accepted
probe_php_version 9.0.0 rejected
printf '%s\n' 'The published constraint admits below-floor patches and PHP 8.6.0; these are not CI-qualified interpreters.'
