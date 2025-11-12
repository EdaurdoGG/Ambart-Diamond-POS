FROM php:7.4-apache

# Instalar dependencias y extensiones PHP
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli zip \
    && docker-php-ext-enable gd mysqli zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Copiar proyecto al contenedor
COPY . /var/www/html/

# Establecer directorio de trabajo
WORKDIR /var/www/html/

# Crear carpeta uploads si no existe y dar permisos
RUN mkdir -p /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/public/uploads

# Exponer puerto 80
EXPOSE 80

# Comando para iniciar Apache
CMD ["apache2-foreground"]
