-- Green Homes Portal — Demo Seed
-- Run after migration-portal.sql
-- 
--   mysql -u root -p green_homes < sql/seed-portal.sql

-- ============================================
-- 1. USERS (the team + client)
-- ============================================
-- Already created by earlier seed: password_hash = password_hash('1234', PASSWORD_DEFAULT)
-- We just update roles if needed.

UPDATE users SET role = 'super_admin'  WHERE email = 'superadmin@thegreen.homes';
UPDATE users SET role = 'admin'        WHERE email = 'admin@thegreen.homes';
UPDATE users SET role = 'client'       WHERE email = 'client@thegreen.homes';
UPDATE users SET role = 'head_engineer' WHERE email = 'headengineer@thegreen.homes';
UPDATE users SET role = 'site_engineer' WHERE email = 'siteengineer@thegreen.homes';

-- Rename to match team
UPDATE users SET name = 'Amit Poddar'      WHERE email = 'superadmin@thegreen.homes';
UPDATE users SET name = 'Seheli'           WHERE email = 'admin@thegreen.homes';
UPDATE users SET name = 'Balaram Sharma'   WHERE email = 'headengineer@thegreen.homes';
UPDATE users SET name = 'Gourav'           WHERE email = 'siteengineer@thegreen.homes';
UPDATE users SET name = 'Arindam Banerjee' WHERE email = 'client@thegreen.homes';

-- Create client with proper details if not exists
INSERT IGNORE INTO users (name, email, phone, password_hash, role) VALUES
('Amit Poddar',      'superadmin@thegreen.homes', '+91 9832012345', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin'),
('Seheli',           'admin@thegreen.homes',       '+91 9832012346', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Balaram Sharma',   'headengineer@thegreen.homes', '+91 9832012347', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'head_engineer'),
('Gourav',           'siteengineer@thegreen.homes', '+91 9832012348', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'site_engineer'),
('Arindam Banerjee', 'client@thegreen.homes',       '+91 9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client');

-- ============================================
-- 2. PROJECT: Banerjee Residence
-- ============================================
SET @client_id   = (SELECT id FROM users WHERE email = 'client@thegreen.homes');
SET @hm_id       = (SELECT id FROM users WHERE email = 'headengineer@thegreen.homes');
SET @se_id       = (SELECT id FROM users WHERE email = 'siteengineer@thegreen.homes');
SET @admin_id    = (SELECT id FROM users WHERE email = 'admin@thegreen.homes');
SET @superadmin  = (SELECT id FROM users WHERE email = 'superadmin@thegreen.homes');

INSERT INTO projects (client_id, manager_id, engineer_id, name, location, description, status, progress_percentage, start_date, expected_completion_date, budget)
VALUES (@client_id, @hm_id, @se_id,
  'Banerjee Residence',
  'Champasari, Siliguri, West Bengal',
  'Luxury G+2 Independent Villa with swimming pool, home theatre, and terrace garden. Complete design & build by Green Homes.',
  'framing',
  68.00,
  '2026-01-01',
  '2026-12-01',
  20000000.00
);
SET @pid = LAST_INSERT_ID();

-- ============================================
-- 3. PROJECT STAGES (12 stages)
-- ============================================
INSERT INTO project_stages (project_id, name, status, progress, sort_order, completed_date) VALUES
(@pid, 'Planning',    'completed', 100,  1, '2026-01-15'),
(@pid, 'Approval',    'completed', 100,  2, '2026-02-10'),
(@pid, 'Foundation',  'completed', 100,  3, '2026-03-20'),
(@pid, 'Plinth',      'completed', 100,  4, '2026-04-15'),
(@pid, 'Ground Floor','completed', 100,  5, '2026-05-25'),
(@pid, 'First Floor', 'completed', 100,  6, '2026-07-10'),
(@pid, 'Roofing',     'in_progress', 60,  7, NULL),
(@pid, 'Plaster',     'pending',    0,   8, NULL),
(@pid, 'Flooring',    'pending',    0,   9, NULL),
(@pid, 'Painting',    'pending',    0,  10, NULL),
(@pid, 'Interior',    'pending',    0,  11, NULL),
(@pid, 'Final Handover','pending',  0,  12, NULL);

-- ============================================
-- 4. MILESTONES (25 milestones for timeline)
-- ============================================
INSERT INTO milestones (project_id, title, description, status, order_index, expected_date, completed_date) VALUES
(@pid, 'Site Survey',           'Completed topographic survey of 10 Katha plot',                'completed',  1, '2026-01-05', '2026-01-05'),
(@pid, 'Soil Testing',          'Bearing capacity and soil composition analysis completed',     'completed',  2, '2026-01-08', '2026-01-08'),
(@pid, 'Architectural Planning','Floor plans, elevations, and 3D views finalized',             'completed',  3, '2026-01-15', '2026-01-15'),
(@pid, 'Structural Design',     'Structural engineering calculations and drawings approved',    'completed',  4, '2026-01-25', '2026-01-25'),
(@pid, 'Municipal Approval',    'Building plan approved by Siliguri Municipal Corporation',    'completed',  5, '2026-02-10', '2026-02-10'),
(@pid, 'Site Cleaning',         'Site cleared and temporary facilities set up',                'completed',  6, '2026-02-15', '2026-02-15'),
(@pid, 'Excavation',            'Excavation completed to required depth for foundation',       'completed',  7, '2026-02-25', '2026-02-25'),
(@pid, 'Foundation',            'Foundation PCC and footings completed',                       'completed',  8, '2026-03-15', '2026-03-15'),
(@pid, 'PCC Work',              'Plain Cement Concrete layer laid for foundation',             'completed',  9, '2026-03-20', '2026-03-20'),
(@pid, 'Footing & Column',      'Column footings and earthwork completed',                     'completed', 10, '2026-04-05', '2026-04-05'),
(@pid, 'Column Casting',        'Ground floor columns cast and cured',                         'completed', 11, '2026-04-15', '2026-04-15'),
(@pid, 'Plinth Beam',           'Plinth beam casting with proper reinforcement',               'completed', 12, '2026-04-25', '2026-04-25'),
(@pid, 'Ground Floor Slab',     'Ground floor slab casting completed with quality checks',     'completed', 13, '2026-05-15', '2026-05-15'),
(@pid, 'Ground Floor Brickwork','Brickwork for ground floor walls completed',                  'completed', 14, '2026-05-30', '2026-05-30'),
(@pid, 'First Floor Columns',   'First floor columns and beams cast',                          'completed', 15, '2026-06-15', '2026-06-15'),
(@pid, 'First Floor Slab',      'First floor slab casting completed — current stage',          'completed', 16, '2026-07-10', '2026-07-10'),
(@pid, 'Second Floor Brickwork','Second floor external & internal walls',                      'in_progress', 17, '2026-07-30', NULL),
(@pid, 'Second Floor Columns',  'Second floor column and beam casting',                        'upcoming',   18, '2026-08-15', NULL),
(@pid, 'Roof Slab',             'Roof / terrace slab casting with waterproofing',               'upcoming',   19, '2026-09-01', NULL),
(@pid, 'Waterproofing',         'Terrace and bathroom waterproofing treatment',                 'upcoming',   20, '2026-09-15', NULL),
(@pid, 'Electrical Conduits',   'Concealed electrical conduit wiring',                          'upcoming',   21, '2026-09-25', NULL),
(@pid, 'Plumbing',              'Internal plumbing and sanitary lines installed',                'upcoming',   22, '2026-10-05', NULL),
(@pid, 'Internal Plaster',      'Wall plastering (internal)',                                    'upcoming',   23, '2026-10-15', NULL),
(@pid, 'External Plaster',      'Wall plastering (external)',                                    'upcoming',   24, '2026-10-25', NULL),
(@pid, 'Flooring',              'Vitrified tiles, wooden flooring, and anti-skid tiles',         'upcoming',   25, '2026-11-05', NULL),
(@pid, 'False Ceiling',         'POP false ceiling with lighting points',                        'upcoming',   26, '2026-11-10', NULL),
(@pid, 'Painting',              'Internal & external painting with weatherproof paint',          'upcoming',   27, '2026-11-15', NULL),
(@pid, 'Interior Works',        'Modular kitchen, wardrobes, and interior finishes',             'upcoming',   28, '2026-11-20', NULL),
(@pid, 'Modular Kitchen',       'Complete modular kitchen installation',                          'upcoming',   29, '2026-11-22', NULL),
(@pid, 'Wardrobes',             'Built-in wardrobes in all bedrooms',                            'upcoming',   30, '2026-11-25', NULL),
(@pid, 'Electrical Fixtures',   'All electrical switches, fans, lights, and appliances',         'upcoming',   31, '2026-11-28', NULL),
(@pid, 'Bathroom Fixtures',     'CP fittings, sanitaryware, and mirrors in 6 bathrooms',        'upcoming',   32, '2026-11-30', NULL),
(@pid, 'Landscaping',           'Terrace garden and compound landscaping',                       'upcoming',   33, '2026-12-03', NULL),
(@pid, 'Final Cleaning',        'Deep cleaning of entire structure',                             'upcoming',   34, '2026-12-05', NULL),
(@pid, 'Quality Inspection',    'Final quality audit by Green Homes team',                       'upcoming',   35, '2026-12-08', NULL),
(@pid, 'Final Handover',        'Keys and documentation handed over to client',                  'upcoming',   36, '2026-12-01', NULL);

-- ============================================
-- 5. WEEKLY UPDATES (20 entries)
-- ============================================
INSERT INTO weekly_updates (project_id, week_number, title, work_completed, remarks, percentage_increased, progress_after, status, created_by, created_at, published_at) VALUES
(@pid, 1, 'Site Preparation & Survey', 'Site survey completed, boundary marking done. Temporary site office and material storage area set up. First team meeting held on site. Weather clear and safe to work.', 'Good start. Team is motivated and ready.', 0, 0, 'published', @se_id, '2026-01-04 17:00:00', '2026-01-04 18:00:00'),
(@pid, 2, 'Soil Testing & Analysis', 'Soil samples collected from 3 different points on site. Sent to Siliguri Civil Engineering Lab. Bearing capacity results received — suitable for G+2 construction.', 'No issues. Soil quality is excellent.', 0, 0, 'published', @se_id, '2026-01-11 17:00:00', '2026-01-11 18:00:00'),
(@pid, 3, 'Architectural Planning Progress', 'Floor plans finalized for all 3 levels. Client approved G+2 villa design with swimming pool. 3D elevation rendered and shared with client. Structural engineer started load calculations.', 'Design phase going smoothly. Client is happy with the modern elevation.', 2, 2, 'published', @hm_id, '2026-01-18 17:00:00', '2026-01-18 18:30:00'),
(@pid, 4, 'Structural Design & Drawings', 'Complete structural drawings prepared. Column placement, beam schedules, and slab reinforcement details finalized. Submitted to Siliguri Municipal Corporation for approval.', 'Engineering is sound. Ready for approval process.', 3, 5, 'published', @hm_id, '2026-01-25 17:00:00', '2026-01-25 18:00:00'),
(@pid, 5, 'Municipal Approval Obtained', 'Building plan approved by Siliguri Municipal Corporation. All fees paid. Approval letter received. Construction can now legally proceed. Celebrated with team.', 'A major milestone achieved! Full approval secured.', 5, 10, 'published', @hm_id, '2026-02-08 17:00:00', '2026-02-08 18:00:00'),
(@pid, 6, 'Site Cleaning & Barricading', 'Site completely cleaned. Temporary wooden barricading erected around plot. Site office with electricity and water connection set up. Material delivery schedule planned.', 'Site is ready for excavation. Safety barricading installed properly.', 2, 12, 'published', @se_id, '2026-02-15 17:00:00', '2026-02-15 18:00:00'),
(@pid, 7, 'Excavation Work Begins', 'JCB excavation started for foundation. Excavated to 1.5m depth as per structural drawings. Soil disposed of properly. No groundwater encountered.', 'Excavation depth achieved as per design. Quality check passed.', 3, 15, 'published', @se_id, '2026-02-22 17:00:00', '2026-02-22 18:00:00'),
(@pid, 8, 'Foundation PCC Laid', 'PCC (Plain Cement Concrete) of 1:4:8 mix laid at foundation level. Thickness maintained at 150mm as per drawing. Curing started. Labour strength: 12 workers on site.', 'PCC work done perfectly. Curing being carried out diligently.', 5, 20, 'published', @se_id, '2026-03-01 17:00:00', '2026-03-01 18:00:00'),
(@pid, 9, 'Column Footing Completed', 'All 18 column footings excavated, reinforcement placed as per structural drawing, and M20 grade concrete poured. Quality tests passed.', 'Footings are strong. Material quality is up to standard.', 5, 25, 'published', @se_id, '2026-03-08 17:00:00', '2026-03-08 18:00:00'),
(@pid, 10, 'Foundation Concreting Done', 'Full foundation concreting completed. Waterproofing treatment applied. Curing period started. 15 labourers and 2 masons worked throughout the week.', 'Foundation is solid. Ready for plinth beam work.', 5, 30, 'published', @se_id, '2026-03-15 16:00:00', '2026-03-15 18:00:00'),
(@pid, 11, 'Plinth Beam Casting', 'Plinth beam shuttering, reinforcement, and concreting completed. M25 grade concrete used. Beam dimensions as per structural drawing. Curing started immediately.', 'Plinth beam work completed ahead of schedule. Quality excellent.', 5, 35, 'published', @se_id, '2026-04-05 16:00:00', '2026-04-05 18:00:00'),
(@pid, 12, 'Ground Floor Columns Cast', 'Ground floor columns cast with M25 concrete. 12 columns in total. Shuttering removed after 7 days. Surface finish is smooth and even.', 'Columns align perfectly with grid. Good work by the team.', 5, 40, 'published', @se_id, '2026-04-19 16:00:00', '2026-04-19 18:00:00'),
(@pid, 13, 'Ground Floor Slab Casting', 'Ground floor slab (6 inch thick) cast with M25 concrete. Reinforcement mesh as per structural design. Continuous curing with water tanks placed on slab. Labour strength: 18 workers.', 'Slab casting went smoothly. Quality check passed with 100% coverage.', 5, 45, 'published', @se_id, '2026-05-10 16:00:00', '2026-05-10 18:00:00'),
(@pid, 14, 'Ground Floor Brickwork', 'External and internal brickwork for ground floor completed. 9 inch external walls with proper reinforcement at junctions. 4.5 inch internal partitions. Ventilation gaps maintained.', 'Brickwork quality is good. Mortar mix ratio maintained.', 5, 50, 'published', @se_id, '2026-05-24 16:00:00', '2026-05-24 18:00:00'),
(@pid, 15, 'First Floor Columns & Beams', 'First floor columns and peripheral beams cast. Staircase reinforcement started. Total 12 columns and 6 beams completed this week. Quality checks in progress.', 'Structural work progressing well. No safety issues.', 5, 55, 'published', @se_id, '2026-06-14 16:00:00', '2026-06-14 18:00:00'),
(@pid, 16, 'First Floor Slab Casting', 'First floor slab of 6 inch thickness cast with M25 concrete. Double mesh reinforcement. Continuous curing started. Site safety maintained throughout.', 'First floor slab completed. One more floor to go!', 5, 60, 'published', @se_id, '2026-07-05 16:00:00', '2026-07-05 18:00:00'),
(@pid, 17, 'First Floor Curing & Staircase', 'Curing of first floor slab in progress. Staircase to first floor completed. Window and door frames ordered. Material delivery for second floor received.', 'Curing is on schedule. Staircase looks excellent.', 3, 63, 'published', @se_id, '2026-07-12 16:00:00', '2026-07-12 18:00:00'),
(@pid, 18, 'Second Floor Brickwork Started', 'Brickwork for second floor started. External 9 inch walls progressing. Ground floor window frames installed. Electrical conduit pipe laying started on ground floor.', 'Brickwork is steady. Progress is on track for completion date.', 3, 66, 'published', @se_id, '2026-07-26 16:00:00', '2026-07-26 18:00:00'),
(@pid, 19, 'Second Floor Brickwork Progress', 'Second floor brickwork 60% complete. First floor electrical conduits done. Plumbing rough-in started on ground floor. Swimming pool excavation in planning.', 'Good progress. Team coordination is excellent.', 2, 68, 'published', @se_id, '2026-08-02 16:00:00', '2026-08-02 18:00:00'),
(@pid, 20, 'Second Floor Brickwork Continues', 'Second floor brickwork 80% complete. Window frames installed on first floor. Material stock received for second floor columns. Safety inspection passed.', 'Quality maintained. On schedule for next milestone.', 0, 68, 'approved', @se_id, '2026-08-09 16:00:00', '2026-08-09 18:00:00');

-- ============================================
-- 6. PHOTO ALBUMS
-- ============================================
INSERT INTO photo_albums (project_id, name, description, sort_order) VALUES
(@pid, 'Before Construction',     'Site photos before construction began — vacant land', 1),
(@pid, 'Excavation',              'Excavation work for foundation and footings', 2),
(@pid, 'Foundation',              'Foundation PCC and footing concreting', 3),
(@pid, 'Columns',                 'Column casting — ground and first floor', 4),
(@pid, 'Ground Floor',            'Ground floor slab and brickwork progress', 5),
(@pid, 'First Floor',             'First floor slab casting and columns', 6),
(@pid, 'Concrete Casting',        'Concrete pouring and finishing work', 7),
(@pid, 'Brickwork',               'Brickwork progress across all floors', 8),
(@pid, 'Steel Reinforcement',     'Reinforcement work for columns and slabs', 9),
(@pid, 'Electrical',              'Electrical conduit and wiring work', 10),
(@pid, 'Plumbing',                'Plumbing rough-in and piping work', 11),
(@pid, 'Material Delivery',       'Material deliveries on site', 12),
(@pid, 'Site Team',               'Green Homes team at work', 13),
(@pid, 'Drone Images',            'Aerial views of the construction site', 14),
(@pid, 'Quality Inspection',      'Quality control and inspection photos', 15);

-- ============================================
-- 7. PHOTOS (sample photos with placeholder URLs)
-- ============================================
SET @album_before   = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Before Construction');
SET @album_excav    = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Excavation');
SET @album_found    = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Foundation');
SET @album_cols     = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Columns');
SET @album_gf       = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Ground Floor');
SET @album_ff       = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='First Floor');
SET @album_concrete = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Concrete Casting');
SET @album_brick    = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Brickwork');
SET @album_steel    = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Steel Reinforcement');
SET @album_elec     = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Electrical');
SET @album_plumb    = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Plumbing');
SET @album_material = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Material Delivery');
SET @album_team     = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Site Team');
SET @album_drone    = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Drone Images');
SET @album_quality  = (SELECT id FROM photo_albums WHERE project_id=@pid AND name='Quality Inspection');

INSERT INTO photos (project_id, album_id, title, url, category) VALUES
-- Before Construction
(@pid, @album_before, 'Vacant Plot — Front View',  'https://placehold.co/800x600/166534/ffffff?text=Vacant+Plot+Front', 'site'),
(@pid, @album_before, 'Vacant Plot — Side View',   'https://placehold.co/800x600/166534/ffffff?text=Vacant+Plot+Side', 'site'),
(@pid, @album_before, 'Boundary Marking',           'https://placehold.co/800x600/166534/ffffff?text=Boundary+Marking', 'site'),
(@pid, @album_before, 'Site Measurement',           'https://placehold.co/800x600/166534/ffffff?text=Site+Measurement', 'site'),
(@pid, @album_before, 'Neighbouring Properties',    'https://placehold.co/800x600/166534/ffffff?text=Neighbouring+Properties', 'site'),

-- Excavation
(@pid, @album_excav, 'JCB at Work',                 'https://placehold.co/800x600/166534/ffffff?text=JCB+Excavation', 'construction'),
(@pid, @album_excav, 'Excavation Pit',              'https://placehold.co/800x600/166534/ffffff?text=Excavation+Pit', 'construction'),
(@pid, @album_excav, 'Excavation Depth Check',      'https://placehold.co/800x600/166534/ffffff?text=Depth+Check', 'construction'),
(@pid, @album_excav, 'Soil Disposal',               'https://placehold.co/800x600/166534/ffffff?text=Soil+Disposal', 'construction'),

-- Foundation
(@pid, @album_found, 'PCC Layer',                   'https://placehold.co/800x600/166534/ffffff?text=PCC+Layer', 'construction'),
(@pid, @album_found, 'Foundation Reinforcement',    'https://placehold.co/800x600/166534/ffffff?text=Foundation+Steel', 'construction'),
(@pid, @album_found, 'Footing Concreting',          'https://placehold.co/800x600/166534/ffffff?text=Footing+Concrete', 'construction'),
(@pid, @album_found, 'Foundation Curing',           'https://placehold.co/800x600/166534/ffffff?text=Foundation+Curing', 'construction'),

-- Columns
(@pid, @album_cols, 'Column Shuttering',            'https://placehold.co/800x600/166534/ffffff?text=Column+Shuttering', 'construction'),
(@pid, @album_cols, 'Column Reinforcement',         'https://placehold.co/800x600/166534/ffffff?text=Column+Steel', 'construction'),
(@pid, @album_cols, 'Column Casting GF',            'https://placehold.co/800x600/166534/ffffff?text=GF+Column+Casting', 'construction'),
(@pid, @album_cols, 'Column After Deshuttering',    'https://placehold.co/800x600/166534/ffffff?text=Column+Finish', 'construction'),

-- Ground Floor
(@pid, @album_gf, 'GF Slab Shuttering',             'https://placehold.co/800x600/166534/ffffff?text=GF+Slab+Shuttering', 'construction'),
(@pid, @album_gf, 'GF Slab Reinforcement',          'https://placehold.co/800x600/166534/ffffff?text=GF+Slab+Steel', 'construction'),
(@pid, @album_gf, 'GF Slab Concreting',             'https://placehold.co/800x600/166534/ffffff?text=GF+Slab+Pouring', 'construction'),
(@pid, @album_gf, 'GF Brickwork Started',           'https://placehold.co/800x600/166534/ffffff?text=GF+Brickwork', 'construction'),

-- First Floor
(@pid, @album_ff, 'FF Shuttering Work',             'https://placehold.co/800x600/166534/ffffff?text=FF+Shuttering', 'construction'),
(@pid, @album_ff, 'FF Slab Reinforcement',          'https://placehold.co/800x600/166534/ffffff?text=FF+Slab+Steel', 'construction'),
(@pid, @album_ff, 'FF Slab Concreting',             'https://placehold.co/800x600/166534/ffffff?text=FF+Slab+Pour', 'construction'),
(@pid, @album_ff, 'FF Slab Curing',                 'https://placehold.co/800x600/166534/ffffff?text=FF+Curing', 'construction'),

-- Concrete Casting
(@pid, @album_concrete, 'Concrete Mix Testing',     'https://placehold.co/800x600/166534/ffffff?text=Concrete+Test', 'quality'),
(@pid, @album_concrete, 'Concrete Pouring',         'https://placehold.co/800x600/166534/ffffff?text=Concrete+Pour', 'construction'),
(@pid, @album_concrete, 'Concrete Finishing',       'https://placehold.co/800x600/166534/ffffff?text=Concrete+Finish', 'construction'),
(@pid, @album_concrete, 'Cube Test Samples',        'https://placehold.co/800x600/166534/ffffff?text=Cube+Tests', 'quality'),

-- Brickwork
(@pid, @album_brick, 'GF Brickwork External',       'https://placehold.co/800x600/166534/ffffff?text=GF+External+Wall', 'construction'),
(@pid, @album_brick, 'GF Brickwork Internal',       'https://placehold.co/800x600/166534/ffffff?text=GF+Internal+Wall', 'construction'),
(@pid, @album_brick, 'FF Brickwork Progress',       'https://placehold.co/800x600/166534/ffffff?text=FF+Brickwork', 'construction'),
(@pid, @album_brick, 'Door Frame Installation',     'https://placehold.co/800x600/166534/ffffff?text=Door+Frames', 'construction'),

-- Steel Reinforcement
(@pid, @album_steel, 'Column Reinforcement Cage',   'https://placehold.co/800x600/166534/ffffff?text=Column+Cage', 'construction'),
(@pid, @album_steel, 'Beam Reinforcement',          'https://placehold.co/800x600/166534/ffffff?text=Beam+Steel', 'construction'),
(@pid, @album_steel, 'Slab Reinforcement Mesh',    'https://placehold.co/800x600/166534/ffffff?text=Slab+Mesh', 'construction'),
(@pid, @album_steel, 'Steel Quality Check',         'https://placehold.co/800x600/166534/ffffff?text=Steel+QC', 'quality'),

-- Electrical
(@pid, @album_elec, 'Conduit Laying GF',            'https://placehold.co/800x600/166534/ffffff?text=Conduit+GF', 'electrical'),
(@pid, @album_elec, 'Switch Box Positioning',       'https://placehold.co/800x600/166534/ffffff?text=Switch+Box', 'electrical'),
(@pid, @album_elec, 'DB Box Installation',          'https://placehold.co/800x600/166534/ffffff?text=DB+Box', 'electrical'),
(@pid, @album_elec, 'Wiring in Progress',           'https://placehold.co/800x600/166534/ffffff?text=Wiring', 'electrical'),

-- Plumbing
(@pid, @album_plumb, 'Bathroom Drainage Pipes',     'https://placehold.co/800x600/166534/ffffff?text=Drain+Pipes', 'plumbing'),
(@pid, @album_plumb, 'Water Supply Lines',          'https://placehold.co/800x600/166534/ffffff?text=Water+Lines', 'plumbing'),
(@pid, @album_plumb, 'Sewer Line Installation',     'https://placehold.co/800x600/166534/ffffff?text=Sewer+Line', 'plumbing'),
(@pid, @album_plumb, 'Overhead Tank Pipes',         'https://placehold.co/800x600/166534/ffffff?text=Tank+Pipes', 'plumbing'),

-- Material Delivery
(@pid, @album_material, 'Steel TMT Bars Delivery',  'https://placehold.co/800x600/166534/ffffff?text=Steel+Delivery', 'material'),
(@pid, @album_material, 'Cement Stock',              'https://placehold.co/800x600/166534/ffffff?text=Cement+Stock', 'material'),
(@pid, @album_material, 'Brick Stack',               'https://placehold.co/800x600/166534/ffffff?text=Brick+Stack', 'material'),
(@pid, @album_material, 'Sand & Aggregate Delivery',  'https://placehold.co/800x600/166534/ffffff?text=Sand+Aggregate', 'material'),

-- Site Team
(@pid, @album_team, 'Morning Meeting',               'https://placehold.co/800x600/166534/ffffff?text=Morning+Meeting', 'team'),
(@pid, @album_team, 'Engineer on Site',              'https://placehold.co/800x600/166534/ffffff?text=Engineer', 'team'),
(@pid, @album_team, 'Mason at Work',                 'https://placehold.co/800x600/166534/ffffff?text=Mason+Work', 'team'),
(@pid, @album_team, 'Labour Team Photo',             'https://placehold.co/800x600/166534/ffffff?text=Team+Photo', 'team'),

-- Drone Images
(@pid, @album_drone, 'Aerial View — Foundation',     'https://placehold.co/800x600/166534/ffffff?text=Drone+Foundation', 'drone'),
(@pid, @album_drone, 'Aerial View — GF Slab',        'https://placehold.co/800x600/166534/ffffff?text=Drone+GF+Slab', 'drone'),
(@pid, @album_drone, 'Aerial View — FF Progress',    'https://placehold.co/800x600/166534/ffffff?text=Drone+FF', 'drone'),
(@pid, @album_drone, 'Aerial View — Full Site',      'https://placehold.co/800x600/166534/ffffff?text=Drone+Full+Site', 'drone'),

-- Quality Inspection
(@pid, @album_quality, 'Slab Thickness Check',       'https://placehold.co/800x600/166534/ffffff?text=Slab+Thickness', 'quality'),
(@pid, @album_quality, 'Brick Alignment Check',      'https://placehold.co/800x600/166534/ffffff?text=Alignment+Check', 'quality'),
(@pid, @album_quality, 'Concrete Strength Test',     'https://placehold.co/800x600/166534/ffffff?text=Strength+Test', 'quality'),
(@pid, @album_quality, 'Safety Inspection',          'https://placehold.co/800x600/166534/ffffff?text=Safety+Check', 'quality');

-- ============================================
-- 8. BEFORE / AFTER
-- ============================================
INSERT INTO before_after (project_id, stage_name, before_url, after_url, description) VALUES
(@pid, 'Site',       'https://placehold.co/600x400/166534/ffffff?text=Before+Site',  'https://placehold.co/600x400/166534/ffffff?text=After+Foundation',  'Transformation from vacant plot to foundation completed'),
(@pid, 'Foundation', 'https://placehold.co/600x400/166534/ffffff?text=Excavation',   'https://placehold.co/600x400/166534/ffffff?text=Footing+Done',     'Excavation to completed column footings'),
(@pid, 'Ground Floor','https://placehold.co/600x400/166534/ffffff?text=Before+GF',   'https://placehold.co/600x400/166534/ffffff?text=GF+Slab+Casted',   'Ground floor slab transformation'),
(@pid, 'First Floor', 'https://placehold.co/600x400/166534/ffffff?text=GF+Brickwork', 'https://placehold.co/600x400/166534/ffffff?text=FF+Slab+Done',    'Ground floor walls to first floor slab');

-- ============================================
-- 9. DOCUMENTS
-- ============================================
INSERT INTO documents (project_id, title, url, type) VALUES
(@pid, 'Construction Agreement',          'https://placehold.co/595x842/166534/ffffff?text=Agreement', 'agreement'),
(@pid, 'Architectural Drawing — Ground Floor', 'https://placehold.co/595x842/166534/ffffff?text=Arch+GF', 'drawing'),
(@pid, 'Architectural Drawing — First Floor',  'https://placehold.co/595x842/166534/ffffff?text=Arch+FF', 'drawing'),
(@pid, 'Architectural Drawing — Second Floor', 'https://placehold.co/595x842/166534/ffffff?text=Arch+SF', 'drawing'),
(@pid, 'Structural Drawing — Foundation',     'https://placehold.co/595x842/166534/ffffff?text=Struct+Foundation', 'drawing'),
(@pid, 'Structural Drawing — Columns & Beams','https://placehold.co/595x842/166534/ffffff?text=Struct+Columns', 'drawing'),
(@pid, 'Electrical Layout Plan',              'https://placehold.co/595x842/166534/ffffff?text=Electrical+Layout', 'drawing'),
(@pid, 'Plumbing Layout Plan',                'https://placehold.co/595x842/166534/ffffff?text=Plumbing+Layout', 'drawing'),
(@pid, '3D Elevation — Front View',           'https://placehold.co/800x600/166534/ffffff?text=3D+Elevation+Front', 'design'),
(@pid, '3D Elevation — Rear View',           'https://placehold.co/800x600/166534/ffffff?text=3D+Elevation+Rear', 'design'),
(@pid, 'Municipal Approval Certificate',      'https://placehold.co/595x842/166534/ffffff?text=Municipal+Approval', 'approval'),
(@pid, 'Soil Test Report',                    'https://placehold.co/595x842/166534/ffffff?text=Soil+Test+Report', 'report'),
(@pid, 'Material Test Report — Concrete',     'https://placehold.co/595x842/166534/ffffff?text=Concrete+Test', 'report'),
(@pid, 'Material Test Report — Steel',        'https://placehold.co/595x842/166534/ffffff?text=Steel+Test+Report', 'report'),
(@pid, 'Warranty Certificate',                'https://placehold.co/595x842/166534/ffffff?text=Warranty', 'warranty'),
(@pid, 'Payment Receipt — Booking Advance',   'https://placehold.co/595x842/166534/ffffff?text=Booking+Receipt', 'receipt'),
(@pid, 'Payment Receipt — Foundation',        'https://placehold.co/595x842/166534/ffffff?text=Foundation+Receipt', 'receipt'),
(@pid, 'Payment Receipt — Ground Floor',      'https://placehold.co/595x842/166534/ffffff?text=GF+Receipt', 'receipt'),
(@pid, 'Insurance Document',                  'https://placehold.co/595x842/166534/ffffff?text=Insurance', 'insurance'),
(@pid, 'Completion Certificate (Draft)',      'https://placehold.co/595x842/166534/ffffff?text=Completion+Cert', 'certificate');

-- ============================================
-- 10. INVOICES & PAYMENTS
-- ============================================
INSERT INTO invoices (project_id, invoice_no, title, amount, status, due_date, paid_date, description) VALUES
(@pid, 'INV-GH-2026-001', 'Booking Advance',         2000000.00, 'paid',   '2026-01-01', '2026-01-01', 'Booking advance to commence site survey and planning'),
(@pid, 'INV-GH-2026-002', 'Foundation Milestone',     3000000.00, 'paid',   '2026-03-15', '2026-03-20', 'Payment upon foundation completion'),
(@pid, 'INV-GH-2026-003', 'Ground Floor Milestone',   3500000.00, 'paid',   '2026-05-25', '2026-06-01', 'Payment upon ground floor slab completion'),
(@pid, 'INV-GH-2026-004', 'First Floor Milestone',    3500000.00, 'pending','2026-07-25', NULL,        'Payment upon first floor slab completion'),
(@pid, 'INV-GH-2026-005', 'Roof Casting Milestone',   2500000.00, 'pending','2026-09-15', NULL,        'Payment upon roof casting'),
(@pid, 'INV-GH-2026-006', 'Interior Work Milestone',  3000000.00, 'pending','2026-11-20', NULL,        'Payment upon interior completion'),
(@pid, 'INV-GH-2026-007', 'Final Handover Milestone', 2500000.00, 'pending','2026-12-01', NULL,        'Final payment at handover');

-- Payment history
INSERT INTO payment_history (invoice_id, amount, payment_date, payment_method, reference_no, receipt_url, notes) VALUES
((SELECT id FROM invoices WHERE invoice_no='INV-GH-2026-001'), 2000000, '2026-01-01', 'bank_transfer', 'GH-BT-2026001', 'https://placehold.co/595x842/166534/ffffff?text=Receipt+001', 'Booking advance paid via NEFT'),
((SELECT id FROM invoices WHERE invoice_no='INV-GH-2026-001'), 100000,  '2026-01-01', 'bank_transfer', 'GH-BT-2026002', 'https://placehold.co/595x842/166534/ffffff?text=Receipt+002', 'GST component'),
((SELECT id FROM invoices WHERE invoice_no='INV-GH-2026-002'), 3000000, '2026-03-20', 'bank_transfer', 'GH-BT-2026003', 'https://placehold.co/595x842/166534/ffffff?text=Receipt+003', 'Foundation milestone paid'),
((SELECT id FROM invoices WHERE invoice_no='INV-GH-2026-003'), 3500000, '2026-06-01', 'bank_transfer', 'GH-BT-2026004', 'https://placehold.co/595x842/166534/ffffff?text=Receipt+004', 'Ground floor milestone paid');

-- ============================================
-- 11. MATERIAL TRACK
-- ============================================
INSERT INTO material_tracking (project_id, material_name, quantity_ordered, quantity_delivered, unit, status, expected_date, received_date, notes) VALUES
(@pid, 'Cement (OPC 53 Grade)',        4500, 3800, 'bags',   'received', '2026-02-01', '2026-02-05', 'Ultratech cement — regular supply'),
(@pid, 'Steel TMT Bars (Fe-550)',      85000, 72000, 'kg',    'received', '2026-02-01', '2026-02-08', 'Tata Tiscon bars — 12mm to 20mm'),
(@pid, 'Bricks (Class A)',             120000, 95000, 'nos',   'received', '2026-02-15', '2026-02-20', 'Champasari brick kiln supply'),
(@pid, 'River Sand',                   720, 600, 'tonnes',    'received', '2026-02-15', '2026-02-18', 'Teesta river sand — fine quality'),
(@pid, 'Coarse Aggregate (20mm)',       580, 480, 'tonnes',    'received', '2026-02-15', '2026-02-22', 'Crushed granite aggregate'),
(@pid, 'Coarse Aggregate (40mm)',       320, 280, 'tonnes',    'received', '2026-03-01', '2026-03-05', 'For PCC and foundation work'),
(@pid, 'Electrical Pipes (PVC)',        650, 500, 'mtr',     'received', '2026-05-01', '2026-05-10', 'Finolex PVC conduits'),
(@pid, 'Plumbing Pipes (CPVC)',         480, 350, 'mtr',     'received', '2026-05-01', '2026-05-12', 'Astral CPVC pipes'),
(@pid, 'Tiles (Vitrified 800x800)',     850, 0,   'boxes',   'pending',  '2026-09-01', NULL,        'For living/dining flooring'),
(@pid, 'Tiles (Anti-skid 300x300)',     350, 0,   'boxes',   'pending',  '2026-09-01', NULL,        'For bathroom and terrace'),
(@pid, 'Tiles (Wooden Finish)',          120, 0,   'boxes',   'pending',  '2026-09-01', NULL,        'For bedrooms'),
(@pid, 'Granite (Countertop)',           45, 0,   'sqft',    'pending',  '2026-10-01', NULL,        'Kitchen countertop — Absolute Black'),
(@pid, 'Marble (Flooring)',             200, 0,   'sqft',    'pending',  '2026-10-01', NULL,        'Living room — Italian Marble'),
(@pid, 'Doors (Flush)',                 18, 12,  'nos',     'partial',  '2026-06-01', '2026-06-15', 'Teak frame with flush shutters'),
(@pid, 'Windows (UPVC)',               12, 8,   'nos',     'partial',  '2026-06-01', '2026-06-20', 'Fenesta UPVC sliding windows'),
(@pid, 'Paint (Interior)',             200, 0,   'ltr',     'pending',  '2026-09-01', NULL,        'Asian Paints Royale — matte finish'),
(@pid, 'Waterproofing Chemical',       120, 80,  'kg',     'received', '2026-03-01', '2026-03-10', 'Fosroc waterproofing compound');

-- ============================================
-- 12. ENGINEER DAILY LOGS
-- ============================================
INSERT INTO engineer_notes (project_id, note_date, content, created_by, created_at) VALUES
(@pid, '2026-07-28', 'Today''s work: Second floor outer wall brickwork continued. 4 masons and 8 labourers on site. Completed 60% of second floor outer wall. Electrical conduit laying started on ground floor.\n\nTomorrow''s plan: Continue second floor brickwork. Start inner wall brickwork. Electrical team to complete GF conduit.\n\nIssues: None. Material stock adequate.\n\nSafety: All workers wearing helmets. Barricading intact.', @se_id, '2026-07-28 17:00:00'),
(@pid, '2026-07-29', 'Today''s work: Second floor brickwork 65% complete. Ground floor electrical conduits 50% done. Window frame installation on first floor started.\n\nTomorrow''s plan: Continue brickwork. Complete GF electrical conduits. Install remaining first floor window frames.\n\nIssues: Sand stock running low. Need to order 50 tonnes.\n\nSafety: No incidents.', @se_id, '2026-07-29 17:00:00'),
(@pid, '2026-07-30', 'Today''s work: Second floor brickwork 70% complete. GF electrical conduits completed. First floor window frames 80% done.\n\nTomorrow''s plan: Continue brickwork. Start plumbing rough-in on ground floor bathroom. Order sand.\n\nIssues: Addressed sand order with supplier. Expected delivery in 2 days.\n\nSafety: Regular safety briefing conducted.', @se_id, '2026-07-30 17:00:00'),
(@pid, '2026-07-31', 'Today''s work: Second floor brickwork progressed to 72%. Plumbing rough-in started on GF bathroom 1 & 2. Sand delivery confirmed.\n\nTomorrow''s plan: Continue all ongoing works. Steel cutting for second floor columns to begin next week.\n\nIssues: Minor delay in tile sample approval from client. Following up.\n\nSafety: All clear.', @se_id, '2026-07-31 17:00:00'),
(@pid, '2026-08-01', 'Today''s work: Second floor brickwork 75%. GF plumbing rough-in for bathroom 1 complete. Sand delivered — 50 tonnes received.\n\nTomorrow''s plan: Complete GF plumbing for bathroom 2 & 3. Continue second floor brickwork.\n\nIssues: None.\n\nSafety: Weekly safety inspection passed. All equipment in good condition.', @se_id, '2026-08-01 17:00:00'),
(@pid, '2026-08-02', 'Today''s work: Second floor brickwork 78%. GF bathroom plumbing rough-in complete for all 3 bathrooms. First floor window frames fully installed.\n\nTomorrow''s plan: Continue second floor brickwork. Start staircase plaster preparation.\n\nIssues: None.\n\nWeather: Clear. Good working conditions.', @se_id, '2026-08-02 17:00:00'),
(@pid, '2026-08-04', 'Today''s work: Second floor brickwork 80% complete. Staircase preparation started. Material planning for second floor columns underway.\n\nTomorrow''s plan: Continue brickwork. Prepare column reinforcement for second floor.\n\nIssues: Need to schedule concrete pump for column casting next week.\n\nQuality: Brick alignment checked. All good.', @se_id, '2026-08-04 17:00:00'),
(@pid, '2026-08-05', 'Today''s work: Second floor brickwork 82%. Column steel cutting started. Staircase formwork prepared.\n\nTomorrow''s plan: Continue column preparation. Complete remaining brickwork by end of week.\n\nIssues: None major.\n\nSafety: All safety protocols followed.', @se_id, '2026-08-05 17:00:00'),
(@pid, '2026-08-06', 'Today''s work: Second floor brickwork 85%. Column reinforcement ready for 6 columns. Staircase plaster work started.\n\nTomorrow''s plan: Continue brickwork to 90%. Start column shuttering.\n\nIssues: Waiting for client approval on tile selection for GF living room.\n\nNotes: Project overall on schedule.', @se_id, '2026-08-06 17:00:00');

-- ============================================
-- 13. UPCOMING WORKS
-- ============================================
INSERT INTO upcoming_works (project_id, title, description, week_commencing, status, sort_order) VALUES
(@pid, 'Complete Second Floor Brickwork',    'Finish all external and internal brickwork for second floor', '2026-08-10', 'in_progress', 1),
(@pid, 'Second Floor Column Casting',        'Cast 12 columns for second floor with M25 concrete', '2026-08-17', 'planned', 2),
(@pid, 'Swimming Pool Excavation',           'Begin excavation for swimming pool in backyard', '2026-08-24', 'planned', 3),
(@pid, 'Roof Slab Shuttering',              'Start shuttering work for roof/terrace slab', '2026-09-01', 'planned', 4),
(@pid, 'Roof Slab Casting',                 'Cast roof slab with waterproofing admixture', '2026-09-08', 'planned', 5),
(@pid, 'Terrace Waterproofing',             'Apply waterproofing treatment on terrace', '2026-09-15', 'planned', 6),
(@pid, 'Electrical Conduit — FF & SF',      'Complete concealed conduit work for first and second floor', '2026-09-22', 'planned', 7),
(@pid, 'Plumbing Rough-in — FF & SF',       'Complete plumbing rough-in for first and second floor bathrooms', '2026-10-01', 'planned', 8),
(@pid, 'Internal Plastering',              'Start internal wall plastering across all floors', '2026-10-08', 'planned', 9),
(@pid, 'External Plastering',              'Start external wall plastering with waterproof plaster', '2026-10-22', 'planned', 10);

-- ============================================
-- 14. ISSUES
-- ============================================
INSERT INTO project_issues (project_id, title, description, impact, status, severity, reported_by, created_at) VALUES
(@pid, 'Waterlogging at Foundation Pit', 'Heavy rain caused water accumulation in excavation pit. Had to pump out water before concreting.', 'Minor delay of 1 day', 'resolved', 'high', @se_id, '2026-02-20 10:00:00'),
(@pid, 'Steel Delivery Delay', 'TMT bars delivery delayed by 3 days due to transport strike in Siliguri.', 'Delayed column casting by 2 days', 'resolved', 'medium', @se_id, '2026-04-02 10:00:00'),
(@pid, 'Labour Shortage During Festival', '10 workers took leave during Durga Puja festival week.', 'Reduced progress for 1 week', 'resolved', 'medium', @hm_id, '2026-04-10 10:00:00'),
(@pid, 'Concrete Mix Design Adjustment', 'Initial mix design for M25 showed lower strength. Adjusted mix ratio from lab recommendation.', 'No delay — caught early', 'resolved', 'critical', @hm_id, '2026-03-05 10:00:00'),
(@pid, 'Neighbour Complaint — Noise', 'Adjacent property owner filed complaint about construction noise beyond permitted hours.', 'Stopped work at 6 PM for 2 days', 'resolved', 'low', @se_id, '2026-03-15 10:00:00'),
(@pid, 'Tile Sample Not Approved', 'Client hasn''t approved tile samples for living room flooring. Selection pending since 2 weeks.', 'May delay flooring start', 'open', 'high', @hm_id, '2026-08-01 10:00:00');

-- ============================================
-- 15. CLIENT APPROVALS
-- ============================================
INSERT INTO client_approvals (project_id, title, description, options_json, deadline, status, created_by, created_at) VALUES
(@pid, 'Floor Plan Approval', 'Please review and approve the final floor plans for all three levels.', '{"options":["Approve as submitted","Approve with changes","Request revision"]}', '2026-01-10', 'approved', @hm_id, '2026-01-05 10:00:00'),
(@pid, 'Exterior Elevation Design', 'Please select the preferred exterior finish and color scheme.', '{"options":["Modern Contemporary — Grey & White","Classic — Warm Beige & Brown","Premium — Stone Texture & Wood"]}', '2026-01-20', 'approved', @hm_id, '2026-01-12 10:00:00'),
(@pid, 'Swimming Pool Design', 'Approve the swimming pool design and location.', '{"options":["Rectangular — Backyard (15x30 ft)","L-shaped — Side (20x25 ft)","Free form — Backyard (custom)"]}', '2026-08-15', 'pending', @hm_id, '2026-08-01 10:00:00'),
(@pid, 'Tile Selection — Living Room', 'Select flooring tiles for living and dining area.', '{"options":["Italian Marble — 800x800","Vitrified Tiles — 800x800 wood finish","Vitrified Tiles — 800x800 marble look"]}', '2026-08-20', 'pending', @hm_id, '2026-08-01 10:00:00');

-- ============================================
-- 16. NOTIFICATIONS
-- ============================================
INSERT INTO notifications (user_id, project_id, type, title, message, created_at) VALUES
(@client_id, @pid, 'milestone', '🎉 First Floor Slab Completed', 'The first floor slab has been cast successfully. Your home is now 68% complete!', '2026-07-10 18:00:00'),
(@client_id, @pid, 'weekly', '📋 Weekly Update Published', 'Week 19 update is now available. Second floor brickwork is progressing well.', '2026-08-02 18:00:00'),
(@client_id, @pid, 'milestone', '🏗️ New Milestone Reached', 'Good news! Your project is ahead of the construction timeline.', '2026-07-26 18:00:00'),
(@client_id, @pid, 'payment', '💰 Payment Reminder', 'Invoice INV-GH-2026-004 of ₹35,00,000 is due on 25 July 2026 for First Floor Milestone.', '2026-07-20 10:00:00'),
(@client_id, @pid, 'photo', '📸 New Photos Uploaded', '20 new photos added to your project gallery. Check them out!', '2026-07-15 14:00:00'),
(@client_id, @pid, 'document', '📄 New Document Added', 'Updated structural drawings have been uploaded to your document center.', '2026-07-20 16:00:00'),
(@client_id, @pid, 'approval', '✅ Approval Needed', 'Please review and approve the swimming pool design options in the Approvals section.', '2026-08-01 12:00:00'),
(@client_id, @pid, 'general', '🔧 Site Visit Scheduled', 'Your next site visit is scheduled for 15 August 2026 at 10:00 AM with Balaram Sharma.', '2026-08-05 09:00:00'),
(@hm_id, @pid, 'general', 'Weekly update pending approval', 'Week 20 update submitted by Gourav. Please review and approve.', '2026-08-09 18:00:00'),
(@admin_id, @pid, 'general', 'New project milestone reached', 'Banerjee Residence crossing 68% completion. Invoice pending for First Floor.', '2026-07-11 10:00:00');

-- ============================================
-- 17. CHAT MESSAGES
-- ============================================
INSERT INTO chat_messages (project_id, sender_id, message, created_at) VALUES
(@pid, @hm_id,    'Good morning Mr. Banerjee! Just a quick update — first floor slab was cast successfully yesterday. The curing process is underway and looking great. 🎉', '2026-07-11 09:00:00'),
(@pid, @client_id, 'Excellent news! Thank you for the update. The photos look wonderful. Can we discuss the tile selection for the living room this week?', '2026-07-11 10:30:00'),
(@pid, @hm_id,    'Absolutely! I''ve shared 3 tile options in the Approvals section. Please take a look when you get a chance. We also need your input on the swimming pool design.', '2026-07-11 11:00:00'),
(@pid, @client_id, 'Perfect, I''ll review them this weekend. Also, we''re considering changing the master bedroom window to a larger size. Is that possible at this stage?', '2026-07-11 14:00:00'),
(@pid, @hm_id,    'Let me check with the structural engineer. Since we''re still early in first floor work, modifications should be possible. I''ll get back to you with the feasibility and any cost implications.', '2026-07-11 15:00:00'),
(@pid, @hm_id,    'Good news! The structural engineer confirmed we can increase the window size without affecting structural integrity. I''ll send you the revised drawing for approval.', '2026-07-13 10:00:00'),
(@pid, @client_id, 'That''s great! Please go ahead. The extra natural light will make a big difference.', '2026-07-13 14:00:00');

-- ============================================
-- 18. APPOINTMENTS
-- ============================================
INSERT INTO appointments (project_id, client_id, title, purpose, appointment_date, appointment_time, status) VALUES
(@pid, @client_id, 'Initial Site Meeting',      'First meeting to discuss project scope and timelines',         '2026-01-03', '10:00:00', 'completed'),
(@pid, @client_id, 'Design Review Meeting',     'Review architectural drawings and floor plans',                '2026-01-12', '11:00:00', 'completed'),
(@pid, @client_id, 'Foundation Inspection Visit','Inspect foundation work after completion',                     '2026-03-22', '10:00:00', 'completed'),
(@pid, @client_id, 'GF Slab Inspection',        'Visit site to inspect ground floor slab casting',               '2026-05-18', '10:00:00', 'completed'),
(@pid, @client_id, 'Progress Review Meeting',   'Monthly progress review and upcoming milestone discussion',     '2026-08-15', '10:00:00', 'confirmed'),
(@pid, @client_id, 'Tile Selection Appointment', 'Visit showroom to finalize tile and sanitaryware selections',  '2026-08-20', '11:00:00', 'pending');

-- ============================================
-- 19. SUGGESTIONS
-- ============================================
INSERT INTO suggestions (project_id, title, description, status, created_at) VALUES
(@pid, 'Additional Power Outlets in Living Room', 'Can we add 2 more power outlets near the TV unit area? We plan to have a home theatre system.', 'pending', '2026-07-20 14:00:00'),
(@pid, 'Charging Point in Car Parking', 'Please add an EV charging point in the car parking area.', 'pending', '2026-07-25 16:00:00');

-- ============================================
-- 20. DASHBOARD TOKENS (so existing tokens work)
-- ============================================
-- Tokens are handled at login time via api.php. No need to pre-seed.

SELECT '✅ Seed complete!' AS status;
SELECT CONCAT('Project ID: ', @pid) AS project_info;
SELECT CONCAT('Client: Arindam Banerjee (ID: ', @client_id, ')') AS client_info;
