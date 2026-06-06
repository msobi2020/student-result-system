<?php
require_once 'includes/config.php';
requireLogin();

$message = '';
$msg_type = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM students WHERE id = $id");
    $message = "Student deleted successfully.";
    $msg_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $student_id = sanitize($conn, $_POST['student_id']);
    $full_name = sanitize($conn, $_POST['full_name']);
    $class = sanitize($conn, $_POST['class']);
    $gender = sanitize($conn, $_POST['gender']);
    $dob = sanitize($conn, $_POST['date_of_birth']);

    if (empty($student_id) || empty($full_name) || empty($class) || empty($gender)) {
        $message = "Please fill all required fields.";
        $msg_type = 'danger';
    } else {
        if ($id > 0) {
            $sql = "UPDATE students SET 
                student_id='$student_id', full_name='$full_name',
                class='$class', gender='$gender', date_of_birth='$dob'
                WHERE id=$id";
            $conn->query($sql);
            $message = "Student updated successfully!";
        } else {
            $sql = "INSERT INTO students (student_id, full_name, class, gender, date_of_birth) 
                    VALUES ('$student_id', '$full_name', '$class', '$gender', '$dob')";
            if ($conn->query($sql)) {
                $message = "Student added successfully!";
            } else {
                $message = "Error: Student ID may already exist.";
                $msg_type = 'danger';
            }
        }
        if (empty($msg_type)) $msg_type = 'success';
    }
}

$search = sanitize($conn, $_GET['search'] ?? '');
$where = $search ? "WHERE full_name LIKE '%$search%' OR student_id LIKE '%$search%' OR class LIKE '%$search%'" : '';
$students = $conn->query("SELECT * FROM students $where ORDER BY created_at DESC");

$edit_student = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM students WHERE id = $edit_id");
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_student = $edit_result->fetch_assoc();
    }
}

$last = $conn->query("SELECT student_id FROM students ORDER BY id DESC LIMIT 1")->fetch_assoc();
$next_num = $last ? (intval(substr($last['student_id'], -3)) + 1) : 1;
$next_id = 'STD-' . date('Y') . '-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

$classes = ['Form 1', 'Form 2', 'Form 3', 'Form 4'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - Student Result System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>Students</h1>
                <p>Manage O-Level student records</p>
            </div>
            <button class="btn btn-primary btn-sm" onclick="openModal('addModal')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Add Student
            </button>
        </div>

        <div class="page-content">
            <?php if ($message): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="GET" class="search-bar">
                <div class="search-input">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" name="search" placeholder="Search by name, ID or class..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="btn btn-secondary">Search</button>
                <?php if ($search): ?>
                <a href="student.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>

            <div class="card">
                <div class="card-header">
                    <h2>All Students
                        <span style="font-size:13px; font-weight:400; color:var(--text-muted);">
                            (<?= $students ? $students->num_rows : 0 ?> found)
                        </span>
                    </h2>
                </div>

                <?php if ($students && $students->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student ID</th>
                                <th>Full Name</th>
                                <th>Class</th>
                                <th>Gender</th>
                                <th>Date of Birth</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; while($s = $students->fetch_assoc()): ?>
                            <tr>
                                <td style="color:var(--text-muted); font-size:13px;"><?= $i++ ?></td>
                                <td><code style="background:var(--bg); padding:3px 8px; border-radius:5px; font-size:12px;"><?= htmlspecialchars($s['student_id']) ?></code></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="width:32px; height:32px; border-radius:50%; background:<?= $s['gender']=='Female' ? '#fce7f3' : '#dbeafe' ?>; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:<?= $s['gender']=='Female' ? '#9d174d' : '#1e40af' ?>;">
                                            <?= strtoupper(substr($s['full_name'], 0, 1)) ?>
                                        </div>
                                        <span style="font-weight:500;"><?= htmlspecialchars($s['full_name']) ?></span>
                                    </div>
                                </td>
                                <td><span class="badge badge-amber"><?= htmlspecialchars($s['class']) ?></span></td>
                                <td>
                                    <span class="badge <?= $s['gender']=='Female' ? 'badge-pink' : 'badge-blue' ?>">
                                        <?= $s['gender'] ?>
                                    </span>
                                </td>
                                <td style="color:var(--text-muted); font-size:13px;"><?= $s['date_of_birth'] ?? '—' ?></td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="result.php?student=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">Results</a>
                                        <a href="?edit=<?= $s['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="?delete=<?= $s['id'] ?>" class="btn btn-danger btn-sm"
                                           onclick="return confirm('Delete <?= htmlspecialchars($s['full_name']) ?>? This will also delete all their results.')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align:center; padding:60px 20px; color:var(--text-muted);">
                    <p style="font-size:15px;">No students found.</p>
                    <button class="btn btn-primary" style="margin-top:16px;" onclick="openModal('addModal')">Add First Student</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay <?= !$edit_student && isset($_GET['action']) && $_GET['action']=='add' ? 'active' : '' ?>" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Add New Student</h2>
            <button class="modal-close" onclick="closeModal('addModal')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="id" value="0">
            <div class="form-group">
                <label>Student ID *</label>
                <input type="text" name="student_id" value="<?= $next_id ?>" required>
            </div>
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" placeholder="e.g. James Nkanda Msobi" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Class *</label>
                    <select name="class" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c ?>"><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Gender *</label>
                    <select name="gender" required>
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth">
            </div>
            <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:8px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Student</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<?php if ($edit_student): ?>
<div class="modal-overlay active" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Edit Student</h2>
            <a href="student.php" class="modal-close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </a>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?= $edit_student['id'] ?>">
            <div class="form-group">
                <label>Student ID *</label>
                <input type="text" name="student_id" value="<?= htmlspecialchars($edit_student['student_id']) ?>" required>
            </div>
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($edit_student['full_name']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Class *</label>
                    <select name="class" required>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c ?>" <?= $c == $edit_student['class'] ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Gender *</label>
                    <select name="gender" required>
                        <option value="Male" <?= $edit_student['gender']=='Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $edit_student['gender']=='Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" value="<?= $edit_student['date_of_birth'] ?>">
            </div>
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <a href="student.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-warning">Update Student</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});
</script>
</body>
</html>