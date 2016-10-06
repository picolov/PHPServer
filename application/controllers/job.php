<?php
require 'baseController.php';

class Job extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('Job');
    	$this->setObjMap(array(
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
    	));
        parent::__construct();
    }
    
    /**
     * Get List Job with Preset Approval detail
     * @return multitype:unknown
     */
    function listWithPre_get() {
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$itemCursor = $itemModel->find();
    	foreach ($itemCursor as $item) {
    		foreach($item as $key => $value) {
    			$prop = $item[$key];
    			if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
    				$innerItemModel = $this->db->selectCollection($prop['$ref']);
    				$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
    				$item[$key] = $innerItem;
    			}
    		}
    		// get Preset Approval for this job
    		$presetModel = $this->db->selectCollection('JarSetsOpenHole');
    		$presetCursor = $presetModel->find(array("job" => $item['_id']));
    		$presetCursor->limit(1);
    		$preset = null;
    		foreach ($presetCursor as $presetItem) {
    			$preset = $presetItem;
    		}
    		$item['preset'] = $preset;
    		$data[] = $item;
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Get List Job with Service Order and DailyJob detail
     * @return multitype:unknown
     */
    function listWithSvoDjr_get() {
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$itemCursor = $itemModel->find();
    	foreach ($itemCursor as $item) {
    		foreach($item as $key => $value) {
    			$prop = $item[$key];
    			if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
    				$innerItemModel = $this->db->selectCollection($prop['$ref']);
    				$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
    				$item[$key] = $innerItem;
    			}
    		}
    		// get service order for this job
    		$serviceModel = $this->db->selectCollection('ServiceOrder');
    		$serviceCursor = $serviceModel->find(array("job" => $item['_id']));
    		$serviceCursor->limit(1);
    		$service = null;
    		foreach ($serviceCursor as $serviceItem) {
    			$service = $serviceItem;
    		}
    		$item['serviceOrder'] = $service;
    		// get DJR for this job
    		$djrModel = $this->db->selectCollection('OnSiteDailyJob');
    		$djrCursor = $djrModel->find(array("job" => $item['_id']));
    		$djrCursor->limit(1);
    		$djr = null;
    		foreach ($djrCursor as $djrItem) {
    			$djr = $djrItem;
    		}
    		$item['dailyJob'] = $djr;
    		$data[] = $item;
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Get List Job with Preset, Service Order and DailyJob detail
     * @return multitype:unknown
     */
    function listWithPreSvoDjr_get() {
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$itemCursor = $itemModel->find();
    	foreach ($itemCursor as $item) {
    		foreach($item as $key => $value) {
    			$prop = $item[$key];
    			if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
    				$innerItemModel = $this->db->selectCollection($prop['$ref']);
    				$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
    				$item[$key] = $innerItem;
    			}
    		}
    		// get Preset Approval for this job
    		$presetModel = $this->db->selectCollection('JarSetsOpenHole');
    		$presetCursor = $presetModel->find(array("job" => $item['_id']));
    		$presetCursor->limit(1);
    		$preset = null;
    		foreach ($presetCursor as $presetItem) {
    			$preset = $presetItem;
    		}
    		$item['preset'] = $preset;
    		// get service order for this job
    		$serviceModel = $this->db->selectCollection('ServiceOrder');
    		$serviceCursor = $serviceModel->find(array("job" => $item['_id']));
    		$serviceCursor->limit(1);
    		$service = null;
    		foreach ($serviceCursor as $serviceItem) {
    			$service = $serviceItem;
    		}
    		$item['serviceOrder'] = $service;
    		// get DJR for this job
    		$djrModel = $this->db->selectCollection('OnSiteDailyJob');
    		$djrCursor = $djrModel->find(array("job" => $item['_id']));
    		$djrCursor->limit(1);
    		$djr = null;
    		foreach ($djrCursor as $djrItem) {
    			$djr = $djrItem;
    		}
    		$item['dailyJob'] = $djr;
    		$data[] = $item;
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Override Get a List of Month for This Year startDate(Potential Date)
     * @return multitype:unknown
     */
    function listMonthThisYear_get() {
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$year = date('Y');
    	$monthList = array();
    	for ($monthNum = 1; $monthNum <= 12; $monthNum++) {
    		$month_start = strtotime('first day of this month 00:00:00', mktime(0, 0, 0, $monthNum, 2, $year));
    		$month_end = strtotime('last day of this month 00:00:00', mktime(0, 0, 0, $monthNum, 2, $year));
    		// for upcoming and cancelled
	    	$itemCursor = $itemModel->find(array("startDate" => array('$gte' => $month_start, '$lte' => $month_end)));
	    	$length = $itemCursor->count();
	    	if ($length > 0) {
	    		$monthList[] = $monthNum;
	    	} else {
		    	// for completed , check DJR toDate
	    		$djrModel = $this->db->selectCollection("OnSiteDailyJob");
	    		$djrCursor = $djrModel->find(array("toDate" => array('$gte' => $month_start, '$lte' => $month_end)));
	    		$length = $djrCursor->count();
	    		if ($length > 0) {
	    			$monthList[] = $monthNum;
	    		} else {
	    			// for completed check DJR fromDate
		    		$djrCursor = $djrModel->find(array("fromDate" => array('$gte' => $month_start, '$lte' => $month_end)));
		    		$length = $djrCursor->count();
		    		if ($length > 0) {
		    			$monthList[] = $monthNum;
		    		}
	    		}
	    	}
	    	
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data, 'currentMonth' => date('n'), 'monthList' => $monthList];
    	return $output;
    }
    
    /**
     * Override Get a List of Job for This Month startDate(Potential Date)
     * @return multitype:unknown
     */
    function listThisMonth_get() {
    	$year = date('Y');
    	$month = $this->get('month');
    	if ($month && strlen($month) > 0) {
    		$month_start = strtotime('first day of this month 00:00:00', mktime(0, 0, 0, $month, 2, $year));
    		$month_end = strtotime('last day of this month 00:00:00', mktime(0, 0, 0, $month, 2, $year));
    	} else {
    		$month_start = strtotime('first day of this month 00:00:00', time());
			$month_end = strtotime('last day of this month 00:00:00', time());
    	}
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	// find start date only for upcoming and cancelled job
    	$itemCursor = $itemModel->find(array("startDate" => array('$gte' => $month_start, '$lte' => $month_end), '$or' => array(
  			array('status.$id' => '0'),
  			array('status.$id' => '3')
		)));
    	$itemCursor->sort(array('startDate' => 1));
    	$itemCursor->limit(200);
    	foreach ($itemCursor as $item) {
    		foreach($item as $key => $value) {
    			$prop = $item[$key];
    			if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
    				$innerItemModel = $this->db->selectCollection($prop['$ref']);
    				$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
    				$item[$key] = $innerItem;
    			}
    		}
    		// get service order for this job
    		$serviceModel = $this->db->selectCollection('ServiceOrder');
    		$serviceCursor = $serviceModel->find(array("job" => $item['_id']));
    		$serviceCursor->limit(1);
    		$service = null;
    		foreach ($serviceCursor as $serviceItem) {
    			$service = $serviceItem;
    		}
    		$item['serviceOrder'] = $service;
    		// get DJR for this job
    		$djrModel = $this->db->selectCollection('OnSiteDailyJob');
    		$djrCursor = $djrModel->find(array("job" => $item['_id']));
    		$djrCursor->limit(1);
    		$djr = null;
    		foreach ($djrCursor as $djrItem) {
    			$djr = $djrItem;
    		}
    		$item['dailyJob'] = $djr;
    		$data[] = $item;
    	}
    	
    	// get all completed job for the specified month
    	$djrModel = $this->db->selectCollection('OnSiteDailyJob');
    	$djrCursor = $djrModel->find(array('$or' => array(
    			array("fromDate" => array('$gte' => $month_start, '$lte' => $month_end)),
    			array("toDate" => array('$gte' => $month_start, '$lte' => $month_end))
    	)));
    	$djrCursor->limit(200);
    	foreach ($djrCursor as $djr) {
    		$jobId = $djr['job'];
    		$item = $itemModel->findOne(array('_id' => $jobId, 'status.$id' => '2'));
    		if ($item) {
    			// check again if this DJR have fromDate and toDate but toDate is not in right month then 
    			// skip from the valid list for this month
    			if (array_key_exists('toDate', $djr) && ($djr['toDate'] < $month_start || $djr['toDate'] > $month_end)) {
    				continue;
    			}
	    		foreach($item as $key => $value) {
	    			$prop = $item[$key];
	    			if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
	    				$innerItemModel = $this->db->selectCollection($prop['$ref']);
	    				$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
	    				$item[$key] = $innerItem;
	    			}
	    		}
	    		// get service order for this job
	    		$serviceModel = $this->db->selectCollection('ServiceOrder');
	    		$serviceCursor = $serviceModel->find(array("job" => $item['_id']));
	    		$serviceCursor->limit(1);
	    		$service = null;
	    		foreach ($serviceCursor as $serviceItem) {
	    			$service = $serviceItem;
	    		}
	    		$item['serviceOrder'] = $service;
	    		// get DJR for this job
	    		$djrModel = $this->db->selectCollection('OnSiteDailyJob');
	    		$djrCursor = $djrModel->find(array("job" => $item['_id']));
	    		$djrCursor->limit(1);
	    		$djr = null;
	    		foreach ($djrCursor as $djrItem) {
	    			$djr = $djrItem;
	    		}
	    		$item['dailyJob'] = $djr;
	    		$data[] = $item;
    		}
    	}
    	
    	if (!$this->get('month') || $this->get('month') == date('n')) {
    		// get all active job if no specified month or specified month is current server month
    		$itemCursor = $itemModel->find(array('status.$id' => '1'));
    		$itemCursor->sort(array('uid' => 1));
    		$itemCursor->limit(200);
    		foreach ($itemCursor as $item) {
    			foreach($item as $key => $value) {
    				$prop = $item[$key];
    				if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
    					$innerItemModel = $this->db->selectCollection($prop['$ref']);
    					$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
    					$item[$key] = $innerItem;
    				}
    			}
    			// get service order for this job
    			$serviceModel = $this->db->selectCollection('ServiceOrder');
    			$serviceCursor = $serviceModel->find(array("job" => $item['_id']));
    			$serviceCursor->limit(1);
    			$service = null;
    			foreach ($serviceCursor as $serviceItem) {
    				$service = $serviceItem;
    			}
    			$item['serviceOrder'] = $service;
    			// get DJR for this job
    			$djrModel = $this->db->selectCollection('OnSiteDailyJob');
    			$djrCursor = $djrModel->find(array("job" => $item['_id']));
    			$djrCursor->limit(1);
    			$djr = null;
    			foreach ($djrCursor as $djrItem) {
    				$djr = $djrItem;
    			}
    			$item['dailyJob'] = $djr;
    			$data[] = $item;
    		}
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data, 'currentMonth' => date('n')];
    	return $output;
    }
    
    /**
     * Override Get a List of Job for User
     * @return multitype:unknown
     */
    function listByUser_get() {
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$itemCursor = $itemModel->find(array("user" => $this->get('_id')));
    	$itemCursor->sort(array('time' => -1));
    	$itemCursor->limit(200);
    	foreach ($itemCursor as $item) {
    		$data[] = $item;
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Override Get a List of job for Map filter
     * @return multitype:unknown
     */
    function listByMapFilter_get() {
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$mapFilter = $this->get('mapFilter');
    	$mapFilter = explode('~', $mapFilter);
    	$query = array();
    	if (strlen($mapFilter[0]) > 0) {
    		$query['$or'] = array();
    		$query['$or'][] = array('technician' => "" . $mapFilter[0]);
    		$query['$or'][] = array('technician2' => "" . $mapFilter[0]);
    	}
    	if (strlen($mapFilter[1]) > 0) {
    		$query['status.$id'] = "" . $mapFilter[1];
    	}
    	if (strlen($mapFilter[2]) > 0) {
    		$query['oilCompany.$id'] = "" . $mapFilter[2];
    	}
    	$itemCursor = $itemModel->find($query);
    	$itemCursor->sort(array('time' => -1));
    	$itemCursor->limit(200);
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
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data, 'query' => $query, 'mapFilter' => $mapFilter];
    	return $output;
    }
    
    /**
     * Create a Class Object Bind it with User in user field in Database
     * @return multitype:unknown
     */
    function saveMine_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	if (is_array($data)) {
    		if (array_key_exists('uid', $data)) {
    			$itemCursor = $itemModel->find(array("uid" => $data['uid']));
    			if ($itemCursor->count() > 0) {
    				$output = ['status' => 20002, 'message' => 'Job Uid is not unique', 'data' => $data];
    			} else if (array_key_exists('status', $data) && $data['status']['_id'] == '2') {
    				$output = ['status' => 20004, 'message' => 'Cannot Complete job without Daily Job Report and Preset Approval created', 'data' => $data];
    			} else {
		    		if (!array_key_exists('_id', $data)) {
		    			$data['_id'] = UUID::v4();
		    		}
		    		foreach ($data as $key => $value) {
		    			if (is_array($value) && array_key_exists('_id', $value)) {
		    				if (array_key_exists($key, $this->objMap)) {
		    					$data[$key] = array('$ref' => $this->objMap[$key], '$id' => $value['_id']);
		    					if ($key == 'wirelineEngineer') { // if wireline engineer also save phone and email to another field
		    						$contactModel = $this->db->selectCollection("Contact");
		    						$contact = $contactModel->findOne(array('_id' => $value['_id']));
		    						if ($contact && array_key_exists('phone', $contact)) {
		    							$data['engineerPhone'] = $contact['phone'];
		    						}
		    						if ($contact && array_key_exists('email', $contact)) {
		    							$data['engineerEmail'] = $contact['email'];
		    						}
		    					}
		    				} else {
		    					$data[$key] = $value['_id'];
		    				}
		    			}
		    		}
		    		$data['createdBy'] = $this->token['user']['username'];
		    		$data['createdTime'] = time();
		    		$data['user'] = $this->token['user']['_id'];
		    		
		    		$itemModel->save($data);
		    		// save Job Query
		    		$jobQueryModel = $this->db->selectCollection("JobQuery");
		    		if (array_key_exists('temp', $data)) { $data['temp'] = 1 * $data['temp']; }
		    		if (array_key_exists('deviation', $data)) { $data['deviation'] = 1 * $data['deviation']; }
		    		
		    		$jobQueryModel->save($data);
		    		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    			}
    		} else {
    			$output = ['status' => 20001, 'message' => 'no uid found', 'data' => $data];
    		}
    	}
    	
    	return $output;
    }
    
    /**
     * Update a Class Object in Database
     * @return multitype:unknown
     */
    function update_post() {
    	$debug = [];
    	// Continue Real Operation
    	$data = $this->_post_args;
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	if (is_array($data) && array_key_exists('_id', $data)) {
    		$dataModified = $itemModel->findOne(array('_id' => $data['_id']));
    		if (array_key_exists('uid', $data)) {
    			$itemCursor = $itemModel->find(array("uid" => $data['uid']));
    			if ($dataModified["uid"] != $data['uid'] && $itemCursor->count() > 0) {
    				$output = ['status' => 20002, 'message' => 'Job Uid is not unique', 'data' => $data];
    			} else {
    				$errorMessage = "";
    				// check condition required when changing job status to completed (status._id=2)
    				if (array_key_exists('status', $data) && $data['status']['_id'] == '2') {
    					if (!array_key_exists('wellType', $data) || $data['wellType'] == null) {
    						$errorMessage .= "Well is not yet filled\n";
    					}
    					if (!array_key_exists('country', $data) || $data['country'] == null) {
    						$errorMessage .= "Country is not yet filled\n";
    					}
    					if (!array_key_exists('latitude', $data) || $data['latitude'] == null) {
    						$errorMessage .= "Latitude is not yet filled\n";
    					}
    					if (!array_key_exists('longitude', $data) || $data['longitude'] == null) {
    						$errorMessage .= "Longitude is not yet filled\n";
    					}
    					// check DJR
    					$djrModel = $this->db->selectCollection('OnSiteDailyJob');
    					$djr = $djrModel->findOne(array("job" => $data['_id']));
    					if ($djr) {
	    					if (!array_key_exists('fromDate', $djr) || $djr['fromDate'] == null) {
	    						$errorMessage .= "Start Date on Daily Job Report is not yet filled\n";
	    					}
	    					if (!array_key_exists('toDate', $djr) || $djr['toDate'] == null) {
	    						$errorMessage .= "End Date on Daily Job Report is not yet filled\n";
	    					}
    					} else {
    						$errorMessage .= "Daily Job report not yet created\n";
    					}
    					// check Preset Approval
    					$presetModel = $this->db->selectCollection('JarSetsOpenHole');
    					$preset = $presetModel->findOne(array("job" => $data['_id']));
    					if ($preset) {
	    					if (array_key_exists('itemCount', $preset) && $preset['itemCount'] != null) {
	    						$itemCount = $preset['itemCount'];
	    						for ($i = 1; $i <= $itemCount; $i++) {
	    							$runErrorMessage = "";
	    							if (!array_key_exists('run_' . $i, $preset) || $preset['run_' . $i] == null) {
			    						$runErrorMessage .= "Run Number on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('fromDate_' . $i, $preset) || $preset['fromDate_' . $i] == null) {
			    						$runErrorMessage .= "Start Date on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('toDate_' . $i, $preset) || $preset['toDate_' . $i] == null) {
			    						$runErrorMessage .= "End Date on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('start_' . $i, $preset) || $preset['start_' . $i] == null) {
			    						$runErrorMessage .= "Start Time on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('end_' . $i, $preset) || $preset['end_' . $i] == null) {
			    						$runErrorMessage .= "End Time on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('wif_' . $i, $preset) || $preset['wif_' . $i] == null) {
			    						$runErrorMessage .= "WIF on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('msp_' . $i, $preset) || $preset['msp_' . $i] == null) {
			    						$runErrorMessage .= "MSP on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('wob_' . $i, $preset) || $preset['wob_' . $i] == null) {
			    						$runErrorMessage .= "WOB on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('wp_' . $i, $preset) || $preset['wp_' . $i] == null) {
			    						$runErrorMessage .= "WP on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('wia_' . $i, $preset) || $preset['wia_' . $i] == null) {
			    						$runErrorMessage .= "WIA on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('pullHead_' . $i, $preset) || $preset['pullHead_' . $i] == null) {
			    						$runErrorMessage .= "Max Pull at head on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('jarSerial_' . $i, $preset) || $preset['jarSerial_' . $i] == null) {
			    						$runErrorMessage .= "Jar Serial # on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('toolstringLength_' . $i, $preset) || $preset['toolstringLength_' . $i] == null) {
			    						$runErrorMessage .= "Toolstring Length on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('activation_' . $i, $preset) || $preset['activation_' . $i] == null) {
			    						$runErrorMessage .= "Activation on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('toolsFreed_' . $i, $preset) || $preset['toolsFreed_' . $i] == null) {
			    						$runErrorMessage .= "Tools Freed on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('fishing_' . $i, $preset) || $preset['fishing_' . $i] == null) {
			    						$runErrorMessage .= "Fishing on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('impactPro_' . $i, $preset) || $preset['impactPro_' . $i] == null) {
			    						$runErrorMessage .= "Impact Pro on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('impactProSlotSize_' . $i, $preset) || $preset['impactProSlotSize_' . $i] == null) {
			    						$runErrorMessage .= "Impact Pro Slot Size on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (!array_key_exists('actualJar_' . $i, $preset) || $preset['actualJar_' . $i] == null) {
			    						$runErrorMessage .= "Preset actually used on Jar on Run- " . $i . " on Preset Approval is not yet filled\n";
			    					}
			    					if (strlen($runErrorMessage) > 0) {
			    						$errorMessage .= "Run- " . $i . " data on Preset Approval is not yet complete\n";
			    					}
	    						}
	    					}
    					} else {
    						$errorMessage .= "Preset Approval is not yet created\n";
    					}
    					if (strlen($errorMessage) > 0) {
    						$errorMessage = "Cannot complete Job before this required field filled : \n". $errorMessage;
    					}
    				}
    				if (strlen($errorMessage) == 0) {
			    		foreach ($data as $key => $value) {
			    			if (is_array($value) && array_key_exists('_id', $value)) {
			    				if (array_key_exists($key, $this->objMap)) {
			    					$data[$key] = array('$ref' => $this->objMap[$key], '$id' => $value['_id']);
			    					$debug[] = $key;
			    					if ($key == 'wirelineEngineer') { // if wireline engineer also save phone and email to another field
			    						$contactModel = $this->db->selectCollection("Contact");
			    						$contact = $contactModel->findOne(array('_id' => $value['_id']));
			    						if ($contact && array_key_exists('phone', $contact)) {
			    							$data['engineerPhone'] = $contact['phone'];
			    						}
			    						if ($contact && array_key_exists('email', $contact)) {
			    							$data['engineerEmail'] = $contact['email'];
			    						}
			    					}
			    				} else {
			    					$data[$key] = $value['_id'];
			    				}
			    			}
			    		}
			    		$data['updatedBy'] = $this->token['user']['username'];
			    		$data['updatedTime'] = time();
			    		foreach ($data as $key => $value) {
			    			$dataModified[$key] = $value;
			    		}
			    		$itemModel->update(array("_id" => $data['_id']), $dataModified);
			    		// update Job Query
			    		$jobQueryModel = $this->db->selectCollection("JobQuery");
			    		$jobQueryModified = $jobQueryModel->findOne(array('_id' => $data['_id']));
			    		foreach ($data as $key => $value) {
			    			if ($key == '_id') continue;
			    			$jobQueryModified[$key] = $value;
			    		}
			    		if (array_key_exists('temp', $jobQueryModified)) { $jobQueryModified['temp'] = 1 * $jobQueryModified['temp']; }
			    		if (array_key_exists('deviation', $jobQueryModified)) { $jobQueryModified['deviation'] = 1 * $jobQueryModified['deviation']; }
			    		$jobQueryModel->update(array("_id" => $data['_id']), $jobQueryModified);
			    		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data, 'debug' => $debug];
		    		} else {
		    			$output = ['status' => 20005, 'message' => $errorMessage, 'data' => $data];
		    		}
    			}
    		} else {
    			$output = ['status' => 20001, 'message' => 'no uid found', 'data' => $data];
    		}
    	} else {
    		$output = ['status' => 20003, 'message' => 'job id not found', 'data' => $data];
    	}
    	
    	return $output;
    }
    
    /**
     * Delete Job and all of its related model
     * @return multitype:unknown
     */
    function delete_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
    	$deletedData = [];
    	if (array_key_exists('_id', $data)) {
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$itemModel = $this->db->selectCollection($this->docName);
    		$deletedData = $itemModel->findOne(array('_id' => $data['_id']));
    		$itemModel->remove(array('_id' => $data['_id']));
    		// JobQuery
    		$relatedModel = $this->db->selectCollection('JobQuery');
    		$relatedModel->remove(array('_id' => $data['_id']));
    		// JarSetsImpactOrder
    		$relatedModel = $this->db->selectCollection('JarSetsImpactOrder');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// JarSetsOpenHole
    		$relatedModel = $this->db->selectCollection('JarSetsOpenHole');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OnSiteAlert
    		$relatedModel = $this->db->selectCollection('OnSiteAlert');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OnSiteCustomer
    		$relatedModel = $this->db->selectCollection('OnSiteCustomer');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OnSiteDailyJob
    		$relatedModel = $this->db->selectCollection('OnSiteDailyJob');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OnSiteJSEA
    		$relatedModel = $this->db->selectCollection('OnSiteJSEA');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OnSiteSummaryJob
    		$relatedModel = $this->db->selectCollection('OnSiteSummaryJob');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OnSiteTechData
    		$relatedModel = $this->db->selectCollection('OnSiteTechData');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OutgoingToolsCasedInspection
    		$relatedModel = $this->db->selectCollection('OutgoingToolsCasedInspection');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OutgoingToolsCasedLeakBaker
    		$relatedModel = $this->db->selectCollection('OutgoingToolsCasedLeakBaker');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OutgoingToolsExpense
    		$relatedModel = $this->db->selectCollection('OutgoingToolsExpense');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OutgoingToolsInOut
    		$relatedModel = $this->db->selectCollection('OutgoingToolsInOut');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OutgoingToolsInspection
    		$relatedModel = $this->db->selectCollection('OutgoingToolsInspection');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OutgoingToolsLeakBaker
    		$relatedModel = $this->db->selectCollection('OutgoingToolsLeakBaker');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OutgoingToolsLeakBerger
    		$relatedModel = $this->db->selectCollection('OutgoingToolsLeakBerger');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OutgoingToolsLeakBurton
    		$relatedModel = $this->db->selectCollection('OutgoingToolsLeakBurton');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OutgoingToolsOpenInspection
    		$relatedModel = $this->db->selectCollection('OutgoingToolsOpenInspection');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OutgoingToolsOpenLeakBaker
    		$relatedModel = $this->db->selectCollection('OutgoingToolsOpenLeakBaker');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// OutgoingToolsTransfer
    		$relatedModel = $this->db->selectCollection('OutgoingToolsTransfer');
    		$relatedModel->remove(array('job' => $data['_id']));
    		// ServiceOrder
    		$relatedModel = $this->db->selectCollection('ServiceOrder');
    		$relatedModel->remove(array('job' => $data['_id']));
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $deletedData];
    	return $output;
    }
    
    /**
     * Delete uploaded file on Job
     * @return multitype:unknown
     */
    function deleteFile_post() {
    	// Continue Real Operation
    	$data = $this->_post_args;
    	$updatedData = [];
    	if (array_key_exists('_id', $data) && array_key_exists('file', $data)) {
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$itemModel = $this->db->selectCollection($this->docName);
    		$updatedData = $itemModel->findOne(array('_id' => $data['_id']));
    		if (array_key_exists('uploadedFile', $updatedData) && $updatedData['uploadedFile']) {
    			for ($i = 0; $i < count($updatedData['uploadedFile']); $i++) {
    				if ($data["file"]["url"] == $updatedData['uploadedFile'][$i]["url"]) {
    					array_splice($updatedData['uploadedFile'], $i, 1);
    				}
    			}
    			$itemModel->update(array("_id" => $updatedData['_id']), $updatedData);
    			
    		} else {
    			
    		}
    		
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $updatedData];
    	return $output;
    }
    
}
?>