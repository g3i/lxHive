# Dockerize lxHive for development

Allow development without a global installation of PHP and MongoDB. 
The code of this repository (`../`) is being mounted into the `php` container, making it easy to develop, test and watch logs.

## Setup

1. Copy `.env.template` to `.env` and fill in your credentials
2. Go  to `../import/` and Copy `import/LRS.yml.template` to `import/LRS.yml` and fill in your initial configuration details
3. Switch back into this directory. build and start services

```bash
docker-compose up -d --build

# alternatively use helper script:
# ./compose.sh build up shell
```

4. Enter lxHive container shell and run the LRS install script:
   
``` bash
# shell access to container
docker exec -it lxhive bash
# active directory should be /api...
cd /api/install/
php ./install.php

# alternatively use helper script:
# ./compose.sh up install
```

## Applications

* lxHive: http://localhost:8080
* MongoDB admin: http://localhost:8088

The ports can be customized in `.env`

The source code (`../`) is mounted into the lxHive container and can be edited from outside the container.
Server logs ~~and MongoDB files~~ are accessible in `../storage/`

## Helpers

The `compose.sh` script aims to make the container administration a bit easier:

```bash
./compose.sh -h # show all options
# examples 
./compose.sh up shell # start and shell
./compose.sh down flush build up shell # build containers from ground up and start
```
