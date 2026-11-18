<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/config.php';
require_once dirname(__DIR__, 2) . '/src/auth.php';
require_once dirname(__DIR__, 2) . '/src/csrf.php';
require_once dirname(__DIR__, 2) . '/src/mail.php';
require_once dirname(__DIR__, 2) . '/src/chat.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

csrf_verify($_POST['_csrf'] ?? null);

$user = current_user();
if (!$user) {
    header('Location: /login');
    exit;
}

$action = $_POST['action'] ?? '';
$db = gh_db();

function notify_client(int $projectId, string $subject, string $bodyHtml): void
{
    $stmt = db()->prepare("SELECT u.email, u.name, p.name AS project_name FROM projects p JOIN users u ON p.client_id = u.id WHERE p.id=?");
    $stmt->execute([$projectId]);
    $row = $stmt->fetch();
    if ($row) {
        $fullHtml = '<div style="max-width:600px;margin:0 auto;font-family:sans-serif;padding:24px;">
            <h2 style="color:#166534;">Green Homes</h2>
            <p>Hi ' . htmlspecialchars($row['name']) . ',</p>
            ' . $bodyHtml . '
            <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">
            <p style="color:#94a3b8;font-size:12px;">
                Project: ' . htmlspecialchars($row['project_name']) . '<br>
                <a href="' . gh_app_url() . '/dashboard" style="color:#166534;">View your dashboard</a>
            </p>
        </div>';
        send_mail($row['email'], $subject, $fullHtml);
    }
}

if ($action === 'update_password') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (strlen($password) < 6) {
        header('Location: /dashboard/settings?err=' . urlencode('Password must be at least 6 characters.'));
        exit;
    }
    if ($password !== $confirm) {
        header('Location: /dashboard/settings?err=' . urlencode('Passwords do not match.'));
        exit;
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    if ($stmt->execute([$hash, $user['id']])) {
        header('Location: /dashboard/settings?msg=' . urlencode('Password updated successfully.'));
    } else {
        header('Location: /dashboard/settings?err=' . urlencode('Failed to update password.'));
    }
    exit;
}

if ($action === 'save_fcm_token') {
    $token = trim($_POST['token'] ?? '');
    if ($token) {
        $stmt = $db->prepare("UPDATE users SET fcm_token = ? WHERE id = ?");
        $stmt->execute([$token, $user['id']]);
        http_response_code(200);
        exit('Token saved');
    }
    http_response_code(400);
    exit('Missing token');
}

if ($action === 'create_project') {
    // Only admins can create projects
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Forbidden');
    }
    
    $client_id = (int)($_POST['client_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $expected_date = $_POST['expected_completion_date'] ?? null;
    
    if (!$client_id || !$name || !$location) {
        header('Location: /admin/projects/new?err=' . urlencode('Missing required fields.'));
        exit;
    }
    
    // Ensure empty dates are treated as null in SQL
    $start_date = empty($start_date) ? null : $start_date;
    $expected_date = empty($expected_date) ? null : $expected_date;
    
    $stmt = $db->prepare("
        INSERT INTO projects (client_id, name, location, start_date, expected_completion_date) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$client_id, $name, $location, $start_date, $expected_date])) {
        $project_id = (int)$db->lastInsertId();
        chat_ensure_room($project_id);
        header('Location: /admin/projects');
    } else {
        header('Location: /admin/projects/new?err=' . urlencode('Failed to create project.'));
    }
    exit;
}

// ==========================================
// ADMIN: Update project details
// ==========================================
if ($action === 'update_project') {
    if ($user['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

    $project_id = (int)($_POST['project_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $status = $_POST['status'] ?? '';
    $budget = $_POST['budget'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $expected_date = $_POST['expected_completion_date'] ?? null;

    $allowed_statuses = ['planning','foundation','framing','exterior','interior','finishing','completed'];
    if (!$project_id || !$name || !$location || !in_array($status, $allowed_statuses, true)) {
        header('Location: /admin/projects/detail?id=' . $project_id . '&err=' . urlencode('Missing or invalid fields.'));
        exit;
    }

    $budget = $budget === '' || $budget === null ? null : (float) $budget;
    $start_date = empty($start_date) ? null : $start_date;
    $expected_date = empty($expected_date) ? null : $expected_date;

    $stmt = $db->prepare("UPDATE projects SET name=?, location=?, status=?, budget=?, start_date=?, expected_completion_date=? WHERE id=?");
    if ($stmt->execute([$name, $location, $status, $budget, $start_date, $expected_date, $project_id])) {
        header('Location: /admin/projects/detail?id=' . $project_id . '&msg=' . urlencode('Project updated.'));
    } else {
        header('Location: /admin/projects/detail?id=' . $project_id . '&err=' . urlencode('Failed to update project.'));
    }
    exit;
}

// ==========================================
// ADMIN: Add milestone
// ==========================================
if ($action === 'add_milestone') {
    if ($user['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

    $project_id = (int)($_POST['project_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $expected_date = $_POST['expected_date'] ?? null;
    $order_index = (int)($_POST['order_index'] ?? 0);
    $status = $_POST['status'] ?? 'upcoming';

    if (!$project_id || !$title) {
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=milestones&err=' . urlencode('Title is required.'));
        exit;
    }

    $expected_date = empty($expected_date) ? null : $expected_date;
    $stmt = $db->prepare("INSERT INTO milestones (project_id, title, description, status, order_index, expected_date) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$project_id, $title, $description, $status, $order_index, $expected_date])) {
        notify_client($project_id, 'New milestone: ' . $title,
            '<p>A new milestone has been added to your project:</p>
            <div style="background:#f0fdf4;padding:16px;border-radius:8px;margin:16px 0;">
                <h3 style="margin:0 0 4px 0;color:#166534;">' . htmlspecialchars($title) . '</h3>
                ' . ($description ? '<p style="margin:0;color:#475569;">' . htmlspecialchars($description) . '</p>' : '') . '
            </div>');
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=milestones&msg=' . urlencode('Milestone added.'));
    } else {
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=milestones&err=' . urlencode('Failed to add milestone.'));
    }
    exit;
}

// ==========================================
// ADMIN: Update milestone status
// ==========================================
if ($action === 'update_milestone_status') {
    if ($user['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

    $milestone_id = (int)($_POST['milestone_id'] ?? 0);
    $project_id = (int)($_POST['project_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed = ['upcoming', 'in_progress', 'completed'];
    if (!$milestone_id || !in_array($status, $allowed, true)) {
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=milestones&err=' . urlencode('Invalid status.'));
        exit;
    }

    $completed_date = $status === 'completed' ? date('Y-m-d') : null;
    $stmt = $db->prepare("UPDATE milestones SET status=?, completed_date=? WHERE id=?");
    $stmt->execute([$status, $completed_date, $milestone_id]);

    if ($status === 'completed') {
        $stmt2 = $db->prepare("SELECT title FROM milestones WHERE id=?");
        $stmt2->execute([$milestone_id]);
        $mTitle = $stmt2->fetchColumn();
        notify_client($project_id, 'Milestone completed: ' . $mTitle,
            '<p style="color:#16a34a;font-weight:600;">✓ A milestone has been marked as completed:</p>
            <div style="background:#f0fdf4;padding:16px;border-radius:8px;margin:16px 0;">
                <h3 style="margin:0;color:#166534;">' . htmlspecialchars($mTitle) . '</h3>
            </div>');
    }

    header('Location: /admin/projects/detail?id=' . $project_id . '&tab=milestones&msg=' . urlencode('Milestone status updated.'));
    exit;
}

// ==========================================
// ADMIN: Delete milestone
// ==========================================
if ($action === 'delete_milestone') {
    if ($user['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

    $milestone_id = (int)($_POST['milestone_id'] ?? 0);
    $project_id = (int)($_POST['project_id'] ?? 0);

    if ($milestone_id) {
        $stmt = $db->prepare("DELETE FROM milestones WHERE id=?");
        $stmt->execute([$milestone_id]);
    }

    header('Location: /admin/projects/detail?id=' . $project_id . '&tab=milestones&msg=' . urlencode('Milestone deleted.'));
    exit;
}

// ==========================================
// ADMIN: Add photo
// ==========================================
if ($action === 'add_photo') {
    if ($user['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

    $project_id = (int)($_POST['project_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if (!$project_id || !$title || !$url) {
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=photos&err=' . urlencode('Title and URL are required.'));
        exit;
    }

    $stmt = $db->prepare("INSERT INTO photos (project_id, title, url, category) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$project_id, $title, $url, $category ?: null])) {
        notify_client($project_id, 'New photo: ' . $title,
            '<p>A new photo has been added to your project gallery:</p>
            <div style="background:#f0fdf4;padding:16px;border-radius:8px;margin:16px 0;">
                <h3 style="margin:0 0 4px 0;color:#166534;">' . htmlspecialchars($title) . '</h3>
                <p style="margin:0;color:#475569;">' . ($category ? htmlspecialchars($category) : 'Site photo') . '</p>
            </div>
            <p><a href="' . gh_app_url() . '/dashboard/gallery" style="color:#166534;">View gallery →</a></p>');
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=photos&msg=' . urlencode('Photo added.'));
    } else {
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=photos&err=' . urlencode('Failed to add photo.'));
    }
    exit;
}

// ==========================================
// ADMIN: Delete photo
// ==========================================
if ($action === 'delete_photo') {
    if ($user['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

    $photo_id = (int)($_POST['photo_id'] ?? 0);
    $project_id = (int)($_POST['project_id'] ?? 0);

    if ($photo_id) {
        $stmt = $db->prepare("DELETE FROM photos WHERE id=?");
        $stmt->execute([$photo_id]);
    }

    header('Location: /admin/projects/detail?id=' . $project_id . '&tab=photos&msg=' . urlencode('Photo deleted.'));
    exit;
}

// ==========================================
// ADMIN: Add document
// ==========================================
if ($action === 'add_document') {
    if ($user['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

    $project_id = (int)($_POST['project_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $type = trim($_POST['type'] ?? 'other');

    if (!$project_id || !$title || !$url) {
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=documents&err=' . urlencode('Title and URL are required.'));
        exit;
    }

    $stmt = $db->prepare("INSERT INTO documents (project_id, title, url, type) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$project_id, $title, $url, $type])) {
        notify_client($project_id, 'New document: ' . $title,
            '<p>A new document has been added to your document vault:</p>
            <div style="background:#f0fdf4;padding:16px;border-radius:8px;margin:16px 0;">
                <h3 style="margin:0 0 4px 0;color:#166534;">' . htmlspecialchars($title) . '</h3>
                <p style="margin:0;color:#475569;">Type: ' . htmlspecialchars(strtoupper($type)) . '</p>
            </div>
            <p><a href="' . gh_app_url() . '/dashboard/documents" style="color:#166534;">View documents →</a></p>');
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=documents&msg=' . urlencode('Document added.'));
    } else {
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=documents&err=' . urlencode('Failed to add document.'));
    }
    exit;
}

// ==========================================
// ADMIN: Delete document
// ==========================================
if ($action === 'delete_document') {
    if ($user['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

    $document_id = (int)($_POST['document_id'] ?? 0);
    $project_id = (int)($_POST['project_id'] ?? 0);

    if ($document_id) {
        $stmt = $db->prepare("DELETE FROM documents WHERE id=?");
        $stmt->execute([$document_id]);
    }

    header('Location: /admin/projects/detail?id=' . $project_id . '&tab=documents&msg=' . urlencode('Document deleted.'));
    exit;
}

// ==========================================
// ADMIN: Create invoice
// ==========================================
if ($action === 'create_invoice') {
    if ($user['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

    $project_id = (int)($_POST['project_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $due_date = $_POST['due_date'] ?? '';
    $url = trim($_POST['url'] ?? '');

    if (!$project_id || !$title || !$amount || !$due_date) {
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=invoices&err=' . urlencode('Title, amount, and due date are required.'));
        exit;
    }

    $stmt = $db->prepare("INSERT INTO invoices (project_id, title, amount, due_date, url) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$project_id, $title, $amount, $due_date, $url ?: null])) {
        notify_client($project_id, 'New invoice: ' . $title,
            '<p>A new invoice has been generated for your project:</p>
            <div style="background:#fef3c7;padding:16px;border-radius:8px;margin:16px 0;">
                <h3 style="margin:0 0 4px 0;color:#b45309;">' . htmlspecialchars($title) . '</h3>
                <p style="margin:0;color:#475569;font-size:24px;font-weight:600;">₹' . number_format($amount) . '</p>
                <p style="margin:4px 0 0 0;color:#475569;">Due: ' . date('M j, Y', strtotime($due_date)) . '</p>
            </div>
            <p><a href="' . gh_app_url() . '/dashboard/payments" style="color:#166534;">View payments →</a></p>');
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=invoices&msg=' . urlencode('Invoice created.'));
    } else {
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=invoices&err=' . urlencode('Failed to create invoice.'));
    }
    exit;
}

// ==========================================
// CLIENT: Add suggestion
// ==========================================
if ($action === 'add_suggestion') {
    $project_id = (int)($_POST['project_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!$project_id || !$title || !$description) {
        header('Location: /dashboard/suggestions?err=' . urlencode('Title and description are required.'));
        exit;
    }

    $stmt = $db->prepare("SELECT id FROM projects WHERE id=? AND client_id=?");
    $stmt->execute([$project_id, $user['id']]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        exit('Forbidden');
    }

    $stmt = $db->prepare("INSERT INTO suggestions (project_id, title, description) VALUES (?, ?, ?)");
    if ($stmt->execute([$project_id, $title, $description])) {
        header('Location: /dashboard/suggestions?msg=' . urlencode('Suggestion submitted. The team will review it.'));
    } else {
        header('Location: /dashboard/suggestions?err=' . urlencode('Failed to submit suggestion.'));
    }
    exit;
}

// ==========================================
// ADMIN: Respond to suggestion
// ==========================================
if ($action === 'respond_to_suggestion') {
    if ($user['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

    $suggestion_id = (int)($_POST['suggestion_id'] ?? 0);
    $project_id = (int)($_POST['project_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $admin_response = trim($_POST['admin_response'] ?? '');

    if (!$suggestion_id || !in_array($decision, ['approved', 'rejected'], true) || !$admin_response) {
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=suggestions&err=' . urlencode('Response and decision are required.'));
        exit;
    }

    $stmt = $db->prepare("UPDATE suggestions SET status=?, admin_response=? WHERE id=?");
    $stmt->execute([$decision, $admin_response, $suggestion_id]);

    $stmt2 = $db->prepare("SELECT title FROM suggestions WHERE id=?");
    $stmt2->execute([$suggestion_id]);
    $sTitle = $stmt2->fetchColumn();
    notify_client($project_id, 'Suggestion ' . $decision . ': ' . $sTitle,
        '<p>Your suggestion has been reviewed:</p>
        <div style="background:' . ($decision === 'approved' ? '#f0fdf4' : '#fef2f2') . ';padding:16px;border-radius:8px;margin:16px 0;">
            <h3 style="margin:0 0 4px 0;color:' . ($decision === 'approved' ? '#166534' : '#991b1b') . ';">' . ($decision === 'approved' ? '✓ Approved' : '✗ Not approved') . '</h3>
            <p style="margin:0 0 8px 0;color:#475569;"><strong>Your suggestion:</strong> ' . htmlspecialchars($sTitle) . '</p>
            <p style="margin:0;color:#475569;"><strong>Team response:</strong> ' . htmlspecialchars($admin_response) . '</p>
        </div>
        <p><a href="' . gh_app_url() . '/dashboard/suggestions" style="color:#166534;">View suggestions →</a></p>');

    header('Location: /admin/projects/detail?id=' . $project_id . '&tab=suggestions&msg=' . urlencode('Suggestion ' . $decision . '.'));
    exit;
}

// ==========================================
// ADMIN: Update invoice status
// ==========================================
if ($action === 'update_invoice_status') {
    if ($user['role'] !== 'admin') { http_response_code(403); exit('Forbidden'); }

    $invoice_id = (int)($_POST['invoice_id'] ?? 0);
    $project_id = (int)($_POST['project_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    $allowed = ['pending', 'paid', 'overdue'];
    if (!$invoice_id || !in_array($status, $allowed, true)) {
        header('Location: /admin/projects/detail?id=' . $project_id . '&tab=invoices&err=' . urlencode('Invalid status.'));
        exit;
    }

    $paid_date = $status === 'paid' ? date('Y-m-d') : null;
    $stmt = $db->prepare("UPDATE invoices SET status=?, paid_date=? WHERE id=?");
    $stmt->execute([$status, $paid_date, $invoice_id]);

    header('Location: /admin/projects/detail?id=' . $project_id . '&tab=invoices&msg=' . urlencode('Invoice status updated.'));
    exit;
}

// Fallback
header('Location: /dashboard');
exit;
