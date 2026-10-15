import testmu, { resolveVar, setVar } from '@testmuai/playwright-bindings';
import { expect } from '@playwright/test';

testmu.configure({
  "build": "03e710da-6ce1-443a-b47b-009a05cce38f",
  "name": "Create Customer, Project, and Activity",
  "tcId": "TC-200",
  "network": (process.env.NETWORK ?? "false").toLowerCase() === "true",
  "autoHealVersion": "AH2",
  "defaultActionTimeoutMs": 10000,
  "defaultNavigationTimeoutMs": 60000,
  "kaneRunV4": true
});

testmu.test("Create Customer, Project, and Activity", async (page) => {
  let element_0, element_1, element_6, element_7, element_8, element_9, element_10, element_11, element_12, element_2, element_14, element_15, element_16, element_3, element_18, element_19, element_20, element_21, element_22, element_23, element_4, element_5, element_26, element_27;
  await testmu.step("Navigate to http://localhost:8001/en/login", { instructionId: "44ed92f8-8b91-40bc-b123-ce4b90e57e6e" }, async () => {
    await page.goto("http://localhost:8001/en/login");
  });
  
  await testmu.step("Waiting for the login fields to complete processing", { instructionId: "32c9f81b-a644-4875-82a6-9a9e8d4d3eea" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"admin@example.com\" into \"Username\"", { instructionId: "38c1d275-8626-46ed-96e3-ff4406b5de59" }, async () => {
    element_0 = page.locator("internal:role=textbox[name=\"Username\"i]");
    await element_0.click();
    await element_0.fill("admin@example.com");
  });
  
  await testmu.step("Type \"Admin12345!\" into \"Password I forgot my password\"", { instructionId: "12afe03e-384c-473d-b861-4d03d4300449" }, async () => {
    element_1 = page.locator("internal:role=textbox[name=\"Password I forgot my password\"i]");
    await element_1.click();
    await element_1.fill("Admin12345!");
  });
  
  await testmu.step("Clicking Log in button in the login form", { instructionId: "188c217c-a70f-446e-8061-9af7022ed6d8" }, async () => {
    element_6 = page.locator("internal:role=button[name=\"Log in\"i]");
    await element_6.click();
  });
  
  await testmu.step("Checking whether the Welcome to Kimai wizard is shown", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("welcome_to_kimai_wizard_shown", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {3: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(3).textContent.trim() === 'Welcome to Kimai');\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=heading[name=\"Welcome to Kimai\"i]"], query: "whether the Welcome to Kimai wizard heading is present", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "The \"Welcome to Kimai\" wizard is shown." }));
  });
  
  await testmu.step("Assertion check", { instructionId: "6e9080d0-4439-49f7-ae4d-a18b001e58cf" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "The \"Welcome to Kimai\" wizard is shown.", "passed": true, "operator": "equals", "transforms": [], "json_path": null, "expected": "true", "extracted_value": "{{welcome_to_kimai_wizard_shown}}", "store_key": "welcome_to_kimai_wizard_shown", "variable_refs": {"{{welcome_to_kimai_wizard_shown}}": "true"}}], "sub_checks": [{"description": "The \"Welcome to Kimai\" wizard is shown.", "store_key": "welcome_to_kimai_wizard_shown", "expected_value": "true", "extracted_value": "{{welcome_to_kimai_wizard_shown}}", "operator": "equals", "transforms": []}], "composite_operator": "and", "claim": "the \"Welcome to Kimai\" wizard is shown"});
  });
  
  await testmu.step("Clicking Next to continue the wizard introduction", { instructionId: "ce0405fc-2a3f-4657-88ef-5e7194fc7ecb" }, async () => {
    element_7 = page.locator("internal:role=link[name=\"Next\"i]");
    await element_7.click();
  });
  
  await testmu.step("Clicking the Next button in the wizard", { instructionId: "226d0609-cb27-4490-b9b0-a4557bab898f" }, async () => {
    element_8 = page.locator("internal:role=button[name=\"Next\"i]");
    await element_8.click();
  });
  
  await testmu.step("Clicking the wizard Next link", { instructionId: "9b451107-3c31-450a-8e26-d8b17db07951" }, async () => {
    element_9 = page.locator("internal:role=link[name=\"Next\"i]");
    await element_9.click();
  });
  
  await testmu.step("Clicking Administration menu button in the left sidebar", { instructionId: "52f2690e-5d9e-4972-8e03-8cc6cec264d4" }, async () => {
    element_10 = page.locator("internal:role=button[name=\"\uf552 Administration\"i]");
    await element_10.click();
  });
  
  await testmu.step("Clicking Customers navigation link in the left sidebar", { instructionId: "a97db7a0-e243-4718-8fa5-bf15723e7947" }, async () => {
    element_11 = page.locator("internal:role=link[name=\"\uf508 Customers\"i]");
    await element_11.click();
  });
  
  await testmu.step("Clicking Create customer link", { instructionId: "f3504a31-ed0a-4c1a-b80e-042177498c21" }, async () => {
    element_12 = page.locator("internal:role=link[name=\"+ Create\"i]");
    await element_12.click();
  });
  
  await testmu.step("Waiting for the next step to type the Name and save", { instructionId: "91c2d388-c79e-4356-8e3e-247d5ac2d6c8" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"Acme Corp\" into \"Name*\"", { instructionId: "4247991d-ad25-4831-8f7f-3406ea148ae1" }, async () => {
    element_2 = page.locator("internal:role=textbox[name=\"Name*\"i]");
    await element_2.click();
    await element_2.fill("Acme Corp");
  });
  
  await testmu.step("Click \"Save\"", { instructionId: "554191e1-97aa-497a-a4b4-c9e721053ae5" }, async () => {
    element_14 = page.locator("internal:role=button[name=\"Save\"i]");
    await element_14.click();
  });
  
  await testmu.step("Waiting 500 ms for saved customer details page to settle", { instructionId: "ff158f84-492a-4678-a12d-96d86b46593a" }, async () => {
    await page.waitForTimeout(500);
  });
  
  await testmu.step("Checking whether Acme Corp is listed on the Customers page", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("acme_corp_listed_on_customers_page", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {44: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(44).textContent.trim() === 'Acme Corp');\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=heading[name=\"Acme Corp\"i]"], query: "whether Acme Corp is listed on the Customers page", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "\"Acme Corp\" is listed on the \"Customers\" page." }));
  });
  
  await testmu.step("Assertion check", { instructionId: "cde57913-9f6e-459c-ac69-fb8990165d62" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "\"Acme Corp\" is listed on the \"Customers\" page.", "passed": true, "operator": "equals", "transforms": [], "json_path": null, "expected": "true", "extracted_value": "{{acme_corp_listed_on_customers_page}}", "store_key": "acme_corp_listed_on_customers_page", "variable_refs": {"{{acme_corp_listed_on_customers_page}}": "true"}}], "sub_checks": [{"description": "\"Acme Corp\" is listed on the \"Customers\" page.", "store_key": "acme_corp_listed_on_customers_page", "expected_value": "true", "extracted_value": "{{acme_corp_listed_on_customers_page}}", "operator": "equals", "transforms": []}], "composite_operator": "and", "claim": "\"Acme Corp\" is listed on the \"Customers\" page."});
  });
  
  await testmu.step("Clicking Projects navigation link in the left sidebar", { instructionId: "feea39eb-6a04-4ce8-89a1-36dd8d27afde" }, async () => {
    element_15 = page.locator("internal:role=link[name=\"\uf0b1 Projects\"i]");
    await element_15.click();
  });
  
  await testmu.step("Clicking + Create project link", { instructionId: "2fdab8bd-14bd-4d31-9415-580b2990f6d0" }, async () => {
    element_16 = page.locator("internal:role=link[name=\"+ Create\"i]");
    await element_16.click();
  });
  
  await testmu.step("Waiting for the next step to load", { instructionId: "50606b0d-3a11-42bb-801d-cf2da425011c" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"Website Redesign\" into \"Name*\"", { instructionId: "da937cd2-f8a3-419f-92db-b3e469f4052e" }, async () => {
    element_3 = page.locator("internal:role=textbox[name=\"Name*\"i]");
    await element_3.click();
    await element_3.fill("Website Redesign");
  });
  
  await testmu.step("Clicking the required customer dropdown", { instructionId: "1f5d6646-5dbf-49db-8c9a-db807b8c3167" }, async () => {
    element_18 = page.locator("internal:role=combobox[name=\"Customer*\"i]");
    await element_18.click();
  });
  
  await testmu.step("Waiting for next action to execute", { instructionId: "ca143d51-94e3-4f47-8a7d-abc2706845e2" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Click \"Acme Corp\"", { instructionId: "0f488e5d-4da1-4b70-8c48-20f55ff6b9df" }, async () => {
    element_19 = page.locator("#project_edit_form_customer-opt-1");
    await element_19.click();
  });
  
  await testmu.step("Click \"Save\"", { instructionId: "301db795-9b40-4ed7-b404-6bd3195826c2" }, async () => {
    element_20 = page.locator("internal:role=button[name=\"Save\"i]");
    await element_20.click();
  });
  
  await testmu.step("Clicking Projects navigation link in the left sidebar", { instructionId: "b2fe1a52-b021-429f-a909-73865b487449" }, async () => {
    element_21 = page.locator("internal:role=link[name=\"\uf0b1 Projects\"i]");
    await element_21.click();
  });
  
  await testmu.step("Checking the Website Redesign customer in the Projects listing", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("website_redesign_customer_listing", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {53: 0};\n  const el = (i) => els[__m[i]];\n  const __v = ((()=>{const row=el(53);const project=row.querySelector('.col_name')?.textContent?.trim();const customer=row.querySelector('.col_customer')?.textContent?.trim();return project==='Website Redesign'&&customer==='Acme Corp';})());\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=row[name=\"Website Redesign 0 Actions\"i]"], query: "whether the Projects listing contains the Website Redesign project with customer Acme Corp", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "\"Website Redesign\" is listed on the \"Projects\" page with the customer \"Acme Corp\"." }));
  });
  
  await testmu.step("Assertion check", { instructionId: "93d748e9-efc6-4eb1-a64d-950237f9c3c2" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "\"Website Redesign\" is listed on the \"Projects\" page with the customer \"Acme Corp\".", "passed": true, "operator": "equals", "transforms": [], "json_path": null, "expected": "true", "extracted_value": "{{website_redesign_customer_listing}}", "store_key": "website_redesign_customer_listing", "variable_refs": {"{{website_redesign_customer_listing}}": "true"}}], "sub_checks": [{"description": "\"Website Redesign\" is listed on the \"Projects\" page with the customer \"Acme Corp\".", "store_key": "website_redesign_customer_listing", "expected_value": "true", "extracted_value": "{{website_redesign_customer_listing}}", "operator": "equals", "transforms": []}], "composite_operator": "and", "claim": "\"Website Redesign\" is listed on the \"Projects\" page with the customer \"Acme Corp\"."});
  });
  
  await testmu.step("Clicking Activities in the left sidebar navigation", { instructionId: "3f1de4e7-8692-44bb-a8c3-1b3bcef5dd4c" }, async () => {
    element_22 = page.locator("internal:role=link[name=\"\uf0ae Activities\"i]");
    await element_22.click();
  });
  
  await testmu.step("Clicking + Create link to create a new activity", { instructionId: "b4d25dbf-4a41-41a4-9f3b-013adcc357a6" }, async () => {
    element_23 = page.locator("internal:role=link[name=\"+ Create\"i]");
    await element_23.click();
  });
  
  await testmu.step("Waiting for the plan to be queued", { instructionId: "88025894-f658-4f8f-b862-d4851a3fda95" }, async () => {
    await page.waitForTimeout(1000);
  });
  
  await testmu.step("Type \"Design\" into \"Name*\"", { instructionId: "fefe785d-4d13-4678-a891-9daf88aa561c" }, async () => {
    element_4 = page.locator("internal:role=textbox[name=\"Name*\"i]");
    await element_4.click();
    await element_4.fill("Design");
  });
  
  await testmu.step("Select \"Website Redesign\" in \"combobox\"", { instructionId: "544e9ca9-0378-4bcd-8de6-9c5d890ac2bf" }, async () => {
    element_5 = page.locator("#activity_edit_form_project");
    await element_5.selectOption();
  });
  
  await testmu.step("Click \"Save\"", { instructionId: "ae107bf0-c4b7-46f4-8966-bc87ad8cb1e4" }, async () => {
    element_26 = page.locator("internal:role=button[name=\"Save\"i]");
    await element_26.click();
  });
  
  await testmu.step("Clicking Activities in the left sidebar navigation", { instructionId: "d025d5c2-c4c6-4c8d-a394-4d382083de7c" }, async () => {
    element_27 = page.locator("internal:role=link[name=\"\uf0ae Activities\"i]");
    await element_27.click();
  });
  
  await testmu.step("Checking the project listed for the Design activity", async () => {
    await page.waitForLoadState("domcontentloaded", { timeout: 5000 });
    setVar("design_activity_project_check", await testmu.textualAnalyzer(page, { wrappedJs: "(els) => {\n  const __m = {53: 0};\n  const el = (i) => els[__m[i]];\n  const __v = (el(53) ? (el(53).querySelector('.label-color')?.textContent.trim() === 'Design' && el(53).querySelector('.label-project')?.textContent.trim() === 'Website Redesign') : null);\n  return (typeof __v === 'boolean' ? String(__v) : __v);\n}", locators: ["internal:role=row[name=\"Design 0 Actions\"i]"], query: "whether the Design activity row lists Website Redesign as its project", expectedValue: "true", needsUnitConversion: false, operator: "equals", transforms: [], condition: "\"Design\" is listed on the \"Activities\" page with the project \"Website Redesign\"." }));
  });
  
  await testmu.step("Assertion check", { instructionId: "483bdfaf-82d1-4f81-9329-e3afa8135517" }, async () => {
    await testmu.verifyAssertion(page, "Assertion check", {"operator": ["equals"], "assertion_operands": [], "left_operand": null, "right_operand": null, "operands": [], "sub_results": [{"description": "\"Design\" is listed on the \"Activities\" page with the project \"Website Redesign\".", "passed": true, "operator": "equals", "transforms": [], "json_path": null, "expected": "true", "extracted_value": "{{design_activity_project_check}}", "store_key": "design_activity_project_check", "variable_refs": {"{{design_activity_project_check}}": "true"}}], "sub_checks": [{"description": "\"Design\" is listed on the \"Activities\" page with the project \"Website Redesign\".", "store_key": "design_activity_project_check", "expected_value": "true", "extracted_value": "{{design_activity_project_check}}", "operator": "equals", "transforms": []}], "composite_operator": "and", "claim": "\"Design\" is listed on the \"Activities\" page with the project \"Website Redesign\"."});
  });
});

testmu.run();