<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

// Only Admin or Registrar (or new Infrastructure role)
if (!hasRole('admin') && !hasRole('registrar')) {
    header("Location: ../../index.php");
    exit();
}

$pdo = getDBConnection();
$message = '';

// Handle Campus Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_campus'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];
    // Logo upload logic skipped for brevity, just text update

    // Check if exists
    $count = $pdo->query("SELECT COUNT(*) FROM campus_details")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO campus_details (name, address) VALUES (?, ?)");
        $stmt->execute([$name, $address]);
    } else {
        $stmt = $pdo->prepare("UPDATE campus_details SET name = ?, address = ?");
        $stmt->execute([$name, $address]);
    }
    $message = "Campus details updated.";
}

// Handle Add Building
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_building'])) {
    $name = $_POST['building_name'];
    $block = $_POST['block_code'];
    try {
        $stmt = $pdo->prepare("INSERT INTO buildings (name, block_code) VALUES (?, ?)");
        $stmt->execute([$name, $block]);
        $message = "Building added.";
    } catch (PDOException $e) { $message = "Error: " . $e->getMessage(); }
}

// Handle Add Classroom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_classroom'])) {
    $b_id = $_POST['building_id'];
    $floor = $_POST['floor_no'];
    $room = $_POST['room_no'];
    $cap = $_POST['capacity'];
    $type = $_POST['type'];
    $side = $_POST['side'];

    try {
        $stmt = $pdo->prepare("INSERT INTO classrooms (building_id, floor_no, room_no, capacity, type, side) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$b_id, $floor, $room, $cap, $type, $side]);
        $message = "Classroom added.";
    } catch (PDOException $e) { $message = "Error: " . $e->getMessage(); }
}

// Fetch Data
$campus = $pdo->query("SELECT * FROM campus_details LIMIT 1")->fetch();
$buildings = $pdo->query("SELECT * FROM buildings ORDER BY name")->fetchAll();
$classrooms = $pdo->query("
    SELECT c.*, b.name as building_name, b.block_code
    FROM classrooms c
    JOIN buildings b ON c.building_id = b.id
    ORDER BY b.name, c.room_no
")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <h2 class="mb-4">Infrastructure Management</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Campus Details -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">Campus Details</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">University Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($campus['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($campus['address'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" name="update_campus" class="btn btn-sm btn-primary w-100">Update Campus Info</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Buildings -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <span>Buildings & Blocks</span>
                    <button class="btn btn-sm btn-light text-success" data-bs-toggle="modal" data-bs-target="#addBuildingModal"><i class="fas fa-plus"></i> Add</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 250px; overflow-y:auto;">
                        <table class="table table-sm table-hover">
                            <thead><tr><th>Name</th><th>Block</th></tr></thead>
                            <tbody>
                                <?php foreach ($buildings as $b): ?>
                                <tr>
                                    <td><?= htmlspecialchars($b['name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($b['block_code']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Classrooms -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Classrooms & Exam Halls</h5>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addClassroomModal">
                <i class="fas fa-plus"></i> Add Classroom
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Building</th>
                            <th>Room No</th>
                            <th>Floor</th>
                            <th>Capacity</th>
                            <th>Type</th>
                            <th>Side</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classrooms as $room): ?>
                        <tr>
                            <td><?= htmlspecialchars($room['building_name']) ?> (<?= htmlspecialchars($room['block_code']) ?>)</td>
                            <td><strong><?= htmlspecialchars($room['room_no']) ?></strong></td>
                            <td><?= htmlspecialchars($room['floor_no']) ?></td>
                            <td><?= htmlspecialchars($room['capacity']) ?></td>
                            <td><?= htmlspecialchars($room['type']) ?></td>
                            <td><?= htmlspecialchars($room['side']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Building Modal -->
<div class="modal fade" id="addBuildingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Building</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Building Name</label>
                        <input type="text" name="building_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Block Code</label>
                        <input type="text" name="block_code" class="form-control" required placeholder="A, B, Science Block...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_building" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Classroom Modal -->
<div class="modal fade" id="addClassroomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Classroom</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Building</label>
                        <select name="building_id" class="form-select" required>
                            <?php foreach ($buildings as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label>Room No</label>
                            <input type="text" name="room_no" class="form-control" required placeholder="101">
                        </div>
                        <div class="col-6 mb-3">
                            <label>Floor</label>
                            <select name="floor_no" class="form-select">
                                <option>Ground</option>
                                <option>1st</option>
                                <option>2nd</option>
                                <option>3rd</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label>Capacity</label>
                            <input type="number" name="capacity" class="form-control" value="30" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label>Side (LHS/RHS)</label>
                            <select name="side" class="form-select">
                                <option value="LHS">LHS</option>
                                <option value="RHS">RHS</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Type</label>
                        <select name="type" class="form-select">
                            <option>Lecture Hall</option>
                            <option>Lab</option>
                            <option>Seminar Hall</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add_classroom" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
