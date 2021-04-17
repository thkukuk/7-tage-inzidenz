#!/bin/sh

if [ ! -e /data ]; then
	mkdir -p /data
fi
chown -h -P -c -R wwwrun:www /data
