<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
require_once $root . '/src/csrf.php';
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Design Ideas', 'tab' => 'chat']);

$ideas = [];
if ($project) {
    $stmt = $db->prepare(
        "SELECT c.*, u.name AS sender_name FROM chat_messages c
         JOIN users u ON c.sender_id = u.id
         WHERE c.project_id = ? ORDER BY c.created_at DESC"
    );
    $stmt->execute([(int) $project['id']]);
    $ideas = $stmt->fetchAll();
}

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
?>

<style>
    .p-main { max-width: none !important; }
    .idea-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1rem; }
    .idea-card { border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; background:#fff; transition:box-shadow .2s; }
    .idea-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.08); }
    .idea-card img { width:100%; height:180px; object-fit:cover; display:block; background:#f1f5f9; }
    .idea-card .idea-body { padding:1rem; }
    .idea-card .idea-meta { font-size:.75rem; color:#64748b; margin-bottom:.5rem; }
    .idea-card .idea-text { font-size:.9rem; line-height:1.5; }
    .idea-card .idea-tag { display:inline-block; font-size:.7rem; padding:2px 8px; border-radius:99px; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; margin-top:.5rem; margin-right:.25rem; }
    .idea-card .idea-tag.exterior { background:#eff6ff; color:#1e40af; border-color:#bfdbfe; }
    .idea-card .idea-tag.interior { background:#fefce8; color:#854d0e; border-color:#fef08a; }
    .idea-card .idea-tag.kitchen { background:#fff7ed; color:#9a3412; border-color:#fed7aa; }
    .idea-card .idea-tag.bathroom { background:#f5f3ff; color:#5b21b6; border-color:#ddd6fe; }
    .idea-card .idea-tag.landscape { background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
    .upload-zone { border:2px dashed #d1d5db; border-radius:12px; padding:2rem; text-align:center; cursor:pointer; transition:border-color .2s; }
    .upload-zone:hover { border-color:#166534; }
    .upload-zone.dragover { border-color:#166534; background:#f0fdf4; }
    .tag-select { display:flex; flex-wrap:wrap; gap:.5rem; margin-top:.5rem; }
    .tag-btn { padding:4px 12px; border-radius:99px; border:1px solid #d1d5db; background:#fff; cursor:pointer; font-size:.8rem; transition:all .15s; }
    .tag-btn:hover { border-color:#166534; }
    .tag-btn.selected { background:#166534; color:#fff; border-color:#166534; }
</style>

<div class="p-content">
    <h1 class="p-title">Design Ideas & Suggestions</h1>
    <p style="color:#64748b;margin-top:-.5rem;margin-bottom:1.5rem;">Share photos, inspiration, and ideas for your home design</p>

    <?php if ($msg): ?><div class="alert alert-green"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-red"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <!-- Share New Idea -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-title">Share an Idea</div>
        <form method="post" action="/api/portal" id="ideaForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="share_design_idea">
            <input type="hidden" name="project_id" value="<?= $project ? $project['id'] : '' ?>">

            <div class="form-group">
                <label>Your Idea / Description *</label>
                <textarea name="message" rows="3" class="form-control" placeholder="Describe the design idea, style, or inspiration you have in mind..." required></textarea>
            </div>

            <div class="form-group">
                <label>Photo URL (optional)</label>
                <input type="url" name="photo_url" class="form-control" placeholder="Paste a link to a photo (Pinterest, Google Images, etc.)">
                <small style="color:#64748b;">Paste a URL to a photo that inspires you</small>
            </div>

            <div class="form-group">
                <label>Category</label>
                <div class="tag-select">
                    <button type="button" class="tag-btn" data-tag="general" onclick="toggleTag(this)">General</button>
                    <button type="button" class="tag-btn" data-tag="exterior" onclick="toggleTag(this)">Exterior</button>
                    <button type="button" class="tag-btn" data-tag="interior" onclick="toggleTag(this)">Interior</button>
                    <button type="button" class="tag-btn" data-tag="kitchen" onclick="toggleTag(this)">Kitchen</button>
                    <button type="button" class="tag-btn" data-tag="bathroom" onclick="toggleTag(this)">Bathroom</button>
                    <button type="button" class="tag-btn" data-tag="landscape" onclick="toggleTag(this)">Landscape</button>
                    <button type="button" class="tag-btn" data-tag="flooring" onclick="toggleTag(this)">Flooring</button>
                    <button type="button" class="tag-btn" data-tag="lighting" onclick="toggleTag(this)">Lighting</button>
                    <button type="button" class="tag-btn" data-tag="other" onclick="toggleTag(this)">Other</button>
                </div>
                <input type="hidden" name="tag" id="tagInput" value="general">
            </div>

            <button type="submit" class="btn">Share Idea</button>
        </form>
    </div>

    <!-- Ideas Board -->
    <?php if (empty($ideas)): ?>
    <div class="card">
        <div class="empty" style="padding:3rem;">
            <p style="font-size:1.1rem;font-weight:600;margin-bottom:.5rem;">No ideas shared yet</p>
            <p style="color:#64748b;">Start by sharing your first design idea above!</p>
        </div>
    </div>
    <?php else: ?>
    <div class="idea-grid">
        <?php foreach ($ideas as $idea): ?>
        <div class="idea-card">
            <?php
            $photoUrl = '';
            $tag = 'general';
            $text = $idea['message'];
            // Extract photo URL from message if it contains [photo:URL]
            if (preg_match('/\[photo:(https?:\/\/[^\]]+)\]/', $text, $m)) {
                $photoUrl = $m[1];
                $text = str_replace($m[0], '', $text);
            }
            // Extract tag from message if it contains [tag:TAG]
            if (preg_match('/\[tag:(\w+)\]/', $text, $m)) {
                $tag = $m[1];
                $text = str_replace($m[0], '', $text);
            }
            $text = trim($text);
            ?>
            <?php if ($photoUrl): ?>
            <div style="cursor:pointer;" onclick="previewPhoto('<?= htmlspecialchars($photoUrl) ?>')">
                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Design idea" style="transition:opacity .2s;" onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'" onerror="this.style.display='none'">
            </div>
            <?php else: ?>
            <div style="height:80px;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);display:flex;align-items:center;justify-content:center;">
                <svg width="32" height="32" fill="none" stroke="#166534" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
            </div>
            <?php endif; ?>
            <div class="idea-body">
                <div class="idea-meta">
                    <?= htmlspecialchars($idea['sender_name']) ?> · <?= time_ago($idea['created_at']) ?>
                </div>
                <?php if ($text): ?>
                <div class="idea-text"><?= nl2br(htmlspecialchars($text)) ?></div>
                <?php endif; ?>
                <span class="idea-tag <?= $tag ?>"><?= ucfirst($tag) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleTag(btn) {
    document.querySelectorAll('.tag-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('tagInput').value = btn.dataset.tag;
}
// Select "general" by default
document.querySelector('.tag-btn[data-tag="general"]').classList.add('selected');
</script>

<?php portal_footer(); ?>
