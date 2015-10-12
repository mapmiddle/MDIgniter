<?php


class Geocoding {
    const GOOGLE_GEOCODE_URL = 'http://maps.googleapis.com/maps/api/geocode/json';

    function find_latlng_by_address($address) {
        $url = self::GOOGLE_GEOCODE_URL.'?address='.urlencode($address);
        $headers = array(
            'Content-Type: application/json',
            "Content-Language: ko",
            "Accept-Language: ko",
        );

        // Open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Execute post
        $result = curl_exec($ch);
        if ($result === FALSE) {
            //die('Curl failed: ' . curl_error($ch));
            return array(NULL, NULL);
        }

        // Close connection
        curl_close($ch);

        $json_result = json_decode($result);
        if ($json_result && isset($json_result->status) && $json_result->status == 'OK') {
            if (isset($json_result->results) && isset($json_result->results[0]) && isset($json_result->results[0]->geometry)) {
                $geometry =& $json_result->results[0]->geometry;

                if (isset($geometry->location)) {
                    return array($geometry->location->lat, $geometry->location->lng);
                }
            }
        }

        return array(NULL, NULL);
    }
}