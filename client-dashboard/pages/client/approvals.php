<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Approvals', 'tab' => 'approvals']);

$pending = [];
$decided = [];
if ($project) {
    $pid = (int) $project['id'];
    $stmt = $db->prepare("SELECT * FROM client_approvals WHERE project_id = ? AND status = 'pending' ORDER BY deadline ASC");
    $stmt->execute([$pid]);
    $pending = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT * FROM client_approvals WHERE project_id = ? AND status != 'pending' ORDER BY decided_at DESC");
    $stmt->execute([$pid]);
    $decided = $stmt->fetchAll();
}
?>

<div class="p-content">
    <h1 class="p-title">Approvals</h1>

    <?php if (!empty($pending)): ?>
    <h2 style="font-size:1.1rem;margin-bottom:.75rem;">Pending Approvals</h2>
    <?php foreach ($pending as $a): ?>
    <div class="card" style="margin-bottom:1rem;border-left:4px solid #f59e0b;">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:.5rem;">
            <div style="font-weight:600;"><?= htmlspecialchars($a['title']) ?></div>
            <span class="pill pill-yellow">Pending</span>
        </div>
        <?php if ($a['description']): ?>
        <div style="font-size:.9rem;color:#555;margin-bottom:.5rem;"><?= nl2br(htmlspecialchars($a['description'])) ?></div>
        <?php endif; ?>
        <?php if ($a['options_json']): ?>
        <div style="font-size:.85rem;color:#666;margin-bottom:.75rem;">
            <strong>Options:</strong> <?= htmlspecialchars($a['options_json']) ?>
        </div>
        <?php endif; ?>
        <div style="font-size:.8rem;color:#888;margin-bottom:.75rem;">Deadline: <?= date('M j, Y', strtotime($a['deadline'])) ?></div>
        <form method="POST" action="/api/portal" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:end;">
            <input type="hidden" name="action" value="approval_decide">
            <input type="hidden" name="approval_id" value="<?= $a['id'] ?>">
            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
            <div style="flex:1;min-width:200px;">
                <label style="font-size:.8rem;font-weight:600;">Comment (optional)</label>
                <input type="text" name="comment" class="input" placeholder="Add a comment…" style="width:100%;margin-top:.25rem;">
            </div>
            <button type="submit" name="decision" value="approved" class="btn" style="background:#16a34a;color:#fff;">Approve</button>
            <button type="submit" name="decision" value="rejected" class="btn btn-danger">Reject</button>
        </form>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($decided)): ?>
    <h2 style="font-size:1.1rem;margin:1.5rem 0 .75rem;">Decision History</h2>
    <div class="card" style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr><th>Title</th><th>Decision</th><th>Comment</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($decided as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['title']) ?></td>
                    <td><span class="pill <?= $d['status'] === 'approved' ? 'pill-green' : 'pill-red' ?>"><?= ucfirst(htmlspecialchars($d['status'])) ?></span></td>
                    <td><?= htmlspecialchars($d['client_comment'] ?? '—') ?></td>
                    <td><?= $d['decided_at'] ? date('M j, Y', strtotime($d['decided_at'])) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (empty($pending) && empty($decided)): ?>
    <div class="empty">No approvals yet.</div>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
