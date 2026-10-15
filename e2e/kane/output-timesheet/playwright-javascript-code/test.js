import testmu, { resolveVar, setVar } from '@testmuai/playwright-bindings';
import { expect } from '@playwright/test';

testmu.configure({
  "build": "643babc9-0c32-49d8-996c-9eedeea97a7f",
  "name": "Create and Log a Time Entry for a Project Activity",
  "tcId": "TC-201",
  "network": (process.env.NETWORK ?? "false").toLowerCase() === "true",
  "autoHealVersion": "AH2",
  "defaultActionTimeoutMs": 10000,
  "defaultNavigationTimeoutMs": 60000,
  "kaneRunV4": true
});

testmu.test("Create and Log a Time Entry for a Project Activity", async (page) => {
  let element_0, element_1, element_8, element_9, element_10, element_11, element_12, element_13, element_14, element_2, element_16, element_17, element_18, element_3, element_20, element_21, element_22, element_23, element_24, element_4, element_26, element_27, element_28, element_29, element_30, element_31, element_5, element_6, element_34, element_7, element_37;
  await testmu.step("Navigate to http://localhost:8001/en/login", { instructionId: "c84416cc-9a63-4a5f-bcba-78688f448c1d" }, async () => {
    await page.goto("http://localhost:8001/en/login");
  });
  
  await testmu.step("Waiting while the login form is being filled", { instructionId: "254d34ac-8b31-42e3-9e24-07552ff0e869" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"admin@example.com\" into \"Username\"", { instructionId: "8689e7eb-1609-45ae-836b-c0ee3b883ca2" }, async () => {
    element_0 = page.locator("internal:role=textbox[name=\"Username\"i]");
    await element_0.click();
    await element_0.fill("admin@example.com");
  });
  
  await testmu.step("Type \"Admin12345!\" into \"Password I forgot my password\"", { instructionId: "0fe63102-7067-4825-86cb-a95c7ed32d86" }, async () => {
    element_1 = page.locator("internal:role=textbox[name=\"Password I forgot my password\"i]");
    await element_1.click();
    await element_1.fill("Admin12345!");
  });
  
  await testmu.step("Clicking Log in button", { instructionId: "8d7e5707-f957-42f8-93c1-ae68d0943ed9" }, async () => {
    element_8 = page.locator("internal:role=button[name=\"Log in\"i]");
    await element_8.click();
  });
  
  await testmu.step("Checking whether the Welcome to Kimai wizard is present", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("cp_1", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {3: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(3).textContent.trim() === 'Welcome to Kimai');\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=heading[name=\"Welcome to Kimai\"i]"], query: "whether the Welcome to Kimai wizard heading is present", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "the \"Welcome to Kimai\" wizard is shown" }));
  });
  
  if (await testmu.evaluateBranch([{"description": "the \"Welcome to Kimai\" wizard is shown", "store_key": "cp_1", "expected_value": "true", "stored_value": "{{cp_1}}", "operator": "equals", "transforms": []}], "and")) {
    await testmu.step("Clicking Next link in the welcome wizard", { instructionId: "6e2ac74d-6d70-4ebf-b67e-c4a0d2d05af4" }, async () => {
      element_9 = page.locator("internal:role=link[name=\"Next\"i]");
      await element_9.click();
    });
    
    await testmu.step("Clicking the Next button in your profile wizard", { instructionId: "61fd8287-29b9-41fa-8ec0-c1e11e4a3be1" }, async () => {
      element_10 = page.locator("internal:role=button[name=\"Next\"i]");
      await element_10.click();
    });
    
    await testmu.step("Clicking Next link in the Congratulations wizard card", { instructionId: "5d754a27-20cf-4dcf-9c45-da8be72a2fee" }, async () => {
      element_11 = page.locator("internal:role=link[name=\"Next\"i]");
      await element_11.click();
    });
  }
  
  await testmu.step("Checking whether the My times page is shown", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("my_times_page_shown", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {32: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(32).textContent.trim() === 'My times');\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=heading[name=\"My times\"i]"], query: "whether the My times page heading is present", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "The \"My times\" page is shown." }));
  });
  
  await testmu.step("Assertion check", { instructionId: "7aa3cc07-1de1-4525-9190-a2fd6c54377e" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "The \"My times\" page is shown.", "passed": true, "operator": "equals", "transforms": [], "json_path": null, "expected": "true", "extracted_value": "{{my_times_page_shown}}", "store_key": "my_times_page_shown", "variable_refs": {"{{my_times_page_shown}}": "true"}}], "sub_checks": [{"description": "The \"My times\" page is shown.", "store_key": "my_times_page_shown", "expected_value": "true", "extracted_value": "{{my_times_page_shown}}", "operator": "equals", "transforms": []}], "composite_operator": "and", "claim": "the \"My times\" page is shown"});
  });
  
  await testmu.step("Clicking Administration menu toggle in left sidebar", { instructionId: "81eb4d0d-30ee-48b6-82fc-f94641326a1c" }, async () => {
    element_12 = page.locator("internal:role=button[name=\"\uf552 Administration\"i]");
    await element_12.click();
  });
  
  await testmu.step("Clicking Customers in the left navigation sidebar", { instructionId: "d628e483-9dad-4bac-a801-8c6a162da93f" }, async () => {
    element_13 = page.locator("internal:role=link[name=\"\uf508 Customers\"i]");
    await element_13.click();
  });
  
  await testmu.step("Clicking Create in the Customers toolbar", { instructionId: "4b2d83d9-3a67-496a-954c-cf42addb3804" }, async () => {
    element_14 = page.locator("internal:role=link[name=\"+ Create\"i]");
    await element_14.click();
  });
  
  await testmu.step("Waiting for the plan steps to load", { instructionId: "fc1c02f2-ad29-43d5-87c5-6e5580af8f79" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"Globex\" into \"Name*\"", { instructionId: "5ba3428b-7c4e-4d1f-b9bb-775a1d1cbf73" }, async () => {
    element_2 = page.locator("internal:role=textbox[name=\"Name*\"i]");
    await element_2.click();
    await element_2.fill("Globex");
  });
  
  await testmu.step("Click \"Save\"", { instructionId: "fdf81ae1-7cdc-4fa7-9384-bb18aa8c52be" }, async () => {
    element_16 = page.locator("internal:role=button[name=\"Save\"i]");
    await element_16.click();
  });
  
  await testmu.step("Clicking Projects navigation link in left sidebar", { instructionId: "eeb332fe-72f4-4c08-a4a1-9ee3b8eeafd0" }, async () => {
    element_17 = page.locator("internal:role=link[name=\"\uf0b1 Projects\"i]");
    await element_17.click();
  });
  
  await testmu.step("Clicking Create project link in Projects toolbar", { instructionId: "2b38df96-375d-4404-83a8-5284efe35381" }, async () => {
    element_18 = page.locator("internal:role=link[name=\"+ Create\"i]");
    await element_18.click();
  });
  
  await testmu.step("Waiting for the save step to complete", { instructionId: "104571cd-bf00-42e4-a259-715f1da716a7" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"Mobile App\" into \"Name*\"", { instructionId: "4e890778-0196-4415-982f-f8e3eecb14ec" }, async () => {
    element_3 = page.locator("internal:role=textbox[name=\"Name*\"i]");
    await element_3.click();
    await element_3.fill("Mobile App");
  });
  
  await testmu.step("Clicking Customer selector dropdown in Create project dialog", { instructionId: "9b1c8482-35db-4711-9c4a-e543af4f397e" }, async () => {
    element_20 = page.locator("internal:role=combobox[name=\"Customer*\"i]");
    await element_20.click();
  });
  
  await testmu.step("Waiting for the plan steps to complete", { instructionId: "b15bcaf7-42e5-4741-a850-ee78b6a35a00" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Click \"Globex\"", { instructionId: "9d107e7e-5249-453c-a9cb-2545b687d8ca" }, async () => {
    element_21 = page.locator("#project_edit_form_customer-opt-1");
    await element_21.click();
  });
  
  await testmu.step("Click \"Save\"", { instructionId: "2f7130ce-3aaa-463b-b259-e654edf22e18" }, async () => {
    element_22 = page.locator("internal:role=button[name=\"Save\"i]");
    await element_22.click();
  });
  
  await testmu.step("Clicking Activities in the left sidebar", { instructionId: "b0c9acc0-5fa8-439a-9688-bc6a7e9b3c96" }, async () => {
    element_23 = page.locator("internal:role=link[name=\"\uf0ae Activities\"i]");
    await element_23.click();
  });
  
  await testmu.step("Clicking Create activity link in Activities toolbar", { instructionId: "22bbaaa8-dc77-48db-bc44-23da02cdc54b" }, async () => {
    element_24 = page.locator("internal:role=link[name=\"+ Create\"i]");
    await element_24.click();
  });
  
  await testmu.step("Waiting for the plan queue to be ready", { instructionId: "abf6a009-10d9-4706-bc93-ec90675671db" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"Development\" into \"Name*\"", { instructionId: "502aa077-2d87-4109-8b94-4d3676e1d74a" }, async () => {
    element_4 = page.locator("internal:role=textbox[name=\"Name*\"i]");
    await element_4.click();
    await element_4.fill("Development");
  });
  
  await testmu.step("Click \"Project\"", { instructionId: "e409ac5b-ccb7-4bf4-84a5-0bfb8ca17950" }, async () => {
    element_26 = page.locator("internal:role=combobox[name=\"Project\"i]");
    await element_26.click();
  });
  
  await testmu.step("Waiting for the next queued click to execute", { instructionId: "b00c82c3-b44f-437d-922a-b95dde235c8b" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Click \"Mobile App\"", { instructionId: "08622b94-b26e-420c-9e40-7546236d407a" }, async () => {
    element_27 = page.locator("#activity_edit_form_project-opt-3");
    await element_27.click();
  });
  
  await testmu.step("Click \"Save\"", { instructionId: "b58fa04c-5863-4571-8571-7c5b823ba326" }, async () => {
    element_28 = page.locator("internal:role=button[name=\"Save\"i]");
    await element_28.click();
  });
  
  await testmu.step("Clicking Activities navigation link in the left sidebar", { instructionId: "aae7e147-aa69-45e9-8790-29e03a9b5df1" }, async () => {
    element_29 = page.locator("internal:role=link[name=\"\uf0ae Activities\"i]");
    await element_29.click();
  });
  
  await testmu.step("Checking whether Development is listed on the Activities page", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("development_listed_on_activities_page", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {54: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(54).textContent.trim() === 'Development');\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=gridcell[name=\"Development\"i]"], query: "whether Development is listed in the Activities page table", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "\"Development\" is listed on the \"Activities\" page." }));
  });
  
  await testmu.step("Assertion check", { instructionId: "31c7084c-7eec-4896-8b1f-3cc1e1295ef4" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "\"Development\" is listed on the \"Activities\" page.", "passed": true, "operator": "equals", "transforms": [], "json_path": null, "expected": "true", "extracted_value": "{{development_listed_on_activities_page}}", "store_key": "development_listed_on_activities_page", "variable_refs": {"{{development_listed_on_activities_page}}": "true"}}], "sub_checks": [{"description": "\"Development\" is listed on the \"Activities\" page.", "store_key": "development_listed_on_activities_page", "expected_value": "true", "extracted_value": "{{development_listed_on_activities_page}}", "operator": "equals", "transforms": []}], "composite_operator": "and", "claim": "\"Development\" is listed on the \"Activities\" page."});
  });
  
  await testmu.step("Clicking My times navigation link in left sidebar", { instructionId: "4fe09920-ae50-4a79-83f4-0e7ae3df1768" }, async () => {
    element_30 = page.locator("internal:role=link[name=\"\uf017 My times\"i]");
    await element_30.click();
  });
  
  await testmu.step("Clicking + Create time record link", { instructionId: "2c445717-e43f-4b49-a1f4-802e9a71d780" }, async () => {
    element_31 = page.locator("internal:role=link[name=\"+ Create\"i]");
    await element_31.click();
  });
  
  await testmu.step("Waiting for the queued plan steps to run", { instructionId: "2207c9d0-85ef-4f97-bff3-d4e6e59ea59b" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"09:00\" into \"h:mm a\"", { instructionId: "5d563a97-a70b-47aa-aa3f-e05e529c41eb" }, async () => {
    element_5 = page.locator("#timesheet_edit_form_begin_time");
    await element_5.click();
    await element_5.fill("09:00");
  });
  
  await testmu.step("Type \"2:00\" into \"Duration / End\"", { instructionId: "126bab4f-0bdd-4486-bb06-1a9e438e89d7" }, async () => {
    element_6 = page.locator("internal:role=textbox[name=\"Duration / End\"i]");
    await element_6.click();
    await element_6.fill("2:00");
  });
  
  await testmu.step("Clicking project selector dropdown", { instructionId: "4c305f50-0e74-4232-934c-c87b2be75529" }, async () => {
    element_34 = page.locator("internal:role=combobox[name=\"Project*\"i]");
    await element_34.click();
  });
  
  await testmu.step("PRIMARY: Mobile App project option; role=option; text=\"Mobile App\" | HINTS: container=expanded Project dropdown in Create time record dialog", { instructionId: "3ead2df3-9de4-4785-8950-3ea5edfd489d" }, async () => {
    const _c_35 = await testmu.getVisionCoordinates(page, "PRIMARY: Mobile App project option; role=option; text=\"Mobile App\" | HINTS: container=expanded Project dropdown in Create time record dialog", "click", 1013, 313);
    await page.mouse.click(_c_35.x, _c_35.y);
  });
  
  await testmu.step("Waiting for the Description field to be filled and Save to be clicked", { instructionId: "71630e27-40e4-427a-93b5-4288adec2175" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"Sprint planning\" into \"Description\"", { instructionId: "67f3842d-0985-4a65-a33e-8b2d370b86f8" }, async () => {
    element_7 = page.locator("internal:role=textbox[name=\"Description\"i]");
    await element_7.click();
    await element_7.fill("Sprint planning");
  });
  
  await testmu.step("Click \"Save\"", { instructionId: "6494b9e9-35d3-4c95-8b55-e18069e0ab76" }, async () => {
    element_37 = page.locator("internal:role=button[name=\"Save\"i]");
    await element_37.click();
  });
  
  await testmu.step("Checking the matching My times entry details", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("my_times_entry_check", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {56: 0};\n  const el = (i) => els[__m[i]];\n  const __v = ((() => { const cells = el(56).querySelectorAll('td'); return cells[4]?.textContent.trim() === '2:00' && cells[9]?.textContent.trim() === 'Mobile App' && cells[10]?.textContent.trim() === 'Development' && cells[11]?.textContent.trim() === 'Sprint planning'; })());\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=row[name=\"Add entry to batch update\"i]"], query: "whether the My times list entry has project Mobile App, activity Development, description Sprint planning, and duration 2:00", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "the \"My times\" list shows an entry for project \"Mobile App\" with activity \"Development\", description \"Sprint planning\" and a duration of 2:00" }));
  });
  
  await testmu.step("Assertion check", { instructionId: "726421e8-29e7-406b-9df3-39d0e2596679" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "the \"My times\" list shows an entry for project \"Mobile App\" with activity \"Development\", description \"Sprint planning\" and a duration of 2:00", "passed": true, "operator": "equals", "transforms": [], "json_path": null, "expected": "true", "extracted_value": "{{my_times_entry_check}}", "store_key": "my_times_entry_check", "variable_refs": {"{{my_times_entry_check}}": "true"}}], "sub_checks": [{"description": "the \"My times\" list shows an entry for project \"Mobile App\" with activity \"Development\", description \"Sprint planning\" and a duration of 2:00", "store_key": "my_times_entry_check", "expected_value": "true", "extracted_value": "{{my_times_entry_check}}", "operator": "equals", "transforms": []}], "composite_operator": "and", "claim": "the \"My times\" list shows an entry for project \"Mobile App\" with activity \"Development\", description \"Sprint planning\" and a duration of 2:00"});
  });
});

testmu.run();