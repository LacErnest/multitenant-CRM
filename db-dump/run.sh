#!/bin/bash

DB_HOST="oz-db"
DB_USER="root"
DB_PASSWORD="secret"
DB_NAME="oz-finance"
DB_PORT="3306"
HOST_VOLUME="$(pwd)/anon_vol"

# if MySQL is running on running on host, add this line to the docker run command
# --add-host host.docker.internal:host-gateway 

docker run --rm --name anon \
  -v "${HOST_VOLUME}:/app/result" \
  -e DB_HOST=$DB_HOST \
  -e DB_USER=$DB_USER \
  -e DB_PASSWORD=$DB_PASSWORD \
  -e DB_NAME=$DB_NAME \
  -e DB_PORT=$DB_PORT \
  registry-gitlab.cyrextech.net/dev-awesome/oz-finance/anon