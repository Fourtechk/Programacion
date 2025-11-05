# Imagen base con PHP y Apache
FROM php:8.2-apache

# Instalar extensiones necesarias (mysqli y pdo_mysql)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copiar los archivos del proyecto al servidor web
COPY ./htdocs /var/www/html

# Dar permisos al contenido
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exponer el puerto del servidor web
EXPOSE 80

# Activar el módulo rewrite (útil para algunos frameworks o rutas amigables)
RUN a2enmod rewrite

# Iniciar Apache
CMD ["apache2-foreground"]