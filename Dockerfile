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

COPY 7-tage-inzidenz.php /srv/www/htdocs/index.php
COPY update-data.php /usr/local/bin/update-data
COPY lib/ /srv/www/htdocs/lib/
COPY 80-fix-data-permissions.sh /docker-entrypoint.d/
COPY 10-set-TZ.sh /docker-entrypoint.d/
COPY 60-set-php-env.sh /docker-entrypoint.d/

RUN mkdir -p /data && chown wwwrun:www /data && sed -i -e 's|lib/RKI_Key_Data.php|/srv/www/htdocs/lib/RKI_Key_Data.php|g' /usr/local/bin/update-data
