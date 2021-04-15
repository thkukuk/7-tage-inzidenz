<?php
include('src/Incidence.php');

### Configure here ###

# Find your region here and get the OBJECTID:
# https://npgeo-corona-npgeo-de.hub.arcgis.com/datasets/917fc37a709542548cc3be077a786c17_0
$nordfriesland = 7; // Kreis Nordfriesland
$fuerth = 283; //Stadt Fürth
$nuernberg = 284; //Stadt Nürnberg
$muenchen = 224; //Stadt München
### End of configs ###

### Main ###

echo "<!DOCTYPE html>
      <html>
      <head>
      <title>7 Tage Inzidenzen</title>
      </head>
      <body>";
echo "<table>";
echo "  <tr>";
echo "    <td>";
drawWideget($nordfriesland);
echo "    </td>";
echo "    <td>";
drawWideget($fuerth);
echo "    </td>";
echo "    <td>";
drawWideget($nuernberg);
echo "    </td>";
echo "    <td>";
drawWideget($muenchen);
echo "    </td>";
echo "  </tr>";
echo "</table>";
echo "</body>";

### Functions ###

function drawWideget($id)
{
    $cache_file = '/data/data-' . $id . '.json';
    $threshold_green = 50;
    $threshold_yellow = 100;
    $threshold_red = 200;

    $incidence = new Incidence($id, $cache_file);

    $today = $incidence->getDaily(0);
    $day_1 = $incidence->getDaily(1);
    $day_2 = $incidence->getDaily(2);
    $day_3 = $incidence->getDaily(3);
    $day_4 = $incidence->getDaily(4);
    $day_5 = $incidence->getDaily(5);
    $day_6 = $incidence->getDaily(6);
    $day_7 = $incidence->getDaily(7);

    echo "<div class='widget'>";

    echo "<h3>Inzidenz für " . $today['GEN'] . "</h3>";
    echo "<h6>(Fälle pro 100.000 Einwohner in 7 Tagen)</h6>";

    drawStoplight($today, $threshold_green, $threshold_yellow, $threshold_red);

    echo "<table id='tbl_incidence'>";
    echo drawLine($today, $day_1, $threshold_green, $threshold_yellow, $threshold_red);
    echo drawLine($day_1, $day_2, $threshold_green, $threshold_yellow, $threshold_red);
    echo drawLine($day_2, $day_3, $threshold_green, $threshold_yellow, $threshold_red);
    echo drawLine($day_3, $day_4, $threshold_green, $threshold_yellow, $threshold_red);
    echo drawLine($day_4, $day_5, $threshold_green, $threshold_yellow, $threshold_red);
    echo drawLine($day_5, $day_6, $threshold_green, $threshold_yellow, $threshold_red);
    echo drawLine($day_6, $day_7, $threshold_green, $threshold_yellow, $threshold_red);
    echo drawLine($day_7, 0, $threshold_green, $threshold_yellow, $threshold_red);

    echo "
         <tr><td colspan='2'>&nbsp;</td></tr>
	 <tr><td id='tbl_incidence_left'>Fälle letzte 7 Tage</td><td id='tbl_incidence_right'>" . $today['cases7_lk'] . "</td></tr>
	 <tr><td id='tbl_incidence_left'>Tote:</td><td id='tbl_incidence_right'>" . $today['deaths'] . "</td></tr>
	 <tr><td colspan='2'><h3>" . $today['BL'] . "</h3></td></tr>
	 <tr><td id='tbl_incidence_left'>7-Tage-Inzidenz</td><td id='tbl_incidence_right'>" . round($today['cases7_bl_per_100k'], 2) . "</td></tr>
	 <tr><td id='tbl_incidence_left'>Fälle letzte 7 Tage</td><td id='tbl_incidence_right'>" . $today['cases7_bl'] . "</td></tr>";

    echo "</table>";
    echo "<h6>Quelle: <a href='https://www.rki.de/DE/Home/homepage_node.html'>RKI</a></h6>";
    echo "</div>";
}

function drawLine($data, $data_old, $threshold_green, $threshold_yellow, $threshold_red)
{
    if ($data) {

        $inc = round($data['cases7_per_100k'], 2);
        if ($inc < $threshold_green) {
            $co = "value_ok";
	} else if ($inc < $threshold_yellow) {
	    $co = "value_risk";
	} else if ($inc < $threshold_red) {
	    $co = "value_stop";
        } else {
            $co = "value_fullstop";
        }

        echo "<tr>
                <td>" . germanDay($data['ts']) . ", " . date("d.m.Y", $data['ts']) . "</td>
                <td class='" . $co . "'>" . round($data['cases7_per_100k'], 2) . "</td>
              </tr>
    	      <tr>
	        <td id='tbl_incidence_fzt'>Fälle insgesamt:</td>
		<td id='tbl_incidence_fzn'>" . $data['cases'];
	if ($data_old) {
	    $new = $data['cases']-$data_old['cases'];
            echo " (";
            if ($new > 0) {
	        echo "+";
	    } else if ($new < 0) {
	        echo "-";
            }
            echo $new . ")";
        }
        echo "</td>
	      </tr>";
    }
}

function drawStoplight($data, $threshold_green, $threshold_yellow, $threshold_red)
{
    if ($data['cases7_per_100k'] < $threshold_green) {
        $color = "stoplight_ok";
        $text = "Geöffnet";
    } else if ($data['cases7_per_100k'] < $threshold_yellow) {
        $color = "stoplight_risk";
        $text = "Click&Meet";
    } else if ($data['cases7_per_100k'] < $threshold_red) {
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
        width: 270px;
        border: thin solid #ccc;
        min-height: 300px;
    }

    #tbl_incidence {
        width: 100%;
        text-align: center;
    }

    #tbl_incidence td {
        width: 50%;
        border-bottom: thin solid #ccc;
    }

    #tbl_incidence_left {
        width: 70%;
        text-align: left;
    }

    #tbl_incidence_right {
        width: 30%;
        text-align: right;
    }


    #tbl_incidence_fzt {
        width: 100%;
        font-size: 0.9em;
	text-align: left;
    }

    #tbl_incidence_fzn {
        width: 100%;
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
        background-color: red;
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
        color: red;
    }

    .value_risk {
        color: #CCCC00;
    }

    .value_ok {
        color: green;
    }
</style>
</html>
