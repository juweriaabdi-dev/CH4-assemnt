<?php
session_start();

$errors = [];
$student = [];
$success = false;

$fields = [
    'name' => 'Full Name',
    'pob' => 'Place of Birth',
    'dob' => 'Date of Birth',
    'address' => 'Address',
    'gender' => 'Gender',
    'telephone' => 'Telephone',
    'email' => 'Email',
    'parent_name' => 'Parent Name',
    'parent_telephone' => 'Parent Telephone',
    'relationship' => 'Relationship',
    'student_id' => 'Student ID',
    'faculty' => 'Faculty',
    'department' => 'Department',
    'class' => 'Class',
    'registration_date' => 'Registration Date'
];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (isset($_GET['result'], $_SESSION['student'])) {
    $student = $_SESSION['student'];
    $success = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($fields as $key => $label) {
        $student[$key] = trim($_POST[$key] ?? '');

        if ($student[$key] === '') {
            $errors[$key] = "$label is required.";
        }
    }

    // Validate student name
    if (
        !empty($student['name']) &&
        !preg_match('/^[\p{L} ]+$/u', $student['name'])
    ) {
        $errors['name'] = 'Name must contain letters and spaces only.';
    }

    // Validate email
    if (
        !empty($student['email']) &&
        !filter_var($student['email'], FILTER_VALIDATE_EMAIL)
    ) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    // Validate gender
    if (
        !empty($student['gender']) &&
        !in_array($student['gender'], ['Male', 'Female'], true)
    ) {
        $errors['gender'] = 'Please select a valid gender.';
    }

    // Validate relationship
    if (
        !empty($student['relationship']) &&
        !in_array(
            $student['relationship'],
            ['Father', 'Mother', 'Guardian', 'Other'],
            true
        )
    ) {
        $errors['relationship'] = 'Please select a valid relationship.';
    }

    // Validate dates
    foreach (['dob', 'registration_date'] as $dateField) {
        if (!empty($student[$dateField])) {
            $date = DateTime::createFromFormat(
                '!Y-m-d',
                $student[$dateField]
            );

            if (
                !$date ||
                $date->format('Y-m-d') !== $student[$dateField]
            ) {
                $errors[$dateField] = 'Please enter a valid date.';
            }
        }
    }

    // Validate student image
    $image = $_FILES['image'] ?? null;
    $extension = '';

    if (!$image || $image['error'] === UPLOAD_ERR_NO_FILE) {
        $errors['image'] = 'Please upload a student image.';
    } elseif ($image['error'] !== UPLOAD_ERR_OK) {
        $errors['image'] = 'Image upload failed.';
    } elseif ($image['size'] > 2 * 1024 * 1024) {
        $errors['image'] = 'Image must not exceed 2 MB.';
    } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($image['tmp_name']);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];

        if (!isset($allowed[$mime])) {
            $errors['image'] = 'Only JPG, PNG, GIF and WEBP images are allowed.';
        } else {
            $extension = $allowed[$mime];
        }
    }

    // Save student information
    if (empty($errors)) {

        $folder = __DIR__ . '/uploads/';

        if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
            $errors['image'] = 'Could not create the uploads folder.';
        } else {

            $filename = bin2hex(random_bytes(16)) . '.' . $extension;

            if (!move_uploaded_file(
                $image['tmp_name'],
                $folder . $filename
            )) {
                $errors['image'] = 'Could not save the uploaded image.';
            } else {

                $student['image'] = 'uploads/' . $filename;

                $_SESSION['student'] = $student;

                header('Location: index.php?result=1');
                exit;
            }
        }
    }
}

$firstError = array_key_first($errors);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Student Registration - Jamhuriya University</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #f2f5f9;
        }

        .registration-card {
            max-width: 950px;
            margin: auto;
            border: none;
            border-radius: 12px;
        }

        .page-header {
            background: #0d6efd;
            color: white;
            padding: 25px;
            text-align: center;
            border-radius: 12px 12px 0 0;
        }

        .section-title {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 8px;
            margin-top: 25px;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
        }

        .required {
            color: red;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>

<div class="container py-5">

    <div class="card shadow registration-card">

        <div class="page-header">
            <h2>Jamhuriya University</h2>
            <h5>Student Registration Form</h5>
        </div>

        <div class="card-body p-4">

        <?php if ($success): ?>

            <div class="alert alert-success text-center">
                <h3>Student Registered Successfully!</h3>
                <p class="mb-0">Student information has been received.</p>
            </div>

            <div class="text-center mb-4">
                <img
                    src="<?= e($student['image']) ?>"
                    alt="Student Image"
                    class="img-thumbnail"
                    style="width:160px;height:160px;object-fit:cover"
                >
            </div>

            <?php
            $sections = [
                'Personal Information' => [
                    'name' => 'Full Name',
                    'pob' => 'Place of Birth',
                    'dob' => 'Date of Birth',
                    'address' => 'Address',
                    'gender' => 'Gender',
                    'telephone' => 'Telephone',
                    'email' => 'Email'
                ],
                'Parent Information' => [
                    'parent_name' => 'Parent Name',
                    'parent_telephone' => 'Parent Telephone',
                    'relationship' => 'Relationship'
                ],
                'Academic Information' => [
                    'student_id' => 'Student ID',
                    'faculty' => 'Faculty',
                    'department' => 'Department',
                    'class' => 'Class',
                    'registration_date' => 'Registration Date'
                ]
            ];
            ?>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">

                    <thead class="table-primary">
                        <tr>
                            <th>Information</th>
                            <th>Submitted Details</th>
                        </tr>
                    </thead>

                    <?php foreach ($sections as $section => $items): ?>

                        <tbody>
                            <tr class="table-secondary">
                                <th colspan="2">
                                    <?= e($section) ?>
                                </th>
                            </tr>

                            <?php foreach ($items as $key => $label): ?>
                                <tr>
                                    <th><?= e($label) ?></th>
                                    <td><?= e($student[$key] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>

                        </tbody>

                    <?php endforeach; ?>

                </table>
            </div>

            <div class="text-center no-print">
                <a href="index.php?new=1" class="btn btn-primary">
                    Register Another Student
                </a>

                <button
                    onclick="window.print()"
                    class="btn btn-secondary"
                >
                    Print
                </button>
            </div>

        <?php else: ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong>Please correct these errors:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $message): ?>
                            <li><?= e($message) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form
                method="POST"
                action="index.php"
                enctype="multipart/form-data"
            >

                <h4 class="section-title">Personal Information</h4>

                <div class="row">

                    <?php
                    $personalFields = [
                        'name' => 'Full Name',
                        'pob' => 'Place of Birth (POB)',
                        'dob' => 'Date of Birth',
                        'address' => 'Address',
                        'telephone' => 'Telephone',
                        'email' => 'Email'
                    ];

                    foreach ($personalFields as $key => $label):
                    ?>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                <?= e($label) ?>
                                <span class="required">*</span>
                            </label>

                            <input
                                type="<?= $key === 'dob' ? 'date' : ($key === 'email' ? 'email' : ($key === 'telephone' ? 'tel' : 'text')) ?>"
                                name="<?= e($key) ?>"
                                class="form-control <?= isset($errors[$key]) ? 'is-invalid' : '' ?>"
                                value="<?= e($student[$key] ?? '') ?>"
                                required
                                <?= $firstError === $key ? 'autofocus' : '' ?>
                            >

                            <?php if (isset($errors[$key])): ?>
                                <div class="invalid-feedback">
                                    <?= e($errors[$key]) ?>
                                </div>
                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Gender <span class="required">*</span>
                        </label>

                        <select
                            name="gender"
                            class="form-select <?= isset($errors['gender']) ? 'is-invalid' : '' ?>"
                            required
                        >
                            <option value="">Select Gender</option>

                            <option
                                value="Male"
                                <?= ($student['gender'] ?? '') === 'Male' ? 'selected' : '' ?>
                            >Male</option>

                            <option
                                value="Female"
                                <?= ($student['gender'] ?? '') === 'Female' ? 'selected' : '' ?>
                            >Female</option>
                        </select>

                        <?php if (isset($errors['gender'])): ?>
                            <div class="invalid-feedback">
                                <?= e($errors['gender']) ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Student Image
                            <span class="required">*</span>
                        </label>

                        <input
                            type="file"
                            name="image"
                            class="form-control <?= isset($errors['image']) ? 'is-invalid' : '' ?>"
                            accept=".jpg,.jpeg,.png,.gif,.webp"
                            required
                        >

                        <small class="text-muted">
                            JPG, PNG, GIF or WEBP. Maximum size: 2 MB.
                        </small>

                        <?php if (isset($errors['image'])): ?>
                            <div class="invalid-feedback">
                                <?= e($errors['image']) ?>
                            </div>
                        <?php endif; ?>

                    </div>

                </div>

                <h4 class="section-title">Parent Information</h4>

                <div class="row">

                    <?php
                    $parentFields = [
                        'parent_name' => 'Parent Name',
                        'parent_telephone' => 'Parent Telephone'
                    ];

                    foreach ($parentFields as $key => $label):
                    ?>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                <?= e($label) ?>
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                name="<?= e($key) ?>"
                                class="form-control <?= isset($errors[$key]) ? 'is-invalid' : '' ?>"
                                value="<?= e($student[$key] ?? '') ?>"
                                required
                            >

                            <?php if (isset($errors[$key])): ?>
                                <div class="invalid-feedback">
                                    <?= e($errors[$key]) ?>
                                </div>
                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Relationship <span class="required">*</span>
                        </label>

                        <select
                            name="relationship"
                            class="form-select <?= isset($errors['relationship']) ? 'is-invalid' : '' ?>"
                            required
                        >
                            <option value="">Select Relationship</option>

                            <?php
                            foreach (
                                ['Father', 'Mother', 'Guardian', 'Other'] as $r
                            ):
                            ?>

                                <option
                                    value="<?= e($r) ?>"
                                    <?= ($student['relationship'] ?? '') === $r ? 'selected' : '' ?>
                                >
                                    <?= e($r) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                        <?php if (isset($errors['relationship'])): ?>
                            <div class="invalid-feedback">
                                <?= e($errors['relationship']) ?>
                            </div>
                        <?php endif; ?>

                    </div>

                </div>

                <h4 class="section-title">Academic Information</h4>

                <div class="row">

                    <?php
                    $academicFields = [
                        'student_id' => 'Student ID',
                        'faculty' => 'Faculty',
                        'department' => 'Department',
                        'class' => 'Class',
                        'registration_date' => 'Registration Date'
                    ];

                    foreach ($academicFields as $key => $label):
                    ?>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                <?= e($label) ?>
                                <span class="required">*</span>
                            </label>

                            <input
                                type="<?= $key === 'registration_date' ? 'date' : 'text' ?>"
                                name="<?= e($key) ?>"
                                class="form-control <?= isset($errors[$key]) ? 'is-invalid' : '' ?>"
                                value="<?= e($student[$key] ?? '') ?>"
                                required
                            >

                            <?php if (isset($errors[$key])): ?>
                                <div class="invalid-feedback">
                                    <?= e($errors[$key]) ?>
                                </div>
                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="d-grid mt-4">
                    <button
                        type="submit"
                        class="btn btn-primary btn-lg"
                    >
                        Register Student
                    </button>
                </div>

            </form>

        <?php endif; ?>

        </div>

        <div class="card-footer text-center text-muted">
            juuuabdi
        </div>

    </div>

</div>

</body>
</html>