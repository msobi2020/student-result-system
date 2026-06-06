<?php
require_once 'includes/config.php';
requireLogin();

$total_students = $conn->query("SELECT COUNT(*) as cnt FROM students")->fetch_assoc()['cnt'];
$total_results = $conn->query("SELECT COUNT(*) as cnt FROM results")->fetch_assoc()['cnt'];
$total_subjects = $conn->query("SELECT COUNT(*) as cnt FROM subjects")->fetch_assoc()['cnt'];
$pass_count = $conn->query("SELECT COUNT(*) as cnt FROM results WHERE grade != 'F'")->fetch_assoc()['cnt'];
$pass_rate = $total_results > 0 ? round(($pass_count / $total_results) * 100) : 0;

$recent_results = $conn->query("
    SELECT r.*, s.full_name, s.student_id as sid, sub.subject_name 
    FROM results r
    JOIN students s ON r.student_id = s.id
    JOIN subjects sub ON r.subject_id = sub.id
    ORDER BY r.created_at DESC 
    LIMIT 5
");

$grade_dist = $conn->query("SELECT grade, COUNT(*) as cnt FROM results GROUP BY grade ORDER BY grade");
$grades = [];
while ($row = $grade_dist->fetch_assoc()) {
    $grades[$row['grade']] = $row['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Result System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>Dashboard</h1>
                <p>Welcome back, <?= htmlspecialchars($_SESSION['admin_name']) ?>!</p>
            </div>
            <span style="font-size:13px; color:var(--text-muted);">
                <?= date('l, F j, Y') ?>
            </span>
        </div>

        <div class="page-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <p>Total Students</p>
                        <h3><?= number_format($total_students) ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14,2 14,8 20,8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <p>Total Results</p>
                        <h3><?= number_format($total_results) ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <p>Total Subjects</p>
                        <h3><?= number_format($total_subjects) ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
                        </svg>
                    </div>
                    <div class="stat-info">
                        <p>Pass Rate</p>
                        <h3><?= $pass_rate ?>%</h3>
                    </div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
                <div class="card" style="grid-column: span 2;">
                    <div class="card-header">
                        <h2>📋 Recent Results</h2>
                        <a href="result.php" class="btn btn-secondary btn-sm">View All</a>
                    </div>
                    <?php if ($recent_results && $recent_results->num_rows > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>Term</th>
                                    <th>Year</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $recent_results->fetch_assoc()): 
                                    $grade = getGrade($row['marks_obtained']);
                                    $gradeClass = getGradeClass($grade);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                                        <small style="color:var(--text-muted);"><?= htmlspecialchars($row['sid']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                    <td><strong><?= $row['marks_obtained'] ?></strong>/<?= $row['total_marks'] ?></td>
                                    <td><span class="<?= $gradeClass ?>"><?= $grade ?></span></td>
                                    <td><?= htmlspecialchars($row['term']) ?></td>
                                    <td><?= $row['exam_year'] ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div style="text-align:center; padding:40px; color:var(--text-muted);">
                        <p>No results recorded yet. <a href="result.php" style="color:var(--primary);">Add results →</a></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-header"><h2>📊 Grade Distribution</h2></div>
                    <?php 
                    $allGrades = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
                    foreach ($grades as $g => $c) $allGrades[$g] = $c;
                    $maxGrade = max(array_values($allGrades)) ?: 1;
                    $colors = ['A'=>'#059669','B'=>'#2563ab','C'=>'#d97706','D'=>'#ea580c','F'=>'#dc2626'];
                    foreach ($allGrades as $g => $c):
                        $pct = round(($c / $maxGrade) * 100);
                    ?>
                    <div style="margin-bottom:14px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:13px;">
                            <span style="font-weight:600;">Grade <?= $g ?></span>
                            <span style="color:var(--text-muted);"><?= $c ?> students</span>
                        </div>
                        <div style="background:var(--bg); border-radius:6px; height:10px; overflow:hidden;">
                            <div style="width:<?= $pct ?>%; background:<?= $colors[$g] ?>; height:100%; border-radius:6px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="card">
                    <div class="card-header"><h2>⚡ Quick Actions</h2></div>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <a href="student.php?action=add" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <line x1="20" y1="8" x2="20" y2="14"/>
                                <line x1="23" y1="11" x2="17" y2="11"/>
                            </svg>
                            Add New Student
                        </a>
                        <a href="result.php?action=add" class="btn btn-success">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <line x1="12" y1="18" x2="12" y2="12"/>
                                <line x1="9" y1="15" x2="15" y2="15"/>
                            </svg>
                            Record Result
                        </a>
                        <a href="result.php" class="btn btn-warning">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14,2 14,8 20,8"/>
                            </svg>
                            View All Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>