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

Once published, install the exact target through its approved distribution
source in a clean Composer project. For the currently published
RC, run these commands in a new empty directory:

```bash
composer require --prefer-dist maatify/php-category:1.0.0-rc.1
composer show maatify/php-category
```

In the external project's `vendor/composer/installed.json`, record Category's
exact version, installation mode, `dist` type, URL and reference, and
installation path. Compare the installed files and release presentation to
the tag and qualification evidence. Run the appropriate Consumer Verification
Harness against that installed version. The structural check above covers
checkout archives. The standalone CI harness installs the checkout from a
temporary VCS repository, so its result covers the checkout.

### Published v1.0.0-rc.1

Packagist resolved `v1.0.0-rc.1` through `dist` to
`57d160f7850a21b6908f343085e7dac644b87e86`. Composer installed it at
`vendor/maatify/php-category` from the [GitHub ZIP distribution](https://api.github.com/repos/Maatify-ltd/php-category/zipball/57d160f7850a21b6908f343085e7dac644b87e86).
The clean project used PHP 8.4.27 and resolved the artifact's legacy
`maatify/persistence` requirement to `v1.4.0`. A runtime smoke using the
installed schema and public facade passed on MySQL 8.4.9 and rolled back the
writes across all five package tables.

**Published Artifact Verification: NOT PASS against the current pinned
standard.** The RC.1 archive omits the required root `llms.txt` and
`examples/`. Its usage guide is at the legacy `docs/USAGE_GUIDE.md` path.
The smoke establishes installability and tested runtime behavior. Full
published Consumer Verification Harness evidence remains unverified for this
version.
