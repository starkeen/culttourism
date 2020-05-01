PHP = php -d memory_limit=768M
COMPOSER = COMPOSER_ALLOW_XDEBUG=1 COMPOSER_DISABLE_XDEBUG_WARN=1 $(PHP) -d allow_url_fopen=On -f bin/composer.phar
SHELL = /bin/bash
DOCKER_COMPOSE="docker-compose"
DOCKER_COMPOSE_FILE="docker-compose.yml"

vendor:
	$(COMPOSER) -- install -o

clean-vendor:
	rm -fr vendor

.env:
	echo "COMPOSE_FILE=$(DOCKER_COMPOSE_FILE)" >> .env

_dev-env-docker: .env
	@${DOCKER_COMPOSE} -v || (echo "Could't find docker-compose. See https://docs.docker.com/compose/install/" && exit 1)

dev-env-prepare: _dev-env-docker
	rm -f templates_c/*

up: dev-env-prepare
	$(DOCKER_COMPOSE) up -d

down: _dev-env-docker
	$(DOCKER_COMPOSE) down --remove-orphans

down-clean: _dev-env-docker
	$(DOCKER_COMPOSE) down -v --remove-orphans
