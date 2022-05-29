#!/usr/bin/env bash

BUILDER_DIR=$(realpath $(dirname "$0"))

(cd $BUILDER_DIR\
  &&rm -rf .subsplit/\
  &&./git-subsplit.sh init git@github.com:manaphp/manaphp.git\
  &&./git-subsplit.sh publish framework:git@github.com:manaphp/framework.git\
  &&rm -rf .subsplit/
)