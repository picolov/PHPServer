<?php
require 'baseController.php';

class ServiceOrder extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('ServiceOrder');
    	$this->setObjMap(array(
    			'billTo' => 'Company',
    			'engineer' => 'profile',
    			'coman' => 'Contact'
    	));
        parent::__construct();
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
    
    function save_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
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
    		// update Job Query
    		$jobQueryModel = $this->db->selectCollection("JobQuery");
    		$jobQueryModified = $jobQueryModel->findOne(array('_id' => $data['job']));
    		foreach ($data as $key => $value) {
    			if ($key == '_id') continue;
    			$jobQueryModified[$key] = $value;
    		}
    		if (array_key_exists('bhp', $jobQueryModified)) { $jobQueryModified['bhp'] = 1 * $jobQueryModified['bhp']; }
    		if (array_key_exists('mudWt', $jobQueryModified)) { $jobQueryModified['mudWt'] = 1 * $jobQueryModified['mudWt']; }
    		if (array_key_exists('td', $jobQueryModified)) { $jobQueryModified['td'] = 1 * $jobQueryModified['td']; }
    		$jobQueryModel->update(array("_id" => $data['job']), $jobQueryModified);
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * update service order and djr
     * @return multitype:unknown
     */
    function update_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
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
    		// update Job Query
    		$jobQueryModel = $this->db->selectCollection("JobQuery");
    		$jobQueryModified = $jobQueryModel->findOne(array('_id' => $data['job']));
    		foreach ($data as $key => $value) {
    			if ($key == '_id') continue;
    			$jobQueryModified[$key] = $value;
    		}
    		if (array_key_exists('bhp', $jobQueryModified)) { $jobQueryModified['bhp'] = 1 * $jobQueryModified['bhp']; }
    		if (array_key_exists('mudWt', $jobQueryModified)) { $jobQueryModified['mudWt'] = 1 * $jobQueryModified['mudWt']; }
    		if (array_key_exists('td', $jobQueryModified)) { $jobQueryModified['td'] = 1 * $jobQueryModified['td']; }
    		$jobQueryModel->update(array("_id" => $data['job']), $jobQueryModified);
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
}
?>