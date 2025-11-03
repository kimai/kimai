-- Demo Data for Kimai Testing
-- Run this to create sample customers, projects, activities, and tags

-- Insert Demo Customer
INSERT INTO kimai2_customers (name, number, country, currency, timezone, visible, billable, color) 
VALUES ('Mountain Dev', 'CUST-001', 'US', 'USD', 'America/New_York', 1, 1, '#4CAF50');

SET @customer_id = LAST_INSERT_ID();

-- Insert Demo Projects
INSERT INTO kimai2_projects (customer_id, name, order_number, visible, billable, color) VALUES
(@customer_id, 'Website Redesign', 'PROJ-001', 1, 1, '#2196F3'),
(@customer_id, 'Mobile App Development', 'PROJ-002', 1, 1, '#FF9800'),
(@customer_id, 'Internal Tools', 'PROJ-003', 1, 1, '#9C27B0');

SET @project1_id = (SELECT id FROM kimai2_projects WHERE order_number = 'PROJ-001' LIMIT 1);
SET @project2_id = (SELECT id FROM kimai2_projects WHERE order_number = 'PROJ-002' LIMIT 1);
SET @project3_id = (SELECT id FROM kimai2_projects WHERE order_number = 'PROJ-003' LIMIT 1);

-- Insert Demo Activities
INSERT INTO kimai2_activities (project_id, name, visible, billable, color) VALUES
(@project1_id, 'Frontend Development', 1, 1, '#00BCD4'),
(@project1_id, 'Backend Development', 1, 1, '#3F51B5'),
(@project1_id, 'Design', 1, 1, '#E91E63'),
(@project2_id, 'iOS Development', 1, 1, '#607D8B'),
(@project2_id, 'Android Development', 1, 1, '#8BC34A'),
(@project2_id, 'Testing', 1, 1, '#FFC107'),
(@project3_id, 'Bug Fixing', 1, 1, '#F44336'),
(@project3_id, 'Code Review', 1, 1, '#673AB7'),
(@project3_id, 'Documentation', 1, 1, '#009688'),
(NULL, 'Meeting', 1, 1, '#795548'),
(NULL, 'Planning', 1, 1, '#9E9E9E');

-- Insert Demo Tags
INSERT INTO kimai2_tags (name, color) VALUES
('Meeting', '#795548'),
('Development', '#2196F3'),
('Bug Fix', '#F44336'),
('Feature', '#4CAF50'),
('Review', '#673AB7'),
('Testing', '#FFC107'),
('Documentation', '#009688'),
('Urgent', '#FF5722'),
('Client Call', '#FF9800'),
('Internal', '#607D8B');

-- Show what was created
SELECT 'Customer Created:' as info, name FROM kimai2_customers WHERE name = 'Mountain Dev';
SELECT 'Projects Created:' as info, name FROM kimai2_projects WHERE customer_id = @customer_id;
SELECT 'Activities Created:' as info, name FROM kimai2_activities WHERE project_id IN (@project1_id, @project2_id, @project3_id) OR project_id IS NULL;
SELECT 'Tags Created:' as info, name FROM kimai2_tags;
