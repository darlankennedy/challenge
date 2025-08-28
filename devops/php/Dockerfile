FROM php:8.3.16-fpm

COPY --from=composer:2.8.10 /usr/bin/composer /usr/bin/composer

WORKDIR /backend/

RUN apt-get update && apt-get install -y \
        git \
        cron \
        curl \
        vim \
        redis-server \
        libpq-dev \
        libgmp-dev \
        libcurl4-openssl-dev \
        libonig-dev \
        libzip-dev \
        libjpeg-dev \
        libpng-dev \
        libfreetype6-dev \
        libkrb5-dev \
        libssl-dev \
        libc-client2007e-dev \
    && pecl install redis xdebug \
    && pecl clear-cache \
    && docker-php-ext-enable redis xdebug \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pgsql \
    && docker-php-ext-install -j$(nproc) \
        mysqli \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        calendar \
        gmp \
        mbstring \
        curl \
        zip \
        sockets \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install puppeteer --save \
    && npx puppeteer browsers install chrome-headless-shell \
    && npm cache clean --force \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

ENV XDEBUG_MODE="coverage"

RUN echo 'date.timezone = "America/Manaus"' > /usr/local/etc/php/conf.d/docker-php-ext-timezone.ini

COPY . .
RUN chmod +x /backend/start.sh \
    && chmod -R 777 /backend/bootstrap/cache /backend/storage
