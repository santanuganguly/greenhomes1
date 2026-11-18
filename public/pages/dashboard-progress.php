<?php
require_once __DIR__ . '/dashboard-layout.php';

$user = current_user();
$db = gh_db();

// Fetch active project
$stmt = $db->prepare("SELECT * FROM projects WHERE client_id = ? AND status != 'completed' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user['id']]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

$milestones = [];
if ($project) {
    $stmt = $db->prepare("SELECT * FROM milestones WHERE project_id = ? ORDER BY order_index ASC");
    $stmt->execute([$project['id']]);
    $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

gh_dashboard_header(['title' => 'Project Timeline', 'active' => '', 'tab' => 'progress']);
?>

<div class="d-card">
    <h1 class="d-title">Project Timeline</h1>
    <p style="color: #64748b; margin-bottom: 2rem;">Track the progress of your home construction.</p>

    <?php if (!$project): ?>
        <div style="text-align: center; padding: 3rem 0; color: #64748b;">
            <p>No active project found.</p>
        </div>
    <?php elseif (empty($milestones)): ?>
        <div style="text-align: center; padding: 3rem 0; color: #64748b;">
            <p>Your project manager is currently setting up the milestones for your project.</p>
        </div>
    <?php else: ?>
        <div style="position: relative; padding-left: 2rem;">
            <!-- Vertical line -->
            <div style="position: absolute; left: 11px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;"></div>
            
            <?php foreach ($milestones as $index => $m): 
                $isCompleted = $m['status'] === 'completed';
                $isInProgress = $m['status'] === 'in_progress';
                $color = $isCompleted ? '#16a34a' : ($isInProgress ? '#f59e0b' : '#94a3b8');
                $bg = $isCompleted ? '#dcfce7' : ($isInProgress ? '#fef3c7' : '#f1f5f9');
            ?>
                <div style="position: relative; margin-bottom: 2rem;">
                    <!-- Circle -->
                    <div style="position: absolute; left: -2rem; top: 0.25rem; width: 24px; height: 24px; border-radius: 50%; background: <?= $bg ?>; border: 2px solid <?= $color ?>; display: flex; align-items: center; justify-content: center; z-index: 1;">
                        <?php if ($isCompleted): ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="<?= $color ?>" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <?php endif; ?>
                    </div>
                    
                    <div style="background: #fff; border: 1px solid <?= $isInProgress ? $color : '#e2e8f0' ?>; padding: 1.25rem; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <h3 style="margin: 0; font-size: 1.125rem; color: #0f172a;"><?= htmlspecialchars($m['title']) ?></h3>
                            <span style="font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 999px; background: <?= $bg ?>; color: <?= $color ?>; font-weight: 600; text-transform: uppercase;">
                                <?= str_replace('_', ' ', $m['status']) ?>
                            </span>
                        </div>
                        <p style="color: #475569; margin: 0 0 1rem 0; font-size: 0.95rem;"><?= htmlspecialchars($m['description']) ?></p>
                        
                        <div style="display: flex; gap: 1.5rem; font-size: 0.875rem; color: #64748b;">
                            <?php if ($m['expected_date']): ?>
                                <div>
                                    <strong style="display: block;">Expected</strong>
                                    <?= date('M j, Y', strtotime($m['expected_date'])) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($m['completed_date']): ?>
                                <div>
                                    <strong style="display: block; color: #16a34a;">Completed</strong>
                                    <span style="color: #16a34a;"><?= date('M j, Y', strtotime($m['completed_date'])) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php gh_dashboard_footer(); ?>
