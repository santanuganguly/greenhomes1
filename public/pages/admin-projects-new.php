<?php
require_once __DIR__ . '/dashboard-layout.php';

$user = current_user();
if (($user['role'] ?? '') !== 'admin') {
    gh_404();
    exit;
}

$db = gh_db();

// Fetch all users to populate client dropdown
$stmt = $db->query("SELECT id, name, email FROM users WHERE role = 'user' ORDER BY name ASC");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

gh_dashboard_header(['title' => 'New Project', 'active' => '', 'tab' => 'admin-projects']);
?>

<div class="d-card" style="max-width: 600px;">
    <h1 class="d-title">Create New Project</h1>
    <p style="color: #64748b; margin-bottom: 2rem;">Assign a new construction project to a client.</p>

    <form action="/api/dashboard-action" method="POST">
        <?= gh_csrf_field() ?>
        <input type="hidden" name="action" value="create_project">
        
        <?php if (isset($_GET['err'])): ?>
            <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 4px; margin-bottom: 1rem;">
                <?= htmlspecialchars($_GET['err']) ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Select Client</label>
            <select name="client_id" class="d-input" required>
                <option value="">-- Choose a client --</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>">
                        <?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">If the client isn't listed, they need to sign up for an account first.</p>
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Project Name</label>
            <input type="text" name="name" class="d-input" required placeholder="e.g. Modern Villa in Siliguri" />
        </div>

        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Location</label>
            <input type="text" name="location" class="d-input" required placeholder="e.g. Salugara, Siliguri" />
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Start Date</label>
                <input type="date" name="start_date" class="d-input" />
            </div>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 500; margin-bottom: 0.5rem;">Expected Completion</label>
                <input type="date" name="expected_completion_date" class="d-input" />
            </div>
        </div>

        <div style="margin-top: 1rem;">
            <button type="submit" class="d-btn">Create Project</button>
            <a href="/admin/projects" class="d-btn" style="background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; margin-left: 0.5rem;">Cancel</a>
        </div>
    </form>
</div>

<?php gh_dashboard_footer(); ?>
