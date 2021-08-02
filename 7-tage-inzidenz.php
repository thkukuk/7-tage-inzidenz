<?php
include('lib/RKI_Corona_Data.php');
include('lib/RKI_Vaccination.php');
include('lib/Mobile_Detect.php');

# Find your AdmUnitID for the comma separated REGIONS list here:
# https://www.arcgis.com/apps/mapviewer/index.html?layers=c093fe0ef8fd4707beeb3dc0c02c3381

### Global configuration ###
$cache_dir = '/data';
$threshold_green = 50;
$threshold_yellow = 100;
$threshold_red = 150;

### Main ###

$regions=getenv("REGIONS");
if (!$regions) {
  # if no region is specified, use Germany
  $regions="0";
}
$reg_arr = explode(',', $regions);

$past_days=getenv("PAST_DAYS");
if (!$past_days) {
  $past_days="7";
}

$browser = new Mobile_Detect;
if ($browser->isTablet()) {
  $max_cols="3";
} else if ($browser->isMobile()) {
  $max_cols="1";
} else {
  $max_cols=GETENV("MAX_COLS");
  if (!$max_cols) {
    $max_cols="5";
  }
}

$vaccination_class = new RKI_Vaccination($cache_dir);
$vaccination = $vaccination_class->getCurrent();

echo "<!DOCTYPE html>
<html lang='de'>
  <head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
    <title>7 Tage Inzidenzen</title>
    <style>

    body, html {
      font-family: Arial, Helvetica, sans-serif;
    }

    h3 {
        text-align: center;
        margin: 1%;
    }

    h6 {
        text-align: center;
        margin: 1%;
        font-size: 0.5em;
    }

    .widget {
        width: 285px;
        border: thin solid #ddd;
        min-height: 200px;
    }

    table.tbl_incidence {
        border-collapse: collapse;
        border: thin solid #ddd;
        width: 100%;
    }

    table.tbl_incidence th {
        width: 50%;
	border-top: thin solid #ddd;
	text-align: center;
        font-size: 1.2em;
    }

    table.tbl_incidence td {
        width: 50%;
	border-top: thin solid #ddd;
    }

    table.tbl_incidence tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    table.tbl_incidence tr:hover {
        background-color: #ddd;
    }

    td.tbl_top {
        vertical-align: top;
    }

    td.left {
        width: 50%;
	text-align: left;
    }

    td.right {
        width: 50%;
	text-align: right;
    }

    td.small_left {
        width: 50%;
	text-align: left;
	font-size: 0.9em;
    }

    td.small_right {
        width: 50%;
	text-align: right;
	font-size: 0.9em;
    }

    div.stoplight {
        /* margin-top: 5%; */
        /* margin-bottom: 5%; */
        padding-top: 5%;
        width: 100%;
        height: 40px;
        text-align: center;
        vertical-align: middle;
        font-size: 1.5em;
        color: #ccc;
    }

    .stoplight_fullstop {
        background-color: darkred;
    }

    .stoplight_stop {
        background-color: #ff0028;
    }

    .stoplight_risk {
        background-color: yellow;
    }

    .stoplight_ok {
        background-color: green;
    }

    .value_fullstop {
        color: darkred;
    }

    .value_stop {
        color: #dc143c;
    }

    .value_risk {
        color: #b8b800;
    }

    .value_ok {
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
echo "    <table>";
echo "      <tr>";

$cols = 0;
foreach($reg_arr as $reg) {
    $cols++;
    echo "      <td class='tbl_top'>";
    drawWidget($reg, $past_days, $vaccination);
    echo "      </td>";
    if ($cols == $max_cols) {
        echo "    </tr><tr>";
	$cols = 0;
    }
}
unset ($reg);
for ($i = $cols+1; $i <= $max_cols; $i++) {
    echo "<td>&nbsp;</td>";
}
echo "</tr><tr>";
echo "<td colspan='" . $max_cols . "'>Quelle: <a href='https://www.rki.de/DE/Home/homepage_node.html'>RKI</a><br>Source Code: <a href='https://github.com/thkukuk/7-tage-inzidenz'>https://github.com/thkukuk/7-tage-inzidenz</a></td>";
echo "  </tr>";
echo "</table>";
echo "</body>";
echo "</html>";

### Functions ###

function drawWidget($id, $past_days, $vaccination)
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

    # If the big widget is a "Bundesland" or "Germany", show
    # vaccination status
    $vacc = NULL;
    if ($id <= 16) {
        foreach($vaccination['data'] as $state) {
            if ( $state['name'] == $today['GEN']) {
	      $vacc = $state;
            }
	}
	unset($state);
    }

    echo "<div class='widget'>";

    echo "<h3>Inzidenz für " . $today['GEN'] . "</h3>";
    echo "<h6>(Fälle pro 100.000 Einwohner in 7 Tagen)</h6>";

    drawStoplight($today['Inz7T']);

    echo "<table class='tbl_incidence'>";
    printEntry($today, 1, 1, $vacc, $vaccination['ts']);
    for ($i = $start_past; $i < ($start_past + $past_days); $i++) {
        $day = $incidence->getDaily($i);
        printEntry($day, 0, 0, NULL, NULL);
    }

    # Zeige 7-Tage-Inzidenz vom Bundesland
    if ($today_bl) {
        echo "<tr><th colspan='2'>" . $today_bl['GEN'] . "</th></tr>";
	$vacc = NULL;
        foreach($vaccination['data'] as $state) {
            if ( $state['name'] == $today_bl['GEN']) {
	      $vacc = $state;
            }
	}
        printEntry($today_bl, 0, 1, $vacc, $vaccination['ts']);
	unset($state);
    }
    echo "</table>";
    echo "</div>";
}

function printColorInz7T($data, $trend)
{
    global $threshold_green;
    global $threshold_yellow;
    global $threshold_red;

    $inc = round($data['Inz7T'], 2);
    if ($inc < $threshold_green) {
        $co = "value_ok";
    } else if ($inc < $threshold_yellow) {
        $co = "value_risk";
    } else if ($inc < $threshold_red) {
        $co = "value_stop";
    } else {
        $co = "value_fullstop";
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

function printEntry($data, $main, $trend, $vaccination, $ts)
{
    if ($data) {

        echo "<tr>\n";
	if ($main) {
	    echo " <th>" . germanDay($data['ts']);
	} else {
	    echo " <td class='small_left'>" . germanDayAbbr($data['ts']);
	}
        echo ", " . date("d.m.Y", $data['ts']);
	if ($main) {
	    echo "</th><th>";
	    printColorInz7T($data, $trend);
	    echo "</th>";
	} else {
	    echo "</td><td class='small_right'>";
	    printColorInz7T($data, $trend);
	    echo "</td>";
	}
        echo "</tr>";

	if ($main) {
	    $class_t = "left";
	    $class_n = "right";
	} else {
	    $class_t = "small_left";
	    $class_n = "small_right";
	}
    	echo "<tr>
	       <td class='" . $class_t . "'>Fälle insgesamt:</td>
               <td class='" . $class_n . "'>";
        echo number_format($data['AnzFall'], 0, ",", ".") . " (";
	$new = $data['AnzFallNeu'];
        if ($new > 0) {
	    echo "+";
	}
        echo number_format($new, 0, ",", ".") . ")</td></tr>";

        if ($main) {
	    echo "<tr>
	            <td class='left'>Tote:</td>
		    <td class='right'>"
                        . number_format($data['AnzTodesfall'], 0, ",", ".")
		        . " (+" . number_format($data['AnzTodesfallNeu'], 0, ",", ".") . ")</td>";
            echo "</tr>";
	}
        if ($vaccination) {
	    echo "<tr>
	            <td class='" . $class_t . "'>Impfquote:</td>
	            <td class='" . $class_n . "'><div class='tooltip'>"
	                . $vaccination['vaccinatedAtLeastOnce']['quote'] . "% / "
		        . $vaccination['fullyVaccinated']['quote']
                        . "%<span class='tooltiptext'>"
			. date("d.m.", $ts)
			. "</span></div></td>
		  </tr>";
	}
    }
}

function drawStoplight($cases7_per_100k)
{
    global $threshold_green;
    global $threshold_yellow;
    global $threshold_red;

    if ($cases7_per_100k < $threshold_green) {
        $color = "stoplight_ok";
        $text = "Geöffnet";
    } else if ($cases7_per_100k < $threshold_yellow) {
        $color = "stoplight_risk";
        $text = "Click&Meet";
    } else if ($cases7_per_100k < $threshold_red) {
        $color = "stoplight_stop";
        $text = "Mit Schnelltest";
    } else {
        $color = "stoplight_fullstop";
        $text = "Geschlossen";
    }
    echo "<div class='stoplight " . $color . "'>";
    echo $text;
    echo "</div>\n";
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
