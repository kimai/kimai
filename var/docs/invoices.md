# Invoices

You can export your timesheet data to invoices in several formats. 

## Invoice document

Invoice documents are searched in two locations:

- `templates/invoice/renderer/`
- `var/invoices/`

Be aware of the following rules:

- Documents are addressed by their filename without extension (e.g. `kimai.html.twig` results in `kimai`) 
- You can use every document name only once: so using `kimai.html.twig` and `kimai.docx` is not possible
- The first file to be found takes precedence 
- Kimai looks first in `var/invoices/`, so you can overwrite default templates
- You should store your templates in `var/invoices/` as this directory is not shipped with Kimai and not touched during updates
- You can configure different search directories through the config key `kimai.invoice.documents` 

The invoice system currently supports the following formats:

- `HTML`
  - through the use of Twig templates
- `DOCX`
  - OOXML - Open Office XML Text
  - Microsoft Word 2007-2013 XML

Caution: the default templates were only tested with LibreOffice!

### Twig templates

Generally speaking, you should use only the variable `model` in your template which is an instance of `App\Model\InvoiceModel`.

Please see the [default templates](https://github.com/kevinpapst/kimai2/tree/master/templates/invoice/renderer) at 
GitHub to find out which variables can be used or debug it with:

```twig
{{ dump(model) }}
```

### Docx templates

Docx templates are powered by [PHPWord](https://github.com/PHPOffice/PHPWord) and its `TemplateProcessor`.

**Important:** The variable `${entry.description}` has to be set in one table row, otherwise the entries won't be rendered! 
This row will then be cloned for every timesheet entry. 

Please read `Template variables` to find out which variables you can use in your template.

### CSV templates

CSV templates are plain text files with a UTF-8 encoding and will be comma-separated.

Please read `Template variables` to find out which variables you can use in your CSV file.

**Important:** You need at least one row that contains a column starting with `${entry.`, otherwise the entries won't be rendered! 
This row will then be cloned for every timesheet entry. 

## Template variables

You can use the following global variables in your templates:

| Key | Description |
|---|---|
| ${invoice.due_date} | x |
| ${invoice.date} | x |
| ${invoice.number} | x |
| ${invoice.currency} | x |
| ${invoice.vat} | x |
| ${invoice.tax} | x |
| ${invoice.total_time} | x |
| ${invoice.total} | x |
| ${invoice.subtotal} | x |
| ${template.name} | x |
| ${template.company} | x |
| ${template.address} | x |
| ${template.title} | x |
| ${template.payment_terms} | x |
| ${template.due_days} | x |
| ${query.begin} | x |
| ${query.end} | x |
| ${query.month} | x |
| ${query.year} | x |
| ${customer.address} | x |
| ${customer.name} | x |
| ${customer.contact} | x |
| ${customer.company} | x |
| ${customer.number} | x |
| ${customer.country} | x |
| ${customer.homepage} | x |
| ${customer.comment} | x |

For each timesheet entry you can use the following variables:

| Key | Description |
|---|---|
| ${entry.description} | The entries description |
| ${entry.amount} | The amount for this entry (normally the amount of hours) |
| ${entry.rate} | The rate for one unit of the entry (normally one hour) |
| ${entry.total} | The total rate for this entry |
| ${entry.duration} | The duration in seconds |
| ${entry.begin} | The begin date - _format may change and include the time in the future_ |
| ${entry.begin_timestamp} | The timestamp for the begin of this entry |
| ${entry.end} | The begin date - _format may change and include the time in the future_ |
| ${entry.end_timestamp} | The timestamp for the end of this entry |
| ${entry.date} | The start date when this record was created |
| ${entry.user_id} | The user ID |
| ${entry.user_name} | The username |
| ${entry.user_alias} | The user alias |
| ${entry.activity} | Activity name |
| ${entry.activity_id} | Activity ID |
| ${entry.project} | Project name |
| ${entry.project_id} | Project ID |
| ${entry.customer} | Customer name |
| ${entry.customer_id} | Customer ID |

Find out more about PHPWord templates [here](https://phpword.readthedocs.io/en/latest/templates-processing.html).