<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/../src/view.php';
require_once dirname(__DIR__) . '/../src/auth.php';

function gh_dashboard_header(array $data): void {
    gh_header($data);
    $user = current_user();
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    ?>
    <style>
    .gh-dashboard {
        display: flex;
        min-height: calc(100vh - 80px);
        background: #f8fafc;
        flex-direction: column;
    }
    @media (min-width: 768px) {
        .gh-dashboard {
            flex-direction: row;
        }
    }
    .gh-sidebar {
        width: 100%;
        background: #fff;
        border-right: 1px solid #e2e8f0;
        padding: 1rem 0;
        flex-shrink: 0;
    }
    @media (min-width: 768px) {
        .gh-sidebar {
            width: 250px;
            padding: 2rem 0;
        }
    }
    .gh-sidebar-nav {
        display: flex;
        flex-direction: column;
    }
    .gh-sidebar-link {
        padding: 0.75rem 2rem;
        color: #475569;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }
    .gh-sidebar-link:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .gh-sidebar-link.is-active {
        color: var(--gh-green-700);
        background: #f0fdf4;
        border-right: 3px solid var(--gh-green-700);
    }
    .gh-dashboard-content {
        flex: 1;
        padding: 1rem;
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
    }
    @media (min-width: 768px) {
        .gh-dashboard-content {
            padding: 2rem;
        }
    }
    /* Simple UI components */
    .d-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #e2e8f0;
    }
    .d-title {
        font-family: var(--font-display);
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #0f172a;
    }
    .d-btn {
        padding: 0.5rem 1rem;
        background: var(--gh-green-700);
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
    }
    .d-btn:hover {
        background: var(--gh-green-800);
    }
    .d-input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        margin-top: 0.25rem;
        margin-bottom: 1rem;
    }
    </style>
    <div class="gh-dashboard">
        <aside class="gh-sidebar">
            <nav class="gh-sidebar-nav">
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                    <a href="/admin" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'admin-dashboard' ? 'is-active' : '' ?>">Overview</a>
                    <a href="/admin/projects" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'admin-projects' ? 'is-active' : '' ?>">Manage Projects</a>
                    <a href="/dashboard/project-chats" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'project-chats' ? 'is-active' : '' ?>">Project Chats</a>
                    <a href="/chat" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'admin-chat' ? 'is-active' : '' ?>">Team Chat</a>
                <?php else: ?>
                    <a href="/dashboard" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'overview' ? 'is-active' : '' ?>">Overview</a>
                    <a href="/dashboard/progress" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'progress' ? 'is-active' : '' ?>">Timeline</a>
                    <a href="/dashboard/gallery" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'gallery' ? 'is-active' : '' ?>">Gallery</a>
                    <a href="/dashboard/documents" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'documents' ? 'is-active' : '' ?>">Documents</a>
                    <a href="/dashboard/payments" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'payments' ? 'is-active' : '' ?>">Payments</a>
                    <a href="/dashboard/settings" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'settings' ? 'is-active' : '' ?>">Settings</a>
                    <a href="/dashboard/suggestions" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'suggestions' ? 'is-active' : '' ?>">Suggestions</a>
                    <a href="/dashboard/project-chats" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'project-chats' ? 'is-active' : '' ?>">Project Chat</a>
                    <a href="/chat" class="gh-sidebar-link <?= ($data['tab'] ?? '') === 'chat' ? 'is-active' : '' ?>">Team Chat</a>
                <?php endif; ?>
            </nav>
        </aside>
        <div class="gh-dashboard-content">
    <?php
}

function gh_dashboard_footer(): void {
    ?>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', async () => {
        if (window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.PushNotifications) {
            const PushNotifications = window.Capacitor.Plugins.PushNotifications;
            let permStatus = await PushNotifications.checkPermissions();

            if (permStatus.receive === 'prompt') {
                permStatus = await PushNotifications.requestPermissions();
            }

            if (permStatus.receive !== 'granted') {
                console.log('User denied push notification permissions');
                return;
            }

            await PushNotifications.createChannel({
                id: 'high_importance_channel',
                name: 'High Importance Notifications',
                description: 'High priority notifications for chat messages and updates',
                importance: 5,
                visibility: 1,
                sound: 'default',
                vibration: true,
                lights: true,
                lightColor: '#166534'
            }).catch(console.error);

            await PushNotifications.register();

            PushNotifications.addListener('registration', (token) => {
                console.log('Push registration success, token: ' + token.value);
                fetch('/api/dashboard-action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        'action': 'save_fcm_token',
                        'token': token.value,
                        'fcm_token': token.value
                    })
                }).catch(console.error);

                fetch('/api/portal', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        'action': 'register_fcm_token',
                        'fcm_token': token.value,
                        'token': token.value
                    })
                }).catch(console.error);
            });

            PushNotifications.addListener('pushNotificationReceived', (notification) => {
                console.log('Push notification received:', notification);
                if (navigator.vibrate) navigator.vibrate([120, 80, 120]);
            });

            PushNotifications.addListener('pushNotificationActionPerformed', (action) => {
                const notif = action.notification || {};
                const data = notif.data || notif.additionalData || {};
                const link = data.link || '/dashboard/project-chats';
                if (link) {
                    window.location.href = link;
                }
            });

            PushNotifications.addListener('registrationError', (error) => {
                console.error('Error on registration: ' + JSON.stringify(error));
            });
        }
    });
    </script>
    <?php
    gh_footer();
}
