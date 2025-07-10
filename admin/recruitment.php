<?php
session_start();
require_once '../config/config.php';

// Only allow admins
if (empty($_SESSION['is_admin'])) {
    header('Location: ../index.php');
    exit;
}

// Approve or reject logic
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $pdo->prepare("UPDATE recruitment SET status = 'active' WHERE id = ?")->execute([$id]);
    header('Location: recruitment.php');
    exit;
}
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $pdo->prepare("UPDATE recruitment SET status = 'rejected' WHERE id = ?")->execute([$id]);
    header('Location: recruitment.php');
    exit;
}

// Handle delete and mark as filled actions
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM recruitment WHERE id = ?")->execute([$id]);
    header('Location: recruitment.php');
    exit;
}
if (isset($_GET['filled'])) {
    $id = (int)$_GET['filled'];
    $pdo->prepare("UPDATE recruitment SET status = 'filled', filled_at = NOW() WHERE id = ?")->execute([$id]);
    header('Location: recruitment.php');
    exit;
}

// Handle user job removal (if admin is also the poster)
if (isset($_GET['remove']) && isset($_SESSION['user_email'])) {
    $id = (int)$_GET['remove'];
    $stmt = $pdo->prepare("DELETE FROM recruitment WHERE id = ? AND contact_email = ?");
    $stmt->execute([$id, $_SESSION['user_email']]);
    header('Location: recruitment.php');
    exit;
}

// Fetch all sectors for dropdown and display
$sectors = $pdo->query("SELECT * FROM job_sectors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sector_map = [];
foreach ($sectors as $sector) {
    $sector_map[$sector['id']] = $sector;
}

// Fetch pending jobs
$pending = $pdo->query("SELECT * FROM recruitment WHERE status = 'pending' AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY created_at DESC")->fetchAll();

// Fetch recently filled jobs (last 7 days)
$recently_filled = $pdo->query("SELECT * FROM recruitment WHERE status = 'filled' AND filled_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY filled_at DESC")->fetchAll();

// Fetch active jobs (not expired)
$active_jobs = $pdo->query("SELECT * FROM recruitment WHERE status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin - Pending Jobs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container py-5">
    <h1 class="mb-4">Pending Job Listings</h1>
    <?php if (empty($pending)): ?>
        <div class="alert alert-info">No pending jobs.</div>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Posted</th>
                    <th>Remuneration</th>
                    <th>Skills</th>
                    <th>Sector</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pending as $job): ?>
                <tr>
                    <td><?= htmlspecialchars($job['job_title'] ?? 'Untitled') ?></td>
                    <td><?= htmlspecialchars($job['company']) ?></td>
                    <td><?= htmlspecialchars($job['location']) ?></td>
                    <td><?= htmlspecialchars($job['created_at']) ?></td>
                    <td><?= htmlspecialchars($job['remuneration'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($job['skills'] ?? 'N/A') ?></td>
                    <td>
                        <?php if (isset($sector_map[$job['sector_id']])): ?>
                            <i class="fa-solid <?= htmlspecialchars($sector_map[$job['sector_id']]['icon']) ?> me-1"></i>
                            <?= htmlspecialchars($sector_map[$job['sector_id']]['name']) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?approve=<?= $job['id'] ?>" class="btn btn-success btn-sm">Approve</a>
                        <a href="?reject=<?= $job['id'] ?>" class="btn btn-danger btn-sm">Reject</a>
                        <a href="?filled=<?= $job['id'] ?>" class="btn btn-warning btn-sm">Mark as Filled</a>
                        <a href="?delete=<?= $job['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this job?')">Delete</a>
                        <?php if (isset($_SESSION['user_email']) && $_SESSION['user_email'] === $job['contact_email']): ?>
                            <a href="?remove=<?= $job['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove this job?')">Remove (User)</a>
                        <?php endif; ?>
                        <?php if ($job['status'] === 'filled' && !empty($job['filled_at']) && strtotime($job['filled_at']) >= strtotime('-7 days')): ?>
                            <span class="badge bg-success ms-2">Success Story</span>
                        <?php endif; ?>
                        <a href="#" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $job['id'] ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <h2 class="mt-5">Active Jobs</h2>
    <?php if (empty($active_jobs)): ?>
        <div class="alert alert-info">No active jobs.</div>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Posted</th>
                    <th>Remuneration</th>
                    <th>Skills</th>
                    <th>Sector</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($active_jobs as $job): ?>
                <tr>
                    <td><?= htmlspecialchars($job['job_title'] ?? 'Untitled') ?></td>
                    <td><?= htmlspecialchars($job['company']) ?></td>
                    <td><?= htmlspecialchars($job['location']) ?></td>
                    <td><?= htmlspecialchars($job['created_at']) ?></td>
                    <td><?= htmlspecialchars($job['remuneration'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($job['skills'] ?? 'N/A') ?></td>
                    <td>
                        <?php if (isset($sector_map[$job['sector_id']])): ?>
                            <i class="fa-solid <?= htmlspecialchars($sector_map[$job['sector_id']]['icon']) ?> me-1"></i>
                            <?= htmlspecialchars($sector_map[$job['sector_id']]['name']) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?filled=<?= $job['id'] ?>" class="btn btn-warning btn-sm">Mark as Filled</a>
                        <a href="?delete=<?= $job['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this job?')">Delete</a>
                        <a href="#" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $job['id'] ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <h2 class="mt-5">Recently Filled Jobs (last 7 days)</h2>
    <?php if (empty($recently_filled)): ?>
        <div class="alert alert-info">No jobs filled in the last week.</div>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Filled At</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recently_filled as $job): ?>
                <tr>
                    <td><?= htmlspecialchars($job['job_title'] ?? 'Untitled') ?></td>
                    <td><?= htmlspecialchars($job['company']) ?></td>
                    <td><?= htmlspecialchars($job['location']) ?></td>
                    <td><?= htmlspecialchars($job['filled_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Edit Modal for each job -->
<?php foreach (array_merge($pending, $active_jobs) as $job): ?>
<div class="modal fade" id="editModal<?= $job['id'] ?>" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post">
      <input type="hidden" name="edit_id" value="<?= $job['id'] ?>">
      <div class="modal-header"><h5 class="modal-title">Edit Job</h5></div>
      <div class="modal-body">
        <input class="form-control mb-2" name="job_title" value="<?= htmlspecialchars($job['job_title']) ?>" placeholder="Job Title" required>
        <input class="form-control mb-2" name="company" value="<?= htmlspecialchars($job['company']) ?>" placeholder="Company">
        <input class="form-control mb-2" name="location" value="<?= htmlspecialchars($job['location']) ?>" placeholder="Location">
        <textarea class="form-control mb-2" name="description" placeholder="Description" required><?= htmlspecialchars($job['description']) ?></textarea>
        <input class="form-control mb-2" name="remuneration" value="<?= htmlspecialchars($job['remuneration']) ?>" placeholder="Remuneration">
        <textarea class="form-control mb-2" name="skills" placeholder="Skills Needed"><?= htmlspecialchars($job['skills']) ?></textarea>
        <label for="sector_id_<?= $job['id'] ?>" class="form-label">Sector</label>
        <select class="form-select mb-2" id="sector_id_<?= $job['id'] ?>" name="sector_id">
            <option value="">Select a sector</option>
            <?php foreach ($sectors as $sector): ?>
                <option value="<?= $sector['id'] ?>" <?= ($job['sector_id'] == $sector['id']) ? 'selected' : '' ?>><?= htmlspecialchars($sector['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input class="form-control mb-2" name="expires_at" type="datetime-local" value="<?= $job['expires_at'] ? date('Y-m-d\TH:i', strtotime($job['expires_at'])) : '' ?>" placeholder="Expiry Date">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<!-- Handle job edit POST -->
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = (int)$_POST['edit_id'];
    $fields = [
        'job_title' => $_POST['job_title'],
        'company' => $_POST['company'],
        'location' => $_POST['location'],
        'description' => $_POST['description'],
        'remuneration' => $_POST['remuneration'],
        'skills' => $_POST['skills'],
        'sector_id' => $_POST['sector_id'] ? (int)$_POST['sector_id'] : null,
        'expires_at' => $_POST['expires_at'] ? date('Y-m-d H:i:s', strtotime($_POST['expires_at'])) : null
    ];
    $sql = "UPDATE recruitment SET job_title = :job_title, company = :company, location = :location, description = :description, remuneration = :remuneration, skills = :skills, sector_id = :sector_id, expires_at = :expires_at WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $fields['id'] = $id;
    $stmt->execute($fields);
    header('Location: recruitment.php');
    exit;
} ?>
</body>
</html> 