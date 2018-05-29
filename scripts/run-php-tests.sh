#!/bin/bash

set -ev
docker run -ti --rm -u $UID:$UID -e COMPOSER_HOME=/application/.composer -v $(pwd):/application pdg vendor/bin/phpunit -c phpunit.xml.dist
