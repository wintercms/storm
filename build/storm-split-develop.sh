#!/bin/bash

split()
{
    SUBDIR=$1
    SPLIT=$2
    HEADS=$3

    mkdir -p $SUBDIR;

    pushd $SUBDIR;

    for HEAD in $HEADS
    do

        HEADDIR="${HEAD//\/}"

        mkdir -p $HEADDIR

        pushd $HEADDIR

        ./../../git-subsplit.sh init git@github.com:wintercms/winter.git
        ./../../git-subsplit.sh update

        time ./../../git-subsplit.sh publish --heads="$HEAD" --no-tags "$SPLIT"

        popd

    done

    popd
}

split backend modules/backend:git@github.com:wintercms/wn-backend-module.git "develop"
split cms     modules/cms:git@github.com:wintercms/wn-cms-module.git         "develop"
split system  modules/system:git@github.com:wintercms/wn-system-module.git   "develop"
