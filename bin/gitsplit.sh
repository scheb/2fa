#!/bin/bash
git pull --tags
docker run --rm -t -e "GH_TOKEN=$GH_TOKEN" -v /cache/gitsplit:/cache/gitsplit -v $(pwd):/srv jderusse/gitsplit
