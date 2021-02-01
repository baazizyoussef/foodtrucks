# Clone the project

To install [Git](http://git-scm.com/book/en/v2/Getting-Started-Installing-Git), download it and install following the instructions :

```sh
git clone https://github.com/baazizyoussef/foodtrucks.git
```

## Project tree

```sh
.
├── .docker
│   └── php
│   │  ├── conf.d
│   │  └── Dockerfile
├── doc
├── docker-compose.yml
├── etc
│   ├── nginx
│   │   ├── default.conf
│   │   └── default.template.conf
├── web
│   ├── bin
│   ├── config
│   └── src
│   │  ├── Controller
│   │  ├── Entity
│   │  ├── Repository     
│   │  └── Kernel.php
│   ├── migrations
│   └── tests
│   └── tmp
│   │  ├── coverage
│   │  └── clover.xml
│   └── public
│   │    └── index.php
│   ├── .env
│   ├── .gitignore
│   ├── composer.json
│   ├── composer.lock
│   ├── phpunit.xml.dist
├── Makefile
├── README.md
```

___

## Run the application

1. Buildez vos images docker :

    ```sh
    docker-compose build
    docker-compose up -d
    ```
2. Open your favorite browser :

    * [http://localhost:8000](http://localhost:8000/) Nginx
    * [http://localhost:8000](http://localhost:3306/) MYSQL
    * [http://localhost:8080](http://localhost:8080/) PHPMyAdmin (username: root, password: root)

___


## Run Docker Container

```sh
docker exec -it foodtrucks_php_1 bash
```

## Install PHP dependencies with composer

```sh
composer install
```

## Lancez les migrations Doctrine

```sh
php bin/console doctrine:migrations:migrate
```

## Testing PHP application with PHPUnit

```sh
./bin/phpunit
```