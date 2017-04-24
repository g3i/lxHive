#!/bin/bash

PHAR_FILE='sami.phar'
PHAR_URI='http://get.sensiolabs.org/'
CONFIG_FILE='sami.config.php'

RED='\033[00;31m'
GREEN='\033[00;32m'
YELLOW='\033[00;33m'
GREY='\033[00;37m'
CLEAR='\033[00;39m'

echo ${YELLOW}
echo "----------------------- "
echo " (1/2) Downloading ${PHAR_URI}${PHAR_FILE}.."
echo "----------------------- "
echo ${CLEAR}

curl -O ${PHAR_URI}${PHAR_FILE}

if [ ! -e sami.phar ]
then
    echo "${RED}-----------------------${CLEAR}"
    echo "${RED}ERROR$ downloading${CLEAR} ${PHAR_URI}${PHAR_FILE}"
    echo "  - download ${PHAR_FILE} manually from: ${GREY}https://github.com/FriendsOfPHP/Sami${CLEAR}"
    echo "  - run: ${GREY}php sami.phar update ${CONFIG_FILE}${CLEAR}"
    echo "${RED}-----------------------${CLEAR}"
    exit 1
fi

echo ${YELLOW}
echo "----------------------- "
echo "Compiling docs.. (using ${CONFIG_FILE})"
echo "----------------------- "
echo ${CLEAR}

php sami.phar update ${CONFIG_FILE}

echo ${GREEN}
echo "----------------------- "
echo "Docs compiled to ${GREY}./docs/${CLEAR}"
echo "----------------------- "
echo ${CLEAR}
