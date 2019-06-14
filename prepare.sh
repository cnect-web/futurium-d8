#!/usr/bin/env bash
echo PROJECT_NAME=$(yq r robo.docker.yml "project.name") > .env
echo ENVIRONMENT=$(yq r robo.docker.yml "project.environment") >> .env
echo HASH_SALT=$(yq r robo.docker.yml "site.hash_salt") >> .env
echo DATABASE_NAME=$(yq r robo.docker.yml "database.name") >> .env
echo DATABASE_USERNAME=$(yq r robo.docker.yml "database.user") >> .env
echo DATABASE_PASSWORD=$(yq r robo.docker.yml "database.password") >> .env
echo DATABASE_ROOT_PASSWORD=$(yq r robo.docker.yml "database.root_password") >> .env
echo DATABASE_HOST=$(yq r robo.docker.yml "database.host") >> .env
echo DATABASE_DRIVER=mysql >> .env
echo DATABASE_PORT=$(yq r robo.docker.yml "database.port") >> .env
echo DATABASE_PREFIX=$(yq r robo.docker.yml "database.prefix") >> .env
echo BEHAT_API_DRIVER=$(yq r robo.docker.yml "behat.api_driver") >> .env
echo BEHAT_WD_HOST_URL=$(yq r robo.docker.yml "behat.webdriver_host") >> .env
echo USER_ID=1000 >> .env
echo GROUP_ID=1000 >> .env