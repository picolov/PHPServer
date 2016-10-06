<?php
require 'baseController.php';

class OnSiteDailyJob extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('OnSiteDailyJob');
    	$this->setObjMap(array(
    			'wirelineEngineer' => 'Contact'
    	));
        parent::__construct();
    }
    
    /**
     * save DJR, and also update job status to active
     * @return multitype:unknown
     */
    function save_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
    	if (is_array($data)) {
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$jobInfo = $data['jobInfo'];
    		$jobModel = $this->db->selectCollection("Job");
    		$jobInfoModified = $jobModel->findOne(array('_id' => $jobInfo['_id']));
    		unset($data['jobInfo']);
    		$svo = $data['svo'];
    		unset($data['svo']);
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
    		$itemModel = $this->db->selectCollection($this->docName);
    		$itemModel->save($data);
    		// update Job Query
    		$jobQueryModel = $this->db->selectCollection("JobQuery");
    		$jobQueryModified = $jobQueryModel->findOne(array('_id' => $data['job']));
    		foreach ($data as $key => $value) {
    			if ($key == '_id') continue;
    			$jobQueryModified[$key] = $value;
    		}
    		// modify Integer attributes
    		if (array_key_exists('fromDate', $jobQueryModified)) { $jobQueryModified['fromDate'] = floor($jobQueryModified['fromDate']/86400)*86400; }
    		if (array_key_exists('toDate', $jobQueryModified)) { $jobQueryModified['fromDate'] = floor($jobQueryModified['fromDate']/86400)*86400; }
    		if (array_key_exists('waterDepth', $jobQueryModified)) { $jobQueryModified['waterDepth'] = 1 * $jobQueryModified['waterDepth']; }
    		if (array_key_exists('holeSize', $jobQueryModified)) { $jobQueryModified['holeSize'] = 1 * $jobQueryModified['holeSize']; }
    		if (array_key_exists('cableSize', $jobQueryModified)) { $jobQueryModified['cableSize'] = 1 * $jobQueryModified['cableSize']; }
    		if (array_key_exists('cableWeight', $jobQueryModified)) { $jobQueryModified['cableWeight'] = 1 * $jobQueryModified['cableWeight']; }
    		// count total revenue
    		$revenue = 0;
    		if (array_key_exists('totalFirst', $jobQueryModified)) {$revenue += $jobQueryModified['totalFirst'];}
    		if (array_key_exists('totalAdditional', $jobQueryModified)) {$revenue += $jobQueryModified['totalAdditional'];}
    		if (array_key_exists('totalRun', $jobQueryModified)) {$revenue += $jobQueryModified['totalRun'];}
    		if (array_key_exists('totalStandBy', $jobQueryModified)) {$revenue += $jobQueryModified['totalStandBy'];}
    		if (array_key_exists('totalTechnician', $jobQueryModified)) {$revenue += $jobQueryModified['totalTechnician'];}
    		if (array_key_exists('totalActivation', $jobQueryModified)) {$revenue += $jobQueryModified['totalActivation'];}
    		if (array_key_exists('totalLost', $jobQueryModified)) {$revenue += $jobQueryModified['totalLost'];}
    		if (array_key_exists('totalMileage', $jobQueryModified)) {$revenue += $jobQueryModified['totalMileage'];}
    		$jobQueryModified['status'] = array('$ref' => 'StatusJob', '$id' => '1');
    		if (array_key_exists('deviation', $data)) {
    			$jobQueryModified['deviation'] = 1 * $data['deviation'];
    		}
    		if (array_key_exists('temp', $data)) {
    			$jobQueryModified['temp'] = 1 * $data['temp'];
    		}
    		if (array_key_exists('wirelineEngineer', $data)) {
    			$jobQueryModified['wirelineEngineer'] = $data['wirelineEngineer'];
    		}
    		if (array_key_exists('td', $data)) {
    			$jobQueryModified['td'] = 1 * $data['td'];
    		}
    		if (array_key_exists('mudWt', $data)) {
    			$jobQueryModified['mudWt'] = 1 * $data['mudWt'];
    		}
    		if (array_key_exists('bhp', $data)) {
    			$jobQueryModified['bhp'] = 1 * $data['bhp'];
    		}
    		$jobQueryModified['revenue'] = $revenue;
    		
    		if (array_key_exists('status', $jobInfoModified) && array_key_exists('$id', $jobInfoModified['status']) && $jobInfoModified['status']['$id'] == 0) {
    			// if job is UPCOMING, then change to ACTIVE
    			$jobQueryModified['status'] = array('$ref' => 'StatusJob', '$id' => '1');
    			$jobInfo['status'] = array('$ref' => 'StatusJob', '$id' => '1');
    		}
    		/*
    		// if activity status contains "End Job" and job is UPCOMING or ACTIVE then update the job status to COMPLETED
    		if (array_key_exists('status', $jobInfoModified) && array_key_exists('$id', $jobInfoModified['status']) &&
    			($jobInfoModified['status']['$id'] == 0 || $jobInfoModified['status']['$id'] == 1)) {
    			if (array_key_exists('itemList', $data) && is_array($data['itemList'])) {
    				foreach ($data['itemList'] as $activity) {
    					if (array_key_exists('status', $activity) && $activity['status'] == 'End Job') {
    						$jobQueryModified['status'] = array('$ref' => 'StatusJob', '$id' => '2');
    						$jobInfo['status'] = array('$ref' => 'StatusJob', '$id' => '2');
    						break;
    					}
    				}
    			}
    		}
    		*/
    		$jobQueryModel->update(array("_id" => $data['job']), $jobQueryModified);
    		// update Job Info
    		
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
    		
    		// update Service Order
    		$svoModel = $this->db->selectCollection("ServiceOrder");
    		$svoModified = $svoModel->findOne(array('_id' => $svo['_id']));
    		$svoMap = array(
    				'billTo' => 'Company',
	    			'engineer' => 'profile',
	    			'coman' => 'Contact'
    		);
    		foreach ($svo as $key => $value) {
    			if (is_array($value) && array_key_exists('_id', $value)) {
    				if (array_key_exists($key, $svoMap)) {
    					$svo[$key] = array('$ref' => $svoMap[$key], '$id' => $value['_id']);
    				} else {
    					$svo[$key] = $value['_id'];
    				}
    			}
    		}
    		foreach ($svo as $key => $value) {
    			$svoModified[$key] = $value;
    		}
    		$svoModel->update(array("_id" => $svo['_id']), $svoModified);
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Update a DJR
     * @return multitype:unknown
     */
    function update_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
    	if (is_array($data) && array_key_exists('_id', $data)) {
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$jobInfo = $data['jobInfo'];
    		$jobModel = $this->db->selectCollection("Job");
    		$jobInfoModified = $jobModel->findOne(array('_id' => $jobInfo['_id']));
    		unset($data['jobInfo']);
    		$svo = $data['svo'];
    		unset($data['svo']);
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
    		
    		$itemModel = $this->db->selectCollection($this->docName);
    		$dataModified = $itemModel->findOne(array('_id' => $data['_id']));
    		foreach ($data as $key => $value) {
    			$dataModified[$key] = $value;
    		}
    		$itemModel->update(array("_id" => $data['_id']), $dataModified);
    		// update Job Query
    		$jobQueryModel = $this->db->selectCollection("JobQuery");
    		$jobQueryModified = $jobQueryModel->findOne(array('_id' => $data['job']));
    		foreach ($data as $key => $value) {
    			if ($key == '_id') continue;
    			$jobQueryModified[$key] = $value;
    		}
    		// modify Integer attributes
    		if (array_key_exists('fromDate', $jobQueryModified)) { $jobQueryModified['fromDate'] = floor($jobQueryModified['fromDate']/86400)*86400; }
    		if (array_key_exists('toDate', $jobQueryModified)) { $jobQueryModified['fromDate'] = floor($jobQueryModified['fromDate']/86400)*86400; }
    		if (array_key_exists('waterDepth', $jobQueryModified)) { $jobQueryModified['waterDepth'] = 1 * $jobQueryModified['waterDepth']; }
    		if (array_key_exists('holeSize', $jobQueryModified)) { $jobQueryModified['holeSize'] = 1 * $jobQueryModified['holeSize']; }
    		if (array_key_exists('cableSize', $jobQueryModified)) { $jobQueryModified['cableSize'] = 1 * $jobQueryModified['cableSize']; }
    		if (array_key_exists('cableWeight', $jobQueryModified)) { $jobQueryModified['cableWeight'] = 1 * $jobQueryModified['cableWeight']; }
    		// count total revenue
    		$revenue = 0;
    		if (array_key_exists('totalFirst', $jobQueryModified)) {$revenue += $jobQueryModified['totalFirst'];}
    		if (array_key_exists('totalAdditional', $jobQueryModified)) {$revenue += $jobQueryModified['totalAdditional'];}
    		if (array_key_exists('totalRun', $jobQueryModified)) {$revenue += $jobQueryModified['totalRun'];}
    		if (array_key_exists('totalStandBy', $jobQueryModified)) {$revenue += $jobQueryModified['totalStandBy'];}
    		if (array_key_exists('totalTechnician', $jobQueryModified)) {$revenue += $jobQueryModified['totalTechnician'];}
    		if (array_key_exists('totalActivation', $jobQueryModified)) {$revenue += $jobQueryModified['totalActivation'];}
    		if (array_key_exists('totalLost', $jobQueryModified)) {$revenue += $jobQueryModified['totalLost'];}
    		if (array_key_exists('totalMileage', $jobQueryModified)) {$revenue += $jobQueryModified['totalMileage'];}
    		if (array_key_exists('deviation', $data)) {
    			$jobQueryModified['deviation'] = 1 * $data['deviation'];
    		}
    		if (array_key_exists('temp', $data)) {
    			$jobQueryModified['temp'] = 1 * $data['temp'];
    		}
    		if (array_key_exists('wirelineEngineer', $data)) {
    			$jobQueryModified['wirelineEngineer'] = $data['wirelineEngineer'];
    		}
    		if (array_key_exists('td', $data)) {
    			$jobQueryModified['td'] = 1 * $data['td'];
    		}
    		if (array_key_exists('mudWt', $data)) {
    			$jobQueryModified['mudWt'] = 1 * $data['mudWt'];
    		}
    		if (array_key_exists('bhp', $data)) {
    			$jobQueryModified['bhp'] = 1 * $data['bhp'];
    		}
    		$jobQueryModified['revenue'] = $revenue;
    		
    		if (array_key_exists('status', $jobInfoModified) && array_key_exists('$id', $jobInfoModified['status']) && $jobInfoModified['status']['$id'] == 0) {
    			// if job is UPCOMING, then change to ACTIVE
    			$jobQueryModified['status'] = array('$ref' => 'StatusJob', '$id' => '1');
    			$jobInfo['status'] = array('$ref' => 'StatusJob', '$id' => '1');
    		}
    		/*
    		// if activity status contains "End Job" and job is UPCOMING or ACTIVE then update the job status to COMPLETED
    		if (array_key_exists('status', $jobInfoModified) && array_key_exists('$id', $jobInfoModified['status']) &&
    			($jobInfoModified['status']['$id'] == 0 || $jobInfoModified['status']['$id'] == 1)) {
    			if (array_key_exists('itemList', $data) && is_array($data['itemList'])) {
    				foreach ($data['itemList'] as $activity) {
    					if (array_key_exists('status', $activity) && $activity['status'] == 'End Job') {
    						$jobQueryModified['status'] = array('$ref' => 'StatusJob', '$id' => '2');
    						$jobInfo['status'] = array('$ref' => 'StatusJob', '$id' => '2');
    						break;
    					}
    				}
    			}
    		}
    		*/
    		$jobQueryModel->update(array("_id" => $data['job']), $jobQueryModified);
    		
    		// update Job Info
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
    		
    		// update Service Order
    		$svoModel = $this->db->selectCollection("ServiceOrder");
    		$svoModified = $svoModel->findOne(array('_id' => $svo['_id']));
    		$svoMap = array(
    				'billTo' => 'Company',
	    			'engineer' => 'profile',
	    			'coman' => 'Contact'
    		);
    		foreach ($svo as $key => $value) {
    			if (is_array($value) && array_key_exists('_id', $value)) {
    				if (array_key_exists($key, $svoMap)) {
    					$svo[$key] = array('$ref' => $svoMap[$key], '$id' => $value['_id']);
    				} else {
    					$svo[$key] = $value['_id'];
    				}
    			}
    		}
    		foreach ($svo as $key => $value) {
    			$svoModified[$key] = $value;
    		}
    		$svoModel->update(array("_id" => $svo['_id']), $svoModified);
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Get a List of daily job by Job id
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
}
?>