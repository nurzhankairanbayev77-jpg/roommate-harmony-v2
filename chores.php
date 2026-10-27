<?php
session_start();

$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/chores.json';


if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

$roommates = array('Aigerim', 'Daniyar', 'Aizat', 'Alina');

if (empty($_SESSION['chores_csrf'])) {
    $_SESSION['chores_csrf'] = bin2hex(random_bytes(16));
}

function loadTasks($file)
{
    if (!file_exists($file)) {
        return array();
    }

    $json = file_get_contents($file);
    $tasks = json_decode($json, true);

    return is_array($tasks) ? $tasks : array();
}

function saveTasks($file, $tasks)
{
    $json = json_encode(
        $tasks,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    return $json !== false &&
        file_put_contents($file, $json, LOCK_EX) !== false;
}

function escapeHtml($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$tasks = loadTasks($dataFile);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf']) ||
        !hash_equals(
            $_SESSION['chores_csrf'],
            $_POST['csrf']
        )
    ) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $action = isset($_POST['action'])
        ? $_POST['action'] : '';

    if ($action === 'add') {

        $title = trim(
            isset($_POST['title']) ? $_POST['title'] : ''
        );

        $person = isset($_POST['person'])
            ? $_POST['person'] : '';

        $dueDate = isset($_POST['due_date'])
            ? $_POST['due_date'] : '';

        $validDate = DateTime::createFromFormat(
            '!Y-m-d',
            $dueDate
        );

        if (
            $title === '' ||
            strlen($title) > 200 ||
            !in_array($person, $roommates, true) ||
            !$validDate ||
            $validDate->format('Y-m-d') !== $dueDate
        ) {
            $error = 'Please enter valid task details.';
        } else {
            $tasks[] = array(
                'id' => bin2hex(random_bytes(8)),
                'title' => $title,
                'person' => $person,
                'due_date' => $dueDate,
                'done' => false
            );
        }
    }

    if ($action === 'toggle') {
        $id = isset($_POST['id']) ? $_POST['id'] : '';

        foreach ($tasks as &$task) {
            if ($task['id'] === $id) {
                $task['done'] = !$task['done'];
                break;
            }
        }
        unset($task);
    }

    if ($error === '') {
        if (saveTasks($dataFile, $tasks)) {
            header('Location: chores.php');
            exit;
        }

        $error = 'Could not save tasks. Check folder permissions.';
    }
}

if (!file_exists($dataFile) && empty($tasks)) {
    $today = date('Y-m-d');

    $tasks = array(
        array(
            'id' => 'demo-1',
            'title' => 'Clean the kitchen',
            'person' => 'Aigerim',
            'due_date' => $today,
            'done' => false
        ),
        array(
            'id' => 'demo-2',
            'title' => 'Take out the trash',
            'person' => 'Daniyar',
            'due_date' => $today,
            'done' => false
        ),
        array(
            'id' => 'demo-3',
            'title' => 'Wipe the floor',
            'person' => 'Aizat',
            'due_date' => $today,
            'done' => true
        )
    );

    if (!saveTasks($dataFile, $tasks)) {
        $error = 'Could not initialize task storage.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<html>
<body>
<div>

<main class="main-content">

    <div class="section-heading">
        <h2>Household Chores</h2>
    </div>

    <p class="chores-subtitle">
        Share responsibilities and keep your home organized.
    </p>

    <?php if ($error !== ''): ?>
        <div class="form-error">
            <?php echo escapeHtml($error); ?>
        </div>
    <?php endif; ?>

    <section class="content-section">

        <div class="section-heading">
            <h2>Task List</h2>
            <span class="task-count">
                <?php echo count($tasks); ?> tasks
            </span>
        </div>

        <?php if (empty($tasks)): ?>
            <div class="info-card">
                No tasks yet. Add your first task below.
            </div>
        <?php endif; ?>

        <?php foreach ($tasks as $task): ?>

            <div class="chore-card">

                <form method="post" class="chore-toggle">

                    <input type="hidden" name="csrf"
                           value="<?php echo escapeHtml($_SESSION['chores_csrf']); ?>">

                    <input type="hidden" name="action"
                           value="toggle">

                    <input type="hidden" name="id"
                           value="<?php echo escapeHtml($task['id']); ?>">

                    <button type="submit"
                            class="chore-check <?php echo $task['done'] ? 'is-done' : ''; ?>"
                            aria-label="Toggle task completion">
                        <?php echo $task['done'] ? '✓' : ''; ?>
                    </button>
                </form>

                <div class="chore-details">
                    <strong class="<?php echo $task['done'] ? 'done-text' : ''; ?>">
                        <?php echo escapeHtml($task['title']); ?>
                    </strong>

                    <small>
                        <?php echo escapeHtml($task['person']); ?>
                        ·
                        <?php echo escapeHtml($task['due_date']); ?>
                    </small>
                </div>

                <span class="chore-status">
                    <?php echo $task['done'] ? 'Done' : 'Pending'; ?>
                </span>

            </div>

        <?php endforeach; ?>

    </section>

    <section class="content-section">

        <div class="section-heading">
            <h2>Add New Task</h2>
        </div>

        <form method="post" class="add-task-form">

            <input type="hidden" name="csrf"
                   value="<?php echo escapeHtml($_SESSION['chores_csrf']); ?>">

            <input type="hidden" name="action" value="add">

            <label for="taskTitle">Task name</label>
            <input type="text"
                   id="taskTitle"
                   name="title"
                   maxlength="200"
                   placeholder="e.g. Clean the bathroom"
                   required>

            <label for="taskPerson">Assign to</label>
            <select id="taskPerson" name="person" required>
                <?php foreach ($roommates as $person): ?>
                    <option value="<?php echo escapeHtml($person); ?>">
                        <?php echo escapeHtml($person); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="taskDate">Due date</label>
            <input type="date"
                   id="taskDate"
                   name="due_date"
                   value="<?php echo date('Y-m-d'); ?>"
                   required>

            <button type="submit" class="add-task-button">
                + Add Task
            </button>

        </form>

    </section>

</main>

<nav class="bottom-nav">
    <?php foreach ($navigation as $file => $item): ?>
        <a href="<?php echo escapeHtml($file); ?>"
           class="<?php echo $currentPage === $file ? 'active' : ''; ?>">
            <span class="nav-icon"><?php echo $item[1]; ?></span>
            <span><?php echo escapeHtml($item[0]); ?></span>
        </a>
    <?php endforeach; ?>
</nav>

</div>
</body>
</html>
