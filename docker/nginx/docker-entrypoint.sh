#!/bin/bash

#set -x

echo "changing directory permission..."
for dir in `mount|awk '{print \$3}'`;do
   if [[ $dir =~ ^/var/www/html/ ]]; then
	cmd="chmod -R a+rw $dir";
	echo $cmd;
   	$cmd;
   fi
done
echo "changing directory permission done"

exec "$@"