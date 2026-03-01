<?php
$tests = [
    '../uploads/jobs/test.jpg' => 'uploads/jobs/test.jpg',
    './uploads/jobs/test.jpg' => 'uploads/jobs/test.jpg',
    'uploads/jobs/test.jpg' => 'uploads/jobs/test.jpg',
    '../../uploads/jobs/test.jpg' => 'uploads/jobs/test.jpg',
    '/uploads/jobs/test.jpg' => 'uploads/jobs/test.jpg' // Assuming absolute path
];

$pass = true;
foreach ($tests as $input => $expected) {
    $result = ltrim($input, './');
    if ($result !== $expected) {
        echo "FAIL: Expected '$expected' for input '$input', but got '$result'\n";
        $pass = false;
    }
}

if ($pass) {
    echo "All path resolution tests passed.\n";
}
