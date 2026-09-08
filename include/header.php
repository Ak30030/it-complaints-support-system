<?php
// Assumes session_start() and db_connect.php have already run (BASE_URL defined).
$isDept  = isset($_SESSION['department_id']);
$isStaff = isset($_SESSION['staff_id']);
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IT Complaints Support System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="<?= BASE_URL ?>/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark itcs-navbar mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
            <i class="fa-solid fa-headset me-2"></i>IT Support
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                <?php if ($isStaff): ?>
                    <li class="nav-item"><a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/complaints/list.php">Complaints</a></li>
                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/departments.php">Departments</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/staff.php">IT Staff</a></li>
                    <?php endif; ?>
                <?php elseif ($isDept): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/complaints/submit.php">My Complaints</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <?php if ($isStaff): ?>
                    <li class="nav-item"><span class="nav-link text-white-50"><?= htmlspecialchars($_SESSION['full_name']) ?> · <?= htmlspecialchars($_SESSION['role']) ?></span></li>
                <?php elseif ($isDept): ?>
                    <li class="nav-item"><span class="nav-link text-white-50"><?= htmlspecialchars($_SESSION['department_name']) ?></span></li>
                <?php endif; ?>
                <?php if ($isStaff || $isDept): ?>
                    <li class="nav-item"><a class="btn btn-sm btn-accent ms-lg-2" href="<?= BASE_URL ?>/logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container pb-5 itcs-fade-in">