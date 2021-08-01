#!/bin/bash

set -xe

chown www-data:www-data data tmp
exec php-fpm --nodaemonize