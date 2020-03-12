#!/bin/bash

set -xe

#export REMOTE_HOST="project.d"
#bash docker/deploy-stack.sh

PROJECT_NAME="ties"
SERVICE_NAME="admin"

if [ -z $IMAGE_TAG ]; then
    TO_BUILD_IMAGE=1
    IMAGE_TAG="${GIT_COMMIT:0:7}"
else
    TO_BUILD_IMAGE=0
fi

touch "build-${GIT_COMMIT:0:7}.txt"

cd docker;

cp .docker-stack.yml docker-stack.yml\
  && sed -i\
      -e "s#__PROJECT_NAME#${PROJECT_NAME}#g"\
      -e "s#__SERVICE_NAME#${SERVICE_NAME}#g"\
      -e "s#__IMAGE_TAG#${IMAGE_TAG}#g"\
      -e "s#__DOTENV_URL#${DOTENV_URL}#g"\
    docker-stack.yml

if [ $TO_BUILD_IMAGE = 1 ]; then
    docker-compose -f docker-stack.yml build\
    &&docker-compose -f docker-stack.yml push
fi

if [ "$APP_ENV" = 'prod' ]; then
  sed -i -e "s#docker.l#docker.l:80#g" docker-stack.yml
fi

REMOTE_COMPOSE_FILE="/docker-stack/${PROJECT_NAME}/${SERVICE_NAME}.yml"

ssh $REMOTE_HOST mkdir -p $(dirname ${REMOTE_COMPOSE_FILE})\
&&scp docker-stack.yml ${REMOTE_HOST}:${REMOTE_COMPOSE_FILE}\
&&ssh $REMOTE_HOST docker stack deploy -c $REMOTE_COMPOSE_FILE ${PROJECT_NAME} --with-registry-auth
