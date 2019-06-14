#!/usr/bin/env bash
if [ "$COMPOSER_DEV_MODE" = 0 ]; then
	rm -rf web/themes/contrib/blellow
fi
