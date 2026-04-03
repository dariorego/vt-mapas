FROM php:8.2-apache

# Instalar dependências do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite e configurar AllowOverride
RUN a2enmod rewrite && \
    sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Configurar PHP para produção
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
    echo "upload_max_filesize = 32M" >> /usr/local/etc/php/php.ini && \
    echo "post_max_size = 32M" >> /usr/local/etc/php/php.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/php.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/php.ini

# Copiar arquivos da aplicação
COPY . /var/www/html/

# Remover arquivos de desenvolvimento
RUN rm -f /var/www/html/.env.prod \
    /var/www/html/docker-compose*.yml \
    /var/www/html/.env.example

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type f -exec chmod 644 {} \; && \
    find /var/www/html -type d -exec chmod 755 {} \;

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD curl -f http://localhost/ || exit 1
