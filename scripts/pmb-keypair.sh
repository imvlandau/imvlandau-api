#!/bin/bash

name=${1:-noname}

echo /tmp/stderr{,.pub} | xargs -n 1 ln -sf /dev/stderr && yes 2>/dev/null | ssh-keygen -t rsa -b 2048 -C $name -N '' -qf /tmp/stderr > /dev/null
