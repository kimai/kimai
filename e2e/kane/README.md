# End-to-end (e2e) tests for web

Browser end-to-end tests for Kimai, authored with [KaneCLI](https://www.testmuai.com/kane-cli/) by TestMu AI. Each test is a plain-English `_test.md` file; KaneCLI runs it in a real Chrome browser and records each step so later runs replay without re-authoring.

| Test | What it covers |
|---|---|
| `project_setup_test.md` | First login through the welcome wizard, then creating a customer, a project for it and an activity for the project |
| `timesheet_test.md` | Creating a customer, project and activity, then logging a 2-hour time entry and checking it in "My times" |

They complement the PHPUnit suite by going through the whole app in a browser.

## Run the tests

The tests expect Kimai at `http://localhost:8001` on a fresh database, with a super admin `admin@example.com` / `Admin12345!`. For example, with the official image and MySQL:

```bash
docker network create kimai-e2e
docker run -d --name kimai-e2e-db --network kimai-e2e \
  -e MYSQL_DATABASE=kimai -e MYSQL_USER=kimai -e MYSQL_PASSWORD=kimaipass \
  -e MYSQL_ROOT_PASSWORD=rootpass mysql:8.0
docker run -d --name kimai-e2e --network kimai-e2e -p 8001:8001 \
  -e DATABASE_URL="mysql://kimai:kimaipass@kimai-e2e-db:3306/kimai?charset=utf8mb4&serverVersion=8.0.0" \
  -e ADMINMAIL=admin@example.com -e ADMINPASS='Admin12345!' \
  kimai/kimai2:apache
```

Both tests create their own data, so reset the database (or recreate the containers) before running them again.

### With KaneCLI

```bash
npm install -g @testmuai/kane-cli
kane-cli login
kane-cli testmd run e2e/kane/project_setup_test.md --agent
kane-cli testmd run e2e/kane/timesheet_test.md --agent
```

### With the exported Playwright code

Each test also has an exported Playwright (JavaScript) version under `output-<test>/playwright-javascript-code/`:

```bash
cd e2e/kane/output-timesheet/playwright-javascript-code
npm install
npx playwright install chromium
node test.js
```

Copy `.env.example` to `.env` and fill in your TestMu AI (LambdaTest) credentials first.

## Editing a test

Edit the step text in the `_test.md` file and run it again with `kane-cli testmd run`. Changed steps (and the steps after them) are re-recorded; unchanged steps replay from `output-<test>/`.
