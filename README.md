# BOF
Birds of a feather flock together: this is a software that can be used for proposing BOF topics and for voting on them

You can try a demo at https://bof.demo.solidcharity.com, with user `admin` and password `secret`. It is being reset each night automatically.

For more details about the Slim framework and other technical details, see the wiki: https://github.com/ICCM-EU/BOF/wiki

Please report issues at https://github.com/ICCM-EU/BOF/issues.

Pull requests are very much welcome!

# Installation

## Via Vagrant… (with Ansible in the background)

Install [Vagrant](https://www.vagrantup.com/downloads.html), [Virtual Box](https://www.virtualbox.org/wiki/Downloads) and [Ansible](http://docs.ansible.com/ansible/latest/intro_installation.html#installing-the-control-machine).

`vagrant up`

Everything should be accessible at [http://192.168.33.153] (or [http://bof.local] if you add it to your hosts file*.

* Install [Vagrant HostManager](https://github.com/devopsgroup-io/vagrant-hostmanager) to make this happen automagically

## Or setup directly on any Ubuntu machine via Ansible…

Setup an Ubuntu 18.04 machine:

```
apt-get install git ansible
git clone https://github.com/ICCM-EU/BOF.git
cd BOF/ansible
# perhaps update group_vars/all.yml with the actual timezone
ansible-playbook playbook.yml -i localhost
cd /root
rm -Rf BOF
ln -s /var/www/bof
```

The initial password for the user admin is: `secret`

You can change the password on the admin page.

The website lives in `/var/www/bof`

The configuration for the database is in `/var/www/bof/cfg/settings.php`

# Deployment and build

To bundle frontend items properly run:

`node_modules/.bin/gulp deploy`

# Resetting the database for real use or for testing

There is now a button on the admin page, that will reset the database. It will keep the admin user, and the prep workshop.

Set dates for testing the nomination or voting in the UI.

# Running the tests with Cypress

```
cd /var/www/bof
npm install cypress
apt-get install xvfb gconf2 libgtk2.0-0 libxtst6 libxss1 libnss3 libasound2
LANG=en CYPRESS_baseUrl=http://localhost ./node_modules/.bin/cypress run --config video=false --spec 'cypress/integration/nomination.js'
LANG=en CYPRESS_baseUrl=http://localhost ./node_modules/.bin/cypress run --config video=false --spec 'cypress/integration/voting.js'
```
