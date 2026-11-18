<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('head_engineer');
$db = db();

$clients = $db->query(
    "SELECT DISTINCT u.id, u.name, u.email, u.phone
     FROM users u
     JOIN projects p ON p.client_id = u.id
     WHERE p.manager_id = ?
     ORDER BY u.name"
);
$clients->execute([$user['id']]);
$clients = $clients->fetchAll();

portal_header(['title' => 'Clients', 'active' => 'clients', 'tab' => 'pm-clients']);
?>

<div class="hero">
    <h1>My Clients</h1>
    <p>Clients assigned to your projects</p>
</div>

<?php if (empty($clients)): ?>
    <div class="card"><p class="empty">No clients found</p></div>
<?php else: ?>
    <div class="grid grid-3">
        <?php foreach ($clients as $c): ?>
            <div class="card">
                <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">
                    <div style="width:40px;height:40px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-weight:600;color:#475569"><?= strtoupper(substr($c['name'], 0, 1)) ?></div>
                    <div>
                        <strong><?= htmlspecialchars($c['name']) ?></strong>
                        <div style="font-size:.85rem;color:#64748b"><?= htmlspecialchars($c['email']) ?></div>
                    </div>
                </div>
                <?php if ($c['phone']): ?><small>📞 <?= htmlspecialchars($c['phone']) ?></small><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php portal_footer(); ?>
