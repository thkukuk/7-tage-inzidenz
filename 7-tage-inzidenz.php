<?php
include('lib/RKI_Corona_Data.php');
include('lib/RKI_Vaccination.php');

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
$max_cols=GETENV("MAX_COLS");
if (!$max_cols) {
  $max_cols="5";
}

$vaccination_class = new RKI_Vaccination($cache_dir);
$vaccination = $vaccination_class->getCurrent();

echo "<!DOCTYPE html>
      <html>
      <head>
      <title>7 Tage Inzidenzen</title>
      </head>
      <body>";
echo "<table>";
echo "  <tr>";

$cols = 0;
foreach($reg_arr as $reg) {
    $cols++;
    echo "    <td id='tbl_top'>";
    drawWideget($reg, $past_days, $vaccination);
    echo "    </td>";
    if ($cols == $max_cols) {
        echo "  </tr><tr>";
	$cols = 0;
    }
}
unset ($reg);
for ($i = $cols+1; $i <= $max_cols; $i++) {
    echo "<td>&nbsp;</td>";
}
echo "</tr><tr>";
echo "<td colspan='" . $max_cols . "'>Source Code: <a href='https://github.com/thkukuk/7-tage-inzidenz'>https://github.com/thkukuk/7-tage-inzidenz</a></td>";

echo "  </tr>";
echo "</table>";
echo "</body>";

### Functions ###

function drawWideget($id, $past_days, $vaccination)
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

    if  ($today['BundeslandId'] != '0') {
        $incidence_bl = new RKI_Corona_Data($today['BundeslandId'], $cache_dir);
	$today_bl = $incidence_bl->getDaily(0);
        if (!$today_bl) {
            $today_bl = $incidence_bl->getDaily(1);
	}
    }

    # If the big widget is a "Bundesland" or "Germany", show
    # vaccination status
    if ($id == 0) {
        $vacc = $vaccination;
    } else if ($id <= 16) {
        $vacc = $vaccination['states'][$today['GEN']];
    } else {
        $vacc = NULL;
    }

    echo "<div class='widget'>";

    echo "<h3>Inzidenz für " . $today['GEN'] . "</h3>";
    echo "<h6>(Fälle pro 100.000 Einwohner in 7 Tagen)</h6>";

    drawStoplight($today['Inz7T']);

    echo "<table id='tbl_incidence'>";
    printEntry($today, 1, 1, $vacc);
    for ($i = $start_past; $i < ($start_past + $past_days); $i++) {
        $day = $incidence->getDaily($i);
        printEntry($day, 0, 0, NULL);
    }

    # Zeige 7-Tage-Inzidenz vom Bundesland
    if ($today_bl) {
        echo "<tr><td colspan='2'><h3>" . $today_bl['GEN'] . "</h3></td></tr>";
        printEntry($today_bl, 0, 1, $vaccination['states'][$today_bl['GEN']]);
    }
    echo "</table>";
    echo "<h6>Quelle: <a href='https://www.rki.de/DE/Home/homepage_node.html'>RKI</a></h6>";
    echo "</div>";
}

function printColorInz7T($data, $trend, $css_id = NULL)
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

    echo "<td class='" . $co;
    if ($css_id) {
        echo "' id='" . $css_id;
    }
    echo "'>&nbsp;&nbsp;&nbsp;&nbsp;" . number_format($data['Inz7T'], 2, ",", ".");
    if ($trend) {
	echo "<div class='tooltip'>";
        if ($data['trendSlope'] > 0.8) {
            echo "  <font color='red'>&nbsp;&nearr;</font>";
        } else if ($data['trendSlope'] < -0.8) {
            echo "  <font color='green'>&nbsp;&searr;</font>";
        } else {
            echo "  <font color='black'>&nbsp;&rarr;</font>";
        }
	echo "<span class='tooltiptext'>"
	       . number_format($data['trendSlope'], 2, ",", ".")
	       . "</span></div>";
    }
    echo "</td>";
}

function printEntry($data, $main, $trend, $vaccination)
{
    if ($data) {

        echo "<tr>
                <td";
	if ($main) {
	    echo " id='tbl_field_date'>" . germanDay($data['ts']);
	} else {
	    echo " id='tbl_field_fzt'>";
	    echo germanDayAbbr($data['ts']);
	}
        echo ", " . date("d.m.Y", $data['ts']) . "</td>";
	if ($main) {
	    printColorInz7T($data, $trend, "tbl_field_7ti");
	} else {
	    printColorInz7T($data, $trend, "tbl_field_fzn");
	}
        echo "</tr>";

	if ($main) {
	    $class_t = "tbl_field_txt";
	    $class_n = "tbl_field_nr";
	} else {
	    $class_t = "tbl_field_fzt";
	    $class_n = "tbl_field_fzn";
	}
    	echo "<tr>
	       <td id='" . $class_t . "'>Fälle insgesamt:</td>
               <td id='" . $class_n . "'>";
        echo number_format($data['AnzFall'], 0, ",", ".") . " (";
	$new = $data['AnzFallNeu'];
        if ($new > 0) {
	    echo "+";
	} else if ($new < 0) {
	    echo "-";
        }
        echo number_format($new, 0, ",", ".") . ")</td></tr>";

        if ($main) {
	    echo "<tr>
	            <td id='tbl_field_txt'>Tote:</td>
		    <td id='tbl_field_nr'>"
                        . number_format($data['AnzTodesfall'], 0, ",", ".")
		        . " (+" . number_format($data['AnzTodesfallNeu'], 0, ",", ".") . ")</td>";
            echo "</tr>";
	}
        if ($vaccination) {
	    echo "<tr>
	            <td id='" . $class_t . "'>Impfquote:</td>
	            <td id='" . $class_n . "'>"
	                . $vaccination['quote'] . "% / "
		        . $vaccination['2nd_vaccination']['quote']
                        . "%</td>
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
    echo "<div id='div_stoplight' class='" . $color . "'>";
    echo $text;
    echo "</div>";
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
<style>
    body,
    html {
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
        border: thin solid #ccc;
        min-height: 200px;
    }

    #tbl_top {
        vertical-align: top;
    }

    #tbl_incidence {
        width: 100%;
        text-align: center;
    }

    #tbl_incidence td {
        width: 50%;
        border-bottom: thin solid #ccc;
    }

    #tbl_field_date {
        width: 50%;
	text-align: center;
	font-size: 1.2em;
    }

    #tbl_field_7ti {
        width: 50%;
	text-align: center;
	font-size: 1.2em;
    }

    #tbl_field_txt {
        width: 50%;
	text-align: left;
    }

    #tbl_field_nr {
        width: 50%;
	text-align: right;
    }

    #tbl_field_fzt {
        width: 50%;
        font-size: 0.9em;
	text-align: left;
    }

    #tbl_field_fzn {
        width: 50%;
        font-size: 0.9em;
	text-align: right;
    }

    #div_stoplight {
        margin-top: 5%;
        margin-bottom: 5%;
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
</html>
