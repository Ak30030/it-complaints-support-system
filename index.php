<?php
session_start();
require_once __DIR__ . '/db/db_connect.php';
require_once __DIR__ . '/includes/auth.php';
require_staff();

// Complaint status counts
$statusCounts = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM complaints
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$pending    = $statusCounts['Pending']     ?? 0;
$inProgress = $statusCounts['In Progress'] ?? 0;
$resolved   = $statusCounts['Resolved']    ?? 0;
$total      = $pending + $inProgress + $resolved;

// Per-department breakdown
$byDepartment = $pdo->query("
    SELECT
        d.department_id,
        d.name AS department_name,
        SUM(CASE WHEN c.status = 'Pending'     THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN c.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN c.status = 'Resolved'    THEN 1 ELSE 0 END) AS resolved,
        COUNT(c.complaint_id) AS total
    FROM departments d
    LEFT JOIN complaints c ON c.department_id = d.department_id
    GROUP BY d.department_id, d.name
    ORDER BY d.name
")->fetchAll();

// Recent complaints
$recentComplaints = $pdo->query("
    SELECT c.subject, c.status, c.date_reported, d.name AS department_name
    FROM complaints c
    JOIN departments d ON d.department_id = c.department_id
    ORDER BY c.date_reported DESC
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2 class="font-display mb-4">Dashboard</h2>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="itcs-stat">
            <div class="text-muted small">Total complaints</div>
            <div class="fs-3 fw-semibold"><span class="count-up" data-target="<?= $total ?>">0</span></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="itcs-stat is-warning">
            <div class="text-muted small">Pending</div>
            <div class="fs-3 fw-semibold"><span class="count-up" data-target="<?= $pending ?>">0</span></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="itcs-stat">
            <div class="text-muted small">Resolved</div>
            <div class="fs-3 fw-semibold"><span class="count-up" data-target="<?= $resolved ?>">0</span></div>
        </div>
    </div>
</div>

<div class="itcs-card p-4 mb-4">
    <h3 class="font-display mb-3" style="font-size:1.2rem;">Complaints by department</h3>

    <?php if (empty($byDepartment)): ?>
        <p class="text-muted mb-0">No departments added yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th class="text-center">Pending</th>
                        <th class="text-center">In Progress</th>
                        <th class="text-center">Resolved</th>
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byDepartment as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['department_name']) ?></td>
                            <td class="text-center"><span class="badge bg-pending"><?= (int)$row['pending'] ?></span></td>
                            <td class="text-center"><span class="badge bg-progress"><?= (int)$row['in_progress'] ?></span></td>
                            <td class="text-center"><span class="badge bg-resolved"><?= (int)$row['resolved'] ?></span></td>
                            <td class="text-center fw-semibold"><?= (int)$row['total'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="itcs-card p-4">
    <h3 class="font-display mb-3" style="font-size:1.2rem;">Recent complaints</h3>

    <?php if (empty($recentComplaints)): ?>
        <p class="text-muted mb-0">No complaints have been submitted yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentComplaints as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['department_name']) ?></td>
                            <td><?= htmlspecialchars($c['subject']) ?></td>
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
                            <td class="text-muted"><?= htmlspecialchars($c['date_reported']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>