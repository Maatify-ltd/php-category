#!/usr/bin/env bash

set -euo pipefail

mode="${1:---phase-diagnostic}"
if [[ "$#" -gt 1 || ( "$mode" != "--phase-diagnostic" && "$mode" != "--require-security-qualified" ) ]]; then
    printf 'Usage: bash scripts/ci/check-composer-php-boundaries.sh [--phase-diagnostic|--require-security-qualified]\n' >&2
    exit 2
fi
release_status=PASS

project_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
probe_dir="$(mktemp -d)"
trap 'rm -rf -- "$probe_dir"' EXIT

probe_php_version() {
    local version="$1"
    local published_expected="$2"
    local release_expected="$3"
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
    if [[ "$mode" == "--phase-diagnostic" && "$result" != "$published_expected" ]]; then
        printf 'Expected %s under the existing published PHP constraint.\n' "$published_expected" >&2
        return 1
    fi
    if [[ "$result" != "$release_expected" ]]; then
        release_status="NOT PASS"
        printf 'Release boundary expects PHP %s to be %s.\n' "$version" "$release_expected"
    fi
}

printf 'Checking Composer PHP boundaries (%s).\n' "$mode"
probe_php_version 8.3.0 rejected rejected
probe_php_version 8.4.26 accepted rejected
probe_php_version 8.4.27 accepted accepted
probe_php_version 8.5.11 accepted rejected
probe_php_version 8.5.12 accepted accepted
probe_php_version 8.6.0 accepted rejected
probe_php_version 9.0.0 rejected rejected
printf 'Release PHP constraint qualification: %s\n' "$release_status"
if [[ "$mode" == "--require-security-qualified" ]]; then
    [[ "$release_status" == "PASS" ]]
fi
