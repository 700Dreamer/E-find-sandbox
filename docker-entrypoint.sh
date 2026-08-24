#!/bin/bash
set -e

# Disable conflicting MPMs and ensure prefork is active
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# If no external database host is provided, run embedded MariaDB
if [ -z "$MYSQLHOST" ] && [ -z "$DB_HOST" ]; then
    echo "Starting embedded MariaDB server..."
    service mariadb start || /etc/init.d/mariadb start

    # Initialize the database and import schema if needed
    echo "Checking and initializing database..."
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS e_find_db;" 2>/dev/null || true
    if [ -f /var/www/html/e_find_db.sql ]; then
        mysql -u root e_find_db < /var/www/html/e_find_db.sql 2>/dev/null || true
    fi
fi

# Railway provides dynamic $PORT variable (defaults to 80 if not set)
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

echo "Starting Apache on port ${PORT}..."
exec apache2-foreground
