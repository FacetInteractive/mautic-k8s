<?php

    $parameters = array(
        'db_driver' => 'pdo_mysql',
        'db_host' => getenv('MYSQL_DB_HOST'),
        'db_port' => '3306',
        'db_name' => getenv('MYSQL_DATABASE'),
        'db_user' => getenv('MYSQL_USER'),
        'db_password' => getenv('MYSQL_PASSWORD'),
        'db_path' => null,
        'db_table_prefix' => null,
        'db_backup_tables' => 0,
        'db_backup_prefix' => 'bak_',
        'db_server_version' => '5.7.31',
        'secret_key' => getenv('SECRET_KEY'),
        'site_url' => getenv('SITE_URL'),
    );