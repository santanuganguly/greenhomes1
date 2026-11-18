<?php
declare(strict_types=1);

header('Access-Control-Allow-Origin: https://app.thegreen.homes');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once dirname(__DIR__, 3) . '/src/config.php';
gh_load_env(dirname(__DIR__, 3));

function get_token(): ?string {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
    if (function_exists('apache_request_headers')) { $h = apache_request_headers(); $auth = $h['Authorization'] ?? $h['authorization'] ?? $auth; }
    if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) return $m[1];
    return $_GET['token'] ?? $_POST['token'] ?? null;
}

function token_user(): array {
    $token = get_token();
    if (!$token) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Missing token.']); exit; }
    $stmt = db()->prepare('SELECT dt.user_id, u.name, u.email, u.phone, u.role FROM dashboard_tokens dt JOIN users u ON u.id=dt.user_id WHERE dt.token=? AND dt.expires_at>NOW()');
    $stmt->execute([$token]); $row = $stmt->fetch();
    if (!$row) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Invalid or expired token.']); exit; }
    return ['id'=>(int)$row['user_id'], 'name'=>$row['name'], 'email'=>$row['email'], 'phone'=>$row['phone']??'', 'role'=>$row['role']];
}

function json_ok(array $data): void { echo json_encode(['ok'=>true] + $data); exit; }
function json_err(string $msg, int $code=400): void { http_response_code($code); echo json_encode(['ok'=>false,'error'=>$msg]); exit; }
function fetch_all(PDO $pdo, string $sql, array $params=[]): array { $s=$pdo->prepare($sql); $s->execute($params); return $s->fetchAll(); }
function fetch_one(PDO $pdo, string $sql, array $params=[]): ?array { $s=$pdo->prepare($sql); $s->execute($params); $r=$s->fetch(); return $r?:null; }
function exec_sql(PDO $pdo, string $sql, array $params=[]): void { $pdo->prepare($sql)->execute($params); }
function is_adm(string $r): bool { return in_array($r,['admin','super_admin'],true); }
function is_staff(string $r): bool { return in_array($r,['admin','super_admin','head_engineer','site_engineer'],true); }
function recalc_progress(PDO $pdo, int $project_id): void {
    $row = fetch_one($pdo, 'SELECT COUNT(*) as total, SUM(CASE WHEN status="completed" THEN 1 ELSE 0 END) as done FROM milestones WHERE project_id=?', [$project_id]);
    if ($row && (int)$row['total'] > 0) {
        $pct = round(((int)$row['done'] / (int)$row['total']) * 100, 2);
        exec_sql($pdo, 'UPDATE projects SET progress_percentage=? WHERE id=?', [$pct, $project_id]);
    }
    $allDone = fetch_all($pdo, 'SELECT id, status FROM project_stages WHERE project_id=? ORDER BY sort_order', [$project_id]);
    if (count($allDone) > 0) {
        $stagePct = 0;
        foreach ($allDone as $s) { if ($s['status'] === 'completed') $stagePct += (100 / count($allDone)); }
        $stagePct = round($stagePct, 2);
        exec_sql($pdo, 'UPDATE projects SET progress_percentage=GREATEST(progress_percentage,?) WHERE id=?', [$stagePct, $project_id]);
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$db = db();
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// --- POST: LOGIN (no token required) ---
if ($action === '' && $method === 'POST') {
    $email = trim($input['email']??''); $password = $input['password']??'';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') json_err('Email and password required.');
    require_once dirname(__DIR__,3).'/src/repo/users.php';
    $u = users_find_by_email($email);
    if (!$u || !password_verify($password, $u['password_hash'])) json_err('Invalid credentials.', 401);
    $token = bin2hex(random_bytes(32)); $exp = date('Y-m-d H:i:s', strtotime('+30 days'));
    exec_sql($db, 'INSERT INTO dashboard_tokens (user_id,token,expires_at,created_at) VALUES (?,?,?,NOW()) ON DUPLICATE KEY UPDATE token=VALUES(token),expires_at=VALUES(expires_at),created_at=NOW()', [$u['id'],$token,$exp]);
    json_ok(['token'=>$token,'user'=>['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'phone'=>$u['phone']??'','role'=>$u['role']??'client']]);
}

// --- ALL OTHER ENDPOINTS REQUIRE TOKEN ---
$user = token_user();
$uid = $user['id'];
$role = $user['role'];
$pid = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);

// --- GET: ME ---
if ($action === 'me') {
    if ($role === 'client') {
        $stmt = $db->prepare('SELECT id,name,location,status,progress_percentage,start_date,expected_completion_date,budget,manager_id,engineer_id,description FROM projects WHERE client_id=? ORDER BY created_at DESC');
        $stmt->execute([$uid]);
    } else {
        $stmt = $db->prepare('SELECT p.*, u.name as client_name FROM projects p LEFT JOIN users u ON p.client_id=u.id WHERE p.manager_id=? OR p.engineer_id=? ORDER BY p.created_at DESC');
        $stmt->execute([$uid, $uid]);
        if ($stmt->rowCount() === 0 && is_adm($role)) {
            $stmt = $db->prepare('SELECT p.*, u.name as client_name FROM projects p LEFT JOIN users u ON p.client_id=u.id ORDER BY p.created_at DESC');
            $stmt->execute();
        }
    }
    $projects = $stmt->fetchAll();
    $cntStmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0'); $cntStmt->execute([$uid]); $cnt = (int)$cntStmt->fetchColumn();
    json_ok(['user'=>$user,'projects'=>$projects,'unread_notifications'=>$cnt]);
}

// --- GET: PROJECT DETAILS (rich payload for client dashboard) ---
if ($action === 'project_details' && $pid) {
    $p = fetch_one($db, 'SELECT p.*, pm.name as manager_name, e.name as engineer_name, u.name as client_name FROM projects p LEFT JOIN users pm ON p.manager_id=pm.id LEFT JOIN users e ON p.engineer_id=e.id LEFT JOIN users u ON p.client_id=u.id WHERE p.id=?', [$pid]);
    if (!$p) json_err('Not found.', 404);
    $stages = fetch_all($db, 'SELECT * FROM project_stages WHERE project_id=? ORDER BY sort_order', [$pid]);
    $milestones = fetch_all($db, 'SELECT * FROM milestones WHERE project_id=? ORDER BY order_index', [$pid]);
    $weekly = fetch_all($db, "SELECT * FROM weekly_updates WHERE project_id=? AND status IN ('published','approved') ORDER BY week_number DESC LIMIT 3", [$pid]);
    $invoices = fetch_all($db, 'SELECT * FROM invoices WHERE project_id=? ORDER BY due_date ASC', [$pid]);
    $payments = fetch_all($db, 'SELECT ph.*, i.title as invoice_title, i.invoice_no FROM payment_history ph JOIN invoices i ON ph.invoice_id=i.id WHERE i.project_id=? ORDER BY ph.created_at DESC', [$pid]);
    $photos = fetch_all($db, 'SELECT * FROM photos WHERE project_id=? ORDER BY uploaded_at DESC LIMIT 8', [$pid]);
    $albums = fetch_all($db, 'SELECT * FROM photo_albums WHERE project_id=? ORDER BY sort_order', [$pid]);
    $documents = fetch_all($db, 'SELECT * FROM documents WHERE project_id=? ORDER BY created_at DESC LIMIT 5', [$pid]);
    $materials = fetch_all($db, 'SELECT * FROM material_tracking WHERE project_id=? ORDER BY created_at DESC', [$pid]);
    $issues = fetch_all($db, 'SELECT * FROM project_issues WHERE project_id=? AND status NOT IN ("closed","resolved") ORDER BY created_at DESC LIMIT 5', [$pid]);
    $notes = fetch_all($db, 'SELECT en.*, u.name as created_by_name FROM engineer_notes en LEFT JOIN users u ON en.created_by=u.id WHERE en.project_id=? ORDER BY en.note_date DESC LIMIT 3', [$pid]);
    $upcoming = fetch_all($db, "SELECT * FROM upcoming_works WHERE project_id=? AND status IN ('planned','in_progress') ORDER BY week_commencing ASC LIMIT 5", [$pid]);
    $tpStmt = $db->prepare("SELECT COALESCE(SUM(ph.amount),0) FROM payment_history ph JOIN invoices i ON ph.invoice_id=i.id WHERE i.project_id=?"); $tpStmt->execute([$pid]); $totalPaid = (float)$tpStmt->fetchColumn();
    $tiStmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE project_id=?"); $tiStmt->execute([$pid]); $totalInvoiced = (float)$tiStmt->fetchColumn();
    $overdueStmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE project_id=? AND status='pending' AND due_date < CURDATE()"); $overdueStmt->execute([$pid]); $overdue = (float)$overdueStmt->fetchColumn();
    $nextDueStmt = $db->prepare("SELECT title, amount, due_date FROM invoices WHERE project_id=? AND status='pending' ORDER BY due_date ASC LIMIT 1"); $nextDueStmt->execute([$pid]); $nextDue = $nextDueStmt->fetch();
    $unStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0"); $unStmt->execute([$uid]); $unreadNotifs = (int)$unStmt->fetchColumn();
    $notifs = fetch_all($db, 'SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5', [$uid]);
    json_ok([
        'project'=>$p, 'stages'=>$stages, 'milestones'=>$milestones, 'weekly'=>$weekly,
        'invoices'=>$invoices, 'payments'=>$payments, 'photos'=>$photos, 'albums'=>$albums,
        'documents'=>$documents, 'materials'=>$materials, 'issues'=>$issues, 'notes'=>$notes, 'upcoming'=>$upcoming,
        'payment_summary'=>[
            'total_budget'=>(float)$p['budget'], 'total_invoiced'=>$totalInvoiced, 'total_paid'=>$totalPaid,
            'remaining'=>$totalInvoiced-$totalPaid, 'overdue'=>$overdue,
            'payment_percentage'=>$totalInvoiced>0?round(($totalPaid/$totalInvoiced)*100,1):0,
            'next_due'=>$nextDue,
        ],
        'notifications'=>$notifs, 'unread_notifications'=>$unreadNotifs,
    ]);
}

// --- GET: PROJECT ---
if ($action === 'project' && $pid) {
    $p = fetch_one($db, 'SELECT p.*, pm.name as manager_name, e.name as engineer_name FROM projects p LEFT JOIN users pm ON p.manager_id=pm.id LEFT JOIN users e ON p.engineer_id=e.id WHERE p.id=?', [$pid]);
    if (!$p) json_err('Not found.', 404);
    $stages = fetch_all($db, 'SELECT * FROM project_stages WHERE project_id=? ORDER BY sort_order', [$pid]);
    $milestones = fetch_all($db, 'SELECT * FROM milestones WHERE project_id=? ORDER BY order_index', [$pid]);
    json_ok(['project'=>$p,'stages'=>$stages,'milestones'=>$milestones]);
}

// --- GENERIC GET: fetch by project_id ---
$getActions = [
    'weekly' => ['table'=>'weekly_updates', 'order'=>'week_number DESC', 'where'=>"status IN ('published','approved')"],
    'gallery_albums' => ['table'=>'photo_albums', 'order'=>'sort_order'],
    'gallery_photos' => ['table'=>'photos', 'order'=>'created_at DESC'],
    'before_after' => ['table'=>'before_after', 'order'=>'created_at DESC'],
    'documents' => ['table'=>'documents', 'order'=>'created_at DESC'],
    'payments_invoices' => ['table'=>'invoices', 'order'=>'due_date DESC'],
    'materials' => ['table'=>'material_tracking', 'order'=>'created_at DESC'],
    'upcoming' => ['table'=>'upcoming_works', 'order'=>'week_commencing ASC, sort_order ASC'],
    'approvals' => ['table'=>'client_approvals', 'order'=>'deadline ASC'],
    'notes' => ['table'=>'engineer_notes', 'order'=>'note_date DESC, created_at DESC'],
];

if (isset($getActions[$action]) && $pid) {
    $cfg = $getActions[$action];
    $where = $cfg['where'] ?? '1=1';
    $list = fetch_all($db, "SELECT * FROM {$cfg['table']} WHERE project_id=? AND {$where} ORDER BY {$cfg['order']}", [$pid]);
    json_ok([$action=>$list]);
}

// --- ISSUES ---
if ($action === 'issues' && $pid) {
    $list = fetch_all($db, 'SELECT i.*, r.name as reported_by_name, s.name as resolved_by_name FROM project_issues i LEFT JOIN users r ON i.reported_by=r.id LEFT JOIN users s ON i.resolved_by=s.id WHERE i.project_id=? ORDER BY i.created_at DESC', [$pid]);
    json_ok(['issues'=>$list]);
}

// --- CHAT ---
if ($action === 'chat' && $pid) {
    $msgs = fetch_all($db, 'SELECT cm.*, u.name as sender_name FROM chat_messages cm JOIN users u ON cm.sender_id=u.id WHERE cm.project_id=? ORDER BY cm.created_at ASC', [$pid]);
    json_ok(['messages'=>$msgs]);
}

// --- SUGGESTIONS ---
if ($action === 'suggestions' && $pid) {
    $list = fetch_all($db, 'SELECT * FROM suggestions WHERE project_id=? ORDER BY created_at DESC', [$pid]);
    json_ok(['suggestions'=>$list]);
}

// --- NOTIFICATIONS ---
if ($action === 'notifications') {
    $list = fetch_all($db, 'SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50', [$uid]);
    json_ok(['notifications'=>$list]);
}

if ($action === 'notification_count') {
    $s = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0'); $s->execute([$uid]);
    json_ok(['count'=>(int)$s->fetchColumn()]);
}

// --- NOTIFICATION MARK READ ---
if ($action === 'mark_read') {
    $nid = (int)($_GET['id']??$_POST['id']??0);
    exec_sql($db, 'UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?', [$nid, $uid]);
    json_ok([]);
}

if ($action === 'mark_all_read') {
    exec_sql($db, 'UPDATE notifications SET is_read=1 WHERE user_id=?', [$uid]);
    json_ok([]);
}

// --- ADMIN STATS ---
if ($action === 'admin_stats' && is_adm($role)) {
    $totalClients = (int)$db->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();
    $activeProjects = (int)$db->query("SELECT COUNT(*) FROM projects WHERE status NOT IN ('completed','cancelled')")->fetchColumn();
    $completedProjects = (int)$db->query("SELECT COUNT(*) FROM projects WHERE status='completed'")->fetchColumn();
    $pendingPayments = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM invoices WHERE status IN ('pending','overdue')")->fetchColumn();
    $recentProjects = fetch_all($db, "SELECT p.*, u.name as client_name FROM projects p LEFT JOIN users u ON p.client_id=u.id ORDER BY p.created_at DESC LIMIT 5");
    json_ok(['stats'=>['totalClients'=>$totalClients,'activeProjects'=>$activeProjects,'completedProjects'=>$completedProjects,'pendingPayments'=>$pendingPayments],'recent_projects'=>$recentProjects]);
}
if ($action === 'admin_clients' && is_adm($role)) {
    json_ok(['clients'=>fetch_all($db, "SELECT id,name,email,phone,role,created_at FROM users ORDER BY created_at DESC")]);
}
if ($action === 'admin_projects' && is_adm($role)) {
    $projects = fetch_all($db, "SELECT p.*, u.name as client_name FROM projects p LEFT JOIN users u ON p.client_id=u.id ORDER BY p.created_at DESC");
    $clients = fetch_all($db, "SELECT id,name,email FROM users WHERE role='client' ORDER BY name");
    json_ok(['projects'=>$projects,'clients'=>$clients]);
}
if ($action === 'admin_all_payments' && is_adm($role)) {
    $invoices = fetch_all($db, "SELECT i.*, p.name as project_name FROM invoices i JOIN projects p ON i.project_id=p.id ORDER BY i.created_at DESC");
    $payments = fetch_all($db, "SELECT ph.*, i.title as invoice_title, p.name as project_name FROM payment_history ph JOIN invoices i ON ph.invoice_id=i.id JOIN projects p ON i.project_id=p.id ORDER BY ph.created_at DESC");
    json_ok(['invoices'=>$invoices,'payments'=>$payments]);
}
if ($action === 'admin_all_documents' && is_adm($role)) {
    json_ok(['documents'=>fetch_all($db, "SELECT d.*, p.name as project_name FROM documents d JOIN projects p ON d.project_id=p.id ORDER BY d.created_at DESC")]);
}
if ($action === 'admin_notifications' && is_adm($role)) {
    $list = fetch_all($db, 'SELECT n.*, u.name as user_name FROM notifications n LEFT JOIN users u ON n.user_id=u.id ORDER BY n.created_at DESC LIMIT 50');
    json_ok(['notifications'=>$list]);
}
if ($action === 'admin_design_ideas' && is_staff($role)) {
    $ideas = fetch_all($db, "SELECT cm.*, u.name as sender_name, p.name as project_name FROM chat_messages cm JOIN users u ON cm.sender_id=u.id JOIN projects p ON cm.project_id=p.id WHERE cm.message LIKE '%[tag:%' ORDER BY cm.created_at DESC LIMIT 50");
    json_ok(['ideas'=>$ideas]);
}

// === PM GET ENDPOINTS ===
if ($action === 'pm_approvals' && is_staff($role)) {
    $list = fetch_all($db, 'SELECT ca.*, u.name as client_name, p.name as project_name FROM client_approvals ca JOIN projects p ON ca.project_id=p.id LEFT JOIN users u ON p.client_id=u.id WHERE ca.status="pending" ORDER BY ca.deadline ASC');
    json_ok(['approvals'=>$list]);
}
if ($action === 'pm_schedule' && is_staff($role)) {
    $list = fetch_all($db, 'SELECT a.*, u.name as client_name, p.name as project_name FROM appointments a JOIN projects p ON a.project_id=p.id LEFT JOIN users u ON a.client_id=u.id ORDER BY a.appointment_date ASC');
    json_ok(['appointments'=>$list]);
}
if ($action === 'pm_clients' && is_staff($role)) {
    $clients = fetch_all($db, "SELECT id,name,email,phone FROM users WHERE role='client' ORDER BY name");
    foreach ($clients as &$c) {
        $st = $db->prepare("SELECT COUNT(*) FROM projects WHERE client_id=?"); $st->execute([$c['id']]); $c['project_count'] = (int)$st->fetchColumn();
        $st2 = $db->prepare("SELECT COALESCE(SUM(budget),0) FROM projects WHERE client_id=?"); $st2->execute([$c['id']]); $c['total_budget'] = (float)$st2->fetchColumn();
    }
    json_ok(['clients'=>$clients]);
}

// === POST ACTIONS ===
if ($method !== 'POST') json_err('Method not allowed.', 405);
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$pid = (int)($input['project_id']??0);

// --- UPDATE PASSWORD ---
if ($action === 'update_password') {
    $pw = $input['password']??''; $cpw = $input['confirm_password']??'';
    if (strlen($pw)<6) json_err('Password must be 6+ characters.');
    if ($pw !== $cpw) json_err('Passwords do not match.');
    exec_sql($db, 'UPDATE users SET password_hash=? WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $uid]);
    json_ok([]);
}

// --- CHAT ---
if ($action === 'send_chat') {
    $msg = trim($input['message']??'');
    if (!$pid||!$msg) json_err('Required.');
    exec_sql($db, 'INSERT INTO chat_messages (project_id,sender_id,message) VALUES (?,?,?)', [$pid,$uid,$msg]);
    json_ok([]);
}

// --- DESIGN IDEA ---
if ($action === 'share_design_idea') {
    $msg = trim($input['message']??''); $photo = trim($input['photo_url']??''); $tag = $input['tag']??'general';
    if (!$pid||!$msg) json_err('Required.');
    $full = $msg; if ($photo) $full .= "\n[photo:$photo]"; $full .= "\n[tag:$tag]";
    exec_sql($db, 'INSERT INTO chat_messages (project_id,sender_id,message) VALUES (?,?,?)', [$pid,$uid,$full]);
    json_ok([]);
}

// --- APPOINTMENT ---
if ($action === 'book_appointment') {
    $date = $input['appointment_date']??''; $time = $input['appointment_time']??null; $purpose = $input['purpose']??'Site Visit';
    if (!$pid||!$date) json_err('Date required.');
    exec_sql($db, 'INSERT INTO appointments (project_id,client_id,title,purpose,appointment_date,appointment_time) VALUES (?,?,?,?,?,?)', [$pid,$uid,'Site Visit',$purpose,$date,$time?:null]);
    json_ok([]);
}

// --- APPROVE ---
if ($action === 'respond_approval') {
    $aid = (int)($input['approval_id']??0); $dec = $input['decision']??''; $comment = trim($input['comment']??'');
    if (!in_array($dec,['approved','rejected'])) json_err('Invalid decision.');
    exec_sql($db, "UPDATE client_approvals SET status=?,client_comment=?,decided_at=NOW() WHERE id=?", [$dec,$comment?:null,$aid]);
    json_ok([]);
}

// --- SUGGESTION ---
if ($action === 'add_suggestion') {
    $title = trim($input['title']??''); $desc = trim($input['description']??'');
    if (!$pid||!$title||!$desc) json_err('Required.');
    exec_sql($db, 'INSERT INTO suggestions (project_id,title,description) VALUES (?,?,?)', [$pid,$title,$desc]);
    json_ok([]);
}

// --- PAYMENT ---
if ($action === 'submit_payment') {
    $iid = (int)($input['invoice_id']??0); $amount = (float)($input['amount']??0);
    $payDate = $input['payment_date']??date('Y-m-d'); $payMethod = $input['payment_method']??'bank_transfer';
    $refNo = trim($input['reference_no']??''); $notes = trim($input['notes']??'');
    if (!$pid||!$iid||!$amount||!$refNo) json_err('All fields required.');
    exec_sql($db, 'INSERT INTO payment_history (invoice_id,amount,payment_date,payment_method,reference_no,notes) VALUES (?,?,?,?,?,?)', [$iid,$amount,$payDate,$payMethod,$refNo,$notes?:null]);
    // Auto-mark invoice as paid if fully covered
    exec_sql($db, "UPDATE invoices SET status='paid', paid_date=? WHERE id=? AND (SELECT COALESCE(SUM(amount),0) FROM payment_history WHERE invoice_id=?) >= amount", [$payDate, $iid, $iid]);
    json_ok([]);
}

// === ADMIN POST ACTIONS ===
$isAdmin = is_adm($role);
$isStaff = is_staff($role);
if ($action === 'create_project' && $isAdmin) {
    $cid=(int)($input['client_id']??0); $nm=trim($input['name']??''); $loc=trim($input['location']??'');
    $budget=(float)($input['budget']??0); $sd=$input['start_date']??null; $ed=$input['expected_completion_date']??null;
    if (!$cid||!$nm) json_err('Required.');
    exec_sql($db, 'INSERT INTO projects (client_id,name,location,budget,start_date,expected_completion_date) VALUES (?,?,?,?,?,?)', [$cid,$nm,$loc,$budget?:null,$sd?:null,$ed?:null]);
    $newPid = (int)$db->lastInsertId();
    $stages = ['Planning','Approval','Foundation','Plinth','Ground Floor','First Floor','Roofing','Plaster','Flooring','Painting','Interior','Final Handover'];
    foreach ($stages as $i=>$s) exec_sql($db, 'INSERT INTO project_stages (project_id,name,status,sort_order) VALUES (?,?,\'pending\',?)', [$newPid,$s,$i+1]);
    json_ok(['project_id'=>$newPid]);
}

if ($action === 'create_user' && $isAdmin) {
    $name=trim($input['name']??''); $email=trim($input['email']??''); $rl=$input['role']??'client';
    if (!$name||!$email) json_err('Required.');
    $exists = fetch_one($db, 'SELECT id FROM users WHERE email=?', [$email]);
    if ($exists) json_err('Email exists.');
    $pw = bin2hex(random_bytes(4)); $hash = password_hash($pw, PASSWORD_DEFAULT);
    exec_sql($db, 'INSERT INTO users (name,email,phone,password_hash,role) VALUES (?,?,?,?,?)', [$name,$email,'',$hash,$rl]);
    json_ok(['password'=>$pw]);
}

if ($action === 'add_milestone' && $isAdmin) {
    $title=trim($input['title']??''); $desc=trim($input['description']??''); $ed=$input['expected_date']??null; $oi=(int)($input['order_index']??0);
    if (!$pid||!$title) json_err('Required.');
    exec_sql($db, 'INSERT INTO milestones (project_id,title,description,order_index,expected_date) VALUES (?,?,?,?,?)', [$pid,$title,$desc,$oi,$ed?:null]);
    recalc_progress($db, $pid);
    json_ok([]);
}

if ($action === 'update_milestone_status' && $isAdmin) {
    $mid = (int)($input['milestone_id']??0); $st = $input['status']??'';
    if (!$mid || !in_array($st,['upcoming','in_progress','completed'])) json_err('Invalid.');
    $cd = $st === 'completed' ? date('Y-m-d') : null;
    exec_sql($db, 'UPDATE milestones SET status=?, completed_date=? WHERE id=?', [$st, $cd, $mid]);
    recalc_progress($db, $pid);
    json_ok([]);
}

if ($action === 'update_project_stage' && $isAdmin) {
    $sid = (int)($input['stage_id']??0); $st = $input['status']??'';
    if (!$sid || !in_array($st,['pending','in_progress','completed'])) json_err('Invalid.');
    $cd = $st === 'completed' ? date('Y-m-d') : null;
    exec_sql($db, 'UPDATE project_stages SET status=?, completed_date=? WHERE id=?', [$st, $cd, $sid]);
    recalc_progress($db, $pid);
    json_ok([]);
}

if ($action === 'add_invoice' && $isAdmin) {
    $title=trim($input['title']??''); $amt=(float)($input['amount']??0); $due=$input['due_date']??'';
    if (!$pid||!$title||!$amt) json_err('Required.');
    exec_sql($db, 'INSERT INTO invoices (project_id,title,amount,due_date) VALUES (?,?,?,?)', [$pid,$title,$amt,$due]);
    json_ok([]);
}

if ($action === 'add_photo' && $isStaff) {
    $title=trim($input['title']??''); $url=trim($input['url']??''); $albumId = (int)($input['album_id']??0);
    if (!$pid||!$title||!$url) json_err('Required.');
    exec_sql($db, 'INSERT INTO photos (project_id,album_id,title,url) VALUES (?,?,?,?)', [$pid, $albumId?:null, $title, $url]);
    json_ok([]);
}

if ($action === 'add_document' && $isAdmin) {
    $title=trim($input['title']??''); $url=trim($input['url']??''); $type=trim($input['type']??'other');
    if (!$pid||!$title||!$url) json_err('Required.');
    exec_sql($db, 'INSERT INTO documents (project_id,title,url,type) VALUES (?,?,?,?)', [$pid,$title,$url,$type]);
    json_ok([]);
}

if ($action === 'update_issue_status' && $isStaff) {
    $iid=(int)($input['issue_id']??0); $st=$input['status']??'';
    if (!in_array($st,['open','in_progress','resolved','closed'])) json_err('Invalid status.');
    $res=$st==='resolved'?date('Y-m-d H:i:s'):null;
    exec_sql($db, 'UPDATE project_issues SET status=?,resolved_at=?,resolved_by=? WHERE id=?', [$st,$res,$st==='resolved'?$uid:null,$iid]);
    json_ok([]);
}

if ($action === 'add_issue' && $isStaff) {
    $title=trim($input['title']??''); $desc=trim($input['description']??''); $sev=$input['severity']??'medium';
    if (!$pid||!$title) json_err('Required.');
    exec_sql($db, 'INSERT INTO project_issues (project_id,title,description,severity,reported_by) VALUES (?,?,?,?,?)', [$pid,$title,$desc,$sev,$uid]);
    json_ok([]);
}

if ($action === 'add_material' && $isStaff) {
    $name=trim($input['material_name']??''); $qty=(float)($input['quantity_ordered']??0); $unit=$input['unit']??'kg';
    if (!$pid||!$name||!$qty) json_err('Required.');
    exec_sql($db, 'INSERT INTO material_tracking (project_id,material_name,quantity_ordered,unit) VALUES (?,?,?,?)', [$pid,$name,$qty,$unit]);
    json_ok([]);
}

if ($action === 'update_material' && $isStaff) {
    $mid=(int)($input['material_id']??0); $del=(float)($input['quantity_delivered']??0); $st=$input['status']??'pending';
    exec_sql($db, 'UPDATE material_tracking SET quantity_delivered=?,status=?,received_date=COALESCE(?,received_date) WHERE id=?', [$del,$st,$input['received_date']??null,$mid]);
    json_ok([]);
}

if ($action === 'create_weekly' && $isStaff) {
    $wk=(int)($input['week_number']??0); $title=trim($input['title']??''); $wc=trim($input['work_completed']??'');
    $remarks=trim($input['remarks']??''); $pctInc=(float)($input['percentage_increased']??0); $pctAfter=(float)($input['progress_after']??0);
    if (!$pid||!$title) json_err('Required.');
    exec_sql($db, 'INSERT INTO weekly_updates (project_id,week_number,title,work_completed,remarks,percentage_increased,progress_after,status,created_by) VALUES (?,?,?,?,?,?,?,\'pending_approval\',?)', [$pid,$wk,$title,$wc,$remarks?:null,$pctInc,$pctAfter,$uid]);
    $wid = (int)$db->lastInsertId();
    $client = fetch_one($db, 'SELECT client_id FROM projects WHERE id=?', [$pid]);
    if ($client) exec_sql($db, 'INSERT INTO notifications (user_id,project_id,type,title,message) VALUES (?,?,?,?,?)', [(int)$client['client_id'],$pid,'weekly','Weekly Update Published',"Week $wk: $title"]);
    json_ok(['weekly_id'=>$wid]);
}

if ($action === 'approve_weekly' && $isStaff) {
    $wid = (int)($input['weekly_id']??0);
    exec_sql($db, "UPDATE weekly_updates SET status='published', published_at=NOW() WHERE id=?", [$wid]);
    json_ok([]);
}

if ($action === 'add_engineer_note' && $isStaff) {
    $date = $input['note_date']??date('Y-m-d'); $content = trim($input['content']??'');
    if (!$pid||!$content) json_err('Required.');
    exec_sql($db, 'INSERT INTO engineer_notes (project_id,note_date,content,created_by) VALUES (?,?,?,?)', [$pid,$date,$content,$uid]);
    json_ok([]);
}

if ($action === 'update_project_info' && $isAdmin) {
    $fields = ['name','location','description','status','start_date','expected_completion_date','budget'];
    $updates = []; $params = [];
    foreach ($fields as $f) { if (isset($input[$f])) { $updates[] = "$f=?"; $params[] = $input[$f]; } }
    if (empty($updates)) json_err('No fields to update.');
    $params[] = $pid;
    exec_sql($db, 'UPDATE projects SET '.implode(',',$updates).' WHERE id=?', $params);
    json_ok([]);
}

if ($action === 'send_notification' && $isAdmin) {
    $title=trim($input['title']??''); $msg=trim($input['message']??'');
    if (!$title||!$msg) json_err('Required.');
    $targetRole = $input['target_role']??'client';
    $users = $targetRole === 'all' ? fetch_all($db, 'SELECT id FROM users', []) : fetch_all($db, 'SELECT id FROM users WHERE role=?', [$targetRole]);
    foreach ($users as $c) exec_sql($db, 'INSERT INTO notifications (user_id,type,title,message) VALUES (?,?,?,?)', [(int)$c['id'],'general',$title,$msg]);
    json_ok([]);
}

json_err('Unknown action: '.$action, 400);
