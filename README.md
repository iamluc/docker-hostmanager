docker-hostmanager
==================

### ABOUT

Update automatically your `/etc/hosts` to access running containers.
Inspired by `vagrant-hostmanager`.

Project homepage: [https://github.com/iamluc/docker-hostmanager](https://github.com/iamluc/docker-hostmanager)


### USAGE

#### Linux

The easiest way is to use the docker image

```console
$ docker run -d --name docker-hostmanager --restart=always -v /var/run/docker.sock:/var/run/docker.sock -v /etc/hosts:/hosts iamluc/docker-hostmanager
```

*Note: the `--restart=always` option will make the container start automatically with your computer (recommended).*

#### Mac OS

Download the PHAR executable here : https://github.com/iamluc/docker-hostmanager/releases

And then run it:

```console
$ sudo php docker-hostmanager.phar synchronize-hosts
```

Note: We run the command as root as we need the permission to write file `/etc/hots`.
If you don't want to run the command as root, grant the correct permission to you user.

Before running the command, don't forget to export your docker environment variables.
i.e.

```
$ eval $(docker-machine env mybox)
```

Also, you should add a route to access containers inside your VM.

For Linux
```
$ sudo route -n add 172.0.0.0/8 $(docker-machine ip $(docker-machine active))
```

For Windows ( requires admin elevation )
```
$ route -p add 172.0.0.0/8 192.168.99.100
```

### INSTALL WITH COMPOSER

Alternatively, you can install `docker-hostmanager` with [composer](https://getcomposer.org/).

Just run
```
composer global require iamluc/docker-hostmanager
```

### OPTIONS

The `DOMAIN_NAME` environment variable lets you define multiple hosts when running your containers.
i.e.
```
$ docker run -d -e DOMAIN_NAME=test.com,www.test.com my_image
```

### Tests

To run test, execute the following command : `vendor/bin/phpunit`

### LICENSE

[MIT](https://opensource.org/licenses/MIT)
