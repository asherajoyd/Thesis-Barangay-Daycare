<?php 
    $pageTitle = "Schedules";

    require_once '../guard.php';
    include '../layout/header.php';
    include "../../../api/conn.php";

    // Handle Add / Edit Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? '';
        $time_slot = $_POST['time_slot'] ?? '';
        $duration = $_POST['duration'] ?? '';
        $category = $_POST['category'] ?? '';
        $activity = $_POST['activity'] ?? '';

        if (!empty($id)) {
            // Update existing schedule
            $stmt = $conn->prepare("UPDATE schedules SET time_slot = ?, duration = ?, category = ?, activity = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $time_slot, $duration, $category, $activity, $id);
            $stmt->execute();
        } else {
            // Add new schedule
            $stmt = $conn->prepare("INSERT INTO schedules (time_slot, duration, category, activity) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $time_slot, $duration, $category, $activity);
            $stmt->execute();
        }
        echo "<script>window.location.href = 'index.php';</script>";
        exit;
    }

    // Handle Delete
    if (isset($_GET['delete'])) {
        $id = $_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo "<script>window.location.href = 'index.php';</script>";
        exit;
    }

    // Fetch Schedules from DB
    $result = $conn->query("SELECT * FROM schedules");
?>

<!-- Import Google Font: DM Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

<style>
    :root {
        --primary-green: #1b5e20;
        --light-green: #e8f5e9;
    }

    body, .schedule-container {
        font-family: 'DM Sans', sans-serif !important;
    }
    
    .schedule-card {
        border: 1px solid #dee2e6;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }

    .time-badge {
        background-color: var(--light-green);
        color: var(--primary-green);
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        display: inline-block;
    }

    .category-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .cat-play { background-color: #e3f2fd; color: #1976d2; }
    .cat-learning { background-color: #f3e5f5; color: #7b1fa2; }
    .cat-snack { background-color: #fff3e0; color: #e65100; }
    .cat-routine { background-color: #e8f5e9; color: #2e7d32; }

    .custom-schedule-table, 
    .custom-schedule-table th, 
    .custom-schedule-table td {
        border: 1px solid #d1d5db !important;
    }
</style>

<div class="container-fluid px-0 schedule-container">
    <!-- Header Section -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
        <div>
            <h2 class="fw-bold fs-3 text-dark mb-1">Daily Schedule & Routine</h2>
            <p class="text-muted small mb-0">Little Daisy Child Development Center • S.Y. 2026–2027</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary fw-semibold px-3 py-2 rounded-3 d-flex align-items-center gap-2" onclick="window.print();">
                <i class="bi bi-printer"></i> Print Schedule
            </button>
            <button type="button" class="btn text-white fw-semibold px-3 py-2 rounded-3 d-flex align-items-center gap-2" style="background-color: var(--primary-green);" data-bs-toggle="modal" data-bs-target="#scheduleModal" onclick="openAddModal()">
                <i class="bi bi-plus-lg"></i> Add Schedule
            </button>
        </div>
    </div>

    <!-- Academic Year Info Card -->
    <div class="card schedule-card mb-4 bg-white">
        <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 text-white d-flex align-items-center justify-content-center" style="background-color: var(--primary-green); width: 48px; height: 48px;">
                    <i class="bi bi-clock-history fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-medium">Active Academic Session</div>
                    <div class="fw-bold fs-5 text-dark">Afternoon Batch (12:45 PM - 3:00 PM)</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-light text-dark border px-3 py-2 rounded-3">
                    <i class="bi bi-calendar3 me-1"></i> S.Y. 2026 - 2027
                </span>
            </div>
        </div>
    </div>

    <!-- Schedule Table Card -->
    <div class="card schedule-card overflow-hidden bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover align-middle mb-0 custom-schedule-table">
                    <thead style="background-color: #f1f3f5;" class="text-dark small fw-bold text-uppercase">
                        <tr>
                            <th class="ps-4 py-3" style="width: 25%;">Time Slot</th>
                            <th class="text-center py-3" style="width: 25%;">Category</th>
                            <th class="ps-4 py-3" style="width: 35%;">Activity</th>
                            <th class="text-center py-3" style="width: 15%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php 
                                    // Piliin ang kulay ng badge depende sa category
                                    $catClass = 'cat-routine';
                                    $catLower = strtolower($row['category']);
                                    if (strpos($catLower, 'play') !== false) $catClass = 'cat-play';
                                    elseif (strpos($catLower, 'learn') !== false) $catClass = 'cat-learning';
                                    elseif (strpos($catLower, 'snack') !== false || strpos($catLower, 'feed') !== false) $catClass = 'cat-snack';
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="time-badge"><i class="bi bi-clock me-1"></i> <?= htmlspecialchars($row['time_slot']) ?></span>
                                        <div class="text-muted small mt-1 ms-1"><?= htmlspecialchars($row['duration']) ?></div>
                                    </td>
                                    <td class="text-center"><span class="category-badge <?= $catClass ?>"><?= htmlspecialchars($row['category']) ?></span></td>
                                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($row['activity']) ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-warning btn-sm text-dark fw-bold px-2 py-1 me-1" 
                                                onclick='openEditModal(<?= json_encode($row) ?>)'>
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <a href="index.php?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm text-white fw-bold px-2 py-1" onclick="return confirm('Are you sure you want to delete this schedule?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No schedules found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark" id="modalTitle">Add New Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id" id="scheduleId">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Time Slot</label>
                    <input type="text" class="form-control" name="time_slot" id="timeSlot" placeholder="e.g. 12:45 - 1:00 PM" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Duration</label>
                    <input type="text" class="form-control" name="duration" id="duration" placeholder="e.g. 15 Mins" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Category</label>
                    <input type="text" class="form-control" name="category" id="category" placeholder="e.g. Free Play, Routine, Learning" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Activity Name</label>
                    <input type="text" class="form-control" name="activity" id="activity" placeholder="e.g. ARRIVAL / FREE PLAY" required>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success px-4" style="background-color: var(--primary-green);">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Add New Schedule';
        document.getElementById('scheduleId').value = '';
        document.getElementById('timeSlot').value = '';
        document.getElementById('duration').value = '';
        document.getElementById('category').value = '';
        document.getElementById('activity').value = '';
    }

    function openEditModal(data) {
        document.getElementById('modalTitle').innerText = 'Edit Schedule';
        document.getElementById('scheduleId').value = data.id;
        document.getElementById('timeSlot').value = data.time_slot;
        document.getElementById('duration').value = data.duration;
        document.getElementById('category').value = data.category;
        document.getElementById('activity').value = data.activity;

        var myModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
        myModal.show();
    }
</script>

<?php include '../layout/footer.php'; ?>