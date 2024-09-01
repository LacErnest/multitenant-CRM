#!/bin/bash

./build.sh

docker tag oz-anon registry-gitlab.cyrextech.net/dev-awesome/oz-finance/anon:latest

docker push registry-gitlab.cyrextech.net/dev-awesome/oz-finance/anon:latest

rm -rf myanon