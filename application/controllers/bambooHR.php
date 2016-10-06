<?php
require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/UUID.php';

class BambooHR extends REST_Controller {
	
	function updateMe_get() {
		$startTime = time();
		$output = [];
		//$url = $this->get('url');
		$url = 'https://api.bamboohr.com/api/gateway.php/impactselector/v1/employees/directory';
		$username = '251e6f39b1796d8ab7f96c53db9f0ce31028f62b';
		$password = 'x';
		// Get cURL resource
		$curl = curl_init($url);
		// Set some options - we are passing in a useragent too here
		$options = array(
				CURLOPT_RETURNTRANSFER => true,     // return web page
				CURLOPT_HEADER         => false,    // don't return headers
				CURLOPT_FOLLOWLOCATION => true,     // follow redirects
				CURLOPT_ENCODING       => '',       // handle all encodings <== this must be set on Bamboo
				//CURLOPT_USERAGENT      => "KMS Update Sync Request", // who am i
				CURLOPT_AUTOREFERER    => true,     // set referer on redirect
				CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
				CURLOPT_TIMEOUT        => 120,      // timeout on response
				CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
				CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks
				CURLOPT_HTTPHEADER	   => array(
                                            'Accept: application/xml',
											'Content-Type: application/json'
											//'Content-Type: application/json'
                                            ),
				CURLOPT_HTTPAUTH	   => CURLAUTH_ANY,
				CURLOPT_USERPWD		   => "$username:$password"
		);
		curl_setopt_array( $curl, $options );
		$resp = curl_exec($curl);
		$xml = simplexml_load_string($resp);
		$error = curl_error($curl);
		$errorno = curl_errno($curl);
		
		// Close request to clear up some resources
		curl_close($curl);
		
		$con = new MongoClient ();
		$profileModel = $con->selectDB(_DB_NAME)->profile;
		
		$employees = $xml->employees->employee;
		$outputEmployees = array();
		$output['debug'] = '';
		$output['newEmployee'] = array();
		$output['updatedEmployee'] = array();
		for ($i = 0; $i < count($employees); $i++) {
			
			$employee = array();
			$employee['id'] = (string) $xml->employees->employee[$i]['id'];
			$employee['displayName'] = (string) $xml->employees->employee[$i]->field[0];
			$employee['firstName'] = (string) $xml->employees->employee[$i]->field[1];
			$employee['lastName'] = (string) $xml->employees->employee[$i]->field[2];
			$employee['nickName'] = (string) $xml->employees->employee[$i]->field[3];
			$employee['gender'] = (string) $xml->employees->employee[$i]->field[4];
			$employee['jobTitle'] = (string) $xml->employees->employee[$i]->field[5];
			$employee['workPhone'] = (string) $xml->employees->employee[$i]->field[6];
			$employee['mobilePhone'] = (string) $xml->employees->employee[$i]->field[7];
			$employee['workEmail'] = (string) $xml->employees->employee[$i]->field[8];			
			$employee['location'] = (string) $xml->employees->employee[$i]->field[9];
			$employee['workPhoneExtension'] = (string) $xml->employees->employee[$i]->field[10];
			$employee['photoUploaded'] = (string) $xml->employees->employee[$i]->field[11];
			$employee['photoUrl'] = (string) $xml->employees->employee[$i]->field[12];
			// disable SSL verification to simplify things, 
			// WARNING : this should be done with using the right certificate, but i'll just turn it off now
			$arrContextOptions=array(
					"ssl"=>array(
							"verify_peer"=>false,
							"verify_peer_name"=>false,
					),
			);
			$employee['photo'] = 'data:image/png;base64,' . base64_encode(file_get_contents($employee['photoUrl'], false, stream_context_create($arrContextOptions)));
			$employee['canUploadPhoto'] = (string) $xml->employees->employee[$i]->field[13];
			
			// Sync the employee to our database
			$profile = $profileModel->findOne(['employeeId' => $employee['id']]);
			// if it is existing then update document, override the data with the one in the bambooHR
			if ($profile) {
				if (array_key_exists('isUsingNickName', $profile) && $profile['isUsingNickName'] === true) {
					$profile['employeeName'] = $employee['nickName'] . ' ' . $employee['lastName'];
				} else {
					$profile['isUsingNickName'] = false;
					$profile['employeeName'] = $employee['firstName'] . ' ' . $employee['lastName'];
				}
				$profile['firstName'] = $employee['firstName'];
				$profile['lastName'] = $employee['lastName'];
				$profile['nickName'] = $employee['nickName'];
				$profile['positionName'] = $employee['jobTitle'];
				$profile['workPhone'] = $employee['workPhone'];
				$profile['mobile'] = $employee['mobilePhone'];
				$profile['email'] = $employee['workEmail'];
				$profile['city'] = $employee['location'];				
				if ($profile['positionName'] && (strpos(strtolower($profile['positionName']), 'tech') !== false || strpos(strtolower($profile['positionName']), 'trainer') !== false)) {
					$profile['position'] = 'Technician';
				} else if ($profile['positionName'] && strpos(strtolower($profile['positionName']), 'sales') !== false) {
					$profile['position'] = 'Sales';
				} else {
					$profile['position'] = '';
				}
				$profile['photo'] = $employee['photo'];
				$profileModel->update(array("_id" => $profile['_id']), $profile);
				$output['updatedEmployee'][] = array("id" => $employee['id'], "name" => $employee['displayName']);
			}
			// if it is not existing then create a new document 
			else {
				$profile = array();
				$profile['isUsingNickName'] = false;
				$profile['_id'] = $employee['id'];
				$profile['employeeId'] = $employee['id'];
				$profile['employeeName'] = $employee['firstName'] . ' ' . $employee['lastName'];
				$profile['firstName'] = $employee['firstName'];
				$profile['lastName'] = $employee['lastName'];
				$profile['nickName'] = $employee['nickName'];
				$profile['positionName'] = $employee['jobTitle'];
				$profile['workPhone'] = $employee['workPhone'];
				$profile['mobile'] = $employee['mobilePhone'];
				$profile['email'] = $employee['workEmail'];
				$profile['city'] = $employee['location'];
				if ($profile['positionName'] && (strpos(strtolower($profile['positionName']), 'tech') !== false  || strpos(strtolower($profile['positionName']), 'trainer') !== false)) {
					$profile['position'] = 'Technician';
				} else if ($profile['positionName'] && strpos(strtolower($profile['positionName']), 'sales') !== false) {
					$profile['position'] = 'Sales';
				} else {
					$profile['position'] = '';
				}
				$profile['photo'] = $employee['photo'];
				$profileModel->save($profile);
				$output['newEmployee'][] = array("id" => $employee['id'], "name" => $employee['displayName']);
			}
			
			$outputEmployees[] = array($profile['_id'], $profile['employeeName'], $profile['positionName'], $profile['position'], strpos(strtolower($profile['positionName']), 'tech'));
		}
		$output['timeTaken'] = (time() - $startTime);
		//$output['employeeList'] = $outputEmployees;
		echo json_encode($output);
		//echo var_dump($xml->employees->employee[0]->attributes()['id']);
	}
	
}
?>