<?php
require_once 'includes/config.php';
requireLogin();

$message = '';
$msg_type = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM results WHERE id = $id");
    $message = "Result deleted successfully.";
    $msg_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $student_id = (int)$_POST['student_id'];
    $subject_id = (int)$_POST['subject_id'];
    $exam_year = sanitize($conn, $_POST['exam_year']);
    $term = sanitize($conn, $_POST['term']);
    $marks = (float)$_POST['marks_obtained'];
    $total = (float)$_POST['total_marks'];
    $grade = getGrade(($marks / $total) * 100);
    $remarks = sanitize($conn, $_POST['remarks']);

    if ($student_id < 1 || $subject_id < 1 || empty($exam_year) || empty($term)) {
        $message = "Please fill all required fields.";
        $msg_type = 'danger';
    } else {
        if ($id > 0) {
            $sql = "UPDATE results SET student_id=$student_id, subject_id=$subject_id,
                    exam_year='$exam_year', term='$term',
                    marks_obtained=$marks, total_marks=$total, grade='$grade', remarks='$remarks'
                    WHERE id=$id";
            $conn->query($sql);
            $message = "Result updated successfully!";
        } else {
            $sql = "INSERT INTO results (student_id, subject_id, exam_year, term, marks_obtained, total_marks, grade, remarks)
                    VALUES ($student_id, $subject_id, '$exam_year', '$term', $marks, $total, '$grade', '$remarks')";
            $conn->query($sql);
            $message = "Result recorded successfully!";
        }
        $msg_type = 'success';
    }
}

$all_students = $conn->query("SELECT id, student_id, full_name, class FROM students ORDER BY full_name");
$all_subjects = $conn->query("SELECT * FROM subjects ORDER BY class, subject_name");

$filter_student = isset($_GET['student']) ? (int)$_GET['student'] : 0;
$filter_year = sanitize($conn, $_GET['year'] ?? '');
$filter_term = sanitize($conn, $_GET['term'] ?? '');
$search = sanitize($conn, $_GET['search'] ?? '');

$where = [];
if ($filter_student > 0) $where[] = "s.id = $filter_student";
if ($filter_year) $where[] = "r.exam_year = '$filter_year'";
if ($filter_term) $where[] = "r.term = '$filter_term'";
if ($search) $where[] = "(s.full_name LIKE '%$search%' OR s.student_id LIKE '%$search%' OR sub.subject_name LIKE '%$search%')";
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

$results = $conn->query("
    SELECT r.*, s.full_name, s.student_id as sid, s.class, sub.subject_name, sub.subject_code
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN subjects sub ON r.subject_id = sub.id
    $where_sql
    ORDER BY r.exam_year DESC, s.full_name, sub.subject_name
");

$filter_student_name = '';
if ($filter_student > 0) {
    $sn = $conn->query("SELECT full_name, student_id FROM students WHERE id = $filter_student")->fetch_assoc();
    if ($sn) $filter_student_name = $sn['full_name'] . ' (' . $sn['student_id'] . ')';
}

$edit_result = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $er = $conn->query("SELECT * FROM results WHERE id = $edit_id");
    if ($er && $er->num_rows > 0) $edit_result = $er->fetch_assoc();
}

$years = range(date('Y'), date('Y') - 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - Student Result System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-header { display: block !important; }
            body { background: white; }
            .main-content { margin: 0; padding: 0; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }
        .print-header { display: none; text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar no-print">
            <div class="topbar-title">
                <h1>Results</h1>
                <p>Record and manage O-Level exam results</p>
            </div>
            <div style="display:flex; gap:10px;">
                <button onclick="window.print()" class="btn btn-secondary btn-sm">🖨 Print</button>
                <button class="btn btn-primary btn-sm" onclick="openModal('addModal')">+ Record Result</button>
            </div>
        </div>

        <div class="page-content">
            <div class="print-header">
                <h2 style="font-size:24px; font-weight:700;">STUDENT RESULT SYSTEM — O-LEVEL</h2>
                <p>Exam Results Report &mdash; <?= date('F Y') ?></p>
                <?php if ($filter_student_name): ?>
                <p><strong>Student:</strong> <?= htmlspecialchars($filter_student_name) ?></p>
                <?php endif; ?>
                <?php if ($filter_year): ?><p><strong>Year:</strong> <?= $filter_year ?></p><?php endif; ?>
                <?php if ($filter_term): ?><p><strong>Term:</strong> <?= $filter_term ?></p><?php endif; ?>
                <hr style="margin:12px 0;">
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?= $msg_type ?> no-print"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($filter_student_name): ?>
            <div class="alert alert-info no-print">
                Showing results for: <strong><?= htmlspecialchars($filter_student_name) ?></strong>
                <a href="result.php" style="margin-left:12px; color:var(--primary);">Clear filter</a>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <form method="GET" class="search-bar no-print">
                <div class="search-input">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" name="search" placeholder="Search student or subject..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <select name="year" style="padding:10px 14px; border:1.5px solid var(--border); border-radius:10px; font-family:Outfit; font-size:14px; background:white; outline:none;">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="term" style="padding:10px 14px; border:1.5px solid var(--border); border-radius:10px; font-family:Outfit; font-size:14px; background:white; outline:none;">
                    <option value="">All Terms</option>
                    <option value="Term 1" <?= $filter_term=='Term 1' ? 'selected' : '' ?>>Term 1</option>
                    <option value="Term 2" <?= $filter_term=='Term 2' ? 'selected' : '' ?>>Term 2</option>
                    <option value="Term 3" <?= $filter_term=='Term 3' ? 'selected' : '' ?>>Term 3</option>
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <a href="result.php" class="btn btn-secondary">Reset</a>
            </form>

            <div class="card">
                <div class="card-header">
                    <h2>Exam Results
                        <span style="font-size:13px; font-weight:400; color:var(--text-muted);">
                            (<?= $results ? $results->num_rows : 0 ?> records)
                        </span>
                    </h2>
                </div>

                <?php if ($results && $results->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Marks</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                                <th>Term</th>
                                <th>Year</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; while($r = $results->fetch_assoc()):
                                $pct = round(($r['marks_obtained'] / $r['total_marks']) * 100, 1);
                                $grade = getGrade($pct);
                                $gradeClass = getGradeClass($grade);
                            ?>
                            <tr>
                                <td style="color:var(--text-muted); font-size:13px;"><?= $i++ ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r['full_name']) ?></strong><br>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($r['sid']) ?></small>
                                </td>
                                <td><span class="badge badge-amber"><?= htmlspecialchars($r['class']) ?></span></td>
                                <td>
                                    <?= htmlspecialchars($r['subject_name']) ?><br>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($r['subject_code']) ?></small>
                                </td>
                                <td style="font-weight:600;"><?= $r['marks_obtained'] ?>/<?= $r['total_marks'] ?></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="flex:1; background:var(--bg); border-radius:4px; height:6px; min-width:60px;">
                                            <div style="width:<?= $pct ?>%; background:<?= $grade=='A'?'#059669':($grade=='B'?'#2563ab':($grade=='F'?'#dc2626':'#d97706')) ?>; height:100%; border-radius:4px;"></div>
                                        </div>
                                        <span style="font-size:13px; color:var(--text-muted); min-width:35px;"><?= $pct ?>%</span>
                                    </div>
                                </td>
                                <td><span class="<?= $gradeClass ?>"><?= $grade ?></span></td>
                                <td style="font-size:13px;"><?= htmlspecialchars($r['term']) ?></td>
                                <td style="font-size:13px; font-family:monospace;"><?= $r['exam_year'] ?></td>
                                <td class="no-print">
                                    <div style="display:flex; gap:6px;">
                                        <a href="?edit=<?= $r['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="?delete=<?= $r['id'] ?>" class="btn btn-danger btn-sm"
                                           onclick="return confirm('Delete this result?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align:center; padding:60px 20px; color:var(--text-muted);">
                    <p>No results found.</p>
                    <button class="btn btn-primary" style="margin-top:16px;" onclick="openModal('addModal')">Record First Result</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ADD RESULT MODAL -->
<div class="modal-overlay <?= !$edit_result && isset($_GET['action']) && $_GET['action']=='add' ? 'active' : '' ?>" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Record New Result</h2>
            <button class="modal-close" onclick="closeModal('addModal')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="id" value="0">
            <div class="form-group">
                <label>Student *</label>
                <select name="student_id" required>
                    <option value="">Select Student</option>
                    <?php
                    if ($all_students) { $all_students->data_seek(0); }
                    while ($s = $all_students->fetch_assoc()):
                    ?>
                    <option value="<?= $s['id'] ?>" <?= $filter_student == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['full_name']) ?> — <?= htmlspecialchars($s['student_id']) ?> (<?= $s['class'] ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Subject *</label>
                <select name="subject_id" required>
                    <option value="">Select Subject</option>
                    <?php
                    if ($all_subjects) { $all_subjects->data_seek(0); }
                    $cur_class = '';
                    while ($sub = $all_subjects->fetch_assoc()):
                        if ($cur_class != $sub['class']) {
                            if ($cur_class) echo '</optgroup>';
                            echo '<optgroup label="' . htmlspecialchars($sub['class']) . '">';
                            $cur_class = $sub['class'];
                        }
                    ?>
                    <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['subject_name']) ?> (<?= $sub['subject_code'] ?>)</option>
                    <?php endwhile; if ($cur_class) echo '</optgroup>'; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Marks Obtained *</label>
                    <input type="number" name="marks_obtained" min="0" max="100" step="0.5" placeholder="e.g. 75" required oninput="calcGrade(this)">
                </div>
                <div class="form-group">
                    <label>Total Marks *</label>
                    <input type="number" name="total_marks" min="1" value="100" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Year *</label>
                    <select name="exam_year" required>
                        <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Term *</label>
                    <select name="term" required>
                        <option value="Term 1">Term 1</option>
                        <option value="Term 2">Term 2</option>
                        <option value="Term 3">Term 3</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Remarks</label>
                <input type="text" name="remarks" placeholder="Optional remarks...">
            </div>
            <div id="grade-preview" style="display:none; padding:12px; background:var(--bg); border-radius:8px; margin-bottom:16px; text-align:center; font-weight:600; font-size:15px;"></div>
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Result</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT RESULT MODAL -->
<?php if ($edit_result): ?>
<div class="modal-overlay active" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h2>Edit Result</h2>
            <a href="result.php" class="modal-close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </a>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?= $edit_result['id'] ?>">
            <div class="form-group">
                <label>Student *</label>
                <select name="student_id" required>
                    <?php
                    if ($all_students) { $all_students->data_seek(0); }
                    while ($s = $all_students->fetch_assoc()):
                    ?>
                    <option value="<?= $s['id'] ?>" <?= $s['id'] == $edit_result['student_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['full_name']) ?> — <?= htmlspecialchars($s['student_id']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Subject *</label>
                <select name="subject_id" required>
                    <?php
                    if ($all_subjects) { $all_subjects->data_seek(0); }
                    while ($sub = $all_subjects->fetch_assoc()):
                    ?>
                    <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $edit_result['subject_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sub['subject_name']) ?> (<?= $sub['subject_code'] ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Marks Obtained *</label>
                    <input type="number" name="marks_obtained" min="0" step="0.5" value="<?= $edit_result['marks_obtained'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Total Marks *</label>
                    <input type="number" name="total_marks" min="1" value="<?= $edit_result['total_marks'] ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Year *</label>
                    <select name="exam_year" required>
                        <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $y == $edit_result['exam_year'] ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Term *</label>
                    <select name="term" required>
                        <option value="Term 1" <?= $edit_result['term']=='Term 1' ? 'selected' : '' ?>>Term 1</option>
                        <option value="Term 2" <?= $edit_result['term']=='Term 2' ? 'selected' : '' ?>>Term 2</option>
                        <option value="Term 3" <?= $edit_result['term']=='Term 3' ? 'selected' : '' ?>>Term 3</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Remarks</label>
                <input type="text" name="remarks" value="<?= htmlspecialchars($edit_result['remarks']) ?>">
            </div>
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <a href="result.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-warning">Update Result</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

function calcGrade(input) {
    const marks = parseFloat(input.value);
    const preview = document.getElementById('grade-preview');
    if (isNaN(marks) || marks < 0) { preview.style.display = 'none'; return; }
    let grade, color;
    if (marks >= 75) { grade = 'A'; color = '#059669'; }
    else if (marks >= 60) { grade = 'B'; color = '#2563ab'; }
    else if (marks >= 50) { grade = 'C'; color = '#d97706'; }
    else if (marks >= 40) { grade = 'D'; color = '#ea580c'; }
    else { grade = 'F'; color = '#dc2626'; }
    preview.style.display = 'block';
    preview.style.color = color;
    preview.innerHTML = `Grade: <span style="font-size:22px;">${grade}</span> — ${marks >= 40 ? 'PASS ✓' : 'FAIL ✗'}`;
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});
</script>
</body>
</html>