#!/bin/bash
set -e

# Ensure only mpm_prefork is loaded for Apache mod_php
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# If no external database host is provided, start and configure embedded MariaDB
if [ -z "$MYSQLHOST" ] && [ -z "$DB_HOST" ]; then
    echo "Configuring embedded MariaDB..."
    mkdir -p /var/run/mysqld /var/lib/mysql
    chown -R mysql:mysql /var/run/mysqld /var/lib/mysql

    # Initialize data directory if needed
    if [ ! -d "/var/lib/mysql/mysql" ]; then
        echo "Initializing MariaDB data directory..."
        mariadb-install-db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1 || true
    fi

    echo "Starting MariaDB server in background..."
    mysqld_safe --skip-syslog --datadir=/var/lib/mysql &

    # Wait until MariaDB is ready to accept connections
    echo "Waiting for MariaDB to be ready..."
    for i in {1..30}; do
        if mysqladmin ping --silent 2>/dev/null; then
            echo "MariaDB is ready!"
            break
        fi
        sleep 1
    done

    # Ensure privileges for root on 127.0.0.1 and localhost
    mysql -u root -e "
        CREATE DATABASE IF NOT EXISTS e_find_db;
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
        FLUSH PRIVILEGES;
    " 2>/dev/null || true

    # Import schema if available
    if [ -f /var/www/html/e_find_db.sql ]; then
        echo "Importing e_find_db.sql..."
        mysql -u root e_find_db < /var/www/html/e_find_db.sql 2>/dev/null || true
    fi
fi

# Railway provides dynamic $PORT variable (defaults to 80 if not set)
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

echo "Starting Apache on port ${PORT}..."
exec apache2-foreground
