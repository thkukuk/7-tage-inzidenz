<?php

# https://npgeo-corona-npgeo-de.hub.arcgis.com/datasets/c2f3c3b935a242169c6bec82e1fa573e_0

class RKI_Key_Data
{

    private $cache_file;
    private $admunit_id;

    # Cache current date of data
    private $current_date;

    private $fields = [
        'AdmUnitId',
	'BundeslandId',
	'AnzFall',
	'AnzTodesfall',
	'AnzFallNeu',
	'AnzTodesfallNeu',
	'AnzFall7T',
	'Inz7T',
	'ObjectId'
    ];

    private $status_fields = [
    	'Datum_txt',
	'Status'
    ];

    private $admunit_fields = [
        'AdmUnitId',
	'Name'
    ];

    public function __construct(int $admunitid, string $cache_file)
    {
        $this->cache_file = $cache_file;
        $this->admunit_id = $admunitid;
    }

    public function getDaily($offset = 0)
    {
        $d = new DateTime("today -" . $offset . " day");
        $dt = $d->format('Ymd');

        $c = $this->getCache($dt);
        if (is_array($c)) {
            $c['cached'] = true;
            return $c;
        }
        $c = $this->fetchData($dt);
        if (is_array($c)) {
            $c['cached'] = false;
            return $c;
        }
    }

    private function getCache(string $dt)
    {
        $f = @file_get_contents($this->cache_file);

        if ($f == false) {
            return;
        }

        $data = json_decode($f, true);
        if (isset($data[$dt])) {
            return $data[$dt];
        } else {
            return;
        }
    }

    # Fetch the date and status of the data.
    # If the status is not OK, don't use it.
    private function fetchDateStatus()
    {
        $fieldstr = implode(",", $this->status_fields);

        $c = curl_init();
        curl_setopt(
            $c,
            CURLOPT_URL,
	    'https://services7.arcgis.com/mOBPykOjAyBO2ZKk/arcgis/rest/services/rki_data_status_v/FeatureServer/0/query?where=1%3D1&outFields='
	    . $fieldstr . '&outSR=4326&f=json'
        );

        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($c);
        if (curl_errno($c)) {
            throw new Exception("could not contact arcgis server");
        }
        curl_close($c);

        $json = json_decode($result, true);

        if (!isset($json['features'][0]['attributes'])) {
            return;
        }

        $data = $json['features'][0]['attributes'];
	if (strcmp($data['Status'], "OK") !== 0) {
	    return;
	}
       	return DateTime::createFromFormat("d.m.Y", $data['Datum_txt']);
    }

    private function fetchAdmUnitName(string $admunit_id) {
        $fieldstr = implode(",", $this->admunit_fields);

	$c = curl_init();
        curl_setopt(
            $c,
            CURLOPT_URL,
	    'https://services7.arcgis.com/mOBPykOjAyBO2ZKk/arcgis/rest/services/rki_admunit_v/FeatureServer/0/query?where=AdmUnitId='
	    . $admunit_id . '&outFields=' . $fieldstr . '&outSR=4326&f=json'
        );

        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($c);
        if (curl_errno($c)) {
            throw new Exception("could not contact arcgis server");
        }
        curl_close($c);

        $json = json_decode($result, true);

        if (!isset($json['features'][0]['attributes'])) {
            return;
        }

        $data = $json['features'][0]['attributes'];

	$name = preg_replace('/^Bundesrepublik\b/', '', $data['Name']);
	$name = preg_replace('/^SK\b/', '', $name);
	return $name;
    }

    private function fetchData(string $dt)
    {
	if (!$this->current_date) {
	    $this->current_date = $this->fetchDateStatus();
 	    if (!$this->current_date) {
	        return;
	    }
        }

	if ($this->current_date->format("Ymd") != $dt) {
	    # The data is from a wrong date, so don't even bother about
	    # fetching it.
	    return;
	}

        $fieldstr = implode(",", $this->fields);

        $c = curl_init();
        curl_setopt(
            $c,
            CURLOPT_URL,
	    'https://services7.arcgis.com/mOBPykOjAyBO2ZKk/arcgis/rest/services/rki_key_data_hubv/FeatureServer/0/query?where=AdmUnitId='
                . $this->admunit_id . '&outFields=' . $fieldstr . '&returnGeometry=false&outSR=4326&f=json'
        );

        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($c);
        if (curl_errno($c)) {
            throw new Exception("could not contact arcgis server");
        }
        curl_close($c);

        $json = json_decode($result, true);

        if (!isset($json['features'][0]['attributes'])) {
            return;
        }

        $data = $json['features'][0]['attributes'];
        $data['GEN'] = $this->fetchAdmUnitName($this->admunit_id);
        $data['ts'] = $this->current_date->format("U");
        $set = $this->setCache($data);
        if ($set == $dt) {
            return $data;
        } else {
            return;
        }
    }

    private function setCache($data)
    {
        $f = @file_get_contents($this->cache_file);
        if ($f == false) {
            $old = [];
        } else {
            $old = json_decode($f, true);
        }
        $key = $this->current_date->format("Ymd");
        $old[$key] = $data;
        file_put_contents($this->cache_file, json_encode($old));
        return $key;
    }
}
