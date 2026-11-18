<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('admin', 'super_admin');
$db = db();

$documents = $db->query(
    "SELECT d.*, p.name as project_name
     FROM documents d
     JOIN projects p ON d.project_id = p.id
     ORDER BY d.uploaded_at DESC"
)->fetchAll();

portal_header(['title' => 'Documents', 'active' => 'documents', 'tab' => 'admin-documents']);

$typeIcons = [
    'pdf'  => ['icon' => '📄', 'color' => '#ef4444', 'bg' => '#fef2f2'],
    'doc'  => ['icon' => '📝', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
    'docx' => ['icon' => '📝', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
    'xls'  => ['icon' => '📊', 'color' => '#16a34a', 'bg' => '#f0fdf4'],
    'xlsx' => ['icon' => '📊', 'color' => '#16a34a', 'bg' => '#f0fdf4'],
    'jpg'  => ['icon' => '🖼️', 'color' => '#f59e0b', 'bg' => '#fffbeb'],
    'jpeg' => ['icon' => '🖼️', 'color' => '#f59e0b', 'bg' => '#fffbeb'],
    'png'  => ['icon' => '🖼️', 'color' => '#f59e0b', 'bg' => '#fffbeb'],
    'dwg'  => ['icon' => '📐', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
    'dxf'  => ['icon' => '📐', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
];
?>

<style>
.docs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    padding: 0;
}
.doc-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: box-shadow 0.2s, transform 0.2s;
    text-decoration: none;
    color: inherit;
}
.doc-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    transform: translateY(-2px);
    text-decoration: none;
}
.doc-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}
.doc-info { flex: 1; min-width: 0; }
.doc-title {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
}
.doc-project {
    font-size: 0.78rem;
    color: #166534;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}
.doc-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.doc-type-badge {
    font-size: 0.68rem;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.doc-date {
    font-size: 0.75rem;
    color: #94a3b8;
}
.doc-download-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f0fdf4;
    color: #16a34a;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
    flex-shrink: 0;
    text-decoration: none;
}
.doc-download-btn:hover { background: #dcfce7; }
.docs-empty {
    text-align: center;
    padding: 4rem 2rem;
    color: #94a3b8;
}
.docs-empty svg { width: 60px; height: 60px; margin: 0 auto 1rem; display: block; opacity: 0.3; }
.docs-empty h3 { color: #64748b; margin-bottom: 0.5rem; }
@media(max-width: 480px) {
    .docs-grid { grid-template-columns: 1fr; }
}
</style>

<div class="hero">
    <h1>Documents</h1>
    <p>All project documents — <?= count($documents) ?> file<?= count($documents) !== 1 ? 's' : '' ?></p>
</div>

<div class="p-content" style="padding-top: 0;">
    <?php if (empty($documents)): ?>
    <div class="docs-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
        </svg>
        <h3>No Documents Yet</h3>
        <p>Documents uploaded for projects will appear here.</p>
    </div>
    <?php else: ?>
    <div class="docs-grid">
        <?php foreach ($documents as $d):
            $ext = strtolower(pathinfo((string)($d['url'] ?? ''), PATHINFO_EXTENSION));
            $typeInfo = $typeIcons[$ext] ?? ['icon' => '📁', 'color' => '#64748b', 'bg' => '#f8fafc'];
            $uploadedDate = !empty($d['uploaded_at']) ? date('M j, Y', strtotime((string)$d['uploaded_at'])) : '—';
            $title = htmlspecialchars((string)($d['title'] ?? 'Untitled'));
            $project = htmlspecialchars((string)($d['project_name'] ?? ''));
            $url = htmlspecialchars((string)($d['url'] ?? '#'));
            $type = ucfirst((string)($d['type'] ?? $ext ?: 'File'));
        ?>
        <div class="doc-card">
            <div class="doc-icon" style="background:<?= $typeInfo['bg'] ?>;">
                <?= $typeInfo['icon'] ?>
            </div>
            <div class="doc-info">
                <div class="doc-title" title="<?= $title ?>"><?= $title ?></div>
                <div class="doc-project">
                    <a href="/admin/projects?id=<?= (int)$d['project_id'] ?>" style="color:#166534;text-decoration:none;">
                        <?= $project ?>
                    </a>
                </div>
                <div class="doc-meta">
                    <span class="doc-type-badge" style="background:<?= $typeInfo['bg'] ?>;color:<?= $typeInfo['color'] ?>;">
                        <?= htmlspecialchars($type) ?>
                    </span>
                    <span class="doc-date"><?= $uploadedDate ?></span>
                </div>
            </div>
            <a href="<?= $url ?>" target="_blank" rel="noopener" class="doc-download-btn" title="Download">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
