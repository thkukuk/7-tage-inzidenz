#!/usr/bin/php
<?php
include('lib/RKI_Corona_Data.php');

# Find your AdmUnitID for the comma separated REGIONS list here:
# https://www.arcgis.com/apps/mapviewer/index.html?layers=c093fe0ef8fd4707beeb3dc0c02c3381

### Global configuration ###
$cache_dir = '/data';

### Main ###

$options = getopt("f");

if (array_key_exists('f', $options)) {
    $forced = !$options['f'];
} else {
    $forced = false;
}

$regions=getenv("REGIONS");
if (!$regions) {
  # if no region is specified, use Germany
  $regions="0";
}
$reg_arr = explode(',', $regions);
$d = new DateTime("today");
$dt = $d->format('d.m.Y');

foreach($reg_arr as $reg) {
    $incidence = new RKI_Corona_Data($reg, $cache_dir);
    if ($forced) {
        $data = $incidence->getDailyNoCache(0);
    } else {
        $data = $incidence->getDaily(0);
    }
    if (!$data) {
        echo "Keine neuen Daten für Region " . $reg . " vom "
	     . $dt . " gefunden\n";
    } else {
        if  ($data['BundeslandId'] != '0') {
            $incidence_bl = new RKI_Corona_Data($data['BundeslandId'],
	                                        $cache_dir);
	    if ($forced) {
                $data_bl = $incidence_bl->getDailyNoCache(0);
	    } else {
                $data_bl = $incidence_bl->getDaily(0);
	    }
            if (!$data_bl) {
                echo "Keine neuen Daten für Region " . $reg . " vom "
	             . $dt . "gefunden\n";
            }
        }
    }
}
unset ($reg);

echo "RKI Corona Daten sind vom " . $dt . "\n";

?>
