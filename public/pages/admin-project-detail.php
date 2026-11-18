<?php
require_once __DIR__ . '/dashboard-layout.php';

$admin = require_admin();

$projectId = (int) ($_GET['id'] ?? 0);
if (!$projectId) {
    gh_404();
    exit;
}

$db = gh_db();

$stmt = $db->prepare("
    SELECT p.*, u.name AS client_name, u.email AS client_email, u.phone AS client_phone
    FROM projects p
    JOIN users u ON p.client_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$projectId]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    gh_404();
    exit;
}

$tab = $_GET['tab'] ?? 'overview';

$stmt = $db->prepare("SELECT * FROM milestones WHERE project_id = ? ORDER BY order_index ASC");
$stmt->execute([$projectId]);
$milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM photos WHERE project_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$projectId]);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM documents WHERE project_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$projectId]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM invoices WHERE project_id = ? ORDER BY due_date ASC");
$stmt->execute([$projectId]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM suggestions WHERE project_id = ? ORDER BY created_at DESC");
$stmt->execute([$projectId]);
$suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statuses = ['planning', 'foundation', 'framing', 'exterior', 'interior', 'finishing', 'completed'];
$currentStatusIdx = array_search($project['status'], $statuses);

gh_dashboard_header(['title' => $project['name'], 'active' => '', 'tab' => 'admin-projects']);
?>

<div class="d-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0;">
        <div>
            <a href="/admin/projects" style="color: var(--gh-green-700); text-decoration: none; font-size: 0.875rem;">&larr; Back to Projects</a>
            <h1 class="d-title" style="margin-top: 0.5rem;"><?= htmlspecialchars($project['name']) ?></h1>
            <p style="color: #64748b;">
                Client: <strong><?= htmlspecialchars($project['client_name']) ?></strong>
                &middot; <?= htmlspecialchars($project['client_email']) ?>
                <?php if ($project['client_phone']): ?>&middot; <?= htmlspecialchars($project['client_phone']) ?><?php endif; ?>
            </p>
        </div>
        <div style="text-align: right;">
            <span style="font-size: 0.75rem; padding: 0.25rem 0.75rem; border-radius: 999px; background: #f0fdf4; color: var(--gh-green-700); font-weight: 600; text-transform: uppercase;">
                <?= htmlspecialchars(str_replace('_', ' ', $project['status'])) ?>
            </span>
        </div>
    </div>
</div>

<div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; border-bottom: 1px solid #e2e8f0; padding-bottom: 0;">
    <a href="/admin/projects/detail?id=<?= $projectId ?>&tab=overview" class="d-btn" style="background: <?= $tab === 'overview' ? 'var(--gh-green-700)' : '#f1f5f9' ?>; color: <?= $tab === 'overview' ? '#fff' : '#0f172a' ?>; border: none; border-radius: 0; border-bottom: <?= $tab === 'overview' ? '2px solid var(--gh-green-700)' : '2px solid transparent' ?>;">Overview</a>
    <a href="/admin/projects/detail?id=<?= $projectId ?>&tab=milestones" class="d-btn" style="background: <?= $tab === 'milestones' ? 'var(--gh-green-700)' : '#f1f5f9' ?>; color: <?= $tab === 'milestones' ? '#fff' : '#0f172a' ?>; border: none; border-radius: 0; border-bottom: <?= $tab === 'milestones' ? '2px solid var(--gh-green-700)' : '2px solid transparent' ?>;">Milestones (<?= count($milestones) ?>)</a>
    <a href="/admin/projects/detail?id=<?= $projectId ?>&tab=photos" class="d-btn" style="background: <?= $tab === 'photos' ? 'var(--gh-green-700)' : '#f1f5f9' ?>; color: <?= $tab === 'photos' ? '#fff' : '#0f172a' ?>; border: none; border-radius: 0; border-bottom: <?= $tab === 'photos' ? '2px solid var(--gh-green-700)' : '2px solid transparent' ?>;">Photos (<?= count($photos) ?>)</a>
    <a href="/admin/projects/detail?id=<?= $projectId ?>&tab=documents" class="d-btn" style="background: <?= $tab === 'documents' ? 'var(--gh-green-700)' : '#f1f5f9' ?>; color: <?= $tab === 'documents' ? '#fff' : '#0f172a' ?>; border: none; border-radius: 0; border-bottom: <?= $tab === 'documents' ? '2px solid var(--gh-green-700)' : '2px solid transparent' ?>;">Documents (<?= count($documents) ?>)</a>
    <a href="/admin/projects/detail?id=<?= $projectId ?>&tab=invoices" class="d-btn" style="background: <?= $tab === 'invoices' ? 'var(--gh-green-700)' : '#f1f5f9' ?>; color: <?= $tab === 'invoices' ? '#fff' : '#0f172a' ?>; border: none; border-radius: 0; border-bottom: <?= $tab === 'invoices' ? '2px solid var(--gh-green-700)' : '2px solid transparent' ?>;">Invoices (<?= count($invoices) ?>)</a>
    <a href="/admin/projects/detail?id=<?= $projectId ?>&tab=suggestions" class="d-btn" style="background: <?= $tab === 'suggestions' ? 'var(--gh-green-700)' : '#f1f5f9' ?>; color: <?= $tab === 'suggestions' ? '#fff' : '#0f172a' ?>; border: none; border-radius: 0; border-bottom: <?= $tab === 'suggestions' ? '2px solid var(--gh-green-700)' : '2px solid transparent' ?>;">Suggestions (<?= count($suggestions) ?>)</a>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div style="padding: 1rem; background: #dcfce7; color: #166534; border-radius: 4px; margin-bottom: 1rem;"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
    <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 4px; margin-bottom: 1rem;"><?= htmlspecialchars($_GET['err']) ?></div>
<?php endif; ?>

<?php if ($tab === 'overview'): ?>
<div class="d-card">
    <h2 class="d-title" style="font-size: 1.25rem;">Project Information</h2>
    <form action="/api/dashboard-action" method="POST" style="max-width: 600px;">
        <?= gh_csrf_field() ?>
        <input type="hidden" name="action" value="update_project">
        <input type="hidden" name="project_id" value="<?= $projectId ?>">

        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Project Name</label>
            <input type="text" name="name" class="d-input" value="<?= htmlspecialchars($project['name']) ?>" required>
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Location</label>
            <input type="text" name="location" class="d-input" value="<?= htmlspecialchars($project['location']) ?>" required>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Status</label>
                <select name="status" class="d-input">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= $project['status'] === $s ? 'selected' : '' ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', $s))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Budget (₹)</label>
                <input type="number" name="budget" class="d-input" step="0.01" value="<?= $project['budget'] ? htmlspecialchars($project['budget']) : '' ?>" placeholder="e.g. 5000000">
            </div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Start Date</label>
                <input type="date" name="start_date" class="d-input" value="<?= htmlspecialchars($project['start_date'] ?? '') ?>">
            </div>
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Expected Completion</label>
                <input type="date" name="expected_completion_date" class="d-input" value="<?= htmlspecialchars($project['expected_completion_date'] ?? '') ?>">
            </div>
        </div>
        <button type="submit" class="d-btn">Save Changes</button>
    </form>
</div>

<div class="d-card">
    <h2 class="d-title" style="font-size: 1.25rem;">Progress Overview</h2>
    <div style="margin: 1rem 0;">
        <div style="display: flex; justify-content: space-between; font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">
            <span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $project['status']))) ?></span>
            <span><?= $currentStatusIdx !== false ? round(($currentStatusIdx / (count($statuses) - 1)) * 100) : 0 ?>%</span>
        </div>
        <div style="height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden;">
            <div style="height: 100%; width: <?= $currentStatusIdx !== false ? round(($currentStatusIdx / (count($statuses) - 1)) * 100) : 0 ?>%; background: var(--gh-green-700); border-radius: 4px; transition: width 0.3s;"></div>
        </div>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem;">
        <?php foreach ($statuses as $i => $s): ?>
            <div style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 4px; background: <?= $i <= $currentStatusIdx ? '#dcfce7' : '#f1f5f9' ?>; color: <?= $i <= $currentStatusIdx ? '#166534' : '#94a3b8' ?>;">
                <?php if ($i < $currentStatusIdx): ?><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg><?php endif; ?>
                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $s))) ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php elseif ($tab === 'milestones'): ?>
<div class="d-card">
    <h2 class="d-title" style="font-size: 1.25rem;">Add Milestone</h2>
    <form action="/api/dashboard-action" method="POST" style="max-width: 600px;">
        <?= gh_csrf_field() ?>
        <input type="hidden" name="action" value="add_milestone">
        <input type="hidden" name="project_id" value="<?= $projectId ?>">

        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Title</label>
            <input type="text" name="title" class="d-input" required placeholder="e.g. Foundation laid">
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Description</label>
            <textarea name="description" class="d-input" rows="2" placeholder="Brief description of this milestone"></textarea>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Expected Date</label>
                <input type="date" name="expected_date" class="d-input">
            </div>
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Order</label>
                <input type="number" name="order_index" class="d-input" value="<?= count($milestones) + 1 ?>" min="0">
            </div>
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Status</label>
                <select name="status" class="d-input">
                    <option value="upcoming">Upcoming</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>
        <button type="submit" class="d-btn">Add Milestone</button>
    </form>
</div>

<div class="d-card">
    <h2 class="d-title" style="font-size: 1.25rem;">Milestones</h2>
    <?php if (empty($milestones)): ?>
        <div style="text-align: center; padding: 2rem 0; color: #64748b;">
            <p>No milestones yet. Add the first one above.</p>
        </div>
    <?php else: ?>
        <?php foreach ($milestones as $m): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 0.75rem; background: #fff;">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 0.875rem; color: #94a3b8; font-weight: 600;">#<?= (int) $m['order_index'] ?></span>
                        <div>
                            <strong style="color: #0f172a;"><?= htmlspecialchars($m['title']) ?></strong>
                            <span style="font-size: 0.75rem; padding: 0.125rem 0.5rem; border-radius: 999px; background: <?= $m['status'] === 'completed' ? '#dcfce7' : ($m['status'] === 'in_progress' ? '#fef3c7' : '#f1f5f9') ?>; color: <?= $m['status'] === 'completed' ? '#16a34a' : ($m['status'] === 'in_progress' ? '#b45309' : '#64748b') ?>; margin-left: 0.5rem; font-weight: 600; text-transform: uppercase;">
                                <?= str_replace('_', ' ', $m['status']) ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($m['description']): ?>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #475569;"><?= htmlspecialchars($m['description']) ?></p>
                    <?php endif; ?>
                    <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">
                        <?php if ($m['expected_date']): ?>Expected: <?= date('M j, Y', strtotime($m['expected_date'])) ?><?php endif; ?>
                        <?php if ($m['completed_date']): ?> &middot; Completed: <?= date('M j, Y', strtotime($m['completed_date'])) ?><?php endif; ?>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <form action="/api/dashboard-action" method="POST" style="display: inline;">
                        <?= gh_csrf_field() ?>
                        <input type="hidden" name="action" value="update_milestone_status">
                        <input type="hidden" name="milestone_id" value="<?= (int) $m['id'] ?>">
                        <input type="hidden" name="project_id" value="<?= $projectId ?>">
                        <select name="status" onchange="this.form.submit()" style="padding: 0.25rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.75rem;">
                            <option value="upcoming" <?= $m['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="in_progress" <?= $m['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="completed" <?= $m['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </form>
                    <form action="/api/dashboard-action" method="POST" style="display: inline;" onsubmit="return confirm('Delete this milestone?')">
                        <?= gh_csrf_field() ?>
                        <input type="hidden" name="action" value="delete_milestone">
                        <input type="hidden" name="milestone_id" value="<?= (int) $m['id'] ?>">
                        <input type="hidden" name="project_id" value="<?= $projectId ?>">
                        <button type="submit" style="padding: 0.25rem 0.5rem; border: 1px solid #fca5a5; border-radius: 4px; background: #fff; color: #dc2626; font-size: 0.75rem; cursor: pointer;">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php elseif ($tab === 'photos'): ?>
<div class="d-card">
    <h2 class="d-title" style="font-size: 1.25rem;">Add Photo</h2>
    <form action="/api/dashboard-action" method="POST" style="max-width: 600px;">
        <?= gh_csrf_field() ?>
        <input type="hidden" name="action" value="add_photo">
        <input type="hidden" name="project_id" value="<?= $projectId ?>">

        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Title</label>
            <input type="text" name="title" class="d-input" required placeholder="e.g. Foundation work - Day 1">
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Image URL</label>
            <input type="url" name="url" class="d-input" required placeholder="https://example.com/image.jpg">
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Category</label>
            <input type="text" name="category" class="d-input" placeholder="e.g. Foundation, Framing, Interior">
        </div>
        <button type="submit" class="d-btn">Add Photo</button>
    </form>
</div>

<div class="d-card">
    <h2 class="d-title" style="font-size: 1.25rem;">Photos</h2>
    <?php if (empty($photos)): ?>
        <div style="text-align: center; padding: 2rem 0; color: #64748b;">
            <p>No photos uploaded yet.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <?php foreach ($photos as $photo): ?>
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #fff;">
                    <div style="aspect-ratio: 4/3; background: #f1f5f9;">
                        <img src="<?= htmlspecialchars($photo['url']) ?>" alt="<?= htmlspecialchars($photo['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div style="padding: 0.75rem;">
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 0.875rem; color: #0f172a;"><?= htmlspecialchars($photo['title']) ?></h3>
                        <div style="font-size: 0.75rem; color: #64748b; display: flex; justify-content: space-between; align-items: center;">
                            <span><?= htmlspecialchars($photo['category'] ?? 'Uncategorized') ?></span>
                            <form action="/api/dashboard-action" method="POST" style="display: inline;" onsubmit="return confirm('Delete this photo?')">
                                <?= gh_csrf_field() ?>
                                <input type="hidden" name="action" value="delete_photo">
                                <input type="hidden" name="photo_id" value="<?= (int) $photo['id'] ?>">
                                <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                <button type="submit" style="border: none; background: none; color: #dc2626; cursor: pointer; font-size: 0.75rem; text-decoration: underline;">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php elseif ($tab === 'documents'): ?>
<div class="d-card">
    <h2 class="d-title" style="font-size: 1.25rem;">Add Document</h2>
    <form action="/api/dashboard-action" method="POST" style="max-width: 600px;">
        <?= gh_csrf_field() ?>
        <input type="hidden" name="action" value="add_document">
        <input type="hidden" name="project_id" value="<?= $projectId ?>">

        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Title</label>
            <input type="text" name="title" class="d-input" required placeholder="e.g. Floor Plan - Final">
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Document URL</label>
            <input type="url" name="url" class="d-input" required placeholder="https://example.com/document.pdf">
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Type</label>
            <select name="type" class="d-input">
                <option value="contract">Contract</option>
                <option value="floor-plan">Floor Plan</option>
                <option value="permit">Permit</option>
                <option value="design">Design</option>
                <option value="report">Report</option>
                <option value="other">Other</option>
            </select>
        </div>
        <button type="submit" class="d-btn">Add Document</button>
    </form>
</div>

<div class="d-card">
    <h2 class="d-title" style="font-size: 1.25rem;">Documents</h2>
    <?php if (empty($documents)): ?>
        <div style="text-align: center; padding: 2rem 0; color: #64748b;">
            <p>No documents uploaded yet.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 0.75rem;">
            <?php foreach ($documents as $doc): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="padding: 0.5rem; background: #f1f5f9; border-radius: 6px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 0.9rem; color: #0f172a;"><?= htmlspecialchars($doc['title']) ?></h3>
                            <div style="font-size: 0.75rem; color: #64748b;">
                                <?= htmlspecialchars(strtoupper($doc['type'])) ?> &middot; <?= date('M j, Y', strtotime($doc['uploaded_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="<?= htmlspecialchars($doc['url']) ?>" target="_blank" class="d-btn" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1;">View</a>
                        <form action="/api/dashboard-action" method="POST" style="display: inline;" onsubmit="return confirm('Delete this document?')">
                            <?= gh_csrf_field() ?>
                            <input type="hidden" name="action" value="delete_document">
                            <input type="hidden" name="document_id" value="<?= (int) $doc['id'] ?>">
                            <input type="hidden" name="project_id" value="<?= $projectId ?>">
                            <button type="submit" style="padding: 0.25rem 0.5rem; border: 1px solid #fca5a5; border-radius: 4px; background: #fff; color: #dc2626; font-size: 0.75rem; cursor: pointer;">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php elseif ($tab === 'invoices'): ?>
<div class="d-card">
    <h2 class="d-title" style="font-size: 1.25rem;">Create Invoice</h2>
    <form action="/api/dashboard-action" method="POST" style="max-width: 600px;">
        <?= gh_csrf_field() ?>
        <input type="hidden" name="action" value="create_invoice">
        <input type="hidden" name="project_id" value="<?= $projectId ?>">

        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Title</label>
            <input type="text" name="title" class="d-input" required placeholder="e.g. Foundation payment">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Amount (₹)</label>
                <input type="number" name="amount" class="d-input" step="0.01" required placeholder="e.g. 500000">
            </div>
            <div>
                <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Due Date</label>
                <input type="date" name="due_date" class="d-input" required>
            </div>
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Invoice URL (optional)</label>
            <input type="url" name="url" class="d-input" placeholder="https://example.com/invoice.pdf">
        </div>
        <button type="submit" class="d-btn">Create Invoice</button>
    </form>
</div>

<div class="d-card">
    <h2 class="d-title" style="font-size: 1.25rem;">Invoices</h2>
    <?php if (empty($invoices)): ?>
        <div style="text-align: center; padding: 2rem 0; color: #64748b;">
            <p>No invoices yet.</p>
        </div>
    <?php else: ?>
        <table style="width: 100%; text-align: left; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 0.875rem;">
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Title</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Amount</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Due Date</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500;">Status</th>
                    <th style="padding: 1rem 0.5rem; font-weight: 500; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv):
                    $isPaid = $inv['status'] === 'paid';
                    $isOverdue = $inv['status'] === 'overdue';
                    $statusColor = $isPaid ? '#166534' : ($isOverdue ? '#991b1b' : '#b45309');
                    $statusBg = $isPaid ? '#dcfce7' : ($isOverdue ? '#fee2e2' : '#fef3c7');
                ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 1rem 0.5rem; font-weight: 500; color: #0f172a;"><?= htmlspecialchars($inv['title']) ?></td>
                        <td style="padding: 1rem 0.5rem;">₹<?= number_format($inv['amount'], 2) ?></td>
                        <td style="padding: 1rem 0.5rem; color: #64748b;"><?= date('M j, Y', strtotime($inv['due_date'])) ?></td>
                        <td style="padding: 1rem 0.5rem;">
                            <form action="/api/dashboard-action" method="POST" style="display: inline;">
                                <?= gh_csrf_field() ?>
                                <input type="hidden" name="action" value="update_invoice_status">
                                <input type="hidden" name="invoice_id" value="<?= (int) $inv['id'] ?>">
                                <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                <select name="status" onchange="this.form.submit()" style="padding: 0.25rem 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.75rem;">
                                    <option value="pending" <?= $inv['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="paid" <?= $inv['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="overdue" <?= $inv['status'] === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                                </select>
                            </form>
                        </td>
                        <td style="padding: 1rem 0.5rem; text-align: right;">
                            <?php if ($inv['url']): ?>
                                <a href="<?= htmlspecialchars($inv['url']) ?>" target="_blank" class="d-btn" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1;">View</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php elseif ($tab === 'suggestions'): ?>
<div class="d-card">
    <h2 class="d-title" style="font-size: 1.25rem;">Client Suggestions</h2>
    <p style="color: #64748b; margin-bottom: 2rem;">Review and respond to feedback from the client.</p>

    <?php if (empty($suggestions)): ?>
        <div style="text-align: center; padding: 2rem 0; color: #64748b;">
            <p>No suggestions from the client yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($suggestions as $s):
            $statusColor = $s['status'] === 'approved' ? '#166534' : ($s['status'] === 'rejected' ? '#991b1b' : '#b45309');
            $statusBg = $s['status'] === 'approved' ? '#dcfce7' : ($s['status'] === 'rejected' ? '#fee2e2' : '#fef3c7');
        ?>
            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem; background: #fff;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                    <h3 style="margin: 0; font-size: 1rem; color: #0f172a;"><?= htmlspecialchars($s['title']) ?></h3>
                    <span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 999px; background: <?= $statusBg ?>; color: <?= $statusColor ?>; font-weight: 600;">
                        <?= ucfirst($s['status']) ?>
                    </span>
                </div>
                <p style="color: #475569; margin: 0 0 0.5rem 0; font-size: 0.9rem;"><?= htmlspecialchars($s['description']) ?></p>
                <div style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 1rem;">Submitted <?= date('M j, Y g:i A', strtotime($s['created_at'])) ?></div>

                <?php if ($s['admin_response']): ?>
                    <div style="margin-bottom: 1rem; padding: 0.75rem; background: #f1f5f9; border-radius: 6px; border-left: 3px solid var(--gh-green-700);">
                        <div style="font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.25rem;">Your Response:</div>
                        <div style="color: #0f172a; font-size: 0.9rem;"><?= htmlspecialchars($s['admin_response']) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($s['status'] === 'pending'): ?>
                    <form action="/api/dashboard-action" method="POST" style="border-top: 1px solid #e2e8f0; padding-top: 1rem;">
                        <?= gh_csrf_field() ?>
                        <input type="hidden" name="action" value="respond_to_suggestion">
                        <input type="hidden" name="suggestion_id" value="<?= (int) $s['id'] ?>">
                        <input type="hidden" name="project_id" value="<?= $projectId ?>">

                        <div style="margin-bottom: 0.75rem;">
                            <label style="display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.875rem;">Response</label>
                            <textarea name="admin_response" class="d-input" rows="2" placeholder="Write your response..." required></textarea>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" name="decision" value="approved" class="d-btn" style="background: #16a34a;">Approve</button>
                            <button type="submit" name="decision" value="rejected" class="d-btn" style="background: #dc2626;">Reject</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php gh_dashboard_footer(); ?>
