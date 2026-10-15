---
mode: testing
url: http://localhost:8001/en/login
max_steps: 40
---

# Log a time entry

## Log in
Open http://localhost:8001/en/login and log in with "admin@example.com" and password "Admin12345!".
If the "Welcome to Kimai" wizard is shown, click "Next" on each wizard page until the "My times" page is shown.
Verify the "My times" page is shown.

## Create a customer and a project
Open the "Customers" page from the menu, click "Create", enter "Globex" as the name and save.
Open the "Projects" page from the menu, click "Create", enter "Mobile App" as the name, select "Globex" as the customer and save.
Open the "Activities" page from the menu, click "Create", enter "Development" as the name, select "Mobile App" as the project and save.
Verify "Development" is listed on the "Activities" page.

## Create a time entry
Open the "My times" page from the menu and click "Create".
Keep today's date, set the start time to 09:00 and the duration to 2:00.
Select "Mobile App" as the project and "Development" as the activity, enter "Sprint planning" as the description and save.

## Check the time entry
Verify the "My times" list shows an entry for project "Mobile App" with activity "Development", description "Sprint planning" and a duration of 2:00.
