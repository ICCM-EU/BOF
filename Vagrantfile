Vagrant.require_version ">= 1.7.0"

Vagrant.configure(2) do |config|

  if Vagrant.has_plugin?("HostManager")
    config.hostmanager.enabled = true
    config.hostmanager.manage_host = true
    config.hostmanager.ignore_private_ip = false
    config.hostmanager.include_offline = true
  end

  config.vm.define "bof" do |tgc|
    tgc.vm.box = "ubuntu/xenial64"
    tgc.vm.network :private_network, ip: "192.168.33.153"
    tgc.vm.hostname = "bof.local"

    tgc.vm.network :forwarded_port, guest: 80, host: 80
    tgc.vm.network :forwarded_port, guest: 8080, host: 8080

    tgc.vm.provision :ansible do |ansible|
      # ansible.verbose = "v"
      ansible.playbook = "ansible/playbook.yml"
    end
  end

  config.vm.synced_folder ".", "/var/www/bof", type: "nfs"

  config.vm.provider "virtualbox" do |v|
    v.memory = 1024
    v.cpus = 1
  end
end
