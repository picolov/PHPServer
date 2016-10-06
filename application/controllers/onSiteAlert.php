<?php
require 'baseController.php';
include 'PDFMerger.php';

require_once APPPATH . "/libraries/Jaspersoft/Client/Client.php";
require_once APPPATH . "/libraries/Jaspersoft/Exception/RESTRequestException.php";
require_once APPPATH . "/libraries/Jaspersoft/Tool/RESTRequest.php";
require_once APPPATH . "/libraries/Jaspersoft/Service/JRSService.php";
require_once APPPATH . "/libraries/Jaspersoft/Tool/Util.php";
require_once APPPATH . "/libraries/Jaspersoft/Service/ReportService.php";

class OnSiteAlert extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('OnSiteAlert');
    	$this->setObjMap(array(
    			
    	));
        parent::__construct();
    }
    
    function wrapTextMultiline($fieldWidthArray, $text, $maxSize = false) {
    	$resultTextArray = array_fill(0, count($fieldWidthArray), '');
    	// calculate font size
    	$minSize = 6;
    	if (strlen($text) == 0) {
    		if ($maxSize) {
    			$size = $maxSize;
    		} else {
    			$size = 10;
    		}
    	} else {
    		$isFit = false;
    		$totalWidth = 0;
    		foreach ($fieldWidthArray as $lineWidth) {
    			$totalWidth = $totalWidth + $lineWidth;
    		}
    		$size = $this->calculateFontSize($totalWidth, strlen($text), $maxSize);
    		while (!$isFit) {
    			$strUnfitted = $text;
    			for ($i = 0; $i < count($fieldWidthArray); $i++) {
    				$lineWidth = $fieldWidthArray[$i];
    				$lineCharCount = floor($lineWidth / ($size * 0.617284));
    				$idxOfSpacebar = strrpos(substr($strUnfitted, 0, $lineCharCount), ' ') + 1;
    				if ($lineCharCount >= strlen($strUnfitted)) {
    					$idxOfSpacebar = strlen($strUnfitted);
    				}
    				if ($idxOfSpacebar == -1) {
    					$idxOfSpacebar = 0;
    				} else {
    					$resultTextArray[$i] = '<style size="' . $size . '">' . htmlspecialchars(substr($strUnfitted, 0, $idxOfSpacebar)) . '</style>';
    					//$resultTextArray[$i] = '.' . substr($strUnfitted, 0, $idxOfSpacebar) . '.';
    				}
    				$strUnfitted = substr($strUnfitted, $idxOfSpacebar);
    			}
    			if (strlen($strUnfitted) == 0) {
    				$isFit = true;
    			} else {
    				$size--;
    				if ($size <= $minSize) {
    					$isFit = true;
    				}
    			}
    		}
    	}
    	return $resultTextArray;
    }
    
    function calculateFontSize($fieldWidth, $charCount, $maxSize = false) {
    	//return floor(($fieldSize / $charCount) * (1.62));
    	$minSize = 6;
    	if ($charCount == 0) {
    		if ($maxSize) {
    			$size = $maxSize;
    		} else {
    			$size = 10;
    		}
    	} else {
    		$size = floor(($fieldWidth / $charCount) * 1.62);
    		if ($maxSize) {
    			if ($size > $maxSize) {
    				$size = $maxSize;
    			}
    		}
    	}
    	if ($size < $minSize) {
    		$size = $minSize;
    	}
    	return $size;
    }
    
    function wrapText($fieldWidth, $text, $maxSize = false) {
    	$size = $this->calculateFontSize($fieldWidth, strlen($text), $maxSize);
    	return '<style size="' . $size . '">' . htmlspecialchars($text) . '</style>';
    }
    
    /**
     * Get a List of Summary job by Job id
     * @return multitype:unknown
     */
    
    function listByJob_get() {
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$itemCursor = $itemModel->find(array('job' => $this->get('_id')));
    	foreach ($itemCursor as $item) {
    		foreach($item as $key => $value) {
    			$prop = $item[$key];
    			if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
    				$innerItemModel = $this->db->selectCollection($prop['$ref']);
    				$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
    				$item[$key] = $innerItem;
    			}
    		}
    		$data[] = $item;
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Save First Alert and send email with pdf to mailTo attrib
     * @return multitype:unknown
     */
    function save_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
    	$mailMessage = null;
    	if (is_array($data)) {
    		if (!array_key_exists('_id', $data)) {
    			$data['_id'] = UUID::v4();
    		}
    		foreach ($data as $key => $value) {
    			if (is_array($value) && array_key_exists('_id', $value)) {
    				if (array_key_exists($key, $this->objMap)) {
    					$data[$key] = array('$ref' => $this->objMap[$key], '$id' => $value['_id']);
    				} else {
    					$data[$key] = $value['_id'];
    				}
    			}
    		}
    		$data['createdBy'] = $this->token['user']['username'];
    		$data['createdTime'] = time();
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$itemModel = $this->db->selectCollection($this->docName);
    		$itemModel->save($data);
    		if (array_key_exists('mailTo', $data) && strlen(trim($data['mailTo'])) > 0) {    			
    			//Generate PDF
    			$c = new Jaspersoft\Client\Client(
    					"http://iclogik.com:8080/jasperserver",
    					"jasperadmin",
    					"jasperadmin"
    			);
    			$onSiteAlertModel = $this->db->selectCollection('OnSiteAlert');
    			$onSiteAlert = $onSiteAlertModel->findOne(array('_id' => $data['_id']));
    			 
    			$jobId = $onSiteAlert['job'];
    			 
    			$onSiteDailyJobModel = $this->db->selectCollection('OnSiteDailyJob');
    			$onSiteDailyJob = $onSiteDailyJobModel->findOne(array('job' => $jobId));
    			 
    			$jarSetsOpenHoleModel = $this->db->selectCollection('JarSetsOpenHole');
    			$jarSetsOpenHole = $jarSetsOpenHoleModel->findOne(array('job' => $jobId));
    			 
    			$jobModel = $this->db->selectCollection('Job');
    			$job = $jobModel->findOne(array('_id' => $jobId));
    			 
    			$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
    			$serviceOrder = $serviceOrderModel->findOne(array('job' => $jobId));
    			 
    			if (key_exists('serviceCompany', $job) && $job['serviceCompany'] != null) {
    				$innerItemModel = $this->db->selectCollection($job['serviceCompany']['$ref']);
    				$wirelineCompany = $innerItemModel->findOne(array('_id' => $job['serviceCompany']['$id']));
    			} else {
    				$wirelineCompany = array();
    			}
    			if (key_exists('oilCompany', $job) && $job['oilCompany'] != null) {
    				$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
    				$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
    			} else {
    				$oilCompany = array();
    			}
    			 
    			$runAlert = key_exists('run', $onSiteAlert) ? $onSiteAlert['run'] : '';
    			 
    			$comanComment = key_exists('comanComment', $onSiteAlert) ? $this->wrapTextMultiline(array(302, 467, 467), $onSiteAlert['comanComment'], 14) : array('','','');
    			$additionalComment = key_exists('additionalComment', $onSiteAlert) ? $this->wrapTextMultiline(array(334, 467, 467), $onSiteAlert['additionalComment'], 14) : array('','','');
    			$controls = array(
    					'onSiteAlert' => key_exists('uid', $job) ? $job['uid'] : '',
    					 
    					'dateOfOccurence' => key_exists('date', $onSiteAlert) ? $this->wrapText(347, gmdate("m/d/y", $onSiteAlert['date']), 14) : '',
    					'technicianName' => key_exists('technician', $onSiteAlert) ? $this->wrapText(357, $onSiteAlert['technician'], 14) : '',
    					'serviceCompany' => ($wirelineCompany != null && key_exists('name', $wirelineCompany)) ? $this->wrapText(357, $wirelineCompany['name'], 14) : '',
    					'oilCompany' => ($oilCompany != null && key_exists('name', $oilCompany)) ? $this->wrapText(381, $oilCompany['name'], 14) : '',
    					'natureOfAlert' => key_exists('nature', $onSiteAlert) ? $this->wrapText(369, $onSiteAlert['nature'], 14) : '',
    					'holeSize' => ($onSiteDailyJob != null && key_exists('holeSize', $onSiteDailyJob)) ? $this->wrapText(406, $onSiteDailyJob['holeSize'], 14) : '',
    					'deviation' => key_exists('deviation', $job) ? $this->wrapText(406, $job['deviation'], 14) : '',
    					'mudWeight' => ($serviceOrder != null && key_exists('mudWt', $serviceOrder)) ? $this->wrapText(391, $serviceOrder['mudWt'], 14) : '',
    					'temperature' => ($job != null && key_exists('temp', $job)) ? $this->wrapText(381, $job['temp'], 14) : '',
    					'tvd' => key_exists('tvd', $onSiteAlert) ? $this->wrapText(432, $onSiteAlert['tvd'], 14) : '',
    					'casingShoeDepth' => key_exists('casingShoe', $onSiteAlert) ? $this->wrapText(347, $onSiteAlert['casingShoe'], 14) : '',
    					'depthStuck' => key_exists('depthStuck', $onSiteAlert) ? $this->wrapText(381, $onSiteAlert['depthStuck'], 14) : '',
    					'toolStringWeightInAir' => key_exists('wia_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(320, $jarSetsOpenHole['wia_' . $runAlert], 14) : '',
    					'toolStringWeightInFluid' => key_exists('wif_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(307, $jarSetsOpenHole['wif_' . $runAlert], 14) : '',
    					'weakPoint' => key_exists('wp_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(391, $jarSetsOpenHole['wp_' . $runAlert], 14) : '',
    					'nameOfToolString' => key_exists('toolstringName_' . $runAlert, $jarSetsOpenHole) && is_array($jarSetsOpenHole['toolstringName_' . $runAlert]) ? $this->wrapText(343, implode(' , ',$jarSetsOpenHole['toolstringName_' . $runAlert]), 14) : '',
    					'lbsPulledOnCableAtSurface' => key_exists('pullSurface', $onSiteAlert) ? $this->wrapText(282, $onSiteAlert['pullSurface'], 14) : '',
    					'headTension' => key_exists('headTension', $onSiteAlert) ? $this->wrapText(379, $onSiteAlert['headTension'], 14) : '',
    					'jarNumber' => key_exists('jarSerial_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(391, $jarSetsOpenHole['jarSerial_' . $runAlert], 14) : '',
    					'jarSetting' => key_exists('actualJar_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(397, $jarSetsOpenHole['actualJar_' . $runAlert], 14) : '',
    					'bottomHolePressure' => ($serviceOrder != null && key_exists('bhp', $serviceOrder)) ? $this->wrapText(333, $serviceOrder['bhp'], 14) : '',
    					'companyMansComments_1' => $comanComment[0],
    					'companyMansComments_2' => $comanComment[1],
    					'companyMansComments_3' => $comanComment[2],
    					'additionalComments_1' => $additionalComment[0],
    					'additionalComments_2' => $additionalComment[1],
    					'additionalComments_3' => $additionalComment[2]
    					 
    			);
    			 
    			$report = $c->reportService()->runReport('/reports/isi/firstAlertInformationNeeded', 'pdf', null, null, $controls);
    			
    			// Settings
    			$to          = $data['mailTo'];
    			$from        = "KMS admin";
    			$subject     = "First Alert Report Created";
    			$mainMessage = "First Alert Report Created, emailed with the PDF attachment";
    			$fileatttype = "application/pdf";
    			$fileattname = "FirstAlert.pdf";
    			$headers = "From: $from";
    		
    			// This attaches the file
    			$semi_rand     = md5(time());
    			$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
    			$headers      .= "\nMIME-Version: 1.0\n" .
    					"Content-Type: multipart/mixed;\n" .
    					" boundary=\"{$mime_boundary}\"";
    			$message = "This is a multi-part message in MIME format.\n\n" .
    					"-{$mime_boundary}\n" .
    					"Content-Type: text/plain; charset=\"iso-8859-1\n" .
    					"Content-Transfer-Encoding: 7bit\n\n" .
    					$mainMessage  . "\n\n";
    		
    			$dataPdf = chunk_split(base64_encode($report));
    			$message .= "--{$mime_boundary}\n" .
    			"Content-Type: {$fileatttype};\n" .
    			" name=\"{$fileattname}\"\n" .
    			"Content-Disposition: attachment;\n" .
    			" filename=\"{$fileattname}\"\n" .
    			"Content-Transfer-Encoding: base64\n\n" .
    			$dataPdf . "\n\n" .
    			"-{$mime_boundary}-\n";
    		
    			// Send the email
    			if(mail($to, $subject, $message, $headers)) {
    				$mailMessage = 'successfuly executed and email sent to ' . $data['mailTo'];
    			}
    			else {
    				$mailMessage ='successfuly executed, but error when sent email to ' . $data['mailTo'];
    			}
    		}
    	}
    	if ($mailMessage) {
    		$output = ['status' => 1, 'message' => $mailMessage, 'data' => $data];
    	} else {
    		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	}
    	return $output;
    }
    
    /**
     * Update First Alert and send email with pdf to mailTo attrib
     * @return multitype:unknown
     */
    function update_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
    	$mailMessage = null;
    	if (is_array($data) && array_key_exists('_id', $data)) {
    		foreach ($data as $key => $value) {
    			if (is_array($value) && array_key_exists('_id', $value)) {
    				if (array_key_exists($key, $this->objMap)) {
    					$data[$key] = array('$ref' => $this->objMap[$key], '$id' => $value['_id']);
    				} else {
    					$data[$key] = $value['_id'];
    				}
    			}
    		}
    		$data['updatedBy'] = $this->token['user']['username'];
    		$data['updatedTime'] = time();
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$itemModel = $this->db->selectCollection($this->docName);
    		$dataModified = $itemModel->findOne(array('_id' => $data['_id']));
    		foreach ($data as $key => $value) {
    			$dataModified[$key] = $value;
    		}
    		$itemModel->update(array("_id" => $data['_id']), $dataModified);
    		if (array_key_exists('mailTo', $data) && strlen(trim($data['mailTo'])) > 0) {
	    		//Generate PDF
    			$c = new Jaspersoft\Client\Client(
    					"http://iclogik.com:8080/jasperserver",
    					"jasperadmin",
    					"jasperadmin"
    			);
    			$onSiteAlertModel = $this->db->selectCollection('OnSiteAlert');
    			$onSiteAlert = $onSiteAlertModel->findOne(array('_id' => $data['_id']));
    			
    			$jobId = $onSiteAlert['job'];
    			
    			$onSiteDailyJobModel = $this->db->selectCollection('OnSiteDailyJob');
    			$onSiteDailyJob = $onSiteDailyJobModel->findOne(array('job' => $jobId));
    			
    			$jarSetsOpenHoleModel = $this->db->selectCollection('JarSetsOpenHole');
    			$jarSetsOpenHole = $jarSetsOpenHoleModel->findOne(array('job' => $jobId));
    			
    			$jobModel = $this->db->selectCollection('Job');
    			$job = $jobModel->findOne(array('_id' => $jobId));
    			
    			$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
    			$serviceOrder = $serviceOrderModel->findOne(array('job' => $jobId));
    			
    			if (key_exists('serviceCompany', $job) && $job['serviceCompany'] != null) {
    				$innerItemModel = $this->db->selectCollection($job['serviceCompany']['$ref']);
    				$wirelineCompany = $innerItemModel->findOne(array('_id' => $job['serviceCompany']['$id']));
    			} else {
    				$wirelineCompany = array();
    			}
    			if (key_exists('oilCompany', $job) && $job['oilCompany'] != null) {
    				$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
    				$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
    			} else {
    				$oilCompany = array();
    			}
    			
    			$runAlert = key_exists('run', $onSiteAlert) ? $onSiteAlert['run'] : '';
    			
    			$comanComment = key_exists('comanComment', $onSiteAlert) ? $this->wrapTextMultiline(array(302, 467, 467), $onSiteAlert['comanComment'], 14) : array('','','');
    			$additionalComment = key_exists('additionalComment', $onSiteAlert) ? $this->wrapTextMultiline(array(334, 467, 467), $onSiteAlert['additionalComment'], 14) : array('','','');
    			$controls = array(
    					'onSiteAlert' => key_exists('uid', $job) ? $job['uid'] : '',
    			
    					'dateOfOccurence' => key_exists('date', $onSiteAlert) ? $this->wrapText(347, gmdate("m/d/y", $onSiteAlert['date']), 14) : '',
    					'technicianName' => key_exists('technician', $onSiteAlert) ? $this->wrapText(357, $onSiteAlert['technician'], 14) : '',
    					'serviceCompany' => ($wirelineCompany != null && key_exists('name', $wirelineCompany)) ? $this->wrapText(357, $wirelineCompany['name'], 14) : '',
    					'oilCompany' => ($oilCompany != null && key_exists('name', $oilCompany)) ? $this->wrapText(381, $oilCompany['name'], 14) : '',
    					'natureOfAlert' => key_exists('nature', $onSiteAlert) ? $this->wrapText(369, $onSiteAlert['nature'], 14) : '',
    					'holeSize' => ($onSiteDailyJob != null && key_exists('holeSize', $onSiteDailyJob)) ? $this->wrapText(406, $onSiteDailyJob['holeSize'], 14) : '',
    					'deviation' => key_exists('deviation', $job) ? $this->wrapText(406, $job['deviation'], 14) : '',
    					'mudWeight' => ($serviceOrder != null && key_exists('mudWt', $serviceOrder)) ? $this->wrapText(391, $serviceOrder['mudWt'], 14) : '',
    					'temperature' => ($job != null && key_exists('temp', $job)) ? $this->wrapText(381, $job['temp'], 14) : '',
    					'tvd' => key_exists('tvd', $onSiteAlert) ? $this->wrapText(432, $onSiteAlert['tvd'], 14) : '',
    					'casingShoeDepth' => key_exists('casingShoe', $onSiteAlert) ? $this->wrapText(347, $onSiteAlert['casingShoe'], 14) : '',
    					'depthStuck' => key_exists('depthStuck', $onSiteAlert) ? $this->wrapText(381, $onSiteAlert['depthStuck'], 14) : '',
    					'toolStringWeightInAir' => key_exists('wia_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(320, $jarSetsOpenHole['wia_' . $runAlert], 14) : '',
    					'toolStringWeightInFluid' => key_exists('wif_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(307, $jarSetsOpenHole['wif_' . $runAlert], 14) : '',
    					'weakPoint' => key_exists('wp_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(391, $jarSetsOpenHole['wp_' . $runAlert], 14) : '',
    					'nameOfToolString' => key_exists('toolstringName_' . $runAlert, $jarSetsOpenHole) && is_array($jarSetsOpenHole['toolstringName_' . $runAlert]) ? $this->wrapText(343, implode(' , ',$jarSetsOpenHole['toolstringName_' . $runAlert]), 14) : '',
    					'lbsPulledOnCableAtSurface' => key_exists('pullSurface', $onSiteAlert) ? $this->wrapText(282, $onSiteAlert['pullSurface'], 14) : '',
    					'headTension' => key_exists('headTension', $onSiteAlert) ? $this->wrapText(379, $onSiteAlert['headTension'], 14) : '',
    					'jarNumber' => key_exists('jarSerial_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(391, $jarSetsOpenHole['jarSerial_' . $runAlert], 14) : '',
    					'jarSetting' => key_exists('actualJar_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(397, $jarSetsOpenHole['actualJar_' . $runAlert], 14) : '',
    					'bottomHolePressure' => ($serviceOrder != null && key_exists('bhp', $serviceOrder)) ? $this->wrapText(333, $serviceOrder['bhp'], 14) : '',
    					'companyMansComments_1' => $comanComment[0],
    					'companyMansComments_2' => $comanComment[1],
    					'companyMansComments_3' => $comanComment[2],
    					'additionalComments_1' => $additionalComment[0],
    					'additionalComments_2' => $additionalComment[1],
    					'additionalComments_3' => $additionalComment[2]
    			
    			);
    			
    			$report = $c->reportService()->runReport('/reports/isi/firstAlertInformationNeeded', 'pdf', null, null, $controls);
	    		
	    		// Settings
	    		$to          = $data['mailTo'];
	    		$from        = "KMS admin";
	    		$subject     = "First Alert Report Updated";
	    		$mainMessage = "First Alert Report Updated, emailed with the PDF attachment";
	    		$fileatttype = "application/pdf";
	    		$fileattname = "FirstAlert.pdf";
	    		$headers = "From: $from";
	    		 
	    		// This attaches the file
	    		$semi_rand     = md5(time());
	    		$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
	    		$headers      .= "\nMIME-Version: 1.0\n" .
	    				"Content-Type: multipart/mixed;\n" .
	    				" boundary=\"{$mime_boundary}\"";
	    		$message = "This is a multi-part message in MIME format.\n\n" .
	    				"-{$mime_boundary}\n" .
	    				"Content-Type: text/plain; charset=\"iso-8859-1\n" .
	    				"Content-Transfer-Encoding: 7bit\n\n" .
	    				$mainMessage  . "\n\n";
	    		 
	    		$dataPdf = chunk_split(base64_encode($report));
	    		$message .= "--{$mime_boundary}\n" .
	    		"Content-Type: {$fileatttype};\n" .
	    		" name=\"{$fileattname}\"\n" .
	    		"Content-Disposition: attachment;\n" .
	    		" filename=\"{$fileattname}\"\n" .
	    		"Content-Transfer-Encoding: base64\n\n" .
	    		$dataPdf . "\n\n" .
	    		"-{$mime_boundary}-\n";
	    		 
	    		// Send the email
	    		if(mail($to, $subject, $message, $headers)) {
	    			$mailMessage = 'successfuly executed and email sent to ' . $data['mailTo'];
	    		}
	    		else {
	    			$mailMessage ='successfuly executed, but error when sent email to ' . $data['mailTo'];
	    		}
    		}
    	}
    	if ($mailMessage) {
    		$output = ['status' => 1, 'message' => $mailMessage, 'data' => $data];
    	} else {
    		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	}
    	return $output;
    }
}
?>