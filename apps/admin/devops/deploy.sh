#!/bin/bash

set -xe

#export REMOTE_HOST="vm.app"
#export REMOTE_ROOT="/data/project"
#(cd ${WORKSPACE}/devops/;chmod +x *.sh; ./test.sh)

SERVICE_NAME="admin"
TAR_NAME="${SERVICE_NAME}-${BUILD_ID}-`date +%y%m%d`"
TAR_GZ="${TAR_NAME}.tar.gz"

type=$1;
REMOTE_PATH="${REMOTE_ROOT}/$type"

rm -rf devops/*.tar.gz

cp  -f .env.${type} .env
cp  -f docker/.env-${type} docker/.env

(cd docker;mv docker-compose.yml fpm.yml;cp swoole.yml docker-compose.yml)

echo $BUILD_ID>"build_id.txt"
echo $GIT_COMMIT>"git_commit.txt"

#find ../docker -name '*.sh'|xargs chmod +x
find devops -name '*.sh'|xargs chmod +x
find docker -name '*.sh' -or -name "Dockerfile"|xargs dos2unix

pwd
tar -czf devops/${TAR_GZ}  \
    --exclude=.git*\
    --exclude=docker/.env-*\
    --exclude=devops/*.tar.gz\
    .

(
    ssh ${REMOTE_HOST} sudo mkdir -p ${REMOTE_PATH}
    scp ${WORKSPACE}/devops/${TAR_GZ} ${REMOTE_HOST}:/tmp/
    ssh ${REMOTE_HOST} sudo mv /tmp/${TAR_GZ} ${REMOTE_PATH}/
)
(ssh ${REMOTE_HOST} "cd ${REMOTE_PATH};
    sudo mkdir ${TAR_NAME};
    sudo tar xzf ${TAR_GZ}  -C ${TAR_NAME};
    if [ ! -d "$SERVICE_NAME" ]; then
        sudo ln -s ${TAR_NAME} ${SERVICE_NAME};
        (cd ${SERVICE_NAME}/docker; sudo docker-compose up -d --build)
    else
        (cd ${SERVICE_NAME}/docker; sudo docker-compose stop;)
        sudo rm -rf ${SERVICE_NAME};
        sudo ln -s ${TAR_NAME} ${SERVICE_NAME};
        (cd ${SERVICE_NAME}/docker; sudo docker-compose up -d --build)
        (if [ $type != prod ];then for dir in ${SERVICE_NAME}-*; do if [ \$dir != $TAR_NAME ]; then rm -rf \$dir ; fi; done fi)
    fi
    ")