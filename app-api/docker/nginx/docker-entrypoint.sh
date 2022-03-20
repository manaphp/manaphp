#!/bin/bash

#set -x

chmod a+rw /var/www/html/runtime

exec "$@"