#!/bin/bash

#set -x

chmod a+rw /var/www/html/data

exec "$@"