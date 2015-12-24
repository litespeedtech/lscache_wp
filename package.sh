#!/bin/sh

SCRIPT_DIR=`dirname $0`

cd $SCRIPT_DIR

PACK_FILE=lswpcache

if [ -f "${PACK_FILE}.zip" ] ; then
	/bin/rm -f "${PACK_FILE}.zip" 
fi

zip -r ${PACK_FILE} CHANGELOG.md README.md litespeed-cache/ 

echo "new package built: "
ls -l ${PACK_FILE}.zip

