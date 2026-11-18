<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('site_engineer', 'head_engineer', 'admin', 'super_admin');
$db = db();

if (in_array($user['role'], ['admin', 'super_admin', 'head_engineer'], true)) {
    $projects = $db->query("SELECT id, name, progress_percentage FROM projects ORDER BY name")->fetchAll();
} else {
    $projects = $db->prepare("SELECT id, name, progress_percentage FROM projects WHERE engineer_id = ? OR engineer_id IS NULL OR manager_id = ? ORDER BY name");
    $projects->execute([$user['id'], $user['id']]);
    $projects = $projects->fetchAll();
}

$selectedProjectId = (int)($_GET['project_id'] ?? ($_POST['project_id'] ?? ($projects[0]['id'] ?? 0)));
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_weekly') {
    $pid = (int) $_POST['project_id'];
    $weekNumber = (int) $_POST['week_number'];
    $title = trim($_POST['title'] ?? '');
    $workCompleted = trim($_POST['work_completed'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $pctIncrease = (int) ($_POST['percentage_increased'] ?? 0);

    if ($title === '' || $workCompleted === '') {
        $error = 'Title and work completed are required.';
    } else {
        $currentProgress = 0;
        $cur = $db->prepare("SELECT progress_percentage FROM projects WHERE id = ?");
        $cur->execute([$pid]);
        $cur = $cur->fetch();
        if ($cur) $currentProgress = (int) $cur['progress_percentage'];

        $newProgress = min(100, $currentProgress + $pctIncrease);

        // Handle Photo Uploads
        $photoUrls = [];
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
                        $filename = 'w' . $pid . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                        $targetPath = $uploadDir . $filename;
                        $publicUrl = '/uploads/photos/' . $filename;

                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $photoUrls[] = $publicUrl;

                            try {
                                $stmtPhoto = $db->prepare("INSERT INTO photos (project_id, title, url, category) VALUES (?, ?, ?, 'weekly_update')");
                                $stmtPhoto->execute([$pid, $title . ' - W' . $weekNumber, $publicUrl]);
                            } catch (Exception $e) {}
                        }
                    }
                }
            }
        }

        $photosJson = !empty($photoUrls) ? json_encode($photoUrls) : null;
        $status = in_array($user['role'], ['admin', 'super_admin'], true) ? 'approved' : 'pending_approval';

        $stmt = $db->prepare("INSERT INTO weekly_updates (project_id, created_by, week_number, title, work_completed, remarks, percentage_increased, progress_after, status, photos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$pid, $user['id'], $weekNumber, $title, $workCompleted, $remarks, $pctIncrease, $newProgress, $status, $photosJson]);

        $db->prepare("UPDATE projects SET progress_percentage = ? WHERE id = ?")->execute([$newProgress, $pid]);
        audit_log('weekly_submitted', 'weekly_update', (int) $db->lastInsertId());
        
        $success = $status === 'approved' ? 'Weekly update published successfully.' : 'Weekly update submitted for approval.';
        $selectedProjectId = $pid;
    }
}

// Fetch past weekly updates for selected project
$pastUpdates = [];
if ($selectedProjectId) {
    $stmt = $db->prepare("SELECT w.*, u.name AS submitted_by FROM weekly_updates w LEFT JOIN users u ON w.created_by = u.id WHERE w.project_id = ? ORDER BY w.week_number DESC, w.created_at DESC");
    $stmt->execute([$selectedProjectId]);
    $pastUpdates = $stmt->fetchAll();
}

portal_header(['title' => 'Weekly Update', 'active' => 'weekly', 'tab' => 'eng-weekly']);
?>

<div class="hero">
    <h1>Weekly Progress Update</h1>
    <p>Submit weekly progress reports & site photos</p>
</div>

<?php if ($success): ?><div class="alert alert-green"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-red"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card">
    <?php if (empty($projects)): ?>
        <p class="empty">No active projects assigned</p>
    <?php else: ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit_weekly">
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Project *</label>
                    <select name="project_id" required class="form-control" onchange="window.location.href='?project_id=' + this.value">
                        <option value="">Select project...</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $selectedProjectId === (int)$p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?> (Current: <?= (int)$p['progress_percentage'] ?>%)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Week Number *</label>
                    <input type="number" name="week_number" value="<?= (int) date('W') ?>" required class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" required class="form-control" placeholder="e.g. Slab casting & foundation completed">
            </div>
            <div class="form-group">
                <label>Work Completed *</label>
                <textarea name="work_completed" rows="4" required class="form-control" placeholder="Describe the key work completed this week..."></textarea>
            </div>
            <div class="form-group">
                <label>Remarks / Notes</label>
                <textarea name="remarks" rows="2" class="form-control" placeholder="Any material updates or site notes..."></textarea>
            </div>

            <!-- PHOTO UPLOAD FIELD -->
            <div class="form-group" style="background:#f8fafc; border:2px dashed #cbd5e1; padding:1.25rem; border-radius:12px; margin-bottom:1.5rem;">
                <label style="font-weight:700; color:#0f172a; display:flex; align-items:center; gap:0.5rem;">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    Attach Progress Photos (Multiple Images)
                </label>
                <p style="font-size:0.8rem; color:#64748b; margin:.25rem 0 0.75rem 0;">Upload photos of site progress for clients & project managers to view.</p>
                <input type="file" name="photos[]" multiple accept="image/*" class="form-control" style="border:none; padding:0;">
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label>Progress Increased By (%)</label>
                    <input type="number" name="percentage_increased" value="0" min="0" max="100" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn" style="background:#166534; color:#fff;">Submit Weekly Report</button>
        </form>
    <?php endif; ?>
</div>

<?php if (!empty($pastUpdates)): ?>
    <div style="margin-top:2rem;">
        <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:1rem; color:#0f172a;">Submitted Weekly Updates</h2>
        <?php foreach ($pastUpdates as $u): ?>
            <div class="card" style="margin-bottom:1rem; border-left:4px solid #166534;">
                <div style="display:flex; justify-content:space-between; align-items:start; flex-wrap:wrap; gap:0.5rem;">
                    <div style="flex:1; min-width:0;">
                        <h3 class="card-title" style="margin:0;">Week <?= $u['week_number'] ?> — <?= htmlspecialchars($u['title']) ?></h3>
                        <p style="margin:.25rem 0; font-size:0.85rem; color:#64748b; line-height:1.4;">
                            Submitted by <strong><?= htmlspecialchars($u['submitted_by'] ?? 'Engineer') ?></strong> on <?= date('M j, Y', strtotime($u['created_at'])) ?>
                        </p>
                    </div>
                    <span class="pill pill-<?= $u['status'] === 'approved' || $u['status'] === 'published' ? 'green' : ($u['status'] === 'pending_approval' ? 'yellow' : 'red') ?>" style="flex-shrink:0;">
                        <?= ucfirst(str_replace('_', ' ', $u['status'])) ?>
                    </span>
                </div>

                <div style="margin-top:.75rem; font-size:0.9rem; color:#334155; line-height:1.5;">
                    <?= nl2br(htmlspecialchars($u['work_completed'])) ?>
                </div>

                <?php if ($u['remarks']): ?>
                    <div style="margin-top:.5rem; font-size:0.85rem; color:#64748b; font-style:italic;">
                        Remarks: <?= htmlspecialchars($u['remarks']) ?>
                    </div>
                <?php endif; ?>

                <!-- DISPLAY PHOTOS -->
                <?php
                $photos = !empty($u['photos']) ? json_decode($u['photos'], true) : [];
                if (!empty($photos) && is_array($photos)):
                ?>
                    <div style="margin-top:1rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
                        <?php foreach ($photos as $imgUrl): ?>
                            <a href="<?= htmlspecialchars($imgUrl) ?>" target="_blank" style="display:inline-block; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0;">
                                <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Progress Photo" style="width:90px; height:90px; object-fit:cover; display:block;">
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php portal_footer(); ?>
