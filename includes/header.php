<?php
// includes/header.php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';


$appTitle = isset($page_title) ? $page_title . ' - SIM Dosen UMB' : 'SIM Dosen - Universitas Muhammadiyah Bengkulu';
$user = is_logged_in() ? current_user() : null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($appTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --umb-blue: #1458b8;
            --umb-blue-dark: #0b2d59;
            --umb-blue-light: #eaf2ff;
            --umb-blue-soft: #f4f8fe;
            --umb-gold: #f59e0b;
            --text-dark: #1b2a47;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 0px;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--umb-blue-soft);
            color: var(--text-dark);
            min-height: 100vh;
            margin: 0;
        }
        /* Layout Structure */
        .app-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }
        .app-sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            border-right: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1040;
            overflow-y: auto;
            overflow-x: hidden;
            transition: width 0.25s ease-in-out, transform 0.25s ease-in-out, box-shadow 0.25s ease-in-out;
            display: flex;
            flex-direction: column;
        }
        /* Sidebar collapsed (desktop) */
        .app-sidebar.collapsed {
            width: 0;
            border-right: none;
        }
        .app-main {
            flex: 1 1 auto;
            width: calc(100% - var(--sidebar-width));
            max-width: calc(100% - var(--sidebar-width));
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 100vh;
            transition: margin-left 0.25s ease-in-out, width 0.25s ease-in-out, max-width 0.25s ease-in-out;
        }
        .app-main.sidebar-collapsed {
            margin-left: 0;
            width: 100%;
            max-width: 100%;
        }
        /* Backdrop (mobile + overlay mode) */
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(11,45,89,0.4);
            backdrop-filter: blur(2px);
            z-index: 1030;
            display: none;
        }
        .sidebar-backdrop.show {
            display: block;
        }
        /* Mobile: sidebar overlaps instead of pushing */
        @media (max-width: 991.98px) {
            .app-sidebar {
                width: var(--sidebar-width);
                transform: translateX(-100%);
            }
            .app-sidebar.show {
                transform: translateX(0);
                box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            }
            .app-sidebar.collapsed {
                width: var(--sidebar-width);
                transform: translateX(-100%);
            }
            .app-main {
                width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
            }
        }
        /* Sidebar Brand Row */
        .sidebar-brand-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            padding-right: 0.75rem;
            flex-shrink: 0;
        }
        .sidebar-brand {
            padding: 1.1rem 1rem 1.1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--umb-blue-dark);
            flex: 1;
            min-width: 0;
        }
        .sidebar-brand:hover { color: var(--umb-blue); }
        .sidebar-brand-text {
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar-close-btn {
            width: 34px;
            height: 34px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: #f8fafc;
            color: #64748b;
            display: grid;
            place-items: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: all 0.15s;
            font-size: 0.95rem;
        }
        .sidebar-close-btn:hover {
            background: var(--umb-blue-light);
            color: var(--umb-blue);
            border-color: var(--umb-blue);
        }
        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--umb-blue), var(--umb-blue-dark));
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .sidebar-heading {
            font-size: 0.70rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            font-weight: 700;
            padding: 1.1rem 1.5rem 0.35rem;
            white-space: nowrap;
        }
        .nav-sidebar {
            padding: 0.4rem 0.85rem;
        }
        .nav-sidebar .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.62rem 0.95rem;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.875rem;
            color: #64748b;
            transition: all 0.15s ease;
            margin-bottom: 2px;
            white-space: nowrap;
        }
        .nav-sidebar .nav-link:hover {
            background-color: var(--umb-blue-light);
            color: var(--umb-blue);
        }
        /* Hanya menu AKTIF yang berwarna biru */
        .nav-sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--umb-blue), #104a9b);
            color: #ffffff !important;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(20,88,184,0.22);
        }
        .nav-sidebar .nav-link.active i {
            color: #ffffff !important;
        }
        .nav-sidebar .nav-link i {
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        /* Navbar */
        .app-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        /* Hamburger button (always visible) */
        .sidebar-hamburger {
            width: 38px;
            height: 38px;
            border: 1px solid var(--border-color);
            border-radius: 9px;
            background: #f8fafc;
            color: #475569;
            display: grid;
            place-items: center;
            cursor: pointer;
            transition: all 0.15s;
            font-size: 1.15rem;
            flex-shrink: 0;
        }
        .sidebar-hamburger:hover {
            background: var(--umb-blue-light);
            color: var(--umb-blue);
            border-color: var(--umb-blue);
        }
        /* Content area */
        .app-content {
            padding: 1.75rem 1.5rem;
            flex: 1;
        }
        /* Card enhancements */
        .card {
            border: 1px solid var(--border-color);
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(11,45,89,0.03);
        }
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
            padding: 1rem 1.25rem;
        }
        /* Modern Buttons */
        .btn-primary {
            background: var(--umb-blue);
            border-color: var(--umb-blue);
            font-weight: 600;
            border-radius: 9px;
            padding: 0.55rem 1rem;
        }
        .btn-primary:hover {
            background: #104a9b;
            border-color: #104a9b;
        }
    </style>
</head>
<body>
<div class="app-layout">
