FROM php:7.2

WORKDIR /application

RUN set -eux; \
	apt-get update && apt-get install -y --no-install-recommends \
	        git \
		zlib1g-dev \
	; \
	docker-php-ext-install \
	        zip 
