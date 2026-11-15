# Package archive verification

Run the structural archive check from the repository checkout with Git,
Composer, Bash, and tar available. Pass the exact candidate commit approved
for verification.

```bash
candidate=$(git rev-parse HEAD)
bash scripts/ci/check-package-archives.sh "$candidate"
```

The check builds Git and Composer archives from that commit and compares each
archive to the tracked distribution tree, including every file under src/,
the schema, and the consumer documentation. The Composer input also includes
dummy development files to verify that local vendor/, composer.lock, and
.env never enter the archive. The command reports the full candidate SHA for
each passing comparison and exits nonzero if a file is missing, additional,
or different. Record the SHA, both command results, and the CI run alongside
release qualification evidence.

For release qualification, also review README identity and installation,
CHANGELOG allocation and its Unreleased boundary, SECURITY lifecycle and
support statements, and Composer requirements against the intended exact
version. Perform that review on the same SHA and record the outcome
separately.

## Published artifact verification

After `v1.0.0-rc.2` is published, run this verification in Bash with
Composer, curl, and jq available. Set `CATEGORY_RC2_RELEASE_SHA` to the
full immutable commit SHA from the approved release evidence before running
the command. The temporary Composer root keeps `minimum-stability: stable`
and explicitly allows the required exact Persistence release candidate.

```bash
(
    set -eu
    version=v1.0.0-rc.2
    reference=${CATEGORY_RC2_RELEASE_SHA:?Set the exact qualified RC.2 SHA}
    workspace=$(mktemp -d) || exit 1
    mkdir -p "$workspace"/{project,home,composer,cache,tmp}
    printf 'Verification workspace: %s\n' "$workspace"
    cd "$workspace/project"

    clean_run() {
        env -i PATH="$PATH" HOME="$workspace/home" TMPDIR="$workspace/tmp" \
            COMPOSER_HOME="$workspace/composer" COMPOSER_CACHE_DIR="$workspace/cache" "$@"
    }
    verify_composer() {
        clean_run composer --no-interaction --no-plugins --no-scripts "$@"
    }

    cat > composer.json <<'JSON'
{"minimum-stability":"stable","config":{"preferred-install":"dist"}}
JSON
    verify_composer --version
    verify_composer config preferred-install
    verify_composer config repositories

    clean_run curl -q --fail --silent --show-error \
        https://repo.packagist.org/p2/maatify/php-category.json > "$workspace/registry.json"
    jq -e --arg version "$version" --arg reference "$reference" '
        .packages["maatify/php-category"][] | select(.version == $version)
        | select(.dist.reference == $reference) | {version, dist, source}
    ' "$workspace/registry.json"

    verify_composer require --prefer-install=dist "maatify/php-category:$version" \
        maatify/php-persistence:1.0.0-rc.1@RC
    jq -e --arg version "$version" --arg reference "$reference" '
        .packages[]
        | select(.name == "maatify/php-category" and .version == $version)
        | select(."installation-source" == "dist" and .dist.reference == $reference)
        | {name, version, "installation-source", "install-path", dist, source}
    ' vendor/composer/installed.json
    jq -e '
        .packages[]
        | select(.name == "maatify/php-persistence" and .version == "v1.0.0-rc.1")
        | select(."installation-source" == "dist"
            and .dist.reference == "34c3b874525d36ae064c162cdd553bc75d18772b")
        | {name, version, "installation-source", "install-path", dist, source}
    ' vendor/composer/installed.json
    test -d vendor/maatify/php-category
    printf 'Installed package directory: %s\n' "$PWD/vendor/maatify/php-category"

    find vendor/maatify/php-category -type f | LC_ALL=C sort

    audit_status=0
    verify_composer audit --abandoned=fail || audit_status=$?
    printf 'Strict Composer audit exit status: %s\n' "$audit_status"
    exit "$audit_status"
)
```

Save the output with the publication evidence. The isolated home and cache
start empty, and the clean environment clears inherited repository,
authentication, platform, install-mode, and security-policy overrides. The
project uses the public Packagist configuration and verifies the observed
installation mode against the intended SHA. Stop on missing or ambiguous
resolution evidence or a source fallback.

Use the printed workspace and installed directory paths to inspect the package
against the exact tag and qualification evidence. Check its `composer.json`,
runtime tree, schema, README, CHANGELOG, SECURITY, Package Reference, Usage Guide,
examples, and other required files. Record the content and release presentation
result separately from the strict Composer audit. Run the appropriate
published Consumer Verification Harness and record its result separately.
The standalone CI harness verifies the checkout installed from a temporary VCS
repository. Remove the temporary workspace once all evidence has been recorded.

### Published v1.0.0-rc.1

Packagist resolved `v1.0.0-rc.1` through `dist` to
`57d160f7850a21b6908f343085e7dac644b87e86`. Composer installed it at
`vendor/maatify/php-category` from the [GitHub ZIP distribution](https://api.github.com/repos/Maatify-ltd/php-category/zipball/57d160f7850a21b6908f343085e7dac644b87e86).
The clean project used PHP 8.4.27 and resolved the artifact's legacy
`maatify/persistence` requirement to `v1.4.0`. A runtime smoke using the
installed schema and public facade passed on MySQL 8.4.9 and rolled back the
writes across all five package tables.

**Published Consumer Verification Harness: PASS.** The unmodified harness
from tag `v1.0.0-rc.1` passed in two separate clean Composer projects, each
installing the exact release through `dist`. Both runs used PHP 8.4.27 and
MySQL 8.4.9, verified 131 public types, and left no package tables or triggers
after cleanup. [Published RC.1 consumer verification in PR #69](https://github.com/Maatify-ltd/php-category/pull/69#issue-6403318206).

**Strict Composer audit: NOT PASS.** `composer audit --abandoned=fail` reported
zero security advisories and exited with status 1 because the tagged dependency
resolved to the abandoned `maatify/persistence` v1.4.0.

**Published Artifact Verification: NOT PASS against the current pinned
standard.** The RC.1 archive omits the required root `llms.txt` and
`examples/`. Its usage guide is at the legacy `docs/USAGE_GUIDE.md` path
instead of `docs/guides/USAGE_GUIDE.md`.
