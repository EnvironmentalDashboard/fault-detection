#!/bin/bash


# see http://sourabhbajaj.com/blog/2017/02/07/gui-applications-docker-mac/ for X11 forwarding
IP=$(ifconfig en0 | grep inet | awk '$1=="inet" {print $2}')
xhost + $IP
docker build -t fault-detection-image . # build image, tag with fault-detection
docker run --rm -v /tmp/.X11-unix:/tmp/.X11-unix -e DISPLAY=$IP:0 --name fault-detection-container -v $(pwd):/src -it fault-detection-image # run container from image (--rm deletes container on exit, --name tags container, -v mounts code)
