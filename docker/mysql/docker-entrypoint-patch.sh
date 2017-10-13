#!/bin/bash

#set -x

for dir in /var/log/mysql /tmp/mysql
do
   if [ ! -d $dir ]; then
      mkdir -p ${dir}
   fi
   chown mysql:mysql $dir
done

echo "############################################################"
#echo "execute ${BASH_SOURCE}"
echo 
source docker-entrypoint.sh
