#!/bin/bash
set -euo pipefail

OPCACHE_MEMORY="${OPCACHE_MEMORY:-256}"
OPCACHE_JIT="${OPCACHE_JIT:-tracing}"
OPCACHE_JIT_BUFFER="${OPCACHE_JIT_BUFFER:-128M}"
OPCACHE_VALIDATE_TIMESTAMPS="${OPCACHE_VALIDATE_TIMESTAMPS:-0}"
FPM_PM_CONTROL="${FPM_PM_CONTROL:-dynamic}"
FPM_MAX_CHILDREN="${FPM_MAX_CHILDREN:-40}"
FPM_START_SERVERS="${FPM_START_SERVERS:-5}"
FPM_MIN_SPARE="${FPM_MIN_SPARE:-5}"
FPM_MAX_SPARE="${FPM_MAX_SPARE:-10}"

# validate_timestamps=0 bỏ stat() trên 30K file PHP của Moodle ở mỗi request.
# JIT tracing tăng tốc các vòng hot trong renderer/format_text. Cần Moodle 4.x trở lên.
cat > /usr/local/etc/php/conf.d/docker-php-opcache-runtime.ini <<EOF
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=${OPCACHE_MEMORY}
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=32531
opcache.validate_timestamps=${OPCACHE_VALIDATE_TIMESTAMPS}
opcache.revalidate_freq=60
opcache.save_comments=1
opcache.fast_shutdown=1
opcache.jit=${OPCACHE_JIT}
opcache.jit_buffer_size=${OPCACHE_JIT_BUFFER}
EOF

WWW_CONF="/usr/local/etc/php-fpm.d/www.conf"
sed -i "s/^pm = .*/pm = ${FPM_PM_CONTROL}/" "$WWW_CONF"
sed -i "s/^pm\\.max_children = .*/pm.max_children = ${FPM_MAX_CHILDREN}/" "$WWW_CONF"
sed -i "s/^pm\\.start_servers = .*/pm.start_servers = ${FPM_START_SERVERS}/" "$WWW_CONF"
sed -i "s/^pm\\.min_spare_servers = .*/pm.min_spare_servers = ${FPM_MIN_SPARE}/" "$WWW_CONF"
sed -i "s/^pm\\.max_spare_servers = .*/pm.max_spare_servers = ${FPM_MAX_SPARE}/" "$WWW_CONF"

exec docker-php-entrypoint php-fpm
