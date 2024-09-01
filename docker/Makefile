SHELL := /bin/bash

export OZ_FINANCE_ROOT_PATH=${PWD}/../

include .docker-compose.env
export
export DOCKER_CLIENT_TIMEOUT=120
export COMPOSE_HTTP_TIMEOUT=120

build-base:
	docker build -t ${BASE_IMAGE} --file=php-base.Dockerfile --no-cache .

build:
	docker login -u private-token -p ${GITLAB_COMPOSER_TOKEN} registry-gitlab.cyrextech.net
	docker pull ${BASE_IMAGE}
	docker-compose build

up:
	docker-compose up -d

up-build: build up

down:
	docker-compose down

status:
	docker ps --filter network=oz-finance-docker_main --format "table {{.Names}}\t{{.Status}}\t{{.ID}}"


cli:
	docker exec -it ozf_cli sh

sh:
	docker exec -it $(filter-out $@,$(MAKECMDGOALS)) sh


logs:
	docker logs -f $(filter-out $@,$(MAKECMDGOALS))

%:
    @:
