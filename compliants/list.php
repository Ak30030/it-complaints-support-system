<?php
session_start();
require_once __DIR__ . '/../db/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_staff();

$error = '';
$success = '';

// -------------------- Update complaint status --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $complaintId = $_POST['complaint_id'] ?? '';
    $status      = $_POST['status'] ?? '';

    if ($complaintId !== '' && in_array($status, ['Pending', 'In Progress', 'Resolved'], true)) {
        if ($status === 'Resolved') {
            $stmt = $pdo->prepare("
                UPDATE complaints
                SET status = ?, date_resolved = NOW(), handled_by = ?
                WHERE complaint_id = ?
            ");
        } else {
            $stmt = $pdo->prepare("
                UPDATE complaints
                SET status = ?, date_resolved = NULL, handled_by = ?
                WHERE complaint_id = ?
            ");
        }
        $stmt->execute([$status, $_SESSION['staff_id'], $complaintId]);
        $success = "Complaint status updated.";
    } else {
        $error = "Could not update status.";
    }
}

// -------------------- Filters --------------------
$filterStatus = $_GET['status'] ?? '';
$filterDept   = $_GET['department_id'] ?? '';

$sql = "
    SELECT c.*, d.name AS department_name, u.full_name AS handled_by_name
    FROM complaints c
    JOIN departments d ON d.department_id = c.department_id
    LEFT JOIN it_staff u ON u.staff_id = c.handled_by
    WHERE 1=1
";
$params = [];

if ($filterStatus !== '') {
    $sql .= " AND c.status = ?";
    $params[] = $filterStatus;
}
if ($filterDept !== '') {
    $sql .= " AND c.department_id = ?";
    $params[] = $filterDept;
}
$sql .= " ORDER BY c.date_reported DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$departments = $pdo->query("SELECT department_id, name FROM departments ORDER BY name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="font-display mb-4">Complaints</h2>

<?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="itcs-card p-4">

    <form method="GET" class="row g-2 mb-3">
        <div class="col-6 col-md-5">
            <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['department_id'] ?>" <?= $filterDept == $dept['department_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-4">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All statuses</option>
                <option value="Pending" <?= $filterStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="In Progress" <?= $filterStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="Resolved" <?= $filterStatus === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
            </select>
        </div>
        <div class="col-md-3">
            <a href="list.php" class="btn btn-sm btn-outline-secondary w-100">Clear filters</a>
        </div>
    </form>

    <input type="text" id="searchComplaints" name="search" class="form-control form-control-sm live-search mb-3"
           data-target="#complaintsTable" placeholder="Search this list...">

    <?php if (empty($complaints)): ?>
        <p class="text-muted mb-0">No complaints match this filter.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle" id="complaintsTable">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Subject</th>
                        <th>Reported</th>
                        <th>Status</th>
                        <th>Handled By</th>
                        <th class="text-end">Update</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($complaints as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['department_name']) ?></td>
                        <td>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#viewModal<?= $c['complaint_id'] ?>">
                                <?= htmlspecialchars($c['subject']) ?>
                            </a>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($c['date_reported']) ?></td>
                        <td>
                            <?php
                                $badgeClass = match($c['status']) {
                                    'Pending' => 'bg-pending',
                                    'In Progress' => 'bg-progress',
                                    'Resolved' => 'bg-resolved',
                                    default => 'bg-secondary'
                                };
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($c['status']) ?></span>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($c['handled_by_name'] ?? '—') ?></td>
                        <td class="text-end">
                            <form method="POST" class="d-flex gap-1 justify-content-end">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="complaint_id" value="<?= $c['complaint_id'] ?>">
                                <select name="status" class="form-select form-select-sm" style="width:auto;">
                                    <option value="Pending" <?= $c['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="In Progress" <?= $c['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Resolved" <?= $c['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-accent">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Modals live outside the table -->
        <?php foreach ($complaints as $c): ?>
            <div class="modal fade" id="viewModal<?= $c['complaint_id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title font-display"><?= htmlspecialchars($c['subject']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-1"><?= htmlspecialchars($c['department_name']) ?> · <?= htmlspecialchars($c['date_reported']) ?></p>
                            <p><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>