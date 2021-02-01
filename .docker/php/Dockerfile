FROM nanoninja/php-fpm:latest

# Installing COMPOSER
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get update
RUN apt-get install -y nodejs npm
RUN npm install -g yarn