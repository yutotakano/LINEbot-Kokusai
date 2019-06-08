<?php

$config = parse_ini_file(__DIR__ . '/../config.ini');

define('APP_ROOT', $config['app_root']);

define('MANAGEBAC_DOMAIN', $config['ManageBac_schoolDomains']);
define('MANAGEBAC_LOGIN', $config['ManageBac_login']);
define('MANAGEBAC_PASSWORD', $config['ManageBac_password']);

define('DB_SERVERNAME', $config['db_server']);
define('DB_USERNAME', $config['db_user']);
define('DB_PASSWORD', $config['db_pass']);
define('DB_DBNAME', $config['db_name']);

define('IMGUR_CLIENT_ID', $config['imgur_client_id']);

define('USER_AGENTS', $config['user_agents']);