<?php

namespace WHMCS\Module\Registrar\IpHost;

/**
 * Sample Registrar Module Simple API Client.
 *
 * A simple API Client for communicating with an external API endpoint.
 */
class ApiClient
{
    const API_URL = 'https://api.portal.iphost.net';
    //const API_URL = 'https://api.devportal.iphost.net';

    protected $results = array();

    /**
     * Make external API call to registrar API.
     *
     * @param string $action
     * @param array $postfields
     * @param string $token
     *
     * @throws \Exception Connection error
     * @throws \Exception Bad API response
     *
     * @return array
     */
    public function call($action, $postfields, $token, $method)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::API_URL . $action,
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
                'Authorization: Bearer '.$token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        //echo $response;

        if (curl_errno($curl)) {
            throw new \Exception('Connection Error: ' . curl_errno($curl) . ' - ' . curl_error($curl));
        }
        curl_close($curl);

        $this->results = $this->processResponse($response);

        logModuleCall(
            'IpHost',
            $action,
            $postfields,
            $response,
            $this->results,
            $token,
        );

        if ($this->results === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Bad response received from API');
        }

        return $this->results;
    }

    /**
     * Process API response.
     *
     * @param string $response
     *
     * @return array
     */
    public function processResponse($response)
    {
        return json_decode($response, true);
    }

    /**
     * Get from response results.
     *
     * @param string $key
     *
     * @return string
     */
    public function getFromResponse($key)
    {
        return isset($this->results[$key]) ? $this->results[$key] : '';
    }
}
