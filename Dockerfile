# 使用 php:8.1-apache 作为基础镜像
FROM php:8.1-apache

# 安装 mysqli 扩展
RUN docker-php-ext-install mysqli
RUN set -eux; \
    docker-php-ext-install mysqli pdo pdo_mysql
# 启用 Apache mod_rewrite 模块
RUN a2enmod rewrite

# 设置 Apache 的根目录
ENV APACHE_DOCUMENT_ROOT /var/www/html
