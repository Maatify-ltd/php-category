#!/usr/bin/env bash

set -euo pipefail

if [[ $# -ne 3 ]]; then
    printf 'Usage: bash %s <event> <base-sha> <head-sha>\n' "$0" >&2
    exit 1
fi

event="$1"
base_sha="$2"
head_sha="$3"

if [[ ! "$base_sha" =~ ^[0-9a-fA-F]{40}$ ]]; then
    printf 'Whitespace verification requires a full base commit SHA.\n' >&2
    exit 1
fi

if [[ "$event" == workflow_dispatch ]]; then
    if ! base_commit="$(git rev-parse --verify --quiet --end-of-options "$base_sha^{object}")" \
        || [[ "$(git cat-file -t "$base_commit")" != commit ]]; then
        printf 'Whitespace verification base must identify an existing commit.\n' >&2
        exit 1
    fi

    head_commit="$(git rev-parse --verify --end-of-options "$head_sha^{commit}")"
    if [[ "$base_commit" == "$head_commit" ]] || ! git merge-base --is-ancestor "$base_commit" "$head_commit"; then
        printf 'Whitespace verification base must be a strict ancestor of HEAD.\n' >&2
        exit 1
    fi
elif [[ "$event" == push && "$base_sha" =~ ^0+$ ]]; then
    base_sha="$(git rev-list --max-parents=0 "$head_sha")"
fi

git diff --check "$base_sha...$head_sha" -- . ':(exclude)docs/php-engineering-standards/**'
