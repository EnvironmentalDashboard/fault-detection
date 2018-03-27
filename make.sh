#!/bin/bash

docker build -t py-backend . # build image (-t = tag)
docker run --rm --name backend-inst -v $(pwd):/src -it py-backend # run container from image (--rm deletes container on exit, --name tags container, -v mounts code)