#!/bin/bash
set -euo pipefail

OPCACHE_MEMORY="${OPCACHE_MEMORY:-256}"
FPM_PM_CONTROL="${FPM_PM_CONTROL:-dynamic}"
FPM_MAX_CHILDREN="${FPM_MAX_CHILDREN:-40}"
FPM_START_SERVERS="${FPM_START_SERVERS:-5}"
FPM_MIN_SPARE="${FPM_MIN_SPARE:-5}"
FPM_MAX_SPARE="${FPM_MAX_SPARE:-10}"

cat > /usr/local/etc/php/conf.d/docker-php-opcache-runtime.ini <<EOF
opcache.enable=1
opcache.memory_consumption=${OPCACHE_MEMORY}
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=32531
opcache.revalidate_freq=60
opcache.enable_cli=1
EOF

WWW_CONF="/usr/local/etc/php-fpm.d/www.conf"
sed -i "s/^pm = .*/pm = ${FPM_PM_CONTROL}/" "$WWW_CONF"
sed -i "s/^pm\\.max_children = .*/pm.max_children = ${FPM_MAX_CHILDREN}/" "$WWW_CONF"
sed -i "s/^pm\\.start_servers = .*/pm.start_servers = ${FPM_START_SERVERS}/" "$WWW_CONF"
sed -i "s/^pm\\.min_spare_servers = .*/pm.min_spare_servers = ${FPM_MIN_SPARE}/" "$WWW_CONF"
sed -i "s/^pm\\.max_spare_servers = .*/pm.max_spare_servers = ${FPM_MAX_SPARE}/" "$WWW_CONF"

exec docker-php-entrypoint php-fpm
