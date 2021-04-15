FROM opensuse/tumbleweed AS build

RUN zypper install --no-recommends -y git

WORKDIR git

RUN git clone https://github.com/jamct/incidence-snippet

# Defines the tag for OBS and build script builds:
#!BuildTag: opensuse/7-tage-inzidenz:latest
#!BuildTag: opensuse/7-tage-inzidenz:1
#!BuildTag: opensuse/7-tage-inzidenz:1.0
#!BuildTag: opensuse/7-tage-inzidenz:1.0-%RELEASE%

#FROM opensuse/php8-nginx:latest
FROM registry.opensuse.org/home/kukuk/container/container/opensuse/php8-nginx:latest
LABEL maintainer="Thorsten Kukuk <kukuk@thkukuk.de>"

LABEL org.opencontainers.image.title="7-Tage-Inzidenz Container"
LABEL org.opencontainers.image.description="7-Tage-Inzidenz Container"
LABEL org.opencontainers.image.created="%BUILDTIME%"
LABEL org.opencontainers.image.version="1.0-%RELEASE%"
LABEL org.opencontainers.image.vendor="openSUSE Project"
LABEL org.openbuildservice.disturl="%DISTURL%"

COPY index.php /srv/www/htdocs/
COPY --from=build git/incidence-snippet/src/ /srv/www/htdocs/src/
COPY 80-fix-data-permissions.sh /docker-entrypoint.d/

RUN mkdir -p /data && chown wwwrun:www /data

EXPOSE 80:80
