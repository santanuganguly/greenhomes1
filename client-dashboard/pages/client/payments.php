<?php
require_once __DIR__ . '/../layout.php';
$user = require_role('client');
$db = db();
require_once $root . '/src/csrf.php';
$project = get_client_project((int) $user['id']);
portal_header(['title' => 'Payments', 'tab' => 'payments']);

$invoices = [];
$payments = [];
$totalCost = 0;
$totalPaid = 0;
$remaining = 0;
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

if ($project) {
    $pid = (int) $project['id'];
    $stmt = $db->prepare("SELECT * FROM invoices WHERE project_id = ? ORDER BY due_date ASC");
    $stmt->execute([$pid]);
    $invoices = $stmt->fetchAll();

    $stmt2 = $db->prepare("SELECT ph.*, i.title as invoice_title FROM payment_history ph JOIN invoices i ON ph.invoice_id = i.id WHERE i.project_id = ? ORDER BY ph.payment_date DESC");
    $stmt2->execute([$pid]);
    $payments = $stmt2->fetchAll();

    $totalCost = (float) ($project['budget'] ?? 0);
    foreach ($invoices as $inv) {
        if ($inv['status'] === 'paid') {
            $totalPaid += (float) $inv['amount'];
        }
    }
    $remaining = $totalCost - $totalPaid;
}

$bankName = gh_env('GH_BANK_NAME', '—');
$accountName = gh_env('GH_ACCOUNT_NAME', '—');
$accountNo = gh_env('GH_ACCOUNT_NO', '—');
$ifsc = gh_env('GH_IFSC', '—');
$upiId = gh_env('GH_UPI_ID', '—');
$gstin = gh_env('GH_GSTIN', '');
?>

<style>
    .pay-modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.5); z-index:1000; justify-content:center; align-items:center; }
    .pay-modal.active { display:flex; }
    .pay-modal-box { background:#fff; border-radius:12px; padding:2rem; max-width:500px; width:90%; max-height:90vh; overflow-y:auto; position:relative; }
    .pay-modal-box h2 { margin-top:0; font-size:1.25rem; }
    .pay-close { position:absolute; top:1rem; right:1rem; background:none; border:none; font-size:1.5rem; cursor:pointer; color:#64748b; }
    .bank-detail { display:flex; justify-content:space-between; padding:.5rem 0; border-bottom:1px solid #f1f5f9; }
    .bank-detail:last-child { border-bottom:none; }
    .bank-label { color:#64748b; font-size:.85rem; }
    .bank-value { font-weight:600; font-family:monospace; }
    .copy-btn { background:none; border:1px solid #d1d5db; border-radius:4px; padding:2px 8px; cursor:pointer; font-size:.75rem; color:#166534; }
    .copy-btn:hover { background:#f0fdf4; }
    .invoice-print { font-family:Arial,sans-serif; }
    .invoice-print table { width:100%; border-collapse:collapse; margin:1rem 0; }
    .invoice-print th, .invoice-print td { border:1px solid #e2e8f0; padding:.5rem .75rem; text-align:left; font-size:.85rem; }
    .invoice-print th { background:#f8fafc; }
</style>

<!-- PAY NOW MODAL -->
<div class="pay-modal" id="payModal">
    <div class="pay-modal-box">
        <button class="pay-close" onclick="document.getElementById('payModal').classList.remove('active')">&times;</button>
        <h2>Bank Payment Details</h2>
        <p style="color:#64748b;font-size:.85rem;margin-bottom:1rem;">Transfer the amount to the account below and submit the reference number.</p>

        <div class="card" style="background:#f0fdf4;border:1px solid #bbf7d0;">
            <div class="bank-detail">
                <span class="bank-label">Bank Name</span>
                <span class="bank-value"><?= htmlspecialchars($bankName) ?></span>
            </div>
            <div class="bank-detail">
                <span class="bank-label">Account Name</span>
                <span class="bank-value"><?= htmlspecialchars($accountName) ?></span>
            </div>
            <div class="bank-detail">
                <span class="bank-label">Account No.</span>
                <span class="bank-value"><?= htmlspecialchars($accountNo) ?> <button class="copy-btn" onclick="navigator.clipboard.writeText('<?= $accountNo ?>')">Copy</button></span>
            </div>
            <div class="bank-detail">
                <span class="bank-label">IFSC</span>
                <span class="bank-value"><?= htmlspecialchars($ifsc) ?> <button class="copy-btn" onclick="navigator.clipboard.writeText('<?= $ifsc ?>')">Copy</button></span>
            </div>
            <div class="bank-detail">
                <span class="bank-label">UPI ID</span>
                <span class="bank-value"><?= htmlspecialchars($upiId) ?> <button class="copy-btn" onclick="navigator.clipboard.writeText('<?= $upiId ?>')">Copy</button></span>
            </div>
            <?php if ($gstin): ?>
            <div class="bank-detail">
                <span class="bank-label">GSTIN</span>
                <span class="bank-value"><?= htmlspecialchars($gstin) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div style="margin-top:1rem;">
            <p style="font-weight:600;font-size:.9rem;">After payment, submit details:</p>
            <form method="post" action="/api/portal">
                <?php require_once $root . '/src/csrf.php'; echo csrf_field(); ?>
                <input type="hidden" name="action" value="submit_payment">
                <input type="hidden" name="project_id" value="<?= $project['id'] ?? '' ?>">
                <div class="form-group">
                    <label>Select Invoice</label>
                    <select name="invoice_id" class="form-control" required>
                        <option value="">Choose invoice...</option>
                        <?php foreach ($invoices as $inv): ?>
                            <?php if ($inv['status'] !== 'paid'): ?>
                            <option value="<?= $inv['id'] ?>"><?= htmlspecialchars($inv['invoice_no'] ?? $inv['title']) ?> — <?= format_currency((float)$inv['amount']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Amount Paid (₹)</label>
                    <input type="number" name="amount" step="0.01" required class="form-control" placeholder="e.g. 50000">
                </div>
                <div class="form-group">
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" required class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="bank_transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                        <option value="upi">UPI</option>
                        <option value="cheque">Cheque</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reference / Transaction No.</label>
                    <input type="text" name="reference_no" required class="form-control" placeholder="UTR / UPI Ref No. / Cheque No.">
                </div>
                <div class="form-group">
                    <label>Notes (optional)</label>
                    <input type="text" name="notes" class="form-control" placeholder="Any notes about this payment">
                </div>
                <button type="submit" class="btn" style="width:100%;justify-content:center;">Submit Payment</button>
            </form>
        </div>
    </div>
</div>

<!-- INVOICE PRINT MODAL -->
<div class="pay-modal" id="invoiceModal">
    <div class="pay-modal-box" style="max-width:700px;">
        <button class="pay-close" onclick="document.getElementById('invoiceModal').classList.remove('active')">&times;</button>
        <div id="invoiceContent"></div>
        <div style="text-align:center;margin-top:1rem;">
            <button class="btn" onclick="window.print()">Print Invoice</button>
        </div>
    </div>
</div>

<div class="p-content">
    <h1 class="p-title">Payments</h1>

    <?php if ($msg): ?><div class="alert alert-green"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-red"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="grid grid-3" style="margin-bottom:1.5rem;">
        <div class="stat">
            <div class="stat__label">Total Project Cost</div>
            <div class="stat__num"><?= format_currency($totalCost) ?></div>
        </div>
        <div class="stat">
            <div class="stat__label">Paid</div>
            <div class="stat__num" style="color:#16a34a;"><?= format_currency($totalPaid) ?></div>
        </div>
        <div class="stat">
            <div class="stat__label">Remaining</div>
            <div class="stat__num" style="color:#dc2626;"><?= format_currency($remaining) ?></div>
        </div>
    </div>

    <?php
    $hasUnpaid = false;
    foreach ($invoices as $inv) {
        if ($inv['status'] !== 'paid') {
            $hasUnpaid = true;
            break;
        }
    }
    ?>
    <?php if ($hasUnpaid): ?>
    <div style="margin-bottom:1rem;">
        <button class="btn" onclick="document.getElementById('payModal').classList.add('active')">Pay Now</button>
    </div>
    <?php else: ?>
    <div style="margin-bottom:1.5rem; padding: 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; color: #64748b; font-size: 0.95rem;">
        You currently have no pending invoices to pay. Once an invoice is generated for this project, you will be able to make a payment here.
    </div>
    <?php endif; ?>

    <?php if (empty($invoices)): ?>
    <div class="empty">No invoices yet.</div>
    <?php else: ?>

    <style>
    /* Mobile-first invoice cards */
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
    .invoice-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }
    .invoice-card-title { font-weight: 600; color: #1e293b; font-size: 0.95rem; flex: 1; }
    .invoice-card-no { font-size: 0.75rem; color: #94a3b8; font-family: monospace; }
    .invoice-card-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
    }
    .invoice-card-amount { font-size: 1.1rem; font-weight: 700; color: #166534; }
    .invoice-card-due { font-size: 0.8rem; color: #64748b; }
    .invoice-card-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.25rem; }
    .invoice-card-actions .btn { flex: 1; min-width: 100px; justify-content: center; font-size: 0.82rem; padding: 0.45rem 0.75rem; }

    .payment-history-cards { display: flex; flex-direction: column; gap: 0.75rem; }
    .ph-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 0.25rem 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .ph-invoice { font-weight: 600; color: #1e293b; font-size: 0.9rem; }
    .ph-amount { font-weight: 700; color: #16a34a; font-size: 1rem; text-align: right; }
    .ph-method { font-size: 0.78rem; color: #64748b; }
    .ph-date { font-size: 0.78rem; color: #94a3b8; text-align: right; }
    .ph-ref { font-size: 0.75rem; color: #64748b; font-family: monospace; grid-column: 1/-1; background: #f8fafc; padding: 3px 8px; border-radius: 4px; }

    /* On larger screens show as table */
    @media(min-width: 768px) {
        .invoice-cards { display: none; }
        .invoice-table-wrap { display: block; }
        .payment-history-cards { display: none; }
        .ph-table-wrap { display: block; }
    }
    @media(max-width: 767px) {
        .invoice-table-wrap { display: none; }
        .ph-table-wrap { display: none; }
    }
    </style>

    <!-- INVOICES: Mobile Card View -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-title">Invoices</div>

        <!-- Desktop table -->
        <div class="invoice-table-wrap" style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Title</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><?= htmlspecialchars($inv['invoice_no'] ?? 'INV-' . str_pad($inv['id'], 4, '0', STR_PAD_LEFT)) ?></td>
                        <td><?= htmlspecialchars($inv['title']) ?></td>
                        <td><?= format_currency((float) $inv['amount']) ?></td>
                        <td><?= date('M j, Y', strtotime($inv['due_date'])) ?></td>
                        <td>
                            <span class="pill <?= $inv['status'] === 'paid' ? 'pill-green' : ($inv['status'] === 'overdue' ? 'pill-red' : 'pill-yellow') ?>">
                                <?= ucfirst(htmlspecialchars($inv['status'])) ?>
                            </span>
                        </td>
                        <td style="white-space:nowrap;">
                            <button class="btn btn-sm btn-outline" onclick="showInvoice(<?= htmlspecialchars(json_encode($inv)) ?>)">Download</button>
                            <?php if ($inv['status'] !== 'paid'): ?>
                            <button class="btn btn-sm" onclick="document.getElementById('payModal').classList.add('active')">Pay</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile card view -->
        <div class="invoice-cards">
            <?php foreach ($invoices as $inv):
                $isPaid = $inv['status'] === 'paid';
                $statusClass = $isPaid ? 'pill-green' : ($inv['status'] === 'overdue' ? 'pill-red' : 'pill-yellow');
                $invNo = htmlspecialchars($inv['invoice_no'] ?? 'INV-' . str_pad($inv['id'], 4, '0', STR_PAD_LEFT));
            ?>
            <div class="invoice-card">
                <div class="invoice-card-header">
                    <div>
                        <div class="invoice-card-title"><?= htmlspecialchars($inv['title']) ?></div>
                        <div class="invoice-card-no"><?= $invNo ?></div>
                    </div>
                    <span class="pill <?= $statusClass ?>"><?= ucfirst(htmlspecialchars($inv['status'])) ?></span>
                </div>
                <div class="invoice-card-row">
                    <div class="invoice-card-amount"><?= format_currency((float) $inv['amount']) ?></div>
                    <div class="invoice-card-due">Due: <?= date('M j, Y', strtotime($inv['due_date'])) ?></div>
                </div>
                <div class="invoice-card-actions">
                    <button class="btn btn-sm btn-outline" onclick="showInvoice(<?= htmlspecialchars(json_encode($inv)) ?>)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 16V4m0 12l-4-4m4 4l4-4M4 20h16"/></svg>
                        Invoice
                    </button>
                    <?php if (!$isPaid): ?>
                    <button class="btn btn-sm" onclick="document.getElementById('payModal').classList.add('active')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                        Pay Now
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($payments)): ?>
    <div class="card" style="overflow-x:auto;">
        <div class="card-title">Payment History</div>

        <!-- Desktop table -->
        <div class="ph-table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Date</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= date('M j, Y', strtotime($p['payment_date'])) ?></td>
                        <td><?= htmlspecialchars($p['invoice_title']) ?></td>
                        <td><?= format_currency((float) $p['amount']) ?></td>
                        <td><?= ucfirst(str_replace('_', ' ', $p['payment_method'] ?? '—')) ?></td>
                        <td><code><?= htmlspecialchars($p['reference_no'] ?? '—') ?></code></td>
                        <td><span class="pill pill-blue">Submitted</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile card view -->
        <div class="payment-history-cards">
            <?php foreach ($payments as $p): ?>
            <div class="ph-card">
                <div class="ph-invoice"><?= htmlspecialchars($p['invoice_title']) ?></div>
                <div class="ph-amount"><?= format_currency((float) $p['amount']) ?></div>
                <div class="ph-method"><?= ucfirst(str_replace('_', ' ', $p['payment_method'] ?? '—')) ?></div>
                <div class="ph-date"><?= date('M j, Y', strtotime($p['payment_date'])) ?></div>
                <?php if (!empty($p['reference_no'])): ?>
                <div class="ph-ref">Ref: <?= htmlspecialchars($p['reference_no']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function showInvoice(inv) {
    var invNo = inv.invoice_no || 'INV-' + String(inv.id).padStart(4, '0');
    var html = '<div class="invoice-print">';
    html += '<div style="text-align:center;margin-bottom:1rem;"><h2 style="margin:0;color:#166534;">GREEN HOMES</h2>';
    html += '<p style="margin:0;color:#64748b;font-size:.85rem;">Design-to-build home construction</p></div>';
    html += '<div style="display:flex;justify-content:space-between;margin-bottom:1rem;">';
    html += '<div><strong>Invoice:</strong> ' + invNo + '<br><strong>Date:</strong> <?= date("M j, Y") ?></div>';
    html += '<div style="text-align:right;"><strong>Due:</strong> ' + new Date(inv.due_date).toLocaleDateString('en-IN', {year:'numeric',month:'short',day:'numeric'}) + '<br><strong>Status:</strong> ' + inv.status.toUpperCase() + '</div></div>';
    html += '<table><thead><tr><th>Description</th><th style="text-align:right;">Amount</th></tr></thead><tbody>';
    html += '<tr><td>' + (inv.title || 'Project Payment') + '</td><td style="text-align:right;">₹' + parseFloat(inv.amount).toLocaleString('en-IN', {minimumFractionDigits:2}) + '</td></tr>';
    html += '</tbody></table>';
    html += '<div style="text-align:right;font-weight:bold;font-size:1.1rem;margin-top:.5rem;">Total: ₹' + parseFloat(inv.amount).toLocaleString('en-IN', {minimumFractionDigits:2}) + '</div>';
    <?php if ($gstin): ?>
    html += '<p style="margin-top:1rem;font-size:.8rem;color:#64748b;">GSTIN: <?= htmlspecialchars($gstin) ?></p>';
    <?php endif; ?>
    html += '<p style="margin-top:1.5rem;font-size:.8rem;color:#64748b;">This is a computer-generated invoice.</p></div>';
    document.getElementById('invoiceContent').innerHTML = html;
    document.getElementById('invoiceModal').classList.add('active');
}
</script>

<?php portal_footer(); ?>
