#!/bin/bash
docker 'run' \
	--rm \
	--volume "$(pwd):/app" \
	--workdir '/app' \
	--link 'redis-test:redis' \
	'mireiawen/sloppey-calendar' \
	php 'read.php'
