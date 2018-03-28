# BOF
Birds of a feather flock together: this is a software that can be used for proposing BOF topics and for voting on them

# Installation

## Via Vagrant…

Install [Vagrant](https://www.vagrantup.com/downloads.html), [Virtual Box](https://www.virtualbox.org/wiki/Downloads) and [Ansible](http://docs.ansible.com/ansible/latest/intro_installation.html#installing-the-control-machine).

`vagrant up`

Everything should be accessible at [http://192.168.33.153] (or [http://bof.local] if you add it to your hosts file*.

* Install [Vagrant HostManager](https://github.com/devopsgroup-io/vagrant-hostmanager) to make this happen automagically

## On any Ubuntu machine via Ansible…

Setup an Ubuntu 16.04 machine:

```
# set password for root:
passwd
adduser deploy
mkdir -p /home/deploy/.ssh
# insert your public ssh key
vi /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
```

Then call with your IP address of your test machine:

```
IP_TARGET_MACHINE=192.168.124.235
ansible-playbook playbook.yml --user=deploy --ask-become-pass --become-method=su -i $IP_TARGET_MACHINE,
```

# Deployment and build

To bundle frontend items properly run:

`node_modules/.bin/gulp deploy`
