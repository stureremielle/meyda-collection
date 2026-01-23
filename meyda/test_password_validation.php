<?php
require_once __DIR__ . '/auth.php';

echo "<h1>Password Validation Test</h1>";

$testCases = [
    ['password' => 'short', 'expected' => false, 'desc' => 'Too short'],
    ['password' => 'nonumber!', 'expected' => false, 'desc' => 'No number'],
    ['password' => 'no_special1', 'expected' => true, 'desc' => 'Valid (underscore is special in my regex?)'],
    ['password' => 'Valid123!', 'expected' => true, 'desc' => 'Valid'],
    ['password' => '12345678', 'expected' => false, 'desc' => 'Only numbers'],
    ['password' => '!!!!!!!!', 'expected' => false, 'desc' => 'Only special'],
    ['password' => 'abc123EFG', 'expected' => false, 'desc' => 'No special'],
];

foreach ($testCases as $case) {
    $result = validatePasswordComplexity($case['password']);
    $status = $result['success'] ? 'PASSED' : 'FAILED';
    $color = ($result['success'] === $case['expected']) ? 'green' : 'red';

    echo "<p style='color: $color;'>";
    echo "Testing: <b>" . htmlspecialchars($case['password']) . "</b> (" . $case['desc'] . ")<br>";
    echo "Result: " . ($result['success'] ? 'Accepted' : 'Rejected - ' . $result['error']) . "<br>";
    echo "Match Expected: " . ($result['success'] === $case['expected'] ? 'YES' : 'NO');
    echo "</p>";
}
?>