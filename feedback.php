<?php
session_start();

$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/feedback.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

function feedbackEscape($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function loadFeedback($file) {
    if (!file_exists($file)) {
        return array();
    }

    $data = json_decode(
        file_get_contents($file),
        true
    );

    return is_array($data) ? $data : array();
}

function saveFeedback($file, $items) {
    $json = json_encode(
        $items,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    return $json !== false &&
        file_put_contents($file, $json, LOCK_EX) !== false;
}

if (empty($_SESSION['feedback_csrf'])) {
    $_SESSION['feedback_csrf'] = bin2hex(random_bytes(16));
}

$types = array(
    'Suggestion',
    'Household Issue',
    'Rule Change',
    'Chore Request',
    'Other'
);

$error = '';
$feedback = loadFeedback($dataFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf']) ||
        !hash_equals(
            $_SESSION['feedback_csrf'],
            $_POST['csrf']
        )
    ) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $type = isset($_POST['type'])
        ? $_POST['type'] : '';

    $message = trim(
        isset($_POST['message'])
        ? $_POST['message'] : ''
    );

    if (
        !in_array($type, $types, true) ||
        $message === '' ||
        strlen($message) > 2000
    ) {
        $error = 'Please enter valid feedback.';
    } else {

        $feedback[] = array(
            'id' => bin2hex(random_bytes(8)),
            'type' => $type,
            'message' => $message,
            'status' => 'New',
            'created_at' => date('Y-m-d H:i:s')
        );

        if (saveFeedback($dataFile, $feedback)) {
            header('Location: feedback.php?success=1');
            exit;
        }

        $error = 'Could not save feedback.';
    }
}

$feedback = array_reverse($feedback);

require_once __DIR__ . '/includes/header.php';
?>
<html>
<body>
<div>
<main class="main-content">

    <div class="section-heading">
        <h2>Feedback & Suggestions</h2>
    </div>

    <p class="chores-subtitle">
        Share your thoughts and help make our
        shared home a better place.
    </p>

    <div class="feedback-intro">
        <strong>Your Voice Matters</strong>
        <p>
            Open communication helps roommates
            solve problems and avoid misunderstandings.
        </p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="feedback-success">
            Your feedback has been submitted successfully!
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="form-error">
            <?php echo feedbackEscape($error); ?>
        </div>
    <?php endif; ?>

    <section class="content-section">

        <div class="section-heading">
            <h2>Share Your Feedback</h2>
        </div>

        <form method="post" class="add-task-form">

            <input type="hidden"
                   name="csrf"
                   value="<?php echo feedbackEscape($_SESSION['feedback_csrf']); ?>">

            <label for="feedbackType">
                Feedback type
            </label>

            <select name="type"
                    id="feedbackType"
                    required>

                <?php foreach ($types as $type): ?>
                    <option value="<?php echo feedbackEscape($type); ?>">
                        <?php echo feedbackEscape($type); ?>
                    </option>
                <?php endforeach; ?>

            </select>

            <label for="feedbackMessage">
                Your message
            </label>

            <textarea
                name="message"
                id="feedbackMessage"
                rows="5"
                maxlength="2000"
                placeholder="Describe your suggestion or concern..."
                required></textarea>

            <button type="submit" class="add-task-button">
                Send Feedback
            </button>

        </form>

    </section>

    <section class="content-section">

        <div class="section-heading">
            <h2>Recent Feedback</h2>

            <span class="task-count">
                <?php echo count($feedback); ?> items
            </span>
        </div>

        <?php if (empty($feedback)): ?>

            <div class="info-card">
                <strong>No feedback yet</strong>
                <p>
                    Be the first to share a suggestion
                    for your shared home.
                </p>
            </div>

        <?php endif; ?>

        <?php foreach ($feedback as $item): ?>

            <div class="feedback-card">

                <div class="feedback-card-top">

                    <span class="rule-category">
                        <?php echo feedbackEscape($item['type']); ?>
                    </span>

                    <span class="feedback-status">
                        <?php echo feedbackEscape($item['status']); ?>
                    </span>

                </div>

                <p class="feedback-message">
                    <?php echo nl2br(
                        feedbackEscape($item['message'])
                    ); ?>
                </p>

                <small class="feedback-date">
                    <?php echo feedbackEscape($item['created_at']); ?>
                </small>

            </div>

        <?php endforeach; ?>

    </section>

</main>

<nav class="bottom-nav">
    <?php foreach ($navigation as $file => $item): ?>
        <a href="<?php echo feedbackEscape($file); ?>"
           class="<?php echo $currentPage === $file ? 'active' : ''; ?>">

            <span class="nav-icon">
                <?php echo $item[1]; ?>
            </span>

            <span>
                <?php echo feedbackEscape($item[0]); ?>
            </span>

        </a>
    <?php endforeach; ?>
</nav>

</div>
</body>
</html>
