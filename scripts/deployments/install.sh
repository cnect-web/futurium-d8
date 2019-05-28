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

  # Need a HOME env var set or drush blows up.
  if [ -z "$HOME" ]; then
    export HOME=/tmp
  fi

  if [ $TABLE_COUNT == "0" ]; then

    chmod -R 0777 $SETTINGS_FOLDER_RELPATH

    # Site is not installed, install it.
    su -s /bin/bash -m -c "
          vendor/bin/drush site-install minimal -y \
          '--root=./web' \
          --config-dir='../config/sync' \
          --site-name='Site Name' \
          --site-mail=email@example.com \
          --locale=en \
          --account-mail=admin@example.org \
          --account-name='admin' \
          --account-pass='admin' \
          --db-prefix= \
          --db-url='mysql://${DATABASE_USERNAME}:${DATABASE_PASSWORD}@${DATABASE_HOST}:3306/${DATABASE_NAME}'" \
          webapp
  else
    # Site is installed, overwrite settings files.
    rm -rf $SETTINGS_FOLDER_RELPATH/settings.php
    rm -rf $SETTINGS_FOLDER_RELPATH/settings.local.php
    cp .ebextensions/files/settings.php $SETTINGS_FOLDER_RELPATH
    cp .ebextensions/files/settings.local.php $SETTINGS_FOLDER_RELPATH

    su -s /bin/bash -c "vendor/bin/drush -r web cache-clear drush" webapp
    su -s /bin/bash -c "vendor/bin/drush -r web updb" webapp
    su -s /bin/bash -c "vendor/bin/drush -r web cim -y" webapp
  fi
fi

# Reset folder and files permissions.
bash ./scripts/deployments/permissions.sh --drupal_path=$(pwd)/web --drupal_user=webapp --httpd_group=webapp