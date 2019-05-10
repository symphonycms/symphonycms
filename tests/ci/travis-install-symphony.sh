#!/bin/bash

HOST='localhost';
PORT='80';

# test for redirect on install
INDEX="$(curl --retry 0 -sI http://$HOST:$PORT/ | grep '302 Found')"
if [ -z "$INDEX" ]; then
    echo 'Failed to get redirected on index';
    exit 100;
else
    echo 'Redirect to installer';
fi;
# copy unattend file
mkdir -p manifest
cp tests/ci/unattend.php manifest/
# test for install http status
INSTALL="$(curl --retry 0 -sI http://$HOST:$PORT/install/ | grep '200 OK')"
if [ -z "$INSTALL" ]; then
    echo 'Failed to load installer page';
    exit 200;
else
    echo 'Install returned 200';
fi;
# test install complete in the install log
COMPLETED="$(grep "INSTALLATION COMPLETED" manifest/logs/install)"
if [ -z "$COMPLETED" ]; then
    echo 'Installation failed';
    exit 300;
else
    echo 'INSTALLATION COMPLETED';
fi;
# test index now returns a 404
INDEXNOTFOUND="$(curl --retry 0 -sI http://$HOST:$PORT/ | grep '404 Not Found')"
if [ -z "$INDEXNOTFOUND" ]; then
    echo 'Failed to get a 404 on index';
    exit 404;
else
    echo 'Index returned 404';
fi;
# test admin login returns a 200
ADMIN="$(curl --retry 0 -sI http://$HOST:$PORT/symphony/ | grep '200 OK')"
if [ -z "$ADMIN" ]; then
    echo 'Failed to load login page';
    exit 500;
else
    echo 'Admin returns 200';
fi;
