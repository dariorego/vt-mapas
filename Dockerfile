FROM php:8.2-apache

# Instalar extensões necessárias
RUN docker-php-ext-install pdo pdo_mysql

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Copiar arquivos da aplicação
COPY . /var/www/html/

# Configurar permissões (opcional, mas recomendado)
RUN chown -R www-data:www-data /var/www/html

# A porta 80 é exposta por padrão no php:apache
EXPOSE 80
