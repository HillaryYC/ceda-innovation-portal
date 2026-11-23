FROM node:22-bookworm-slim AS frontend
WORKDIR /build
COPY frontend/package*.json ./
RUN npm ci --no-audit --no-fund
COPY frontend/ ./
ENV VITE_API_MODE=true
ARG VITE_CHATWOOT_WEBSITE_TOKEN
RUN npm run build

FROM dunglas/frankenphp:1-php8.4-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends poppler-utils && rm -rf /var/lib/apt/lists/* \n && install-php-extensions pdo_pgsql intl zip opcache
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
COPY backend/ ./
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
 && chmod -R ug+rwX storage bootstrap/cache
COPY --from=frontend /build/dist/ /app/public/
COPY Caddyfile /etc/caddy/Caddyfile
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr
CMD ["sh", "-c", "php artisan migrate --force && exec frankenphp run --config /etc/caddy/Caddyfile"]
