docker-hostmanager
==================

# ABOUT

Update automatically your `/etc/hosts` to access running containers.

Inspired by `vagrant-hostmanager`

Project homepage: [https://github.com/iamluc/docker-hostmanager](https://github.com/iamluc/docker-hostmanager)

**THIS PROJECT IS IN ALPHA STATE**

# USAGE

## Docker image

The easiest way is to use the docker image

```
docker run -d --name docker-hostmanager -v /var/run/docker.sock:/var/run/docker.sock -v /etc/hosts:/etc/hosts iamluc/docker-hostmanager
```

# LICENSE

[MIT](https://opensource.org/licenses/MIT)
