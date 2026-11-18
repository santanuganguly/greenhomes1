<?php
declare(strict_types=1);

require_once __DIR__ . '/dashboard-layout.php';

$user = current_user();
if (!$user) {
    header('Location: /login');
    exit;
}

$role = $user['role'] ?? '';
$tab = ($role === 'admin' || $role === 'super_admin') ? 'admin-chat' : 'project-chats'; // Might change based on layout
gh_dashboard_header(['title' => 'Project Chats', 'tab' => 'project-chats']);
?>

<?php
$db = db();
require_once __DIR__ . '/../../src/chat.php';
$userId = (int)$user['id'];
$rooms = chat_get_user_rooms($userId);
$activeRoomId = !empty($_GET['room']) ? (int)$_GET['room'] : 0;
$activeRoom = null;
$messages = [];
$pinned = [];
$memberCount = 0;
if ($activeRoomId) {
    $ar = $db->prepare('SELECT project_id FROM project_chat_rooms WHERE id = ?');
    $ar->execute([$activeRoomId]);
    $roomData = $ar->fetch();
    if ($roomData && chat_can_access($activeRoomId, $userId)) {
        $activeRoom = chat_get_room((int)$roomData['project_id']);
        $messages = chat_get_messages($activeRoomId, $userId, 0, 50);
        $pinned = chat_get_pinned($activeRoomId);
        chat_mark_room_read($activeRoomId, $userId);
        $mc = $db->prepare('SELECT COUNT(*) FROM project_chat_members WHERE room_id = ?');
        $mc->execute([$activeRoomId]);
        $memberCount = (int)$mc->fetchColumn();
    }
}
?>
<style>
:root {
    --gh-chat-bg: #EFEAE2; /* WhatsApp light theme bg fallback, we will use dark */
    --gh-chat-bg-dark: #0b141a;
    --gh-chat-primary: #00a884;
    --gh-chat-primary-hover: #008f6f;
    --gh-chat-header: #202c33;
    --gh-chat-msg-own: #005c4b;
    --gh-chat-msg-other: #202c33;
    --gh-chat-text-main: #e9edef;
    --gh-chat-text-muted: #8696a0;
    --gh-chat-border: #222d34;
}

/* =========================================
   MOBILE-FIRST WHATSAPP-STYLE ARCHITECTURE
========================================= */
body, .chat-app, .chat-layout {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
}

/* Base app container */
.app {
    /* Safe areas for iPhone/Android */
    height: 100dvh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--gh-chat-bg-dark);
    color: var(--gh-chat-text-main);
    width: 100%;
}

/* Chat Layout Wrappers */
.chat-layout {
    display: flex;
    flex-direction: column;
    height: 100dvh; /* Pure mobile height */
    width: 100%;
    overflow: hidden;
    background: var(--gh-chat-bg-dark);
    position: relative;
    border: none;
    border-radius: 0;
}

/* Hide sidebar by default on mobile when in a room */
.chat-layout.has-active-room .chat-sidebar { display: none !important; }
.chat-layout:not(.has-active-room) .chat-sidebar { display: flex !important; flex-direction: column; height: 100%; width: 100%; }
.chat-layout:not(.has-active-room) .chat-main { display: none !important; }

.chat-sidebar-search { padding: 10px; background: var(--gh-chat-header); }
.chat-sidebar-search input { width: 100%; padding: 8px 12px; border: none; border-radius: 8px; background: #2a3942; color: #fff; outline: none; }
.chat-room-list { flex: 1; overflow-y: auto; background: var(--gh-chat-bg-dark); }
.chat-room-item { display: flex; align-items: center; padding: 12px 15px; border-bottom: 1px solid var(--gh-chat-border); gap: 12px; }
.chat-room-avatar { width: 48px; height: 48px; border-radius: 50%; background: var(--gh-chat-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: bold; flex-shrink: 0; }
.chat-room-info { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; }
.chat-room-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 2px; }
.chat-room-name { font-weight: 500; font-size: 1.05rem; color: var(--gh-chat-text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.chat-room-time { font-size: 0.75rem; color: var(--gh-chat-text-muted); flex-shrink: 0; }
.chat-room-last-row { display: flex; justify-content: space-between; align-items: center; }
.chat-room-last { font-size: 0.85rem; color: var(--gh-chat-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.chat-room-unread { background: var(--gh-chat-primary); color: #111b21; font-size: 0.75rem; font-weight: 600; min-width: 20px; height: 20px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 6px; }

/* Main Chat Area */
.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 100dvh;
    min-height: 0; /* Important for flex child scrolling */
}

/* App Header */
.app-header {
    flex-shrink: 0;
    height: 60px;
    background: var(--gh-chat-header);
    display: flex;
    align-items: center;
    padding: 0 10px;
    gap: 10px;
    z-index: 10;
    padding-top: env(safe-area-inset-top);
}
.chat-back-btn { color: var(--gh-chat-text-main); padding: 5px; display: flex; align-items: center; text-decoration: none; cursor: pointer; }
.chat-header-info { flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; }
.chat-header-title { font-size: 1.1rem; font-weight: 500; color: var(--gh-chat-text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0; }
.chat-header-sub { font-size: 0.8rem; color: var(--gh-chat-text-muted); }
.chat-header-actions { display: flex; gap: 15px; color: var(--gh-chat-text-main); align-items: center; padding-right: 5px; }

/* Scrollable Messages */
.chat-content {
    flex: 1;
    min-height: 0;
    position: relative;
    background: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
    background-color: var(--gh-chat-bg-dark);
    display: flex;
    flex-direction: column;
}
.messages-scroll {
    flex: 1;
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 15px 10px;
    display: flex;
    flex-direction: column;
    gap: 4px; /* Default gap between messages */
}

/* Date Separator */
.chat-date-separator {
    align-self: center;
    background: rgba(32, 44, 51, 0.9);
    color: var(--gh-chat-text-muted);
    font-size: 0.75rem;
    font-weight: 500;
    padding: 3px 10px; font-size: 0.7rem;
    border-radius: 8px;
    margin: 8px 0;
    text-transform: uppercase;
    box-shadow: 0 1px 0.5px rgba(11,20,26,.13);
}

/* Message Bubbles */
.chat-msg {
    max-width: 80%;
    display: flex;
    flex-direction: column;
    position: relative;
}
.chat-msg.own { align-self: flex-end !important; margin-left: auto !important; margin-right: 0 !important; }
.chat-msg.other { align-self: flex-start !important; margin-right: auto !important; margin-left: 0 !important; }

.chat-msg-bubble {
    padding: 6px 8px 18px 10px;
    border-radius: 8px;
    font-size: 0.95rem;
    position: relative;
    box-shadow: 0 1px 0.5px rgba(11,20,26,.13);
    word-wrap: break-word;
    line-height: 1.35;
}
.chat-msg.own .chat-msg-bubble {
    background: var(--gh-chat-msg-own);
    color: var(--gh-chat-text-main);
    border-top-right-radius: 0;
}
.chat-msg.other .chat-msg-bubble {
    background: var(--gh-chat-msg-other);
    color: var(--gh-chat-text-main);
    border-top-left-radius: 0;
}

/* Compact Sender Info (Only shown on first message of group) */
.chat-msg-sender-info {
    font-size: 0.8rem;
    font-weight: 500;
    color: #e59fb2; /* Distinct color for names like WhatsApp group chats */
    margin-bottom: 2px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.chat-msg-role {
    font-size: 0.65rem;
    padding: 1px 4px;
    border-radius: 4px;
    background: rgba(255,255,255,0.1);
    color: var(--gh-chat-text-muted);
    font-weight: normal;
}

/* Message Meta (Time and Ticks) */

.chat-msg-meta {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 4px;
    font-size: 0.68rem;
    color: rgba(255,255,255,0.6);
    margin-top: -2px;
    margin-bottom: -2px;
    float: right;
    margin-left: 12px;
    padding-top: 8px;
}
.chat-msg-time {
    font-size: 0.68rem;
    color: rgba(255,255,255,0.6);
}
.chat-msg-status svg {
    width: 16px;
    height: 16px;
}
/* Tick colors */
.status-clock { color: var(--gh-chat-text-muted); }
.status-sent { color: var(--gh-chat-text-muted); }
.status-delivered { color: var(--gh-chat-text-muted); }
.status-read { color: #53bdeb; }

/* Composer Area */
.message-composer {
    flex-shrink: 0;
    background: var(--gh-chat-header);
    padding: 8px 10px;
    padding-bottom: calc(8px + env(safe-area-inset-bottom));
    display: flex;
    align-items: flex-end;
    gap: 8px;
    z-index: 10;
}
.composer-input-wrap {
    flex: 1;
    background: #2a3942;
    border-radius: 24px;
    display: flex;
    align-items: flex-end;
    padding: 8px 12px;
    min-height: 44px;
}
.composer-input-wrap textarea {
    flex: 1;
    background: transparent;
    border: none;
    color: var(--gh-chat-text-main);
    font-size: 1rem;
    resize: none;
    padding: 0;
    margin: 0;
    max-height: 120px;
    outline: none;
    line-height: 1.4;
    overflow-y: auto;
}
.composer-input-wrap textarea::placeholder { color: var(--gh-chat-text-muted); }
.composer-btn {
    background: transparent;
    border: none;
    color: var(--gh-chat-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4px;
    cursor: pointer;
}
.composer-send-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--gh-chat-primary);
    color: #111b21;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    flex-shrink: 0;
    cursor: pointer;
    transition: transform 0.1s;
}
.composer-send-btn:active { transform: scale(0.95); }
.composer-send-btn svg { width: 20px; height: 20px; margin-left: 2px; }

/* Typing indicator overlay */
.chat-typing-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(32, 44, 51, 0.85);
    padding: 3px 10px; font-size: 0.7rem;
    font-size: 0.8rem;
    color: var(--gh-chat-primary);
    z-index: 5;
    transform: translateY(100%);
    transition: transform 0.2s;
}
.chat-typing-overlay.show { transform: translateY(0); }

/* =========================================
   DESKTOP OVERRIDES
========================================= */
@media (min-width: 769px) {
    /* Hide the mobile elements that shouldn't appear on desktop */
    .gh-rail, .portal-sidebar, .p-sidebar { display: none !important; }
    body, .gh-page, .gh-wrap, .p-main, .gh-content, .portal-main, .portal-layout { 
        padding: 0 !important; margin: 0 !important; max-width: none !important; overflow: hidden !important; 
    }
    
    .chat-layout {
        height: 100vh !important;
        width: 100vw !important;
        position: fixed !important;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: 999999 !important;
        flex-direction: row !important;
    }
    
    .chat-layout:not(.has-active-room) .chat-main { display: flex !important; }
    .chat-sidebar { display: flex !important; width: 350px !important; max-width: 35% !important; border-right: 1px solid var(--gh-chat-border) !important; }
    .chat-main { display: flex !important; flex: 1 !important; }
    
    .chat-back-btn { display: none !important; } /* Hide back button on desktop since sidebar is always visible */
    
    /* Make message width max 60% on large screens */
    .chat-msg { max-width: 60%; }
}

    .messages-scroll {
        display: flex !important;
        flex-direction: column !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
    }
    
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
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--gh-chat-text-muted);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:64px;height:64px;opacity:0.3;margin-bottom:15px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <h3 style="font-weight:400;">WhatsApp for Green Homes</h3>
                <p style="font-size:0.9rem;">Select a project chat from the sidebar</p>
            </div>
            <?php else: ?>
            
            <!-- APP HEADER -->
            <header class="app-header">
                <a href="<?= explode('?', $_SERVER['REQUEST_URI'])[0] ?>" class="chat-back-btn">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
                <div class="chat-room-avatar" style="width:40px;height:40px;font-size:1rem;"><?= htmlspecialchars(mb_strtoupper(mb_substr($activeRoom['project_name'], 0, 1))) ?></div>
                <div class="chat-header-info">
                    <div class="chat-header-title"><?= htmlspecialchars($activeRoom['project_name']) ?></div>
                    <div class="chat-header-sub"><?= $memberCount ?> members · <?= htmlspecialchars($activeRoom['project_status'] ?? 'active') ?></div>
                </div>
                <div class="chat-header-actions">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                </div>
            </header>

            <!-- CHAT CONTENT -->
            <div class="chat-content">
                <div class="messages-scroll" id="chatMessages">
                    <?php 
                    $lastDate = '';
                    $lastSenderId = null;
                    $lastTime = 0;
                    
                    if (count($messages) >= 50): ?>
                    <div style="text-align:center;padding:10px;"><a onclick="loadOlder()" style="color:var(--gh-chat-primary);cursor:pointer;font-size:0.85rem;">Load older messages</a></div>
                    <?php endif; 
                    
                    foreach ($messages as $m):
                        $isOwn = (int)$m['sender_id'] === $userId;
                        $files = chat_get_files((int)$m['id']);
                        
                        // Date grouping
                        $msgDate = date('Y-m-d', strtotime($m['created_at']));
                        $msgTime = strtotime($m['created_at']);
                        $isNewDate = $msgDate !== $lastDate;
                        
                        // Sender grouping (if same sender within 5 minutes)
                        $isSameSender = !$isNewDate && ($lastSenderId === (int)$m['sender_id']) && (($msgTime - $lastTime) < 300);
                        
                        $lastDate = $msgDate;
                        $lastSenderId = (int)$m['sender_id'];
                        $lastTime = $msgTime;
                        
                        $timeStr = date('h:i A', strtotime($m['created_at']));
                        
                        // Status Ticks for Own Messages
                        $ticksHtml = '';
                        if ($isOwn) {
                            $read = (int)($m['read_count'] ?? 0);
                            $delivered = (int)($m['delivered_count'] ?? 0);
                            $totalMembers = (int)($m['total_members'] ?? 1);
                            
                            if ($read >= $totalMembers) {
                                // Double Blue
                                $ticksHtml = '<svg class="status-read" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L7 17l-5-5"/><path d="M22 10l-5 5-1.5-1.5"/></svg>';
                            } elseif ($delivered > 0 || $read > 0) {
                                // Double Grey
                                $ticksHtml = '<svg class="status-delivered" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L7 17l-5-5"/><path d="M22 10l-5 5-1.5-1.5"/></svg>';
                            } else {
                                // Single Grey (Sent)
                                $ticksHtml = '<svg class="status-sent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
                            }
                        }
                    ?>
                    
                    <?php if ($isNewDate): ?>
                    <div class="chat-date-separator">
                        <?= date('Y-m-d') === $msgDate ? 'TODAY' : strtoupper(date('d M Y', strtotime($m['created_at']))) ?>
                    </div>
                    <?php endif; ?>

                    <div class="chat-msg <?= $isOwn ? 'own' : 'other' ?>" data-msg-id="<?= (int)$m['id'] ?>" data-sender-id="<?= (int)$m['sender_id'] ?>" style="margin-top: <?= $isSameSender ? '2px' : '6px' ?>;">
                        <div class="chat-msg-bubble">
                            <?php if (!$isOwn && !$isSameSender): ?>
                            <div class="chat-msg-sender-info">
                                <?= htmlspecialchars($m['sender_name']) ?>
                                <span class="chat-msg-role"><?= htmlspecialchars(get_role_label($m['sender_role'])) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($m['reply_to_id'])): 
                                $r = $db->prepare("SELECT m.id, m.message, u.name AS sender_name FROM project_chat_messages m JOIN users u ON m.sender_id = u.id WHERE m.id = ?");
                                $r->execute([$m['reply_to_id']]);
                                $replyTo = $r->fetch();
                                if ($replyTo):
                            ?>
                            <div style="background: rgba(0,0,0,0.1); border-left: 4px solid var(--gh-chat-primary); padding: 4px 8px; border-radius: 4px; margin-bottom: 6px; font-size: 0.85rem; cursor: pointer;" onclick="scrollToMsg(<?= (int)$replyTo['id'] ?>)">
                                <div style="font-weight: 500; color: var(--gh-chat-primary); margin-bottom: 2px;"><?= htmlspecialchars($replyTo['sender_name']) ?></div>
                                <div style="color: var(--gh-chat-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;"><?= htmlspecialchars(mb_strimwidth($replyTo['message'], 0, 50, '...')) ?></div>
                            </div>
                            <?php endif; endif; ?>
                            
                            <?php if (!empty($m['message'])): ?>
                            <span style="white-space: pre-wrap; word-break: break-word;"><?= htmlspecialchars($m['message']) ?></span>
                            <?php endif; ?>
                            
                            <?php if (!empty($files)): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:6px;">
                                <?php foreach ($files as $f):
                                    $ext = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION));
                                    $imgExts = ['jpg','jpeg','png','gif','webp'];
                                    $isImg = in_array($ext, $imgExts, true);
                                ?>
                                    <?php if ($isImg): ?>
                                    <img src="/<?= htmlspecialchars($f['file_path']) ?>" style="max-width:200px;max-height:200px;border-radius:6px;" onclick="previewPhoto('/<?= htmlspecialchars($f['file_path']) ?>')">
                                    <?php else: ?>
                                    <a href="/<?= htmlspecialchars($f['file_path']) ?>" target="_blank" style="display:flex;align-items:center;gap:6px;background:rgba(0,0,0,0.2);padding:8px;border-radius:6px;color:var(--gh-chat-text-main);text-decoration:none;">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <span style="max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:0.85rem;"><?= htmlspecialchars($f['file_name']) ?></span>
                                    </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <span class="chat-msg-meta">
                                <span class="chat-msg-time"><?= $timeStr ?></span>
                                <?php if ($isOwn): ?>
                                <span class="chat-msg-status" id="status-<?= (int)$m['id'] ?>">
                                    <?= $ticksHtml ?>
                                </span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div id="chatScrollTarget"></div>
                </div>
                <div class="chat-typing-overlay" id="chatTyping">Someone is typing...</div>
            </div>

            <!-- COMPOSER -->
            <footer class="message-composer">
                <button class="composer-btn">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                </button>
                
                <input type="file" id="chatFileInput" style="display:none" onchange="handleFileSelect(event)">
                <div id="filePreviewContainer" style="display:none; position:absolute; bottom:100%; left:0; width:100%; background:var(--gh-chat-header); padding:10px; border-top:1px solid var(--gh-chat-border); z-index:9; align-items:center; justify-content:space-between;">
                    <span id="filePreviewName" style="color:var(--gh-chat-text-main); font-size:0.85rem; max-width:80%; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></span>
                    <button onclick="clearFilePreview()" style="background:none; border:none; color:var(--gh-chat-text-muted); padding:4px; cursor:pointer;"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                </div>
                <div class="composer-input-wrap">
                    <textarea id="chatInput" placeholder="Message" rows="1" oninput="autoResize(this)" onfocus="handleInputFocus()" onblur="handleInputBlur()"></textarea>
                    <button class="composer-btn" style="margin-right:4px;" onclick="document.getElementById('chatFileInput').click()">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    </button>
                    <button class="composer-btn">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </button>
                </div>
                <button class="composer-send-btn" id="sendBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:0;"><path d="M22 2L11 13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </footer>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
const roomId = <?= $activeRoomId ?>;
const userId = <?= $userId ?>;
let lastMessageId = <?= !empty($messages) ? $messages[count($messages)-1]['id'] : 0 ?>;
const messagesDiv = document.getElementById('chatMessages');
const input = document.getElementById('chatInput');
const sendBtn = document.getElementById('sendBtn');
let isTyping = false;
let typingTimeout = null;
let pollInterval = null;

// Auto-resize textarea
function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = (el.scrollHeight) + 'px';
    if(el.value.trim().length > 0) {
        sendBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left: -2px;"><path d="M22 2L11 13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';
    } else {
        sendBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>';
    }
}

// Keyboard handling
function handleInputFocus() {
    const bottomNav = document.querySelector('.gh-bottom-nav');
    if(bottomNav) bottomNav.style.display = 'none';
    setTimeout(() => scrollToBottom(true), 150);
}
function handleInputBlur() {
    const bottomNav = document.querySelector('.gh-bottom-nav');
    if(bottomNav) {
        bottomNav.style.display = '';
    }
}


if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', () => {
        scrollToBottom(false);
    });
}
function scrollToBottom(smooth = false) {
    if (!messagesDiv) return;
    messagesDiv.scrollTo({
        top: messagesDiv.scrollHeight,
        behavior: smooth ? 'smooth' : 'auto'
    });
}

// Generate unique ID for offline sync
function generateClientId() {
    return Date.now().toString() + Math.random().toString(36).substr(2, 5);
}

function getFormattedTime() {
    const d = new Date();
    let h = d.getHours();
    let m = d.getMinutes();
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12;
    h = h ? h : 12;
    m = m < 10 ? '0'+m : m;
    return h + ':' + m + ' ' + ampm;
}

// OPTIMISTIC SENDING
function sendMessage() {
    if (!input) return;
    const msg = input.value.trim();
    if (!msg && !window.selectedFile) return;

    // Reset input
    input.value = '';
    autoResize(input);
    input.focus(); // Keep keyboard open

    const clientId = generateClientId();
    const tempId = 'temp-' + clientId;

    // Append to UI immediately
    const html = `<div class="chat-msg own" id="${tempId}" style="margin-top: 6px;">
        <div class="chat-msg-bubble">
            <span style="white-space: pre-wrap; word-break: break-word;">${msg.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</span>
            <span class="chat-msg-meta">
                <span class="chat-msg-time">${getFormattedTime()}</span>
                <span class="chat-msg-status" id="status-${tempId}">
                    <svg class="status-clock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
            </span>
        </div>
    </div>`;
    
    document.getElementById('chatScrollTarget').insertAdjacentHTML('beforebegin', html);
    scrollToBottom(true);

    const payload = {
        _csrf: '<?= $_SESSION['csrf_token'] ?? '' ?>',
        action: 'send',
        room_id: roomId,
        message: msg,
        client_msg_id: clientId
    };

    if (!navigator.onLine) {
        // Offline Queue
        let queue = JSON.parse(localStorage.getItem('gh_chat_queue') || '[]');
        queue.push(payload);
        localStorage.setItem('gh_chat_queue', JSON.stringify(queue));
        return; // Will be synced when online
    }

    
    let fileToSend = window.selectedFile;
    if (fileToSend) {
        clearFilePreview(); // Reset UI
    }
    sendPayload(payload, tempId, fileToSend);
    
}


// File upload handling
window.selectedFile = null;

function handleFileSelect(e) {
    if (e.target.files.length > 0) {
        window.selectedFile = e.target.files[0];
        document.getElementById('filePreviewName').innerText = window.selectedFile.name;
        document.getElementById('filePreviewContainer').style.display = 'flex';
        sendBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left: -2px;"><path d="M22 2L11 13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';
    }
}

function clearFilePreview() {
    window.selectedFile = null;
    document.getElementById('chatFileInput').value = '';
    document.getElementById('filePreviewContainer').style.display = 'none';
    autoResize(document.getElementById('chatInput')); // Reset icon if text is empty
}

// Modify sendPayload to support FormData for files
function sendPayload(payload, tempId, file = null) {
    let bodyData;
    let headers = {};
    
    if (file) {
        bodyData = new FormData();
        for (const key in payload) { bodyData.append(key, payload[key]); }
        bodyData.append('file', file);
        payload.action = 'upload'; // Important!
        bodyData.set('action', 'upload');
    } else {
        bodyData = new URLSearchParams();
        for (const key in payload) { bodyData.append(key, payload[key]); }
        headers['Content-Type'] = 'application/x-www-form-urlencoded';
    }

    const apiUrl = '/api/project-chats.php';

    fetch(apiUrl, {
        method: 'POST',
        headers: headers,
        body: bodyData
    })
    .then(res => res.json())
    .then(data => {
        if (data.message && tempId) {
            const msgEl = document.getElementById(tempId);
            if(msgEl) {
                msgEl.id = 'msg-' + data.message.id;
                msgEl.setAttribute('data-msg-id', data.message.id);
                document.getElementById('status-' + tempId).innerHTML = '<svg class="status-sent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';
                document.getElementById('status-' + tempId).id = 'status-' + data.message.id;
                
                // If it was a file, we should re-poll immediately to show the actual image/file preview properly
                if (file) {
                    setTimeout(() => fetchMessagesPoll(), 500);
                }
            }
        }
    })
    .catch(err => console.error('Send failed', err));
}

function fetchMessagesPoll() {
    if (!roomId) return;
    const apiUrl = '/api/project-chats.php';
    fetch(apiUrl + '?action=fetch&room_id=' + roomId + '&after=' + lastMessageId)
        .then(r => r.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(m => {
                    if(m.sender_id != userId) {
                        const html = `<div class="chat-msg other" style="margin-top: 6px;">
                            <div class="chat-msg-bubble">
                                <div class="chat-msg-sender-info">${m.sender_name} <span class="chat-msg-role">${m.sender_role}</span></div>
                                <span style="white-space: pre-wrap; word-break: break-word;">${m.message.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</span>
                                <span class="chat-msg-meta"><span class="chat-msg-time">${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span></span>
                            </div>
                        </div>`;
                        document.getElementById('chatScrollTarget').insertAdjacentHTML('beforebegin', html);
                        lastMessageId = Math.max(lastMessageId, parseInt(m.id));
                    }
                });
                scrollToBottom(true);
            }
        }).catch(console.error);
}

// Handle sending without losing focus
if(sendBtn) {
    sendBtn.addEventListener('mousedown', function(e) {
        e.preventDefault(); // Prevents input from losing focus!
        sendMessage();
    });
    sendBtn.addEventListener('touchstart', function(e) {
        e.preventDefault(); 
        sendMessage();
    });
}

// Sync offline queue
window.addEventListener('online', () => {
    let queue = JSON.parse(localStorage.getItem('gh_chat_queue') || '[]');
    if (queue.length > 0) {
        queue.forEach(payload => {
            sendPayload(payload, 'temp-' + payload.client_msg_id);
        });
        localStorage.removeItem('gh_chat_queue');
    }
});

// Polling
function startPolling() {
    if (!roomId) return;
    pollInterval = setInterval(() => {
        fetch(`/api/project-chats.php?action=fetch&room_id=${roomId}&after=${lastMessageId}`)
            .then(r => r.json())
            .then(data => {
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(m => {
                        if(m.sender_id != userId) {
                            // Append new incoming msg
                            const html = `<div class="chat-msg other" style="margin-top: 6px;">
                                <div class="chat-msg-bubble">
                                    <div class="chat-msg-sender-info">
                                        ${m.sender_name} <span class="chat-msg-role">${m.sender_role}</span>
                                    </div>
                                    <span style="white-space: pre-wrap; word-break: break-word;">${m.message.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</span>
                                    <span class="chat-msg-meta"><span class="chat-msg-time">${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span></span>
                                </div>
                            </div>`;
                            document.getElementById('chatScrollTarget').insertAdjacentHTML('beforebegin', html);
                            lastMessageId = Math.max(lastMessageId, parseInt(m.id));
                        }
                    });
                    scrollToBottom(true);
                }
            })
            .catch(console.error);
    }, 3000);
}

if (roomId) {
    setTimeout(() => scrollToBottom(false), 100);
    startPolling();
}

function loadRoom(id) {
    window.location.href = '?room=' + id;
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
