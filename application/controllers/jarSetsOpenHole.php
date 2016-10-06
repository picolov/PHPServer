<?php
require 'baseController.php';

class JarSetsOpenHole extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('JarSetsOpenHole');
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
    		// update Job Query
    		$jobQueryModel = $this->db->selectCollection("JobQuery");
    		$jobQueryModified = $jobQueryModel->findOne(array('_id' => $data['job']));
    		foreach ($data as $key => $value) {
    			if ($key == '_id') continue;
    			$jobQueryModified[$key] = $value;
    		}
    		// modify Integer attributes
    		if (array_key_exists('itemCount', $jobQueryModified)) { $jobQueryModified['itemCount'] = 1 * $jobQueryModified['itemCount']; }
    		for ($itemNum = 1; $itemNum <= $jobQueryModified['itemCount']; $itemNum++) {
    			if (array_key_exists('msp_' . $itemNum, $jobQueryModified)) { $jobQueryModified['msp_' . $itemNum] = 1 * $jobQueryModified['msp_' . $itemNum]; }
    			if (array_key_exists('wif_' . $itemNum, $jobQueryModified)) { $jobQueryModified['wif_' . $itemNum] = 1 * $jobQueryModified['wif_' . $itemNum]; }
    			if (array_key_exists('wia_' . $itemNum, $jobQueryModified)) { $jobQueryModified['wia_' . $itemNum] = 1 * $jobQueryModified['wia_' . $itemNum]; }
    			if (array_key_exists('wob_' . $itemNum, $jobQueryModified)) { $jobQueryModified['wob_' . $itemNum] = 1 * $jobQueryModified['wob_' . $itemNum]; }
    			if (array_key_exists('toolstringLength_' . $itemNum, $jobQueryModified)) { $jobQueryModified['toolstringLength_' . $itemNum] = 1 * $jobQueryModified['toolstringLength_' . $itemNum]; }
    			if (array_key_exists('actualJar_' . $itemNum, $jobQueryModified)) { $jobQueryModified['actualJar_' . $itemNum] = 1 * $jobQueryModified['actualJar_' . $itemNum]; }
    			if (array_key_exists('wp_' . $itemNum, $jobQueryModified)) { $jobQueryModified['wp_' . $itemNum] = 1 * $jobQueryModified['wp_' . $itemNum]; }
    			if (array_key_exists('fromDate_' . $itemNum, $jobQueryModified) && $jobQueryModified['fromDate_' . $itemNum] &&
    					array_key_exists('toDate_' . $itemNum, $jobQueryModified) && $jobQueryModified['toDate_' . $itemNum] &&
    					array_key_exists('start_' . $itemNum, $jobQueryModified) && $jobQueryModified['start_' . $itemNum] &&
    					array_key_exists('end_' . $itemNum, $jobQueryModified) && $jobQueryModified['end_' . $itemNum]
    			) {
    				$fromDate = new DateTime();
    				$fromDate->setTimestamp(1*$jobQueryModified['fromDate_' . $itemNum]);
    				$toDate = new DateTime();
    				$toDate->setTimestamp(1*$jobQueryModified['toDate_' . $itemNum]);
    				$startArr = explode(':', $jobQueryModified['start_' . $itemNum]);
    				$endArr = explode(':', $jobQueryModified['end_' . $itemNum]);
    				date_time_set($fromDate, intval($startArr[0]), intval($startArr[1]));
    				date_time_set($toDate, intval($endArr[0]), intval($endArr[1]));
    				$jobQueryModified['totalHour_' . $itemNum] = intval((date_timestamp_get($toDate) - date_timestamp_get($fromDate)) / 3600);
    			}
    		}
    		// count totalActivation = total of activation_x == 1 
    		$totalActivation = 0;
    		$totalImpactPro = 0;
    		for ($i = 1; $i <= $jobQueryModified['itemCount']; $i++) {
    			if (array_key_exists('activation_' . $i, $jobQueryModified) && $jobQueryModified['activation_' . $i] == '1') {
    				$totalActivation++;
    			}
    			if (array_key_exists('impactPro_' . $i, $jobQueryModified) && $jobQueryModified['impactPro_' . $i] == '1') {
    				$totalImpactPro++;
    			}
    		}
    		$jobQueryModified['totalCountActivation'] = $totalActivation;
    		$jobQueryModel->update(array("_id" => $data['job']), $jobQueryModified);
    		// update job reflecting impact pro changes
    		$jobModel = $this->db->selectCollection("Job");
    		if ($totalImpactPro > 0) {
    			$updatedImpactPro = array('$set' => array("impactPro" => true));
    			$jobModel->update(array("_id" => $data['job']), $updatedImpactPro);
    		} else {
    			$updatedImpactPro = array('$set' => array("impactPro" => true));
    			$jobModel->update(array("_id" => $data['job']), $updatedImpactPro);
    		}
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    function update_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
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
    			if ($key == '_id') continue;
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
    		if (array_key_exists('itemCount', $jobQueryModified)) { $jobQueryModified['itemCount'] = 1 * $jobQueryModified['itemCount']; }
    		for ($itemNum = 1; $itemNum <= $jobQueryModified['itemCount']; $itemNum++) {
    			if (array_key_exists('msp_' . $itemNum, $jobQueryModified)) { $jobQueryModified['msp_' . $itemNum] = 1 * $jobQueryModified['msp_' . $itemNum]; }
    			if (array_key_exists('wif_' . $itemNum, $jobQueryModified)) { $jobQueryModified['wif_' . $itemNum] = 1 * $jobQueryModified['wif_' . $itemNum]; }
    			if (array_key_exists('wia_' . $itemNum, $jobQueryModified)) { $jobQueryModified['wia_' . $itemNum] = 1 * $jobQueryModified['wia_' . $itemNum]; }
    			if (array_key_exists('wob_' . $itemNum, $jobQueryModified)) { $jobQueryModified['wob_' . $itemNum] = 1 * $jobQueryModified['wob_' . $itemNum]; }
    			if (array_key_exists('toolstringLength_' . $itemNum, $jobQueryModified)) { $jobQueryModified['toolstringLength_' . $itemNum] = 1 * $jobQueryModified['toolstringLength_' . $itemNum]; }
    			if (array_key_exists('actualJar_' . $itemNum, $jobQueryModified)) { $jobQueryModified['actualJar_' . $itemNum] = 1 * $jobQueryModified['actualJar_' . $itemNum]; }
    			if (array_key_exists('wp_' . $itemNum, $jobQueryModified)) { $jobQueryModified['wp_' . $itemNum] = 1 * $jobQueryModified['wp_' . $itemNum]; }
    			if (array_key_exists('fromDate_' . $itemNum, $jobQueryModified) && $jobQueryModified['fromDate_' . $itemNum] &&
    					array_key_exists('toDate_' . $itemNum, $jobQueryModified) && $jobQueryModified['toDate_' . $itemNum] &&
    					array_key_exists('start_' . $itemNum, $jobQueryModified) && $jobQueryModified['start_' . $itemNum] &&
    					array_key_exists('end_' . $itemNum, $jobQueryModified) && $jobQueryModified['end_' . $itemNum]
    			) {
    				$fromDate = new DateTime();
    				$fromDate->setTimestamp(1*$jobQueryModified['fromDate_' . $itemNum]);
    				$toDate = new DateTime();
    				$toDate->setTimestamp(1*$jobQueryModified['toDate_' . $itemNum]);
    				$startArr = explode(':', $jobQueryModified['start_' . $itemNum]);
    				$endArr = explode(':', $jobQueryModified['end_' . $itemNum]);
    				date_time_set($fromDate, intval($startArr[0]), intval($startArr[1]));
    				date_time_set($toDate, intval($endArr[0]), intval($endArr[1]));
    				$jobQueryModified['totalHour_' . $itemNum] = intval((date_timestamp_get($toDate) - date_timestamp_get($fromDate)) / 3600);
    			}
    		}
    		// count totalActivation = total of activation_x == 1 
    		$totalActivation = 0;
    		$totalImpactPro = 0;
    		for ($i = 1; $i <= $jobQueryModified['itemCount']; $i++) {
    			if (array_key_exists('activation_' . $i, $jobQueryModified) && $jobQueryModified['activation_' . $i] == '1') {
    				$totalActivation++;
    			}
    			if (array_key_exists('impactPro_' . $i, $jobQueryModified) && $jobQueryModified['impactPro_' . $i] == '1') {
    				$totalImpactPro++;
    			}
    		}
    		$jobQueryModified['totalCountActivation'] = $totalActivation;
    		$jobQueryModel->update(array("_id" => $data['job']), $jobQueryModified);
    		// update job reflecting impact pro changes
    		$jobModel = $this->db->selectCollection("Job");
    		if ($totalImpactPro > 0) {
    			$updatedImpactPro = array('$set' => array("impactPro" => true));
    			$jobModel->update(array("_id" => $data['job']), $updatedImpactPro);
    		} else {
    			$updatedImpactPro = array('$set' => array("impactPro" => false));
    			$jobModel->update(array("_id" => $data['job']), $updatedImpactPro);
    		}
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
}
?>