#!/bin/bash

set -e

DEBUG=${DEBUG:-"0"}
[ "${DEBUG}" = "1" ] && set -x

# Set Europe/Berlin as default if nothing else is configured
ln -snf /usr/share/zoneinfo/Europe/Berlin /etc/localtime
