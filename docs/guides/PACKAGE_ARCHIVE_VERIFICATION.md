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
separately. Once the release is published, check the exact package from
its approved distribution source in a clean external Composer project.
The local structural check covers checkout archives.
