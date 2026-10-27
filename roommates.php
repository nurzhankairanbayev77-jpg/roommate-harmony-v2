<?php
session_start();

$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/roommates.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

function roommateEscape($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function loadRoommates($file) {
    if (!file_exists($file)) {
        return array();
    }

    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : array();
}

function saveRoommates($file, $items) {
    $json = json_encode(
        $items,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    return $json !== false &&
        file_put_contents($file, $json, LOCK_EX) !== false;
}

if (empty($_SESSION['roommates_csrf'])) {
    $_SESSION['roommates_csrf'] = bin2hex(random_bytes(16));
}

$preferences = array(
    'Quiet environment',
    'Clean shared spaces',
    'Cooking together',
    'Organized schedule',
    'Other'
);

if (!file_exists($dataFile)) {
    $defaults = array(
        array(
            'id' => 'member-1',
            'name' => 'Aigerim',
            'room' => 'Room 1',
            'preference' => 'Clean shared spaces',
            'note' => 'Prefers keeping the kitchen tidy.'
        ),
        array(
            'id' => 'member-2',
            'name' => 'Daniyar',
            'room' => 'Room 2',
            'preference' => 'Cooking together',
            'note' => 'Enjoys preparing shared meals.'
        ),
        array(
            'id' => 'member-3',
            'name' => 'Aizat',
            'room' => 'Room 3',
            'preference' => 'Quiet environment',
            'note' => 'Prefers quiet evenings for studying.'
        ),
        array(
            'id' => 'member-4',
            'name' => 'Alina',
            'room' => 'Room 4',
            'preference' => 'Organized schedule',
            'note' => 'Likes planning chores in advance.'
        )
    );

    saveRoommates($dataFile, $defaults);
}

$roommates = loadRoommates($dataFile);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf']) ||
        !hash_equals(
            $_SESSION['roommates_csrf'],
            $_POST['csrf']
        )
    ) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $name = trim(isset($_POST['name'])
        ? $_POST['name'] : '');

    $room = trim(isset($_POST['room'])
        ? $_POST['room'] : '');

    $preference = isset($_POST['preference'])
        ? $_POST['preference'] : '';

    $note = trim(isset($_POST['note'])
        ? $_POST['note'] : '');

    if (
        $name === '' ||
        $room === '' ||
        strlen($name) > 100 ||
        strlen($room) > 100 ||
        strlen($note) > 500 ||
        !in_array($preference, $preferences, true)
    ) {
        $error = 'Please enter valid roommate details.';
    } else {

        $roommates[] = array(
            'id' => bin2hex(random_bytes(8)),
            'name' => $name,
            'room' => $room,
            'preference' => $preference,
            'note' => $note
        );

        if (saveRoommates($dataFile, $roommates)) {
            header('Location: roommates.php');
            exit;
        }

        $error = 'Could not save roommate.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<html>
<body>
<div>
<main class="main-content">

    <div class="section-heading">
        <h2>Our Roommates</h2>
        <span class="task-count">
            <?php echo count($roommates); ?> members
        </span>
    </div>

    <p class="chores-subtitle">
        Get to know the people you share your home with.
    </p>

    <div class="roommates-intro">
        <strong>Better Together</strong>
        <p>
            Understanding each other's habits and
            preferences helps create a comfortable home.
        </p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="form-error">
            <?php echo roommateEscape($error); ?>
        </div>
    <?php endif; ?>

    <section class="content-section">

        <div class="section-heading">
            <h2>Apartment Members</h2>
        </div>

        <?php foreach ($roommates as $person): ?>

            <details class="member-card">

                <summary class="member-summary">

                    <span class="member-avatar">
                        <?php
                        echo roommateEscape(
                            strtoupper(substr($person['name'], 0, 1))
                        );
                        ?>
                    </span>

                    <span class="member-info">
                        <strong>
                            <?php echo roommateEscape($person['name']); ?>
                        </strong>

                        <small>
                            <?php echo roommateEscape($person['room']); ?>
                        </small>
                    </span>

                    <span class="member-arrow">⌄</span>

                </summary>

                <div class="member-extra">

                    <strong>Living preference</strong>
                    <p>
                        <?php echo roommateEscape($person['preference']); ?>
                    </p>

                    <?php if ($person['note'] !== ''): ?>
                        <strong>About me</strong>
                        <p>
                            <?php echo roommateEscape($person['note']); ?>
                        </p>
                    <?php endif; ?>

                </div>

            </details>

        <?php endforeach; ?>

    </section>

    <section class="content-section">

        <div class="section-heading">
            <h2>Add a Roommate</h2>
        </div>

        <form method="post" class="add-task-form">

            <input type="hidden"
                   name="csrf"
                   value="<?php echo roommateEscape($_SESSION['roommates_csrf']); ?>">

            <label for="memberName">Name</label>
            <input type="text"
                   name="name"
                   id="memberName"
                   maxlength="100"
                   placeholder="Enter roommate name"
                   required>

            <label for="memberRoom">Room</label>
            <input type="text"
                   name="room"
                   id="memberRoom"
                   maxlength="100"
                   placeholder="e.g. Room 5"
                   required>

            <label for="memberPreference">
                Living preference
            </label>

            <select name="preference"
                    id="memberPreference"
                    required>
                <?php foreach ($preferences as $pref): ?>
                    <option value="<?php echo roommateEscape($pref); ?>">
                        <?php echo roommateEscape($pref); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="memberNote">
                About roommate (optional)
            </label>

            <textarea name="note"
                      id="memberNote"
                      maxlength="500"
                      rows="3"
                      placeholder="Share a household preference..."></textarea>

            <button type="submit" class="add-task-button">
                + Add Roommate
            </button>

        </form>

    </section>

</main>

<nav class="bottom-nav">
    <?php foreach ($navigation as $file => $item): ?>
        <a href="<?php echo roommateEscape($file); ?>"
           class="<?php echo $currentPage === $file ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo $item[1]; ?></span>
            <span><?php echo roommateEscape($item[0]); ?></span>
        </a>
    <?php endforeach; ?>
</nav>

</div>
</body>
</html>
