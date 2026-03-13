#!/bin/bash
set -e

CONFIG_FILE="/var/www/html/config.php"

# Generate config.php from environment variables if MOODLE_WWWROOT is set
if [ -n "$MOODLE_WWWROOT" ]; then
  echo "Generating config.php from environment variables..."
  cat > "$CONFIG_FILE" <<'PHPEOF'
<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = getenv('MOODLE_DB_TYPE') ?: 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('MOODLE_DB_HOST');
$CFG->dbname    = getenv('MOODLE_DB_NAME') ?: 'moodle';
$CFG->dbuser    = getenv('MOODLE_DB_USER') ?: 'moodleuser';
$CFG->dbpass    = getenv('MOODLE_DB_PASSWORD');
$CFG->prefix    = 'mdl_';
$_sslmode = getenv('MOODLE_DB_SSLMODE');
$CFG->dboptions = array(
  'dbpersist'       => 0,
  'dbport'          => getenv('MOODLE_DB_PORT') ?: '',
  'dbsocket'        => '',
  'connect_timeout' => 30,
);
if ($_sslmode) {
  $CFG->dboptions['sslmode'] = $_sslmode;
}

$CFG->wwwroot   = getenv('MOODLE_WWWROOT');
$CFG->sslproxy  = filter_var(getenv('MOODLE_SSL_PROXY') ?: 'true', FILTER_VALIDATE_BOOLEAN);
$CFG->dataroot  = '/var/www/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 02777;

require_once(__DIR__ . '/lib/setup.php');
PHPEOF
  chown www-data:www-data "$CONFIG_FILE"
  echo "config.php generated successfully (wwwroot: $MOODLE_WWWROOT)"
fi

# Execute the original CMD (apache2-foreground)
exec "$@"
