NAME := $(shell basename `pwd`)

all: lint
.PHONY: all

lint:
	php7 vendor/bin/phpcbf || [ $$? -eq 1 ]
	php7 vendor/bin/phpcs
.PHONY: lint


bundle:
	mkdir -p build
	zip -r build/$(NAME).zip *.php src screenshot.png style.css
.PHONY: bundle

stg:
	rsync -avz *.php src screenshot.png style.css wordpress:stg/wp-content/themes/generatepress-mindspun/
.PHONY: stg
