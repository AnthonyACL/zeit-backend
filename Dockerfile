FROM php:8.3-fpm

# Instalar dependencias del sistema y extensiones PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    pkg-config \
    libssl-dev \
    && docker-php-ext-install pdo pdo_mysql bcmath pcntl zip

# Instalar extensión Redis (opcional, útil para cache/colas en Laravel)
RUN pecl install redis \
    && docker-php-ext-enable redis

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www
