#!/bin/bash

do_compose_down=0
do_compose_build=0
do_compose_up=0
do_clear_data=0
do_list_containers=0
do_install_lxhive=0
do_shell_lxhive=0
do_logs_lxhive=0

build_args=
compose_args=" -d"

source ./.env

function usage () {
    echo
    echo "Build and run lxHive multi-container network"
    echo
    echo "  syntax: ./$(basename "${0}") [flush|build|install|up|list|logs|shell]"
    echo "  options:"
    echo "    -h|help    Print this Help."
    echo "    -f|flush   Clear data and files, recreate containers from ground up"
    echo "    -b|build   Build containers (docker compose build [args])"
    echo "    -i|install Run lxHive install script (force - removes all configs and database)"
    echo "    -u|up      Starts application and all its services (docker compose up [args])"
    echo "    -d|down    Stops containers and removes containerss, networks, volumes, and images"
    echo "    -l|list    List containers"
    echo "    -L|logs    lxhive logs"
    echo "    -s|shell   Shell access to lxhive container"
    echo "    -m|mongo   Shell access to mongo container"
    echo
}

if [ $# -eq 0 ]; then
    usage; exit 0
fi

for arg in "${@}"; do
    if [[ "${arg}" == "help"  || "${arg}" == "-h" ]]; then
        usage; exit 0
    fi
    if [[ "${arg}" == "flush" || "${arg}" == "-f" ]]; then
        build_args+=" --no-cache"
        compose_args+=" --force-recreate --renew-anon-volumes"
        do_compose_down=1
        do_clear_data=1
    fi
    if [[ "${arg}" == "build" || "${arg}" == "-b" ]]; then
        do_compose_down=1
        do_compose_build=1
    fi
    if [[ "${arg}" == "up"    || "${arg}" == "-u" ]]; then
        do_compose_up=1
        do_compose_down=0
    fi
    if [[ "${arg}" == "down"    || "${arg}" == "-d" ]]; then
        do_compose_up=0
        do_compose_down=1
    fi
    if [[ "${arg}" == "list"    || "${arg}" == "-l" ]]; then
        do_list_containers=1
    fi
    if [[ "${arg}" == "install" || "${arg}" == "-i" ]]; then
        do_install_lxhive=1
    fi
    if [[ "${arg}" == "logs"    || "${arg}" == "-L" ]]; then
        do_logs_lxhive=1
    fi
    if [[ "${arg}" == "shell"    || "${arg}" == "-s" ]]; then
        do_shell_lxhive=1
    fi
    if [[ "${arg}" == "mongo"    || "${arg}" == "-m" ]]; then
        do_shell_mongo=1
    fi
done

function list() {
    echo -e "\e[33m------------------------\e[0m"
    docker container ls --filter "name=lx*" --all
    echo -e "\e[33m------------------------\e[0m"
}

function ownership() {
    owner="${USER_ID}:${GROUP_ID}"
    if [[ -d "${1}" ]]; then
        sudo chown -R "${owner}" "${1}"
        return
    fi
    sudo chown "${owner}" "${1}"
}

function reset_dir() {
    if [[ -d "${1}" ]]; then
        sudo rm -rf "${1}"
    fi
    # create as current
    sudo mkdir -p "${1}"
    ownership "${1}"
}

function logs() {
    echo -e "\e[33m------------------------\e[0m"
    docker container logs "${1}" -f
    echo -e "\e[33m------------------------\e[0m"
}

function shell_mongo() {
    docker exec -w /data -it lxdata bash
}

function shell_lxhive() {
    docker exec -u www-data -w /api/lxHive -it lxhive bash
}

function install_lxhive() {
    docker exec -u www-data lxhive bash -c 'cd /api/lxHive/install && ./install.php force'
}

# always
sudo mkdir -p ../storage/logs ../storage/files ../data/mongodump
ownership ../storage
ownership ../data

if [[ $do_compose_down -eq 1 ]]; then
    docker compose down
fi

if [[ $do_clear_data -eq 1 ]]; then
    reset_dir ../storage
fi

if [[ $do_compose_build -eq 1 ]]; then
    echo " - run: compose build${build_args}"
    docker compose build${build_args}
fi

if [[ $do_compose_up -eq 1 ]]; then
    echo " - run: compose up${compose_args}"
    docker compose up ${compose_args} && list
fi

if [[ $do_list_containers -eq 1 ]]; then
    echo " - run: list lx* containers"
    list
fi

if [[ $do_install_lxhive -eq 1 ]]; then
    echo " - run: Run lxHive install script (cd /api/install && ./install.php force)"
    install_lxhive
fi

if [[ $do_logs_lxhive -eq 1 ]]; then
    echo " - run: lxhive container logs"
    logs lxhive
fi

if [[ $do_shell_lxhive -eq 1 ]]; then
    echo " - run: shell access to lxhive container"
    shell_lxhive
fi

if [[ $do_shell_mongo -eq 1 ]]; then
    echo " - run: shell access to mongo container"
    shell_mongo
fi

## actions

echo -e "\e[33m------------------------\e[0m"
echo " - lxHive Api:    http://localhost:${LXHIVE_HOST_PORT}"
echo " - Mongo Express: http://localhost:${ME_HOST_PORT}"
echo -e "\e[33m------------------------\e[0m"

echo -e "Next step:"

PS3="Select step: "
options=("Shell into lxHive" "Shell into Mongo" "List services" "Stop services" "Start services" "View lxhive logs" "Quit")
select opt in "${options[@]}"

do
    case $opt in
        "Shell into lxHive")
            echo  "- accessing container"
            shell_lxhive
            break;;
        "Shell into Mongo")
            echo  "- accessing container"
            shell_mongo
            break;;
        "List services")
            list
            ;; # stay active
        "Stop services")
            docker compose down
            ;; # stay active
        "Start services")
            docker compose up${compose_args}
            ;; # stay active
        "View lxhive logs")
            logs lxhive
            ;; # stay active
        "Quit")
            break;;
        *)
            echo "Invalid option $REPLY";;
    esac
done
