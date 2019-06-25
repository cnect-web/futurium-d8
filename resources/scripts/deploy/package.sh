#!/usr/bin/env bash
#Invoke from project root.

# Assign the file a project based on the current working dir.
if [ $# -eq 0 ] ; then
	PROJECT_NAME=$(basename `pwd`)
else
	PROJECT_NAME=$1
fi

# Delete file if it exists.
if [ -f "${PROJECT_NAME}.zip" ]; then
	rm ${PROJECT_NAME}.zip
fi

# Create a zip file without the cruft.
# @todo: maybe ensure composer install --no-dev ?
zip --symlinks -x \
--exclude=*.zip \
--exclude=*.git* \
--exclude=*.idea* \
--exclude=*.md \
--exclude=LICENSE \
--exclude=phpcs-ruleset.xml \
--exclude=phpunit.xml.dist \
--exclude=config.yml \
--exclude=config.yml.dist \
--exclude=bin/\* \
--exclude=docker/\* \
--exclude=tests/\* \
--exclude=robo/\* \
--exclude=*\/node_modules/\* \
--exclude=*\/.vscode/\* \
--exclude=*\/tests/\* \
--exclude=*\/Tests/\* \
--exclude=web/sites/simpletest/\* \
--exclude=web/sites/default/settings.php \
--exclude=web/sites/default/settings.local.php \
--exclude=web/sites/default/files/* \
-r ${PROJECT_NAME}.zip ./* ./.ebextensions
