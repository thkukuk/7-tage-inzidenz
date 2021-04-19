#!/bin/bash

set -e

DEBUG=${DEBUG:-"0"}
[ "${DEBUG}" = "1" ] && set -x

if [ -n "${REGIONS}" ]; then
	echo "env[REGIONS] = ${REGIONS}" >> /etc/php8/fpm/php-fpm.d/www.conf
fi

if [ -n "${PAST_DAYS}" ]; then
        echo "env[PAST_DAYS] = ${PAST_DAYS}" >> /etc/php8/fpm/php-fpm.d/www.conf
fi
