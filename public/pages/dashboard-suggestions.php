<?php
require_once __DIR__ . '/dashboard-layout.php';

$user = current_user();
$db = gh_db();

$stmt = $db->prepare("SELECT * FROM projects WHERE client_id = ? AND status != 'completed' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user['id']]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

$suggestions = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM suggestions WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([$project['id']]);
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

gh_dashboard_header(['title' => 'Suggestions', 'active' => '', 'tab' => 'suggestions']);
?>

<div class="d-card">
    <h1 class="d-title">Suggestions & Feedback</h1>
    <p style="color: #64748b; margin-bottom: 2rem;">Share your ideas or feedback about the project. The team will review and respond.</p>

    <?php if (!$project): ?>
        <div style="text-align: center; padding: 2rem 0; color: #64748b;">
            <p>No active project found.</p>
        </div>
    <?php else: ?>
        <?php if (isset($_GET['msg'])): ?>
            <div style="padding: 1rem; background: #dcfce7; color: #166534; border-radius: 4px; margin-bottom: 1rem;"><?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['err'])): ?>
            <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 4px; margin-bottom: 1rem;"><?= htmlspecialchars($_GET['err']) ?></div>
        <?php endif; ?>

        <form action="/api/dashboard-action" method="POST" style="max-width: 600px; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid #e2e8f0;">
            <?= gh_csrf_field() ?>
            <input type="hidden" name="action" value="add_suggestion">
            <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">

            <h2 class="d-title" style="font-size: 1.125rem;">Submit a Suggestion</h2>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Title</label>
                <input type="text" name="title" class="d-input" required placeholder="e.g. Change tile color in master bathroom">
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.25rem;">Description</label>
                <textarea name="description" class="d-input" rows="4" required placeholder="Describe your suggestion in detail..."></textarea>
            </div>
            <button type="submit" class="d-btn">Submit Suggestion</button>
        </form>

        <h2 class="d-title" style="font-size: 1.125rem;">Your Suggestions</h2>

        <?php if (empty($suggestions)): ?>
            <div style="text-align: center; padding: 2rem 0; color: #64748b; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1;">
                <p>No suggestions submitted yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($suggestions as $s):
                $statusColor = $s['status'] === 'approved' ? '#166534' : ($s['status'] === 'rejected' ? '#991b1b' : '#b45309');
                $statusBg = $s['status'] === 'approved' ? '#dcfce7' : ($s['status'] === 'rejected' ? '#fee2e2' : '#fef3c7');
                $statusIcon = $s['status'] === 'approved' ? '✓' : ($s['status'] === 'rejected' ? '✗' : '⋯');
            ?>
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem; background: #fff;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <h3 style="margin: 0; font-size: 1rem; color: #0f172a;"><?= htmlspecialchars($s['title']) ?></h3>
                        <span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 999px; background: <?= $statusBg ?>; color: <?= $statusColor ?>; font-weight: 600;">
                            <?= $statusIcon ?> <?= ucfirst($s['status']) ?>
                        </span>
                    </div>
                    <p style="color: #475569; margin: 0 0 0.75rem 0; font-size: 0.9rem;"><?= htmlspecialchars($s['description']) ?></p>
                    <div style="font-size: 0.75rem; color: #94a3b8;">Submitted <?= date('M j, Y g:i A', strtotime($s['created_at'])) ?></div>

                    <?php if ($s['admin_response']): ?>
                        <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 6px; border-left: 3px solid <?= $statusColor ?>;">
                            <div style="font-weight: 600; font-size: 0.8rem; color: #475569; margin-bottom: 0.25rem;">Team Response:</div>
                            <div style="color: #0f172a; font-size: 0.9rem;"><?= htmlspecialchars($s['admin_response']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php gh_dashboard_footer(); ?>
