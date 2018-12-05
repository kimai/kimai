# Updating Kimai

> **NOTE**
> 
> Don't forget that some tweaks may be necessary to these instructions if you are using FTP, developing or updating on your 
personal computer instead of a server. Read the [installation docu](installation.md) for more information.

**STOP** 

1. It is important that you don't execute the installation steps before or after your update
2. Make sure that you have a working database backup before you start the update
3. Read the [UPGRADING](https://github.com/kevinpapst/kimai2/blob/master/UPGRADING.md) guide and the [release information](https://github.com/kevinpapst/kimai2/releases) to check if there are further steps required

**START** 

Change into your Kimai 2 installation directory, then fetch the latest code and install all dependencies:

```bash
git pull origin master
sudo -u www-data composer install --no-dev --optimize-autoloader
```

Refresh your cache:

```bash
sudo -u www-data bin/console cache:clear --env=prod
sudo -u www-data bin/console cache:warmup --env=prod
```

And upgrade your database:

```bash
bin/console doctrine:migrations:migrate
```

Done! You can use the latest version of Kimai 2. 
