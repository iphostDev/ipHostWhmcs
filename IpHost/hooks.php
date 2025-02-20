<?php
/**
 * WHMCS SDK Sample Registrar Module Hooks File
 *
 * Hooks allow you to tie into events that occur within the WHMCS application.
 *
 * This allows you to execute your own code in addition to, or sometimes even
 * instead of that which WHMCS executes by default.
 *
 * WHMCS recommends as good practice that all named hook functions are prefixed
 * with the keyword "hook", followed by your module name, followed by the action
 * of the hook function. This helps prevent naming conflicts with other addons
 * and modules.
 *
 * For every hook function you create, you must also register it with WHMCS.
 * There are two ways of registering hooks, both are demonstrated below.
 *
 * @see https://developers.whmcs.com/hooks/
 *
 * @copyright Copyright (c) WHMCS Limited 2016
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */

use WHMCS\Module\Registrar\IpHost\ApiClient;
use WHMCS\Module\Registrar\IpHost\Greeklish;
use WHMCS\Database\Capsule;
use WHMCS\Config\Setting;

// Require any libraries needed for the module to function.
// require_once __DIR__ . '/path/to/library/loader.php';
//
// Also, perform any initialization required by the service's library.

/**
 * Register a hook with WHMCS.
 *
 * add_hook(string $hookPointName, int $priority, string|array|Closure $function)
 */
add_hook('AdminHomeWidgets', 1, function($vars) {
    //return new SampleRegistrarModuleWidget();

        $table = "tblregistrars";
        $fields = "value";
        $where = array("setting"=>'ShowLog');
        $result = select_query($table,$fields,$where);
        $data = mysql_fetch_array($result);
        
        $showlog = decrypt($data[0]);

        if ($showlog === "on") {
            return new SampleRegistrarModuleWidget();
        }

});

/**
 * Sample Registrar Module Admin Dashboard Widget.
 *
 * @see https://developers.whmcs.com/addon-modules/admin-dashboard-widgets/
 */
class SampleRegistrarModuleWidget extends \WHMCS\Module\AbstractWidget
{
    protected $title = 'IpHost Log';
    protected $description = '';
    protected $weight = 150;
    protected $columns = 2;
    protected $cache = false;
    protected $cacheExpiry = 120;
    protected $requiredPermission = '';

    public $showLog;
    public $modulename;
    public $modulepath;
    public $settings;

    public function getData()
    {
        $command = 'GetActivityLog';
        $postData = array(
            'description' => 'iphost'
        );

        $results = localAPI($command, $postData, '');
        return $results;
    }

    public function generateOutput($data)
    {
            $html = '';
            $html .= '<h3>More Logs for Module you can see <strong><a href="/index.php?rp=/admin/logs/module-log">Here</a></strong> (Have to enable Module Logging to ON) </h3>';
            $html .= '<table border="1" id="IphostLogTable">';
            $html .= '<tr class="iphosthead"><td>Date</td><td>Message</td></tr>';

            foreach ($data['activity']['entry'] as $product) {
                $html .= '<tr><td>'.$product['date'].'</td>';
                $html .= '<td>'.$product['description'].'</td></tr>';
            }        

            $html .= '</table>';

            return <<<EOF

            <style>
                #IphostLogTable td {padding:10px;}
                .iphosthead {background:#fc4f00;color:#fff;font-weight:bold;}
            </style>
            <div class="widget-content-padded">
               $html 
            </div>
            EOF;
    }
}


add_hook('AdminAreaHeaderOutput', 1, function($vars)
{
    $output .= <<<HTML
    <script type="text/javascript">
        $(document).ready(function(){
            console.log($('#registrarsDropDown option:selected').val())
            console.log('iphost')
            if ( $('#registrarsDropDown option:selected').val() === 'IpHost') {
                //console.log($('#registrarsDropDown option:selected').val())
                //$('input[type="button"][value="Transfer"]').hide()
                //$('input[type="button"][value="Modify Contact Details"]').hide()
                $('input[type="button"][value="Request Delete"]').hide()
                $('input[type="button"][value="Release Domain"]').hide()
            } else {
                $('input[type="button"][value="Transfer"]').show()
                $('input[type="button"][value="Modify Contact Details"]').show()
                $('input[type="button"][value="Request Delete"]').show()
                $('input[type="button"][value="Release Domain"]').show()
            }
        });
    </script>
HTML;

    return $output;
});


add_hook('AdminAreaFooterOutput', 1, function($vars) {
    return <<<HTML
<script type="text/javascript">

    window.onload = function() {


        var firstFieldArea = document.querySelector('#frmDomainContactModification .fieldarea');
            if (firstFieldArea) {
                var registrar = firstFieldArea.textContent.trim(); // trim() to remove leading/trailing whitespace
        }

        //add mesage box for delay
        var successBox = document.querySelector('#frmDomainContactModification .form');
        var infoDiv = document.createElement('div');
        infoDiv.textContent = 'Προσοχή οι αλλαγές που θα πραγματοποιήσετε παρακάτω, είναι πιθανόν να μην εμφανιστούν άμεσα'; 
        infoDiv.style.textAlign = 'center';
        infoDiv.style.backgroundColor = '#fcf8e3';
        infoDiv.style.padding = '10px';
        infoDiv.style.fontWeight = 'bold';
        if (successBox) {
            successBox.parentNode.insertBefore(infoDiv, successBox);
        }

        function techElements() {
                        //tech
                        var wc_technical = document.querySelector('input[name="wc[Technical]"]');
                        if (wc_technical) {
                            wc_technical.style.display = 'none';
                            if (wc_technical.parentElement) {
                                wc_technical.parentElement.style.display = 'none';
                            }
                        }

                        var sel_technical = document.querySelector('select[name="sel[Technical]"]');
                        if (sel_technical) {
                             sel_technical.style.display = 'none';
                             if (sel_technical.parentElement) {
                                sel_technical.parentElement.style.display = 'none';
                            }
                        }

                        var inputContact = document.querySelector('input[name="contactdetails[Technical][IPHOST_contact]"]');
                        if (inputContact) {
                            var parentRow = inputContact.closest('tr');
                            if (parentRow) {
                                parentRow.style.display = 'none';
                            }
                        }

                        //read only
                        var techFirstname = document.querySelector('input[name="contactdetails[Technical][First Name]"]');
                            if (techFirstname) {
                                techFirstname.readOnly = true; // Makes the field read-only
                        }
                        var techLastName = document.querySelector('input[name="contactdetails[Technical][Last Name]"]');
                            if (techLastName) {
                                techLastName.readOnly = true; // Makes the field read-only
                        }
                        var techCompany = document.querySelector('input[name="contactdetails[Technical][Company Name]"]');
                            if (techCompany) {
                                techCompany.readOnly = true; // Makes the field read-only
                        }
                    }


                    function billingElements() {
                        //billng
                        var wc_billing = document.querySelector('input[name="wc[Billing]"]');
                        if (wc_billing) {
                            wc_billing.style.display = 'none';
                            if (wc_billing.parentElement) {
                                wc_billing.parentElement.style.display = 'none';
                            }
                        }

                        var sel_billing = document.querySelector('select[name="sel[Billing]"]');
                        if (sel_billing) {
                             sel_billing.style.display = 'none';
                             if (sel_billing.parentElement) {
                                sel_billing.parentElement.style.display = 'none';
                            }
                        }

                        var inputContact = document.querySelector('input[name="contactdetails[Billing][IPHOST_contact]"]');
                        if (inputContact) {
                            var parentRow = inputContact.closest('tr');
                            // Hide the parent row
                            if (parentRow) {
                                parentRow.style.display = 'none';
                            }
                        }

                        //read only
                        var billFirstname = document.querySelector('input[name="contactdetails[Billing][First Name]"]');
                            if (billFirstname) {
                                billFirstname.readOnly = true; // Makes the field read-only
                        }
                        var billLastName = document.querySelector('input[name="contactdetails[Billing][Last Name]"]');
                            if (billLastName) {
                                billLastName.readOnly = true; // Makes the field read-only
                        }
                        var billCompany = document.querySelector('input[name="contactdetails[Billing][Company Name]"]');
                            if (billCompany) {
                                billCompany.readOnly = true; // Makes the field read-only
                        }
                    }


                    function AdminElements() {
                        //admin
                        var wc_admin = document.querySelector('input[name="wc[Admin]"]');
                        if (wc_admin) {
                            wc_admin.style.display = 'none';
                            if (wc_admin.parentElement) {
                                wc_admin.parentElement.style.display = 'none';
                            }
                        }

                        var sel_admin = document.querySelector('select[name="sel[Admin]"]');
                        if (sel_admin) {
                             sel_admin.style.display = 'none';
                             if (sel_admin.parentElement) {
                                sel_admin.parentElement.style.display = 'none';
                            }
                        }

                        var inputContact = document.querySelector('input[name="contactdetails[Admin][IPHOST_contact]"]');
                        if (inputContact) {
                            var parentRow = inputContact.closest('tr');
                            // Hide the parent row
                            if (parentRow) {
                                parentRow.style.display = 'none';
                            }
                        }


                        //read only
                        var adminFirstname = document.querySelector('input[name="contactdetails[Admin][First Name]"]');
                            if (adminFirstname) {
                                adminFirstname.readOnly = true; // Makes the field read-only
                        }
                        var adminLastName = document.querySelector('input[name="contactdetails[Admin][Last Name]"]');
                            if (adminLastName) {
                                adminLastName.readOnly = true; // Makes the field read-only
                        }
                        var adminCompany = document.querySelector('input[name="contactdetails[Admin][Company Name]"]');
                            if (adminCompany) {
                                adminCompany.readOnly = true; // Makes the field read-only
                        }
                    }


                    //create new buttons
                    function createButtons(title, elementID, form) {

                        var span = document.createElement('span');
                        // Set span properties
                        span.innerText = 'Change ' + title; // Span text
                        span.id = 'myCustomSpan'; // Optional: Assign an ID to the span
                        span.style.cursor = 'pointer'; // Change cursor to pointer for better UX
                        span.style.color = 'blue'; // Optional: Change color to indicate it's clickable
                        span.style.textDecoration = 'underline'; // Optional: Underline to indicate it's a link
                        // Get the reference to the element before which the span will be inserted
                        var technicalElement = document.getElementById(elementID);
                        // Insert the span before the specified element
                        if (technicalElement) {
                            technicalElement.parentNode.insertBefore(span, technicalElement);
                        }
                        // Add click event listener to the span
                        span.addEventListener('click', function() {
                            // Get all input fields within the table
                            var inputs = technicalElement.querySelectorAll('input[type="text"]');
                            // Clear each input field
                            inputs.forEach(function(input) {
                                input.value = ''; // Set the value of each input to an empty string
                            });


                            var Firstname = document.querySelector('input[name="contactdetails[' + form + '][First Name]"]');
                            if (Firstname) {
                                Firstname.readOnly = false; // Makes the field read-only
                            }
                            var LastName = document.querySelector('input[name="contactdetails[' + form + '][Last Name]"]');
                            if (LastName) {
                                LastName.readOnly = false; // Makes the field read-only
                            }
                            var Company = document.querySelector('input[name="contactdetails[' + form + '][Company Name]"]');
                            if (Company) {
                                Company.readOnly = false; // Makes the field read-only
                            }

                        });
                    }

        if (registrar === 'IpHost') { 
                    //Start HERE
                    techElements()
                    billingElements()
                    AdminElements()

                    createButtons('Tech Details', 'Technicalcustomwhois', 'Technical')
                    createButtons('Billing Details', 'Billingcustomwhois', 'Billing')
                    createButtons('Admin Details', 'Admincustomwhois', 'Admin')

        }
    }
</script>
HTML;
});

/*
add_hook('AdminAreaFooterOutput', 1, function($vars) {
    return <<<HTML
<script type="text/javascript">

    window.onload = function() {

        var tech = null;
        var billing = null;
        var admin = null;


        function getNumericValue(value) {
            // Use a regular expression to replace non-digit characters with an empty string
            var numericValue = value.replace(/\D/g, '');
            // Convert the result to a number
            return numericValue ? Number(numericValue) : null; // Return null if no digits were found
        }


        function createUrl(){
            console.log(tech,billing,admin)
            var formElement = document.getElementById('frmDomainContactModification'); // Get the form by ID
            console.log("Form Action:", formElement.action)

            if (tech !== null ) {
               formElement.action = formElement.action + "&tech_erp_id=" + tech;   
            }
            if (billing !== null ) {
               formElement.action = formElement.action + "&billing_erp_id=" + billing;   
            }
            if (admin !== null) {
               formElement.action = formElement.action + "&admin_erp_id=" + admin;   
            }
        }


        function modifyUrl() {
            console.log(tech,billing,admin)
            const formElement = document.getElementById('frmDomainContactModification');
            const currentUrl = formElement.action; // Get the current URL
            const url = new URL(currentUrl, window.location.origin); // Add origin if needed
            url.searchParams.set('tech_erp_id', tech);
            url.searchParams.set('billing_erp_id', billing);
            url.searchParams.set('admin_erp_id', admin);
            formElement.action = url.toString();
            console.log(url.toString())
        }



        function hideElementsForNewContact() {

            var secondRadioButton = document.getElementById('Technical2');
            if (secondRadioButton) {
                secondRadioButton.closest('p').style.display = 'none';
            }
            // Hide the table
            var technicalTable = document.getElementById('Technicalcustomwhois');
            if (technicalTable) {
                technicalTable.style.display = 'none';
            }

            var secondRadioButton = document.getElementById('Billing2');
            if (secondRadioButton) {
                secondRadioButton.closest('p').style.display = 'none';
            }
            // Hide the table
            var billingTable = document.getElementById('Billingcustomwhois');
            if (billingTable) {
                billingTable.style.display = 'none';
            }

            var secondRadioButton = document.getElementById('Admin2');
            if (secondRadioButton) {
                secondRadioButton.closest('p').style.display = 'none';
            }
            // Hide the table
            var billingTable = document.getElementById('Admincustomwhois');
            if (billingTable) {
                billingTable.style.display = 'none';
            }

        }


        var pageName = window.location.pathname.split("/").pop()
        if (pageName === 'clientsdomaincontacts.php') {

            hideElementsForNewContact()

            //console.log("contactsss page")
            
            document.querySelector('input[name="wc[Technical]"]').setAttribute('checked', 'checked');
            document.querySelector('input[name="wc[Technical]"]').checked = true;

            document.querySelector('input[name="wc[Billing]"]').setAttribute('checked', 'checked');
            document.querySelector('input[name="wc[Billing]"]').checked = true;

            document.querySelector('input[name="wc[Admin]"]').setAttribute('checked', 'checked');
            document.querySelector('input[name="wc[Admin]"]').checked = true;

            var tech_element = document.querySelector('select[name="sel[Technical]"]');
            if (tech_element) {
                tech = getNumericValue(tech_element.options[tech_element.selectedIndex].value);
            }

            var billing_element = document.querySelector('select[name="sel[Billing]"]');
            if (billing_element) {
                billing = getNumericValue(billing_element.options[billing_element.selectedIndex].value);
            }

            var admin_element = document.querySelector('select[name="sel[Admin]"]');
            if (admin_element) {
                admin = getNumericValue(admin_element.options[admin_element.selectedIndex].value);
            }


            createUrl();
        
        }

        // Function to handle change event
        function handleTechChange() {
            var selectElement = document.querySelector('select[name="sel[Technical]"]');
            if (selectElement) {
                tech = getNumericValue(selectElement.value);
                modifyUrl();
            }
        }

        function handleBillingChange() {
            var selectElement = document.querySelector('select[name="sel[Billing]"]');
            if (selectElement) {
                billing = getNumericValue(selectElement.value);
                modifyUrl();
            }
        }

        function handleAdminChange() {
            var selectElement = document.querySelector('select[name="sel[Admin]"]');
            if (selectElement) {
                admin = getNumericValue(selectElement.value);
                modifyUrl();
            }
        }

        // Add change event listener to the select element
        var techElement = document.querySelector('select[name="sel[Technical]"]');
        if (techElement) {
            techElement.addEventListener('change', handleTechChange);
        }

        var billingElement = document.querySelector('select[name="sel[Billing]"]');
        if (billingElement) {
            billingElement.addEventListener('change', handleBillingChange);
        }

        var adminElement = document.querySelector('select[name="sel[Admin]"]');
        if (adminElement) {
            adminElement.addEventListener('change', handleAdminChange);
        }

    }
</script>
HTML;
});
*/



/*
add_hook('ClientAreaPageDomainDetails', 1, function($vars) {
$output .= <<<HTML
    <script type="text/javascript">
        $(document).ready(function(){
            console.log('domains')
            
        });
    </script>
HTML;

    return $output;
});
*/


add_hook('ContactAdd', 1, function($params) {

 try {
    
    $APItoken = Capsule::table('tblregistrars')
        ->where('registrar', 'IpHost')
        ->where('setting', 'APItoken')
        ->value('value'); // Retrieve the value column

    $APItoken = decrypt($APItoken);
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
            "telephones" => [str_replace(" ", "", $params["phonenumber"])],
            "mobiles" => [],
            "faxes" => [],
            "tax_id" => "",
            "tax_office" => "",
            "occupation" => ""   
        );

        $api = new ApiClient();
        $api->call('/contact', $contactData, $APItoken, 'POST');

        if ( $api->getFromResponse('contact_id') ) {
            $contact_id = $api->getFromResponse('contact_id');
            update_query('tblcontacts', array(
                'erp_contact_id' => $contact_id), 
                array('id' => $params['contactid'])
            );
        }

        if (!$api->getFromResponse('success')) {
            logActivity("Contact {$params["firstname"]}-{$params["lastname"]} not created to IPHOST");    
        }

    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
});


add_hook('ContactEdit', 1, function($params) {

    if ($params["olddata"]["erp_contact_id"]) {

        $erp_contact_id = $params["olddata"]["erp_contact_id"];

        $APItoken = Capsule::table('tblregistrars')
            ->where('registrar', 'IpHost')
            ->where('setting', 'APItoken')
            ->value('value'); // Retrieve the value column

        $APItoken = decrypt($APItoken);
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
            "telephones" => [str_replace(" ", "", $params["phonenumber"])],
            "mobiles" => [],
            "faxes" => [],
            "tax_id" => "",
            "tax_office" => "",
            "occupation" => ""   
        );

        $api = new ApiClient();
        $api->call('/contact/'.$erp_contact_id, $contactData, $APItoken, 'PUT');
        if (!$api->getFromResponse('success')) {
            logActivity("Contact {$params["firstname"]}-{$params["lastname"]} not updated to IPHOST");    
        }
        
    }

});

add_hook('AfterRegistrarModuleInit', 1, function($params) {
    $result = full_query("SHOW COLUMNS FROM tblcontacts LIKE 'erp_contact_id'");
    
    if (!mysql_num_rows($result)) {
        full_query("ALTER TABLE tblcontacts ADD `erp_contact_id` VARCHAR(255) NULL");
    }
});

add_hook('ClientAreaPage', 1, function($params) {
    $result = full_query("SHOW COLUMNS FROM tblcontacts LIKE 'erp_contact_id'");
    
    if (!mysql_num_rows($result)) {
        full_query("ALTER TABLE tblcontacts ADD `erp_contact_id` VARCHAR(255) NULL");
    }
});



add_hook('PreRegistrarSaveContactDetails', 1, function($vars) {

    //var_dump($vars);
    //die;

    $errors = [];
    $forms = ['Technical','Billing','Admin'];

    foreach ($forms as $form) {
        
        $firstname = $vars['params']['contactdetails'][$form]['First Name'];
        $lastname = $vars['params']['contactdetails'][$form]['Last Name'];
        $email = $vars['params']['contactdetails'][$form]['Email Address'];
        $company = $vars['params']['contactdetails'][$form]['Company Name'];

        $address = $vars['params']['contactdetails'][$form]['Address 1'];
        $city = $vars['params']['contactdetails'][$form]['City'];
        $state = $vars['params']['contactdetails'][$form]['State'];
        $postcode = $vars['params']['contactdetails'][$form]['Postcode'];
        $phone = $vars['params']['contactdetails'][$form]['Phone Number'];


        if (empty($firstname) || !preg_match('/^[a-zA-Z\s\-]+$/', $firstname)) {
            $errors[] = $form.": First name required</strong>.";
        }
        if (strlen($company) == 0) {
            if (empty($lastname) || !preg_match('/^[a-zA-Z\s\-]+$/', $lastname)) {
                $errors[] = $form.": Last name required.";
            }
        }    
        if (empty($address)) {
            $errors[] = $form.": Address required.";
        }
        if (empty($city)) {
            $errors[] = $form.": City required.";
        }
        if (empty($state)) {
            $errors[] = $form.": State required.";
        }
        if (empty($postcode)) {
            $errors[] = $form.": State required.";
        }
        if (empty($email)) {
            $errors[] = $form.": Email Required.";
        }
        if (!preg_match('/^\+[1-9]\d{1,2}\.\d{6,14}$/', $phone)) {
            $errors[] = $form.": Invalid phone number format. Please use the format +<CountryCode>.<PhoneNumber> (e.g., +30.2105445900).";
        }
    }


    // Return the error if validation fails
    if (!empty($errors)) {
        return [
            'abortWithError' => "Contact not updated | ".implode("\n", $errors), // Each error on a new line
        ];
    }
});
