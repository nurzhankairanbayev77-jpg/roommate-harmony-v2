
<?php
$currentPage = basename($_SERVER['SCRIPT_NAME']);

$navigation = array(
    'index.php' => array('Home', '⌂'),
    'chores.php' => array('Chores', '✓'),
    'rules.php' => array('Rules', '☷'),
    'roommates.php' => array('Roommates', '♙'),
    'feedback.php' => array('Feedback', '✉')
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Roommate Harmony</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-container">
    <header class="app-header">
        <div class="logo-icon">⌂</div>
        <div>
            <h1>Roommate Harmony</h1>
            <p>Shared living made easier</p>
        </div>
    </header>
