# Defines the tag for OBS and build script builds:
#!BuildTag: opensuse/7-tage-inzidenz:latest
#!BuildTag: opensuse/7-tage-inzidenz:2
#!BuildTag: opensuse/7-tage-inzidenz:2.1
#!BuildTag: opensuse/7-tage-inzidenz:2.1-%RELEASE%

#FROM opensuse/php8-nginx:latest
FROM registry.opensuse.org/opensuse/php8-nginx:latest
LABEL maintainer="Thorsten Kukuk <kukuk@thkukuk.de>"

LABEL org.opencontainers.image.title="7-Tage-Inzidenz Container"
LABEL org.opencontainers.image.description="7-Tage-Inzidenz Container"
LABEL org.opencontainers.image.created="%BUILDTIME%"
LABEL org.opencontainers.image.version="2.1-%RELEASE%"
LABEL org.opencontainers.image.vendor="openSUSE Project"
LABEL org.openbuildservice.disturl="%DISTURL%"

COPY index.php /srv/www/htdocs/
COPY src/ /srv/www/htdocs/src/
COPY 80-fix-data-permissions.sh /docker-entrypoint.d/
COPY 10-set-TZ.sh /docker-entrypoint.d/
COPY 60-set-php-env.sh /docker-entrypoint.d/

RUN mkdir -p /data && chown wwwrun:www /data
