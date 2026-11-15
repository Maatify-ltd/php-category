#!/usr/bin/env bash

set -euo pipefail

project_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$project_root"

scratch="$(mktemp -d)"
trap 'rm -rf -- "$scratch"' EXIT
consumer_dir="$scratch/consumer"
vcs_dir="$scratch/category-vcs.git"
source_sha="$(git rev-parse HEAD)"
mkdir "$consumer_dir"

if jq -r '.require | keys[]' composer.json | grep -E -i 'adminkernel|admin-control-panel|catalog|slim|php-di'; then
  echo "A forbidden host/runtime Composer dependency is declared."
  exit 1
fi
if grep -R -E -n -i 'adminkernel|admin-control-panel|(^|[^[:alnum:]_])catalog([^[:alnum:]_]|$)|(^|[^[:alnum:]_])slim([^[:alnum:]_]|$)|php-di' composer.json src; then
  echo "A forbidden host/runtime symbol is referenced by the package."
  exit 1
fi
if grep -R -E -n '^(namespace|use)[[:space:]]+(App|Host|Admin|Catalog|Slim|DI)(\\|[[:space:]]|;)' src; then
  echo "A forbidden host namespace is referenced by package runtime code."
  exit 1
fi
if grep -R -E -n '(^|[^[:alnum:]_])(require|require_once|include|include_once)([^[:alnum:]_]|$)' src; then
  echo "Package runtime code includes a host-owned file."
  exit 1
fi
if grep -R -E -n -i 'CREATE TABLE.*(admin|catalog|host)|REFERENCES.*(admin|catalog|host)' schema; then
  echo "The package schema references a forbidden host-owned table."
  exit 1
fi

git init --bare --initial-branch=category-consumer "$vcs_dir"
git --git-dir="$vcs_dir" fetch --no-tags --depth=1 "$project_root" "$source_sha:refs/heads/category-consumer"
printf 'Standalone consumer source: %s\n' "$source_sha"

cp tests/StandaloneConsumer/composer.json "$consumer_dir/composer.json"
cp tests/StandaloneConsumer/consumer.php "$consumer_dir/consumer.php"
composer config repositories.category vcs "$vcs_dir" --working-dir="$consumer_dir"
composer require --no-interaction --prefer-dist --no-progress --working-dir="$consumer_dir" "maatify/php-category:dev-category-consumer"
composer validate --strict --working-dir="$consumer_dir"
composer install --no-interaction --prefer-dist --no-progress --working-dir="$consumer_dir"
composer dump-autoload --optimize --strict-psr --working-dir="$consumer_dir"
composer check-platform-reqs --working-dir="$consumer_dir"
composer audit --no-interaction --abandoned=fail --working-dir="$consumer_dir"
jq -e --arg sha "$source_sha" '.packages[] | select(.name == "maatify/php-category" and .source.type == "git" and .source.reference == $sha)' "$consumer_dir/composer.lock"
jq -e '
  .packages
  | select(all(.[]; .name != "maatify/persistence"))
  | .[]
  | select(.name == "maatify/php-persistence"
      and .version == "v1.0.0-rc.1"
      and ."installation-source" == "dist"
      and .dist.reference == "34c3b874525d36ae064c162cdd553bc75d18772b")
  | {name, version, "installation-source", "dist-reference": .dist.reference}
' "$consumer_dir/vendor/composer/installed.json"
if composer show --tree --working-dir="$consumer_dir" | grep -E -i '(^|[^[:alnum:]_])(adminkernel|admin-control-panel|catalog|slim|php-di)([^[:alnum:]_]|$)'; then
  echo "The clean consumer resolved a forbidden host dependency."
  exit 1
fi
(cd "$consumer_dir" && php consumer.php)

for expected_file in composer.json composer.lock consumer.php vendor; do
  test -e "$consumer_dir/$expected_file"
done
unexpected_file="$(find "$consumer_dir" -mindepth 1 -maxdepth 1 ! -name composer.json ! -name composer.lock ! -name consumer.php ! -name vendor -print -quit)"
if [ -n "$unexpected_file" ]; then
  echo "The clean consumer contains an unexpected host file: $unexpected_file"
  exit 1
fi
host_file="$(find "$consumer_dir" -maxdepth 1 -type f \( -name '*Host*' -o -name '*Admin*' -o -name '*Catalog*' \) -print -quit)"
if [ -n "$host_file" ]; then
  echo "The clean consumer contains a host-owned file: $host_file"
  exit 1
fi
