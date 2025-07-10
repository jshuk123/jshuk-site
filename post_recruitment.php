<?php
session_start();
require_once 'config/config.php';
include 'config/header.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $location = $_POST['location'] ?? '';
    $description = $_POST['description'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';

    if ($title && $location && $description && $contact_email) {
        $stmt = $pdo->prepare("INSERT INTO recruitment (title, location, description, contact_email, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
        $stmt->execute([$title, $location, $description, $contact_email]);
        $message = '✅ Job listing submitted successfully!';
    } else {
        $message = '❌ Please fill in all fields.';
    }
}
?>

<div class="container py-10 px-4 mx-auto max-w-xl">
  <h1 class="text-2xl font-bold mb-4">Post a Job</h1>
  <?php if ($message): ?>
    <div class="mb-4 text-sm font-semibold text-blue-700"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <div>
      <label class="block font-medium">Job Title</label>
      <input type="text" name="title" class="w-full border rounded p-2" required>
    </div>
    <div>
      <label class="block font-medium">Location</label>
      <input type="text" name="location" class="w-full border rounded p-2" required>
    </div>
    <div>
      <label class="block font-medium">Job Description</label>
      <textarea name="description" class="w-full border rounded p-2" rows="4" required></textarea>
    </div>
    <div>
      <label class="block font-medium">Contact Email</label>
      <input type="email" name="contact_email" class="w-full border rounded p-2" required>
    </div>
    <div>
      <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Submit</button>
    </div>
  </form>
</div>

<?php include 'config/footer.php'; ?>
