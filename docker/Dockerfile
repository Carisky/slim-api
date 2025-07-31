FROM php:8.2-cli

# Установим зависимости
RUN apt-get update && apt-get install -y \
    curl \
    gnupg2 \
    apt-transport-https \
    unixodbc \
    unixodbc-dev \
    gcc \
    g++ \
    make \
    autoconf \
    libc-dev \
    pkg-config \
    libssl-dev \
    libxml2-dev \
    zip \
    unzip \
    git

# Добавим ключ Microsoft вручную (новый способ)
RUN curl -sSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > /usr/share/keyrings/microsoft.gpg

# Добавим Microsoft репозиторий вручную
RUN echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" > /etc/apt/sources.list.d/mssql-release.list

# Установим msodbcsql18
RUN apt-get update && ACCEPT_EULA=Y apt-get install -y msodbcsql18

# Установим драйверы PHP
RUN pecl install pdo_sqlsrv sqlsrv && \
    docker-php-ext-enable pdo_sqlsrv sqlsrv

# Установим Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY .env .env
COPY . .

CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]
