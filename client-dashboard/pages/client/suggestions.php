<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Feedback', 'tab' => 'suggestions']);

$previous = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM suggestions WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([(int) $project['id']]);
    $previous = $stmt->fetchAll();
}
?>

<div class="p-content">
    <h1 class="p-title">Feedback & Suggestions</h1>

    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-title">Submit Feedback</div>
        <form method="POST" action="/api/portal">
            <input type="hidden" name="action" value="suggestion_submit">
            <input type="hidden" name="project_id" value="<?= $project ? $project['id'] : '' ?>">
            <div style="margin-bottom:.75rem;">
                <label style="font-size:.85rem;font-weight:600;">Rating</label>
                <div style="display:flex;gap:.25rem;margin-top:.25rem;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" class="star-btn" data-val="<?= $i ?>" style="font-size:1.5rem;background:none;border:none;cursor:pointer;color:#d1d5db;">★</button>
                    <?php endfor; ?>
                    <input type="hidden" name="rating" id="rating-input" value="0">
                </div>
            </div>
            <div style="margin-bottom:.75rem;">
                <label style="font-size:.85rem;font-weight:600;">Title</label>
                <input type="text" name="title" required class="input" placeholder="Brief title…" style="width:100%;margin-top:.25rem;">
            </div>
            <div style="margin-bottom:.75rem;">
                <label style="font-size:.85rem;font-weight:600;">Description</label>
                <textarea name="description" rows="4" required class="input" placeholder="Share your thoughts…" style="width:100%;margin-top:.25rem;resize:vertical;"></textarea>
            </div>
            <button type="submit" class="btn">Submit</button>
        </form>
    </div>

    <?php if (!empty($previous)): ?>
    <h2 style="font-size:1.1rem;margin-bottom:.75rem;">Previous Feedback</h2>
    <?php foreach ($previous as $s): ?>
    <div class="card" style="margin-bottom:1rem;">
        <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:.5rem;margin-bottom:.5rem;">
            <div style="font-weight:600;"><?= htmlspecialchars($s['title']) ?></div>
            <div style="color:#f59e0b;font-size:1rem;">
                <?php for ($i = 1; $i <= 5; $i++): ?><?= $i <= ($s['rating'] ?? 0) ? '★' : '☆' ?><?php endfor; ?>
            </div>
        </div>
        <div style="font-size:.9rem;color:#555;"><?= nl2br(htmlspecialchars($s['description'])) ?></div>
        <?php if ($s['admin_response']): ?>
        <div style="margin-top:.75rem;padding:.75rem;background:#f0fdf4;border-radius:6px;font-size:.85rem;">
            <strong style="color:#16a34a;">Response:</strong> <?= nl2br(htmlspecialchars($s['admin_response'])) ?>
        </div>
        <?php endif; ?>
        <div style="font-size:.75rem;color:#aaa;margin-top:.5rem;"><?= date('M j, Y', strtotime($s['created_at'])) ?></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.star-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const val = parseInt(btn.dataset.val);
        document.getElementById('rating-input').value = val;
        document.querySelectorAll('.star-btn').forEach(b => {
            b.style.color = parseInt(b.dataset.val) <= val ? '#f59e0b' : '#d1d5db';
        });
    });
});
</script>

<?php portal_footer(); ?>
