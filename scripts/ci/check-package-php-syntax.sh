#!/usr/bin/env bash

set -euo pipefail

project_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$project_root"

php -l .php-cs-fixer.dist.php
find src tests scripts/ci examples -type f -name '*.php' -print0 | xargs -0 -n 1 php -l
