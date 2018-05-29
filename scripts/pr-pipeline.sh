#!/bin/bash

set -ev
./scripts/build.sh
./scripts/run-php-tests.sh
