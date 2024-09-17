<?php
require 'Predis/Predis/Autoload.php';

use Predis\Client;

$redis = new Predis\Client([
    'scheme' => 'tcp',
    'host'   => 'localhost',
    'port'   => 6379,
]);

$client = new Client();

if ($redis->ping()) {
    echo "punten";
   }


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
$csv_file = $_FILES['csv_file']['tmp_name'];

// read csv
if (($handle = fopen($csv_file, "r")) !== false) {
        $data = array();
        $header = fgetcsv($handle, 1000, ",");
        // $tskey = 'ts';

        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            // change dt to msc
            $timestamp = strtotime($row[0]);
            $msc = $timestamp * 1000;
            $val = array();
            $data[] = $row;

            for($i=1; $i<count($row); $i++){
                $val[$header[$i]] = $row[$i];
                // $redis->executeRaw(['TS.ADD', '$ts_key', $msc, $val[$i],]);
            }
             // create rule
            // $redis->executeRaw(['TS.CREATE', "compact"]);
            // $redis->executeRaw(['TS.CREATERULE', 'csv_data1', "compact1", 'AGGREGATION', 'avg', '31540000000']);
            // $redis->executeRaw(['TS.CREATERULE', 'csv_data2', "compact2", 'AGGREGATION', 'avg', '31540000000']);
            // $redis->executeRaw(['TS.CREATERULE', 'csv_data3', "compact3", 'AGGREGATION', 'avg', '31540000000']);
            // $redis->executeRaw(['TS.CREATERULE', 'csv_data4', "compact4", 'AGGREGATION', 'avg', '31540000000']);
            // $redis->executeRaw(['TS.CREATERULE', 'csv_data5', "compact5", 'AGGREGATION', 'max', '31540000000']);
            // $redis->executeRaw(['TS.CREATERULE', 'csv_data6', "compact6", 'AGGREGATION', 'max', '31540000000']);
            // $redis->executeRaw(['TS.CREATERULE', 'csv_data7', "compact7", 'AGGREGATION', 'min', '31540000000']);
            // $redis->executeRaw(['TS.CREATERULE', 'csv_data8', "compact8", 'AGGREGATION', 'max', '31540000000']);

            // create rule adn insert data to redis timeseries
            for ($i=1; $i<count($row); $i++){
                // delete key if exist
                $redis->executeRaw(['TS.DELETE', "compact_$i"]);
                $redis->executeRaw(['TS.CREATE', "compact_$i"]);
                $redis->executeRaw(['TS.EXPIRE', "compact_$i", 120]);
                if($i==1 || $i==2 || $i==7 || $i==8){
                    $redis->executeRaw(['TS.CREATERULE', "row_$i", "compact_$i", 'AGGREGATION', 'avg', '31540000000']);
                }
                if($i==3 || $i==4){
                    $redis->executeRaw(['TS.CREATERULE', "row_$i", "compact_$i", 'AGGREGATION', 'max', '31540000000']);
                }
                if($i==5 || $i==6){
                    $redis->executeRaw(['TS.CREATERULE', "row_$i", "compact_$i", 'AGGREGATION', 'min', '31540000000']);
                }
                $redis->executeRaw(['TS.ADD', "row_$i", $msc, $row[$i]]);
            }
            // echo gettype($row);
            $len = count($row);
        }
        // echo gettype($len);
        fclose($handle);

        // $result = $client->executeRaw(['TS.RANGE', 'csv_data', '-', '+']);

        // $redis->executeRaw(['TS.RANGE', 'csv_data', '-', '+','AGGREGATION', 'avg', '31540000000']);
        // echo gettype($row);
        // aggregate the data
        $agg_data = array();
        for($i=1 ; $i<$len ; $i++){
            if($i==1 || $i==2 || $i==7 || $i==8){
                array_push($agg_data,$redis->executeRaw(['TS.RANGE', "row_$i", '-', '+','AGGREGATION', 'avg', '31540000000']));
            }
            if($i==3 || $i==4){
                array_push($agg_data,$redis->executeRaw(['TS.RANGE', "row_$i", '-', '+','AGGREGATION', 'max', '31540000000']));
            }
            if($i==5 || $i==6){
                array_push($agg_data,$redis->executeRaw(['TS.RANGE', "row_$i", '-', '+','AGGREGATION', 'min', '31540000000']));
            }
        }

    }
}

// print original data
if (isset($data)) {
    echo '<table>';
    echo '<tr>';

    foreach ($header as $cell) {
        echo '<th>' . $cell . '</th>';
    }
    echo '</tr>';

    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
        echo '<td>' . $cell . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
}

// Print aggregated data
echo '<table>';
echo '<tr>';
echo '<th>Time</th>
     <th>Land AVG</th>
     <th>Land AVG Uncertain</th>
     <th>Land MAX</th>
     <th>Land MAX Uncertain</th>
     <th>Land MIN</th>
     <th>Land MIN Uncertain</th>
     <th>Land & Ocean AVG</th>
     <th>Land & Ocean AVG Uncertain</th>';
// foreach ($header as $cell) {
//     echo '<th>' . $cell . '</th>';
// }
// echo '</tr>';
// $start=1970;
// for($i=0;$i<count($agg_data[0]);$i++) {
//     echo "<tr><td>" .$start. "</td><td>" . $agg_data[0][$i][1] . "</td><td>" . $yearly_data[1][$i][1] . "</td><td>" . $yearly_data[2][$i][1]."</td><td>" . $yearly_data[3][$i][1] . "</td><td>" . $yearly_data[4][$i][1] . "</td><td>" . $yearly_data[5][$i][1] . "</td><td>" . $yearly_data[6][$i][1] . "</td><td>" . $yearly_data[7][$i][1] . "</td></tr>";
//     $start++;
//   }

echo '</table>';

// foreach ($result as $data) {
//     $timestamp = date($data[0]);
//     $value = $data[1];
//     echo '<tr>';
//     echo '<td>' . $timestamp . '</td>';
//     echo '<td>' . $value . '</td>';
//     echo '</tr>';
// };

?>
<style>
        table {
            width: 50%;
            border: 1px solid;
        }
        th, td, tr {
            /* text-align: center; */
            padding: 5px;
            border: 1px solid;
        }
</style>

<form method="post" enctype="multipart/form-data">
  <input type="file" name="csv_file">
  <button type="submit" name="submit">Upload</button>
</form>

