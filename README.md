# NGF Project


## Composer

```
composer install
```

## List of available commands
```
./bin/robo
```


## Configuration

```
cp robo.yml.dist robo.yml
```

Update site settings
```
nano robo.yml
```

## Installation

```
./bin/robo project:install-config

or if you wish to run the importers:

./bin/robo pic -i

```

## Behat

Update paths for behat in robo.yml

```
nano robo.yml
```

Setup behat settings

```
./bin/robo project:setup-behat
```

Run behat tests

```
cd tests
./behat_no_proxy.sh behat
```
