<?php
// Database connection (using SQLite for simplicity)
$db = new SQLite3('database.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);

// Create a table if it doesn't exist for users
$db->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT, comment TEXT)');

// Seed some data if the users table is empty
$user_count_result = $db->querySingle('SELECT COUNT(*) FROM users');
if ($user_count_result == 0) {
    $db->exec("INSERT INTO users (name, comment) VALUES ('Alice', 'Hello from Alice!')");
    $db->exec("INSERT INTO users (name, comment) VALUES ('Bob', 'Bob\'s first comment.')");
    $db->exec("INSERT INTO users (name, comment) VALUES ('Charlie', 'Charlie\'s thoughts.')");
}

// Create a table for admin credentials if it doesn't exist
$db->exec('CREATE TABLE IF NOT EXISTS admin_credentials (id INTEGER PRIMARY KEY, username TEXT, password_hash TEXT)');

// Seed some admin credentials if the table is empty
$admin_count_result = $db->querySingle('SELECT COUNT(*) FROM admin_credentials');
if ($admin_count_result == 0) {
    // In a real app, use password_hash() and proper hashing!
    $db->exec("INSERT INTO admin_credentials (username, password_hash) VALUES ('admin', 'fake_hash_for_admin_password123')");
    $db->exec("INSERT INTO admin_credentials (username, password_hash) VALUES ('root', 'fake_hash_for_root_password456')");
}

$xss_name = '';
if (isset($_GET['name'])) {
    $xss_name = $_GET['name']; // XSS vulnerability here
}

$sql_id = '';
$user_comment = '';
if (isset($_GET['id'])) {
    $sql_id = $_GET['id'];
    // SQL Injection vulnerability here
    $query = "SELECT name, comment FROM users WHERE id = $sql_id"; // This query selects 2 columns
    $result = $db->query($query);
    if ($result) {
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row) {
            $user_comment = "<p><strong>Name:</strong> " . htmlspecialchars($row['name']) . "</p><p><strong>Comment:</strong> " . htmlspecialchars($row['comment']) . "</p>";
        } else {
            $user_comment = "<p>No user found with ID: " . htmlspecialchars($sql_id) . "</p>";
        }
    } else {
        // Display database errors for debugging the injection, but in prod this is also a vulnerability (info disclosure)
        $user_comment = "<p>Error in query: " . htmlspecialchars($db->lastErrorMsg()) . "</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vulnerable Web App</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
        }
        form {
            margin-bottom: 20px;
        }
        input[type='text'], input[type='submit'] {
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        input[type='submit'] {
            background-color: #5cb85c;
            color: white;
            cursor: pointer;
        }
        input[type='submit']:hover {
            background-color: #4cae4c;
        }
        .output {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #eee;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to the Vulnerable Web App!</h1>

        <h2>XSS Test</h2>
        <form method="GET" action="index.php">
            <label for="name">Enter your name:</label>
            <input type="text" id="name" name="name">
            <input type="submit" value="Greet Me">
        </form>
        <?php if (!empty($xss_name)): ?>
            <div class="output">
                <p>Hello, <?php echo $xss_name; // Outputting directly - XSS vulnerability ?>!</p>
            </div>
        <?php endif; ?>

        <h2>SQL Injection Test</h2>
        <p>Try to retrieve credentials from the <code>admin_credentials</code> table (columns: <code>username</code>, <code>password_hash</code>) using a UNION attack.</p>
        <form method="GET" action="index.php">
            <label for="id">Enter User ID to view comment:</label>
            <input type="text" id="id" name="id" placeholder="e.g., 1 UNION SELECT username, password_hash FROM admin_credentials">
            <input type="submit" value="View Comment">
        </form>
        <?php if (!empty($user_comment)): ?>
            <div class="output">
                <?php echo $user_comment; ?>
            </div>
        <?php endif; ?>

        <h3>Existing Users (for reference)</h3>
        <ul>
            <?php
            $all_users_result = $db->query('SELECT id, name FROM users');
            while ($user = $all_users_result->fetchArray(SQLITE3_ASSOC)) {
                echo "<li>ID: " . htmlspecialchars($user['id']) . " - Name: " . htmlspecialchars($user['name']) . "</li>";
            }
            ?>
        </ul>

    </div>
</body>
</html>

<?php
$db->close();
?> 