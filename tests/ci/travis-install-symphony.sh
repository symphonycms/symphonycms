#!/bin/bash

# test for redirect on install
INDEX="$(curl --retry 0 -sI http://localhost:80/ | grep '302 Found')"
if [ -z "$INDEX" ]; then
    exit 100;
else
    echo 'Redirect to installer';
fi;
# copy unattend file
cp tests/ci/unattend.php manifest/
# test for install http status
INSTALL="$(curl --retry 0 -sI http://localhost:80/install/ | grep '200 OK')"
if [ -z "$INSTALL" ]; then
    exit 200;
else
    echo 'Install returned 200';
fi;
# test install complete in the install log
COMPLETED="$(grep "INSTALLATION COMPLETED" manifest/logs/install)"
if [ -z "$COMPLETED" ]; then
    exit 300;
else
    echo 'INSTALLATION COMPLETED';
fi;
# test index now returns a 404
INDEXNOTFOUND="$(curl --retry 0 -sI http://localhost:80/ | grep '404 Not Found')"
if [ -z "$INDEXNOTFOUND" ]; then
    exit 404;
else
    echo 'Index returned 404';
fi;
# test admin login returns a 200
ADMIN="$(curl --retry 0 -sI http://localhost:80/symphony/ | grep '200 OK')"
if [ -z "$INDEXNOTFOUND" ]; then
    exit 500;
else
    echo 'Admin returns 200';
fi;
