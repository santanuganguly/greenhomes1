<?php
require_once __DIR__ . '/dashboard-layout.php';

$user = current_user();
$db = gh_db();

// Fetch active project
$stmt = $db->prepare("SELECT * FROM projects WHERE client_id = ? AND status != 'completed' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user['id']]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

$photos = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM photos WHERE project_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$project['id']]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

gh_dashboard_header(['title' => 'Project Gallery', 'active' => '', 'tab' => 'gallery']);
?>

<div class="d-card">
    <h1 class="d-title">Project Gallery</h1>
    <p style="color: #64748b; margin-bottom: 2rem;">Watch your dream home come to life.</p>

    <?php if (!$project): ?>
        <div style="text-align: center; padding: 3rem 0; color: #64748b;">
            <p>No active project found.</p>
        </div>
    <?php elseif (empty($photos)): ?>
        <div style="text-align: center; padding: 3rem 0; color: #64748b;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem auto; opacity: 0.5;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
            <p>No photos have been uploaded for your project yet.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">
            <?php foreach ($photos as $photo): ?>
                <div style="border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff;">
                    <div style="aspect-ratio: 4/3; background: #f1f5f9; position: relative;">
                        <img src="<?= htmlspecialchars($photo['url']) ?>" alt="<?= htmlspecialchars($photo['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy" />
                    </div>
                    <div style="padding: 1rem;">
                        <h3 style="margin: 0 0 0.25rem 0; font-size: 1rem; color: #0f172a;"><?= htmlspecialchars($photo['title']) ?></h3>
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748b;">
                            <span><?= htmlspecialchars($photo['category'] ?? 'Site Update') ?></span>
                            <span><?= date('M j, Y', strtotime($photo['uploaded_at'])) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php gh_dashboard_footer(); ?>
