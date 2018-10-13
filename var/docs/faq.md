# Frequently asked questions

## I have a problem

Please open a new issue at [GitHub](https://github.com/kevinpapst/kimai2/issues/).
Add the last entries from your logfile at `var/log/prod.log`.

## I have only FTP available ...

So you want to install Kimai v2 but have no SSH access to your server? 
There is a workaround available, read the additional [installation instructions](installation.md). 

## Error on bin/console doctrine:schema:create

If you get an error during the installation of the database schema that mentions `DC2Type:json_array`, e.g. like the following: 

```
In PDOConnection.php line 109:

  SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'JSON
   NOT NULL COMMENT '(DC2Type:json_array)', UNIQUE INDEX UNIQ_B9AC5BCE5E237E06' at line 1
```

Then the most likely case is that you are using a MariaDB database which is too old. You can find out which version is 
used by executing `mysql --version` or by checking the server information e.g. with PHPMyAdmin.

There is a [discussion in the issue tracker](https://github.com/kevinpapst/kimai2/issues/191) about this topic.

There are two solution for this: 

- Update your MariaDB server to at least 10.2.7
- Switch to SQLite (that can be changed in your `.env` file)

Further readings:

- [MariaDB - JSON support was added with 10.2.7](https://mariadb.com/kb/en/library/json-data-type/)
- [Using JSON fields with Doctrine ORM on PostgreSQL & MySQL](https://symfony.fi/entry/using-json-fields-with-doctrine-orm-on-postgresql-mysql)
