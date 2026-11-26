
## How to deploy

### Install Composer

```bash
cd /tmp

curl -sS https://getcomposer.org/installer -o composer-setup.php

sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

echo 'export PATH="/usr/local/bin:$PATH"' >> ~/.bashrc

source ~/.bashrc
```

### Enable Required Apache Modules

````bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
````

### Configure mod_php

```bash
ls /etc/apache2/mods-enabled/ | grep php

# if any, disable first
sudo a2dismod php8.3

# check 8.5 availibility
ls /etc/apache2/mods-available/ | grep php8.5

# install if missing
sudo apt install libapache2-mod-php8.5

# load php 8.5
sudo a2enmod php8.5
sudo systemctl restart apache2
```

### Create virtual host

```bash
sudo nano /etc/apache2/sites-available/job-tracker.conf
```

```apache
<VirtualHost *:80>
    ServerName job-tracker.test
    DocumentRoot /var/www/job-tracker/public

    <Directory /var/www/job-tracker/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/job-tracker-error.log
    CustomLog ${APACHE_LOG_DIR}/job-tracker-access.log combined

    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-Content-Type-Options nosniff
</VirtualHost>
```

### Set permissions

````bash
sudo chown -R www-data:www-data /var/www/job-tracker

sudo find /var/www/job-tracker -type d -exec chmod 755 {} \;
sudo find /var/www/job-tracker -type f -exec chmod 644 {} \;

sudo chmod -R 775 /var/www/job-tracker/storage
sudo chmod -R 775 /var/www/job-tracker/bootstrap/cache
````

### Enable virtual host

```bash
sudo a2ensite job-tracker.conf
sudo systemctl reload apache2
```

### Config test

````bash
sudo apache2ctl configtest
````
