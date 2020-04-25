#!/bin/sh

COPR_REPO=php
PROJECT_NAME=vpn-for-web

# setup the build directory
rpmdev-setuptree

# download the tar from GitHub
spectool -g -R ${PROJECT_NAME}.spec

# copy the additional sources
cp ${PROJECT_NAME}*.conf ${PROJECT_NAME}*.cron ${PROJECT_NAME}*.patch $HOME/rpmbuild/SOURCES

# create the SRPM
SRPM_FILE_PATH=$(rpmbuild -bs ${PROJECT_NAME}.spec | grep 'Wrote:' | cut -d ':' -f 2)
SRPM_FILE=$(basename $SRPM_FILE_PATH)

# upload the SPEC/SRPM to fedorapeople.org
scp ${PROJECT_NAME}.spec ${SRPM_FILE_PATH} fkooman@fedorapeople.org:~/public_html/${PROJECT_NAME}/

# build @ COPR
copr-cli build ${COPR_REPO} https://fkooman.fedorapeople.org/${PROJECT_NAME}/${SRPM_FILE}
