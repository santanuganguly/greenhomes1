<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
if (!file_exists($root . '/src/config.php')) { $root = dirname(__DIR__, 3); }
require_once $root . '/src/view.php';
require_once $root . '/src/auth.php';
require_once $root . '/src/portal.php';
if (file_exists($root . '/src/chat.php')) { require_once $root . '/src/chat.php'; }

/* -----------------------------------------------------------------------
   Helper: store the current tab across header → footer
   ----------------------------------------------------------------------- */
function _gh_current_tab(?string $set = null): string {
    static $t = '';
    if ($set !== null) $t = $set;
    return $t;
}

/* -----------------------------------------------------------------------
   Helper: map tab slug → bottom-nav section
   ----------------------------------------------------------------------- */
function _gh_section(string $tab): string {
    static $map = [
        'overview'              => 'home',
        'admin-dashboard'       => 'home',
        'pm-dashboard'          => 'home',
        'eng-dashboard'         => 'home',

        'progress'              => 'projects',
        'admin-projects'        => 'projects',
        'pm-projects'           => 'projects',
        'eng-today'             => 'projects',

        'client-project-chats'  => 'chats',
        'admin-project-chats'   => 'chats',
        'pm-project-chats'      => 'chats',
        'eng-project-chats'     => 'chats',

        'weekly'                => 'updates',
        'admin-weekly'          => 'updates',
        'eng-weekly'            => 'updates',
        'pm-schedule'           => 'updates',
        'admin-notifications'   => 'updates',
    ];
    return $map[$tab] ?? 'more';
}

/* -----------------------------------------------------------------------
   Helper: build role-based nav tab definitions
   Returns array of ['label','icon','url','section','badge']
   ----------------------------------------------------------------------- */
function _gh_nav_tabs(string $role, int $unreadChat, int $unreadNotif): array {
    $chatSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
    $moreSvg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>';

    if (in_array($role, ['admin', 'super_admin'], true)) {
        return [
            ['label'=>'Home',     'section'=>'home',     'url'=>'/admin',              'badge'=>0,          'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
            ['label'=>'Projects', 'section'=>'projects', 'url'=>'/admin/projects',     'badge'=>0,          'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>'],
            ['label'=>'Chats',    'section'=>'chats',    'url'=>'/admin/project-chats','badge'=>$unreadChat,'icon'=>$chatSvg],
            ['label'=>'Updates',  'section'=>'updates',  'url'=>'/admin/weekly',       'badge'=>0,          'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>'],
            ['label'=>'More',     'section'=>'more',     'url'=>'#',                  'badge'=>0,          'icon'=>$moreSvg],
        ];
    } elseif ($role === 'head_engineer') {
        return [
            ['label'=>'Home',     'section'=>'home',     'url'=>'/pm',                'badge'=>0,          'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
            ['label'=>'Projects', 'section'=>'projects', 'url'=>'/pm/projects',       'badge'=>0,          'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>'],
            ['label'=>'Chats',    'section'=>'chats',    'url'=>'/pm/project-chats',  'badge'=>$unreadChat,'icon'=>$chatSvg],
            ['label'=>'Schedule', 'section'=>'updates',  'url'=>'/pm/schedule',       'badge'=>0,          'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>'],
            ['label'=>'More',     'section'=>'more',     'url'=>'#',                  'badge'=>0,          'icon'=>$moreSvg],
        ];
    } elseif ($role === 'site_engineer') {
        return [
            ['label'=>'Home',     'section'=>'home',     'url'=>'/engineer',                  'badge'=>0,          'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
            ['label'=>'Today',    'section'=>'projects', 'url'=>'/engineer/today',            'badge'=>0,          'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'],
            ['label'=>'Chats',    'section'=>'chats',    'url'=>'/engineer/project-chats',    'badge'=>$unreadChat,'icon'=>$chatSvg],
            ['label'=>'Updates',  'section'=>'updates',  'url'=>'/engineer/weekly',           'badge'=>0,          'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>'],
            ['label'=>'More',     'section'=>'more',     'url'=>'#',                          'badge'=>0,          'icon'=>$moreSvg],
        ];
    } else {
        // Client
        return [
            ['label'=>'Home',     'section'=>'home',     'url'=>'/dashboard',                 'badge'=>0,          'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
            ['label'=>'Project',  'section'=>'projects', 'url'=>'/dashboard/progress',        'badge'=>0,          'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>'],
            ['label'=>'Chat',     'section'=>'chats',    'url'=>'/dashboard/project-chats',   'badge'=>$unreadChat,'icon'=>$chatSvg],
            ['label'=>'Updates',  'section'=>'updates',  'url'=>'/dashboard/weekly',          'badge'=>$unreadNotif,'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>'],
            ['label'=>'More',     'section'=>'more',     'url'=>'#',                          'badge'=>0,          'icon'=>$moreSvg],
        ];
    }
}

/* -----------------------------------------------------------------------
   Helper: build role-based More Sheet items
   ----------------------------------------------------------------------- */
function _gh_more_items(string $role): array {
    $items = [];

    if (in_array($role, ['admin', 'super_admin'], true)) {
        $items[] = ['label'=>'Clients',      'url'=>'/admin/clients',       'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'];
        $items[] = ['label'=>'Payments',     'url'=>'/admin/payments',      'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 8h12"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 13h8.5l-5 8"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 13h3"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 13c6.667 0 6.667-10 0-10"/></svg>'];
        $items[] = ['label'=>'Documents',    'url'=>'/admin/documents',     'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'];
        $items[] = ['label'=>'Reports',      'url'=>'/admin/reports',       'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>'];
        $items[] = ['label'=>'Design Ideas', 'url'=>'/admin/design-ideas',  'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>'];
        $items[] = ['label'=>'Notifications','url'=>'/admin/notifications', 'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>'];
        $items[] = 'divider';
        $items[] = ['label'=>'Logout', 'url'=>'/logout', 'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>', 'danger'=>true];

    } elseif ($role === 'head_engineer') {
        $items[] = ['label'=>'Clients',      'url'=>'/pm/clients',      'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>'];
        $items[] = ['label'=>'Approvals',    'url'=>'/pm/approvals',    'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>'];
        $items[] = ['label'=>'Design Ideas', 'url'=>'/pm/design-ideas', 'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>'];
        $items[] = 'divider';
        $items[] = ['label'=>'Logout', 'url'=>'/logout', 'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>', 'danger'=>true];

    } elseif ($role === 'site_engineer') {
        $items[] = ['label'=>'Notes',        'url'=>'/engineer/notes',       'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>'];
        $items[] = ['label'=>'Materials',    'url'=>'/engineer/materials',   'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>'];
        $items[] = ['label'=>'Issues',       'url'=>'/engineer/issues',      'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'];
        $items[] = ['label'=>'Design Ideas', 'url'=>'/engineer/design-ideas','icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>'];
        $items[] = 'divider';
        $items[] = ['label'=>'Logout', 'url'=>'/logout', 'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>', 'danger'=>true];

    } else {
        // Client
        $items[] = ['label'=>'Gallery',      'url'=>'/dashboard/gallery',      'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>'];
        $items[] = ['label'=>'Before & After','url'=>'/dashboard/before-after', 'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>'];
        $items[] = ['label'=>'Payments',     'url'=>'/dashboard/payments',     'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 8h12"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 13h8.5l-5 8"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 13h3"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 13c6.667 0 6.667-10 0-10"/></svg>'];
        $items[] = ['label'=>'Documents',    'url'=>'/dashboard/documents',    'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'];
        $items[] = ['label'=>'Materials',    'url'=>'/dashboard/materials',    'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>'];
        $items[] = ['label'=>'Appointments', 'url'=>'/dashboard/appointments', 'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>'];
        $items[] = ['label'=>'Design Ideas', 'url'=>'/dashboard/chat',         'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>'];
        $items[] = ['label'=>'Settings',     'url'=>'/dashboard/settings',     'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>'];
        $items[] = ['label'=>'Feedback',     'url'=>'/dashboard/suggestions',  'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>'];
        $items[] = 'divider';
        $items[] = ['label'=>'Logout', 'url'=>'/logout', 'icon'=>'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>', 'danger'=>true];
    }

    return $items;
}

/* =======================================================================
   PORTAL HEADER
   ======================================================================= */
function portal_header(array $data = []): void {
    $user = current_user();
    if (!$user) { header('Location: /login'); exit; }

    $role            = $user['role'] ?? 'client';
    $unreadNotif     = get_unread_notification_count((int) $user['id']);
    $unreadChat      = function_exists('chat_get_unread_count') ? chat_get_unread_count((int) $user['id']) : 0;
    $title           = $data['title'] ?? 'Green Homes';
    $tab             = $data['tab']   ?? '';
    $activeSection   = _gh_section($tab);
    $tabs            = _gh_nav_tabs($role, $unreadChat, $unreadNotif);
    $isPrimaryTab    = in_array($activeSection, ['home', 'projects', 'chats', 'updates'], true);
    $initials        = mb_strtoupper(mb_substr(trim($user['name'] ?? 'U'), 0, 1));
    $notifUrl        = match(true) {
        in_array($role, ['admin','super_admin']) => '/admin/notifications',
        default => '/dashboard/weekly',
    };

    // Store tab for footer
    _gh_current_tab($tab);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-T8RKR9SR');</script>
<!-- End Google Tag Manager -->

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-5KNSV6G8DX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-5KNSV6G8DX');
</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= htmlspecialchars($title) ?> — Green Homes</title>
<link rel="icon" type="image/png" href="/assets/img/favicon.png">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/portal.css?v=wai4">
<style>
/* Enforce safe areas for standalone/installed apps (Capacitor/PWA) */
html.is-standalone .gh-appbar {
    padding-top: max(44px, env(safe-area-inset-top)) !important;
    height: auto !important;
    min-height: calc(60px + 44px) !important;
}
html.is-standalone .gh-bottom-nav {
    padding-bottom: max(34px, env(safe-area-inset-bottom)) !important;
    height: auto !important;
    min-height: calc(64px + 34px) !important;
}
html.is-standalone .p-main {
    padding-bottom: calc(1.75rem + max(34px, env(safe-area-inset-bottom))) !important;
}
</style>
<script>
// Detect PWA or Capacitor to add padding for overlapping system bars
if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone || window.Capacitor) {
    document.documentElement.classList.add('is-standalone');
}
</script>
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T8RKR9SR"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<div class="gh-wrap">

<!-- ═══ DESKTOP NAV RAIL ═══ -->
<nav class="gh-rail" role="navigation" aria-label="Main navigation">
    <img src="/assets/img/logo-green-hills.png" class="gh-rail__logo" alt="Green Homes">
    <div class="gh-rail__nav">
    <?php foreach ($tabs as $t):
        if ($t['section'] === 'more') continue; // More items go in bottom sheet on desktop too — but show all in rail
    ?>
        <a href="<?= htmlspecialchars($t['url']) ?>"
           class="gh-rail__item <?= $activeSection === $t['section'] ? 'is-active' : '' ?>"
           title="<?= htmlspecialchars($t['label']) ?>">
            <?= $t['icon'] ?>
            <span><?= htmlspecialchars($t['label']) ?></span>
            <?php if ($t['badge'] > 0): ?><span class="gh-rail__badge"><?= (int)$t['badge'] ?></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
    <div class="gh-rail__spacer"></div>
    </div>
    <div class="gh-rail__bottom">
        <button class="gh-rail__item" onclick="ghOpenMore()" title="More options" style="color:var(--gh-text-muted)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
            <span>More</span>
        </button>
        <a href="/logout" class="gh-rail__item" title="Logout" style="color:#dc2626">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span>Logout</span>
        </a>
    </div>
</nav>

<!-- ═══ PAGE SHELL ═══ -->
<div class="gh-page">

    <!-- Mobile Top App Bar -->
    <header class="gh-appbar" role="banner">
        <?php if ($isPrimaryTab): ?>
        <img src="/assets/img/logo-green-hills.png" class="gh-appbar__logo" alt="GH">
        <?php else: ?>
        <button class="gh-appbar__btn" onclick="history.back()" aria-label="Go back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
            </svg>
        </button>
        <?php endif; ?>

        <div class="gh-appbar__title">
            <?= htmlspecialchars($title) ?>
        </div>

        <div class="gh-appbar__actions">
            <a href="<?= $notifUrl ?>" class="gh-appbar__btn" aria-label="Notifications">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <?php if ($unreadNotif > 0): ?>
                <span class="gh-appbar__badge"></span>
                <?php endif; ?>
            </a>
            <button class="gh-appbar__btn" onclick="ghOpenMore()" aria-label="More options">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>
                </svg>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="p-main">
<?php
}

/* =======================================================================
   PORTAL FOOTER
   ======================================================================= */
function portal_footer(): void {
    $user          = current_user();
    $role          = $user['role'] ?? 'client';
    $tab           = _gh_current_tab();
    $activeSection = _gh_section($tab);
    $unreadNotif   = get_unread_notification_count((int) $user['id']);
    $unreadChat    = function_exists('chat_get_unread_count') ? chat_get_unread_count((int) $user['id']) : 0;
    $tabs          = _gh_nav_tabs($role, $unreadChat, $unreadNotif);
    $moreItems     = _gh_more_items($role);
    $initials      = mb_strtoupper(mb_substr(trim($user['name'] ?? 'U'), 0, 2));
    ?>
    </main>
</div><!-- .gh-page -->

<!-- ═══ MOBILE BOTTOM NAV ═══ -->
<nav class="gh-bottom-nav" role="navigation" aria-label="Bottom navigation">
    <div class="gh-bottom-nav__inner">
    <?php foreach ($tabs as $t):
        $isActive = $activeSection === $t['section'];
        if ($t['section'] === 'more'): ?>
        <button class="gh-bottom-nav__tab <?= $isActive ? 'is-active' : '' ?>"
                onclick="ghOpenMore()" aria-label="More options">
            <?= $t['icon'] ?>
            <span><?= htmlspecialchars($t['label']) ?></span>
        </button>
        <?php else: ?>
        <a href="<?= htmlspecialchars($t['url']) ?>"
           class="gh-bottom-nav__tab <?= $isActive ? 'is-active' : '' ?>"
           aria-label="<?= htmlspecialchars($t['label']) ?>">
            <?= $t['icon'] ?>
            <span><?= htmlspecialchars($t['label']) ?></span>
            <?php if ($t['badge'] > 0): ?>
            <span class="gh-bottom-nav__badge"><?= (int)$t['badge'] ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
    <?php endforeach; ?>
    </div>
</nav>

</div><!-- .gh-wrap -->

<!-- ═══ MORE SHEET OVERLAY ═══ -->
<div class="gh-more-overlay" id="ghMoreOverlay" onclick="ghCloseMore()"></div>

<!-- ═══ MORE BOTTOM SHEET ═══ -->
<div class="gh-more-sheet" id="ghMoreSheet" role="dialog" aria-modal="true" aria-label="More options">
    <div class="gh-more-handle"></div>

    <div class="gh-more-header">
        <div class="gh-more-avatar"><?= htmlspecialchars($initials) ?></div>
        <div>
            <div class="gh-more-user-name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
            <div class="gh-more-user-role"><?= htmlspecialchars(get_role_label($role)) ?></div>
        </div>
    </div>

    <div class="gh-more-list">
    <?php foreach ($moreItems as $item):
        if ($item === 'divider'): ?>
        <div class="gh-more-divider"></div>
        <?php else:
            $danger = !empty($item['danger']) ? ' danger' : '';
        ?>
        <a href="<?= htmlspecialchars($item['url']) ?>" class="gh-more-item<?= $danger ?>">
            <?= $item['icon'] ?>
            <?= htmlspecialchars($item['label']) ?>
        </a>
        <?php endif; ?>
    <?php endforeach; ?>
    </div>
</div>

<!-- ═══ PHOTO LIGHTBOX ═══ -->
<div id="photoLightbox" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.88);z-index:9999;justify-content:center;align-items:center;cursor:pointer;" onclick="closeLightbox()">
    <button onclick="closeLightbox()" style="position:absolute;top:1rem;right:1.5rem;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;z-index:10000;">&times;</button>
    <img id="lightboxImg" src="" alt="Preview" style="max-width:90vw;max-height:90vh;border-radius:8px;box-shadow:0 8px 32px rgba(0,0,0,.4);object-fit:contain;">
</div>

<!-- ═══ FCM TOAST ═══ -->
<div id="ghFcmToastContainer" class="gh-fcm-toast-container">
    <div id="ghFcmToast" class="gh-fcm-toast">
        <div class="gh-fcm-toast-icon" id="ghFcmToastIcon">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="gh-fcm-toast-content">
            <div class="gh-fcm-toast-title" id="ghFcmToastTitle">New Message</div>
            <div class="gh-fcm-toast-body" id="ghFcmToastBody">You have a new update.</div>
        </div>
        <button type="button" class="gh-fcm-toast-close" id="ghFcmToastClose">&times;</button>
    </div>
</div>

<script>
/* ── More Sheet ── */
function ghOpenMore() {
    document.getElementById('ghMoreOverlay').classList.add('is-open');
    document.getElementById('ghMoreSheet').classList.add('is-open');
    document.body.style.overflow = 'hidden';
}
function ghCloseMore() {
    document.getElementById('ghMoreOverlay').classList.remove('is-open');
    document.getElementById('ghMoreSheet').classList.remove('is-open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { ghCloseMore(); closeLightbox(); }
});

/* ── Lightbox ── */
function previewPhoto(url) {
    if (event) event.stopPropagation();
    document.getElementById('lightboxImg').src = url;
    document.getElementById('photoLightbox').style.display = 'flex';
}
function closeLightbox() {
    document.getElementById('photoLightbox').style.display = 'none';
    document.getElementById('lightboxImg').src = '';
}

/* ── Bottom nav tap animation ── */
document.querySelectorAll('.gh-bottom-nav__tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        this.style.transform = 'scale(0.92)';
        setTimeout(() => { this.style.transform = ''; }, 150);
    });
});

/* ── Smooth page entry animation ── */
document.addEventListener('DOMContentLoaded', function() {
    var main = document.querySelector('.p-main');
    if (main) {
        main.style.opacity = '0';
        main.style.transform = 'translateY(8px)';
        main.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                main.style.opacity = '1';
                main.style.transform = 'translateY(0)';
            });
        });
    }
});
</script>

<script>
/* ── Push Notifications (Capacitor) ── */
document.addEventListener('DOMContentLoaded', () => {
    let toastTimeout = null;

    function playNotificationChime() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12);
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } catch(e) {}
    }

    function showHeadsUpToast(title, body, link) {
        const container = document.getElementById('ghFcmToastContainer');
        const toast     = document.getElementById('ghFcmToast');
        const titleEl   = document.getElementById('ghFcmToastTitle');
        const bodyEl    = document.getElementById('ghFcmToastBody');
        const closeBtn  = document.getElementById('ghFcmToastClose');
        if (!container) return;
        titleEl.textContent = title || 'New Notification';
        bodyEl.textContent  = body  || '';
        toast.onclick = (e) => {
            if (e.target === closeBtn) return;
            if (link) window.location.href = link;
            container.classList.remove('is-visible');
        };
        closeBtn.onclick = (e) => {
            e.stopPropagation();
            container.classList.remove('is-visible');
        };
        if (navigator.vibrate) navigator.vibrate([120, 80, 120]);
        playNotificationChime();
        container.classList.add('is-visible');
        if (toastTimeout) clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => container.classList.remove('is-visible'), 4500);
    }

    function sendFcmToken(tokenStr) {
        if (!tokenStr) return;
        const body = new URLSearchParams();
        body.append('action', 'register_fcm_token');
        body.append('fcm_token', tokenStr);
        body.append('token', tokenStr);
        fetch('/api/portal', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, credentials: 'same-origin', body: body.toString() })
            .then(r => r.json()).then(d => console.log('[FCM Token]', d)).catch(console.error);
    }

    function getPushPlugin() {
        const cap = window.Capacitor || (window.parent && window.parent.Capacitor);
        if (!cap) return null;
        if (cap.Plugins && cap.Plugins.PushNotifications) return cap.Plugins.PushNotifications;
        if (typeof cap.registerPlugin === 'function') { try { return cap.registerPlugin('PushNotifications'); } catch(e) {} }
        return null;
    }

    let pushInitialized = false;
    function initPushNotifications() {
        const PN = getPushPlugin();
        if (!PN || pushInitialized) return;
        pushInitialized = true;
        PN.requestPermissions().then(r => {
            if (r.receive === 'granted' || r.display === 'granted') {
                PN.createChannel({ id:'high_importance_channel', name:'High Importance Notifications', importance:5, visibility:1, sound:'default', vibration:true, lights:true, lightColor:'#075e54' }).catch(console.error);
                PN.register();
            }
        }).catch(console.error);
        PN.addListener('registration', t => { if (t && t.value) sendFcmToken(t.value); });
        PN.addListener('pushNotificationReceived', n => {
            showHeadsUpToast(n.title||(n.data&&n.data.title)||'New', n.body||(n.data&&n.data.body)||'', (n.data&&n.data.link)||'/dashboard/project-chats');
        });
        PN.addListener('pushNotificationActionPerformed', a => {
            const d = (a.notification||{}).data || (a.notification||{}).additionalData || {};
            if (d.link) window.location.href = d.link;
        });
    }

    window.registerAppPushToken = () => { pushInitialized = false; initPushNotifications(); };
    [0, 200, 800, 2000, 4000].forEach(d => setTimeout(initPushNotifications, d));
});
</script>

</body>
</html>
<?php
}
