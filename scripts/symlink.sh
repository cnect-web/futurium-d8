# Symlink vendor binaries.
ln -sfn ./vendor/bin .
# Symlink tests
# @todo: fix web
ln -sfn ../../web/core/scripts/run-tests.sh ./bin/
