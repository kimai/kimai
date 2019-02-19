# Customer

Customers in Kimai are used to manage project and activities, which are then used for time-records.

It is very common to have a _customer_ for your own company, to track times for administration and other internal work.
 
## Creating customer

Define the default values for a customer like this: 
```yaml
kimai:
    defaults:
        customer:
            timezone: Europe/London
            country: GB
            currency: GBP
```