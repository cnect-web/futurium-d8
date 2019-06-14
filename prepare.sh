#!/usr/bin/env bash
if [ ! -f "robo.yml" ]; then
  cp robo.yml.dist robo.yml
fi

> .env
echo PROJECT_NAME=$(yq r robo.yml "project.name") >> .env
echo ENVIRONMENT=$(yq r robo.yml "project.environment") >> .env
echo BEHAT_API_DRIVER=$(yq r robo.yml "behat.api_driver") >> .env
echo BEHAT_WD_HOST_URL=$(yq r robo.yml "behat.webdriver_host") >> .env
echo USER_ID=1000 >> .env
echo GROUP_ID=1000 >> .env
echo DATABASE_DRIVER=mysql >> .env
echo DATABASE_ROOT_PASSWORD=$(yq r robo.yml "database.root_password") >> .env
echo DATABASE_PORT=$(yq r robo.yml "database.port") >> .env

# Check if we're on beanstalk.
# If we aren't, add the database info to .env
# If we are, the database info should already be in place.
if [ -z $EFS_MOUNT_DIR ]; then
	echo DATABASE_NAME=$(yq r robo.yml "database.name") >> .env
	echo DATABASE_USERNAME=$(yq r robo.yml "database.user") >> .env
	echo DATABASE_PASSWORD=$(yq r robo.yml "database.password") >> .env
	echo DATABASE_HOST=$(yq r robo.yml "database.host") >> .env
	echo HASH_SALT=$(yq r robo.yml "site.hash_salt") >> .env
fi

# Export the vars in .env.
# The .env file should be present in the folder from where this script is invoked.

if [ ! -f ".env" ]; then
  echo "No .env file present. Stopping."
  exit 1
else
  echo ".env file found."
fi

# Check if docker is installed.
if [ -x "$(command -v docker)" ]; then
  # Docker is installed, carry on with docker.
  echo "Using docker-compose."
  export $(egrep -v '^#' .env | xargs)
  REBUILD_CONTAINERS=${1:-no}

  if [ "$REBUILD_CONTAINERS" = "yes" ]; then
      # Kill all running containers.
      RUNNING_CONTAINERS=$(docker ps -q)
      if [ ! -z "$RUNNING_CONTAINERS" ]
      then
        echo "Killing running containers."
        KILL=$(docker kill $RUNNING_CONTAINERS)
      fi
  fi

  docker-compose -f docker/docker-compose.yml up -d \
  && docker exec -u 1000 -it php bash -c 'if [[ $ENVIRONMENT == "development" ]]; then composer install; else composer install --no-dev; fi' \
  && docker exec -u ${USER_ID} php echo "" \
  && docker exec -u ${USER_ID} php ./bin/robo pic --force
else
  # No docker, install on bare metal.
  echo "Bare metal install."
  if [[ $ENVIRONMENT == "development" ]]; then
    composer install;
  else
    composer install --no-dev;
  fi
  ./bin/robo pic
fi