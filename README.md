# BOF
Birds of a feather flock together: this is a software that can be used for proposing BOF topics and for voting on them

# Installation

## Via Vagrant… (with Ansible in the background)

Install [Vagrant](https://www.vagrantup.com/downloads.html), [Virtual Box](https://www.virtualbox.org/wiki/Downloads) and [Ansible](http://docs.ansible.com/ansible/latest/intro_installation.html#installing-the-control-machine).

`vagrant up`

Everything should be accessible at [http://192.168.33.153] (or [http://bof.local] if you add it to your hosts file*.

* Install [Vagrant HostManager](https://github.com/devopsgroup-io/vagrant-hostmanager) to make this happen automagically

## Or setup directly on any Ubuntu machine via Ansible…

Setup an Ubuntu 18.04 machine:

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

Then call with your IP address (and port if other than 22) of your test machine:

```
cd ansible
TARGET_MACHINE=192.168.124.235:22
ansible-playbook playbook.yml --user=deploy --ask-become-pass --become-method=su -i $TARGET_MACHINE,
```

The initial password for the user admin is: `secret`

You can change the password, see SQL commands below.

The website lives in `/var/www/bof`

The configuration for the database is in `/var/www/bof/cfg/settings.php`

# Deployment and build

To bundle frontend items properly run:

`node_modules/.bin/gulp deploy`

# Resetting the database for real use or for testing

```
UPDATE participant SET password=PASSWORD('bofadminpwd') WHERE name = 'admin';
DELETE FROM participant;
INSERT INTO participant(name, password) VALUES('admin', PASSWORD('bofadminpwd'));

DELETE FROM workshop;
DELETE FROM workshop_participant;
```

Set dates for testing the nomination or voting in the UI.

# Running the tests with Cypress

```
cd /var/www/bof
npm install cypress
apt-get install xvfb gconf2 libgtk2.0-0 libxtst6 libxss1 libnss3 libasound2
LANG=en CYPRESS_baseUrl=http://localhost ./node_modules/.bin/cypress run --config video=false --spec 'cypress/integration/nomination.js'
LANG=en CYPRESS_baseUrl=http://localhost ./node_modules/.bin/cypress run --config video=false --spec 'cypress/integration/voting.js'
```
