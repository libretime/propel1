ARG PHP_VERSION="8.2"
ARG PHP_EXTENSIONS="intl pdo_pgsql pgsql pdo_sqlite sqlite3"

FROM thecodingmachine/php:${PHP_VERSION}-v5-slim-cli
