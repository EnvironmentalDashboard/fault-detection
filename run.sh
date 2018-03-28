#!/bin/bash

docker build -t fault-detection-image . # build image, tag with fault-detection
docker run --rm --name fault-detection-container -v $(pwd):/src -it fault-detection-image # run container from image (--rm deletes container on exit, --name tags container, -v mounts code)