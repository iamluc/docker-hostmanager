docker-hostmanager
==================

### ABOUT

Update automatically your `/etc/hosts` to access running containers.
Inspired by `vagrant-hostmanager`.

Project homepage: [https://github.com/iamluc/docker-hostmanager](https://github.com/iamluc/docker-hostmanager)

THIS PROJECT IS IN ALPHA STATE


### USAGE

#### Docker image

The easiest way is to use the docker image

```console
$ docker run -d --name docker-hostmanager -v /var/run/docker.sock:/var/run/docker.sock -v /etc/hosts:/hosts iamluc/docker-hostmanager
```

*TIPS*

To start automatically your container with your computer, add the option `--restart=always`

*OPTIONS*

The `DOMAIN_NAME` environment variable lets you define multiple hosts.
i.e.
```
$ docker run -d -e DOMAIN_NAME=test.com,www.test.com my_image
```

### Tests

To run test, execute the following command : `vendor/bin/phpunit`

### LICENSE

[MIT](https://opensource.org/licenses/MIT)
