# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|

    config.vm.box = "ubuntu/bionic64"

    config.vm.synced_folder "./", "/vagrant", id: "vagrant-root",
    owner: "vagrant",
    group: "www-data",
    mount_options: ["dmode=775,fmode=774"]

    config.vm.provider "virtualbox" do |v|
        v.name = "dotvoid_forum"
        v.memory = 2048
        v.cpus = 2
    end

    config.vm.provision :shell, path: "bootstrap.sh"
    config.vm.network :forwarded_port, guest: 80, host: 4567
end

