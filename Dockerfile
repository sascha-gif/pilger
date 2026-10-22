# pilger.milsh.com — PHP 8.3 mit Apache.
# Der Server ist eine Docker-Maschine; wie die anderen Projekte dort läuft auch
# diese App im Container, damit auf dem Host kein PHP installiert werden muss.

# **Die Fassung steht fest und wird nicht gerundet.** Das BSI warnt am
# 21.10.2026 (BITS-H 2026-297981-1132, Kritikalität Orange) vor
# CVE-2026-109618 und CVE-2026-109619 — „PHP2Shell", CVSS 9.2, Codeausführung
# aus der Ferne ohne Anmeldung, betroffen ist jede PHP-Fassung zurück bis 5.
# Behoben ist es in 8.5.12, 8.4.27, **8.3.36** und 8.2.35.
#
# Der rollende Tag `php:8.3-apache` hätte das irgendwann mitgebracht — aber
# `docker compose up --build` zieht kein Basis-Image nach, wenn eines im
# Zwischenspeicher liegt. Der Deploy hätte die alte Fassung also stillschweigend
# weitergefahren. Mit der genauen Nummer ist das ausgeschlossen: sie ist neu,
# also wird sie geholt, und im Git steht schwarz auf weiß, was läuft.
FROM php:8.3.36-apache

# gd und exif für die Fotos: hochgeladene Bilder werden verkleinert und nach
# der EXIF-Orientierung gedreht, damit Handy-Hochformat nicht quer liegt.
RUN apt-get update \
 && apt-get install -y --no-install-recommends libjpeg-dev libpng-dev libfreetype6-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install pdo_mysql gd exif \
 && a2enmod rewrite headers \
 && apt-get purge -y --auto-remove \
 && rm -rf /var/lib/apt/lists/*

# Grenzen für Anfragen — enger gezogen, als sie waren.
#
# Bilder und Videos kommen längst in Brocken von 1 MB als JSON (`Stueckweise`),
# ein Rumpf ist damit keine 1,4 MB groß. Am Stück hochgeladen wird nur noch die
# Sprachnotiz über `upload.php`; 24 MB sind dafür reichlich — bei den üblichen
# 128 kbit/s ist das eine Viertelstunde am Stück. Die alten 64/72 MB stammen
# aus der Zeit, als ganze Handyfotos als Formular-Upload kamen; die gibt es
# nicht mehr.
#
# Das BSI empfiehlt gegen CVE-2026-109618 ausdrücklich einen niedrigen
# `post_max_size` (8M ohne Datei-Uploads, 32M mit) und `upload_max_filesize`
# unterhalb davon. Genau das steht hier.
#
# `memory_limit = -1` ist **kein Versehen**: bei CVE-2026-109618 führt der
# Fehler an einem erreichbaren Speicherlimit in den verwundbaren Codepfad. Das
# BSI rät deshalb, das Limit in PHP aufzuheben und den Speicher statt dessen am
# Container zu begrenzen — das steht in der docker-compose.yml als `mem_limit`.
# Für Anfragen setzt die Apache-Konfiguration denselben Wert noch einmal als
# `php_admin_value`, damit ihn kein `ini_set()` aus der Anwendung heraus
# zurückdrehen kann.
#
# `session.upload_progress.enabled = 0` schließt den bestätigten Angriffspfad
# von CVE-2026-109619. Es steht hier und nicht in einer `.user.ini`, weil es
# greifen muss, **bevor** eine Anfrage verarbeitet wird.
#
# `expose_php = Off` nimmt den Kopfzeilen das `X-Powered-By: PHP/8.3.36`.
# Solange es drinsteht, trägt jede einzelne Antwort die genaue Fassung nach
# außen — die Angabe, nach der ein Scanner sucht, der sich Ziele für genau
# diese beiden CVEs zusammenstellt. Es schützt vor nichts, aber es macht die
# Seite unauffällig, und das kostet nichts.
#
# Was hier **nicht** steht: `fatal_error_backtraces = 0`. Das ist der
# Workaround für PHP 8.5; in 8.3 gibt es die Einstellung nicht, und eine
# unbekannte Einstellung löst pro Anfrage genau die Diagnosemeldung aus, die
# das BSI im dritten Punkt zu entfernen verlangt. Sie hier einzutragen wäre
# also keine Härtung, sondern das Gegenteil.
RUN printf '%s\n' \
      'upload_max_filesize = 24M' \
      'post_max_size = 32M' \
      'max_file_uploads = 5' \
      'max_input_vars = 200' \
      'memory_limit = -1' \
      'session.upload_progress.enabled = 0' \
      'expose_php = Off' \
      > /usr/local/etc/php/conf.d/pilger.ini \
 && rm -f /usr/local/etc/php/conf.d/uploads.ini

# Document-Root auf public/ legen — src/, config/ und db/ liegen dadurch
# außerhalb des ausgelieferten Bereichs und sind über den Webserver nicht erreichbar.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Hinter Caddy liefert Apache nur HTTP aus; die echte Adresse steht im
# X-Forwarded-For, das reicht für die Protokolle.
RUN printf 'ServerName pilger.milsh.com\n' > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

# Grenzen am Webserver, also **vor** PHP.
#
# `LimitRequestBody` ist der Riegel, den `post_max_size` nicht schieben kann:
# er greift auch bei Anfragen ohne verwertbaren `Content-Length`, etwa bei
# `Transfer-Encoding: chunked`. Genau darauf weist das BSI hin. Der Wert liegt
# knapp über `post_max_size`, damit eine bloß zu große Sprachnotiz noch bei PHP
# ankommt und `upload.php` die verständliche Meldung dazu schreiben kann —
# alles darüber bricht Apache ab, bevor PHP den Rumpf überhaupt anfasst.
#
# `php_admin_value` statt `php_value`: so kann die Anwendung den Wert per
# `ini_set()` nicht mehr ändern.
RUN printf '%s\n' \
      '# BSI BITS-H 2026-297981-1132 — CVE-2026-109618 / CVE-2026-109619' \
      'LimitRequestBody 34603008' \
      'LimitRequestFields 60' \
      '<IfModule mod_php.c>' \
      '  php_admin_value memory_limit -1' \
      '  php_admin_value session.upload_progress.enabled 0' \
      '</IfModule>' \
      '<IfModule mod_php8.c>' \
      '  php_admin_value memory_limit -1' \
      '  php_admin_value session.upload_progress.enabled 0' \
      '</IfModule>' \
      'ServerTokens Prod' \
      'ServerSignature Off' \
      > /etc/apache2/conf-available/pilger-haertung.conf \
 && a2enconf pilger-haertung

# Welcher Stand steckt in diesem Image? Steht spaeter im Seitenfuss.
ARG GIT_COMMIT=unbekannt
ARG BUILD_TIME=
ENV PILGER_COMMIT=$GIT_COMMIT
ENV PILGER_BUILD_TIME=$BUILD_TIME

WORKDIR /var/www/html
COPY . /var/www/html

# var/ ist der Ablageort für den SQLite-Rückfall und muss beschreibbar sein.
RUN mkdir -p var && chown -R www-data:www-data var

# /var/www/data ist der Einhängepunkt des Foto-Volumes. Das Verzeichnis muss
# schon im Image existieren und www-data gehören — Docker übernimmt Rechte und
# Eigentümer beim ersten Anlegen des Volumes von hier.
RUN mkdir -p /var/www/data/fotos /var/www/data/audio /var/www/data/videos /var/www/data/haelften \
 && chown -R www-data:www-data /var/www/data

# Beim Bauen nachrechnen, statt es zu glauben.
#
# Drei Dinge, die stillschweigend kaputtgehen können und dann niemandem
# auffallen: eine PHP-Fassung unterhalb des Patches (etwa weil jemand den Tag
# wieder auf `8.3-apache` zurückdreht und Docker ein altes Image aus dem
# Zwischenspeicher nimmt), eine Einstellung, die nicht greift — und eine
# **ungültige** Einstellung. Letztere ist hier kein Schönheitsfehler: sie löst
# pro Anfrage eine Diagnosemeldung vor der Initialisierung aus, und genau die
# soll laut BSI weg. Ein stummer PHP-Start ist der Nachweis, dass keine da ist.
RUN set -eu; \
    laut="$(php -r 'exit(0);' 2>&1)"; \
    if [ -n "$laut" ]; then echo "PHP meldet beim Start etwas: $laut" >&2; exit 1; fi; \
    php -r ' \
      $fehler = []; \
      require "src/helpers.php"; \
      if (!php_gepatcht()) { $fehler[] = "PHP " . PHP_VERSION . " ist nicht gepatcht (CVE-2026-109618/109619)"; } \
      if (ini_get("memory_limit") !== "-1") { $fehler[] = "memory_limit ist " . ini_get("memory_limit"); } \
      if (ini_get("session.upload_progress.enabled")) { $fehler[] = "session.upload_progress ist an"; } \
      if (ini_get("post_max_size") !== "32M") { $fehler[] = "post_max_size ist " . ini_get("post_max_size"); } \
      if (ini_get("expose_php")) { $fehler[] = "expose_php ist an"; } \
      if ($fehler) { fwrite(STDERR, implode("; ", $fehler) . PHP_EOL); exit(1); } \
      echo "PHP ", PHP_VERSION, " — Haertung sitzt.", PHP_EOL;'

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD php -r 'exit(@file_get_contents("http://127.0.0.1/") === false ? 1 : 0);'

EXPOSE 80
