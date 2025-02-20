<?php

if (!defined("WHMCS")) {
    //die("This file cannot be accessed directly");
}

require_once '../init.php';

use WHMCS\Database\Capsule;
use WHMCS\Exception\Module\InvalidConfiguration;


$APItoken = Capsule::table('tblregistrars')
        ->where('registrar', 'IpHost')
        ->where('setting', 'APItoken')
        ->value('value'); // Retrieve the value column

$APItoken = decrypt($APItoken);

$domain = trim($_REQUEST["domainName"]);

$postfields = array('domain' => $domain);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.portal.iphost.net/domain/whois',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => json_encode($postfields),
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Authorization: Bearer '.$APItoken,
            'Content-Type: application/json'
        ),
    ));

    $response = curl_exec($curl);
    $jsonData = json_decode($response);

    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($httpcode == 200) {    

        $result = [
            'domain' => $domainName,
            'whoisData' => $jsonData->whois,
            'available' => !$jsonData->success ? 'not registered' : 'registered'
        ];

        echo $result['whoisData'];

    } else {
        'Error';
    }


