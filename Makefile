.PHONY: install test cs cs-fix stan check all docs-serve

install:
	composer install

test:
	composer test

cs:
	composer cs

cs-fix:
	composer cs:fix

stan:
	composer stan

check:
	composer check

all: install cs-fix check

docs-serve:
	@$(HOME)/.venv/bin/mkdocs serve --dev-addr=0.0.0.0:8001
