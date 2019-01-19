# Invoices

You can export your timesheet data to invoices in several formats. 

## Invoice document

The invoice system currently supports the following formats:

- `HTML`
  - through the use of Twig templates
  - filename must end with `.html.twig` 
  - Pros: no need for additional software, print or convert to PDF from your browser (if supported)
- `DOCX`
  - OOXML - Open Office XML Text
  - Microsoft Word 2007-2013 XML
  - filename must end with `.docx` 
  - Pros: simple customization and possibility to edit the invoice later on
  - our recommended invoice document format
- `CSV`
  - Comma-separated file with UTF-8 encoding and double-quotes around each field 
  - filename must end with `.csv` 
  - Pros: good for exporting and creating enhanced reports with an office software package
  - our recommended export format
- `XLSX`
  - Microsoft Excel™ 2007 shipped with a new file format, namely Microsoft Office Open XML SpreadsheetML, and Excel 2010 extended this still further with new features. 
  - file extension: filename must end with `.xlsx` 
  - PRO: good for exporting, creating enhanced reports with an office software package
- `ODS`
  - Open Document Spreadsheet or OASIS, is the OpenOffice.org XML file format for spreadsheets supported by OpenOffice, LibreOffice, StarCalc, Microsoft and others 
  - file extension: filename must end with `.ods` 
  - Pros: open format - good for exporting, creating enhanced reports with an office software package
  - our recommended spreadsheet format

**Be aware**: the default templates were created and tested ONLY with LibreOffice!

## Create your own invoice document

Invoice documents are searched in two locations:

- `templates/invoice/renderer/`
- `var/invoices/`

Be aware of the following rules:

- Documents are addressed by their filename without extension (e.g. `kimai.html.twig` results in `kimai`) 
- You can use every document name only once: so using `kimai.html.twig` and `kimai.docx` is not possible
- The first file to be found takes precedence 
- Kimai looks first in `var/invoices/`, so you can overwrite default templates
- You should store your templates in `var/invoices/` as this directory is not shipped with Kimai and not touched during updates
- You can configure different search directories through the config key `kimai.invoice.documents` if you want to 
  - hide the default templates
  - add additional template source directories
  - see below in `Configure search path` to find out how

### Twig templates

Generally speaking, you should use only the variable `model` in your template which is an instance of `App\Model\InvoiceModel`.

Please see the [default templates](https://github.com/kevinpapst/kimai2/tree/master/templates/invoice/renderer) at 
GitHub to find out which variables can be used or debug it with:

```twig
{{ dump(model) }}
```

### Docx templates

Docx templates are powered by [PHPWord](https://github.com/PHPOffice/PHPWord) and its `TemplateProcessor`.

**Important:** You have to add one of the variables - either `${entry.description}` or `${entry.row}` - in one table row, 
otherwise the rendering process will fail! The row containing this variable will be cloned for every included timesheet record.

If you do not use `${entry.description}` then a fallback for `${entry.row}` is used and will be removed in the rendering process, 
it will not show up in the generated invoice.

See below in `Template variables` to find out which variables you can use in your template.

Find out more about PHPWord templates [here](https://phpword.readthedocs.io/en/latest/templates-processing.html).

### Spreadsheets (ODS, XLSX and CSV)

Spreadsheet templates are powered by [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet).

**Important:** within the first 100 rows you MUST-HAVE the template row for timesheet entries, which means there must be 
a value starting with `${entry.` in one of the first 10 columns, otherwise no timesheet records will be rendered!

_Check the default templates if that doesn't make sense to you ;-)_

This row will then be cloned for every timesheet entry. 

See below in `Template variables` to find out which variables you can use in your CSV file.

## Template variables

### Global variables 

You can use the following global variables in your templates:

| Key | Description |
|---|---|
| ${invoice.due_date} | The due date for the invoice payment |
| ${invoice.date} | The creation date of this invoice |
| ${invoice.number} | The generated invoice number |
| ${invoice.currency} | The invoice currency |
| ${invoice.total_time} | The total working time (entries with a fixed rate are always calculated with 1) |
| ${invoice.total} | The invoices total (including tax) |
| ${invoice.subtotal} | The invoices subtotal (excluding tax) |
| ${invoice.vat} | The VAT in percent for this invoice |
| ${invoice.tax} | The tax of the invoice amount |
| ${template.name} | The invoice name, as configured in your template |
| ${template.company} | The company name, as configured in your template |
| ${template.address} | The invoicing address, as configured in your template |
| ${template.title} | The invoice title, as configured in your template |
| ${template.payment_terms} | Your payment terms, usage might differ from template to template |
| ${template.due_days} | The amount of days for the payment, starting with the day of creating the invoice |
| ${query.begin} | The query begin as formatted short date |
| ${query.end} | The query end as formatted short date |
| ${query.month} | The month for this query (begin date) |
| ${query.year} | The year for this query (begin date) |

If a customer was selected the following values exist as well:

| Key | Description |
|---|---|
| ${customer.id} | The customer ID |
| ${customer.address} | The customer address |
| ${customer.name} | The customer name |
| ${customer.contact} | The customer contact |
| ${customer.company} | The customer company |
| ${customer.number} | The customer number |
| ${customer.country} | The customer country |
| ${customer.homepage} | The customer homepage |
| ${customer.comment} | The customer comment |

If a project was selected the following values exist as well:

| Key | Description |
|---|---|
| ${project.id} | The project ID |
| ${project.name} | The project name |
| ${project.comment} | The project name |
| ${project.order_number} | The project order number |

### Timesheet entry variables 

For each timesheet entry you can use the variables from the following table.

| Key | Description | Example |
|---|---|---|
| ${entry.row} | An empty string, used as template row for docx | |
| ${entry.description} | The entries description | _foo bar_ |
| ${entry.amount} | The format duration/amount for this entry | 02:47 h |
| ${entry.rate} | The rate for one unit of the entry (normally one hour) | 100 |
| ${entry.total} | The total rate for this entry | 278,33 |
| ${entry.duration} | The duration in seconds | 10020 |
| ${entry.duration_minutes} | The duration in minutes with no decimals | 167 |
| ${entry.begin} | The begin date (format depends on the users language) | 27.10.2018 |
| ${entry.begin_time} | The formatted time for the begin of this entry | 14:57 |
| ${entry.begin_timestamp} | The timestamp for the begin of this entry | 1542016273 |
| ${entry.end} | The begin date  (format depends on the users language) | 27.10.2018 |
| ${entry.end_time} | The formatted time for the end of this entry | 17:44 |
| ${entry.end_timestamp} | The timestamp for the end of this entry | 1542016273 |
| ${entry.date} | The start date when this record was created | 27.10.2018 |
| ${entry.user_id} | The user ID | 1 |
| ${entry.user_name} | The username | susan_super |
| ${entry.user_alias} | The user alias | Susan Miller |
| ${entry.activity} | Activity name | Post production |
| ${entry.activity_id} | Activity ID | 124 |
| ${entry.project} | Project name | Nemesis |
| ${entry.project_id} | Project ID | 10 |
| ${entry.customer} | Customer name | Acme Studios |
| ${entry.customer_id} | Customer ID | 3 |

## Configure search path

An example config `config/packages/local.yaml` file might look like this:

```yaml
kimai:
    invoice:
        documents:
            - 'var/invoices/'
```

This would disable the default documents, as Kimai will onl look in the directory `var/invoices/` for files.