.PHONY: build install test cs cs-fix stan check shell

# Inside a container: run commands directly.
# Outside: delegate to docker-compose.
ifneq ($(wildcard /.dockerenv),)
  RUN :=
else
  RUN := docker compose run --rm app
endif

build:
	docker compose build

install:
	$(RUN) composer install

test:
	$(RUN) composer test

cs:
	$(RUN) composer cs

cs-fix:
	$(RUN) composer cs:fix

stan:
	$(RUN) composer stan

check:
	$(RUN) composer check

shell:
	docker compose run --rm app bash
