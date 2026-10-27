<?php
// Roommate Harmony V2
// Compatible with PHP 7.1

function homeEscape($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function homeReadJson($filename) {
    $path = __DIR__ . '/data/' . $filename;

    if (!is_file($path)) {
        return array();
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true);

    return is_array($data) ? $data : array();
}

$tasks = homeReadJson('chores.json');
$roommates = homeReadJson('roommates.json');
$rules = homeReadJson('rules.json');
$feedback = homeReadJson('feedback.json');

$today = date('Y-m-d');
$todayTasks = array();
$completedTasks = 0;

foreach ($tasks as $task) {
    if (!empty($task['done'])) {
        $completedTasks++;
    }

    if (
        isset($task['due_date']) &&
        $task['due_date'] === $today
    ) {
        $todayTasks[] = $task;
    }
}

$roommateCount = count($roommates);
$taskCount = count($tasks);
$ruleCount = count($rules);
$feedbackCount = count($feedback);

require_once __DIR__ . '/includes/header.php';
?>
<html>
<body>
<div>
<main class="main-content">

    <section class="welcome-card">
        <span class="eyebrow">YOUR SHARED HOME</span>
        <h2>Sunrise Apartment</h2>
        <p>
            <?php echo $roommateCount; ?>
            roommates living together
        </p>
    </section>

    <section class="home-stats">
        <div class="stat-card">
            <strong><?php echo $taskCount; ?></strong>
            <span>Total Tasks</span>
        </div>

        <div class="stat-card">
            <strong><?php echo $completedTasks; ?></strong>
            <span>Completed</span>
        </div>

        <div class="stat-card">
            <strong><?php echo $ruleCount; ?></strong>
            <span>House Rules</span>
        </div>
    </section>

    <section class="content-section">

        <div class="section-heading">
            <h2>Today's Tasks</h2>
            <a href="chores.php">See all →</a>
        </div>

        <div class="card">

            <?php if (empty($todayTasks)): ?>
                <div class="home-empty">
                    No tasks scheduled for today.
                </div>
            <?php endif; ?>

            <?php foreach ($todayTasks as $task): ?>
                <div class="task-item">

                    <span class="task-check <?php echo !empty($task['done']) ? 'completed' : ''; ?>">
                        <?php echo !empty($task['done']) ? '✓' : ''; ?>
                    </span>

                    <div class="task-details">
                        <strong>
                            <?php echo homeEscape($task['title']); ?>
                        </strong>

                        <small>
                            Today ·
                            <?php echo homeEscape($task['person']); ?>
                        </small>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>

    </section>

    <section class="content-section">

        <div class="section-heading">
            <h2>Roommates</h2>
            <a href="roommates.php">See all →</a>
        </div>

        <div class="home-roommates">

            <?php foreach ($roommates as $person): ?>
                <a href="roommates.php" class="home-roommate">

                    <div class="avatar">
                        <?php
                        echo homeEscape(
                            strtoupper(substr($person['name'], 0, 1))
                        );
                        ?>
                    </div>

                    <span>
                        <?php echo homeEscape($person['name']); ?>
                    </span>

                </a>
            <?php endforeach; ?>

            <?php if (empty($roommates)): ?>
                <p>No roommates added yet.</p>
            <?php endif; ?>

        </div>

    </section>

    <section class="content-section">

        <div class="section-heading">
            <h2>House Rules</h2>
            <a href="rules.php">View all →</a>
        </div>

        <?php foreach (array_slice($rules, 0, 3) as $rule): ?>

            <div class="info-card">
                <strong>
                    <?php echo homeEscape($rule['title']); ?>
                </strong>

                <p>
                    <?php echo homeEscape($rule['description']); ?>
                </p>
            </div>

        <?php endforeach; ?>

        <?php if (empty($rules)): ?>
            <div class="info-card">
                No house rules added yet.
            </div>
        <?php endif; ?>

    </section>

    <section class="content-section">

        <div class="section-heading">
            <h2>Feedback</h2>
            <a href="feedback.php">View all →</a>
        </div>

        <div class="home-feedback">
            <strong>
                <?php echo $feedbackCount; ?>
                suggestions received
            </strong>

            <p>
                Share ideas and solve household concerns together.
            </p>

            <a href="feedback.php">
                Share your feedback →
            </a>
        </div>

    </section>

</main>

<nav class="bottom-nav">
    <?php foreach ($navigation as $file => $item): ?>
        <a href="<?php echo homeEscape($file); ?>"
           class="<?php echo $currentPage === $file ? 'active' : ''; ?>">

            <span class="nav-icon">
                <?php echo $item[1]; ?>
            </span>

            <span>
                <?php echo homeEscape($item[0]); ?>
            </span>

        </a>
    <?php endforeach; ?>
</nav>

</div>
</body>
</html>
