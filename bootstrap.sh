separator()
{
	echo "-------------------------"
}

prepare()
{
	cd /vagrant

	sudo apt-get install software-properties-common
	sudo apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8
	sudo add-apt-repository 'deb [arch=amd64,arm64,ppc64el] http://mariadb.mirrors.ovh.net/MariaDB/repo/10.3/ubuntu bionic main'
	apt-get update

	debconf-set-selections <<< 'maria-db-10.3 mysql-server/root_password password root'
	debconf-set-selections <<< 'maria-db-10.3 mysql-server/root_password_again password root'
}

install_dependencies()
{
	separator
	echo "Installing dependencies..."

	apt-get install -y apache2 apache2-utils libexpat1 ssl-cert
	apt-get install -y php7.2 libapache2-mod-php7.2 php7.2-curl php7.2-mysql php7.2-json php7.2-gd php7.2-intl php7.2-gmp php7.2-mbstring php7.2-xml php7.2-zip
	apt-get install -y mariadb-server mariadb-client
	apt-get install -y npm
	apt-get install -y composer
	apt-get install -y git
	apt-get install -y supervisor

	apt-get -y autoremove

	composer install

	npm install --global laravel-echo-server

	npm install
	nodejs node_modules/node-sass/scripts/install.js
	npm rebuild node-sass

	npm run dev
}

setup_symlink()
{
	echo "Creating symlink"
	if ! [ -L /var/www ]; then
		rm -rf /var/www
		ln -fs /vagrant /var/www
	fi
}

setup_database()
{
	separator
	echo "Setting up database..."
	mysql -u root --password="root" --execute="CREATE USER 'laravel'@'localhost' IDENTIFIED BY 'secret'"
	mysql -u root --password="root" --execute="CREATE DATABASE dotvoid DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci"
	mysql -u root --password="root" --execute="GRANT ALL PRIVILEGES ON dotvoid.* TO 'laravel'@'localhost'"
	mysql -u root --password="root" --execute="CREATE DATABASE dotvoid_test DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci"
	mysql -u root --password="root" --execute="GRANT ALL PRIVILEGES ON dotvoid_test.* TO 'laravel'@'localhost'"

	php artisan migrate:install
	php artisan migrate
	php artisan db:seed
}

setup_worker()
{
	echo "Setting up worker"
	cat <<EOF > /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /vagrant/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/vagrant/storage/logs/worker.log
EOF
}

configure_apache()
{
	separator
	echo "Configuring apache..."

	sed ':a;$!{N;ba};s/AllowOverride None/AllowOverride All/3' /etc/apache2/apache2.conf > apache2.conf
	mv apache2.conf /etc/apache2/apache2.conf

	setup_symlink
	echo "Creating virtual host"
	cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
	ServerAdmin webmaster@localhost
	DocumentRoot /var/www/public

	ErrorLog ${APACHE_LOG_DIR}/error.log
	CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF
}

configure()
{
	separator
	echo "Configuring..."

	configure_apache
	setup_worker
	chmod -R 777 /vagrant/storage/
	chmod -R 777 /vagrant/bootstrap/

	cat <<EOF > /vagrant/.env
APP_NAME=DotVoid
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:4567

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dotvoid
DB_USERNAME=laravel
DB_PASSWORD=secret

BROADCAST_DRIVER=log
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_DRIVER=database

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=

MIX_PUSHER_APP_KEY=""
MIX_PUSHER_APP_CLUSTER=""
EOF

	chmod 777 .env
	php artisan key:generate

	setup_database
}

install()
{
	install_dependencies
	configure
}

start_worker()
{
	separator
	echo "Starting worker..."

	supervisorctl reread
	supervisorctl update
	supervisorctl start laravel-worker:*	
}


register_cron_task()
{
	separator
	echo "Registering cron task"

	line="* * * * * php /vagrant/artisan schedule:run >> /dev/null 2>&1"
	(crontab -u www-data -l; echo "$line" ) | crontab -u www-data -
}

provision()
{
	echo "Starting provisionning..."
	prepare
	install

	separator

	a2enmod rewrite
	service apache2 restart
	start_worker
	echo "Provision completed"
}

provision
