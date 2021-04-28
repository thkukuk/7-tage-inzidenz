<?php
include('lib/RKI_Vaccination.php');

### Global configuration ###
$cache_dir = '/data';

$states = [
	'Baden-Württemberg',
	'Bayern',
	'Berlin',
	'Brandenburg',
	'Bremen',
	'Hamburg',
	'Hessen',
	'Mecklenburg-Vorpommern',
	'Niedersachsen',
	'Nordrhein-Westfalen',
	'Rheinland-Pfalz',
	'Saarland',
	'Sachsen',
	'Sachsen-Anhalt',
	'Schleswig-Holstein',
	'Thüringen'
	];

### Main ###

$vaccination = new RKI_Vaccination($cache_dir);
$data = $vaccination->getCurrent();

echo "<!DOCTYPE html>
<html lang='de'>
  <head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
    <title>Corona Impfquoten</title>
    <style>
#customers {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  border: 1px solid black;
  /* width: 100%; */
}

#customers td {
  border: 1px solid #ddd;
  padding: 5px;
  text-align: right;
}

#customers td.text { text-align: left; }
#customers td.perc { text-align: center; }

#customers tr:nth-child(even){background-color: #f2f2f2;}

#customers tr:hover {background-color: #ddd;}

#customers th {
  border: 1px solid #ddd;
  padding: 5px;
  padding-top: 10px;
  padding-bottom: 10px;
  text-align: center;
  background-color: grey;
  color: white;
}
    </style>
  </head>
  <body>";
echo "<h1>Gegen Corona geimpfte Personen in Deutschland</h1>\n";
echo "<table id='customers'>";
echo "  <tr>";
echo "    <th>&nbsp;</th><th>1./2. Impfung</th><th>Gesamt 1.</th><th>Neu 1.</th><th>Gesamt 2.</th><th>Neu 2.</th><th>Einwohner</th>";
echo "  </tr>";

printEntry("Deutschland", $data);

foreach($states as $state) {
    printEntry($state, $data['states'][$state]);
}
unset ($state);
echo "</table>\n";
echo "Status vom " . date("d.m.Y", $data['ts']) . "<br>\n";
echo "Quelle: <a href='https://www.rki.de/DE/Home/homepage_node.html'>RKI</a>";
echo "</body>";
echo "</html>";

### Functions ###

function printEntry($state, $data)
{
    echo "<tr><td class='text'>$state</td><td class='perc'>";
    echo number_format($data['quote'], 2) . "% / ";
    echo number_format($data['2nd_vaccination']['quote'], 2) . "%</td>";
    echo "<td>" . number_format($data['vaccinated'], 0, ",", ".") . "</td>";
    echo "<td> +" . number_format($data['difference_to_the_previous_day'], 0, ",", ".") . "</td>";
    echo "<td>" . number_format($data['2nd_vaccination']['vaccinated'], 0, ",", ".") . "</td>";
    echo "<td> +" . number_format($data['2nd_vaccination']['difference_to_the_previous_day'], 0, ",", ".") . "</td>";
    echo "<td>" . number_format($data['total'], 0, ",", ".") . "</td>";
    echo "</tr>";
}

?>
