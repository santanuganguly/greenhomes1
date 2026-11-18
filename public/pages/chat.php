<?php
require_once __DIR__ . '/dashboard-layout.php';

$user = current_user();
if (!$user) { header('Location: /login'); exit; }

$db = gh_db();

$messages = $db->query(
    "SELECT c.*, u.name AS sender_name, u.role AS sender_role FROM global_chat_messages c
     JOIN users u ON c.sender_id = u.id ORDER BY c.created_at ASC"
)->fetchAll();

$role = $user['role'] ?? '';
$tab = ($role === 'admin') ? 'admin-chat' : 'chat';
gh_dashboard_header(['title' => 'Team Chat', 'tab' => $tab]);
?>

<style>
.chat-container { display:flex; flex-direction:column; height:calc(100vh - 220px); min-height:400px; }
.chat-msgs { flex:1; overflow-y:auto; padding:1rem; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0; display:flex; flex-direction:column; gap:.75rem; }
.chat-msg { max-width:75%; padding:.6rem .9rem; border-radius:12px; line-height:1.45; font-size:.9rem; }
.chat-msg.own { align-self:flex-end; background:#166534; color:#fff; border-bottom-right-radius:4px; }
.chat-msg.other { align-self:flex-start; background:#fff; color:#0f172a; border:1px solid #e2e8f0; border-bottom-left-radius:4px; }
.chat-msg .sender { font-size:.7rem; font-weight:600; margin-bottom:.2rem; }
.chat-msg.own .sender { color:#bbf7d0; }
.chat-msg.other .sender { color:#64748b; }
.chat-msg .time { font-size:.65rem; opacity:.7; margin-top:.25rem; }
.chat-form { display:flex; gap:.5rem; margin-top:1rem; }
.chat-form input { flex:1; margin:0; }
.chat-form .btn { flex-shrink:0; margin:0; }
.tag-role { display:inline-block; font-size:.6rem; padding:1px 6px; border-radius:99px; margin-left:.35rem; font-weight:400; }
.tag-role.admin { background:#eff6ff; color:#1e40af; }
.tag-role.engineer { background:#fef3c7; color:#92400e; }
.tag-role.client { background:#f0fdf4; color:#166534; }
</style>

<div class="d-card">
    <h1 class="d-title" style="margin-bottom:.25rem;">Team Chat</h1>
    <p style="color:#64748b;margin-bottom:1.5rem;">Messages are visible to everyone</p>

    <?php $msg = $_GET['msg'] ?? ''; $err = $_GET['err'] ?? ''; ?>
    <?php if ($msg): ?><div style="padding:.75rem 1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#166534;margin-bottom:1rem;"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div style="padding:.75rem 1rem;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#991b1b;margin-bottom:1rem;"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="chat-container">
        <div class="chat-msgs" id="chatMessages">
            <?php foreach ($messages as $m): $isOwn = (int)$m['sender_id'] === (int)$user['id']; ?>
            <div class="chat-msg <?= $isOwn ? 'own' : 'other' ?>">
                <div class="sender">
                    <?= htmlspecialchars($m['sender_name']) ?>
                    <span class="tag-role <?= htmlspecialchars($m['sender_role']) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $m['sender_role']))) ?></span>
                </div>
                <div><?= nl2br(htmlspecialchars($m['message'])) ?></div>
                <div class="time"><?= date('M j, g:i A', strtotime($m['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
            <div id="scrollTarget"></div>
        </div>

        <form method="post" action="/api/portal" class="chat-form">
            <?php $tkn = csrf_token(); ?>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($tkn) ?>">
            <input type="hidden" name="action" value="send_global_chat">
            <input type="text" name="message" class="d-input" placeholder="Type your message..." required autocomplete="off" autofocus style="margin:0;">
            <button type="submit" class="d-btn">Send</button>
        </form>
    </div>
</div>

<script>
var target = document.getElementById('scrollTarget');
if (target) target.scrollIntoView({ behavior: 'auto' });
setTimeout(function() { location.reload(); }, 10000);
</script>

<?php gh_dashboard_footer(); ?>
