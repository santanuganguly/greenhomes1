<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('admin', 'super_admin');
$db = db();

$success = $error = '';
$isSuper = $user['role'] === 'super_admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'client';
        $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

        $allowed_roles = ['client', 'site_engineer', 'head_engineer'];
        if ($isSuper) $allowed_roles[] = 'admin';

        if ($name === '' || $email === '' || $_POST['password'] === '') {
            $error = 'Name, email and password are required.';
        } elseif (!in_array($role, $allowed_roles, true)) {
            $error = 'Invalid role.';
        } else {
            $exists = $db->prepare("SELECT id FROM users WHERE email = ?");
            $exists->execute([$email]);
            if ($exists->fetch()) {
                $error = 'Email already registered.';
            } else {
                $stmt = $db->prepare("INSERT INTO users (name, email, phone, role, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $role, $password]);
                audit_log('user_created', 'user', (int) $db->lastInsertId(), "Created $role: $email");
                $success = 'User created successfully.';
            }
        }
    } elseif ($action === 'edit_user') {
        $id = (int)($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'client';
        
        // Fetch target user to check permissions
        $target = $db->prepare("SELECT * FROM users WHERE id = ?");
        $target->execute([$id]);
        $targetUser = $target->fetch();
        
        $allowed_roles = ['client', 'site_engineer', 'head_engineer'];
        if ($isSuper) $allowed_roles[] = 'admin';
        
        if (!$targetUser) {
            $error = 'User not found.';
        } elseif ($name === '' || $email === '') {
            $error = 'Name and email are required.';
        } elseif (!in_array($role, $allowed_roles, true) && $role !== $targetUser['role']) {
            $error = 'Invalid role.';
        } elseif ($targetUser['role'] === 'super_admin' && !$isSuper) {
            $error = 'You do not have permission to edit a super admin.';
        } else {
            $exists = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $exists->execute([$email, $id]);
            if ($exists->fetch()) {
                $error = 'Email already used by another user.';
            } else {
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
                // If an admin is editing another admin, don't let them change the role if it's not allowed, 
                // but we already check allowed_roles above.
                $stmt->execute([$name, $email, $phone, $role, $id]);
                audit_log('user_updated', 'user', $id, "Updated details for $email");
                $success = 'User updated successfully.';
            }
        }
    } elseif ($action === 'delete_user') {
        $id = (int)($_POST['user_id'] ?? 0);
        
        $target = $db->prepare("SELECT * FROM users WHERE id = ?");
        $target->execute([$id]);
        $targetUser = $target->fetch();
        
        if (!$targetUser) {
            $error = 'User not found.';
        } elseif ($id === (int)$user['id']) {
            $error = 'You cannot delete yourself.';
        } elseif ($targetUser['role'] === 'super_admin' && !$isSuper) {
            $error = 'You do not have permission to delete a super admin.';
        } else {
            // Check if user is associated with any projects
            $chk = $db->prepare("SELECT id FROM projects WHERE client_id = ? OR manager_id = ? OR engineer_id = ?");
            $chk->execute([$id, $id, $id]);
            if ($chk->fetch()) {
                $error = 'Cannot delete user because they are assigned to one or more projects.';
            } else {
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                audit_log('user_deleted', 'user', $id, "Deleted user ID $id");
                $success = 'User deleted successfully.';
            }
        }
    }
}

$users = $db->query("SELECT u.*, (SELECT COUNT(*) FROM projects WHERE client_id = u.id OR manager_id = u.id OR engineer_id = u.id) as project_count FROM users u ORDER BY u.created_at DESC")->fetchAll();

portal_header(['title' => 'Client Management', 'active' => 'clients', 'tab' => 'admin-clients']);
?>

<div class="p-header-row">
    <div class="p-header-title">
        <h1>Client Management</h1>
        <p>Create and manage users, clients, and engineers</p>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-green"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-red"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="grid grid-cols-layout">
    <div class="card">
        <div class="card-title">Create New User</div>
        <form method="post">
            <input type="hidden" name="action" value="create_user">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" required class="form-control">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required class="form-control">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required class="form-control">
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role" class="form-control">
                    <option value="client">Client</option>
                    <option value="site_engineer">Site Engineer</option>
                    <option value="head_engineer">Head Engineer</option>
                    <?php if ($isSuper): ?><option value="admin">Admin</option><?php endif; ?>
                </select>
            </div>
            <button type="submit" class="btn">Create User</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">All Users (<?= count($users) ?>)</div>
        <table class="table table-responsive-stack">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Projects</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): 
                $canEdit = $isSuper || !in_array($u['role'], ['super_admin', 'admin']);
                $canDelete = $canEdit && $u['id'] !== $user['id'];
            ?>
                <tr>
                    <td data-label="Name"><?= htmlspecialchars($u['name']) ?></td>
                    <td data-label="Email"><?= htmlspecialchars($u['email']) ?></td>
                    <td data-label="Phone"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td data-label="Role"><span class="pill pill-<?= $u['role'] === 'admin' || $u['role'] === 'super_admin' ? 'red' : ($u['role'] === 'head_engineer' ? 'blue' : ($u['role'] === 'site_engineer' ? 'yellow' : 'gray')) ?>"><?= get_role_label($u['role']) ?></span></td>
                    <td data-label="Projects"><?= $u['project_count'] ?></td>
                    <td data-label="Joined"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td data-label="Actions">
                        <div style="display: flex; gap: 8px;">
                            <a href="user-details.php?id=<?= $u['id'] ?>" class="btn btn-sm" style="background:#f1f5f9; color:#475569;" title="View Details">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <?php if ($canEdit): ?>
                            <button type="button" class="btn btn-sm" style="background:#f1f5f9; color:#00a884;" title="Edit" onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                'id' => $u['id'],
                                'name' => $u['name'],
                                'email' => $u['email'],
                                'phone' => $u['phone'] ?? '',
                                'role' => $u['role']
                            ])) ?>)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                            <button type="button" class="btn btn-sm" style="background:#fef2f2; color:#ef4444;" title="Delete" onclick="openDeleteModal(<?= $u['id'] ?>, '<?= htmlspecialchars(str_replace("'", "\\'", $u['name'])) ?>')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center; opacity:0; transition:opacity 0.2s;">
    <div style="background:#fff; width:90%; max-width:400px; border-radius:12px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.2); transform:translateY(20px); transition:transform 0.2s;">
        <div style="padding:16px 20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <h3 style="margin:0; font-size:1.1rem; color:#1e293b; font-weight:600;">Edit User</h3>
            <button onclick="closeEditModal()" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer; padding:0;">&times;</button>
        </div>
        <form method="post" style="padding:20px;">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="editUserId">
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" id="editName" required class="form-control">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" id="editEmail" required class="form-control">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" id="editPhone" class="form-control">
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role" id="editRole" class="form-control">
                    <option value="client">Client</option>
                    <option value="site_engineer">Site Engineer</option>
                    <option value="head_engineer">Head Engineer</option>
                    <?php if ($isSuper): ?><option value="admin">Admin</option><?php endif; ?>
                    <option value="super_admin" style="display:none;" id="editSuperAdminOption">Super Admin</option>
                </select>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete User Modal -->
<div id="deleteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center; opacity:0; transition:opacity 0.2s;">
    <div style="background:#fff; width:90%; max-width:400px; border-radius:12px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.2); transform:translateY(20px); transition:transform 0.2s;">
        <div style="padding:16px 20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; background:#fef2f2;">
            <h3 style="margin:0; font-size:1.1rem; color:#dc2626; font-weight:600;">Delete User</h3>
            <button onclick="closeDeleteModal()" style="background:none; border:none; font-size:1.5rem; color:#64748b; cursor:pointer; padding:0;">&times;</button>
        </div>
        <form method="post" style="padding:20px;">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" id="deleteUserId">
            <p style="margin:0 0 20px 0; color:#475569; font-size:0.95rem;">Are you sure you want to delete <strong id="deleteUserName"></strong>? This action cannot be undone.</p>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn" style="background:#ef4444;">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editName').value = user.name;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editPhone').value = user.phone;
    
    const roleSelect = document.getElementById('editRole');
    const saOption = document.getElementById('editSuperAdminOption');
    if (user.role === 'super_admin') {
        saOption.style.display = 'block';
    } else {
        saOption.style.display = 'none';
    }
    roleSelect.value = user.role;
    
    const modal = document.getElementById('editModal');
    modal.style.display = 'flex';
    void modal.offsetWidth;
    modal.style.opacity = '1';
    modal.children[0].style.transform = 'translateY(0)';
}
function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.style.opacity = '0';
    modal.children[0].style.transform = 'translateY(20px)';
    setTimeout(() => { modal.style.display = 'none'; }, 200);
}

function openDeleteModal(id, name) {
    document.getElementById('deleteUserId').value = id;
    document.getElementById('deleteUserName').textContent = name;
    
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'flex';
    void modal.offsetWidth;
    modal.style.opacity = '1';
    modal.children[0].style.transform = 'translateY(0)';
}
function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.opacity = '0';
    modal.children[0].style.transform = 'translateY(20px)';
    setTimeout(() => { modal.style.display = 'none'; }, 200);
}
</script>

<?php portal_footer(); ?>
