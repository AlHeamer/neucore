[![Build Status](https://travis-ci.com/bravecollective/brvneucore.svg?branch=master)](https://travis-ci.com/bravecollective/brvneucore)
[![StyleCI](https://styleci.io/repos/115431007/shield?branch=master)](https://styleci.io/repos/115431007)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=brvneucore&metric=alert_status)](https://sonarcloud.io/dashboard?id=brvneucore)

# Brave Collective Core Services

aka "neucore"

## Overview

A player management system for [EVE Online](https://www.eveonline.com/).

*Objectives*:
- Manage alliance specific groups for players.
- Access to [ESI](http://esi.evetech.net) data of all members.
- Provide an API for third-party applications.

This project consists of two applications, the backend and the frontend.
For more information, see the [**Frontend**](frontend/README.md) and [**Backend**](backend/README.md) Readme.

More documentation is available in the `doc` directory: 
[**Documentation**](doc/documentation.md), [**API**](doc/API.md).

A preview/demo installation is available at https://brvneucore.herokuapp.com
([Swagger UI](https://brvneucore.herokuapp.com/api),
[OpenAPI for apps](https://brvneucore.herokuapp.com/application-api.json))

## Installation

### EVE API Setup

- visit https://developers.eveonline.com or https://developers.testeveonline.com
- create a new application (eg: Neucore DEV)
- Connection Type: "Authentication & API Access", add the required scopes. Scopes for the backend
  are configured with the environment variable BRAVECORE_EVE_SCOPES.
- set the callback to https://your.domain/login-callback

### App Setup

Clone the repository or download the distribution (the distribution does not require Composer, Node.js or Java).

Copy `backend/.env.dist` file to `backend/.env` and adjust values or
set the required environment variables accordingly.

Make sure that the web server can write in `backend/var/logs` and `backend/var/cache`.

Please note that both the web server and console user write the same files to `backend/var/cache`,
so make sure they can override each other's files, e. g. by putting them into each other's group
(the app uses umask 0002 when writing files and directories).

If running in `prod` mode, the session cookie for the login is limited to HTTPS.

### Install/Update

If available, the app uses the APCu cache in production mode. This must be cleared during an update
(depending on the configuration, restart the web server or php-fpm).

##### Archive file

If you downloaded the .tar.gz file, you only need to run the database migrations and, 
depending on the update method, clear the cache:

```
cd backend
rm -rf var/cache/{di,http,proxies}
vendor/bin/doctrine-migrations migrations:migrate --no-interaction
```

##### Git

If you have cloned the repository, you must install the dependencies and build the backend and frontend
(see also "Local Development Requirements" below):

`./install.sh` or

`./install.sh prod`

#### Cron Job

Set up necessary cron jobs, e.g. 3 times daily, with flock (adjust user and paths):

```
0 4,12,20 * * * neucore /usr/bin/flock -n /tmp/neucore-jobs.lockfile backend/bin/run-jobs.sh
```

The output is logged to backend/var/logs.

### Local Development Requirements

* PHP 7.1+ with Composer, see `backend/composer.json` for necessary extensions
* Node.js 8 or 10, npm 6 (other versions may work, but are not tested)
* MariaDB or MySQL Server
* Apache or another HTTP Server
    * Set the document root to the `web` directory.
    * A sample Apache configuration in included in the [Vagrantfile](Vagrantfile) file and there 
      is a [.htaccess](web/.htaccess) file in the web directory.
    * A sample Nginx configuration can be found in the doc directory [nginx.conf](doc/nginx.conf)
* Java (only for swagger-codegen)

### Using Vagrant

Only tested with Vagrant 2 + libvirt.

- `vagrant up` creates and configures the virtual machine.
- If the Vagrant file changes, run `vagrant provision` to update the VM.
- `vagrant destroy` will completely remove the VM.

Please note that the `rsync` that is used is a one way sync from the host to the virtual
machine which is done every time `vagrant up` or `vagrant reload` is executed.

The Vagrant setup will create the file `backend/.env` with correct values for the database connection.
The values for the EVE application must be adjusted.

## Deploy on Heroku

- Create a new app
- Add a compatible database, e. g. JawsDB Maria.
- Add the necessary config vars (see `backend/.env.dist` file)
- Add build packs in this order:

```
heroku buildpacks:add heroku/java
heroku buildpacks:add heroku/nodejs
heroku buildpacks:add heroku/php
```

Logs are streamed to `stderr` instead of being written to files.
