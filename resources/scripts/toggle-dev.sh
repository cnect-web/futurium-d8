#!/usr/bin/env bash
if [ "$COMPOSER_DEV_MODE" = 1 ]; then
	echo "Replacing blellow theme with dev version."
	rm -rf web/themes/contrib/blellow
	git clone https://gitlab.dgcnect.eu/web-team/blellow-d8-theme --branch develop web/themes/contrib/blellow
fi

LINE=grep -Fn "# if (file_exists(\$app_root . '/' . \$site_path . '/settings.local.php')) {" web/sites/default/default.settings.php | cut -f 1 -d":"

sed "$LINE s/^#//" web/sites/default/default.settings.php
LINE=$((LINE+1))
sed "$LINE s/^#//" web/sites/default/default.settings.php
LINE=$((LINE+1))
sed "$LINE s/^#//" web/sites/default/default.settings.php
