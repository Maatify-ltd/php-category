#!/usr/bin/env bash

set -euo pipefail

candidate="$1"
target="$2"
work="$3"
persistence_version=1.0.0-rc.1
persistence_reference=34c3b874525d36ae064c162cdd553bc75d18772b
consumer_category_constraint=''

fail() {
    printf 'Prepared release verification failed: %s\n' "$*" >&2
    exit 1
}

verify_presentation() {
    local artifact="$1"
    local changelog="$artifact/CHANGELOG.md"
    local readme="$artifact/README.md"
    local version_badge
    local install_text
    local headings
    local escaped_target="${target//-/--}"

    headings="$(awk '/^## \[/{print; if (++headings == 2) exit}' "$changelog")"
    if [[ "$headings" != "## [$target]" && "$headings" != "## [$target]"$'\n'*
        && "$headings" != "## [Unreleased]"$'\n'"## [$target]"
    ]]; then
        fail 'CHANGELOG must start with the undated candidate, optionally after [Unreleased].'
    fi
    if grep -Fq "[$target]:" "$changelog"; then
        fail 'CHANGELOG links to the unpublished exact candidate.'
    fi
    [[ "$(grep -Fxc "## [$target]" "$changelog")" == 1 ]] \
        || fail 'CHANGELOG repeats or omits the exact undated candidate.'
    version_badge="$(grep -F '[![Version](' "$readme")" || fail 'README missing the Version badge.'
    [[ "$version_badge" != *$'\n'*
        && "$version_badge" == '[![Version](https://img.shields.io/badge/Version-v'"$escaped_target"-*')](CHANGELOG.md'*
    ]] || fail 'README Version badge must show the exact candidate and link to the local changelog.'
    if grep -Fq '[![Install](' "$readme"; then
        fail 'README shows an Install badge for an unpublished candidate.'
    fi
    install_text="$(tr '\n\\\047\042' '    ' < "$readme" | tr -s '[:space:]' ' ')"
    if [[ "$install_text" == *"composer require maatify/php-category:$target@RC maatify/php-persistence:$persistence_version@RC "* ]]; then
        consumer_category_constraint="$target@RC"
    elif [[ "$install_text" == *"composer require maatify/php-category:$target maatify/php-persistence:$persistence_version@RC "* ]]; then
        consumer_category_constraint="$target"
    else
        fail 'README does not show the exact scoped Category and Persistence RC install command.'
    fi
    grep -F "| \`v$target\` |" "$artifact/SECURITY.md" | grep -F 'Prepared Release Candidate' >/dev/null \
        || fail 'SECURITY does not list the exact candidate as prepared.'
    grep -Fq '| Stable release lines | None |' "$artifact/SECURITY.md" \
        || fail 'SECURITY must preserve the absence of a Stable support line.'
    jq -e --arg persistence "$persistence_version" '
        .name == "maatify/php-category" and .type == "library" and .license == "proprietary"
        and (has("version") | not)
        and (.require | (has("maatify/persistence") | not)
            and .["maatify/php-persistence"] == (">=" + $persistence + " <=" + $persistence))
    ' "$artifact/composer.json" >/dev/null \
        || fail 'Composer manifest must identify the proprietary VCS-versioned library and its exact Persistence RC.'
}

for format in git composer; do
    verify_presentation "$work/$format"
    printf '%s archive presentation passed for %s.\n' "$format" "$target"
done

consumer="$work/clean-consumer"
mkdir -p "$consumer" "$work/clean-home" "$work/clean-tmp" "$work/clean-composer-home" "$work/clean-composer-cache"
isolated=(
    env -i
    "PATH=$PATH"
    "HOME=$work/clean-home"
    "TMPDIR=$work/clean-tmp"
    "COMPOSER_HOME=$work/clean-composer-home"
    "COMPOSER_CACHE_DIR=$work/clean-composer-cache"
)

# Synthetic local Composer metadata exposes the exact candidate TAR as a dist.
# Registry resolution stays enabled for the real Persistence dependency.
jq --arg target "$target" --arg sha "$candidate" --arg archive "file://$work/composer.tar" '
    {
        name: "maatify/category-release-verification",
        type: "project",
        "minimum-stability": "stable",
        "prefer-stable": true,
        repositories: [{
            type: "package",
            package: (. + {
                version: $target,
                dist: {type: "tar", url: $archive, reference: $sha}
            })
        }]
    }
' "$work/composer/composer.json" > "$consumer/composer.json"

"${isolated[@]}" composer --working-dir="$consumer" --no-interaction --no-plugins --no-scripts \
    require --prefer-dist --no-progress \
    "maatify/php-category:$consumer_category_constraint" "maatify/php-persistence:$persistence_version@RC"
"${isolated[@]}" composer --working-dir="$consumer" --no-interaction --no-plugins --no-scripts check-platform-reqs --no-dev

jq -e --arg category "$consumer_category_constraint" --arg persistence "$persistence_version@RC" '
    ."minimum-stability" == "stable"
    and .require == { "maatify/php-category": $category, "maatify/php-persistence": $persistence }
' "$consumer/composer.json" >/dev/null \
    || fail 'Clean Composer probe did not retain only the two scoped RC allowances.'
jq -e --arg target "$target" --arg sha "$candidate" --arg persistence "v$persistence_version" \
    --arg persistence_ref "$persistence_reference" '
    .packages as $packages
    | [$packages[] | select(
        (.name == "maatify/php-category" and .version == $target
            and ."installation-source" == "dist" and .dist.type == "tar" and .dist.reference == $sha)
        or (.name == "maatify/php-persistence" and .version == $persistence
            and ."installation-source" == "dist" and .dist.reference == $persistence_ref)
    ) | {name, version, "installation-source", dist}]
    | select(length == 2 and all($packages[]; .name != "maatify/persistence"))
' "$consumer/vendor/composer/installed.json" \
    || fail 'Candidate or Persistence dist identity differs from the approved version and reference.'
diff -qr "$work/composer" "$consumer/vendor/maatify/php-category"
(
    cd "$consumer"
    "${isolated[@]}" php -r '
        require "vendor/autoload.php";
        $factory = new ReflectionClass(\Maatify\Category\Factory\CategoryFactory::class);
        if ($factory->getFileName() !== realpath("vendor/maatify/php-category/src/Factory/CategoryFactory.php")) {
            exit(1);
        }
    '
)
printf 'Clean Composer candidate dist probe passed for %s at %s (synthetic local Category metadata; published Persistence dist).\n' "$target" "$candidate"
