#!/usr/bin/env bash
# Deploy script.
RELATIVE_PATH_TO_SETTINGS_FOLDER="$(find . -type d -path '*/sites/default')"

if [ ! -z $DATABASE_USERNAME ]; then
  QUERY_RESULT=$(mysql \
  -u ${DATABASE_USERNAME} \
  -p${DATABASE_PASSWORD} \
  -h ${DATABASE_HOST} \
  -e 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = "'${DATABASE_NAME}'";')

  TABLE_COUNT=${QUERY_RESULT#"COUNT(*)"}

  if [ $TABLE_COUNT == "0" ]; then

    #chmod -R 0777 ${RELATIVE_PATH_TO_SETTINGS_FOLDER}

    # Site is not installed, install it.
    su -s /bin/bash -m -c "
          . /opt/elasticbeanstalk/support/envvars && \
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

    #chmod -R 0555 ${RELATIVE_PATH_TO_SETTINGS_FOLDER}
    #chmod -R 0775 ${RELATIVE_PATH_TO_SETTINGS_FOLDER}/files

  else
    # Site is installed, overwrite settings files.
    rm -rf $RELATIVE_PATH_TO_SETTINGS_FOLDER/settings.php
    rm -rf $RELATIVE_PATH_TO_SETTINGS_FOLDER/settings.local.php
    cp .ebextensions/files/settings.php $RELATIVE_PATH_TO_SETTINGS_FOLDER
    cp .ebextensions/files/settings.local.php $RELATIVE_PATH_TO_SETTINGS_FOLDER

    su -s /bin/bash -c "vendor/bin/drush -r web cache-clear drush" webapp
    su -s /bin/bash -c "vendor/bin/drush -r web updb" webapp
    su -s /bin/bash -c "vendor/bin/drush -r web csim -y" webapp
  fi

fi
