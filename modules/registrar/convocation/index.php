<?php
session_start();
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/auth.php';

// Check Login & Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'registrar') {
    header("Location: ../../../login.php");
    exit();
}

$pdo = getDBConnection();
$message = '';

// Handle Create Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $title = $_POST['title'];
    $event_date = $_POST['event_date'];
    $venue = $_POST['venue'];
    $batch_year = $_POST['batch_year'];

    try {
        $stmt = $pdo->prepare("INSERT INTO convocation_events (title, event_date, venue, batch_year) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $event_date, $venue, $batch_year]);
        $message = "Convocation event created successfully!";
    } catch (PDOException $e) {
        $message = "Error creating event: " . $e->getMessage();
    }
}

// Fetch Events
$stmt = $pdo->query("SELECT * FROM convocation_events ORDER BY event_date DESC");
$events = $stmt->fetchAll();

require_once '../../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Convocation Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
            <i class="fas fa-plus"></i> Create New Event
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Event Title</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Batch Year</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($event['title']) ?></strong></td>
                            <td><?= htmlspecialchars($event['event_date']) ?></td>
                            <td><?= htmlspecialchars($event['venue']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($event['batch_year']) ?></span></td>
                            <td>
                                <span class="badge bg-<?= $event['status'] === 'Completed' ? 'success' : 'warning' ?>">
                                    <?= htmlspecialchars($event['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="manage_degrees.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-graduation-cap"></i> Manage Degrees
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No convocation events found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Convocation Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Event Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. 15th Annual Convocation">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Event Date</label>
                        <input type="date" name="event_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Venue</label>
                        <input type="text" name="venue" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Batch Year</label>
                        <input type="text" name="batch_year" class="form-control" required placeholder="e.g. 2023-2024">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_event" class="btn btn-primary">Create Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
