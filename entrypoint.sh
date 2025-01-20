#!/bin/bash

echo "Start entrypoint file"

echo "APACHE_REMOTE_IP_HEADER: ${APACHE_REMOTE_IP_HEADER}"
echo "APACHE_REMOTE_IP_TRUSTED_PROXY: ${APACHE_REMOTE_IP_TRUSTED_PROXY}"
echo "APACHE_REMOTE_IP_INTERNAL_PROXY: ${APACHE_REMOTE_IP_INTERNAL_PROXY}"

echo "Setup TZ"
php -r "date_default_timezone_set('${TZ}');"
php -r "echo date_default_timezone_get();"


echo "Install composer"
composer dump-auto


echo "Add env data"
printf "%s\n" "$ENV_ARG" >> /var/www/html/.env
echo DB_HOST=$DBHOST >> /var/www/html/.env
echo DB_PORT=$DBPORT >> /var/www/html/.env
echo DB_DATABASE=$DBNAME >> /var/www/html/.env
echo DB_USERNAME=$DBUSER >> /var/www/html/.env
echo DB_PASSWORD=$DBPASS >> /var/www/html/.env

echo "Starting apache:"
/usr/sbin/apache2ctl start

echo "Restarting apache:"
/usr/sbin/apache2ctl restart


echo "End entrypoint"
while :; do
sleep 300
done
