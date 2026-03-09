<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [
    'total_entries' => $db->query("SELECT COUNT(*) FROM whitelist_entries")->fetchColumn(),
    'active_entries' => $db->query("SELECT COUNT(*) FROM whitelist_entries WHERE status = 'active'")->fetchColumn(),
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'expiring_soon' => $db->query("SELECT COUNT(*) FROM whitelist_entries WHERE expires_at IS NOT NULL AND expires_at > NOW() AND expires_at < DATE_ADD(NOW(), INTERVAL 7 DAY)")->fetchColumn()
];

// Get recent entries
$recent_entries = $db->query("
    SELECT w.*, u.username 
    FROM whitelist_entries w 
    LEFT JOIN users u ON w.user_id = u.id 
    ORDER BY w.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WhiteList Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --border-color: #334155;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--card-bg);
            height: 100vh;
            position: fixed;
            border-right: 1px solid var(--border-color);
            padding: 30px 20px;
            overflow-y: auto;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .sidebar-header h2 {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.8rem;
        }

        .sidebar-header p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .nav-link i {
            width: 20px;
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--primary-color);
            color: white;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--card-bg);
            border-radius: 15px;
            border: 1px solid var(--border-color);
        }

        .page-title h1 {
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info span {
            color: var(--text-secondary);
        }

        .logout-btn {
            padding: 10px 20px;
            background: var(--danger-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
        }

        .stat-info h3 {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .stat-info .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--text-primary);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.5rem;
            color: white;
        }

        /* Recent Entries Table */
        .recent-entries {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-size: 1.3rem;
        }

        .add-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-decoration: none;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.3s ease;
        }

        .add-btn:hover {
            transform: translateY(-2px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 10px;
            color: var(--text-secondary);
            font-weight: 500;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 15px 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--dark-bg);
            color: var(--text-secondary);
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .edit-btn:hover {
            background: var(--warning-color);
            color: white;
        }

        .delete-btn:hover {
            background: var(--danger-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>WhiteList Panel</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="whitelist.php" class="nav-link">
                    <i class="fas fa-list"></i> Whitelist Entries
                </a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a href="logs.php" class="nav-link">
                    <i class="fas fa-history"></i> Activity Logs
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Dashboard</h1>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo $_SESSION['role']; ?>)</span>
                <a href="?logout=1" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Entries</h3>
                    <div class="stat-number"><?php echo $stats['total_entries']; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-database"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Active Entries</h3>
                    <div class="stat-number"><?php echo $stats['active_entries']; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Total Users</h3>
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <h3>Expiring Soon</h3>
                    <div class="stat-number"><?php echo $stats['expiring_soon']; ?></div>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>

        <div class="recent-entries">
            <div class="section-header">
                <h2>Recent Whitelist Entries</h2>
                <a href="whitelist.php?add=1" class="add-btn">
                    <i class="fas fa-plus"></i> Add New
                </a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Identifier</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_entries as $entry): ?>
                    <tr>
                        <td>#<?php echo $entry['id']; ?></td>
                        <td><?php echo htmlspecialchars($entry['identifier']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $entry['type'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $entry['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo ucfirst($entry['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($entry['username'] ?? 'System'); ?></td>
                        <td><?php echo date('d M Y', strtotime($entry['created_at'])); ?></td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn edit-btn" onclick="editEntry(<?php echo $entry['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn delete-btn" onclick="deleteEntry(<?php echo $entry['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function editEntry(id) {
            window.location.href = 'whitelist.php?edit=' + id;
        }

        function deleteEntry(id) {
            if (confirm('Are you sure you want to delete this entry?')) {
                window.location.href = 'whitelist.php?delete=' + id;
            }
        }
    </script>
</body>
</html>