#!/bin/bash
ROOTDIR="/jelixapp"
APPNAME="testapp"
APPDIR="$ROOTDIR/$APPNAME"
VAGRANTDIR="$APPDIR/vagrant"
POSTGRESQL_VERSION=9.4

source $VAGRANTDIR/system.sh


# --- testapp
resetJelixMysql testapp root jelix
resetJelixInstall $APPDIR

MYSQLTABLES="labels1_test labels_test myconfig product_tags_test product_test products towns testkvdb"
for TABLE in $MYSQLTABLES
do
    mysql -u root -pjelix -e "drop table if exists $TABLE;" testapp;
done

PGTABLES="jacl2_group jacl2_rights jacl2_subject jacl2_subject_group jacl2_user_group jsessions labels1_tests labels_tests product_tags_test product_test products testkvdb"
for TABLE in $PGTABLES
do
    sudo -u postgres -- psql -d testapp -c "drop table if exists $TABLE cascade;"
done

if [ -f $APPDIR/var/db/sqlite3/tests.sqlite3.bak ]; then
    cp -a $APPDIR/var/db/sqlite3/tests.sqlite3.bak $APPDIR/var/db/sqlite3/tests.sqlite3
fi

initapp $APPDIR

# --- adminapp
resetJelixInstall $APPDIR/adminapp
resetJelixMysql testapp root jelix admin_
initapp $APPDIR/adminapp

