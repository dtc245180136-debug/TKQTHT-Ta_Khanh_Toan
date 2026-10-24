FROM php:8.3-cli-alpine
RUN docker-php-ext-install pdo_mysql && addgroup -g 10001 app && adduser -D -u 10001 -G app app
WORKDIR /app
COPY --chown=app:app . .
USER app
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "public/index.php"]
