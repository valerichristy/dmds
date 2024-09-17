<!DOCTYPE html>
<html>
<head>
  <title>Assignment 4 DMDS</title>
</head>
<body>
  <h1>Competitor</h1>
  <?php
  // connection
  require 'autoload.php';
  use Laudis\Neo4j\ClientBuilder;
  $client = ClientBuilder::create()
   ->withDriver('default', 'bolt://neo4j:password@localhost')
   ->build();

   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // select company name from user input
    $selectComp = $_POST['company'];
    $query = 'MATCH (s1:Supplier)-->()-->()<--()<--(s2:Supplier)
            WHERE s1.companyName = $company
            RETURN s2.companyName as Competitor, count(s2) as NoProducts
            ORDER BY NoProducts DESC';
    
    $param = ['company' => $selectComp];
    $result = $client->run($query, $param);

    echo '<table>';
    echo '<tr><th>Competitor</th>
    <th>NoProducts</th></tr>';

    // print match result
    foreach ($result as $record) {
        echo '<tr><td>' . $record->get('Competitor') . '</td><td>' . $record->get('NoProducts') . '</td></tr>';
    }
    echo '</table>';

  } 

  else {
    // show company list using drop down
    $query = 'MATCH (s:Supplier) RETURN s.companyName as companyName';
    $result = $client->run($query);

    echo '<form action="" method="POST">';
    echo '<label for="company">Select a Company:</label>';
    echo '<select name="company" id="company">';

    foreach ($result as $record) {
        echo '<option value="' . $record->get('companyName') . '">' . $record->get('companyName') . '</option>';
    }

    echo '</select>';
    echo '<button type="submit">submit</button>';
    echo '</form>';

  }
  ?>
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
</body>
</html>
