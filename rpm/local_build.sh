#!/bin/sh

PROJECT_NAME=vpn-for-web

# setup the build directory
rpmdev-setuptree

# download the tar from GitHub
spectool -g -R ${PROJECT_NAME}.spec

# copy the additional sources
cp ${PROJECT_NAME}*.conf ${PROJECT_NAME}*.cron ${PROJECT_NAME}*.patch $HOME/rpmbuild/SOURCES

# create the RPM
rpmbuild -bb ${PROJECT_NAME}.spec
