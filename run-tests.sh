#!/usr/bin/env bash

./vendor/bin/codecept run && \
./vendor/bin/codecept -c codeception.secure.yml run --env https
