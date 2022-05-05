<?php
include('lib/RKI_Corona_Data.php');
include('lib/Mobile_Detect.php');

# Find your AdmUnitID for the comma separated REGIONS list here:
# https://www.arcgis.com/apps/mapviewer/index.html?layers=c093fe0ef8fd4707beeb3dc0c02c3381

### Global configuration ###
$cache_dir = '/data';
$threshold_yellow = 35;
$threshold_red = 100;

### Main ###

$regions=getenv("REGIONS");
if (!$regions) {
  # if no region is specified, use Germany
  $regions="0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16";
}
$reg_arr = explode(',', $regions);

$past_days=getenv("PAST_DAYS");
if (!$past_days) {
  $past_days="7";
}

echo "<!DOCTYPE html>
<html lang='de'>
  <head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
    <title>7 Tage Inzidenzen</title>
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
th.text { text-align: left; }
td.inzidenz { text-align: center; }
td.perf { text-align: center; }
td.number { text-align: right; }

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

.value_red {
  color: #dc143c;
}


.value_yellow {
  color: #b8b800;
}

.value_green {
  color: green;
}

    .tooltip {
      position: relative;
      display: inline-block;
      /* border-bottom: 1px dotted black; */
    }

    .tooltip .tooltiptext {
      visibility: hidden;
      width: 50px;
      bottom: 100%;
      left: 50%;
      margin-left: -25px; /* Use half of the width (50/2 = 25), to center the tooltip */
      background-color: black;
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 5px 0;

      /* Position the tooltip */
      position: absolute;
      z-index: 1;
    }

    .tooltip:hover .tooltiptext {
      visibility: visible;
    }

    </style>
  </head>
  <body>";

    echo "<h1>7-Tage Inzidenz";

    # if we don't have data for today, start with yesterdays data as current
    # ones.
    $start_past = 1;

    $incidence = new RKI_Corona_Data(0, $cache_dir);

    $today = $incidence->getDaily(0);
    if (!$today) {
        $today = $incidence->getDaily(1);
	if ($today) {
            $start_past = 2;
	}
    }

    echo " - " . germanDay($today['ts']) . ", " . date("d.m.Y", $today['ts']) . "</h1>";
    echo "<table>";
    echo "  <tr>";
    echo "    <th class='text'>Region</th><th>Inzidenz</th><th>Fälle insgesamt</th><th>Tote</th>";
    for ($i = $start_past; $i < ($start_past + $past_days); $i++) {
        $day = $incidence->getDaily($i);
	if ($day) {
	    echo "<th>" . germanDayAbbr($day['ts']) . ", " . date("d.m.", $day['ts']) . "</th>";
	} else {
	    echo "<th>&nbsp;</th>";
	}
    }
    echo "  </tr>";

$cols = 0;
foreach($reg_arr as $reg) {
    PrintRegion($reg, $start_past, $past_days);
}
unset ($reg);

echo "</table>\n";
echo "<a href='https://www.rki.de/DE/Home/homepage_node.html'>RKI</a><br>Source Code: <a href='https://github.com/thkukuk/7-tage-inzidenz'>https://github.com/thkukuk/7-tage-inzidenz</a>";
echo "</body>";
echo "</html>";

### Functions ###

function PrintRegion($id, $start_past, $past_days)
{
    global $cache_dir;

    $today_bl = NULL;
    # if we don't have data for today, start with yesterdays data as current
    # ones.
    $start_past = 1;

    $incidence = new RKI_Corona_Data($id, $cache_dir);

    $today = $incidence->getDaily(0);
    if (!$today) {
        $today = $incidence->getDaily(1);
	if (!$today) {
	    # No data at all, quit
	    return;
	}
	$start_past = 2;
    }

    if ($today['BundeslandId'] != '0' AND $today['BundeslandId'] != $today['AdmUnitId']) {
        $incidence_bl = new RKI_Corona_Data($today['BundeslandId'], $cache_dir);
	$today_bl = $incidence_bl->getDaily(0);
        if (!$today_bl) {
            $today_bl = $incidence_bl->getDaily(1);
	}
    }

    echo "<tr><td class='text'>" . $today['GEN'] . "</td><td class='inzidenz'>";
    printColorInz7T($today, 1);
    echo "</td><td class='number'>";
    echo number_format($today['AnzFall'], 0, ",", ".") . " (";
    $new = $today['AnzFallNeu'];
    if ($new > 0) {
        echo "+";
    }
    echo number_format($new, 0, ",", ".") . ")</td>";
    echo "<td class='number'>"
         . number_format($today['AnzTodesfall'], 0, ",", ".")
         . " (+" . number_format($today['AnzTodesfallNeu'], 0, ",", ".") . ")</td>";

    for ($i = $start_past; $i < ($start_past + $past_days); $i++) {
        $day = $incidence->getDaily($i);
	if ($day) {
	    echo "<td>";
	    printColorInz7T($day, 0);
            echo "</td>";
	} else {
	    echo "<td>&nbsp;</td>";
	}
    }
    echo "</tr>";
}

function printColorInz7T($data, $trend)
{
    global $threshold_yellow;
    global $threshold_red;

    $inc = round($data['Inz7T'], 2);
    if ($inc >= $threshold_red) {
        $co = "value_red";
    } else if ($inc >= $threshold_yellow) {
        $co = "value_yellow";
    } else {
        $co = "value_green";
    }

    echo "<span class='" . $co . "'>&nbsp;&nbsp;&nbsp;&nbsp;";
    echo number_format($data['Inz7T'], 2, ",", ".") . "</span>\n";
    if ($trend) {
	echo "<div class='tooltip'>";
        if ($data['trendSlope'] >= 0.1) {
            echo "<span style='color:red;font-weight:bold'>&nbsp;&nearr;</span>";
        } else if ($data['trendSlope'] <= -0.1) {
            echo "<span style='color:green;font-weight:bold'>&nbsp;&searr;</span>";
        } else {
            echo "<span style='color:black;font-weight:bold'>&nbsp;&rarr;</span>";
        }
	echo "<span class='tooltiptext'>"
	       . number_format($data['trendSlope'], 2, ",", ".")
	       . "</span></div>";
    }
}

function germanDay($ts)
{
    $d = [
        1 => "Montag",
        2 => "Dienstag",
        3 => "Mittwoch",
        4 => "Donnerstag",
        5 => "Freitag",
        6 => "Samstag",
        7 => "Sonntag"
    ];
    return $d[date("N", $ts)];
}

function germanDayAbbr($ts)
{
    $d = [
        1 => "Mo",
        2 => "Di",
        3 => "Mi",
        4 => "Do",
        5 => "Fr",
        6 => "Sa",
        7 => "So"
    ];
    return $d[date("N", $ts)];
}

?>
