# Export

The export module allows you to export filtered timesheet data into several formats.

## Difference between export and invoice

There are a couple of differences in these two Kimai modules, the most important ones:

- Invoices can only be created for a dedicated customer, where export can be done without selecting a customer
- Export state is saved with each timesheet record, so you can filter whether already exported items should be included or not
- Invoices do more calculation (e.g. tax) and support export to self-created templates (e.g. XLSX, ODS, DOCX)
- Invoices set duration to 1 in case of a fixed rate, export shows the real duration

## Security and privacy

The export extension does not check timesheet permissions, as this would defeat the purpose of an export.

Giving a user the permission to export data allows to basically see everything inside Kimai.
So all customer, projects, activities, all hourly rates, the personal time worked, the money earned become visible!

