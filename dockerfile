# Usar a imagem oficial do PHP com Apache
FROM php:8.1-apache

# Instalar dependências do sistema e o SQLite
RUN apt-get update && apt-get install -y libsqlite3-dev

# Instalar extensões PHP necessárias
RUN docker-php-ext-install pdo pdo_sqlite

# Instalar o Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Habilitar o módulo de reescrita do Apache
RUN a2enmod rewrite

# Copiar o código da aplicação para o diretório do Apache
COPY . /var/www/html/

# Definir as permissões para o diretório do Apache
RUN chown -R www-data:www-data /var/www/html

# Expor a porta 80
EXPOSE 80

# Iniciar o Apache em modo foreground
CMD ["apache2-foreground"]
