<?php
/**
 * Auth guards
 * Include after session_start() AND after db_connect.php (BASE_URL
 * must already be defined), before any page output.
 *
 * Usage:
 *   session_start();
 *   require_once __DIR__ . '/../db/db_connect.php';
 *   require_once __DIR__ . '/../includes/auth.php';
 *   require_department();      // department complaint pages
 *   require_staff();           // any Admin or IT Staff page
 *   require_admin();           // Admin-only pages (manage departments/staff)
 */

function require_department(): void
{
    if (!isset($_SESSION['department_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

function require_staff(): void
{
    if (!isset($_SESSION['staff_id'])) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

function require_admin(): void
{
    require_staff();
    if ($_SESSION['role'] !== 'Admin') {
        http_response_code(403);
        die("Access denied. Admins only.");
    }
}