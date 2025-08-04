FROM php:7.2-fpm
 
# Locale support installieren
RUN apt-get update && apt-get install -y \
    locales \
    && echo "de_DE.UTF-8 UTF-8" >> /etc/locale.gen \
    && echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen \
    && locale-gen \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHP Extensions installieren
RUN docker-php-ext-install mysqli

# UTF-8 Environment Variables setzen
ENV LANG=de_DE.UTF-8
ENV LC_ALL=de_DE.UTF-8
ENV LC_CTYPE=de_DE.UTF-8

# PHP Konfiguration für UTF-8
RUN echo "default_charset = \"UTF-8\"" >> /usr/local/etc/php/conf.d/charset.ini \
    && echo "mbstring.language = German" >> /usr/local/etc/php/conf.d/charset.ini \
    && echo "mbstring.internal_encoding = UTF-8" >> /usr/local/etc/php/conf.d/charset.ini \
    && echo "mbstring.http_input = UTF-8" >> /usr/local/etc/php/conf.d/charset.ini \
    && echo "mbstring.http_output = UTF-8" >> /usr/local/etc/php/conf.d/charset.ini \
    && echo "mbstring.detect_order = UTF-8" >> /usr/local/etc/php/conf.d/charset.ini

# PHP-FPM UTF-8 Konfiguration
RUN echo "php_admin_value[default_charset] = UTF-8" >> /usr/local/etc/php-fpm.d/www.conf
