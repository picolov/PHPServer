<?php
require 'baseController.php';
include 'PDFMerger.php';

require_once APPPATH . "/libraries/Jaspersoft/Client/Client.php";
require_once APPPATH . "/libraries/Jaspersoft/Exception/RESTRequestException.php";
require_once APPPATH . "/libraries/Jaspersoft/Tool/RESTRequest.php";
require_once APPPATH . "/libraries/Jaspersoft/Service/JRSService.php";
require_once APPPATH . "/libraries/Jaspersoft/Tool/Util.php";
require_once APPPATH . "/libraries/Jaspersoft/Service/ReportService.php";

class JarSetsImpactOrder extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('JarSetsImpactOrder');
    	$this->setObjMap(array(
    			'cableType' => 'TypeCable'
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
     * Get a Detail of Service Order by Job id
     * @return multitype:unknown
     */
    function detailByJob_get() {
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$item = $itemModel->findOne(array('job' => $this->get('_id')));
    	if ($item) {
	    	foreach($item as $key => $value) {
	    		$prop = $item[$key];
	    		if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
	    			$innerItemModel = $this->db->selectCollection($prop['$ref']);
	    			$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
	    			$item[$key] = $innerItem;
	    		}
	    	}
    	}
    	$data[] = $item;
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Save ImpactOrder and then send email with pdf to mailTo attrib
     * @return multitype:unknown
     */
    function save_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
    	$mailMessage = null;
    	if (is_array($data)) {
    		$jobInfo = $data['jobInfo'];
    		unset($data['jobInfo']);
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
    		
    		// update Job Info
    		$jobModel = $this->db->selectCollection("Job");
    		$jobInfoModified = $jobModel->findOne(array('_id' => $jobInfo['_id']));
    		$jobMap = array(
    				'rigType' => 'TypeRig',
    				'status' => 'StatusJob',
    				'oilCompany' => 'Company',
    				'serviceCompany' => 'Company',
    				'sales' => 'profile',
    				'wirelineEngineer' => 'Contact',
    				'companyMan' => 'Contact',
    				'dispatcher' => 'Contact',
    				'contact' => 'Contact',
    				'region' => 'Region'
    		);
    		foreach ($jobInfo as $key => $value) {
    			if (is_array($value) && array_key_exists('_id', $value)) {
    				if (array_key_exists($key, $jobMap)) {
    					$jobInfo[$key] = array('$ref' => $jobMap[$key], '$id' => $value['_id']);
    				} else {
    					$jobInfo[$key] = $value['_id'];
    				}
    			}
    		}
    		foreach ($jobInfo as $key => $value) {
    			$jobInfoModified[$key] = $value;
    		}
    		$jobModel->update(array("_id" => $jobInfo['_id']), $jobInfoModified);
    		// end update Job info
    		
    		if (array_key_exists('mailTo', $data) && strlen(trim($data['mailTo'])) > 0) {
    			// generate PDF
    			$c = new Jaspersoft\Client\Client(
					"http://iclogik.com:8080/jasperserver",
					"jasperadmin",
					"jasperadmin"
				);
			
    			$JarSetsImpactOrderModel = $this->db->selectCollection('JarSetsImpactOrder');
    			$JarSetsImpactOrder = $JarSetsImpactOrderModel->findOne(array('job' => $data['job']));
    			
    			$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
    			$serviceOrder = $serviceOrderModel->findOne(array('job' => $data['job']));
    			
    			$jobModel = $this->db->selectCollection('Job');
    			$job = $jobModel->findOne(array('_id' => $data['job']));
    			
    			$onSiteDailyJobModel = $this->db->selectCollection('OnSiteDailyJob');
    			$onSiteDailyJob = $onSiteDailyJobModel->findOne(array('job' => $data['job']));
    			
    			if (key_exists('well', $job)) {
    				$innerItemModel = $this->db->selectCollection($job['well']['$ref']);
    				$well = $innerItemModel->findOne(array('_id' => $job['well']['$id']));
    			} else {
    				$well = array();
    			}
    			if (key_exists('oilCompany', $job) && $job['oilCompany'] != null) {
    				$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
    				$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
    			} else {
    				$oilCompany = array();
    			}
    			if (key_exists('serviceCompany', $job) && $job['serviceCompany'] != null) {
    				$innerItemModel = $this->db->selectCollection($job['serviceCompany']['$ref']);
    				$wirelineCompany = $innerItemModel->findOne(array('_id' => $job['serviceCompany']['$id']));
    			} else {
    				$wirelineCompany = array();
    			}
    			if (key_exists('cableType', $JarSetsImpactOrder)) {
    				$innerItemModel = $this->db->selectCollection($JarSetsImpactOrder['cableType']['$ref']);
    				$cableType = $innerItemModel->findOne(array('_id' => $JarSetsImpactOrder['cableType']['$id']));
    			} else {
    				$cableType = array();
    			}
    			
    			$blockFieldLease = "";
    			if (key_exists('block', $job)) {
    				$blockFieldLease = $blockFieldLease . $job['block'] . '/';
    			}
    			if (key_exists('lease', $job)) {
    				$blockFieldLease = $blockFieldLease . $job['lease'] . '/';
    			}
    			if (key_exists('field', $job)) {
    				$blockFieldLease = $blockFieldLease . $job['field'] . '/';
    			}
    			if (strlen($blockFieldLease) > 0) {
    				$blockFieldLease = substr($blockFieldLease, 0, strlen($blockFieldLease) - 1);
    			}
    			
    			$detailsNotesArr = key_exists('detailsNotes', $JarSetsImpactOrder) ? $this->wrapTextMultiline(array(633, 710), $JarSetsImpactOrder['detailsNotes'], 10) : array('', '');
    			
    			$controls = array(
    					'JarSetsImpactOrder' => key_exists('uid', $job) ? $job['uid'] : '',
    			
    					'orderDate' => key_exists('orderDate', $JarSetsImpactOrder) ? $this->wrapText(253, gmdate("m/d/y", $JarSetsImpactOrder['orderDate']), 10) : '',
    					'orderBy' => key_exists('orderedBy', $JarSetsImpactOrder) ? $this->wrapText(153, $JarSetsImpactOrder['orderedBy'], 10) : '',
    					'serviceCompany' => key_exists('name', $wirelineCompany) ? $this->wrapText(253, $wirelineCompany['name'], 10) : '',
    					'operatingCompany' => key_exists('name', $oilCompany) ? $this->wrapText(253, $oilCompany['name'], 10) : '',
    					'contactName' => key_exists('contactName', $JarSetsImpactOrder) ? $this->wrapText(253, $JarSetsImpactOrder['contactName'], 10) : '',
    					'contactPhone' => key_exists('contactPhone', $JarSetsImpactOrder) ? $this->wrapText(253, $JarSetsImpactOrder['contactPhone'], 10) : '',
    					'contactEmail' => key_exists('contactEmail', $JarSetsImpactOrder) ? $this->wrapText(253, $JarSetsImpactOrder['contactEmail'], 10) : '',
    					'rtaNumber' => key_exists('uid', $job) ? $this->wrapText(153, $job['uid'], 10) : '',
    					'blockLeaseField' => $this->wrapText(153, $blockFieldLease, 10),
    					'wellNumber' => key_exists('wellName', $job) ? $this->wrapText(153, $job['wellName'], 10) : '',
    					'rigPlatform' => key_exists('rigName', $job) ? $this->wrapText(153, $job['rigName'], 10) : '',
    					'mudWeight' => key_exists('mudWt', $serviceOrder) ? $this->wrapText(253, $serviceOrder['mudWt'], 10) : '',
    					'cableType' => key_exists('name', $cableType) ? $this->wrapText(253, $cableType['name'], 10) : '',
    					'cableWeightInAir' => ($onSiteDailyJob!=null && key_exists('cableWeight', $onSiteDailyJob)) ? $this->wrapText(253, $onSiteDailyJob['cableWeight'], 10) : '',
    					'maxSafePull' => key_exists('maxSafePull', $JarSetsImpactOrder) ? $this->wrapText(153, $JarSetsImpactOrder['maxSafePull'], 10) : '',
    					'weakPoint' => key_exists('weakPoint', $JarSetsImpactOrder) ? $this->wrapText(153, $JarSetsImpactOrder['weakPoint'], 10) : '',
    					'totalDepth' => key_exists('td', $serviceOrder) ? $this->wrapText(153, $serviceOrder['td'], 10) : '',
    					'detailsNotes_1' => $detailsNotesArr[0],
    					'detailsNotes_2' => $detailsNotesArr[1],
    					"isiRep" => key_exists('technician', $JarSetsImpactOrder) ? $this->wrapText(323, $JarSetsImpactOrder['technician'], 10) : '',
    					'date' => key_exists('isiRepDate', $JarSetsImpactOrder) ? $this->wrapText(290, gmdate("m/d/y", $JarSetsImpactOrder['isiRepDate']), 10) : '',
    			
    			);
    			
    			foreach ($JarSetsImpactOrder['itemList'] as $idx => $runItem) {
    				$controls['run_' . ($idx + 1)] = key_exists('run', $runItem) ? $this->wrapText(54, $runItem['run'], 10) : '';
    				$controls['toolstring_' . ($idx + 1)] = key_exists('toolString', $runItem) && is_array($runItem['toolString']) ? $this->wrapText(159, implode(", ", $runItem['toolString']), 10) : '';
    				$controls['length_' . ($idx + 1)] = key_exists('length', $runItem) ? $this->wrapText(54, $runItem['length'], 10) : '';
    				$controls['weightInAir_' . ($idx + 1)] = key_exists('weightInAir', $runItem) ? $this->wrapText(83, $runItem['weightInAir'], 10) : '';
    				$controls['weigthInFluid_' . ($idx + 1)] = key_exists('weightInFluid', $runItem) ? $this->wrapText(58, $runItem['weightInFluid'], 10) : '';
    				$controls['surfaceTensionTotalDepth_' . ($idx + 1)] = key_exists('surfaceTensionTotalDepth', $runItem) ? $this->wrapText(74, $runItem['surfaceTensionTotalDepth'], 10) : '';
    				$controls['maxToolOuterDiameter_' . ($idx + 1)] = key_exists('maxToolOuterDiameter', $runItem) ? $this->wrapText(80, $runItem['maxToolOuterDiameter'], 10) : '';
    				$controls['targetJarSetting_' . ($idx + 1)] = key_exists('targetJarSetting', $runItem) ? $this->wrapText(74, $runItem['targetJarSetting'], 10) : '';
    				$controls['targetMaxJarForce_' . ($idx + 1)] = key_exists('targetMaxJarForce', $runItem) ? $this->wrapText(74, $runItem['targetMaxJarForce'], 10) : '';
    			}
    			$report = $c->reportService()->runReport('/reports/isi/impactProServiceOrder', 'pdf', null, null, $controls);
    			 
    			// Settings
    			$to          = $data['mailTo'];
    			$from        = "KMS admin";
    			$subject     = "Impact Pro Service Order Created";
    			$mainMessage = "Impact Pro Service Order Created, emailed with the PDF attachment";
    			$fileatttype = "application/pdf";
    			$fileattname = "ImpactProServiceOrder.pdf";
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
     * Update Impact Order and send email with pdf to mailTo attrib
     * @return multitype:unknown
     */
    function update_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
    	$mailMessage = null;
    	if (is_array($data) && array_key_exists('_id', $data)) {
    		$jobInfo = $data['jobInfo'];
    		unset($data['jobInfo']);
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
    		
    		// update Job Info
    		$jobModel = $this->db->selectCollection("Job");
    		$jobInfoModified = $jobModel->findOne(array('_id' => $jobInfo['_id']));
    		$jobMap = array(
    				'rigType' => 'TypeRig',
    				'status' => 'StatusJob',
    				'oilCompany' => 'Company',
    				'serviceCompany' => 'Company',
    				'sales' => 'profile',
    				'wirelineEngineer' => 'Contact',
    				'companyMan' => 'Contact',
    				'dispatcher' => 'Contact',
    				'contact' => 'Contact',
    				'region' => 'Region'
    		);
    		foreach ($jobInfo as $key => $value) {
    			if (is_array($value) && array_key_exists('_id', $value)) {
    				if (array_key_exists($key, $jobMap)) {
    					$jobInfo[$key] = array('$ref' => $jobMap[$key], '$id' => $value['_id']);
    				} else {
    					$jobInfo[$key] = $value['_id'];
    				}
    			}
    		}
    		foreach ($jobInfo as $key => $value) {
    			$jobInfoModified[$key] = $value;
    		}
    		$jobModel->update(array("_id" => $jobInfo['_id']), $jobInfoModified);
    		// end update Job info
    		
    		if (array_key_exists('mailTo', $data) && strlen(trim($data['mailTo'])) > 0) {
	    		// generate PDF
	    		$c = new Jaspersoft\Client\Client(
					"http://iclogik.com:8080/jasperserver",
					"jasperadmin",
					"jasperadmin"
				);
			
	    		$JarSetsImpactOrderModel = $this->db->selectCollection('JarSetsImpactOrder');
	    		$JarSetsImpactOrder = $JarSetsImpactOrderModel->findOne(array('job' => $dataModified['job']));
	    		 
	    		$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
	    		$serviceOrder = $serviceOrderModel->findOne(array('job' => $dataModified['job']));
	    		 
	    		$jobModel = $this->db->selectCollection('Job');
	    		$job = $jobModel->findOne(array('_id' => $dataModified['job']));
	    		 
	    		$onSiteDailyJobModel = $this->db->selectCollection('OnSiteDailyJob');
	    		$onSiteDailyJob = $onSiteDailyJobModel->findOne(array('job' => $dataModified['job']));
	    		 
	    		if (key_exists('well', $job)) {
	    			$innerItemModel = $this->db->selectCollection($job['well']['$ref']);
	    			$well = $innerItemModel->findOne(array('_id' => $job['well']['$id']));
	    		} else {
	    			$well = array();
	    		}
	    		if (key_exists('oilCompany', $job) && $job['oilCompany'] != null) {
	    			$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
	    			$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
	    		} else {
	    			$oilCompany = array();
	    		}
	    		if (key_exists('serviceCompany', $job) && $job['serviceCompany'] != null) {
	    			$innerItemModel = $this->db->selectCollection($job['serviceCompany']['$ref']);
	    			$wirelineCompany = $innerItemModel->findOne(array('_id' => $job['serviceCompany']['$id']));
	    		} else {
	    			$wirelineCompany = array();
	    		}
	    		if (key_exists('cableType', $JarSetsImpactOrder)) {
	    			$innerItemModel = $this->db->selectCollection($JarSetsImpactOrder['cableType']['$ref']);
	    			$cableType = $innerItemModel->findOne(array('_id' => $JarSetsImpactOrder['cableType']['$id']));
	    		} else {
	    			$cableType = array();
	    		}
	    		 
	    		$blockFieldLease = "";
	    		if (key_exists('block', $job)) {
	    			$blockFieldLease = $blockFieldLease . $job['block'] . '/';
	    		}
	    		if (key_exists('lease', $job)) {
	    			$blockFieldLease = $blockFieldLease . $job['lease'] . '/';
	    		}
	    		if (key_exists('field', $job)) {
	    			$blockFieldLease = $blockFieldLease . $job['field'] . '/';
	    		}
	    		if (strlen($blockFieldLease) > 0) {
	    			$blockFieldLease = substr($blockFieldLease, 0, strlen($blockFieldLease) - 1);
	    		}
	    		 
	    		$detailsNotesArr = key_exists('detailsNotes', $JarSetsImpactOrder) ? $this->wrapTextMultiline(array(633, 710), $JarSetsImpactOrder['detailsNotes'], 10) : array('', '');
	    		 
	    		$controls = array(
	    				'JarSetsImpactOrder' => key_exists('uid', $job) ? $job['uid'] : '',
	    				 
	    				'orderDate' => key_exists('orderDate', $JarSetsImpactOrder) ? $this->wrapText(253, gmdate("m/d/y", $JarSetsImpactOrder['orderDate']), 10) : '',
	    				'orderBy' => key_exists('orderedBy', $JarSetsImpactOrder) ? $this->wrapText(153, $JarSetsImpactOrder['orderedBy'], 10) : '',
	    				'serviceCompany' => key_exists('name', $wirelineCompany) ? $this->wrapText(253, $wirelineCompany['name'], 10) : '',
	    				'operatingCompany' => key_exists('name', $oilCompany) ? $this->wrapText(253, $oilCompany['name'], 10) : '',
	    				'contactName' => key_exists('contactName', $JarSetsImpactOrder) ? $this->wrapText(253, $JarSetsImpactOrder['contactName'], 10) : '',
	    				'contactPhone' => key_exists('contactPhone', $JarSetsImpactOrder) ? $this->wrapText(253, $JarSetsImpactOrder['contactPhone'], 10) : '',
	    				'contactEmail' => key_exists('contactEmail', $JarSetsImpactOrder) ? $this->wrapText(253, $JarSetsImpactOrder['contactEmail'], 10) : '',
	    				'rtaNumber' => key_exists('uid', $job) ? $this->wrapText(153, $job['uid'], 10) : '',
	    				'blockLeaseField' => $this->wrapText(153, $blockFieldLease, 10),
	    				'wellNumber' => key_exists('wellName', $job) ? $this->wrapText(153, $job['wellName'], 10) : '',
	    				'rigPlatform' => key_exists('rigName', $job) ? $this->wrapText(153, $job['rigName'], 10) : '',
	    				'mudWeight' => key_exists('mudWt', $serviceOrder) ? $this->wrapText(253, $serviceOrder['mudWt'], 10) : '',
	    				'cableType' => key_exists('name', $cableType) ? $this->wrapText(253, $cableType['name'], 10) : '',
	    				'cableWeightInAir' => ($onSiteDailyJob!=null && key_exists('cableWeight', $onSiteDailyJob)) ? $this->wrapText(253, $onSiteDailyJob['cableWeight'], 10) : '',
	    				'maxSafePull' => key_exists('maxSafePull', $JarSetsImpactOrder) ? $this->wrapText(153, $JarSetsImpactOrder['maxSafePull'], 10) : '',
	    				'weakPoint' => key_exists('weakPoint', $JarSetsImpactOrder) ? $this->wrapText(153, $JarSetsImpactOrder['weakPoint'], 10) : '',
	    				'totalDepth' => key_exists('td', $serviceOrder) ? $this->wrapText(153, $serviceOrder['td'], 10) : '',
	    				'detailsNotes_1' => $detailsNotesArr[0],
	    				'detailsNotes_2' => $detailsNotesArr[1],
	    				"isiRep" => key_exists('technician', $JarSetsImpactOrder) ? $this->wrapText(323, $JarSetsImpactOrder['technician'], 10) : '',
	    				'date' => key_exists('isiRepDate', $JarSetsImpactOrder) ? $this->wrapText(290, gmdate("m/d/y", $JarSetsImpactOrder['isiRepDate']), 10) : '',
	    				 
	    		);
	    		 
	    		foreach ($JarSetsImpactOrder['itemList'] as $idx => $runItem) {
	    			$controls['run_' . ($idx + 1)] = key_exists('run', $runItem) ? $this->wrapText(54, $runItem['run'], 10) : '';
	    			$controls['toolstring_' . ($idx + 1)] = key_exists('toolString', $runItem) && is_array($runItem['toolString']) ? $this->wrapText(159, implode(", ", $runItem['toolString']), 10) : '';
	    			$controls['length_' . ($idx + 1)] = key_exists('length', $runItem) ? $this->wrapText(54, $runItem['length'], 10) : '';
	    			$controls['weightInAir_' . ($idx + 1)] = key_exists('weightInAir', $runItem) ? $this->wrapText(83, $runItem['weightInAir'], 10) : '';
	    			$controls['weigthInFluid_' . ($idx + 1)] = key_exists('weightInFluid', $runItem) ? $this->wrapText(58, $runItem['weightInFluid'], 10) : '';
	    			$controls['surfaceTensionTotalDepth_' . ($idx + 1)] = key_exists('surfaceTensionTotalDepth', $runItem) ? $this->wrapText(74, $runItem['surfaceTensionTotalDepth'], 10) : '';
	    			$controls['maxToolOuterDiameter_' . ($idx + 1)] = key_exists('maxToolOuterDiameter', $runItem) ? $this->wrapText(80, $runItem['maxToolOuterDiameter'], 10) : '';
	    			$controls['targetJarSetting_' . ($idx + 1)] = key_exists('targetJarSetting', $runItem) ? $this->wrapText(74, $runItem['targetJarSetting'], 10) : '';
	    			$controls['targetMaxJarForce_' . ($idx + 1)] = key_exists('targetMaxJarForce', $runItem) ? $this->wrapText(74, $runItem['targetMaxJarForce'], 10) : '';
	    		}
	    		$report = $c->reportService()->runReport('/reports/isi/impactProServiceOrder', 'pdf', null, null, $controls);
	    		
	    		// Settings
	    		$to          = $data['mailTo'];
	    		$from        = "KMS admin";
	    		$subject     = "Impact Pro Service Order Updated";
	    		$mainMessage = "Impact Pro Service Order Updated, emailed with the PDF attachment";
	    		$fileatttype = "application/pdf";
	    		$fileattname = "ImpactProServiceOrder.pdf";
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