---
mode: testing
url: http://localhost:8001/en/login
max_steps: 40
---

# First login and project setup

## Log in and finish the welcome wizard
Open http://localhost:8001/en/login and log in with "admin@example.com" and password "Admin12345!".
Verify the "Welcome to Kimai" wizard is shown.
Click "Next" on each wizard page, keeping the default profile settings, until the "My times" page is shown.

## Create a customer
Open the "Customers" page from the menu and click "Create".
Enter "Acme Corp" as the name and save.
Verify "Acme Corp" is listed on the "Customers" page.

## Create a project for the customer
Open the "Projects" page from the menu and click "Create".
Enter "Website Redesign" as the name, select "Acme Corp" as the customer and save.
Verify "Website Redesign" is listed on the "Projects" page with the customer "Acme Corp".

## Create an activity for the project
Open the "Activities" page from the menu and click "Create".
Enter "Design" as the name, select "Website Redesign" as the project and save.
Verify "Design" is listed on the "Activities" page with the project "Website Redesign".
