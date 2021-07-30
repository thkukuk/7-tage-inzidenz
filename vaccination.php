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

body, html {
  font-family: Arial, Helvetica, sans-serif;
}

table {
  border-collapse: collapse;
  border: thin solid #ddd;
  /* width: 100%; */
}

td {
  border: thin solid #ddd;
  padding: 5px;
  text-align: right;
}

td.text { text-align: left; }
td.perc { text-align: center; }
td.perf { text-align: center; }

tr:nth-child(odd){background-color: #f2f2f2;}

tr:hover {background-color: #ddd;}

th {
  border: 1px solid #ddd;
  padding: 5px;
  padding-top: 10px;
  padding-bottom: 10px;
  text-align: center;
  background-color: grey;
  color: white;
}

.quote_low {
  color: #dc143c;
}

.quote_medium {
  color: #b8b800;
}

.quote_good {
  color: green;
}

    </style>
  </head>
  <body>";
echo "<h1>Gegen Corona geimpfte Personen in Deutschland</h1>\n";
echo "<table>";
echo "  <tr>";
echo "    <th>&nbsp;</th><th>1./2. Impfung</th><th>Gesamt 1.</th><th>Neu 1.</th><th>Gesamt 2.</th><th>Neu 2.</th><th>Einwohner</th><th>Performance</th>";
echo "  </tr>";

printEntry("Deutschland", $data, "-");

$performance = array();
foreach($states as $state) {
  $performance[$state] =
      $data['states'][$state]['quote'] +
      $data['states'][$state]['2nd_vaccination']['quote'];
}
array_multisort($performance, SORT_DESC, $performance);
$i=0;
foreach ($performance as $state => $value) {
    $performance[$state] = ++$i;
}

foreach($states as $state) {
    printEntry($state, $data['states'][$state], $performance[$state]);
}
unset ($state);
echo "</table>\n";
echo "Status vom " . date("d.m.Y", $data['ts']) . "<br>\n";
echo "Quelle: <a href='https://www.rki.de/DE/Home/homepage_node.html'>RKI</a>";
echo "</body>";
echo "</html>";

### Functions ###

function printColPercNr($quote) {
    if ($quote >= 85) {
        $color = "quote_good";
    } else if ($quote >= 70) {
        $color = "quote_medium";
    } else {
        $color = "quote_low";
    }

    echo "<span class='" . $color . "'>";
    echo number_format($quote, 2, ",", ".");
    echo "%</span>";
}

function printEntry($state, $data, $performance)
{
    echo "<tr><td class='text'>$state</td><td class='perc'>";
    echo printColPercNr($data['quote']) . " / ";
    echo printColPercNr($data['2nd_vaccination']['quote']) . "</td>";
    echo "<td>" . number_format($data['vaccinated'], 0, ",", ".") . "</td>";
    echo "<td> +" . number_format($data['difference_to_the_previous_day'], 0, ",", ".") . "</td>";
    echo "<td>" . number_format($data['2nd_vaccination']['vaccinated'], 0, ",", ".") . "</td>";
    echo "<td> +" . number_format($data['2nd_vaccination']['difference_to_the_previous_day'], 0, ",", ".") . "</td>";
    echo "<td>" . number_format($data['total'], 0, ",", ".") . "</td>";
    echo "<td class='perf'>" . $performance . "</td>";
    echo "</tr>";
}

?>
