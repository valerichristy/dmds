<?php
require 'Predis/Predis/Autoload.php';

use Predis\Client;

$redis = new Client();
// //$redis->set('khiwl', 'lalala');
// $test = $redis->get('abcd');
// echo $test;
// echo 'duar';

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $value = $_POST['value'];

    // $length = $redis->lrange('people', 0, -1);

    if ($action=='lpush'){
        $redis->lpush('people', $value);
        // use ltrim to keep the value at 10 by deleting the last value
        $redis->ltrim('people', 0, 9);   
    } elseif($action=='rpush'){
        $redis->rpush('people', $value);
        // use ltrim to keep the value at 10 by deleting the first value
        $redis->ltrim('people', -10, -1);
    } elseif($action=='lpop'){
        $redis->lpop('people');
    } elseif($action=='rpop'){
        $redis->rpop('people');
    }

}
$values = $redis->lrange('people', 0, -1);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assignment 1 DMDS</title>
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
</head>

<body>
    <h1>Redis List Example</h1>
    <p>It can only show 10 values</p>
    <p>If adding a new value with LPUSH, it will delete the last data</p>
    <p>If adding a new value with RPUSH, it will delete the first data</p>
    <table id="myTable", style="width:40%">
        <tr>
            <th style="width:75%">PEOPLE</th>
        </tr>
        <?php foreach ($values as $index => $value): ?>
            <tr>
                <td align=center><?php echo $value; ?></td>
            </tr>
        <?php endforeach; ?>
        <td>
            <form method="post">
            <input type="text" name="value" placeholder="input value here">
            <input type="submit" name="action" value="lpush">
            <input type="submit" name="action" value="rpush">
            <input type="submit" name="action" value="lpop">
            <input type="submit" name="action" value="rpop">
            </form>
        </td>
    </table>

</body>
</html>