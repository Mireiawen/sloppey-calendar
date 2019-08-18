#!/bin/bash
configfile="$(dirname "$(readlink -f "${0}")")/config.json"
docker 'run' \
	--rm \
	--volume "${configfile}:/app/config.json" \
	--workdir '/app' \
	--link 'redis:redis' \
	'mireiawen/sloppey-calendar' \
	php 'read.php'
