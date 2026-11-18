<?php
header('Access-Control-Allow-Origin: https://app.thegreen.homes');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'error'=>'POST required']); exit; }

try {
    require_once dirname(__DIR__, 3) . '/src/config.php';
    gh_load_env(dirname(__DIR__, 3));

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $token = trim($input['token'] ?? '');
    if (!$token) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Token required']); exit; }

    $st = db()->prepare('SELECT u.role FROM dashboard_tokens dt JOIN users u ON u.id=dt.user_id WHERE dt.token=? AND dt.expires_at>NOW()');
    $st->execute([$token]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if (!$u || !in_array($u['role'], ['admin','super_admin'], true)) {
        http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Admin only']); exit;
    }

    $db = db();
    $log = [];

    // Ensure tables
    $tables = [
        "CREATE TABLE IF NOT EXISTS project_stages (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending', progress TINYINT UNSIGNED NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0, completed_date DATE, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS weekly_updates (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, week_number INT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, work_completed TEXT, remarks TEXT, percentage_increased DECIMAL(5,2) DEFAULT 0, progress_after DECIMAL(5,2) DEFAULT 0, status ENUM('draft','pending_approval','approved','published') NOT NULL DEFAULT 'draft', created_by INT UNSIGNED, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, published_at DATETIME, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS photo_albums (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, description TEXT, sort_order INT DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS before_after (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, stage_name VARCHAR(120) NOT NULL, before_url VARCHAR(500) NOT NULL, after_url VARCHAR(500) NOT NULL, description TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS upcoming_works (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, description TEXT, week_commencing DATE, status ENUM('planned','in_progress','completed') NOT NULL DEFAULT 'planned', sort_order INT DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS payment_history (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, invoice_id INT UNSIGNED NOT NULL, amount DECIMAL(12,2) NOT NULL, payment_date DATE NOT NULL, payment_method VARCHAR(60), reference_no VARCHAR(100), receipt_url VARCHAR(500), notes TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS material_tracking (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, material_name VARCHAR(120) NOT NULL, quantity_ordered DECIMAL(10,2) DEFAULT 0, quantity_delivered DECIMAL(10,2) DEFAULT 0, unit VARCHAR(30) NOT NULL DEFAULT 'kg', status ENUM('pending','partial','received') NOT NULL DEFAULT 'pending', expected_date DATE, received_date DATE, notes TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS engineer_notes (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, note_date DATE NOT NULL, content TEXT NOT NULL, created_by INT UNSIGNED, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS project_issues (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, description TEXT, status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open', severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium', reported_by INT UNSIGNED, resolved_by INT UNSIGNED, resolution TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS client_approvals (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, description TEXT, options_json TEXT, deadline DATE NOT NULL, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending', client_comment TEXT, decided_at DATETIME, created_by INT UNSIGNED, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS notifications (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, project_id INT UNSIGNED, type VARCHAR(60) NOT NULL, title VARCHAR(255) NOT NULL, message TEXT, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_user_read (user_id, is_read)) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS chat_messages (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, sender_id INT UNSIGNED NOT NULL, message TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS appointments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, project_id INT UNSIGNED NOT NULL, client_id INT UNSIGNED NOT NULL, title VARCHAR(255), purpose TEXT, appointment_date DATE NOT NULL, appointment_time TIME, status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE) ENGINE=InnoDB",
    ];
    foreach ($tables as $sql) { $db->exec($sql); }
    $log[]='Tables created';

    // Ensure user roles
    $db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('user','admin','super_admin','head_engineer','site_engineer','client') NOT NULL DEFAULT 'client'");
    $log[]='Users extended';

    // Ensure project columns
    try { $db->exec("ALTER TABLE projects ADD COLUMN manager_id INT UNSIGNED NULL AFTER client_id"); } catch(Exception $e){}
    try { $db->exec("ALTER TABLE projects ADD COLUMN engineer_id INT UNSIGNED NULL AFTER manager_id"); } catch(Exception $e){}
    try { $db->exec("ALTER TABLE projects ADD COLUMN progress_percentage DECIMAL(5,2) NOT NULL DEFAULT 0"); } catch(Exception $e){}
    try { $db->exec("ALTER TABLE projects ADD COLUMN description TEXT"); } catch(Exception $e){}
    try { $db->exec("ALTER TABLE photos ADD COLUMN album_id INT UNSIGNED NULL AFTER project_id"); } catch(Exception $e){}
    try { $db->exec("ALTER TABLE photos ADD COLUMN is_video TINYINT(1) NOT NULL DEFAULT 0"); } catch(Exception $e){}
    try { $db->exec("ALTER TABLE invoices ADD COLUMN invoice_no VARCHAR(50)"); } catch(Exception $e){}
    try { $db->exec("ALTER TABLE invoices ADD COLUMN description TEXT"); } catch(Exception $e){}
    $log[]='Columns added';

    $hash = password_hash('1234', PASSWORD_DEFAULT);
    $users = [
        ['superadmin@thegreen.homes','Amit Poddar','+91 9832012345','super_admin'],
        ['admin@thegreen.homes','Seheli','+91 9832012346','admin'],
        ['headengineer@thegreen.homes','Balaram Sharma','+91 9832012347','head_engineer'],
        ['siteengineer@thegreen.homes','Gourav','+91 9832012348','site_engineer'],
        ['client@thegreen.homes','Arindam Banerjee','+91 9876543210','client'],
    ];
    foreach ($users as $u) {
        $s=$db->prepare("SELECT id FROM users WHERE email=?"); $s->execute([$u[0]]);
        if ($r=$s->fetch(PDO::FETCH_ASSOC)) {
            $db->prepare("UPDATE users SET name=?,role=? WHERE id=?")->execute([$u[1],$u[3],$r['id']]);
        } else {
            $db->prepare("INSERT INTO users (name,email,phone,password_hash,role) VALUES (?,?,?,?,?)")->execute([$u[1],$u[0],$u[2],$hash,$u[3]]);
        }
    }
    $log[]='Users ready';

    $cid=(int)$db->query("SELECT id FROM users WHERE email='client@thegreen.homes'")->fetchColumn();
    $hm=(int)$db->query("SELECT id FROM users WHERE email='headengineer@thegreen.homes'")->fetchColumn();
    $se=(int)$db->query("SELECT id FROM users WHERE email='siteengineer@thegreen.homes'")->fetchColumn();
    if (!$cid||!$hm||!$se) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'User IDs not found']); exit; }

    // Project
    $s=$db->prepare("SELECT id FROM projects WHERE name='Banerjee Residence'"); $s->execute();
    if ($pid=(int)$s->fetchColumn()) {
        $log[]="Project exists (ID:$pid) - clearing old data";
        foreach(['weekly_updates','payment_history','invoices','photos','photo_albums','before_after','documents','material_tracking','engineer_notes','upcoming_works','project_issues','client_approvals','notifications','chat_messages','appointments','suggestions','milestones','project_stages'] as $t) {
            $db->exec("DELETE FROM $t WHERE project_id=$pid");
        }
    } else {
        $db->prepare("INSERT INTO projects (client_id,manager_id,engineer_id,name,location,description,status,progress_percentage,start_date,expected_completion_date,budget) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$cid,$hm,$se,'Banerjee Residence','Champasari, Siliguri, West Bengal','Luxury G+2 villa with pool, home theatre, terrace garden.','framing',68,'2026-01-01','2026-12-01',20000000]);
        $pid=(int)$db->lastInsertId();
        $log[]="Project created (ID:$pid)";
    }

    // Stages
    $stg=[['Planning','completed','2026-01-15'],['Approval','completed','2026-02-10'],['Foundation','completed','2026-03-20'],['Plinth','completed','2026-04-15'],['Ground Floor','completed','2026-05-25'],['First Floor','completed','2026-07-10'],['Roofing','in_progress',null],['Plaster','pending',null],['Flooring','pending',null],['Painting','pending',null],['Interior','pending',null],['Final Handover','pending',null]];
    foreach ($stg as $i=>$s) $db->prepare("INSERT INTO project_stages (project_id,name,status,progress,sort_order,completed_date) VALUES (?,?,?,?,?,?)")->execute([$pid,$s[0],$s[1],$s[1]==='completed'?100:($s[1]==='in_progress'?60:0),$i+1,$s[2]]);
    $log[]='Stages created';

    // Milestones
    $mls=[
        [1,'Site Survey','Topographic survey of 10 Katha plot','completed','2026-01-05','2026-01-05'],[2,'Soil Testing','Bearing capacity analysis','completed','2026-01-08','2026-01-08'],[3,'Architectural Planning','Floor plans and 3D views finalized','completed','2026-01-15','2026-01-15'],[4,'Structural Design','Engineering drawings approved','completed','2026-01-25','2026-01-25'],[5,'Municipal Approval','Approved by Siliguri Municipal Corporation','completed','2026-02-10','2026-02-10'],[6,'Site Cleaning','Site cleared and barricaded','completed','2026-02-15','2026-02-15'],[7,'Excavation','Excavation for foundation','completed','2026-02-25','2026-02-25'],[8,'Foundation','PCC and footings completed','completed','2026-03-15','2026-03-15'],[9,'PCC Work','Plain Cement Concrete laid','completed','2026-03-20','2026-03-20'],[10,'Footing & Column','Column footings completed','completed','2026-04-05','2026-04-05'],[11,'Column Casting','Ground floor columns cast','completed','2026-04-15','2026-04-15'],[12,'Plinth Beam','Plinth beam casting','completed','2026-04-25','2026-04-25'],[13,'Ground Floor Slab','GF slab casting completed','completed','2026-05-15','2026-05-15'],[14,'GF Brickwork','Brickwork for ground floor','completed','2026-05-30','2026-05-30'],[15,'First Floor Columns','FF columns and beams','completed','2026-06-15','2026-06-15'],[16,'First Floor Slab','FF slab casting','completed','2026-07-10','2026-07-10'],[17,'Second Floor Brickwork','SF brickwork in progress','in_progress','2026-07-30',null],[18,'Second Floor Columns','SF column casting','upcoming','2026-08-15',null],[19,'Roof Slab','Roof slab with waterproofing','upcoming','2026-09-01',null],[20,'Waterproofing','Terrace and bathroom waterproofing','upcoming','2026-09-15',null],[21,'Electrical Conduits','Concealed conduit wiring','upcoming','2026-09-25',null],[22,'Plumbing','Plumbing and sanitary lines','upcoming','2026-10-05',null],[23,'Internal Plaster','Wall plastering (internal)','upcoming','2026-10-15',null],[24,'External Plaster','Wall plastering (external)','upcoming','2026-10-25',null],[25,'Flooring','Tiles, wooden, anti-skid flooring','upcoming','2026-11-05',null],[26,'False Ceiling','POP false ceiling','upcoming','2026-11-10',null],[27,'Painting','Internal and external painting','upcoming','2026-11-15',null],[28,'Interior Works','Modular kitchen, wardrobes, finishes','upcoming','2026-11-20',null],[29,'Modular Kitchen','Kitchen installation','upcoming','2026-11-22',null],[30,'Wardrobes','Built-in wardrobes all bedrooms','upcoming','2026-11-25',null],[31,'Electrical Fixtures','Switches, fans, lights, appliances','upcoming','2026-11-28',null],[32,'Bathroom Fixtures','CP fittings, sanitaryware 6 bathrooms','upcoming','2026-11-30',null],[33,'Landscaping','Terrace garden and compound','upcoming','2026-12-03',null],[34,'Final Cleaning','Deep cleaning','upcoming','2026-12-05',null],[35,'Quality Inspection','Final quality audit','upcoming','2026-12-08',null],[36,'Final Handover','Keys and documents handover','upcoming','2026-12-01',null],
    ];
    foreach ($mls as $m) $db->prepare("INSERT INTO milestones (project_id,title,description,status,order_index,expected_date,completed_date) VALUES (?,?,?,?,?,?,?)")->execute([$pid,$m[1],$m[2],$m[3],$m[0],$m[4],$m[5]]);
    $log[]='36 milestones created';

    // Auto-calc progress
    $tm=(int)$db->prepare("SELECT COUNT(*) FROM milestones WHERE project_id=?")->execute([$pid]);
    $tmS=$db->prepare("SELECT COUNT(*) FROM milestones WHERE project_id=?"); $tmS->execute([$pid]); $tm=(int)$tmS->fetchColumn();
    $dmS=$db->prepare("SELECT COUNT(*) FROM milestones WHERE project_id=? AND status='completed'"); $dmS->execute([$pid]); $dm=(int)$dmS->fetchColumn();
    $pct=$tm>0?round(($dm/$tm)*100,2):0;
    $db->prepare("UPDATE projects SET progress_percentage=? WHERE id=?")->execute([$pct,$pid]);
    $log[]="Progress: $pct%";

    // Weekly updates
    $wk=[
        [1,'Site Preparation & Survey','Survey done, boundary marked. Site office set.','0,0,2026-01-04'],[2,'Soil Testing & Analysis','Samples tested. Suitable for G+2.','0,0,2026-01-11'],[3,'Architectural Planning','Floor plans finalized. 3D rendered.','2,2,2026-01-18'],[4,'Structural Design','Drawings submitted for approval.','3,5,2026-01-25'],[5,'Municipal Approval','Building plan approved.','5,10,2026-02-08'],[6,'Site Cleaning & Barricading','Site cleaned. Barricading up.','2,12,2026-02-15'],[7,'Excavation Work Begins','JCB excavation to 1.5m depth.','3,15,2026-02-22'],[8,'Foundation PCC Laid','PCC 150mm laid. Curing started.','5,20,2026-03-01'],[9,'Column Footing','18 footings with M20 concrete.','5,25,2026-03-08'],[10,'Foundation Done','Full foundation. Waterproofing applied.','5,30,2026-03-15'],[11,'Plinth Beam','M25 concreting. Curing started.','5,35,2026-04-05'],[12,'GF Columns','12 columns M25 cast.','5,40,2026-04-19'],[13,'GF Slab','6 inch M25 slab cast. 18 workers.','5,45,2026-05-10'],[14,'GF Brickwork','9 inch external, 4.5 internal done.','5,50,2026-05-24'],[15,'FF Columns & Beams','12 columns, 6 beams cast.','5,55,2026-06-14'],[16,'FF Slab','6 inch slab M25. Double mesh.','5,60,2026-07-05'],[17,'Curing & Staircase','Curing. Staircase completed.','3,63,2026-07-12'],[18,'SF Brickwork Started','External walls started. Conduits on GF.','3,66,2026-07-26'],[19,'SF Brickwork Progress','60% done. FF electrical done.','2,68,2026-08-02'],
    ];
    foreach ($wk as $w) {
        $parts=explode(',',$w[2]);
        $db->prepare("INSERT INTO weekly_updates (project_id,week_number,title,work_completed,percentage_increased,progress_after,status,created_by,created_at,published_at) VALUES (?,?,?,?,?,?,'published',?,?,?)")
            ->execute([$pid,$w[0],$w[1],$w[1],(float)$parts[0],(float)$parts[1],$se,$parts[2].' 17:00:00',$parts[2].' 18:00:00']);
    }
    $log[]='19 weekly updates';

    // Photo albums
    $albums=['Before Construction','Excavation','Foundation','Columns','Ground Floor','First Floor','Concrete Casting','Brickwork','Steel Reinforcement','Electrical','Plumbing','Material Delivery','Site Team','Drone Images','Quality Inspection'];
    $albumIds=[];
    foreach ($albums as $i=>$a) { $db->prepare("INSERT INTO photo_albums (project_id,name,sort_order) VALUES (?,?,?)")->execute([$pid,$a,$i+1]); $albumIds[$a]=(int)$db->lastInsertId(); }

    // Photos (sample batch)
    $photos=[
        ['Before Construction','Vacant Plot','https://placehold.co/800x600/166534/ffffff?text=Vacant+Plot'],
        ['Before Construction','Boundary Marking','https://placehold.co/800x600/166534/ffffff?text=Boundary'],
        ['Excavation','JCB Excavation','https://placehold.co/800x600/166534/ffffff?text=Excavation'],
        ['Excavation','Excavation Pit','https://placehold.co/800x600/166534/ffffff?text=Pit'],
        ['Foundation','PCC Layer','https://placehold.co/800x600/166534/ffffff?text=PCC'],
        ['Foundation','Reinforcement','https://placehold.co/800x600/166534/ffffff?text=Steel'],
        ['Columns','Column Shuttering','https://placehold.co/800x600/166534/ffffff?text=Column'],
        ['Columns','Casting','https://placehold.co/800x600/166534/ffffff?text=Casting'],
        ['Ground Floor','Slab Shuttering','https://placehold.co/800x600/166534/ffffff?text=Slab'],
        ['Ground Floor','Slab Concreting','https://placehold.co/800x600/166534/ffffff?text=Concrete'],
        ['First Floor','FF Slab','https://placehold.co/800x600/166534/ffffff?text=FF+Slab'],
        ['First Floor','FF Curing','https://placehold.co/800x600/166534/ffffff?text=FF+Curing'],
        ['Brickwork','External Wall','https://placehold.co/800x600/166534/ffffff?text=Brickwork'],
        ['Brickwork','Internal Wall','https://placehold.co/800x600/166534/ffffff?text=Internal'],
        ['Electrical','Conduit Laying','https://placehold.co/800x600/166534/ffffff?text=Conduit'],
        ['Electrical','Switch Box','https://placehold.co/800x600/166534/ffffff?text=Switches'],
        ['Plumbing','Drainage Pipes','https://placehold.co/800x600/166534/ffffff?text=Pipes'],
        ['Plumbing','Water Supply','https://placehold.co/800x600/166534/ffffff?text=Water'],
        ['Material Delivery','Steel Delivery','https://placehold.co/800x600/166534/ffffff?text=Steel+Delivery'],
        ['Material Delivery','Cement Stock','https://placehold.co/800x600/166534/ffffff?text=Cement'],
        ['Site Team','Morning Meeting','https://placehold.co/800x600/166534/ffffff?text=Meeting'],
        ['Site Team','Engineer','https://placehold.co/800x600/166534/ffffff?text=Engineer'],
        ['Drone Images','Aerial View','https://placehold.co/800x600/166534/ffffff?text=Drone'],
        ['Drone Images','Full Site','https://placehold.co/800x600/166534/ffffff?text=Full+Site'],
        ['Quality Inspection','Thickness Check','https://placehold.co/800x600/166534/ffffff?text=QC'],
        ['Quality Inspection','Safety Check','https://placehold.co/800x600/166534/ffffff?text=Safety'],
    ];
    foreach ($photos as $ph) {
        $aid=$albumIds[$ph[0]]??null;
        $db->prepare("INSERT INTO photos (project_id,album_id,title,url) VALUES (?,?,?,?)")->execute([$pid,$aid,$ph[1],$ph[2]]);
    }
    $log[]='26 photos in 15 albums';

    // Before/After
    foreach ([['Site','https://placehold.co/600x400/166534/ffffff?text=Before+Site','https://placehold.co/600x400/166534/ffffff?text=After+Foundation'],['Foundation','https://placehold.co/600x400/166534/ffffff?text=Excavation','https://placehold.co/600x400/166534/ffffff?text=Footing'],['Ground Floor','https://placehold.co/600x400/166534/ffffff?text=Before+GF','https://placehold.co/600x400/166534/ffffff?text=GF+Slab'],['First Floor','https://placehold.co/600x400/166534/ffffff?text=GF+Brickwork','https://placehold.co/600x400/166534/ffffff?text=FF+Slab']] as $ba) {
        $db->prepare("INSERT INTO before_after (project_id,stage_name,before_url,after_url) VALUES (?,?,?,?)")->execute([$pid,$ba[0],$ba[1],$ba[2]]);
    }
    $log[]='4 before/after';

    // Documents
    $docs=[
        ['Construction Agreement','agreement'],['Architectural Drawing GF','drawing'],['Architectural Drawing FF','drawing'],['Structural Drawing Foundation','drawing'],['Structural Drawing Columns','drawing'],['Electrical Layout','drawing'],['Plumbing Layout','drawing'],['3D Elevation Front','design'],['3D Elevation Rear','design'],['Municipal Approval','approval'],['Soil Test Report','report'],['Concrete Test Report','report'],['Steel Test Report','report'],['Warranty Certificate','warranty'],['Insurance Document','insurance'],
    ];
    foreach ($docs as $d) $db->prepare("INSERT INTO documents (project_id,title,type,url) VALUES (?,?,?,?)")->execute([$pid,$d[0],$d[1],'https://placehold.co/595x842/166534/ffffff?text='.str_replace(' ','+',$d[0])]);
    $log[]='15 documents';

    // Invoices
    $invs=[['INV-GH-2026-001','Booking Advance',2000000,'paid','2026-01-01'],['INV-GH-2026-002','Foundation Milestone',3000000,'paid','2026-03-15'],['INV-GH-2026-003','Ground Floor Milestone',3500000,'paid','2026-05-25'],['INV-GH-2026-004','First Floor Milestone',3500000,'pending','2026-07-25'],['INV-GH-2026-005','Roof Casting Milestone',2500000,'pending','2026-09-15'],['INV-GH-2026-006','Interior Work Milestone',3000000,'pending','2026-11-20'],['INV-GH-2026-007','Final Handover Milestone',2500000,'pending','2026-12-01']];
    foreach ($invs as $inv) $db->prepare("INSERT INTO invoices (project_id,invoice_no,title,amount,status,due_date) VALUES (?,?,?,?,?,?)")->execute([$pid,$inv[0],$inv[1],$inv[2],$inv[3],$inv[4]]);
    $log[]='7 invoices';

    // Payments
    for ($i=1;$i<=3;$i++) {
        $iid=$db->prepare("SELECT id FROM invoices WHERE invoice_no=?"); $iid->execute(['INV-GH-2026-00'.$i]); $iid=$iid->fetchColumn();
        if ($iid) $db->prepare("INSERT INTO payment_history (invoice_id,amount,payment_date,payment_method,reference_no) VALUES (?,?,?,?,?)")->execute([$iid,[2000000,3000000,3500000][$i-1],['2026-01-01','2026-03-20','2026-06-01'][$i-1],'bank_transfer','GH-BT-202600'.$i]);
    }
    $log[]='3 payments';

    // Materials
    $mats=[['Cement (OPC 53 Grade)',4500,3800,'bags','received','2026-02-05'],['Steel TMT Bars (Fe-550)',85000,72000,'kg','received','2026-02-08'],['Bricks (Class A)',120000,95000,'nos','received','2026-02-20'],['River Sand',720,600,'tonnes','received','2026-02-18'],['Coarse Aggregate (20mm)',580,480,'tonnes','received','2026-02-22'],['Electrical Pipes PVC',650,500,'mtr','received','2026-05-10'],['Plumbing Pipes CPVC',480,350,'mtr','received','2026-05-12'],['Tiles (Vitrified)',850,0,'boxes','pending',null],['Tiles (Anti-skid)',350,0,'boxes','pending',null],['Doors (Flush)',18,12,'nos','partial','2026-06-15'],['Windows (UPVC)',12,8,'nos','partial','2026-06-20'],['Paint (Interior)',200,0,'ltr','pending',null]];
    foreach ($mats as $m) $db->prepare("INSERT INTO material_tracking (project_id,material_name,quantity_ordered,quantity_delivered,unit,status,received_date) VALUES (?,?,?,?,?,?,?)")->execute([$pid,$m[0],$m[1],$m[2],$m[3],$m[4],$m[5]]);
    $log[]='12 materials';

    // Engineer notes
    $notes=['Second floor brickwork 60%. Electrical conduits GF started.','SF brickwork 65%. GF electrical 50%. Window frames started.','SF brickwork 70%. GF electrical done. Plumbing rough-in started.','SF brickwork 75%. GF plumbing Bath 1 complete. Sand received.','SF brickwork 78%. GF plumbing all done. FF window frames installed.','SF brickwork 80%. Staircase prep started.','SF brickwork 82%. Column steel cutting started.','Tile sample approval pending from client.'];
    foreach ($notes as $i=>$n) $db->prepare("INSERT INTO engineer_notes (project_id,note_date,content,created_by) VALUES (?,?,?,?)")->execute([$pid,date('Y-m-d',strtotime("2026-07-28 +$i days")),$n,$se]);
    $log[]='8 engineer notes';

    // Upcoming
    $ups=[['Complete SF Brickwork','2026-08-10','in_progress'],['SF Column Casting','2026-08-17','planned'],['Swimming Pool Excavation','2026-08-24','planned'],['Roof Slab Shuttering','2026-09-01','planned'],['Roof Slab Casting','2026-09-08','planned'],['Terrace Waterproofing','2026-09-15','planned'],['Electrical Conduit FF & SF','2026-09-22','planned'],['Plumbing Rough-in FF & SF','2026-10-01','planned'],['Internal Plastering','2026-10-08','planned'],['External Plastering','2026-10-22','planned']];
    foreach ($ups as $i=>$u) $db->prepare("INSERT INTO upcoming_works (project_id,title,week_commencing,status,sort_order) VALUES (?,?,?,?,?)")->execute([$pid,$u[0],$u[1],$u[2],$i+1]);
    $log[]='10 upcoming works';

    // Issues
    $iss=[['Waterlogging at Foundation','Resolved with pumping.','resolved','high'],['Steel Delivery Delay','3 day delay due to strike.','resolved','medium'],['Labour Shortage Festival','10 workers on leave.','resolved','medium'],['Concrete Mix Adjustment','M25 ratio adjusted.','resolved','critical'],['Noise Complaint','Adjusted work hours.','resolved','low'],['Tile Sample Not Approved','Awaiting client selection.','open','high']];
    foreach ($iss as $i) $db->prepare("INSERT INTO project_issues (project_id,title,description,status,severity,reported_by) VALUES (?,?,?,?,?,?)")->execute([$pid,$i[0],$i[1],$i[2],$i[3],$se]);
    $log[]='6 issues';

    // Approvals
    $apv=[['Floor Plan Approval','Review final floor plans','approved','2026-01-10'],['Exterior Elevation','Select exterior finish','approved','2026-01-20'],['Swimming Pool Design','Approve pool location','pending','2026-08-15'],['Tile Selection','Select living room tiles','pending','2026-08-20']];
    foreach ($apv as $a) $db->prepare("INSERT INTO client_approvals (project_id,title,description,status,deadline,created_by) VALUES (?,?,?,?,?,?)")->execute([$pid,$a[0],$a[1],$a[2],$a[3],$hm]);
    $log[]='4 approvals';

    // Notifications
    $nots=[[$cid,'First Floor Slab Completed','Your home is 68% complete!','milestone'],[$cid,'Weekly Update Published','Week 19 available.','weekly'],[$cid,'Payment Reminder','Rs 35L due 25 July.','payment'],[$cid,'New Photos Uploaded','Gallery updated.','photo'],[$cid,'Approval Needed','Review swimming pool design.','approval'],[$cid,'Site Visit Scheduled','15 Aug with Balaram Sharma.','general']];
    foreach ($nots as $n) $db->prepare("INSERT INTO notifications (user_id,project_id,type,title,message) VALUES (?,?,?,?,?)")->execute([$n[0],$pid,$n[3],$n[1],$n[2]]);
    $log[]='6 notifications';

    // Chat
    $chats=[[$hm,'Good morning! First floor slab cast successfully yesterday.'],[$cid,'Great news! Can we discuss tile selection this week?'],[$hm,'Shared 3 tile options in Approvals. Also need pool design input.'],[$cid,'Can we enlarge master bedroom window?'],[$hm,'Checking with structural engineer. Will update you.'],[$hm,'Confirmed! We can increase window size without issues.']];
    foreach ($chats as $c) $db->prepare("INSERT INTO chat_messages (project_id,sender_id,message) VALUES (?,?,?)")->execute([$pid,$c[0],$c[1]]);
    $log[]='6 chat messages';

    // Appointments
    $apts=[['Initial Meeting','2026-01-03','10:00:00','completed'],['Design Review','2026-01-12','11:00:00','completed'],['Foundation Inspection','2026-03-22','10:00:00','completed'],['GF Slab Inspection','2026-05-18','10:00:00','completed'],['Progress Review','2026-08-15','10:00:00','confirmed'],['Tile Selection','2026-08-20','11:00:00','pending']];
    foreach ($apts as $a) $db->prepare("INSERT INTO appointments (project_id,client_id,title,appointment_date,appointment_time,status) VALUES (?,?,?,?,?,?)")->execute([$pid,$cid,$a[0],$a[1],$a[2],$a[3]]);
    $log[]='6 appointments';

    echo json_encode(['ok'=>true, 'message'=>'Seed completed successfully', 'project_id'=>$pid, 'output'=>$log]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage(), 'line'=>$e->getLine(), 'file'=>$e->getFile()]);
}
