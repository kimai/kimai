# Importing data from Kimai v1

Before importing your data from a Kimai v1 installation, please read the following carefully:

- Data from the existing v1 installation is only read and will never be changed
- Data can only be imported from a Kimai installation with at least `v1.0.1` and database revision `1388` (check your `configuration` table)
- Kimai v1 has support for activities without project assignment, which Kimai v2 doesn't support. Unattached activities will be created for every project that has a linked activity in any of the imported timesheet records
- Rates and fixed-rates are handled in a completely different way and for now only the timesheet record total amounts are imported
- Customers cannot login and no user accounts will be created for them
- The customers country has to be manually assigned afterwards, as there is no field in Kimai v1 for that
- You have to supply the default password that is used for every imported user, as their password will be resetted
- Data that was deleted in Kimai v1 (user, customer, projects, activities) will be imported and set to `invisible` (if you don't want that, you have to delete all entries that have the value `1` in the `trash` column before importing)

A possible full command for import:
```bash
bin/console kimai:import-v1 "mysql://user:password@127.0.0.1:3306/database?charset=utf8" "db_prefix" "password" "country"
```

It is recommended to test the import in a fresh database. You can test your import as often as you like and fix possible problems in your installation.
A sample command could look like that:
```bash
bin/console doctrine:schema:drop --force && bin/console doctrine:schema:create && bin/console kimai:import-v1 "mysql://kimai:test@127.0.0.1:3306/kimai?charset=latin1" "kimai_" "test123" "de"
```
That will drop the configured Kimai v2 database schema and re-create it, before importing the data from the `mysql` database at `127.0.0.1` on port `3306` authenticating the user `kimai` with the password `test` for import.
The connection will use the charset `latin1` and the default table prefix `kimai_` for reading data. Imported users can login with the password `test123` and all customer will have the country `de` assigned.
