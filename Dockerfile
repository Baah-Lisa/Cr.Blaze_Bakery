FROM php:8.2-apache

RUN docker-php-ext-install mysqli \
    && a2enmod headers rewrite

WORKDIR /var/www/html
COPY . /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && mkdir -p /opt/seed-images \
    && cp -a /var/www/html/images/. /opt/seed-images/ \
    && chown -R www-data:www-data /var/www/html/images \
    && chmod 775 /var/www/html/images

EXPOSE 80
CMD ["docker-entrypoint.sh"]
