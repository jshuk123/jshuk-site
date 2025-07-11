<?php
// Start output buffering and session management
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once '../config/config.php';

    // Check admin access
    function checkAdminAccess() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../auth/login.php');
            ob_end_clean();
            exit();
        }

        global $pdo;
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || $user['role'] !== 'admin') {
            header('Location: ../index.php');
            ob_end_clean();
            exit();
        }
    }

    checkAdminAccess();

    // Handle user actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            $userId = $_POST['user_id'] ?? null;
            switch ($_POST['action']) {
                case 'toggle_status':
                    if ($userId == $_SESSION['user_id']) {
                        $_SESSION['admin_error'] = "You cannot suspend your own account.";
                        break;
                    }

                    $stmt = $pdo->prepare("SELECT COALESCE(is_active, 1) as is_active FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $currentStatus = $stmt->fetchColumn();

                    $stmt = $pdo->prepare("UPDATE users SET is_active = ?, suspended_at = ? WHERE id = ?");
                    $newStatus = !$currentStatus;
                    $suspendedAt = $newStatus ? null : date('Y-m-d H:i:s');
                    $stmt->execute([$newStatus, $suspendedAt, $userId]);

                    $_SESSION['admin_message'] = "User has been " . ($newStatus ? 'activated' : 'suspended') . " successfully.";
                    header("Location: users.php");
                    ob_end_clean();
                    exit();

                case 'change_role':
                    if ($userId == $_SESSION['user_id']) {
                        $_SESSION['admin_error'] = "You cannot change your own role.";
                        break;
                    }

                    $newRole = $_POST['role'] ?? 'user';
                    if (in_array($newRole, ['user', 'admin'])) {
                        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $stmt->execute([$newRole, $userId]);
                        $_SESSION['admin_message'] = "User role updated successfully.";
                    }
                    header("Location: users.php");
                    ob_end_clean();
                    exit();
            }
        }
    }

    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $total = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPages = ceil($total / $limit);
    
    // Enhanced query with fallback for missing columns
    $query = "SELECT *, 
                     COALESCE(is_active, 1) as is_active,
                     COALESCE(is_banned, 0) as is_banned,
                     COALESCE(ban_reason, '') as ban_reason
              FROM users 
              ORDER BY created_at DESC 
              LIMIT $limit OFFSET $offset";
    $users = $pdo->query($query)->fetchAll();

} catch (Exception $e) {
    error_log("Admin users page error: " . $e->getMessage());
    ob_end_clean();
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<h1>Error</h1>";
        echo "<p>An error occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
    } else {
        header("Location: ../500.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - JSHUK Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f9f9f9;
        }
        .sidebar {
            min-height: 100vh;
            background: #002244;
        }
        .nav-link {
            color: #fff;
        }
        .nav-link:hover, .nav-link.active {
            background: #f5c518;
            color: #000 !important;
        }
        .card-header {
            border-left: 5px solid #002244;
        }
        .btn {
            border-radius: 20px;
        }
        .badge {
            border-radius: 12px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0 sidebar">
            <div class="d-flex flex-column p-3">
                <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                    <span class="fs-4">JSHUK Admin</span>
                </a>
                <hr class="text-white">
                <ul class="nav nav-pills flex-column mb-auto">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="nav-link active">
                            <i class="fas fa-users me-2"></i>Users
                        </a>
                    </li>
                    <li>
                        <a href="businesses.php" class="nav-link">
                            <i class="fas fa-store me-2"></i>Businesses
                        </a>
                    </li>
                    <li>
                        <a href="categories.php" class="nav-link">
                            <i class="fas fa-tags me-2"></i>Categories
                        </a>
                    </li>
                    <li>
                        <a href="reviews.php" class="nav-link">
                            <i class="fas fa-star me-2"></i>Reviews
                        </a>
                    </li>
                </ul>
                <hr class="text-white">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i>
                        <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                        <li><a class="dropdown-item" href="../profile.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Sign out</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">User Management</h1>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 text-primary">Users</h6>
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" id="searchUser" placeholder="Search users...">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Ban Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-success' : 'btn-danger'; ?>" onclick="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Suspended'; ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_banned']): ?>
                                            <span class="badge bg-danger">Banned</span>
                                            <?php if ($user['ban_reason']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($user['ban_reason']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <?php if ($user['is_banned']): ?>
                                                <span class="badge bg-danger">Banned</span>
                                                <form action="actions/unban_user.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to unban this user?');">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-user-check"></i> Unban
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#banModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-user-slash"></i> Ban
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ban User Modals -->
<?php foreach ($users as $user): ?>
    <?php if ($user['id'] !== $_SESSION['user_id'] && !$user['is_banned']): ?>
    <div class="modal fade" id="banModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="banModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="banModalLabel<?php echo $user['id']; ?>">Ban User: <?php echo htmlspecialchars($user['username']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="actions/ban_user.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <p>Are you sure you want to ban <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
                        <div class="mb-3">
                            <label for="ban_reason<?php echo $user['id']; ?>" class="form-label">Ban Reason (Optional)</label>
                            <textarea class="form-control" id="ban_reason<?php echo $user['id']; ?>" name="ban_reason" rows="3" placeholder="Enter the reason for banning this user..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Ban User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('searchUser').addEventListener('keyup', function () {
        const searchText = this.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(searchText) ? '' : 'none';
        });
    });
</script>

<?php if (isset($_SESSION['admin_message'])): ?>
<div class="alert alert-success alert-dismissible fade show m-3" role="alert">
    <?php echo $_SESSION['admin_message']; unset($_SESSION['admin_message']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['admin_error'])): ?>
<div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
    <?php echo $_SESSION['admin_error']; unset($_SESSION['admin_error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
</body>
</html>
<?php
// End output buffering and flush
ob_end_flush();
?>

