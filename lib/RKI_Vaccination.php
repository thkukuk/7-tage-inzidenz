<?php

# https://rki-vaccination-data.vercel.app/api/v2

class RKI_Vaccination
{

    private $cache_file;
    private $updated = 0;

    public function __construct(string $cache_dir)
    {
        $this->cache_file = $cache_dir . '/vaccination-apiv2.json';
    }

    # get the latest data, independent how old they are.
    public function getCurrent()
    {
        # if not updated fetch the current numbers, they are
	# not published daily...
        if (!$this->updated) {
            $data = $this->fetchData();
            if (is_array($data)) {
	        $this->updated = 1;
                return $data;
            }
	}

	for ($offset = 0; $offset < 10; $offset++) {
            $d = new DateTime("today -" . $offset . " day");
            $dt = $d->format('Ymd');

            $data = $this->getCache($dt);
            if ($data) {
                return $data;
            }
	}
    }

    # offset is the days in the past.
    public function getDaily($offset = 0)
    {
        # if not updated fetch the current numbers to fill the
	# cache, they are not published daily...
        if (!$this->updated) {
            $data = $this->fetchData();
            if ($data) {
	        $this->updated = 1;
            }
	}

        $d = new DateTime("today -" . $offset . " day");
        $dt = $d->format('Ymd');

        $data = $this->getCache($dt);
        if ($data) {
            return $data;
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

    private function fetchData()
    {
        $c = curl_init();
        curl_setopt(
            $c,
            CURLOPT_URL,
	    'https://rki-vaccination-data.vercel.app/api/v2'
        );

        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($c);
        if (curl_errno($c)) {
            throw new Exception("could not contact arcgis server");
        }
        curl_close($c);

        $data = json_decode($result, true);

        if (is_array($data)) {
	    # Create some fields to make usage easier
	    $date = DateTime::createFromFormat("Y-m-d H:i:s", str_replace("T", " ", $data['lastUpdate']));
            $data['ts'] = $date->format("U");
	    # RKI does not provides the quote for 2nd vaccination for
	    # whole germany, calculate it our own.
            #$data['2nd_vaccination']['quote'] = round(($data['2nd_vaccination']['vaccinated']/$data['total']*100), 2);
	    # update cache, in worst case we overwrite the same
	    # values...
	    $this->setCache($data);
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
	$date = DateTime::createFromFormat("Y-m-d H:i:s", str_replace("T", " ", $data['lastUpdate']));
        $key = $date->format("Ymd");
        $old[$key] = $data;
        file_put_contents($this->cache_file, json_encode($old));
        return $key;
    }
}
