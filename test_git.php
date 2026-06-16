<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Git di PHP</h1>";

// Test 1: Cek function exec
echo "<h2>Test 1: Cek function exec()</h2>";
if (function_exists('exec')) {
    echo "<p style='color:green'>✅ exec() available</p>";
} else {
    echo "<p style='color:red'>❌ exec() NOT available</p>";
}

// Test 2: Cek git version
echo "<h2>Test 2: Cek git --version</h2>";
$output = [];
$returnCode = 0;
exec('git --version 2>&1', $output, $returnCode);
echo "<pre>Output: " . print_r($output, true) . "</pre>";
echo "<p>Return Code: $returnCode</p>";
if ($returnCode === 0) {
    echo "<p style='color:green'>✅ Git found!</p>";
} else {
    echo "<p style='color:red'>❌ Git NOT found!</p>";
}

// Test 3: Cek apakah direktori ini git repo
echo "<h2>Test 3: Cek .git directory</h2>";
$rootDir = __DIR__;
if (is_dir($rootDir . '/.git')) {
    echo "<p style='color:green'>✅ .git directory exists at $rootDir</p>";
} else {
    echo "<p style='color:red'>❌ .git directory NOT found at $rootDir</p>";
}

// Test 4: Coba git status
echo "<h2>Test 4: Coba git status</h2>";
chdir($rootDir);
$output = [];
$returnCode = 0;
exec('git status 2>&1', $output, $returnCode);
echo "<pre>Output: " . print_r($output, true) . "</pre>";
echo "<p>Return Code: $returnCode</p>";
?>
