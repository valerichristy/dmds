<!DOCTYPE html>
<html>
<head>
    <title>Assignment 3 DMDS </title>
</head>
<style>
        table {
            width: 50%;
            border: 1px solid;
        }

        th, td {
            /* text-align: center; */
            padding: 5px;
            border: 1px solid;
        }

        th {
            text-align: center;
            background-color: #B2BEB5;
            color: black;
        }

        form {
            margin-top: 20px;
        }
    </style>
<body>
    <h2>Filter Thread, User, Tags</h2>
    <p>Note: case sensitive</p>
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <label for="thread">Thread:</label>
        <input type="text" id="thread" name="thread">
        <br>
        <label for="user">User:</label>
        <input type="text" id="user" name="user">
        <br>
        <label for="tag">Tag:</label>
        <input type="text" id="tag" name="tag">
        <br>
        <br>
        <input type="submit" value="Filter">
    </form>
    <p>if filter empty, filter button will show all data. if filter is not empty, filter button will show filtered result</p>
    
    <!-- <script>
        function showAllData() {
            document.getElementById("thread").value = "";
            document.getElementById("user").value = "";
            document.getElementById("tag").value = "";
            document.getElementById("filterForm").submit();
        }
    </script> -->
</body>
</html>


<?php
// require 'vendor/autoload.php';
// use MongoDB\Driver\Manager;
$mongo = new MongoDB\Driver\Manager("mongodb://localhost:27017");
echo ("ppp");

// Get user input for the filter criteria
$thread = $_POST['thread'] ?? '';
$user = $_POST['user'] ?? '';
$tag = $_POST['tag'] ?? '';

// Construct the filter based on user input
$filter = [];
if (!empty($thread)) {
    $filter['thread'] = $thread;
}
if (!empty($user)) {
    $filter['user'] = $user;
}
if (!empty($tag)) {
    $filter['tags'] = $tag;
}

// Construct the query based on the filter
$query = new MongoDB\Driver\Query($filter);

// Execute the query
$result = $mongo->executeQuery('assignment3.db_forum', $query);

// Prepare table structure
$table = '<table>';
$table .= '<tr><th>User</th><th>Timestamp</th><th>Thread</th><th>Message</th><th>Tags</th></tr>';

// Iterate over the result and add rows to the table
foreach ($result as $document) {
    $table .= '<tr>';
    $table .= '<td>' . $document->user . '</td>';
    $table .= '<td>' . $document->timestamp . '</td>';
    $table .= '<td>' . $document->thread . '</td>';
    $table .= '<td>' . $document->message . '</td>';
    $table .= '<td>' . implode(', ', $document->tags) . '</td>';
    $table .= '</tr>';
}

$table .= '</table>';

// Display the filtered results if any filter is applied
if (!empty($thread) || !empty($user) || !empty($tag)) {
    echo "<h3>Filtered results</h3>";
    echo $table;
} else {
    echo "<h3>All data</h3>";
    echo $table;
}

// phpinfo();
?>

