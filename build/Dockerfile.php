FROM php:8.2-apache

ARG UID
ARG GID

RUN apt-get update && apt-get install -y unzip git \
    && docker-php-ext-install mysqli \
    && a2enmod rewrite headers

RUN sed -ri -e 's/^([ \t]*)(<\/VirtualHost>)/\1\tHeader set Access-Control-Allow-Headers "Content-Type"\n\1\2/g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's/^([ \t]*)(<\/VirtualHost>)/\1\tHeader set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"\n\1\2/g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's/^([ \t]*)(<\/VirtualHost>)/\1\tHeader set Access-Control-Allow-Origin "http:\/\/localhost:3000"\n\1\2/g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's/^([ \t]*)(<\/VirtualHost>)/\1\tHeader set Access-Control-Allow-Credentials "true"\n\1\2/g' /etc/apache2/sites-available/*.conf

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# --- AGGIUNTE PER KAMAL / PRODUZIONE ---

# 1. Imposta la cartella di lavoro
WORKDIR /var/www/html

# 2. COPIA il tuo codice PHP nel container
# Nota: Kamal eseguirà la build dal contesto indicato in deploy.yml (./build)
# quindi dobbiamo assicurarci che la cartella php sia raggiungibile.
COPY ../php /var/www/html

# 3. Sistema i permessi per Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80