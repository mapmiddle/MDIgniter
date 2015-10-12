<?php


class PushMessage {
    function send_to_android($google_api_key, $registatoin_ids, $data) {
        $gcm_send_url = 'https://android.googleapis.com/gcm/send';

        if (!is_array($registatoin_ids)) {
            $registatoin_ids = array($registatoin_ids);
        }

        if (!is_array($data)) {
            $data = array('message' => $data);
        }

        // Set POST variables
        $fields = array(
            'registration_ids' => $registatoin_ids,
            'data' => $data,
        );

        $headers = array(
            'Authorization: key=' . $google_api_key,
            'Content-Type: application/json'
        );

        // Open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $gcm_send_url);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        // Execute post
        $result = curl_exec($ch);
        if ($result === FALSE) {
            //die('Curl failed: ' . curl_error($ch));
            return $result;
        }

        // Close connection
        curl_close($ch);
        return $result;
    }

    function send_to_ios($apnsHost, $apnsCertPath, $device_token, $data) {
        header('Content-Type: text/html; charset=UTF-8');
        $deviceToken = $device_token;

        /*
         * $apnsHost
         * development : gateway.sandbox.push.apple.com
         * deployment : gateway.push.apple.com
         */

        $apnsPort = 2195;

        $alert = '';
        if (array_key_exists('ios_alert', $data)) {
            $alert = $data['ios_alert'];
        }

        $payload = array(
            'aps' => array('alert' => $alert, 'badge' => 0, 'sound' => 'default'),
        );

        if (array_key_exists('ios_custom', $data)) {
            $payload['ios_custom'] = $data['ios_custom'];
        }

        $payload = json_encode($payload);

        $streamContext = stream_context_create();
        stream_context_set_option($streamContext, 'ssl', 'local_cert', $apnsCertPath);

        $apns = stream_socket_client('ssl://'.$apnsHost.':'.$apnsPort, $error, $errorString, 2, STREAM_CLIENT_CONNECT, $streamContext);

        if($apns) {
            $apnsMessage = chr(0).chr(0).chr(32).pack('H*', str_replace(' ', '', $deviceToken)).chr(0).chr(strlen($payload)).$payload;
            fwrite($apns,  $apnsMessage);
            fclose($apns);
            return TRUE;
        }

        //middle.error.log
        MDI_Log::write('IOS_PUSH_ERROR-'.$this->input->ip_address().'-'.$this->input->user_agent().'-'.current_url());
        return FALSE;
    }
}