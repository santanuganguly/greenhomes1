<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('admin', 'super_admin');
$db = db();
require_once $root . '/src/csrf.php';

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

$payments = $db->query(
    "SELECT ph.*, i.title as invoice_title, i.invoice_no, i.amount as invoice_amount,
            p.name as project_name, u.name as client_name
     FROM payment_history ph
     JOIN invoices i ON ph.invoice_id = i.id
     JOIN projects p ON i.project_id = p.id
     LEFT JOIN users u ON p.client_id = u.id
     ORDER BY ph.created_at DESC"
)->fetchAll();

$invoices = $db->query(
    "SELECT i.*, p.name as project_name, u.name as client_name
     FROM invoices i
     JOIN projects p ON i.project_id = p.id
     LEFT JOIN users u ON p.client_id = u.id
     ORDER BY i.due_date ASC"
)->fetchAll();

$projects = $db->query("SELECT id, name FROM projects ORDER BY name")->fetchAll();

portal_header(['title' => 'Payments', 'active' => 'payments', 'tab' => 'admin-payments']);
?>

<style>
/* Mobile-first card styles */
.invoice-cards { display: flex; flex-direction: column; gap: 0.75rem; }
.invoice-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.invoice-card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
.invoice-card-title { font-weight: 600; color: #1e293b; font-size: 0.95rem; flex: 1; min-width: 0; }
.invoice-card-no { font-size: 0.75rem; color: #94a3b8; font-family: monospace; }
.invoice-card-row { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; margin-top:0.25rem; }
.invoice-card-amount { font-size: 1.1rem; font-weight: 700; color: #166534; }
.invoice-card-due { font-size: 0.8rem; color: #64748b; }
.invoice-card-actions { margin-top: 0.5rem; }

.payment-history-cards { display: flex; flex-direction: column; gap: 0.75rem; }
.ph-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.35rem 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.ph-invoice { font-weight: 600; color: #1e293b; font-size: 0.9rem; grid-column: 1 / -1; }
.ph-client { font-size: 0.8rem; color: #64748b; grid-column: 1 / -1; margin-bottom: 0.25rem; }
.ph-amount { font-weight: 700; color: #16a34a; font-size: 1rem; text-align: left; }
.ph-method { font-size: 0.78rem; color: #64748b; align-self: center; text-align: right; }
.ph-date { font-size: 0.78rem; color: #94a3b8; text-align: left; }
.ph-ref { font-size: 0.75rem; color: #64748b; font-family: monospace; grid-column: 1/-1; background: #f8fafc; padding: 4px 8px; border-radius: 4px; margin-top: 0.25rem; word-break: break-all; }

/* Responsive Grid Overrides */
@media(min-width: 768px) {
    .invoice-cards { display: none; }
    .invoice-table-wrap { display: block; }
    .payment-history-cards { display: none; }
    .ph-table-wrap { display: block; }
    .mobile-summary-first { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
}
@media(max-width: 767px) {
    .invoice-table-wrap { display: none; }
    .ph-table-wrap { display: none; }
    .mobile-summary-first { display: flex; flex-direction: column-reverse; gap: 1.5rem; }
    .grid.grid-2 { grid-template-columns: 1fr !important; }
}
</style>

<div class="hero">
    <h1>Payments</h1>
    <p>Create invoices and track payments</p>
</div>

<?php if ($msg): ?><div class="alert alert-green"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-red"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="mobile-summary-first" style="margin-bottom:1.5rem;">
    <!-- Create Invoice -->
    <div class="card">
        <div class="card-title">Create Invoice</div>
        <form method="post" action="/api/portal">
            <?php require_once $root . '/src/csrf.php'; echo csrf_field(); ?>
            <input type="hidden" name="action" value="create_invoice">
            <div class="form-group">
                <label>Project *</label>
                <select name="project_id" class="form-control" required>
                    <option value="">Select project...</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" required class="form-control" placeholder="e.g. Foundation Phase Payment">
            </div>
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Amount (₹) *</label>
                    <input type="number" name="amount" step="0.01" required class="form-control" placeholder="50000">
                </div>
                <div class="form-group">
                    <label>Due Date *</label>
                    <input type="date" name="due_date" required class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label>Description (optional)</label>
                <textarea name="description" rows="2" class="form-control" placeholder="Invoice description..."></textarea>
            </div>
            <button type="submit" class="btn">Create Invoice</button>
        </form>
    </div>

    <!-- Summary -->
    <div class="card">
        <div class="card-title">Summary</div>
        <?php
        $totalInvoiced = 0;
        $totalPaidAmt = 0;
        $totalPendingAmt = 0;
        foreach ($invoices as $inv) {
            $totalInvoiced += (float)$inv['amount'];
            if ($inv['status'] === 'paid') $totalPaidAmt += (float)$inv['amount'];
            else $totalPendingAmt += (float)$inv['amount'];
        }
        ?>
        <div class="stat" style="margin-bottom:.5rem;">
            <div class="stat__label">Total Invoiced</div>
            <div class="stat__num"><?= format_currency($totalInvoiced) ?></div>
        </div>
        <div class="stat" style="margin-bottom:.5rem;">
            <div class="stat__label">Paid</div>
            <div class="stat__num" style="color:#16a34a;"><?= format_currency($totalPaidAmt) ?></div>
        </div>
        <div class="stat" style="margin-bottom:.5rem;">
            <div class="stat__label">Pending</div>
            <div class="stat__num" style="color:#dc2626;"><?= format_currency($totalPendingAmt) ?></div>
        </div>
        <div class="stat">
            <div class="stat__label">Total Payments Received</div>
            <div class="stat__num"><?= count($payments) ?></div>
        </div>
    </div>
</div>

<!-- Payment Submissions -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-title">Payment Submissions (<?= count($payments) ?>)</div>
    <?php if (empty($payments)): ?>
        <p class="empty">No payment submissions yet</p>
    <?php else: ?>
        <div class="ph-table-wrap" style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr><th>Date</th><th>Client</th><th>Project</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Reference</th><th>Notes</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($p['payment_date'])) ?></td>
                        <td><?= htmlspecialchars($p['client_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($p['project_name']) ?></td>
                        <td><?= htmlspecialchars($p['invoice_no'] ?? $p['invoice_title']) ?></td>
                        <td><strong><?= format_currency((float)$p['amount']) ?></strong></td>
                        <td><span class="pill pill-blue"><?= ucfirst(str_replace('_', ' ', $p['payment_method'])) ?></span></td>
                        <td><code style="font-size:.8rem;"><?= htmlspecialchars($p['reference_no']) ?></code></td>
                        <td><?= htmlspecialchars($p['notes'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="payment-history-cards">
            <?php foreach ($payments as $p): ?>
            <div class="ph-card">
                <div class="ph-invoice"><?= htmlspecialchars($p['invoice_no'] ?? $p['invoice_title']) ?></div>
                <div class="ph-client"><?= htmlspecialchars($p['project_name']) ?> (<?= htmlspecialchars($p['client_name'] ?? '—') ?>)</div>
                <div class="ph-amount"><?= format_currency((float)$p['amount']) ?></div>
                <div class="ph-method"><?= ucfirst(str_replace('_', ' ', $p['payment_method'])) ?></div>
                <div class="ph-date"><?= date('M j, Y', strtotime($p['payment_date'])) ?></div>
                <div class="ph-ref"><?= htmlspecialchars($p['reference_no']) ?></div>
                <?php if (!empty($p['notes'])): ?>
                <div style="grid-column: 1/-1; font-size:0.8rem; color:#475569; margin-top:0.25rem;">Notes: <?= htmlspecialchars($p['notes']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- All Invoices -->
<div class="card">
    <div class="card-title">All Invoices</div>
    <?php if (empty($invoices)): ?>
        <p class="empty">No invoices created</p>
    <?php else: ?>
        <div class="invoice-table-wrap" style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr><th>Invoice</th><th>Project</th><th>Client</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><?= htmlspecialchars($inv['invoice_no'] ?? 'INV-' . str_pad($inv['id'], 4, '0', STR_PAD_LEFT)) ?></td>
                        <td><a href="/admin/projects/detail?id=<?= $inv['project_id'] ?>"><?= htmlspecialchars($inv['project_name']) ?></a></td>
                        <td><?= htmlspecialchars($inv['client_name'] ?? '—') ?></td>
                        <td><?= format_currency((float)$inv['amount']) ?></td>
                        <td><?= date('M j, Y', strtotime($inv['due_date'])) ?></td>
                        <td>
                            <span class="pill pill-<?= $inv['status'] === 'paid' ? 'green' : ($inv['status'] === 'overdue' ? 'red' : 'yellow') ?>">
                                <?= ucfirst($inv['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($inv['status'] !== 'paid'): ?>
                            <form method="post" action="/api/portal" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="update_invoice_status">
                                <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                                <input type="hidden" name="project_id" value="<?= $inv['project_id'] ?>">
                                <input type="hidden" name="status" value="paid">
                                <button type="submit" class="btn btn-sm btn-outline" onclick="return confirm('Mark as paid?')">Mark Paid</button>
                            </form>
                            <?php else: ?>
                            <small style="color:#16a34a;">Paid <?= $inv['paid_date'] ? date('M j', strtotime($inv['paid_date'])) : '' ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="invoice-cards">
            <?php foreach ($invoices as $inv): 
                $isPaid = $inv['status'] === 'paid';
                $statusClass = $isPaid ? 'pill-green' : ($inv['status'] === 'overdue' ? 'pill-red' : 'pill-yellow');
                $invNo = htmlspecialchars($inv['invoice_no'] ?? 'INV-' . str_pad($inv['id'], 4, '0', STR_PAD_LEFT));
            ?>
            <div class="invoice-card">
                <div class="invoice-card-header">
                    <div>
                        <div class="invoice-card-title"><?= htmlspecialchars($inv['project_name']) ?></div>
                        <div class="invoice-card-no"><?= $invNo ?> &middot; <?= htmlspecialchars($inv['client_name'] ?? '—') ?></div>
                    </div>
                    <span class="pill <?= $statusClass ?>" style="flex-shrink:0;"><?= ucfirst($inv['status']) ?></span>
                </div>
                <div class="invoice-card-row">
                    <div class="invoice-card-amount"><?= format_currency((float)$inv['amount']) ?></div>
                    <div class="invoice-card-due">Due: <?= date('M j, Y', strtotime($inv['due_date'])) ?></div>
                </div>
                <div class="invoice-card-actions">
                    <?php if (!$isPaid): ?>
                    <form method="post" action="/api/portal" style="width:100%; display:flex;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="update_invoice_status">
                        <input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>">
                        <input type="hidden" name="project_id" value="<?= $inv['project_id'] ?>">
                        <input type="hidden" name="status" value="paid">
                        <button type="submit" class="btn btn-outline" style="flex:1; justify-content:center; padding: 0.5rem;" onclick="return confirm('Mark as paid?')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:0.25rem;"><polyline points="20 6 9 17 4 12"/></svg>
                            Mark Paid
                        </button>
                    </form>
                    <?php else: ?>
                    <div style="font-size:0.85rem; color:#16a34a; font-weight:600; text-align:right; width:100%;">
                        Paid <?= $inv['paid_date'] ? date('M j, Y', strtotime($inv['paid_date'])) : '' ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php portal_footer(); ?>
