#!/bin/bash

if [ $# -eq 0 ] || [ -z "$1" ]; then
    echo "No public key provided"
    exit 1
fi

ssh-keygen -l -E md5 -f <(echo "${1}")
