#!/bin/bash

set -xe

chown www-data:www-data /var/www/html/runtime
exec php-fpm --nodaemonize