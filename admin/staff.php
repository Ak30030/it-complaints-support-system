<?php
session_start();
require_once __DIR__ . '/../db/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$error = '';
$success = '';

// -------------------- Add new staff account --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone_number'] ?? '');
    $role     = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === '' || $fullName === '' || $phone === '' || $role === '' || $password === '') {
        $error = "All fields are required.";
    } elseif (!in_array($role, ['Admin', 'IT Staff'], true)) {
        $error = "Invalid role selected.";
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO it_staff (username, password, full_name, phone_number, role)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $hash, $fullName, $phone, $role]);
            $success = "Account for \"$fullName\" created.";
        } catch (PDOException $e) {
            $error = ($e->getCode() === '23000')
                ? "That username is already taken."
                : "Could not create account.";
        }
    }
}

// -------------------- Reset a staff member's password --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    $staffId  = $_POST['staff_id'] ?? '';
    $password = $_POST['new_password'] ?? '';

    if ($staffId === '' || $password === '') {
        $error = "Please provide a new password.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE it_staff SET password = ? WHERE staff_id = ?");
        $stmt->execute([$hash, $staffId]);
        $success = "Password updated.";
    }
}

// -------------------- Update a staff member's phone number --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_phone') {
    $staffId = $_POST['staff_id'] ?? '';
    $phone   = trim($_POST['new_phone'] ?? '');

    if ($staffId === '' || $phone === '') {
        $error = "Please provide a phone number.";
    } else {
        $stmt = $pdo->prepare("UPDATE it_staff SET phone_number = ? WHERE staff_id = ?");
        $stmt->execute([$phone, $staffId]);
        $success = "Phone number updated.";
    }
}

// -------------------- Delete a staff account --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $staffId = $_POST['staff_id'] ?? '';

    if ($staffId !== '') {
        if ((int)$staffId === (int)$_SESSION['staff_id']) {
            $error = "You can't delete your own account while logged in.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM it_staff WHERE staff_id = ?");
            $stmt->execute([$staffId]);
            $success = "Account removed.";
        }
    }
}

$staff = $pdo->query("SELECT * FROM it_staff ORDER BY role, full_name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="font-display mb-4">IT Staff</h2>

<?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row g-4">

    <div class="col-lg-4">
        <div class="itcs-card p-4">
            <h3 class="font-display" style="font-size:1.2rem;">Add IT staff</h3>
            <form method="POST" class="mt-3">
                <input type="hidden" name="action" value="add">

                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone number</label>
                    <input type="text" name="phone_number" class="form-control" placeholder="e.g. 0244123456" required>
                    <div class="form-text">Used for SMS alerts — Ghana format (0XX... or 233XX...)</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="" disabled selected>Select a role</option>
                        <option value="Admin">Admin</option>
                        <option value="IT Staff">IT Staff</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="text" name="password" class="form-control" placeholder="Set a login password" required>
                </div>

                <button type="submit" class="btn btn-accent w-100 py-2">Add Account</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="itcs-card p-4">
            <h3 class="font-display mb-3" style="font-size:1.2rem;">All staff (<?= count($staff) ?>)</h3>

            <?php if (empty($staff)): ?>
                <p class="text-muted mb-0">No staff accounts yet.</p>
            <?php else: ?>
                <input type="text" id="searchStaff" name="search" class="form-control form-control-sm live-search mb-3"
                       data-target="#staffTable" placeholder="Search staff...">
                <div class="table-responsive">
                    <table class="table align-middle" id="staffTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($staff as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['full_name']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($u['username']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($u['phone_number']) ?></td>
                                <td><span class="badge <?= $u['role'] === 'Admin' ? 'bg-resolved' : 'bg-secondary' ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#phoneModal<?= $u['staff_id'] ?>">
                                        Edit phone
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#resetModal<?= $u['staff_id'] ?>">
                                        Reset password
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Remove this staff account? This cannot be undone.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="staff_id" value="<?= $u['staff_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                <?= (int)$u['staff_id'] === (int)$_SESSION['staff_id'] ? 'disabled' : '' ?>>
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modals live outside the table -->
                <?php foreach ($staff as $u): ?>
                    <div class="modal fade" id="phoneModal<?= $u['staff_id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_phone">
                                    <input type="hidden" name="staff_id" value="<?= $u['staff_id'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title font-display">Edit phone — <?= htmlspecialchars($u['full_name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label class="form-label">Phone number</label>
                                        <input type="text" name="new_phone" class="form-control" value="<?= htmlspecialchars($u['phone_number']) ?>" required>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-accent">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="resetModal<?= $u['staff_id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="staff_id" value="<?= $u['staff_id'] ?>">
                                    <div class="modal-header">
                                        <h5 class="modal-title font-display">Reset password — <?= htmlspecialchars($u['full_name']) ?></h5>
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