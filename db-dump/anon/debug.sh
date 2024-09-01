#!/bin/bash

DB_HOST="host.docker.internal"
DB_USER="root"
DB_PASSWORD="secret"
DB_NAME="oz-finance"
DB_PORT="3306"

#For mysql container
DB_LOCAL_PORT="3301"
HOST_VOLUME="$(pwd)/anon_vol"

clear_all(){
    docker stop $DB_HOST
    docker rm -f $DB_HOST
    docker rm -f anon
    docker network rm testnet
}

clear_all

docker network create testnet

docker run --name $DB_HOST \
    -e MYSQL_ROOT_PASSWORD=$DB_PASSWORD \
    -v "$(pwd)/init.sql":/docker-entrypoint-initdb.d/init.sql \
    --network testnet \
    -p $DB_PORT:3306 \
    -d mysql:8

sleep 30

docker run --name anon \
    #--add-host host.docker.internal:host-gateway \
    -v "${HOST_VOLUME}:/app/result" \
    -e DB_HOST=$DB_HOST \
    -e DB_USER=$DB_USER \
    -e DB_PASSWORD=$DB_PASSWORD \
    -e DB_NAME=$DB_NAME \
    -e DB_PORT=$DB_PORT \
    --network testnet \
    oz-anon

clear_all