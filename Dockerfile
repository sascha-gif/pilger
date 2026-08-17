# pilger.milsh.com — PHP 8.3 mit Apache.
# Der Server ist eine Docker-Maschine; wie die anderen Projekte dort läuft auch
# diese App im Container, damit auf dem Host kein PHP installiert werden muss.

FROM php:8.3-apache

# gd und exif für die Fotos: hochgeladene Bilder werden verkleinert und nach
# der EXIF-Orientierung gedreht, damit Handy-Hochformat nicht quer liegt.
RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg-dev libpng-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install pdo_mysql gd exif \
 && a2enmod rewrite headers \
 && apt-get purge -y --auto-remove \
 && rm -rf /var/lib/apt/lists/*

# Fotos und Sprachaufnahmen kommen vom Handy — 2 MB Standardgrenze reichen nicht.
RUN printf 'upload_max_filesize = 32M\npost_max_size = 40M\nmax_file_uploads = 30\n' \
      > /usr/local/etc/php/conf.d/uploads.ini

# Document-Root auf public/ legen — src/, config/ und db/ liegen dadurch
# außerhalb des ausgelieferten Bereichs und sind über den Webserver nicht erreichbar.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Hinter Caddy liefert Apache nur HTTP aus; die echte Adresse steht im
# X-Forwarded-For, das reicht für die Protokolle.
RUN printf 'ServerName pilger.milsh.com\n' > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

WORKDIR /var/www/html
COPY . /var/www/html

# var/ ist der Ablageort für den SQLite-Rückfall und muss beschreibbar sein.
RUN mkdir -p var && chown -R www-data:www-data var

# /var/www/data ist der Einhängepunkt des Foto-Volumes. Das Verzeichnis muss
# schon im Image existieren und www-data gehören — Docker übernimmt Rechte und
# Eigentümer beim ersten Anlegen des Volumes von hier.
RUN mkdir -p /var/www/data/fotos /var/www/data/audio \
 && chown -R www-data:www-data /var/www/data

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD php -r 'exit(@file_get_contents("http://127.0.0.1/") === false ? 1 : 0);'

EXPOSE 80
