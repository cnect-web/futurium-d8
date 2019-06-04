#!/usr/bin/env bash
SETTINGS_FOLDER_RELPATH="$(find . -type d -path '*/sites/default')"

# Deploy script.
if [ ! -z $DATABASE_USERNAME ]; then
  QUERY_RESULT=$(mysql \
  -u ${DATABASE_USERNAME} \
  -p${DATABASE_PASSWORD} \
  -h ${DATABASE_HOST} \
  -e 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = "'${DATABASE_NAME}'";')

  TABLE_COUNT=${QUERY_RESULT#"COUNT(*)"}

  if [ $TABLE_COUNT != "0" ]; then
    # Site is installed, overwrite settings files.
    rm -rf $SETTINGS_FOLDER_RELPATH/settings.php
    rm -rf $SETTINGS_FOLDER_RELPATH/settings.local.php
    cp .ebextensions/files/settings.php $SETTINGS_FOLDER_RELPATH
    cp .ebextensions/files/settings.local.php $SETTINGS_FOLDER_RELPATH
  fi
fi

# Reset folder and files permissions.
bash ./scripts/deployments/permissions.sh --drupal_path=$(pwd)/web --drupal_user=webapp --httpd_group=webapp