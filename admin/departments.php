<?php
session_start();
require_once __DIR__ . '/../db/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$error = '';
$success = '';

// -------------------- Add new department --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name     = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $password === '') {
        $error = "All fields are required.";
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO departments (name, password) VALUES (?, ?)");
            $stmt->execute([$name, $hash]);
            $success = "Department \"$name\" added.";
        } catch (PDOException $e) {
            $error = ($e->getCode() === '23000')
                ? "A department with that name already exists."
                : "Could not add department.";
        }
    }
}

// -------------------- Reset a department's password --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    $deptId   = $_POST['department_id'] ?? '';
    $password = $_POST['new_password'] ?? '';

    if ($deptId === '' || $password === '') {
        $error = "Please provide a new password.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE departments SET password = ? WHERE department_id = ?");
        $stmt->execute([$hash, $deptId]);
        $success = "Password updated.";
    }
}

// -------------------- Delete a department --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deptId = $_POST['department_id'] ?? '';
    if ($deptId !== '') {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE department_id = ?");
        $stmt->execute([$deptId]);
        $success = "Department removed.";
    }
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="font-display mb-0">Departments</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row g-4">

    <div class="col-lg-4">
        <div class="itcs-card p-4">
            <h3 class="font-display" style="font-size:1.2rem;">Add a department</h3>
            <form method="POST" class="mt-3">
                <input type="hidden" name="action" value="add">

                <div class="mb-3">
                    <label class="form-label">Department name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Radiology" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Department password</label>
                    <input type="text" name="password" class="form-control" placeholder="Set a login password" required>
                </div>

                <button type="submit" class="btn btn-accent w-100 py-2">Add Department</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="itcs-card p-4">
            <h3 class="font-display mb-3" style="font-size:1.2rem;">All departments (<?= count($departments) ?>)</h3>

            <?php if (empty($departments)): ?>
                <p class="text-muted mb-0">No departments added yet — use the form to add your first one.</p>
            <?php else: ?>
                <input type="text" id="searchDepartments" name="search" class="form-control form-control-sm live-search mb-3"
                       data-target="#departmentsTable" placeholder="Search departments...">
                <div class="table-responsive">
                    <table class="table align-middle" id="departmentsTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><?= htmlspecialchars($dept['name']) ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#resetModal<?= $dept['department_id'] ?>">
                                        Reset password
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Remove this department? This cannot be undone.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="department_id" value="<?= $dept['department_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modals live outside the table -->
                <?php foreach ($departments as $dept): ?>
                    <div class="modal fade" id="resetModal<?= $dept['department_id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="department_id" value="<?= $dept['department_id'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title font-display">Reset password — <?= htmlspecialchars($dept['name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label class="form-label">New password</label>
                                        <input type="text" name="new_password" class="form-control" required>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-accent">Update Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>