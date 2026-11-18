<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('site_engineer', 'head_engineer', 'admin', 'super_admin');
$db = db();

if (in_array($user['role'], ['admin', 'super_admin', 'head_engineer'], true)) {
    $projects = $db->query("SELECT id, name FROM projects WHERE status != 'completed' ORDER BY name")->fetchAll();
} else {
    $projects = $db->prepare("SELECT id, name FROM projects WHERE (engineer_id = ? OR engineer_id IS NULL OR manager_id = ?) AND status != 'completed' ORDER BY name");
    $projects->execute([$user['id'], $user['id']]);
    $projects = $projects->fetchAll();
}

$selectedProject = (int) ($_GET['project_id'] ?? ($_POST['project_id'] ?? ($projects[0]['id'] ?? 0)));
$today = date('Y-m-d');
$existingNote = null;

if ($selectedProject) {
    $stmt = $db->prepare("SELECT * FROM engineer_notes WHERE project_id = ? AND note_date = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$selectedProject, $today]);
    $existingNote = $stmt->fetch();
}

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_today') {
    $pid = (int) $_POST['project_id'];
    $report = trim($_POST['report'] ?? '');

    // Handle Photo Uploads
    $photoUrls = [];
    if ($existingNote && !empty($existingNote['photos'])) {
        $existingArr = json_decode($existingNote['photos'], true);
        if (is_array($existingArr)) $photoUrls = $existingArr;
    }

    if (!empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name'])) {
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/photos/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        foreach ($_FILES['photos']['name'] as $i => $name) {
            if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK && !empty($name)) {
                $tmpName = $_FILES['photos']['tmp_name'][$i];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic'], true)) {
                    $filename = 'd' . $pid . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                    $targetPath = $uploadDir . $filename;
                    $publicUrl = '/uploads/photos/' . $filename;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $photoUrls[] = $publicUrl;

                        try {
                            $stmtPhoto = $db->prepare("INSERT INTO photos (project_id, title, url, category) VALUES (?, ?, ?, 'daily_report')");
                            $stmtPhoto->execute([$pid, 'Daily Work - ' . date('M j, Y'), $publicUrl]);
                        } catch (Exception $e) {}
                    }
                }
            }
        }
    }

    $photosJson = !empty($photoUrls) ? json_encode($photoUrls) : null;

    if ($existingNote) {
        $stmt = $db->prepare("UPDATE engineer_notes SET content = ?, photos = ? WHERE id = ?");
        $stmt->execute([$report, $photosJson, $existingNote['id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO engineer_notes (project_id, created_by, note_date, content, photos) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$pid, $user['id'], $today, $report, $photosJson]);
    }
    $success = "Today's work report & photos saved successfully.";

    // Refresh
    $stmt = $db->prepare("SELECT * FROM engineer_notes WHERE project_id = ? AND note_date = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$pid, $today]);
    $existingNote = $stmt->fetch();
    $selectedProject = $pid;
}

// Fetch past daily notes for the project
$pastNotes = [];
if ($selectedProject) {
    $stmt = $db->prepare("SELECT n.*, u.name AS created_by_name FROM engineer_notes n LEFT JOIN users u ON n.created_by = u.id WHERE n.project_id = ? ORDER BY n.note_date DESC, n.created_at DESC LIMIT 15");
    $stmt->execute([$selectedProject]);
    $pastNotes = $stmt->fetchAll();
}

portal_header(['title' => "Today's Work", 'active' => 'today', 'tab' => 'eng-today']);
?>

<div class="hero">
    <h1>Today's Work</h1>
    <p><?= date('l, F j, Y') ?></p>
</div>

<?php if ($success): ?><div class="alert alert-green"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
    <?php if (empty($projects)): ?>
        <p class="empty">No active projects assigned</p>
    <?php else: ?>
        <form method="get" style="margin-bottom:1.25rem">
            <div class="form-group">
                <label style="font-weight:700;">Select Project</label>
                <select name="project_id" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $selectedProject === (int)$p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($selectedProject): ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_today">
                <input type="hidden" name="project_id" value="<?= $selectedProject ?>">
                <div class="form-group">
                    <label style="font-weight:700;">Today's Work Report *</label>
                    <textarea name="report" rows="6" class="form-control" required placeholder="Describe what work was completed on site today..."><?= htmlspecialchars($existingNote['content'] ?? '') ?></textarea>
                </div>

                <!-- PHOTO UPLOAD FIELD -->
                <div class="form-group" style="background:#f8fafc; border:2px dashed #cbd5e1; padding:1.25rem; border-radius:12px; margin-bottom:1.5rem;">
                    <label style="font-weight:700; color:#0f172a; display:flex; align-items:center; gap:0.5rem;">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        Attach Today's Work Photos (Multiple Images)
                    </label>
                    <p style="font-size:0.8rem; color:#64748b; margin:.25rem 0 0.75rem 0;">Upload site pictures of today's work for admin & client review.</p>
                    <input type="file" name="photos[]" multiple accept="image/*" class="form-control" style="border:none; padding:0;">
                </div>

                <?php
                $existingPhotos = !empty($existingNote['photos']) ? json_decode($existingNote['photos'], true) : [];
                if (!empty($existingPhotos) && is_array($existingPhotos)):
                ?>
                    <div style="margin-bottom:1.5rem;">
                        <label style="font-weight:600; font-size:0.85rem; color:#64748b;">Uploaded Photos Today:</label>
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.5rem;">
                            <?php foreach ($existingPhotos as $imgUrl): ?>
                                <a href="<?= htmlspecialchars($imgUrl) ?>" target="_blank" style="border-radius:8px; overflow:hidden; border:1px solid #e2e8f0;">
                                    <img src="<?= htmlspecialchars($imgUrl) ?>" style="width:80px; height:80px; object-fit:cover; display:block;">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn" style="background:#166534; color:#fff;"><?= $existingNote ? 'Update Today\'s Report' : 'Submit Today\'s Report' ?></button>
                <?php if ($existingNote): ?>
                    <small style="margin-left:.75rem; color:#64748b">Last updated: <?= date('g:i A', strtotime($existingNote['created_at'])) ?></small>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (!empty($pastNotes)): ?>
    <div style="margin-top:2rem;">
        <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:1rem; color:#0f172a;">Recent Daily Work Log</h2>
        <?php foreach ($pastNotes as $n): ?>
            <div class="card" style="margin-bottom:1rem; border-left:4px solid #0284c7;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <strong style="font-size:0.95rem; color:#0f172a;"><?= date('l, M j, Y', strtotime($n['note_date'])) ?></strong>
                    <span style="font-size:0.8rem; color:#64748b;">By <?= htmlspecialchars($n['created_by_name'] ?? 'Engineer') ?></span>
                </div>
                <div style="margin-top:.5rem; font-size:0.9rem; color:#334155; line-height:1.5;">
                    <?= nl2br(htmlspecialchars($n['content'])) ?>
                </div>

                <?php
                $nPhotos = !empty($n['photos']) ? json_decode($n['photos'], true) : [];
                if (!empty($nPhotos) && is_array($nPhotos)):
                ?>
                    <div style="margin-top:0.75rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                        <?php foreach ($nPhotos as $imgUrl): ?>
                            <a href="<?= htmlspecialchars($imgUrl) ?>" target="_blank" style="border-radius:8px; overflow:hidden; border:1px solid #e2e8f0;">
                                <img src="<?= htmlspecialchars($imgUrl) ?>" style="width:75px; height:75px; object-fit:cover; display:block;">
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php portal_footer(); ?>
