#!/bin/bash

set -ev

docker build . -t pdg
docker run pdg curl -s http://getcomposer.org/installer | php
docker run -ti --rm -u $UID:$UID -e COMPOSER_HOME=/application/.composer -v $(pwd):/application pdg php composer.phar install
