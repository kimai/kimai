# Installation

If you want to install Kimai v2 in your production environment and have SSH access, then switch to the official 
installation instruction in our [README](https://github.com/kevinpapst/kimai2/blob/master/README.md).

You need GIT and [Composer](https://getcomposer.org/doc/00-intro.md) on the machine where you want to install Kimai. 

## Hosting & 1-click installations

These platforms adopted Kimai 2 to be compatible with their one-click installation systems:

[![Install kimai2 with YunoHost](https://install-app.yunohost.org/install-with-yunohost.png)](https://install-app.yunohost.org/?app=kimai2)

Kimai 2 [package](https://github.com/YunoHost-Apps/kimai2_ynh) for [YunoHost](https://yunohost.org).
 
## FTP installation

If you have no SSH access to your server (e.g. when you use a shared hosting package) then you need to install Kimai locally and upload it afterwards.

Before I start to explain how to apply this workaround let me briefly explain the problem:
Kimai has no [web-based installer](https://github.com/kevinpapst/kimai2/issues/209) for now and you have to create the first user with a console command.
It also does not come as pre-built ZIP file, so you have to install the dependencies manually.

These are the steps you have to perform:

```
git clone https://github.com/kevinpapst/kimai2.git
cd kimai2/
```

Create the `.env` file, using the `prod` environment and SQLite as database:
```
APP_ENV=prod
APP_SECRET=insert_a_random_secret_string_for_production
DATABASE_URL=sqlite:///%kernel.project_dir%/var/data/kimai.sqlite
```
The file `var/data/kimai.sqlite` will hold all your data, so make sure to include it in your backups!

Prepare the environment by installing all dependencies:

```bash
composer install --no-dev
```

Create the database schemas:
```bash
bin/console doctrine:schema:create
```

And create your first user with the following command. You will be asked to enter a password afterwards.

```bash
bin/console kimai:create-user username admin@example.com ROLE_SUPER_ADMIN
```

Now you can upload the `kimai2/` directory to your hosting environment and point your domain (document root) to `kimai2/public/`.

## Development installation

Clone the repository and install all dependencies:

```bash
git clone https://github.com/kevinpapst/kimai2.git
cd kimai2/
composer install
```

The default installation uses a SQLite database, so there is no need to create a database for your first tests.
Our default settings will work out-of-the-box, but you might want to adjust the `.env` values to your needs.
You can configure your database through your environment (e.g. Webserver, Cloud-Provider) or in your `.env` file:
```
DATABASE_PREFIX=kimai2_
DATABASE_URL=sqlite:///%kernel.project_dir%/var/data/kimai.sqlite
APP_ENV=dev
APP_SECRET=some_random_secret_string_for_your_installation
```

The next commands will create the database and the schema:
```bash
bin/console doctrine:database:create
bin/console doctrine:schema:create
```

Lets bootstrap your environment by executing this commands (which is only available in dev environment):
```bash
bin/console kimai:reset-dev
```

You just imported demo data, to test the application in its full beauty and with several different user accounts and permission sets.

You can now login with these accounts:

| Username | Password | Role |
|---|:---:|---|
| clara_customer | kitten | Customer |
| john_user | kitten | User |
| chris_user | kitten | User (deactivated) |
| tony_teamlead | kitten | Teamlead |
| anna_admin | kitten | Administrator |
| susan_super | kitten | Super-Administrator |

Demo data can always be deleted by dropping the schema and re-creating it.
ATTENTION - this will erase all your data:

```bash
bin/console doctrine:schema:drop --force
bin/console doctrine:schema:create
```

The `kimai:reset-dev` command can always be executed later on to reset your dev database and cache.

There is no need to configure a virtual host in your web server to access the application for testing.
Just use the built-in web server for your first tests:

```bash
bin/console server:run
```

This command will start a web server for Kimai. Now you can access the application in your browser at <http://127.0.0.1:8000/>.
You can stop the built-in web server by pressing `Ctrl + C` while you're in the terminal.

To re-generate the frontend assets ([more information here](var/docs/developers.md)), execute:
```bash
yarn install
npm run prod
```
