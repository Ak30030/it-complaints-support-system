<?php
session_start();
require_once __DIR__ . '/db/db_connect.php';

// If already logged in, send straight to the right place
if (isset($_SESSION['department_id'])) {
    header("Location: complaints/submit.php");
    exit;
}
if (isset($_SESSION['staff_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginType = $_POST['login_type'] ?? '';

    if ($loginType === 'department') {
        $deptId   = $_POST['department_id'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($deptId === '' || $password === '') {
            $error = "Please select your department and enter the password.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM departments WHERE department_id = ?");
            $stmt->execute([$deptId]);
            $dept = $stmt->fetch();

            if ($dept && password_verify($password, $dept['password'])) {
                session_regenerate_id(true);
                $_SESSION['department_id']   = $dept['department_id'];
                $_SESSION['department_name'] = $dept['name'];
                header("Location: complaints/submit.php");
                exit;
            } else {
                $error = "Incorrect department or password.";
            }
        }

    } elseif ($loginType === 'staff') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = "Please enter your username and password.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM it_staff WHERE username = ?");
            $stmt->execute([$username]);
            $staff = $stmt->fetch();

            if ($staff && password_verify($password, $staff['password'])) {
                session_regenerate_id(true);
                $_SESSION['staff_id']   = $staff['staff_id'];
                $_SESSION['username']   = $staff['username'];
                $_SESSION['full_name']  = $staff['full_name'];
                $_SESSION['role']       = $staff['role'];
                header("Location: index.php");
                exit;
            } else {
                $error = "Incorrect username or password.";
            }
        }

    } else {
        $error = "Please choose a login type.";
    }
}

$departments = $pdo->query("SELECT department_id, name FROM departments ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign in — IT Complaints Support System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/css/style.css" rel="stylesheet">
</head>
<body>

<div class="auth-shell">

    <!-- Left: hero / brand panel -->
    <div class="auth-hero">
        <svg class="auth-hero__mark" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M28 4C15 4 5 13 5 25c0 5 2 9 5 12l-2 11 12-6c2.6 0.6 5.3 1 8 1 13 0 23-9 23-21S41 4 28 4z"
                  stroke="#F2B705" stroke-width="2.5"/>
            <path d="M17 26h6l3 6 4-12 3 8 2-4h6" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>

        <h1 class="font-display">IT Complaints Support System</h1>
        <p>Departments report issues in one click — and IT gets notified by SMS immediately, even away from the desk.</p>

        <svg class="auth-hero__pulse" viewBox="0 0 500 100" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 60 L100 60 L120 20 L145 90 L165 60 L500 60" stroke="#ffffff" stroke-width="2" fill="none"/>
        </svg>
    </div>

    <!-- Right: login form -->
    <div class="auth-form-side">
        <div class="auth-card">
            <div class="auth-card__eyebrow">Welcome back</div>
            <h2 class="font-display">Sign in to continue</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="segmented" id="loginSegments">
                <button type="button" class="is-active" data-target="dept-pane">Department</button>
                <button type="button" data-target="staff-pane">IT Staff / Admin</button>
            </div>

            <div id="dept-pane">
                <form method="POST" action="login.php">
                    <input type="hidden" name="login_type" value="department">

                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select" required>
                            <option value="" disabled selected>Select your department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['department_id'] ?>">
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-accent w-100 py-2">Sign In</button>
                </form>
            </div>

            <div id="staff-pane" style="display:none;">
                <form method="POST" action="login.php">
                    <input type="hidden" name="login_type" value="staff">

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary-green w-100 py-2">Sign In</button>
                </form>
            </div>

        </div>
    </div>

</div>

<script>
document.querySelectorAll('#loginSegments button').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#loginSegments button').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        document.getElementById('dept-pane').style.display = 'none';
        document.getElementById('staff-pane').style.display = 'none';
        document.getElementById(btn.dataset.target).style.display = 'block';
    });
});
</script>

</body>
</html>