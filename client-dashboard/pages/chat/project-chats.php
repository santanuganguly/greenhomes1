<?php
require_once __DIR__ . '/../layout.php';
$user = current_user();
if (!$user) { header('Location: /login'); exit; }
$db = db();
if (file_exists(dirname(__DIR__, 3) . '/src/chat.php')) { require_once dirname(__DIR__, 3) . '/src/chat.php'; } else { require_once dirname(__DIR__, 4) . '/src/chat.php'; }

$userId = (int)$user['id'];
$rooms = chat_get_user_rooms($userId);
$activeRoomId = !empty($_GET['room']) ? (int)$_GET['room'] : 0;

$activeRoom = null;
$messages = [];
$pinned = [];
$memberCount = 0;
if ($activeRoomId) {
    $ar = $db->prepare("SELECT project_id FROM project_chat_rooms WHERE id = ?");
    $ar->execute([$activeRoomId]);
    $roomData = $ar->fetch();
    if ($roomData && chat_can_access($activeRoomId, $userId)) {
        $activeRoom = chat_get_room((int)$roomData['project_id']);
        $messages = chat_get_messages($activeRoomId, $userId, 0, 50);
        $pinned = chat_get_pinned($activeRoomId);
        chat_mark_room_read($activeRoomId, $userId);
        $mc = $db->prepare("SELECT COUNT(*) FROM project_chat_members WHERE room_id = ?");
        $mc->execute([$activeRoomId]);
        $memberCount = (int)$mc->fetchColumn();
    }
}

portal_header(['title' => 'Project Chats', 'tab' => 'chat']);
?>

<?php if ($activeRoom): ?>
<style>
@media (max-width: 768px) {
    .gh-appbar { display: none !important; }
    .gh-page { padding-top: env(safe-area-inset-top) !important; display: flex !important; flex-direction: column !important; height: calc(100dvh - 64px - env(safe-area-inset-bottom)) !important; }
    .p-main { padding: 0 !important; margin: 0 !important; flex: 1 !important; display: flex !important; flex-direction: column !important; min-height: 0 !important; }
    .p-content { padding: 0 !important; flex: 1 !important; display: flex !important; flex-direction: column !important; min-height: 0 !important; }
    .chat-layout { flex: 1 !important; height: auto !important; min-height: 0 !important; }
}
</style>
<?php else: ?>
<style>
@media (max-width: 768px) {
    .gh-page { display: flex !important; flex-direction: column !important; height: calc(100dvh - 64px - env(safe-area-inset-bottom)) !important; }
    .p-main { padding-bottom: 0 !important; padding-left: 0 !important; padding-right: 0 !important; flex: 1 !important; display: flex !important; flex-direction: column !important; min-height: 0 !important; }
    .p-content { padding: 0 !important; flex: 1 !important; display: flex !important; flex-direction: column !important; min-height: 0 !important; }
    .chat-layout { flex: 1 !important; height: auto !important; min-height: 0 !important; }
}
</style>
<?php endif; ?>
<style>

:root {
    --chat-sidebar-w: 300px;
    --chat-header-h: 60px;
    --chat-input-h: 70px;
    --chat-green: #166534;
    --chat-green-light: #dcfce7;
    --chat-green-bg: #f0fdf4;
}
.chat-layout { display:flex; height:calc(100vh - 130px); min-height:500px; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; background:#fff; }
.chat-sidebar { width:var(--chat-sidebar-w); flex-shrink:0; border-right:1px solid #e2e8f0; display:flex; flex-direction:column; background:#fafafa; }
.chat-sidebar-search { padding:.75rem; border-bottom:1px solid #e2e8f0; }
.chat-sidebar-search input { width:100%; padding:.5rem .75rem; border:1.5px solid #e2e8f0; border-radius:8px; font-size:.82rem; margin:0; }
.chat-sidebar-search input:focus { border-color:var(--chat-green); box-shadow:0 0 0 3px rgba(22,101,52,.1); }
.chat-room-list { flex:1; overflow-y:auto; }
.chat-room-item { display:flex; align-items:center; gap:.6rem; padding:.7rem .75rem; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background .12s; position:relative; }
.chat-room-item:hover { background:var(--chat-green-bg); }
.chat-room-item.active { background:var(--chat-green-light); border-left:3px solid var(--chat-green); }
.chat-room-avatar { width:40px; height:40px; border-radius:50%; background:var(--chat-green); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:.85rem; flex-shrink:0; }
.chat-room-info { flex:1; min-width:0; }
.chat-room-name { font-weight:600; font-size:.85rem; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.chat-room-client { font-size:.7rem; color:#64748b; }
.chat-room-last { font-size:.75rem; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
.chat-room-meta { display:flex; flex-direction:column; align-items:flex-end; gap:2px; flex-shrink:0; }
.chat-room-time { font-size:.65rem; color:#94a3b8; }
.chat-room-unread { background:#dc2626; color:#fff; font-size:.6rem; font-weight:700; min-width:18px; height:18px; border-radius:999px; display:flex; align-items:center; justify-content:center; padding:0 5px; }
.chat-room-status { font-size:.6rem; padding:1px 6px; border-radius:99px; font-weight:600; text-transform:uppercase; letter-spacing:.03em; }
.chat-room-status.active { background:#dcfce7; color:#166534; }
.chat-room-status.completed { background:#e2e8f0; color:#64748b; }
.chat-room-status.on_hold { background:#fef3c7; color:#b45309; }
.chat-room-status.cancelled { background:#fee2e2; color:#991b1b; }

.chat-main { flex:1; display:flex; flex-direction:column; min-width:0; }
.chat-placeholder { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#94a3b8; padding:2rem; text-align:center; }
.chat-placeholder svg { width:64px; height:64px; opacity:.3; margin-bottom:1rem; }
.chat-placeholder h3 { font-size:1.1rem; color:#64748b; margin-bottom:.25rem; font-family:var(--font-display); }
.chat-header { padding:.6rem 1rem; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:.75rem; background:#fff; flex-shrink:0; }
.chat-header-info { flex:1; min-width:0; }
.chat-header-title { font-weight:600; font-size:.95rem; }
.chat-header-sub { font-size:.75rem; color:#64748b; }
.chat-header-status { font-size:.65rem; padding:2px 8px; border-radius:99px; font-weight:600; text-transform:uppercase; }
.chat-header-status.active { background:#dcfce7; color:#166534; }
.chat-header-status.completed { background:#e2e8f0; color:#64748b; }
.chat-header-status.on_hold { background:#fef3c7; color:#b45309; }
.chat-header-count { font-size:.72rem; color:#64748b; display:flex; align-items:center; gap:4px; }

.chat-pinned { background:#fefce8; padding:.35rem .75rem; font-size:.78rem; color:#854d0e; border-bottom:1px solid #fef08a; display:flex; align-items:center; gap:.5rem; cursor:pointer; flex-shrink:0; }
.chat-pinned svg { flex-shrink:0; width:14px; height:14px; }
.chat-pinned-text { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1; }
.chat-pinned-count { font-weight:600; white-space:nowrap; }

.chat-messages { flex:1; overflow-y:auto; padding:1rem; display:flex; flex-direction:column; gap:.4rem; background:#f8fafc; }
.chat-msg { max-width:80%; display:flex; flex-direction:column; }
.chat-msg.own { align-self:flex-end; align-items:flex-end; }
.chat-msg.other { align-self:flex-start; align-items:flex-start; }
.chat-msg-bubble { padding:.5rem .75rem; border-radius:12px; line-height:1.45; font-size:.85rem; position:relative; word-wrap:break-word; max-width:100%; }
.chat-msg.own .chat-msg-bubble { background:var(--chat-green); color:#fff; border-bottom-right-radius:4px; }
.chat-msg.other .chat-msg-bubble { background:#fff; color:#0f172a; border:1px solid #e2e8f0; border-bottom-left-radius:4px; }
.chat-msg-sender { font-size:.7rem; font-weight:600; margin-bottom:2px; display:flex; align-items:center; gap:.3rem; }
.chat-msg.own .chat-msg-sender { color:#bbf7d0; }
.chat-msg.other .chat-msg-sender { color:#64748b; }
.chat-msg-role { font-size:.6rem; padding:1px 5px; border-radius:99px; font-weight:400; background:rgba(255,255,255,.15); }
.chat-msg.other .chat-msg-role { background:#f1f5f9; color:#64748b; }
.chat-msg-time { font-size:.6rem; opacity:.7; margin-top:2px; }
.chat-msg-read { font-size:.6rem; opacity:.6; margin-left:2px; }
.chat-msg-reply-indicator { font-size:.7rem; padding:4px 8px; background:rgba(255,255,255,.1); border-left:3px solid rgba(255,255,255,.4); border-radius:4px; margin-bottom:4px; cursor:pointer; }
.chat-msg.other .chat-msg-reply-indicator { background:#f1f5f9; border-left-color:#94a3b8; }
.chat-msg-reply-sender { font-weight:600; }
.chat-msg-reply-text { opacity:.8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.chat-msg .chat-msg-actions { display:none; gap:4px; margin-top:2px; }
.chat-msg:hover .chat-msg-actions { display:flex; }
.chat-msg-action { font-size:.6rem; padding:1px 6px; border-radius:4px; border:1px solid #e2e8f0; background:#fff; cursor:pointer; color:#64748b; }
.chat-msg-action:hover { background:#f1f5f9; color:#0f172a; }

.chat-files { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.35rem; }
.chat-file { display:flex; align-items:center; gap:.35rem; padding:3px 8px; border-radius:6px; background:rgba(255,255,255,.1); font-size:.75rem; }
.chat-msg.other .chat-file { background:#f1f5f9; }
.chat-file img { width:60px; height:60px; object-fit:cover; border-radius:6px; cursor:pointer; }
.chat-file-icon { width:32px; height:32px; display:flex; align-items:center; justify-content:center; background:#e2e8f0; border-radius:6px; font-size:.7rem; color:#64748b; }
.chat-file-name { max-width:120px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.chat-file-size { font-size:.65rem; opacity:.7; }

.chat-typing { padding:4px 16px; font-size:.78rem; color:#64748b; font-style:italic; min-height:24px; flex-shrink:0; }
.chat-typing-dots { display:inline-flex; align-items:center; gap:2px; margin-left:4px; }
.chat-typing-dots span { width:5px; height:5px; border-radius:50%; background:#94a3b8; animation:chatTyping 1.2s infinite; }
.chat-typing-dots span:nth-child(2) { animation-delay:.2s; }
.chat-typing-dots span:nth-child(3) { animation-delay:.4s; }
@keyframes chatTyping { 0%,60%,100% { opacity:.3; transform:scale(1); } 30% { opacity:1; transform:scale(1.3); } }

.chat-input-wrap { padding:.6rem .75rem; border-top:1px solid #e2e8f0; background:#fff; flex-shrink:0; }
.chat-reply-preview { display:flex; align-items:center; gap:.5rem; padding:.35rem .5rem; margin-bottom:.4rem; background:#f1f5f9; border-radius:6px; border-left:3px solid var(--chat-green); font-size:.78rem; }
.chat-reply-preview-close { cursor:pointer; color:#94a3b8; font-size:1rem; line-height:1; }
.chat-reply-preview-sender { font-weight:600; color:#0f172a; }
.chat-reply-preview-text { color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1; }
.chat-input-row { display:flex; gap:.5rem; align-items:flex-end; }
.chat-input-row textarea { flex:1; padding:.5rem .75rem; border:1.5px solid #e2e8f0; border-radius:8px; font-size:.85rem; font-family:var(--font-body); resize:none; min-height:38px; max-height:120px; margin:0; }
.chat-input-row textarea:focus { border-color:var(--chat-green); box-shadow:0 0 0 3px rgba(22,101,52,.1); }
.chat-input-btn { width:36px; height:36px; border-radius:8px; border:1.5px solid #e2e8f0; background:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:all .15s; }
.chat-input-btn:hover { border-color:var(--chat-green); background:var(--chat-green-bg); }
.chat-input-btn svg { width:18px; height:18px; color:#64748b; }
.chat-input-btn:hover svg { color:var(--chat-green); }
.chat-send-btn { width:36px; height:36px; border-radius:8px; border:none; background:var(--chat-green); color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:background .15s; }
.chat-send-btn:hover { background:#14532d; }
.chat-send-btn svg { width:18px; height:18px; }

.chat-emoji-picker { display:none; position:absolute; bottom:100%; left:0; margin-bottom:4px; background:#fff; border:1px solid #e2e8f0; border-radius:10px; box-shadow:0 4px 16px rgba(0,0,0,.1); padding:.5rem; width:280px; z-index:100; }
.chat-emoji-picker.show { display:block; }
.chat-emoji-grid { display:grid; grid-template-columns:repeat(8,1fr); gap:2px; }
.chat-emoji-item { font-size:1.3rem; text-align:center; padding:4px; cursor:pointer; border-radius:4px; transition:background .1s; }
.chat-emoji-item:hover { background:#f1f5f9; }

.spinner { width:32px; height:32px; border:3px solid #e2e8f0; border-top-color:var(--chat-green); border-radius:50%; animation:spin .6s linear infinite; margin:1rem auto; }
@keyframes spin { to { transform:rotate(360deg); } }

.chat-load-more { text-align:center; padding:4px; }
.chat-load-more a { font-size:.78rem; color:var(--chat-green); cursor:pointer; }

@media(max-width:768px) {
    .chat-layout { flex-direction:column; height:calc(100vh - 100px); }
    .chat-layout.has-active-room .chat-sidebar { display:none !important; }
    .chat-layout.has-active-room .chat-main { display:flex !important; flex:1; height:100%; }
    .chat-layout:not(.has-active-room) .chat-sidebar { display:flex !important; width:100%; height:100%; max-height:100%; }
    .chat-layout:not(.has-active-room) .chat-main { display:none !important; }
    .chat-back-btn { display:inline-flex !important; align-items:center; gap:4px; padding:4px 8px; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem; font-weight:500; color:#334155; text-decoration:none; cursor:pointer; margin-right:6px; }
}
.chat-back-btn { display:none; }
.chat-mobile-toggle { display:none; }
</style>

<div class="p-content" style="padding:0;">
    <div class="chat-layout <?= $activeRoom ? 'has-active-room' : '' ?>" id="chatLayout">
        <!-- LEFT SIDEBAR -->
        <aside class="chat-sidebar" id="chatSidebar">
            <div class="chat-sidebar-search">
                <input type="text" id="chatSearch" class="form-control" placeholder="Search projects..." style="margin:0;">
            </div>
            <div class="chat-room-list" id="chatRoomList">
                <?php if (empty($rooms)): ?>
                <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.85rem;">No project chats yet</div>
                <?php else: ?>
                <?php foreach ($rooms as $r):
                    $rid = (int)$r['room_id'];
                    $isActive = $rid === $activeRoomId;
                    $otherMsgs = (int)$r['total_other_msgs'];
                    $myRead = (int)$r['my_read_count'];
                    $unread = max(0, $otherMsgs - $myRead);
                    $initial = mb_strtoupper(mb_substr($r['project_name'], 0, 1));
                    $status = $r['project_status'] ?? 'active';
                ?>
                <div class="chat-room-item <?= $isActive ? 'active' : '' ?>" data-room-id="<?= $rid ?>" data-project="<?= htmlspecialchars($r['project_name']) ?>" onclick="loadRoom(<?= $rid ?>)">
                    <div class="chat-room-avatar"><?= htmlspecialchars($initial) ?></div>
                    <div class="chat-room-info">
                        <div class="chat-room-name"><?= htmlspecialchars($r['project_name']) ?></div>
                        <div class="chat-room-client"><?= htmlspecialchars($r['client_name'] ?? 'Client') ?></div>
                        <?php if (!empty($r['last_msg'])): ?>
                        <div class="chat-room-last"><?= htmlspecialchars(mb_strimwidth($r['last_msg'], 0, 40, '...')) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="chat-room-meta">
                        <?php if (!empty($r['last_msg_at'])): ?>
                        <span class="chat-room-time"><?= time_ago($r['last_msg_at']) ?></span>
                        <?php endif; ?>
                        <?php if ($unread > 0 && !$isActive): ?>
                        <span class="chat-room-unread"><?= $unread > 99 ? '99+' : $unread ?></span>
                        <?php endif; ?>
                        <span class="chat-room-status <?= $status ?>"><?= ucfirst(str_replace('_', ' ', $status)) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- RIGHT MAIN PANEL -->
        <main class="chat-main">
            <?php if (!$activeRoom): ?>
            <div class="chat-placeholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <h3>Select a project chat</h3>
                <p>Choose a conversation from the sidebar to start chatting</p>
            </div>
            <?php else: ?>
            <!-- Chat Header -->
            <div class="chat-header">
                <a href="<?= explode('?', $_SERVER['REQUEST_URI'])[0] ?>" class="chat-back-btn">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    Chats
                </a>
                <div class="chat-header-info">
                    <div class="chat-header-title"><?= htmlspecialchars($activeRoom['project_name']) ?></div>
                    <div class="chat-header-sub"><?= htmlspecialchars($activeRoom['client_name'] ?? 'Client') ?> · <?= htmlspecialchars($activeRoom['project_status'] ?? 'active') ?></div>
                </div>
                <span class="chat-header-status <?= $activeRoom['project_status'] ?? 'active' ?>"><?= ucfirst(str_replace('_', ' ', $activeRoom['project_status'] ?? 'active')) ?></span>
                <span class="chat-header-count">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    <?= $memberCount ?>
                </span>
            </div>

            <!-- Pinned Messages -->
            <?php if (!empty($pinned)): ?>
            <div class="chat-pinned" onclick="togglePinned()">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                <span class="chat-pinned-count"><?= count($pinned) ?> pinned</span>
                <span class="chat-pinned-text"><?= htmlspecialchars(mb_strimwidth($pinned[0]['message'], 0, 60, '...')) ?></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
            <?php endif; ?>

            <!-- Messages -->
            <div class="chat-messages" id="chatMessages">
                <?php if (count($messages) >= 50): ?>
                <div class="chat-load-more"><a onclick="loadOlder()">Load older messages</a></div>
                <?php endif; ?>
                <?php foreach ($messages as $m):
                    $isOwn = (int)$m['sender_id'] === $userId;
                    $files = chat_get_files((int)$m['id']);
                    $replyTo = null;
                    if (!empty($m['reply_to_id'])) {
                        $r = $db->prepare("SELECT m.id, m.message, u.name AS sender_name FROM project_chat_messages m JOIN users u ON m.sender_id = u.id WHERE m.id = ?");
                        $r->execute([$m['reply_to_id']]);
                        $replyTo = $r->fetch();
                    }
                ?>
                <div class="chat-msg <?= $isOwn ? 'own' : 'other' ?>" data-msg-id="<?= (int)$m['id'] ?>" data-sender-id="<?= (int)$m['sender_id'] ?>">
                    <?php if (!$isOwn): ?>
                    <div class="chat-msg-sender">
                        <?= htmlspecialchars($m['sender_name']) ?>
                        <span class="chat-msg-role"><?= htmlspecialchars(get_role_label($m['sender_role'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($replyTo): ?>
                    <div class="chat-msg-reply-indicator" onclick="scrollToMsg(<?= (int)$replyTo['id'] ?>)">
                        <strong class="chat-msg-reply-sender"><?= htmlspecialchars($replyTo['sender_name']) ?></strong>
                        <span class="chat-msg-reply-text"><?= htmlspecialchars(mb_strimwidth($replyTo['message'], 0, 80, '...')) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($isOwn): ?>
                    <div class="chat-msg-sender" style="display:flex;gap:.3rem;">
                        <span class="chat-msg-role"><?= htmlspecialchars(get_role_label($m['sender_role'])) ?></span>
                        <?= htmlspecialchars($m['sender_name']) ?>
                    </div>
                    <?php endif; ?>
                    <div class="chat-msg-bubble">
                        <?php if (!empty($m['message'])): ?>
                        <div><?= nl2br(htmlspecialchars($m['message'])) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($files)): ?>
                        <div class="chat-files">
                            <?php foreach ($files as $f):
                                $ext = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION));
                                $imgExts = ['jpg','jpeg','png','gif','webp'];
                                $isImg = in_array($ext, $imgExts, true);
                            ?>
                                <?php if ($isImg): ?>
                                <img src="/<?= htmlspecialchars($f['file_path']) ?>" alt="<?= htmlspecialchars($f['file_name']) ?>" onclick="previewPhoto('/<?= htmlspecialchars($f['file_path']) ?>')" loading="lazy">
                                <?php else: ?>
                                <a href="/<?= htmlspecialchars($f['file_path']) ?>" target="_blank" class="chat-file">
                                    <span class="chat-file-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    </span>
                                    <span class="chat-file-name"><?= htmlspecialchars($f['file_name']) ?></span>
                                    <span class="chat-file-size"><?= $f['file_size'] > 1048576 ? round($f['file_size'] / 1048576, 1) . 'MB' : round($f['file_size'] / 1024, 0) . 'KB' ?></span>
                                </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:4px;margin-top:2px;">
                        <span class="chat-msg-time"><?= time_ago($m['created_at']) ?></span>
                        <?php if ($isOwn): ?>
                        <span class="chat-msg-read">✓</span>
                        <?php endif; ?>
                    </div>
                    <div class="chat-msg-actions">
                        <span class="chat-msg-action" onclick="replyToMessage(<?= (int)$m['id'] ?>, '<?= htmlspecialchars(str_replace("'", "\\'", $m['sender_name'])) ?>', '<?= htmlspecialchars(str_replace("'", "\\'", mb_strimwidth($m['message'], 0, 50, '...'))) ?>')">Reply</span>
                    </div>
                </div>
                <?php endforeach; ?>
                <div id="chatScrollTarget"></div>
            </div>

            <!-- Typing Indicator -->
            <div class="chat-typing" id="chatTyping"></div>

            <!-- Input Area -->
            <div class="chat-input-wrap">
                <div class="chat-reply-preview" id="chatReplyPreview" style="display:none;">
                    <span class="chat-reply-preview-sender" id="chatReplySender"></span>
                    <span class="chat-reply-preview-text" id="chatReplyText"></span>
                    <span class="chat-reply-preview-close" onclick="cancelReply()">&times;</span>
                </div>
                <div class="chat-input-row" style="position:relative;">
                    <div class="chat-emoji-picker" id="chatEmojiPicker">
                        <div class="chat-emoji-grid" id="chatEmojiGrid"></div>
                    </div>
                    <textarea id="chatInput" rows="1" placeholder="Type a message..." style="margin:0;" onkeydown="onChatKeydown(event)"></textarea>
                    <button class="chat-input-btn" onclick="toggleEmojiPicker()" title="Emoji">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                    </button>
                    <button class="chat-input-btn" onclick="document.getElementById('chatFileInput').click()" title="Attach file">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    </button>
                    <input type="file" id="chatFileInput" style="display:none;" onchange="uploadFile(this)" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.mp4,.webm,.mp3,.ogg,.zip,.rar,.csv,.txt,.dwg,.dxf">
                    <button class="chat-send-btn" onclick="sendMessage()" title="Send">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
var currentRoomId = <?= $activeRoomId ?: 0 ?>;
var userId = <?= $userId ?>;
var lastMessageId = <?= !empty($messages) ? (int)end($messages)['id'] : 0 ?>;
var replyToId = null;
var csrfToken = '<?= htmlspecialchars(csrf_token()) ?>';
var polling = null;
var typingTimeout = null;

function loadRoom(roomId) {
    if (roomId === currentRoomId) return;
    currentRoomId = roomId;
    lastMessageId = 0;
    replyToId = null;
    var replyPreview = document.getElementById('chatReplyPreview');
    if (replyPreview) {
        replyPreview.style.display = 'none';
    }

    var url = new URL(window.location.href);
    url.searchParams.set('room', roomId);
    window.location.href = url.toString();
}


if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', () => {
        scrollToBottom(false);
    });
}
function scrollToBottom() {
    var target = document.getElementById('chatScrollTarget');
    if (target) target.scrollIntoView({ behavior: 'auto' });
}

function onChatKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
    updateTyping();
}

function sendMessage() {
    var input = document.getElementById('chatInput');
    var text = input.value.trim();
    if (!text || !currentRoomId) return;
    input.disabled = true;

    var fd = new FormData();
    fd.append('action', 'send');
    fd.append('room_id', currentRoomId);
    fd.append('message', text);
    fd.append('_csrf', csrfToken);
    if (replyToId) fd.append('reply_to_id', replyToId);

    fetch('/api/chat?action=send', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.message) {
                appendMessage(data.message);
                input.value = '';
                cancelReply();
                scrollToBottom();
                lastMessageId = data.message.id;
            }
        })
        .catch(function() {})
        .finally(function() { input.disabled = false; input.focus(); });
}

function appendMessage(msg) {
    var container = document.getElementById('chatMessages');
    var isOwn = parseInt(msg.sender_id) === userId;
    var div = document.createElement('div');
    div.className = 'chat-msg ' + (isOwn ? 'own' : 'other');
    div.dataset.msgId = msg.id;
    div.dataset.senderId = msg.sender_id;

    var html = '';
    if (!isOwn) {
        html += '<div class="chat-msg-sender">' + escapeHtml(msg.sender_name) + ' <span class="chat-msg-role">' + escapeHtml(msg.sender_role || 'User') + '</span></div>';
    }
    if (msg.reply_to) {
        html += '<div class="chat-msg-reply-indicator" onclick="scrollToMsg(' + msg.reply_to.id + ')"><strong class="chat-msg-reply-sender">' + escapeHtml(msg.reply_to.sender_name) + '</strong> <span class="chat-msg-reply-text">' + escapeHtml((msg.reply_to.message || '').substring(0, 80)) + '</span></div>';
    }
    if (isOwn) {
        html += '<div class="chat-msg-sender"><span class="chat-msg-role">' + escapeHtml(msg.sender_role || 'User') + '</span> ' + escapeHtml(msg.sender_name) + '</div>';
    }
    html += '<div class="chat-msg-bubble">';
    if (msg.message) {
        html += '<div>' + escapeHtml(msg.message).replace(/\n/g, '<br>') + '</div>';
    }
    if (msg.files && msg.files.length) {
        html += '<div class="chat-files">';
        for (var i = 0; i < msg.files.length; i++) {
            var f = msg.files[i];
            var ext = f.file_name.split('.').pop().toLowerCase();
            var imgExts = ['jpg','jpeg','png','gif','webp'];
            if (imgExts.indexOf(ext) !== -1) {
                html += '<img src="/' + f.file_path + '" alt="' + escapeHtml(f.file_name) + '" onclick="previewPhoto(\'/' + escapeHtml(f.file_path) + '\')" loading="lazy">';
            } else {
                var size = f.file_size > 1048576 ? (f.file_size / 1048576).toFixed(1) + 'MB' : Math.round(f.file_size / 1024) + 'KB';
                html += '<a href="/' + f.file_path + '" target="_blank" class="chat-file"><span class="chat-file-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><span class="chat-file-name">' + escapeHtml(f.file_name) + '</span><span class="chat-file-size">' + size + '</span></a>';
            }
        }
        html += '</div>';
    }
    html += '</div>';
    html += '<div style="display:flex;align-items:center;gap:4px;margin-top:2px;"><span class="chat-msg-time">Just now</span>' + (isOwn ? '<span class="chat-msg-read">✓</span>' : '') + '</div>';
    html += '<div class="chat-msg-actions"><span class="chat-msg-action" onclick="replyToMessage(' + msg.id + ',\'' + escapeHtml(msg.sender_name).replace(/'/g, "\\'") + '\',\'' + escapeHtml((msg.message || '').substring(0, 50)).replace(/'/g, "\\'") + '\')">Reply</span></div>';
    div.innerHTML = html;
    container.insertBefore(div, document.getElementById('chatScrollTarget'));
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

function uploadFile(input) {
    if (!input.files || !input.files[0] || !currentRoomId) return;
    var file = input.files[0];
    var fd = new FormData();
    fd.append('action', 'upload');
    fd.append('room_id', currentRoomId);
    fd.append('file', file);
    fd.append('_csrf', csrfToken);
    if (replyToId) fd.append('reply_to_id', replyToId);

    fetch('/api/chat?action=upload', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.message) {
                appendMessage(data.message);
                scrollToBottom();
                lastMessageId = data.message.id;
                cancelReply();
            }
        })
        .catch(function() {})
        .finally(function() { input.value = ''; });
}

function replyToMessage(id, sender, text) {
    replyToId = id;
    document.getElementById('chatReplyPreview').style.display = 'flex';
    document.getElementById('chatReplySender').textContent = sender;
    document.getElementById('chatReplyText').textContent = text;
    document.getElementById('chatInput').focus();
}

function cancelReply() {
    replyToId = null;
    document.getElementById('chatReplyPreview').style.display = 'none';
}

function scrollToMsg(id) {
    var el = document.querySelector('.chat-msg[data-msg-id="' + id + '"]');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function toggleEmojiPicker() {
    document.getElementById('chatEmojiPicker').classList.toggle('show');
}

function insertEmoji(emoji) {
    var input = document.getElementById('chatInput');
    input.value += emoji;
    input.focus();
    document.getElementById('chatEmojiPicker').classList.remove('show');
}

var emojis = ['😀','😃','😄','😁','😅','😂','🤣','😊','😇','🙂','😉','😌','😍','🥰','😘','😗','😋','😛','😜','🤪','😝','🤑','🤗','🤭','🤫','🤔','🤐','🤨','😐','😑','😶','😏','😒','🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🤧','🥵','🥶','🥴','😵','🤯','🤠','🥳','😎','🤓','🧐','😕','😟','🙁','😮','😯','😲','😳','🥺','😢','😭','😤','😠','😡','🤬','😈','👿','💀','☠️','💩','🤡','👹','👺','👻','👽','👾','🤖','🎃','😺','😸','😹','😻','😼','😽','🙀','😿','😾','❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','👍','👎','👊','✊','🤛','🤜','👏','🙌','👐','🤲','🤝','🙏','✌️','🤞','🖕','🤟','🤘','👌','💪','🦵','🦶','👂','🦻','👃','🧠','🦷','🦴','👀','👁️','👅','👄','💋'];
(function() {
    var grid = document.getElementById('chatEmojiGrid');
    for (var i = 0; i < emojis.length; i++) {
        var span = document.createElement('span');
        span.className = 'chat-emoji-item';
        span.textContent = emojis[i];
        span.onclick = function() { insertEmoji(this.textContent); };
        grid.appendChild(span);
    }
})();

document.addEventListener('click', function(e) {
    var picker = document.getElementById('chatEmojiPicker');
    if (picker && !e.target.closest('.chat-emoji-picker') && !e.target.closest('[onclick*="toggleEmojiPicker"]')) {
        picker.classList.remove('show');
    }
});

// Polling
function startPolling() {
    if (polling) clearInterval(polling);
    polling = setInterval(function() {
        if (!currentRoomId) return;
        var url = '/api/chat?action=fetch&room_id=' + currentRoomId + '&after=' + lastMessageId;
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.messages && data.messages.length) {
                    for (var i = 0; i < data.messages.length; i++) {
                        var m = data.messages[i];
                        if (parseInt(m.id) > lastMessageId) {
                            appendMessage(m);
                            lastMessageId = parseInt(m.id);
                        }
                    }
                    markRoomRead();
                    if (data.messages.length) updateUnreadCounts();
                }
                checkTyping();
            })
            .catch(function() {});
    }, 3000);
}

function updateUnreadCounts() {
    fetch('/api/chat?action=poll&room_id=' + currentRoomId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.rooms) {
                data.rooms.forEach(function(r) {
                    var el = document.querySelector('.chat-room-item[data-room-id="' + r.room_id + '"]');
                    if (el) {
                        var badge = el.querySelector('.chat-room-unread');
                        if (r.unread > 0 && parseInt(r.room_id) !== currentRoomId) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'chat-room-unread';
                                el.querySelector('.chat-room-meta').insertBefore(badge, el.querySelector('.chat-room-meta').firstChild);
                            }
                            badge.textContent = r.unread > 99 ? '99+' : r.unread;
                        } else if (badge) {
                            badge.remove();
                        }
                    }
                });
            }
        })
        .catch(function() {});
}

function markRoomRead() {
    var fd = new FormData();
    fd.append('action', 'mark_read');
    fd.append('room_id', currentRoomId);
    fetch('/api/chat?action=mark_read', { method: 'POST', body: fd }).catch(function() {});
}

function updateTyping() {
    if (!currentRoomId) return;
    if (typingTimeout) clearTimeout(typingTimeout);
    typingTimeout = setTimeout(function() {
        var fd = new FormData();
        fd.append('action', 'typing');
        fd.append('room_id', currentRoomId);
        fetch('/api/chat?action=typing', { method: 'POST', body: fd }).catch(function() {});
    }, 300);
}

function checkTyping() {
    if (!currentRoomId) return;
    fetch('/api/chat?action=get_typing&room_id=' + currentRoomId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var el = document.getElementById('chatTyping');
            if (data && data.length) {
                var names = data.map(function(u) { return u.user_name; }).join(', ');
                el.innerHTML = names + ' typing<span class="chat-typing-dots"><span></span><span></span><span></span></span>';
            } else {
                el.innerHTML = '';
            }
        })
        .catch(function() {});
}

function togglePinned() {
    var el = document.querySelector('.chat-pinned');
    if (el) {
        var msgs = document.getElementById('chatMessages');
        var pinnedMsgs = msgs.querySelectorAll('.chat-msg.is-pinned');
        // For simplicity, scroll to first pinned message
        msgs.scrollTop = 0;
    }
}

function loadOlder() {
    if (!currentRoomId) return;
    var firstMsg = document.querySelector('.chat-msg');
    if (!firstMsg) return;
    var beforeId = firstMsg.dataset.msgId;
    var url = '/api/chat?action=fetch&room_id=' + currentRoomId + '&before=' + beforeId;
    fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.messages && data.messages.length) {
                var loadMore = document.querySelector('.chat-load-more');
                for (var i = data.messages.length - 1; i >= 0; i--) {
                    var tempDiv = document.createElement('div');
                    var isOwn = parseInt(data.messages[i].sender_id) === userId;
                    tempDiv.innerHTML = buildMessageHtml(data.messages[i], isOwn);
                    var msgEl = tempDiv.firstElementChild;
                    if (loadMore) {
                        document.getElementById('chatMessages').insertBefore(msgEl, loadMore.nextSibling);
                    } else {
                        document.getElementById('chatMessages').insertBefore(msgEl, document.getElementById('chatMessages').firstChild);
                    }
                }
                if (!data.has_more) {
                    var lm = document.querySelector('.chat-load-more');
                    if (lm) lm.innerHTML = '';
                }
            }
        })
        .catch(function() {});
}

function buildMessageHtml(msg, isOwn) {
    var html = '<div class="chat-msg ' + (isOwn ? 'own' : 'other') + '" data-msg-id="' + msg.id + '" data-sender-id="' + msg.sender_id + '">';
    if (!isOwn) {
        html += '<div class="chat-msg-sender">' + escapeHtml(msg.sender_name) + ' <span class="chat-msg-role">' + escapeHtml(msg.sender_role || 'User') + '</span></div>';
    }
    if (msg.reply_to) {
        html += '<div class="chat-msg-reply-indicator" onclick="scrollToMsg(' + msg.reply_to.id + ')"><strong class="chat-msg-reply-sender">' + escapeHtml(msg.reply_to.sender_name) + '</strong> <span class="chat-msg-reply-text">' + escapeHtml((msg.reply_to.message || '').substring(0, 80)) + '</span></div>';
    }
    if (isOwn) {
        html += '<div class="chat-msg-sender"><span class="chat-msg-role">' + escapeHtml(msg.sender_role || 'User') + '</span> ' + escapeHtml(msg.sender_name) + '</div>';
    }
    html += '<div class="chat-msg-bubble">';
    if (msg.message) {
        html += '<div>' + escapeHtml(msg.message).replace(/\n/g, '<br>') + '</div>';
    }
    if (msg.files && msg.files.length) {
        html += '<div class="chat-files">';
        for (var i = 0; i < msg.files.length; i++) {
            var f = msg.files[i];
            var ext = f.file_name.split('.').pop().toLowerCase();
            var imgExts = ['jpg','jpeg','png','gif','webp'];
            if (imgExts.indexOf(ext) !== -1) {
                html += '<img src="/' + f.file_path + '" alt="' + escapeHtml(f.file_name) + '" onclick="previewPhoto(\'/' + escapeHtml(f.file_path) + '\')" loading="lazy">';
            } else {
                var size = f.file_size > 1048576 ? (f.file_size / 1048576).toFixed(1) + 'MB' : Math.round(f.file_size / 1024) + 'KB';
                html += '<a href="/' + f.file_path + '" target="_blank" class="chat-file"><span class="chat-file-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><span class="chat-file-name">' + escapeHtml(f.file_name) + '</span><span class="chat-file-size">' + size + '</span></a>';
            }
        }
        html += '</div>';
    }
    html += '</div>';
    html += '<div style="display:flex;align-items:center;gap:4px;margin-top:2px;"><span class="chat-msg-time">' + (msg.created_at ? timeAgo(msg.created_at) : 'Just now') + '</span>' + (isOwn ? '<span class="chat-msg-read">✓</span>' : '') + '</div>';
    html += '<div class="chat-msg-actions"><span class="chat-msg-action" onclick="replyToMessage(' + msg.id + ',\'' + escapeHtml(msg.sender_name).replace(/'/g, "\\'") + '\',\'' + escapeHtml((msg.message || '').substring(0, 50)).replace(/'/g, "\\'") + '\')">Reply</span></div>';
    html += '</div>';
    return html;
}

function timeAgo(dt) {
    var d = new Date(dt.replace(' ', 'T') + 'Z');
    var now = new Date();
    var diff = Math.floor((now - d) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// Search filter
document.getElementById('chatSearch')?.addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('.chat-room-item').forEach(function(el) {
        var name = (el.dataset.project || '').toLowerCase();
        el.style.display = name.indexOf(q) !== -1 ? '' : 'none';
    });
});

// Mark visible messages as read on scroll
document.getElementById('chatMessages')?.addEventListener('scroll', function() {
    var msgs = this.querySelectorAll('.chat-msg');
    msgs.forEach(function(el) {
        var rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight - 100 && rect.bottom > 0) {
            var mid = el.dataset.msgId;
            if (mid) {
                var fd = new FormData();
                fd.append('action', 'mark_read');
                fd.append('message_id', mid);
                fetch('/api/chat?action=mark_read', { method: 'POST', body: fd }).catch(function() {});
            }
        }
    });
});

// Notification sound
function playNotification() {
    try {
        var ctx = new (window.AudioContext || window.webkitAudioContext)();
        var osc = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 800;
        osc.type = 'sine';
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.2);
    } catch(e) {}
}

function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

// Confirm before close with unsent message
window.addEventListener('beforeunload', function(e) {
    var input = document.getElementById('chatInput');
    if (input && input.value.trim()) {
        e.preventDefault();
        e.returnValue = 'You have unsent message.';
    }
});

// Start
if (currentRoomId) {
    scrollToBottom();
    startPolling();
    requestNotificationPermission();
}
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
    // Keyboard fix
    const composer = document.querySelector('.chat-composer');
    const input = document.querySelector('.chat-input');
    const messages = document.querySelector('.messages-scroll');
    const bottomNav = document.querySelector('.gh-bottom-nav');

    if (input && bottomNav && composer) {
        input.addEventListener('focus', () => {
            bottomNav.style.display = 'none';
            setTimeout(() => {
                if (messages) messages.scrollTop = messages.scrollHeight;
                if (window.visualViewport) {
                    document.body.style.height = window.visualViewport.height + 'px';
                }
            }, 300);
        });
        
        input.addEventListener('blur', () => {
            bottomNav.style.display = '';
            document.body.style.height = '';
        });
    }
});
</script>

<?php portal_footer(); ?>
