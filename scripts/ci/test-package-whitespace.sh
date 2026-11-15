#!/usr/bin/env bash

set -euo pipefail

check_script="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)/check-package-whitespace.sh"
fixture=$(mktemp -d)
trap 'rm -rf -- "$fixture"' EXIT

export GIT_CONFIG_NOSYSTEM=1 GIT_CONFIG_GLOBAL=/dev/null
cd "$fixture"
git init --quiet
git config user.name 'Whitespace Verification'
git config user.email verification@example.invalid
git commit --quiet --allow-empty -m base
base=$(git rev-parse HEAD)

printf 'line with trailing space \n' > fixture.md
git add fixture.md
git commit --quiet -m dirty
dirty=$(git rev-parse HEAD)
uppercase_dirty=$(printf '%s' "$dirty" | tr '[:lower:]' '[:upper:]')

printf 'clean line\n' > fixture.md
git add fixture.md
git commit --quiet -m clean
clean=$(git rev-parse HEAD)
blob=$(git rev-parse HEAD:fixture.md)

git -c advice.detachedHead=false switch --quiet --detach "$base"
git commit --quiet --allow-empty -m sibling
sibling=$(git rev-parse HEAD)

expect_result() {
    local description="$1" expected_status="$2" expected_text="$3" output status=0
    shift 3
    output=$(bash "$check_script" "$@" 2>&1) || status=$?

    if [[ "$status" != "$expected_status" || "$output" != *"$expected_text"* ]]; then
        printf 'FAIL %s (expected status %s, got %s)\n%s\n' "$description" "$expected_status" "$status" "$output" >&2
        exit 1
    fi
    printf 'PASS %s\n' "$description"
}

zero_sha=0000000000000000000000000000000000000000
expect_result 'manual missing base' 1 'full base commit SHA' workflow_dispatch '' "$dirty"
expect_result 'manual missing commit' 1 'existing commit' workflow_dispatch "$zero_sha" "$dirty"
expect_result 'manual blob base' 1 'existing commit' workflow_dispatch "$blob" "$dirty"
expect_result 'manual self-comparison on dirty HEAD' 1 'strict ancestor' workflow_dispatch "$dirty" "$dirty"
expect_result 'manual equivalent uppercase self-comparison' 1 'strict ancestor' workflow_dispatch "$uppercase_dirty" "$dirty"
expect_result 'manual sibling base' 1 'strict ancestor' workflow_dispatch "$sibling" "$dirty"
expect_result 'manual descendant base' 1 'strict ancestor' workflow_dispatch "$clean" "$dirty"
expect_result 'manual valid base and clean HEAD' 0 '' workflow_dispatch "$base" "$clean"
expect_result 'manual valid base and dirty HEAD' 2 'trailing whitespace.' workflow_dispatch "$base" "$dirty"
expect_result 'pull request diverged base still compares with merge base' 2 'trailing whitespace.' pull_request "$sibling" "$dirty"
expect_result 'push still compares from the previous SHA' 2 'trailing whitespace.' push "$base" "$dirty"
expect_result 'first push still handles the all-zero base' 2 'trailing whitespace.' push "$zero_sha" "$dirty"
