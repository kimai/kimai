---
test: ../timesheet_test.md
status: passed
started: 2026-10-15T11:15:22.833Z
duration_s: 283
session_id: 8ee74592-a61e-4a88-bfaf-eec75ee0515d
---

# Log a time entry — Result

## Log in ✓ passed (71.6s)
md5: fda6134f424eb8c179002b3db281bf4e
Open http://localhost:8001/en/login and log in with "admin@example.com" and password "Admin12345!".
If the "Welcome to Kimai" wizard is shown, click "Next" on each wizard page until the "My times" page is shown.
Verify the "My times" page is shown.

## Create a customer and a project ✓ passed (120.5s)
md5: dec9c5f988bcb2faa901dbfc41a37d48
Open the "Customers" page from the menu, click "Create", enter "Globex" as the name and save.
Open the "Projects" page from the menu, click "Create", enter "Mobile App" as the name, select "Globex" as the customer and save.
Open the "Activities" page from the menu, click "Create", enter "Development" as the name, select "Mobile App" as the project and save.
Verify "Development" is listed on the "Activities" page.

## Create a time entry ✓ passed (62.3s)
md5: 6de007e255ccb0f98aa24feddf03d75c
Open the "My times" page from the menu and click "Create".
Keep today's date, set the start time to 09:00 and the duration to 2:00.
Select "Mobile App" as the project and "Development" as the activity, enter "Sprint planning" as the description and save.

## Check the time entry ✓ passed (23.3s)
md5: a3ccc416c2afcda08f758905bb4030e6
Verify the "My times" list shows an entry for project "Mobile App" with activity "Development", description "Sprint planning" and a duration of 2:00.
