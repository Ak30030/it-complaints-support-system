<?php
session_start();
require_once __DIR__ . '/../db/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_department();
require_once __DIR__ . '/../sms/nalo_sms.php';

$deptId = $_SESSION['department_id'];
$error = '';
$success = '';

// -------------------- Submit a new complaint --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit') {
    $subject     = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($subject === '' || $description === '') {
        $error = "Please fill in both the subject and description.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO complaints (department_id, subject, description)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$deptId, $subject, $description]);
        $complaintId = $pdo->lastInsertId();

        // Fire the SMS gateway — every IT staff member gets notified now,
        // regardless of whether they're at their desk.
        $message = "New IT complaint from {$_SESSION['department_name']}: \"$subject\". Log in to view details.";
        notify_all_it_staff($pdo, $message, $complaintId);

        $success = "Your complaint has been submitted and IT has been notified by SMS.";
    }
}

// This department's complaint history
$history = $pdo->prepare("
    SELECT complaint_id, subject, description, status, date_reported, date_resolved
    FROM complaints
    WHERE department_id = ?
    ORDER BY date_reported DESC
");
$history->execute([$deptId]);
$history = $history->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="font-display mb-1">My Complaints</h2>
<p class="text-muted mb-4"><?= htmlspecialchars($_SESSION['department_name']) ?></p>

<?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row g-4">

    <div class="col-lg-4">
        <div class="itcs-card p-4">
            <h3 class="font-display" style="font-size:1.2rem;">Report an issue</h3>
            <form method="POST" class="mt-3">
                <input type="hidden" name="action" value="submit">

                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="e.g. Network down in ward" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Describe the issue in detail" required></textarea>
                </div>

                <button type="submit" class="btn btn-accent w-100 py-2">Submit Complaint</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="itcs-card p-4">
            <h3 class="font-display mb-3" style="font-size:1.2rem;">History</h3>

            <?php if (empty($history)): ?>
                <p class="text-muted mb-0">You haven't submitted any complaints yet.</p>
            <?php else: ?>
                <input type="text" id="searchMyComplaints" name="search" class="form-control form-control-sm live-search mb-3"
                       data-target="#myComplaintsTable" placeholder="Search your complaints...">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="myComplaintsTable">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Date Reported</th>
                                <th>Status</th>
                                <th>Date Resolved</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['subject']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($row['date_reported']) ?></td>
                                    <td>
                                        <?php
                                            $badgeClass = match($row['status']) {
                                                'Pending' => 'bg-pending',
                                                'In Progress' => 'bg-progress',
                                                'Resolved' => 'bg-resolved',
                                                default => 'bg-secondary'
                                            };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['status']) ?></span>
                                    </td>
                                    <td class="text-muted"><?= htmlspecialchars($row['date_resolved'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>