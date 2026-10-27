<?php
session_start();

$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/rules.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

function ruleEscape($value) {
    return htmlspecialchars(
        (string)$value, ENT_QUOTES, 'UTF-8'
    );
}

function loadRules($file) {
    if (!file_exists($file)) {
        return array();
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : array();
}

function saveRules($file, $rules) {
    $json = json_encode(
        $rules,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );
    return $json !== false &&
        file_put_contents($file, $json, LOCK_EX) !== false;
}

if (empty($_SESSION['rules_csrf'])) {
    $_SESSION['rules_csrf'] = bin2hex(random_bytes(16));
}

if (!file_exists($dataFile)) {
    $defaults = array(
        array(
            'id' => 'rule-1',
            'category' => 'Quiet Hours',
            'title' => 'Quiet Hours',
            'description' => 'Keep noise low from 10 PM to 8 AM.'
        ),
        array(
            'id' => 'rule-2',
            'category' => 'Kitchen',
            'title' => 'Shared Kitchen',
            'description' => 'Clean up after cooking and return everything to its place.'
        ),
        array(
            'id' => 'rule-3',
            'category' => 'Kitchen',
            'title' => 'Fridge Organization',
            'description' => 'Keep your food in the agreed section of the refrigerator.'
        ),
        array(
            'id' => 'rule-4',
            'category' => 'Cleaning',
            'title' => 'Cleaning Schedule',
            'description' => 'Follow the shared cleaning schedule and take turns with household chores.'
        ),
        array(
            'id' => 'rule-5',
            'category' => 'Communication',
            'title' => 'House Meeting',
            'description' => 'Discuss household concerns together at the weekly meeting.'
        )
    );

    saveRules($dataFile, $defaults);
}

$rules = loadRules($dataFile);
$error = '';

$categories = array(
    'Quiet Hours',
    'Kitchen',
    'Cleaning',
    'Bathroom',
    'Guests',
    'Communication',
    'Other'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf']) ||
        !hash_equals($_SESSION['rules_csrf'], $_POST['csrf'])
    ) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
    $description = trim(
        isset($_POST['description']) ? $_POST['description'] : ''
    );
    $category = isset($_POST['category'])
        ? $_POST['category'] : '';

    if (
        $title === '' ||
        $description === '' ||
        strlen($title) > 160 ||
        strlen($description) > 1000 ||
        !in_array($category, $categories, true)
    ) {
        $error = 'Please enter valid rule details.';
    } else {
        $rules[] = array(
            'id' => bin2hex(random_bytes(8)),
            'category' => $category,
            'title' => $title,
            'description' => $description
        );

        if (saveRules($dataFile, $rules)) {
            header('Location: rules.php');
            exit;
        }

        $error = 'Could not save the rule.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<html>
<body>
<div>
<main class="main-content">

    <div class="section-heading">
        <h2>House Rules</h2>
    </div>

    <p class="chores-subtitle">
        Understand your shared home and respect each other.
    </p>

    <div class="rules-intro">
        <strong>Living Together</strong>
        <p>
            Clear household rules help new roommates
            settle in and avoid misunderstandings.
        </p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="form-error">
            <?php echo ruleEscape($error); ?>
        </div>
    <?php endif; ?>

    <section class="content-section">

        <div class="section-heading">
            <h2>Our House Rules</h2>
            <span class="task-count">
                <?php echo count($rules); ?> rules
            </span>
        </div>

        <?php foreach ($rules as $rule): ?>
            <div class="rule-card">
                <div class="rule-top">
                    <span class="rule-category">
                        <?php echo ruleEscape($rule['category']); ?>
                    </span>
                </div>

                <h3><?php echo ruleEscape($rule['title']); ?></h3>
                <p><?php echo ruleEscape($rule['description']); ?></p>
            </div>
        <?php endforeach; ?>

    </section>

    <section class="content-section">
        <div class="section-heading">
            <h2>Add a House Rule</h2>
        </div>

        <form method="post" class="add-task-form">
            <input type="hidden" name="csrf"
                   value="<?php echo ruleEscape($_SESSION['rules_csrf']); ?>">

            <label for="category">Category</label>
            <select name="category" id="category" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo ruleEscape($category); ?>">
                        <?php echo ruleEscape($category); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="ruleTitle">Rule title</label>
            <input type="text" id="ruleTitle"
                   name="title" maxlength="160"
                   placeholder="e.g. Bathroom Schedule" required>

            <label for="ruleDescription">Description</label>
            <textarea id="ruleDescription"
                      name="description"
                      maxlength="1000"
                      rows="4"
                      placeholder="Describe the house rule..."
                      required></textarea>

            <button type="submit" class="add-task-button">
                + Add House Rule
            </button>
        </form>
    </section>

</main>

<nav class="bottom-nav">
    <?php foreach ($navigation as $file => $item): ?>
        <a href="<?php echo ruleEscape($file); ?>"
           class="<?php echo $currentPage === $file ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo $item[1]; ?></span>
            <span><?php echo ruleEscape($item[0]); ?></span>
        </a>
    <?php endforeach; ?>
</nav>

</div>
</body>
</html>
