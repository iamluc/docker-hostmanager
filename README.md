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

Note: We run the command as root as we need the permission to write file `/etc/hosts`.
If you don't want to run the command as root, grant the correct permission to you user.

Before running the command, don't forget to export your docker environment variables.
i.e.

```
$ eval $(docker-machine env mybox)
```

Also, you should add a route to access containers inside your VM.

```
$ sudo route -n add 172.0.0.0/8 $(docker-machine ip $(docker-machine active))
```

#### Windows

If the host, dont use Docker ToolBox or not a Windows 10 PRO, then needs to mount the /c/Windows folder onto VirtualBox.

```console
$ docker run -d --name docker-hostmanager --restart=always -v /var/run/docker.sock:/var/run/docker.sock -v /c/Windows/System32/drivers/etc/hosts:/hosts iamluc/docker-hostmanager
```

After run the container we need to add a route to access container subnets.

```
$ route /P add 172.17.0.0/8 192.168.99.100
```

### CONFIGURATION

#### With networks

When a container belongs to at least one network (typically when using a `docker-compose.yml` file in version >= 2), the name defined to access the container is `CONTAINER_NAME.CONTAINER_NETWORK`. It works also with the alias defined for the network.

As a container can belongs to several networks at the same time, and thanks to alias, you can define how you want to access your container.

**Example 1 (default network):**
```
version: '2'

services:
    web:
        image: iamluc/symfony
        volumes:
            - .:/var/www/html
```

The container `web` will be accessible with `web.myapp_default` (if the docker-compose project name is `myapp`)

**Example 2 (external network and alias):**
```
version: '2'

networks:
    default:
        external:
            name: myapp

services:
    web:
        image: iamluc/symfony
        volumes:
            - .:/var/www/html

    mysql:
        image: mysql
        networks:
            default:
                aliases:
                    - bdd
```

The `web` container will be accessible with `web.myapp`.
The `mysql` container will be accessible with `mysql.myapp` or `bdd.myapp`

#### Without networks

When a container has no defined network (only the default "bridge" one), it is accessible by its container name, concatened with the defined TLD (`.docker` by default).
It is the case when you run a single container with the `docker` command or when you use a `docker-compose.yml` file in version 1.

The `DOMAIN_NAME` environment variable lets you define additional hosts for your container.
e.g.:
```
$ docker run -d -e DOMAIN_NAME=test.com,www.test.com my_image
```

### Tests

To run test, execute the following command : `vendor/bin/phpunit`

### LICENSE

[MIT](https://opensource.org/licenses/MIT)
