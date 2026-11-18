<?php
require_once __DIR__ . '/dashboard-layout.php';

$user = current_user();
$db = gh_db();

// Fetch active project
$stmt = $db->prepare("SELECT * FROM projects WHERE client_id = ? AND status != 'completed' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user['id']]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

$documents = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM documents WHERE project_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$project['id']]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

gh_dashboard_header(['title' => 'Documents Vault', 'active' => '', 'tab' => 'documents']);
?>

<div class="d-card">
    <h1 class="d-title">Documents Vault</h1>
    <p style="color: #64748b; margin-bottom: 2rem;">Securely access your contracts, floor plans, and permits.</p>

    <?php if (!$project): ?>
        <div style="text-align: center; padding: 3rem 0; color: #64748b;">
            <p>No active project found.</p>
        </div>
    <?php elseif (empty($documents)): ?>
        <div style="text-align: center; padding: 3rem 0; color: #64748b;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem auto; opacity: 0.5;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            <p>No documents have been uploaded for your project yet.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
            <?php foreach ($documents as $doc): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="padding: 0.75rem; background: #f1f5f9; border-radius: 8px; color: #475569;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        </div>
                        <div>
                            <h3 style="margin: 0 0 0.25rem 0; font-size: 0.95rem; color: #0f172a;"><?= htmlspecialchars($doc['title']) ?></h3>
                            <div style="font-size: 0.75rem; color: #64748b;">
                                <?= htmlspecialchars(strtoupper($doc['type'])) ?> • <?= date('M j, Y', strtotime($doc['uploaded_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <a href="<?= htmlspecialchars($doc['url']) ?>" target="_blank" class="d-btn" style="padding: 0.5rem; background: transparent; color: var(--gh-green-700); border: 1px solid var(--gh-green-700);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php gh_dashboard_footer(); ?>
