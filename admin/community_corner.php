<?php
/**
 * Community Corner Admin
 * Manage community corner content
 */

require_once '../config/config.php';
require_once '../includes/community_corner_functions.php';

// Check if user is admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO community_corner (title, body_text, type, emoji, link_url, link_text, is_featured, is_active, priority, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['body_text'],
                        $_POST['type'],
                        $_POST['emoji'],
                        $_POST['link_url'],
                        $_POST['link_text'],
                        isset($_POST['is_featured']) ? 1 : 0,
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['priority'],
                        $_SESSION['user_id']
                    ]);
                    $message = 'Community corner item added successfully!';
                } catch (PDOException $e) {
                    $error = 'Error adding item: ' . $e->getMessage();
                }
                break;
                
            case 'update':
                try {
                    $stmt = $pdo->prepare("
                        UPDATE community_corner 
                        SET title = ?, body_text = ?, type = ?, emoji = ?, link_url = ?, link_text = ?, 
                            is_featured = ?, is_active = ?, priority = ?, expire_date = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['body_text'],
                        $_POST['type'],
                        $_POST['emoji'],
                        $_POST['link_url'],
                        $_POST['link_text'],
                        isset($_POST['is_featured']) ? 1 : 0,
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['priority'],
                        $_POST['expire_date'] ?: null,
                        $_POST['id']
                    ]);
                    $message = 'Community corner item updated successfully!';
                } catch (PDOException $e) {
                    $error = 'Error updating item: ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                try {
                    $stmt = $pdo->prepare("DELETE FROM community_corner WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $message = 'Community corner item deleted successfully!';
                } catch (PDOException $e) {
                    $error = 'Error deleting item: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get all community corner items
try {
    $stmt = $pdo->query("
        SELECT cc.*, u.username as created_by_name, a.username as approved_by_name
        FROM community_corner cc
        LEFT JOIN users u ON cc.created_by = u.id
        LEFT JOIN users a ON cc.approved_by = a.id
        ORDER BY cc.priority DESC, cc.date_added DESC
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching items: ' . $e->getMessage();
    $items = [];
}

$typeInfo = getCommunityCornerTypeInfo();
$pageTitle = "Community Corner Admin";
include '../includes/header_main.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Community Corner Admin</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <!-- Add New Item Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Add New Community Corner Item</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type</label>
                                    <select class="form-control" id="type" name="type" required>
                                        <?php foreach ($typeInfo as $type => $info): ?>
                                            <option value="<?= $type ?>"><?= $info['name'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="emoji" class="form-label">Emoji</label>
                                    <input type="text" class="form-control" id="emoji" name="emoji" value="❤️" maxlength="10">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority (Higher = More Important)</label>
                                    <input type="number" class="form-control" id="priority" name="priority" value="5" min="0" max="100">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="body_text" class="form-label">Content</label>
                            <textarea class="form-control" id="body_text" name="body_text" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="link_url" class="form-label">Link URL (Optional)</label>
                                    <input type="url" class="form-control" id="link_url" name="link_url">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="link_text" class="form-label">Link Text</label>
                                    <input type="text" class="form-control" id="link_text" name="link_text" value="Learn More →">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" checked>
                                    <label class="form-check-label" for="is_featured">
                                        Featured (Show on homepage)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">Add Item</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Existing Items Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Manage Community Corner Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Content</th>
                                    <th>Featured</th>
                                    <th>Active</th>
                                    <th>Priority</th>
                                    <th>Views</th>
                                    <th>Clicks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= $item['id'] ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= $typeInfo[$item['type']]['name'] ?? $item['type'] ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($item['title']) ?></td>
                                        <td>
                                            <small><?= htmlspecialchars(substr($item['body_text'], 0, 50)) ?>...</small>
                                        </td>
                                        <td>
                                            <?php if ($item['is_featured']): ?>
                                                <span class="badge bg-success">Yes</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($item['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $item['priority'] ?></td>
                                        <td><?= $item['views_count'] ?></td>
                                        <td><?= $item['clicks_count'] ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editItem(<?= $item['id'] ?>)">
                                                Edit
                                            </button>
                                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Community Corner Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm" method="POST" action="">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="edit_title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_type" class="form-label">Type</label>
                                <select class="form-control" id="edit_type" name="type" required>
                                    <?php foreach ($typeInfo as $type => $info): ?>
                                        <option value="<?= $type ?>"><?= $info['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_emoji" class="form-label">Emoji</label>
                                <input type="text" class="form-control" id="edit_emoji" name="emoji" maxlength="10">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_priority" class="form-label">Priority</label>
                                <input type="number" class="form-control" id="edit_priority" name="priority" min="0" max="100">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="edit_expire_date" class="form-label">Expire Date (Optional)</label>
                                <input type="date" class="form-control" id="edit_expire_date" name="expire_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_body_text" class="form-label">Content</label>
                        <textarea class="form-control" id="edit_body_text" name="body_text" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_link_url" class="form-label">Link URL</label>
                                <input type="url" class="form-control" id="edit_link_url" name="link_url">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_link_text" class="form-label">Link Text</label>
                                <input type="text" class="form-control" id="edit_link_text" name="link_text">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_featured" name="is_featured">
                                <label class="form-check-label" for="edit_is_featured">
                                    Featured
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">Update Item</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editItem(id) {
    // Fetch item data and populate modal
    fetch(`/api/get_community_corner_item.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = data.item;
                document.getElementById('edit_id').value = item.id;
                document.getElementById('edit_title').value = item.title;
                document.getElementById('edit_type').value = item.type;
                document.getElementById('edit_emoji').value = item.emoji;
                document.getElementById('edit_priority').value = item.priority;
                document.getElementById('edit_body_text').value = item.body_text;
                document.getElementById('edit_link_url').value = item.link_url || '';
                document.getElementById('edit_link_text').value = item.link_text || '';
                document.getElementById('edit_is_featured').checked = item.is_featured == 1;
                document.getElementById('edit_is_active').checked = item.is_active == 1;
                document.getElementById('edit_expire_date').value = item.expire_date || '';
                
                new bootstrap.Modal(document.getElementById('editModal')).show();
            }
        })
        .catch(error => console.error('Error:', error));
}
</script>

<?php include '../includes/footer_main.php'; ?> 