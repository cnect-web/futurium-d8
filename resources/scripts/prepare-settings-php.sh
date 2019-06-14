#!/usr/bin/env bash
SETTINGS_FOLDER=web/sites/default/
SETTINGS_PATH=web/sites/default/settings.php

chmod u+w $SETTINGS_FOLDER
if [ -f $SETTINGS_PATH ]; then
	rm $SETTINGS_PATH
fi
cp $SETTINGS_FOLDER/default.settings.php $SETTINGS_PATH

# Add config dir.
LINE=$(grep -Fn "\$config_directories = [];" $SETTINGS_PATH | cut -f 1 -d":")
sed -i -e "$LINE s|\$config_directories = \[\];|\$config_directories[CONFIG_SYNC_DIRECTORY] = '../config/sync';|" $SETTINGS_PATH

# Uncomment settings.local.php inclusion in settings.php.
LINE=$(grep -Fn "# if (file_exists(\$app_root . '/' . \$site_path . '/settings.local.php')) {" $SETTINGS_PATH | cut -f 1 -d":")
sed -i "$LINE s/^# //" $SETTINGS_PATH
LINE=$((LINE+1))
sed -i "$LINE s/^# //" $SETTINGS_PATH
LINE=$((LINE+1))
sed -i "$LINE s/^# //" $SETTINGS_PATH
chmod u-w $SETTINGS_FOLDER