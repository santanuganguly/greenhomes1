<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
if (!file_exists($root . '/src/config.php')) {
    $root = dirname(__DIR__, 3);
}
require_once $root . '/src/config.php';
gh_load_env($root);
require_once $root . '/src/auth.php';
require_once $root . '/src/csrf.php';
require_once $root . '/src/portal.php';
require_once $root . '/src/mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$action = $_POST['action'] ?? '';

if (!in_array($action, ['register_fcm', 'register_fcm_token', 'save_fcm_token'], true)) {
    csrf_verify($_POST['_csrf'] ?? null);
}

$user = current_user();
if (!$user) {
    if (in_array($action, ['register_fcm', 'register_fcm_token', 'save_fcm_token'], true)) {
        http_response_code(401);
        exit(json_encode(['error' => 'Unauthenticated']));
    }
    header('Location: /login');
    exit;
}
$db = gh_db();
$role = $user['role'] ?? '';
$isAdmin = in_array($role, ['admin', 'super_admin'], true);

function redir(string $url): void {
    header("Location: $url");
    exit;
}



function get_home(string $role): string {
    return match($role) {
        'super_admin','admin' => '/admin',
        'head_engineer' => '/pm',
        'site_engineer' => '/engineer',
        default => '/dashboard',
    };
}

// --- REGISTER FCM TOKEN ---
if ($action === 'register_fcm' || $action === 'register_fcm_token' || $action === 'save_fcm_token') {
    $token = trim($_POST['fcm_token'] ?? $_POST['token'] ?? '');
    $saved = false;
    if ($token && !empty($user['id'])) {
        $stmt = $db->prepare("UPDATE users SET fcm_token = ? WHERE id = ?");
        $saved = $stmt->execute([$token, $user['id']]);
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'saved' => $saved, 'user_id' => $user['id'] ?? null, 'token_length' => strlen($token)]);
    exit;
}

// --- UPDATE PASSWORD ---
if ($action === 'update_password') {
    $pw = $_POST['password'] ?? '';
    $cpw = $_POST['confirm_password'] ?? '';
    if (strlen($pw) < 6) redir('/dashboard/settings?err=' . urlencode('Password must be at least 6 characters.'));
    if ($pw !== $cpw) redir('/dashboard/settings?err=' . urlencode('Passwords do not match.'));
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $db->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $user['id']]);
    audit_log('password_change', 'users', $user['id']);
    redir('/dashboard/settings?msg=' . urlencode('Password updated.'));
}

// --- SEND CHAT ---
if ($action === 'send_chat') {
    $pid = (int)($_POST['project_id'] ?? 0);
    $msg = trim($_POST['message'] ?? '');
    if (!$pid || !$msg) redir('/dashboard/chat?err=Message required.');
    $db->prepare("INSERT INTO chat_messages (project_id, sender_id, message) VALUES (?,?,?)")->execute([$pid, $user['id'], $msg]);
    notify_project_users($pid, 'chat', 'New message from ' . $user['name'], $msg, '/dashboard/chat');
    gh_fcm_notify_project($db, $pid, 'New message from ' . $user['name'], $msg);
    redir('/dashboard/chat');
}

// --- SHARE DESIGN IDEA ---
if ($action === 'share_design_idea') {
    $pid = (int)($_POST['project_id'] ?? 0);
    $msg = trim($_POST['message'] ?? '');
    $photoUrl = trim($_POST['photo_url'] ?? '');
    $tag = trim($_POST['tag'] ?? 'general');
    if (!$pid || !$msg) redir('/dashboard/chat?err=Please describe your idea.');

    $allowedTags = ['general','exterior','interior','kitchen','bathroom','landscape','flooring','lighting','other'];
    if (!in_array($tag, $allowedTags, true)) $tag = 'general';

    // Embed photo URL and tag in message for storage
    $fullMessage = $msg;
    if ($photoUrl) $fullMessage .= "\n[photo:$photoUrl]";
    $fullMessage .= "\n[tag:$tag]";

    $db->prepare("INSERT INTO chat_messages (project_id, sender_id, message) VALUES (?,?,?)")
        ->execute([$pid, $user['id'], $fullMessage]);
    notify_project_users($pid, 'design_idea', 'New idea from ' . $user['name'], substr($msg, 0, 200), '/dashboard/chat');
    gh_fcm_notify_project($db, $pid, 'New idea from ' . $user['name'], substr($msg, 0, 200));
    redir('/dashboard/chat?msg=' . urlencode('Idea shared!'));
}

// --- BOOK APPOINTMENT ---
if ($action === 'book_appointment') {
    $pid = (int)($_POST['project_id'] ?? 0);
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['appointment_time'] ?? null;
    $purpose = $_POST['purpose'] ?? 'Site Visit';
    if (!$pid || !$date) redir('/dashboard/appointments?err=Date required.');
    $db->prepare("INSERT INTO appointments (project_id, client_id, title, purpose, appointment_date, appointment_time) VALUES (?,?,?,?,?,?)")
        ->execute([$pid, $user['id'], 'Site Visit', $purpose, $date, $time ?: null]);
    notify_project_users($pid, 'appointment', 'Visit request from ' . $user['name'], $purpose, '/pm/schedule');
    gh_fcm_notify_project($db, $pid, 'Visit request from ' . $user['name'], $purpose);
    redir('/dashboard/appointments?msg=' . urlencode('Request submitted.'));
}

// --- RESPOND APPROVAL ---
if ($action === 'respond_approval') {
    $aid = (int)($_POST['approval_id'] ?? 0);
    $pid = (int)($_POST['project_id'] ?? 0);
    $dec = $_POST['decision'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    if (!in_array($dec, ['approved','rejected'], true)) redir('/dashboard/approvals?err=Invalid decision.');
    $db->prepare("UPDATE client_approvals SET status=?, client_comment=?, decided_at=NOW() WHERE id=?")
        ->execute([$dec, $comment ?: null, $aid]);
    notify_project_users($pid, 'approval', 'Client ' . $dec, $user['name'] . ' responded', '/pm/approvals');
    gh_fcm_notify_project($db, $pid, 'Client ' . $dec, $user['name'] . ' responded');
    redir('/dashboard/approvals?msg=' . urlencode('Response recorded.'));
}

// --- ADD SUGGESTION ---
if ($action === 'add_suggestion') {
    $pid = (int)($_POST['project_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    if (!$pid || !$title || !$desc) redir('/dashboard/suggestions?err=Required fields.');
    $db->prepare("INSERT INTO suggestions (project_id, title, description) VALUES (?,?,?)")->execute([$pid, $title, $desc]);
    if ($rating > 0) {
        $db->prepare("INSERT INTO milestone_feedback (project_id, client_id, rating, comment) VALUES (?,?,?,?)")
            ->execute([$pid, $user['id'], $rating, $desc]);
    }
    redir('/dashboard/suggestions?msg=' . urlencode('Thank you!'));
}

// --- ENGINEER: ADD NOTE ---
if ($action === 'add_engineer_note') {
    $pid = (int)($_POST['project_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $noteDate = $_POST['note_date'] ?? date('Y-m-d');
    if (!$pid || !$content) redir('/engineer/today?err=Content required.');
    $db->prepare("INSERT INTO engineer_notes (project_id, note_date, content, created_by) VALUES (?,?,?,?)")
        ->execute([$pid, $noteDate, $content, $user['id']]);
    notify_project_users($pid, 'note', 'Daily note from ' . $user['name'], substr($content,0,200), '/dashboard/notes');
    gh_fcm_notify_project($db, $pid, 'Daily note from ' . $user['name'], substr($content,0,200));
    redir('/engineer/today?msg=' . urlencode('Note saved.'));
}

// --- ENGINEER: WEEKLY UPDATE ---
if ($action === 'create_weekly_update') {
    $pid = (int)($_POST['project_id'] ?? 0);
    $wk = (int)($_POST['week_number'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $wc = trim($_POST['work_completed'] ?? '');
    $rem = trim($_POST['remarks'] ?? '');
    $pctInc = (float)($_POST['percentage_increased'] ?? 0);
    $pctAfter = (float)($_POST['progress_after'] ?? 0);
    $st = $_POST['status'] ?? 'pending_approval';
    if (!$pid || !$title) redir('/engineer/weekly?err=Title required.');
    $db->prepare("INSERT INTO weekly_updates (project_id, week_number, title, work_completed, remarks, percentage_increased, progress_after, status, created_by) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([$pid, $wk, $title, $wc, $rem, $pctInc, $pctAfter, $st, $user['id']]);
    if ($pctAfter > 0) $db->prepare("UPDATE projects SET progress_percentage=? WHERE id=?")->execute([$pctAfter, $pid]);
    if ($st === 'published') {
        notify_project_users($pid, 'weekly_update', 'New update: ' . $title, '+'.(int)$pctInc.'%', '/dashboard/weekly');
        gh_fcm_notify_project($db, $pid, 'New update: ' . $title, '+'.(int)$pctInc.'%');
    }
    redir('/engineer/weekly?msg=' . urlencode('Update submitted.'));
}

// --- ENGINEER: REPORT ISSUE ---
if ($action === 'report_issue') {
    $pid = (int)($_POST['project_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $impact = trim($_POST['impact'] ?? '');
    $delay = trim($_POST['expected_delay'] ?? '');
    $sev = $_POST['severity'] ?? 'medium';
    if (!$pid || !$title) redir('/engineer/issues?err=Title required.');
    $db->prepare("INSERT INTO project_issues (project_id, title, description, impact, expected_delay, severity, reported_by) VALUES (?,?,?,?,?,?,?)")
        ->execute([$pid, $title, $desc, $impact, $delay, $sev, $user['id']]);
    notify_project_users($pid, 'issue', 'Issue: ' . $title, 'Severity: '.$sev, '/dashboard/issues');
    gh_fcm_notify_project($db, $pid, 'Issue: ' . $title, 'Severity: '.$sev);
    redir('/engineer/issues?msg=' . urlencode('Issue reported.'));
}

// --- ENGINEER: UPDATE ISSUE ---
if ($action === 'update_issue_status') {
    $iid = (int)($_POST['issue_id'] ?? 0);
    $pid = (int)($_POST['project_id'] ?? 0);
    $st = $_POST['status'] ?? '';
    $res = trim($_POST['resolution'] ?? '');
    if (!in_array($st, ['open','in_progress','resolved','closed'], true)) redir('/engineer/issues?err=Invalid status.');
    $resolvedAt = $st === 'resolved' ? date('Y-m-d H:i:s') : null;
    $db->prepare("UPDATE project_issues SET status=?, resolution=?, resolved_at=?, resolved_by=? WHERE id=? AND project_id=?")
        ->execute([$st, $res ?: null, $resolvedAt, $st === 'resolved' ? $user['id'] : null, $iid, $pid]);
    redir('/engineer/issues?msg=' . urlencode('Issue updated.'));
}

// --- ENGINEER: ADD MATERIAL ---
if ($action === 'add_material') {
    $pid = (int)($_POST['project_id'] ?? 0);
    $name = trim($_POST['material_name'] ?? '');
    $qty = (float)($_POST['quantity_ordered'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'kg');
    if (!$pid || !$name || !$qty) redir('/engineer/materials?err=Required.');
    $db->prepare("INSERT INTO material_tracking (project_id, material_name, quantity_ordered, unit) VALUES (?,?,?,?)")
        ->execute([$pid, $name, $qty, $unit]);
    redir('/engineer/materials?msg=' . urlencode('Material added.'));
}

// --- ENGINEER: UPDATE DELIVERY ---
if ($action === 'update_material') {
    $mid = (int)($_POST['material_id'] ?? 0);
    $pid = (int)($_POST['project_id'] ?? 0);
    $del = (float)($_POST['quantity_delivered'] ?? 0);
    $db->prepare("UPDATE material_tracking SET quantity_delivered=quantity_delivered+?, status=IF(quantity_delivered+?>=quantity_ordered,'received','partial') WHERE id=? AND project_id=?")
        ->execute([$del, $del, $mid, $pid]);
    redir('/engineer/materials?msg=' . urlencode('Updated.'));
}

// --- PM: APPROVE WEEKLY UPDATE ---
if ($action === 'approve_weekly_update') {
    $uid = (int)($_POST['weekly_update_id'] ?? 0);
    $ns = $_POST['new_status'] ?? 'published';
    $db->prepare("UPDATE weekly_updates SET status=?, published_at=NOW() WHERE id=?")->execute([$ns, $uid]);
    $wu = $db->prepare("SELECT project_id, title, percentage_increased FROM weekly_updates WHERE id=?"); $wu->execute([$uid]); $wu = $wu->fetch();
    if ($wu && in_array($ns, ['published','approved'])) {
        notify_project_users((int)$wu['project_id'], 'weekly_update', $wu['title'], '+'.(int)$wu['percentage_increased'].'%', '/dashboard/weekly');
        gh_fcm_notify_project($db, (int)$wu['project_id'], $wu['title'], '+'.(int)$wu['percentage_increased'].'%');
    }
    redir('/pm/approvals?msg=' . urlencode('Updated.'));
}

// --- ADMIN: CREATE USER ---
if ($action === 'create_client_user') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $phone = trim($_POST['phone'] ?? ''); $rl = $_POST['role'] ?? 'client';
    if (!$name || !$email) redir('/admin/clients?err=Name and email required.');
    $exists = $db->prepare("SELECT id FROM users WHERE email=?"); $exists->execute([$email]); if ($exists->fetch()) redir('/admin/clients?err=Email exists.');
    $pw = bin2hex(random_bytes(4)); $hash = password_hash($pw, PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO users (name,email,phone,password_hash,role) VALUES (?,?,?,?,?)")->execute([$name,$email,$phone,$hash,$rl]);
    $body = '<p>Hi '.htmlspecialchars($name).',</p><p>Your Green Homes portal account is ready.</p><p><strong>Email:</strong> '.$email.'<br><strong>Password:</strong> '.$pw.'</p><p><a href="/" style="background:#166534;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">Login to Portal</a></p>';
    send_mail($email, 'Welcome to Green Homes Portal', $body);
    redir('/admin/clients?msg=' . urlencode('User created. Password sent via email.'));
}

// --- ADMIN: CREATE PROJECT ---
if ($action === 'create_project') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $cid=(int)($_POST['client_id']??0); $nm=trim($_POST['name']??''); $loc=trim($_POST['location']??''); $desc=trim($_POST['description']??'');
    $budget=$_POST['budget']??null; $sd=$_POST['start_date']??null; $ed=$_POST['expected_completion_date']??null;
    $mid=!empty($_POST['manager_id'])?(int)$_POST['manager_id']:null; $eid=!empty($_POST['engineer_id'])?(int)$_POST['engineer_id']:null;
    if (!$cid || !$nm) redir('/admin/projects/new?err=Client and name required.');
    $budget=$budget===''?null:(float)$budget; $sd=empty($sd)?null:$sd; $ed=empty($ed)?null:$ed;
    $db->prepare("INSERT INTO projects (client_id,manager_id,engineer_id,name,location,description,budget,start_date,expected_completion_date) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([$cid,$mid,$eid,$nm,$loc,$desc,$budget,$sd,$ed]);
    $pid = (int)$db->lastInsertId();
    
    // Auto-create project chat room
    if (file_exists($root . '/src/chat.php')) { require_once $root . '/src/chat.php'; }
    chat_ensure_room($pid);
    
    $stages = ['Planning','Approval','Foundation','Plinth','Ground Floor','First Floor','Roofing','Plaster','Flooring','Painting','Interior','Final Handover'];
    foreach ($stages as $i=>$s) $db->prepare("INSERT INTO project_stages (project_id,name,status,sort_order) VALUES (?,'$s','pending',?)")->execute([$pid, $i+1]);
    notify_user($cid, 'project', 'Your project is active!', $nm, '/dashboard');
    redir('/admin/projects?msg=' . urlencode('Project created.'));
}

// --- ADMIN: UPDATE PROJECT ---
if ($action === 'update_project') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $pid=(int)($_POST['project_id']??0); $nm=trim($_POST['name']??''); $loc=trim($_POST['location']??''); $desc=trim($_POST['description']??'');
    $st=$_POST['status']??''; $budget=$_POST['budget']??null; $sd=$_POST['start_date']??null; $ed=$_POST['expected_completion_date']??null;
    $mid=!empty($_POST['manager_id'])?(int)$_POST['manager_id']:null; $eid=!empty($_POST['engineer_id'])?(int)$_POST['engineer_id']:null;
    $pg=!empty($_POST['progress_percentage'])?(float)$_POST['progress_percentage']:null;
    $db->prepare("UPDATE projects SET name=?,location=?,description=?,status=?,budget=?,start_date=?,expected_completion_date=?,manager_id=?,engineer_id=?,progress_percentage=COALESCE(?,progress_percentage) WHERE id=?")
        ->execute([$nm,$loc,$desc,$st,$budget?:null,$sd?:null,$ed?:null,$mid,$eid,$pg,$pid]);
    
    require_once dirname(__DIR__, 2) . '/src/chat.php';
    $stmt_room = $db->prepare("SELECT id FROM project_chat_rooms WHERE project_id = ?");
    $stmt_room->execute([$pid]);
    if ($roomId = (int)$stmt_room->fetchColumn()) {
        chat_sync_members($roomId, $pid);
    } else {
        chat_ensure_room($pid);
    }
        
    redir('/admin/projects/detail?id='.$pid.'&msg='.urlencode('Updated.'));
}

// --- ADMIN: ADD MILESTONE ---
if ($action === 'add_milestone') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $pid=(int)($_POST['project_id']??0); $title=trim($_POST['title']??''); $desc=trim($_POST['description']??'');
    $ed=$_POST['expected_date']??null; $oi=(int)($_POST['order_index']??0);
    $db->prepare("INSERT INTO milestones (project_id,title,description,order_index,expected_date) VALUES (?,?,?,?,?)")
        ->execute([$pid,$title,$desc,$oi,$ed?:null]);
    redir('/admin/projects/detail?id='.$pid.'&tab=milestones&msg='.urlencode('Added.'));
}

// --- ADMIN: UPDATE MILESTONE ---
if ($action === 'update_milestone_status') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $mid=(int)($_POST['milestone_id']??0); $pid=(int)($_POST['project_id']??0); $st=$_POST['status']??'';
    $cd=$st==='completed'?date('Y-m-d'):null;
    $db->prepare("UPDATE milestones SET status=?,completed_date=? WHERE id=?")->execute([$st,$cd,$mid]);
    if ($st==='completed') {
        $t=$db->prepare("SELECT title FROM milestones WHERE id=?"); $t->execute([$mid]);
        $mTitle = $t->fetchColumn();
        notify_project_users($pid,'milestone','Completed: '.$mTitle,'','/dashboard/progress');
        gh_fcm_notify_project($db, $pid, 'Completed: '.$mTitle, '');
    }
    redir('/admin/projects/detail?id='.$pid.'&tab=milestones&msg='.urlencode('Updated.'));
}

// --- ADMIN: DELETE MILESTONE ---
if ($action === 'delete_milestone') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $mid=(int)($_POST['milestone_id']??0); $pid=(int)($_POST['project_id']??0);
    $db->prepare("DELETE FROM milestones WHERE id=?")->execute([$mid]);
    redir('/admin/projects/detail?id='.$pid.'&tab=milestones&msg='.urlencode('Deleted.'));
}

// --- ADMIN: ADD PHOTO ---
if ($action === 'add_photo') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $pid=(int)($_POST['project_id']??0); $title=trim($_POST['title']??''); $url=trim($_POST['url']??''); $cat=trim($_POST['category']??'');
    $db->prepare("INSERT INTO photos (project_id,title,url,category) VALUES (?,?,?,?)")->execute([$pid,$title,$url,$cat?:null]);
    redir('/admin/projects/detail?id='.$pid.'&tab=photos&msg='.urlencode('Added.'));
}

// --- ADMIN: DELETE PHOTO ---
if ($action === 'delete_photo') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $phid=(int)($_POST['photo_id']??0); $pid=(int)($_POST['project_id']??0);
    $db->prepare("DELETE FROM photos WHERE id=?")->execute([$phid]);
    redir('/admin/projects/detail?id='.$pid.'&tab=photos&msg='.urlencode('Deleted.'));
}

// --- ADMIN: ADD DOCUMENT ---
if ($action === 'add_document') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $pid=(int)($_POST['project_id']??0); $title=trim($_POST['title']??''); $url=trim($_POST['url']??''); $type=trim($_POST['type']??'other');
    $db->prepare("INSERT INTO documents (project_id,title,url,type) VALUES (?,?,?,?)")->execute([$pid,$title,$url,$type]);
    redir('/admin/projects/detail?id='.$pid.'&tab=documents&msg='.urlencode('Added.'));
}

// --- ADMIN: DELETE DOCUMENT ---
if ($action === 'delete_document') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $did=(int)($_POST['document_id']??0); $pid=(int)($_POST['project_id']??0);
    $db->prepare("DELETE FROM documents WHERE id=?")->execute([$did]);
    redir('/admin/projects/detail?id='.$pid.'&tab=documents&msg='.urlencode('Deleted.'));
}

// --- ADMIN: CREATE INVOICE ---
if ($action === 'create_invoice') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $pid=(int)($_POST['project_id']??0); $title=trim($_POST['title']??''); $amt=(float)($_POST['amount']??0);
    $due=$_POST['due_date']??''; $url=trim($_POST['url']??'');
    $db->prepare("INSERT INTO invoices (project_id,title,amount,due_date,url) VALUES (?,?,?,?,?)")
        ->execute([$pid,$title,$amt,$due,$url?:null]);
    redir('/admin/projects/detail?id='.$pid.'&tab=invoices&msg='.urlencode('Created.'));
}

// --- ADMIN: UPDATE INVOICE ---
if ($action === 'update_invoice_status') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $iid=(int)($_POST['invoice_id']??0); $pid=(int)($_POST['project_id']??0); $st=$_POST['status']??'';
    $pd=$st==='paid'?date('Y-m-d'):null;
    $db->prepare("UPDATE invoices SET status=?,paid_date=? WHERE id=?")->execute([$st,$pd,$iid]);
    redir('/admin/projects/detail?id='.$pid.'&tab=invoices&msg='.urlencode('Updated.'));
}

// --- ADMIN: RESPOND SUGGESTION ---
if ($action === 'respond_to_suggestion') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $sid=(int)($_POST['suggestion_id']??0); $pid=(int)($_POST['project_id']??0);
    $dec=$_POST['decision']??''; $res=trim($_POST['admin_response']??'');
    $db->prepare("UPDATE suggestions SET status=?,admin_response=? WHERE id=?")->execute([$dec,$res,$sid]);
    redir('/admin/projects/detail?id='.$pid.'&tab=suggestions&msg='.urlencode('Done.'));
}

// --- PM: UPDATE APPOINTMENT ---
if ($action === 'update_appointment') {
    $aid=(int)($_POST['appointment_id']??0); $st=$_POST['status']??'';
    if (!in_array($st,['confirmed','cancelled','completed'],true)) redir('/pm/schedule?err=Invalid.');
    $db->prepare("UPDATE appointments SET status=? WHERE id=?")->execute([$st,$aid]);
    redir('/pm/schedule?msg='.urlencode('Updated.'));
}

// --- ADMIN: SEND NOTIFICATION ---
if ($action === 'send_notification') {
    if (!$isAdmin) redir('/admin?err=Forbidden');
    $rec=$_POST['recipient']??'all'; $type=trim($_POST['type']??'general');
    $title=trim($_POST['title']??''); $msg=trim($_POST['message']??'');
    if (!$title||!$msg) redir('/admin/notifications?err=Required.');
    if ($rec==='all') {
        $users=$db->query("SELECT id FROM users WHERE role='client'")->fetchAll(PDO::FETCH_COLUMN);
        foreach($users as $uid) notify_user((int)$uid,$type,$title,$msg);
    } elseif ($rec!=='project') {
        notify_user((int)$rec,$type,$title,$msg);
    } else {
        $pid=(int)($_POST['project_id']??0);
        notify_project_users($pid,$type,$title,$msg);
    }
    redir('/admin/notifications?msg='.urlencode('Sent.'));
}

// --- CLIENT: SUBMIT PAYMENT ---
if ($action === 'submit_payment') {
    $pid = (int)($_POST['project_id'] ?? 0);
    $iid = (int)($_POST['invoice_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $payDate = $_POST['payment_date'] ?? date('Y-m-d');
    $payMethod = $_POST['payment_method'] ?? 'bank_transfer';
    $refNo = trim($_POST['reference_no'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$pid || !$iid || !$amount || !$refNo) {
        redir('/dashboard/payments?err=' . urlencode('All fields are required.'));
    }

    $allowed = ['bank_transfer', 'upi', 'cheque', 'cash'];
    if (!in_array($payMethod, $allowed, true)) $payMethod = 'bank_transfer';

    $db->prepare("INSERT INTO payment_history (invoice_id, amount, payment_date, payment_method, reference_no, notes) VALUES (?,?,?,?,?,?)")
        ->execute([$iid, $amount, $payDate, $payMethod, $refNo, $notes ?: null]);

    // Notify admin about payment submission
    $inv = $db->prepare("SELECT i.*, p.name as project_name, u.name as client_name FROM invoices i JOIN projects p ON i.project_id = p.id LEFT JOIN users u ON p.client_id = u.id WHERE i.id = ?");
    $inv->execute([$iid]);
    $inv = $inv->fetch();
    if ($inv) {
        $admins = $db->query("SELECT id FROM users WHERE role IN ('admin','super_admin')")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($admins as $aid) {
            notify_user((int)$aid, 'payment', 'Payment submitted by ' . ($inv['client_name'] ?? 'Client'),
                $inv['project_name'] . ' — ' . format_currency($amount) . ' via ' . str_replace('_', ' ', $payMethod) . ' (Ref: ' . $refNo . ')',
                '/admin/payments');
        }
    }

    audit_log('payment_submitted', 'payment_history', (int)$db->lastInsertId(), format_currency($amount) . ' ref:' . $refNo);
    redir('/dashboard/payments?msg=' . urlencode('Payment submitted successfully. We will verify and update within 24 hours.'));
}

redir(get_home($role));
