#!/bin/bash
# Export the vars in .env.
# The .env file should be present in the folder from where this script is invoked.

if [ ! -f ".env" ]; then
  echo "No .env file present. Stopping."
  exit 1
else
  echo ".env file found."
fi

export $(egrep -v '^#' .env | xargs)

# Kill all running containers.
RUNNING_CONTAINERS=$(docker ps -q)
if [ ! -z "$RUNNING_CONTAINERS" ]
then
  echo "Killing running containers."
  docker kill $RUNNING_CONTAINERS
fi

docker-compose -f docker/docker-compose.yml up -d \
&& docker exec -u ${USER_ID} php composer install \
&& docker exec -u ${USER_ID} php ./bin/robo pic