---
test: ../project_setup_test.md
status: passed
started: 2026-10-15T11:10:58.470Z
duration_s: 224
session_id: 7e947f38-d28d-4b57-8d0b-8f7cf21d848b
---

# First login and project setup — Result

## Log in and finish the welcome wizard ✓ passed (56s)
md5: 47d50053418aae0974e97b3fc6d42ee0
Open http://localhost:8001/en/login and log in with "admin@example.com" and password "Admin12345!".
Verify the "Welcome to Kimai" wizard is shown.
Click "Next" on each wizard page, keeping the default profile settings, until the "My times" page is shown.

## Create a customer ✓ passed (46.5s)
md5: 9aa127092c5d63783e1ee51164215e1b
Open the "Customers" page from the menu and click "Create".
Enter "Acme Corp" as the name and save.
Verify "Acme Corp" is listed on the "Customers" page.

## Create a project for the customer ✓ passed (64.1s)
md5: 98ad0fb30249a114aee157d46180bd00
Open the "Projects" page from the menu and click "Create".
Enter "Website Redesign" as the name, select "Acme Corp" as the customer and save.
Verify "Website Redesign" is listed on the "Projects" page with the customer "Acme Corp".

## Create an activity for the project ✓ passed (51.4s)
md5: 7dedbdeb3caf67682a83bad3217dd9ab
Open the "Activities" page from the menu and click "Create".
Enter "Design" as the name, select "Website Redesign" as the project and save.
Verify "Design" is listed on the "Activities" page with the project "Website Redesign".
