# Dockerize lxHive for development

Goal: allow development without a global installation of PHP and MongoDB

## Setup

1. Copy `.env.template` to `.env` and fill in your credentials
2. Copy `import/LRS.yml.template` to `import/LRS.yml` and fill in your initial configuration details
3. Start services `docker-compose up -d --build`
4. Enter lxHive container shell and run the LRS install script:

``` bash
# shell access to container
docker exec -it lxhive bash
    # active directory should be /api...
    cd /api/install/
    php ./install.php
```

## Applications

* lxHive: http://localhost:8080
* MongoDB admin: http://localhost:8088

The ports can be customized in `.env`

The source code (`../`) is mounted into the lxHive container and can be edited from outside the container.
Server logs and MongoDB files are accessible in `../storage/`

## Helpers

The `compose.sh` script aims to make the container administration a bit easier:

```
Build and run lxHive multi-container network

  syntax: ./compose.sh [flush|build|up|list|logs|shell]
  options:
    -h|help   Print this Help.
    -f|flush  Clear data and files, recreate containers from ground up
    -b|build  Build containers (docker compose build [args])
    -u|up     Run services (docker compose up [args])
    -d|list   List containers
    -l|logs    lxhive logs
    -s|shell  Shell access to lxhive container
```
