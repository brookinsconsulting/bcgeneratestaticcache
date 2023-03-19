#!/bin/bash

# Change Directory (Optional; Infered as current dir by default)

pushd .;

# Clear all caches (Optional; Performance Problem)

rm -rf var/cache/* var/site/cache/*;

# Generate all static cache (forces updates to existing static files; Required but more than needed)

php -d memory_limit=-1 ./extension/bcgeneratestaticcache/bin/php/bcgeneratestaticcache.php --force --debug=TRUE --quiet=FALSE$@;

# Return and exit

popd;

# Fin
