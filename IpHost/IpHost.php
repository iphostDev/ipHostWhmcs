<?php
/**
 * WHMCS SDK Sample Registrar Module
 *
 * Registrar Modules allow you to create modules that allow for domain
 * registration, management, transfers, and other functionality within
 * WHMCS.
 *
 * This sample file demonstrates how a registrar module for WHMCS should
 * be structured and exercises supported functionality.
 *
 * Registrar Modules are stored in a unique directory within the
 * modules/registrars/ directory that matches the module's unique name.
 * This name should be all lowercase, containing only letters and numbers,
 * and always start with a letter.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For
 * example this file, the filename is "registrarmodule.php" and therefore all
 * function begin "IpHost_".
 *
 * If your module or third party API does not support a given function, you
 * should not define the function within your module. WHMCS recommends that
 * all registrar modules implement Register, Transfer, Renew, GetNameservers,
 * SaveNameservers, GetContactDetails & SaveContactDetails.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/domain-registrars/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;
use WHMCS\Exception\Module\InvalidConfiguration;
use WHMCS\Module\Registrar\IpHost\ApiClient;
use WHMCS\Module\Registrar\IpHost\Greeklish;


use WHMCS\Domain\Registrar\Domain;
use WHMCS\Carbon;
use WHMCS\Database\Capsule;

// Require any libraries needed for the module to function.
// require_once __DIR__ . '/path/to/library/loader.php';
//
// Also, perform any initialization required by the service's library.

/**
 * Define module related metadata
 *
 * Provide some module information including the display name and API Version to
 * determine the method of decoding the input values.
 *
 * @return array
 */
function IpHost_MetaData()
{
    return array(
        'DisplayName' => 'IpHost.net',
        'APIVersion' => '1.1',
    );
}

/**
 * Define registrar configuration options.
 *
 * The values you return here define what configuration options
 * we store for the module. These values are made available to
 * each module function.
 *
 * You can store an unlimited number of configuration settings.
 * The following field types are supported:
 *  * Text
 *  * Password
 *  * Yes/No Checkboxes
 *  * Dropdown Menus
 *  * Radio Buttons
 *  * Text Areas
 *
 * @return array
 */
function IpHost_getConfigArray()
{
    return [
        // Friendly display name for the module
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'IpHost Registrar Module for WHMCS',
        ],
        // a text field type allows for single line text input
        'APItoken' => [
            'FriendlyName' => 'Token',
            'Type' => 'text',
            'Size' => '50', 
            'Description' => 'Token For Api',
            'Default' => '',
        ],
        'contact_id' => [
            'FriendlyName' => 'Contact ID',
            'Type' => 'text',
            'Size' => '50', 
            'Description' => 'Contact ID number'
        ],
        'ShowLog' => [
            'FriendlyName' => 'Show Log Widget',
            'Type' => 'yesno',
            'Description' => 'Enables Log Widget on Dashboard',
        ]
    ];
}

function IpHost_config_validate($params) {
    
    $APItoken = $params['APItoken'];
  
    if ($APItoken == null) {
        throw new InvalidConfiguration('API Key is invalid.');
    }
    
    //if ($contact_id == null) {
    //    throw new InvalidConfiguration('You have to provide a valid contact id. Please contact with IpHost Support ');
    //}
}


/**
 * Check Domain Availability.
 *
 * Determine if a domain or group of domains are available for
 * registration or transfer.
 *
 * @param array $params common module parameters
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @see \WHMCS\Domains\DomainLookup\SearchResult
 * @see \WHMCS\Domains\DomainLookup\ResultsList
 *
 * @throws Exception Upon domain availability check failure.
 *
 * @return \WHMCS\Domains\DomainLookup\ResultsList An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
 */

function IpHost_CheckAvailability($params)
{

    $APItoken = $params['APItoken'];

    $domains = array();

    foreach($params['tlds'] as $ext) {
        array_push($domains, $params['sld'].''.$ext);
    }

    $postfields = array('domains' => $domains);

    try {
        $api = new ApiClient();
        $api->call('/domain/check', $postfields, $APItoken, 'POST');

        $results = new ResultsList();

        foreach ($api->getFromResponse('domains') as $domain) {

            //create domain with extension
            $split = explode('.', $domain['name']);

            // Instantiate a new domain search result object
            $searchResult = new SearchResult($split[0],$split[1]);

            // Determine the appropriate status to return
            if ($domain['avail'] === 1 ) {
                $status = SearchResult::STATUS_NOT_REGISTERED;
            } elseif ($domain['avail'] === 0) {
                $status = SearchResult::STATUS_REGISTERED;
            } else {
                $status = SearchResult::STATUS_TLD_NOT_SUPPORTED;
            }
            $searchResult->setStatus($status);

            // Append to the search results list
            $results->append($searchResult);
        }


         logModuleCall(
            'ipHost',          // The name of your module
            'checkfromERP',              // The action being performed (e.g., 'GetDomainSuggestions')
            $domains,             // Data sent to the remote API or module
            '',            // Data received from the remote API or module
            $results,    // (Optional) Additional array data to log
            $replaceVars = null   // (Optional) Sensitive data to mask in the log
        );

        return $results;

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}


// function IpHost__DomainSuggestionOptions() {
//     return array(
//         'includeCCTlds' => array(
//             'FriendlyName' => 'Include Country Level TLDs',
//             'Type' => 'yesno',
//             'Description' => 'Tick to enable',
//         ),
//     );
// }




function IpHost_Sync($params) {

    $APItoken = $params['APItoken'];
    $domain = $params['domain'];
    $call = '/domain/'.$domain;

    sleep(2);

    try {
        
        $api = new ApiClient();
        $api->call($call, [], $APItoken, 'GET');

            if ( $api->getFromResponse('success')) {

                $response = $api->getFromResponse('domain');

                $registration = Carbon::parse($response['started_at']);
                $expiration = Carbon::parse($response['expires_at']);

                $mydata = array(
                    'regdate' => $registration->format('Y-m-d'),
                    'expirydate' => $expiration->format('Y-m-d'),
                    'active' => ($response['status']['id'] === 4) ? true : false , // Return true if the domain is active
                    'transferredAway' => ($response['status']['id'] === 14) ? true : false, // Return true if the domain is 
                    'error' => ""
                );

                logModuleCall(
                        'IpHost',
                        'sync',
                        $params,
                        $mydata,
                        $mydata,
                        ''
                );

                return $mydata;
            }

    } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
    }
}




function IpHost_GetDomainSuggestions($params){

    logActivity('GetDomainSuggestions function called with params: ' . print_r($params, true));


    $APItoken = $params['APItoken'];
    
    $domains = array();

    foreach($params['tldsToInclude'] as $ext) {
        array_push($domains, $params['searchTerm'].'.'.$ext); 
    }

    logModuleCall(
        'ipHost',          // The name of your module
        'GetDomainSuggestions',              // The action being performed (e.g., 'GetDomainSuggestions')
        $domains,             // Data sent to the remote API or module
        '',            // Data received from the remote API or module
        $params,    // (Optional) Additional array data to log
        $replaceVars = null   // (Optional) Sensitive data to mask in the log
    );

    $postfields = array('domains' => $domains);

        $api = new ApiClient();
        $api->call('/domain/checkprices', $postfields, $APItoken, 'POST');

        $results = new ResultsList();

        $suggestions = $api->getFromResponse('domains');


    foreach ($suggestions as $suggestion) {
        $domainParts = explode('.', $suggestion['name']);
        $domain = $domainParts[0];
        $tld = $domainParts[1];

        $x = $domainParts[0];

        $mydomain = "my-".$domain;
        $searchResult = new SearchResult($mydomain, $tld);
        $results->append($searchResult);
    }

    return $results;
}


function GetWhoisInformation($params) {
    // $sld = $params['sld'];
    // $tld = $params['tld'];
    // $domain = $sld . '.' . $tld;

    // // Make API call to your registrar's WHOIS service
    // //$whoisData = yourRegistrarApiCall($domain);
    // //var_dump($domain);die;

    // if ($whoisData) {
    //     return array(
    //         'result' => 'success',
    //         'whois' => $whoisData
    //     );
    // } else {
    //     return array(
    //         'result' => 'error',
    //         'whois' => 'Unable to retrieve WHOIS information for ' . $domain
    //     );
    // }
}


/**
 * Register a domain.
 *
 * Attempt to register a domain with the domain registrar.
 *
 * This is triggered when the following events occur:
 * * Payment received for a domain registration order
 * * When a pending domain registration order is accepted
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function IpHost_RegisterDomain($params) {

    $APItoken = $params['APItoken'];
    $contact_id = $params['contact_id'];

    // registration parameters
    $sld = $params['sld'];
    $tld = $params['tld'];
    $registrationPeriod = $params['regperiod'];

    //logActivity('RegisterDomain function called with params: ' . print_r($params, true));

    $nameserver1 = $params['ns1'];
    $nameserver2 = $params['ns2'];
    $nameserver3 = $params['ns3'];
    $nameserver4 = $params['ns4'];
    $nameserver5 = $params['ns5'];

    $nameservers = array($params['ns1'], $params['ns2'], $params['ns3'], $params['ns4'], $params['ns5']);
    $nameservers = array_filter($nameservers);


    $greeklish = new Greeklish();

    $contactData = array(
        "type" => 1,
        "name" => $params["firstname"],
        "int_name" => $greeklish->convertText($params["firstname"]),
        "surname" => $params["lastname"],
        "int_surname" => $greeklish->convertText($params["lastname"]),
        "organization" => $params["companyname"],
        "int_organization" => $greeklish->convertText($params["companyname"]),
        "email" => $params["email"],
        "address" => $params["address1"],
        "int_address" => $greeklish->convertText($params["address1"]),
        "city" => $params["city"],
        "int_city" => $greeklish->convertText($params["city"]),
        "province" => $params["state"],
        "int_province" => $greeklish->convertText($params["state"]),
        "country_id" => 86,
        "citizenship" => 56,
        "postcode" => $params["postcode"],
        "telephones" => ["+30.".$params["phonenumber"]],
        "mobiles" => [],
        "faxes" => [],
        "tax_id" => "",
        "tax_office" => "",
        "occupation" => ""   
    );


    $getRecord = select_query("tblcontacts", "erp_contact_id", array("id" => $params['contact_id']));
    $data = mysql_fetch_array($getRecord);
    $erp_contact_id = $data['erp_contact_id'];


    try {


        if ( !$erp_contact_id ) {

            $api = new ApiClient();
            $api->call('/contact', $contactData, $APItoken, 'POST');

            if ( $api->getFromResponse('contact_id') ) {
                $contact_id_to_send = $api->getFromResponse('contact_id');

                //field erp_contact_id is custom for erp and is set on hooks.php
                update_query('tblcontacts', array(
                    'erp_contact_id' => $contact_id_to_send), 
                    array('id' => $params['contact_id'])
                );

            } else {
                $contact_id_to_send = $params['contact_id'];
            }

        } else {
            $contact_id_to_send = $erp_contact_id;
        }



        if ($contact_id_to_send) {
            
            $data = array(
                "name" => $params['sld'].'.'.$params['tld'],
                "period" => $params['regperiod'],
                "owner" => $contact_id_to_send,
                //"dns" => array($params['ns1'],$params['ns2'],$params['ns3'],$params['ns4'],$params['ns5']),
                "dns" => $nameservers,
                "administrator" => array($contact_id_to_send),
                "technical" => array($contact_id_to_send),
                "billing" => array($contact_id_to_send),
                "autoRenew" => 0
            );

            $postfields = array(
                'domains' => [$data]
            );
            
            $api->call('/domain', $postfields, $APItoken, 'POST');
            
            if ( $api->getFromResponse('order_id') ) {
                $result = array('success' => $api->getFromResponse('order_id') );
            } else {
                $result = array('error' => $api->getFromResponse('error') );
            }

            return $result;

        }

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}


/**
 * Renew a domain.
 *
 * Attempt to renew/extend a domain for a given number of years.
 *
 * This is triggered when the following events occur:
 * * Payment received for a domain renewal order
 * * When a pending domain renewal order is accepted
 * * Upon manual request by an admin user
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */

function IpHost_RenewDomain($params){
    
    $APItoken = $params['APItoken'];

    $sld = $params['sld'];
    $tld = $params['tld'];

    $call = '/domain/'.$params['sld'].'.'.$params['tld'].'/renew';

    $postfields = array(
        'period' => $params['regperiod']
    );

    try {
        
        $api = new ApiClient();
        $api->call($call, $postfields, $APItoken, 'PUT');

        if ( $api->getFromResponse('order_id') ) {
            $result = array('success' => $api->getFromResponse('order_id') );
        } else {
            $result = array('error' => 'Προσοχή!! Δεν υπάρχει επαρκές πιστωτικό υπόλοιπο για την συνέχιση της παραγγελίας');
        }

        return $result;

    } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
    }

}


/**
 * Save nameserver changes.
 *
 * This function should submit a change of nameservers request to the
 * domain registrar.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */

function IpHost_SaveNameservers($params){

    $APItoken = $params['APItoken'];

    $nameservers = array($params['ns1'], $params['ns2'], $params['ns3'], $params['ns4'], $params['ns5']);
    $nameservers = array_filter($nameservers);

    $postfields = array(
        'dns' => $nameservers
    );

    $call = '/domain/'.$params['sld'].'.'.$params['tld'].'/dns';

    try {
        
        $api = new ApiClient();
        $api->call($call, $postfields, $APItoken, 'PUT');

        if ( $api->getFromResponse('success') == 1 ) {
            $result = array('success' => $api->getFromResponse('msg') );
        } else {
            $result = array('error' => $api->getFromResponse('error') );
        }

        return $result;

    } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
    }
}


/**
 * Register a Nameserver.
 *
 * Adds a child nameserver for the given domain name.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */

function IpHost_RegisterNameserver($params){

    $APItoken = $params['APItoken'];
    
    $postfields = array(
        'domain_id'  => $params['sld'].'.'.$params['tld'],
        //κανω replace για να στείλω μόνο το όνομα του subdomain και κόβω και την τελεια στο τελος
        'subdomain' =>  rtrim(str_replace($params['sld'].'.'.$params['tld'],"",$params['nameserver']),"."),
        'ip' => $params['ipaddress']
    );

    try {
        
        $api = new ApiClient();
        $api->call('/host', $postfields, $APItoken, 'POST');

        if ( $api->getFromResponse('success') == 1 ) {
            $result = array('success' => $api->getFromResponse('msg') );
        } else {
            $result = array('error' => $api->getFromResponse('error') );
        }

        return $result;

    } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
    }
}


/**
 * Modify a Nameserver.
 *
 * Modifies the IP of a child nameserver.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function IpHost_ModifyNameserver($params){

}


/**
 * Delete a Nameserver.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return array
 */
function IpHost_DeleteNameserver($params){

    $APItoken = $params['APItoken'];

    $call = '/host/'.$params['nameserver'];

    try {
        
        $api = new ApiClient();
        $api->call($call, $params['nameserver'], $APItoken, 'DELETE');

        if ( $api->getFromResponse('success') == 1 ) {
            $result = array('success' => $api->getFromResponse('msg') );
        } else {
            $result = array('error' => $api->getFromResponse('error') );
        }

        return $result;

    } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
    }
}


/**
 * Client Area Output.
 *
 * This function renders output to the domain details interface within
 * the client area. The return should be the HTML to be output.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @return string HTML Output
 */
function IpHost_ClientArea($params)
{
    $output = '
        <div class="row" style="margin-top:40px;background:#eee;">
            <div class="col-md-8">.Your custom HTML output goes here...</div>
            <div class="col-md-4">.col-md-4</div>
        </div>
    ';

    //return $output;
}


function IpHost_GetEPPCode($params){

    $APItoken = $params['APItoken'];

    $domain = $params['sld'].'.'.$params['tld'];
    $call = '/domain/'.$domain.'/reset-password';

    try {
        
        $api = new ApiClient();
        $api->call($call, [$domain], $APItoken, 'PUT');

        if ( $api->getFromResponse('success') ) {
            $result = array('eppcode' => $api->getFromResponse('msg') );
        } else {
            $result = array('error' => 'Unknown Domain' );
        }

        return $result;

    } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
    }

}

function IpHost_IDProtectToggle($params){

    $APItoken = $params['APItoken'];

    $domain = $params['sld'].'.'.$params['tld'];

    $call = '/domain/'.$domain.'/toggle-idshield';

    $protectEnable = (bool) $params['protectenable'];

    try {
        
        $api = new ApiClient();

        if ($protectEnable) {
            $api->call($call, [$domain], $APItoken, 'PUT');
        } 

        return array(
            'success' => 'success',
        );
        

    } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
    }
}

function IpHost_GetRegistrarLock($params){


    $APItoken = $params['APItoken'];

    $domain = $params['sld'].'.'.$params['tld'];

    $call = '/domain/'.$domain.'/lock';

    try {
        
        $api = new ApiClient();
        $api->call($call, [$domain], $APItoken, 'GET');

        if ( $api->getFromResponse('success') == 1 ) {
            return ($api->getFromResponse('locked') == true) ? 'locked' : 'unlocked';
        }

    } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
    }

}

function IpHost_SaveRegistrarLock($params){

    $APItoken = $params['APItoken'];
    $lockStatus = $params['lockenabled'];

    //($lockStatus == 'locked') ? 1 : 0;

    $domain = $params['sld'].'.'.$params['tld'];

    $call = '/domain/'.$domain.'/lock';

    try {
        
        $api = new ApiClient();
        $api->call($call, [$domain], $APItoken, 'PUT');

        if ( $api->getFromResponse('success') == 1 ) {
            $result = array('success' => 'success');
        }

        // if ( $api->getFromResponse('success') == 1 ) {
        //     $result = array('eppcode' => $api->getFromResponse('password') );
        // } else {
        //     $result = array('error' => 'Unknown Domain' );
        // }

        return $result;

    } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
    }

}



function IpHost_SaveDNS($params){


}

function IpHost_TransferDomain($params){

   try {

        $sld = $params['sld'];
        $tld = $params['tld'];
        $eppCode = $params['eppcode'];
        $APItoken = $params['APItoken'];

        $api = new ApiClient();
        
        $api->call('/account', '', $APItoken, 'GET');
        if ( $api->getFromResponse('success')) {
            $data = $api->getFromResponse('accounts');
            $account_id = $data['data'][0]['id'];
        }

        if ($account_id) {
            $postfields = [
                "account_id" => $account_id,
                "domains" => [
                    [
                        "name" => $params['sld'].'.'.$params['tld'],
                        "password" => $eppCode,
                        "owner_id" => $account_id
                    ]
                ]
            ];

            $api->call('/domain/transfer', $postfields, $APItoken, 'POST');
            if ( $api->getFromResponse('success')) {
                return array(
                    'success' => true,
                );
            } else {
                return array(
                    'error' => $api->getFromResponse('message'),
                );
            }

        } else {
            return array(
                'error' => $e->getMessage(),
            );
        }

    } catch (\Exception $e) {
        return array(
            'error' => "3".$e->getMessage(),
        );
    }    
}

function IpHost_GetNameservers($params){

}

function IpHost_GetContactDetails($params){
    //var_dump($params);
    //die;
    //return;

    $domain = $params['sld'].'.'.$params['tld'];
    $APItoken = $params['APItoken'];
    $call = '/domain/'.$domain;


    try {
        $api = new ApiClient();
        $api->call($call, [], $APItoken, 'GET');

        if ( $api->getFromResponse('success') ) {
            $data =  $api->getFromResponse('domain');


            //var_dump($data);
            //die;

            
            if (sizeof($data['technical']) == 1) {
                $techData = $data['technical'][0];
            } else {
                $techData = $data['technical'];
            }

            if (sizeof($data['billing']) == 1) {
                $billingData = $data['billing'][0];
            } else {
                $billingData = $data['billing'];
            }

            if (sizeof($data['administrators']) == 1) {
                $adminData = $data['administrators'][0];
            } else {
                $adminData = $data['administrators'];
            }


            //var_dump($techData);
            //die;


            $registrar_name = explode(" ",$data['owner']['details']['name']);
            $technical_name = explode(" ",$techData['details']['name']);
            $billing_name = explode(" ",$billingData['details']['name']);
            $admin_name = explode(" ",$adminData['details']['name']);


            $result =  array(
                // "Registrant" => array(
                //     'First Name' => $registrar_name[0],
                //     'Last Name' => $registrar_name[1],
                //     'Company Name' => $data['owner']['details']['organization'],
                //     'Email Address' => $data['owner']['details']['email'],
                //     'Address 1' => $data['owner']['details']['address'],
                //     //'Address 2' => $api->getFromResponse('registrant.address2'),
                //     //'City' => $data['owner']['details']['city'],
                //     'City' => $data['owner']['details']['city'],
                //     'State' => $data['owner']['details']['state'],
                //     'Postcode' => $data['owner']['details']['postcode'],
                //     //'Country' => $data['owner']['details']['organization'],
                //     'Phone Number' => $data['owner']['details']['phone'][0],
                //     'Fax Number' => $data['owner']['details']['fax'],
                // ),
                "Technical" => array(
                    'First Name' => $technical_name[0],
                    'Last Name' => $technical_name[1],
                    'Company Name' => $techData['details']['organization'],
                    'Email Address' => $techData['details']['email'],
                    'Address 1' => $techData['details']['address'],
                    //'Address 2' => $api->getFromResponse('registrant.address2'),
                    'City' => $techData['details']['city'],
                    'State' => $techData['details']['state'],
                    'Postcode' => $techData['details']['postcode'],
                    //'Country' => $data['owner']['details']['organization'],
                    'Phone Number' => is_array($techData['details']['phone']) ? $techData['details']['phone'][0] : $techData['details']['phone'],
                    'Fax Number' => $techData['details']['fax'][0],
                    'IPHOST_contact' => $techData['id'],
                ),
                "Billing" => array(
                    'First Name' => $billing_name[0],
                    'Last Name' => $billing_name[1],
                    'Company Name' => $billingData['details']['organization'],
                    'Email Address' => $billingData['details']['email'],
                    'Address 1' => $billingData['details']['address'],
                    //'Address 2' => $api->getFromResponse('registrant.address2'),
                    'City' => $billingData['details']['city'],
                    'State' => $billingData['details']['state'],
                    'Postcode' => $billingData['details']['postcode'],
                    //'Country' => $data['owner']['details']['organization'],
                    'Phone Number' => is_array($billingData['details']['phone']) ? $billingData['details']['phone'][0] : $billingData['details']['phone'],
                    'Fax Number' => $billingData['details']['fax'][0],
                    'IPHOST_contact' => $billingData['id'],
                ),
                'Admin' => array(
                    'First Name' => $admin_name[0],
                    'Last Name' => $admin_name[1],
                    'Company Name' => $adminData['details']['organization'],
                    'Email Address' => $adminData['details']['email'],
                    'Address 1' => $adminData['details']['address'],
                    //'Address 2' => $api->getFromResponse('registrant.address2'),
                    'City' => $adminData['details']['city'],
                    'State' => $adminData['details']['state'],
                    'Postcode' => $adminData['details']['postcode'],
                    //'Country' => $data['owner']['details']['organization'],
                    'Phone Number' => is_array($adminData['details']['phone']) ? $adminData['details']['phone'][0] : $adminData['details']['phone'],
                    'Fax Number' => $adminData['details']['fax'][0],
                    'IPHOST_contact' => $adminData['id'],
                )
            );


                //var_dump($result);
                //die;

                logModuleCall(
                    'IpHost',
                    'GetContactDetails',
                    $call,
                    '',
                    $data,
                    '',
                );

            return $result;
        }

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }    

}


function IpHost_SaveContactDetails($params){

     $APItoken = $params['APItoken'];
     $contactDetails = $params['contactdetails'];
     $greeklish = new Greeklish();
     $domain = $params['sld'].'.'.$params['tld'];

     $api = new ApiClient();

     //start tech
     $techData = array(
            "type" => strlen($contactDetails['Technical']['Company Name'] > 0) ? 2 : 1,
            "name" => $contactDetails['Technical']['First Name'],
            "int_name" => $greeklish->convertText($contactDetails['Technical']['First Name']),
            "surname" => $contactDetails['Technical']['Last Name'],
            "int_surname" => $greeklish->convertText($contactDetails['Technical']['Last Name']),
            "organization" => $contactDetails['Technical']['Company Name'],
            "int_organization" => $greeklish->convertText($contactDetails['Technical']['Company Name']),
            "email" => $contactDetails['Technical']['Email Address'],
            "address" => $contactDetails['Technical']['Address 1'],
            "int_address" => $greeklish->convertText($contactDetails['Technical']['Address 1']),
            "city" => $contactDetails['Technical']['City'],
            "int_city" => $greeklish->convertText($contactDetails['Technical']['City']),
            "province" => $contactDetails['Technical']['State'],
            "int_province" => $greeklish->convertText($contactDetails['Technical']['State']),
            "country_id" => 86,
            "citizenship" => 56,
            "postcode" => $contactDetails['Technical']['Postcode'],
            "telephones" => [$contactDetails['Technical']['Phone Number']],
            "mobiles" => [],
            "faxes" => [],
            "tax_id" => "",
            "tax_office" => "",
            "occupation" => ""   
        );

     $erp_contact_id = $contactDetails['Technical']['IPHOST_contact'];

     if ($erp_contact_id) {
        $api->call('/contact/'.$erp_contact_id, $techData, $APItoken, 'PUT');
        $tech_erp_id = $erp_contact_id;
     } else {
        $api->call('/contact', $techData, $APItoken, 'POST');
        if ( $api->getFromResponse('contact_id') ) {
            $contact_id = $api->getFromResponse('contact_id');
        }
        $tech_erp_id = $contact_id;
     }
     //end tech



     //start billing
     $billingData = array(
            "type" => strlen($contactDetails['Technical']['Company Name'] > 0) ? 2 : 1,
            "name" => $contactDetails['Billing']['First Name'],
            "int_name" => $greeklish->convertText($contactDetails['Billing']['First Name']),
            "surname" => $contactDetails['Billing']['Last Name'],
            "int_surname" => $greeklish->convertText($contactDetails['Billing']['Last Name']),
            "organization" => $contactDetails['Billing']['Company Name'],
            "int_organization" => $greeklish->convertText($contactDetails['Billing']['Company Name']),
            "email" => $contactDetails['Billing']['Email Address'],
            "address" => $contactDetails['Billing']['Address 1'],
            "int_address" => $greeklish->convertText($contactDetails['Billing']['Address 1']),
            "city" => $contactDetails['Billing']['City'],
            "int_city" => $greeklish->convertText($contactDetails['Billing']['City']),
            "province" => $contactDetails['Billing']['State'],
            "int_province" => $greeklish->convertText($contactDetails['Billing']['State']),
            "country_id" => 86,
            "citizenship" => 56,
            "postcode" => $contactDetails['Billing']['Postcode'],
            "telephones" => [$contactDetails['Billing']['Phone Number']],
            "mobiles" => [],
            "faxes" => [],
            "tax_id" => "",
            "tax_office" => "",
            "occupation" => ""   
        );

     $erp_contact_id_for_billing = $contactDetails['Billing']['IPHOST_contact'];

     if ($erp_contact_id_for_billing) {
        $api->call('/contact/'.$erp_contact_id_for_billing, $billingData, $APItoken, 'PUT');
        $billing_erp_id = $erp_contact_id_for_billing;
     } else {
        $api->call('/contact', $billingData, $APItoken, 'POST');
        if ( $api->getFromResponse('contact_id') ) {
            $contact_id = $api->getFromResponse('contact_id');
        }
        $billing_erp_id = $contact_id;
     }
     //end billing



     //start admin
     $adminData = array(
            "type" => strlen($contactDetails['Technical']['Company Name'] > 0) ? 2 : 1,
            "name" => $contactDetails['Admin']['First Name'],
            "int_name" => $greeklish->convertText($contactDetails['Admin']['First Name']),
            "surname" => $contactDetails['Admin']['Last Name'],
            "int_surname" => $greeklish->convertText($contactDetails['Admin']['Last Name']),
            "organization" => $contactDetails['Admin']['Company Name'],
            "int_organization" => $greeklish->convertText($contactDetails['Admin']['Company Name']),
            "email" => $contactDetails['Admin']['Email Address'],
            "address" => $contactDetails['Admin']['Address 1'],
            "int_address" => $greeklish->convertText($contactDetails['Admin']['Address 1']),
            "city" => $contactDetails['Admin']['City'],
            "int_city" => $greeklish->convertText($contactDetails['Admin']['City']),
            "province" => $contactDetails['Admin']['State'],
            "int_province" => $greeklish->convertText($contactDetails['Admin']['State']),
            "country_id" => 86,
            "citizenship" => 56,
            "postcode" => $contactDetails['Admin']['Postcode'],
            "telephones" => [$contactDetails['Admin']['Phone Number']],
            "mobiles" => [],
            "faxes" => [],
            "tax_id" => "",
            "tax_office" => "",
            "occupation" => ""   
        );

     $erp_contact_id_for_admin = $contactDetails['Admin']['IPHOST_contact'];

     if ($erp_contact_id_for_admin) {
        $api->call('/contact/'.$erp_contact_id_for_admin, $adminData, $APItoken, 'PUT');
        $admin_erp_id = $erp_contact_id_for_admin;
     } else {
        $api->call('/contact', $adminData, $APItoken, 'POST');
        if ( $api->getFromResponse('contact_id') ) {
            $contact_id = $api->getFromResponse('contact_id');
        }
        $admin_erp_id = $contact_id;
     }
     //end admin

     sleep(3);

     $erpContacts = [];
     if ($tech_erp_id) {
        $erpContacts['technical'] = [$tech_erp_id];
     }
     if ($billing_erp_id) {
        $erpContacts['billing'] = [$billing_erp_id];
     }
     if ($admin_erp_id) {
        $erpContacts['administrator'] = [$admin_erp_id];
     }
     $api->call('/domain/'.$domain.'/contacts', $erpContacts, $APItoken, 'PUT');

     sleep(3);

}





function IpHost_DomainSuggestionOptions() {
}




function IpHost_GetDNS($params){
}


function IpHost_ReleaseDomain($params){
}

function IpHost_RequestDelete($params){
}


function IpHost_TransferSync($params){

        $APItoken = $params['APItoken'];
        $domain = $params['domain'];
        $call = '/domain/'.$domain;

        sleep(2);

        try {
            
            $api = new ApiClient();
            $api->call($call, [], $APItoken, 'GET');

                if ( $api->getFromResponse('success')) {

                    $response = $api->getFromResponse('domain');

                    if (!isset($response['status']['id'], $response['expires_at'])) {
                        return ['error' => 'Invalid API response structure'];
                    }

                    $expiration = Carbon::parse($response['expires_at'])->format('Y-m-d');

                    $completedStatuses = [4, 11, 19, 20]; // Transfer complete
                    $failedStatuses = [3, 5]; // Transfer failed

                    $transferStatus = $response['status']['id'];

                    $mydata = [
                        'completed' => in_array($transferStatus, $completedStatuses, true),
                        'expirydate' => $expiration,
                        'failed' => in_array($transferStatus, $failedStatuses, true),
                        'reason' => in_array($transferStatus, $failedStatuses, true) ? 'Transfer failed or still in progress' : '',
                        'error' => ''
                    ];


                    logModuleCall(
                        'IpHost',
                        'TransferSync',
                        $params,
                        $response,
                        $mydata,
                        []
                    );

                    return $mydata;
                } else {
                    return ['error' => 'API call failed'];
                }

        } catch (\Exception $e) {
                return array(
                    'error' => $e->getMessage(),
                );
        }
        
}

function IpHost_ClientAreaCustomButtonArray(){
}

function IpHost_ClientAreaAllowedFunctions(){
}

function IpHost_push($params){
}



function IpHost_GetDomainInformation($params) {
 

    $APItoken = $params['APItoken'];
    $lockStatus = $params['lockenabled'];
    $domain = $params['domain'];
    $call = '/domain/'.$domain;

    try {
        
        $api = new ApiClient();
        $api->call($call, [], $APItoken, 'GET');

        if ( $api->getFromResponse('success')) {

            $response = $api->getFromResponse('domain');

            $domain = new Domain;
            $domain->setDomain($domain);
            
            $nameservers = array();
            for ($i=0;$i<5;$i++) {
                $nameservers['ns'.($i+1)] = strtolower($response['dns'][$i]['name']);
            }

            $domain->setNameservers($nameservers);

            // if ($response['status']['id'] === 4 ) {
            //     $status = 'active';
            // } elseif ($response['status']['id'] === 8) {
            //     $status = 'expired';
            // } elseif ($response['status']['id'] === 9) {
            //     $status = 'suspended';    
            // }

            // $domain->setRegistrationStatus($status);

            return $domain;
        }

    } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
    }

    logModuleCall(
        'ipHost',          // The name of your module
        'GetDomainInformation',              // The action being performed (e.g., 'GetDomainSuggestions')
        $call,             // Data sent to the remote API or module
        $response,            // Data received from the remote API or module
        $params,    // (Optional) Additional array data to log
        $replaceVars = null   // (Optional) Sensitive data to mask in the log
    );

}
