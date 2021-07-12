FROM registry.opensuse.org/opensuse/tumbleweed:latest AS build-stage
RUN zypper install --no-recommends --auto-agree-with-product-licenses -y git
RUN git clone https://github.com/serbanghita/Mobile-Detect.git
RUN sed -i -e 's/|CMR-AL19/|CMR-AL19|CMR-W09/g' Mobile-Detect/Mobile_Detect.php

FROM registry.opensuse.org/opensuse/php8-nginx:latest
LABEL maintainer="Thorsten Kukuk <kukuk@thkukuk.de>"

LABEL org.opencontainers.image.title="7-Tage-Inzidenz Container"
LABEL org.opencontainers.image.description="7-Tage-Inzidenz Container"
LABEL org.opencontainers.image.created="%BUILDTIME%"
LABEL org.opencontainers.image.version="2.13"

COPY --from=build-stage /Mobile-Detect/Mobile_Detect.php /srv/www/htdocs/lib/
COPY lib/ /srv/www/htdocs/lib/
COPY 7-tage-inzidenz.php vaccination.php index.html /srv/www/htdocs/
COPY update-data.php /usr/local/bin/update-data
COPY 10-set-TZ.sh 60-set-php-env.sh 80-fix-data-permissions.sh /docker-entrypoint.d/

RUN rm /srv/www/htdocs/index.php && mkdir -p /data && chown wwwrun:www /data && sed -i -e 's|lib/RKI_Corona_Data.php|/srv/www/htdocs/lib/RKI_Corona_Data.php|g' -e 's|lib/RKI_Vaccination.php|/srv/www/htdocs/lib/RKI_Vaccination.php|g' /usr/local/bin/update-data
