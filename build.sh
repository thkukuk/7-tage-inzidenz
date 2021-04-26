#!/bin/sh

if [ -z "$1" ]; then
	echo "Usage: build.sh <TAG>"
	exit 1
fi

sudo podman pull registry.opensuse.org/opensuse/php8-nginx:latest
sudo podman build -t 7-tage-inzidenz .
sudo podman login docker.io
sudo podman tag localhost/7-tage-inzidenz thkukuk/7-tage-inzidenz:$1
sudo podman tag localhost/7-tage-inzidenz thkukuk/7-tage-inzidenz:latest
sudo podman push thkukuk/7-tage-inzidenz:$1
sudo podman push thkukuk/7-tage-inzidenz:latest
