.PHONY: vendor clean-vendor up down down-clean exec test coverage analyze

PHP = php -d memory_limit=768M
PHPUNIT = $(PHP) -dxdebug.mode=coverage -f vendor/bin/phpunit -- --verbose --fail-on-warning
COMPOSER = COMPOSER_ALLOW_XDEBUG=1 COMPOSER_DISABLE_XDEBUG_WARN=1 $(PHP) -d allow_url_fopen=On -f bin/composer.phar
SHELL = /bin/bash
DOCKER_COMPOSE="docker-compose"
DOCKER_COMPOSE_FILE="docker-compose.yml"
DOMAIN = `cat config/DOMAIN`
ROOT = /var/www/html
PATH_VAR = ${ROOT}/var
PATH_CACHE = ${PATH_VAR}/cache
PATH_COMPILED_TEMPLATES = ${PATH_VAR}/templates_c
PATH_CACHED_TEMPLATES = ${PATH_VAR}/templates_cache
VERSION=$(shell git log -1 --pretty=format:"%H")

vendor:
	$(COMPOSER) -- install -o

clean-vendor:
	rm -fr vendor

directories:
	mkdir -p ${PATH_VAR}
	chmod a+r ${PATH_VAR}
	chmod a+w ${PATH_VAR}
	mkdir -p ${PATH_CACHE}
	chmod a+r ${PATH_CACHE}
	chmod a+w ${PATH_CACHE}
	mkdir -p ${PATH_COMPILED_TEMPLATES}
	chmod a+r ${PATH_COMPILED_TEMPLATES}
	chmod a+w ${PATH_COMPILED_TEMPLATES}
	mkdir -p ${PATH_CACHED_TEMPLATES}
	chmod a+r ${PATH_CACHED_TEMPLATES}
	chmod a+w ${PATH_CACHED_TEMPLATES}

.env:
	echo "DOMAIN=$(DOMAIN)" > .env
	echo "DIRECTORY_ROOT=$(ROOT)" >> .env
	echo "COMPOSE_FILE=$(DOCKER_COMPOSE_FILE)" >> .env
	echo "PATH_VAR=$(PATH_VAR)" >> .env
	echo "PATH_CACHE=$(PATH_CACHE)" >> .env
	echo "PATH_COMPILED_TEMPLATES=$(PATH_COMPILED_TEMPLATES)" >> .env
	echo "PATH_CACHED_TEMPLATES=$(PATH_CACHED_TEMPLATES)" >> .env
	echo "DB_ROOT_PASSWORD=" >> .env
	echo "DB_DATABASE=" >> .env
	echo "DB_USER=" >> .env
	echo "DB_PASSWORD=" >> .env
	chmod a+r .env
	chmod a+w .env

_dev-env-docker: .env
	@${DOCKER_COMPOSE} -v || (echo "Could't find docker-compose. See https://docs.docker.com/compose/install/" && exit 1)

dev-env-prepare: directories _dev-env-docker
	rm -f $(PATH_COMPILED_TEMPLATES)/*
	rm -f $(PATH_CACHED_TEMPLATES)/*

up: dev-env-prepare
	$(DOCKER_COMPOSE) up -d

down: _dev-env-docker
	$(DOCKER_COMPOSE) down --remove-orphans

down-clean: _dev-env-docker
	$(DOCKER_COMPOSE) down -v --remove-orphans

exec:
	$(DOCKER_COMPOSE) exec -u nobody app bash

test: vendor
	$(PHPUNIT) -c tests/phpunit.xml tests/

coverage: vendor
	$(PHPUNIT) --coverage-clover build/clover.xml -c tests/phpunit.xml tests/
	sed -i 's#$(shell pwd)/##g' build/clover.xml

analyze:
	$(DOCKER_COMPOSE) run \
		-e SONAR_HOST_URL="http://sonar.starkeen.ru:9000" \
		-e SONAR_LOGIN="22dfc271ad734bbfb936eb87f7801a5320c8a636" \
		-v "$(shell pwd):/usr/src" \
		scaner \
		-Dsonar.projectBaseDir=/usr/src \
		-Dsonar.projectKey=culttourism \
		-Dsonar.sonar.projectVersion=$(VERSION)
