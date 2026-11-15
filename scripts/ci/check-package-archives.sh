#!/usr/bin/env bash

set -euo pipefail

usage() {
    printf 'Usage: %s <candidate-commit> [--release-candidate <version>|--verify-prepared]\n' "$0" >&2
    exit 2
}

release_target=''
verify_prepared=0
case "${2:-}" in
    '') [[ $# == 1 ]] || usage ;;
    --release-candidate)
        [[ $# == 3 && "$3" =~ ^[0-9]+\.[0-9]+\.[0-9]+-rc\.[0-9]+$ ]] || usage
        release_target="$3"
        ;;
    --verify-prepared)
        [[ $# == 2 ]] || usage
        verify_prepared=1
        ;;
    *) usage ;;
esac

repository=$(git rev-parse --show-toplevel)
candidate=$(git -C "$repository" rev-parse --verify --end-of-options "$1^{commit}")
work=$(mktemp -d)
trap 'rm -rf -- "$work"' EXIT
mkdir -p "$work/source" "$work/expected/docs" "$work/git" "$work/composer"

# Populate the candidate from Git without changing the repository index or checkout.
GIT_INDEX_FILE="$work/index" git -C "$repository" read-tree "$candidate"
GIT_INDEX_FILE="$work/index" git -C "$repository" checkout-index --all --prefix="$work/source/"

# Use Git's candidate tree as the reference for every distributed file, including src/.
required_paths=(
    CATEGORY_PACKAGE_REFERENCE.md
    CHANGELOG.md
    CODE_OF_CONDUCT.md
    CONTRIBUTING.md
    LICENSE
    README.md
    SECURITY.md
    composer.json
    docs/decisions
    docs/guides
    examples
    llms.txt
    schema
    src
)
required_files=(
    docs/decisions/DECISIONS_INDEX.md
    docs/guides/PACKAGE_ARCHIVE_VERIFICATION.md
    docs/guides/USAGE_GUIDE.md
    examples/README.md
    examples/category-workflow.php
    schema/category.sql
)
for path in "${required_paths[@]}"; do
    cp -R "$work/source/$path" "$work/expected/$path"
done
for file in "${required_files[@]}"; do
    test -f "$work/expected/$file" || {
        printf 'Candidate is missing required distribution file: %s\n' "$file" >&2
        exit 1
    }
done

if [[ "$verify_prepared" == 1 ]]; then
    release_target="$(sed -nE 's/^## \[([0-9]+\.[0-9]+\.[0-9]+-rc\.[0-9]+)\]$/\1/p' "$work/expected/CHANGELOG.md")"
    if [[ "$release_target" == *$'\n'* ]]; then
        printf 'Multiple undated release candidate targets in %s.\n' "$candidate" >&2
        exit 1
    fi
fi

git -C "$repository" archive --format=tar --output="$work/git.tar" "$candidate"
tar -xf "$work/git.tar" -C "$work/git"

# Check Composer's local archiver with files left by a normal development checkout.
mkdir "$work/source/vendor"
touch "$work/source/vendor/autoload.php" "$work/source/.env"
printf '%s\n' '{"packages":[],"packages-dev":[]}' > "$work/source/composer.lock"
(
    cd "$work/source"
    COMPOSER_ROOT_VERSION="${release_target:-dev-main}" composer --no-interaction --no-plugins --no-scripts archive \
        --format=tar --dir="$work" --file=composer
)
tar -xf "$work/composer.tar" -C "$work/composer"

# Any missing, extra, or changed file fails independently for each archive.
for format in git composer; do
    if ! diff -qr "$work/expected" "$work/$format"; then
        printf '%s archive does not match the distribution tree of %s\n' "$format" "$candidate" >&2
        exit 1
    fi
    printf '%s archive matches the complete distribution tree of %s\n' "$format" "$candidate"
done

if [[ -n "$release_target" ]]; then
    printf 'Verifying prepared Category %s at %s.\n' "$release_target" "$candidate"
    bash "$repository/scripts/ci/check-prepared-category-release.sh" \
        "$candidate" "$release_target" "$work"
fi
