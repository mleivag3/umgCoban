FROM php:8.3-apache

ARG APP_VERSION=1.0.0
ARG BUILD_DATE=unknown

ENV APP_VERSION=${APP_VERSION}
ENV BUILD_DATE=${BUILD_DATE}

RUN a2enmod rewrite session

COPY src/ /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD curl -f http://localhost/ || exit 1
