<?php
require APPPATH . '/libraries/REST_Controller.php';
//require 'baseController.php';

require APPPATH . '/libraries/PHPExcel.php';
require APPPATH . '/libraries/PHPExcel/Writer/Excel2007.php';

class JobQuery extends REST_Controller {
	protected $docName;
	protected $token;
	protected $objMap = array();
	
	public function __construct()
    {
    	$this->docName = 'JobQuery';
    	$this->objMap = array(
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
        parent::__construct();
    }
    
    /**
     * Query based by searchParam
     * @return multitype:unknown
     */
    function list_get() {
    	// debug variable
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	// post filter for removing Run that is not match with the query
    	$runFilters = array();
    	// search param
    	$query = array();
    	for ($i = 1; $i <= 5; $i++) {
	    	if (
	    			($this->get('field' . $i) || $this->get('field' . $i) === '0') && 
	    			($this->get('operator' . $i) || $this->get('operator' . $i) === '0')
	    		) {
	    		$field = $this->get('field' . $i);
	    		$operator = $this->get('operator' . $i);
	    		$paramLeft = $this->get('paramLeft' . $i);
	    		$paramRight = $this->get('paramRight' . $i);
	    		/*
	    		array('$or' => array(
	    			array('brand' => 'anti'),
	    			array('aa' => 'bb')
	    			)
	    		);
	    		*/
	    		$unitQuery = array();
	    		if ($operator['id'] == 1) {
	    			if ($field['type'] == 'int') {
	    				$unitQuery = 1 * $paramLeft;
	    			} else if ($field['type'] == 'date') {
	    				$unitQuery = floor($paramLeft/86400)*86400;
	    			} else if ($field['type'] == 'WellCondition') {
	    				// do nothing, the process is at bottom
	    			} else if ($field['type'] == "JarSerialJob") { // same process like string, just need to duplicate for 4 serial field
    					$unitQuery = new MongoRegex("/" . preg_quote($paramLeft) . "/i");
    				} else if ($field['type'] == "LatLon") {
    					// do nothing, the process is at bottom
    				} else {
		    			if (is_array($paramLeft)) {
		    				if (array_key_exists('_id', $paramLeft)) { // equal to Model
		    					$unitQuery = "" . $paramLeft['_id'];
		    				} else { // equals to array
		    					$unitQuery = array('$in' => $paramLeft);
		    				}
		    			} else { // equals to string or int
		    				$unitQuery = new MongoRegex("/" . preg_quote($paramLeft) . "/i");
		    			}
	    			}
	    		} else if ($operator['id'] == 2) { // <
	    			if ($field['type'] == 'int') {
	    				$unitQuery = array('$lt' => 1 * $paramLeft);
	    			} else if ($field['type'] == 'date') {
	    				$unitQuery = array('$lt' => floor($paramLeft/86400)*86400);
	    			}
	    		} else if ($operator['id'] == 3) { // <=
	    			if ($field['type'] == 'int') {
	    				$unitQuery = array('$lte' => 1 * $paramLeft);
	    			} else if ($field['type'] == 'date') {
	    				$unitQuery = array('$lte' => floor($paramLeft/86400)*86400);
	    			}
	    		} else if ($operator['id'] == 4) { // >
	    			if ($field['type'] == 'int') {
	    				$unitQuery = array('$gt' => 1 * $paramLeft);
	    			} else if ($field['type'] == 'date') {
	    				$unitQuery = array('$gt' => floor($paramLeft/86400)*86400);
	    			}
	    		} else if ($operator['id'] == 5) { // >=
	    			if ($field['type'] == 'int') {
	    				$unitQuery = array('$gte' => 1 * $paramLeft);
	    			} else if ($field['type'] == 'date') {
	    				$unitQuery = array('$gte' => floor($paramLeft/86400)*86400);
	    			}
	    		} else if ($operator['id'] == 6 && $paramRight) { // between
	    			if ($field['type'] == 'int') {
	    				$unitQuery = array('$gte' => 1 * $paramLeft, '$lte' => 1 * $paramRight);
	    			} elseif ($field['type'] == 'date') {
	    				$unitQuery = array('$gte' => floor($paramLeft/86400)*86400, '$lte' => floor($paramRight/86400)*86400);
	    			}
	    		}
	    		if ($field['type'] == "WellCondition") {
	    			$paramLeft1 = $this->get('paramLeft' . $i . '_1');
	    			$paramLeft2 = $this->get('paramLeft' . $i . '_2');
	    			$paramLeft3 = $this->get('paramLeft' . $i . '_3');
	    			$paramLeft4 = $this->get('paramLeft' . $i . '_4');
	    			$paramLeft5 = $this->get('paramLeft' . $i . '_5');
	    			if ($paramLeft1 && $paramLeft1 == 'true') { $query['isCO2'] = true; }
	    			if ($paramLeft2 && $paramLeft2 == 'true') { $query['isH2S'] = true; }
	    			if ($paramLeft3 && $paramLeft3 == 'true') { $query['isHighTemp'] = true; }
	    			if ($paramLeft4 && $paramLeft4 == 'true') { $query['isHighPress'] = true; }
	    			if ($paramLeft5 && $paramLeft5 == 'true') { $query['isOther'] = true; }
	    		} else if ($field['type'] == "JarSerialJob") {
	    			$queryOr = array();
	    			$queryOr['$or'] = array();
	    			for ($serialNum = 1; $serialNum <= 4; $serialNum++) {
	    				$queryOr['$or'][] = array($field['variable'] . $serialNum => $unitQuery);
	    			}
	    			if (!array_key_exists('$and', $query)) {
	    				$query['$and'] = array();
	    			}
	    			$query['$and'][] = $queryOr;
	    		} else if ($field['type'] == "JarType") {
	    			$queryOr = array();
	    			$queryOr['$or'] = array();
	    			for ($toolNum = 1; $toolNum <= 15; $toolNum++) {
	    				$queryOr['$or'][] = array($field['variable'] . $toolNum => $unitQuery);
	    			}
	    			if (!array_key_exists('$and', $query)) {
	    				$query['$and'] = array();
	    			}
	    			$query['$and'][] = $queryOr;
	    		} else if ($field['type'] == "Technician") {
	    			$queryOr = array();
	    			$queryOr['$or'] = array();
	    			$queryOr['$or'][] = array('technician' => $unitQuery);
	    			$queryOr['$or'][] = array('technician2' => $unitQuery);
	    			if (!array_key_exists('$and', $query)) {
	    				$query['$and'] = array();
	    			}
	    			$query['$and'][] = $queryOr;
	    		} else if ($field['type'] == "LatLon") {
	    			$paramLeftToken = explode(",", $paramLeft);
	    			$query['latitude'] = new MongoRegex("/^" . preg_quote($paramLeftToken[0]) . "/i");
	    			$query['longitude'] = new MongoRegex("/^" . preg_quote($paramLeftToken[1]) . "/i");
	    		} else if (strpos($field['variable'], '_x') !== false) {
	    			$runVar = substr($field['variable'], 0, strlen($field['variable']) - 2);
	    			$runFilters[] = array ('variable' => $runVar, 'operator' => $operator, 'field' => $field, 'paramLeft' => $paramLeft, 'paramRight' => $paramRight);
	    			$queryOr = array();
	    			$queryOr['$or'] = array();
	    			for ($runNum = 1; $runNum <= 10; $runNum++) {
	    				$queryOr['$or'][] = array($runVar . '_' . $runNum => $unitQuery);
	    			}
	    			if (!array_key_exists('$and', $query)) {
	    				$query['$and'] = array();
	    			}
	    			$query['$and'][] = $queryOr;
	    		} else {
	    			if (is_array($paramLeft) && array_key_exists('_id', $paramLeft)) {
	    				$query[$field['variable'] . '.$id'] = $unitQuery;
	    			} else if (is_array($paramLeft) && $field['type'] == "StatusJob") {
	    				$query[$field['variable'] . '.$id'] = $unitQuery;
	    			} else {
	    				$query[$field['variable']] = $unitQuery;
	    			}
	    		}
	    	}
    	}
    	
    	$itemCursor = $itemModel->find($query);
    	$itemCursor->sort(array('uid' => -1));
    	foreach ($itemCursor as $item) {
    		foreach($item as $key => $value) {
    			$prop = $item[$key];
    			if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
    				$innerItemModel = $this->db->selectCollection($prop['$ref']);
    				$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
    				$item[$key] = $innerItem;
    			}
    		}
    		$matchRunIdx = null;
    		foreach ($runFilters as $runFilter) {
    			$matchRunUnitIdx = array();
    			$runVar = $runFilter['variable'];
    			$runField = $runFilter['field'];
    			$runOperator = $runFilter['operator'];
    			$runParamLeft = $runFilter['paramLeft'];
    			$runParamRight = $runFilter['paramRight'];
    			for ($runNum = 1; $runNum <= 10; $runNum++) {
    				//$queryOr['$or'][] = array($runVar . '_' . $runNum => $unitQuery);
    				$runVarNum = $runVar . '_' . $runNum;
    				if (array_key_exists($runVarNum, $item) && $runParamLeft != null) {
    					$valueToCheck = $item[$runVarNum];
    					// check if the query match the row record
    					if ($runOperator['id'] == 1) {
    						if ($runField['type'] == 'int') {
    							$unitQuery = 1 * $runParamLeft;
    							if ($valueToCheck == $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						} else if ($runField['type'] == 'date') {
    							$unitQuery = floor($runParamLeft/86400)*86400;
    							if ($valueToCheck == $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						} else if ($runField['type'] == 'WellCondition') {
    							// do nothing, there are no well condition in Run
    						} else {
    							if (is_array($runParamLeft)) {
    								if (array_key_exists('_id', $runParamLeft)) { // equal to Model
    									$unitQuery = "" . $runParamLeft['_id'];
    									if ($valueToCheck === $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    								} else { // equals to array
    									$unitQuery = array('$in' => $runParamLeft);
    									$found = false;
    									// check is contained or not
    									foreach ($runParamLeft as $runParamLeftItem) {
    										if (in_array($runParamLeftItem, $valueToCheck)) {
    											$found = true; break;
    										}
    									}
    									if ($found) { $matchRunUnitIdx[] = $runNum; }
    								}
    							} else { // equals to string or int
    								$unitQuery = "" . $runParamLeft;
    								if (strpos(strtoupper($valueToCheck), strtoupper($unitQuery)) !== false) { $matchRunUnitIdx[] = $runNum; }
    							}
    						}
    					} else if ($runOperator['id'] == 2) { // <
    						if ($runField['type'] == 'int') {
    							$unitQuery = 1 * $runParamLeft;
    							if ($valueToCheck < $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						} else if ($runField['type'] == 'date') {
    							$unitQuery = floor($runParamLeft/86400)*86400;
    							if ($valueToCheck < $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						}
    					} else if ($runOperator['id'] == 3) { // <=
    						if ($runField['type'] == 'int') {
    							$unitQuery = 1 * $runParamLeft;
    							if ($valueToCheck <= $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						} else if ($runField['type'] == 'date') {
    							$unitQuery = floor($runParamLeft/86400)*86400;
    							if ($valueToCheck <= $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						}
    					} else if ($runOperator['id'] == 4) { // >
    						if ($runField['type'] == 'int') {
    							$unitQuery = 1 * $runParamLeft;
    							if ($valueToCheck > $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						} else if ($runField['type'] == 'date') {
    							$unitQuery = floor($runParamLeft/86400)*86400;
    							if ($valueToCheck > $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						}
    					} else if ($runOperator['id'] == 5) { // >=
    						if ($runField['type'] == 'int') {
    							$unitQuery = 1 * $runParamLeft;
    							if ($valueToCheck >= $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						} else if ($runField['type'] == 'date') {
    							$unitQuery = floor($runParamLeft/86400)*86400;
    							if ($valueToCheck > $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						}
    					} else if ($runOperator['id'] == 6 && $runParamRight) { // between
    						if ($runField['type'] == 'int') {
    							if ($valueToCheck >= (1 * $runParamLeft) && $valueToCheck <= (1 * $runParamRight)) { $matchRunUnitIdx[] = $runNum; }
    						} elseif ($runField['type'] == 'date') {
    							if ($valueToCheck >= (floor($runParamLeft/86400)*86400) && $valueToCheck <= (floor($runParamRight/86400)*86400)) { $matchRunUnitIdx[] = $runNum; }
    						}
    					}
    				}
    			}
    			// take the cutting of all filtered Run num
    			if (count($matchRunUnitIdx) > 0) {
    				if ($matchRunIdx == null) {
    					$matchRunIdx = $matchRunUnitIdx;
    				} else {
    					$matchRunIdx = array_values(array_intersect($matchRunIdx, $matchRunUnitIdx));
    				}
    			}
    		}
    		// remove non match Run
    		if ($matchRunIdx != null) {
    			$matchRunIdx = array_values(array_unique($matchRunIdx));
    		}
    		$item['matchRunIdx'] = $matchRunIdx;
    		$data[] = $item;
    	}
    	
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data, 'query' => $query];
    	echo json_encode($output);
    }
    
    /**
     * Query based by searchParam, output as CSV
     * @return multitype:unknown
     */
    function listCsv_get() {
    	// debug variable
    	// Continue Real Operation
    	$data = [];
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	// post filter for removing Run that is not match with the query
    	$runFilters = array();
    	// search param
    	$columns = $this->get('columns');
    	$query = array();
    	for ($i = 1; $i <= 5; $i++) {
    		if (
    				($this->get('field' . $i) || $this->get('field' . $i) === '0') &&
    				($this->get('operator' . $i) || $this->get('operator' . $i) === '0')
    		) {
    			$field = $this->get('field' . $i);
    			$operator = $this->get('operator' . $i);
    			$paramLeft = $this->get('paramLeft' . $i);
    			$paramRight = $this->get('paramRight' . $i);
    			/*
    			 array('$or' => array(
    			 array('brand' => 'anti'),
    			 array('aa' => 'bb')
    			 )
    			 );
    			*/
    			$unitQuery = array();
    			if ($operator['id'] == 1) {
    				if ($field['type'] == 'int') {
    					$unitQuery = 1 * $paramLeft;
    				} else if ($field['type'] == 'date') {
    					$unitQuery = floor($paramLeft/86400)*86400;
    				} else if ($field['type'] == 'WellCondition') {
    					// do nothing, the process is at bottom
    				} else if ($field['type'] == "JarSerialJob") { // same process like string, just need to duplicate for 4 serial field
    					$unitQuery = new MongoRegex("/" . preg_quote($paramLeft) . "/i");
    				} else if ($field['type'] == "LatLon") {
    					// do nothing, the process is at bottom
	    			} else {
    					if (is_array($paramLeft)) {
    						if (array_key_exists('_id', $paramLeft)) { // equal to Model
    							$unitQuery = "" . $paramLeft['_id'];
    						} else { // equals to array
    							$unitQuery = array('$in' => $paramLeft);
    						}
    					} else { // equals to string or int
    						$unitQuery = new MongoRegex("/" . preg_quote($paramLeft) . "/i");
    					}
    				}
    			} else if ($operator['id'] == 2) { // <
    				if ($field['type'] == 'int') {
    					$unitQuery = array('$lt' => 1 * $paramLeft);
    				} else if ($field['type'] == 'date') {
    					$unitQuery = array('$lt' => floor($paramLeft/86400)*86400);
    				}
    			} else if ($operator['id'] == 3) { // <=
    				if ($field['type'] == 'int') {
    					$unitQuery = array('$lte' => 1 * $paramLeft);
    				} else if ($field['type'] == 'date') {
    					$unitQuery = array('$lte' => floor($paramLeft/86400)*86400);
    				}
    			} else if ($operator['id'] == 4) { // >
    				if ($field['type'] == 'int') {
    					$unitQuery = array('$gt' => 1 * $paramLeft);
    				} else if ($field['type'] == 'date') {
    					$unitQuery = array('$gt' => floor($paramLeft/86400)*86400);
    				}
    			} else if ($operator['id'] == 5) { // >=
    				if ($field['type'] == 'int') {
    					$unitQuery = array('$gte' => 1 * $paramLeft);
    				} else if ($field['type'] == 'date') {
    					$unitQuery = array('$gte' => floor($paramLeft/86400)*86400);
    				}
    			} else if ($operator['id'] == 6 && $paramRight) { // between
    				if ($field['type'] == 'int') {
    					$unitQuery = array('$gte' => 1 * $paramLeft, '$lte' => 1 * $paramRight);
    				} elseif ($field['type'] == 'date') {
    					$unitQuery = array('$gte' => floor($paramLeft/86400)*86400, '$lte' => floor($paramRight/86400)*86400);
    				}
    			}
    			if ($field['type'] == "WellCondition") {
    				$paramLeft1 = $this->get('paramLeft' . $i . '_1');
    				$paramLeft2 = $this->get('paramLeft' . $i . '_2');
    				$paramLeft3 = $this->get('paramLeft' . $i . '_3');
    				$paramLeft4 = $this->get('paramLeft' . $i . '_4');
    				$paramLeft5 = $this->get('paramLeft' . $i . '_5');
    				if ($paramLeft1 && $paramLeft1 == 'true') { $query['isCO2'] = true; }
    				if ($paramLeft2 && $paramLeft2 == 'true') { $query['isH2S'] = true; }
    				if ($paramLeft3 && $paramLeft3 == 'true') { $query['isHighTemp'] = true; }
    				if ($paramLeft4 && $paramLeft4 == 'true') { $query['isHighPress'] = true; }
    				if ($paramLeft5 && $paramLeft5 == 'true') { $query['isOther'] = true; }
    			} else if ($field['type'] == "JarSerialJob") {
	    			$queryOr = array();
	    			$queryOr['$or'] = array();
	    			for ($serialNum = 1; $serialNum <= 4; $serialNum++) {
	    				$queryOr['$or'][] = array($field['variable'] . $serialNum => $unitQuery);
	    			}
	    			if (!array_key_exists('$and', $query)) {
	    				$query['$and'] = array();
	    			}
	    			$query['$and'][] = $queryOr;
    			} else if ($field['type'] == "JarType") {
	    			$queryOr = array();
	    			$queryOr['$or'] = array();
	    			for ($toolNum = 1; $toolNum <= 15; $toolNum++) {
	    				$queryOr['$or'][] = array($field['variable'] . $toolNum => $unitQuery);
	    			}
	    			if (!array_key_exists('$and', $query)) {
	    				$query['$and'] = array();
	    			}
	    			$query['$and'][] = $queryOr;
	    		} else if ($field['type'] == "Technician") {
	    			$queryOr = array();
	    			$queryOr['$or'] = array();
	    			$queryOr['$or'][] = array('technician' => $unitQuery);
	    			$queryOr['$or'][] = array('technician2' => $unitQuery);
	    			if (!array_key_exists('$and', $query)) {
	    				$query['$and'] = array();
	    			}
	    			$query['$and'][] = $queryOr;
	    		} else if ($field['type'] == "LatLon") {
	    			$paramLeftToken = explode(",", $paramLeft);
	    			$query['latitude'] = new MongoRegex("/^" . preg_quote($paramLeftToken[0]) . "/i");
	    			$query['longitude'] = new MongoRegex("/^" . preg_quote($paramLeftToken[1]) . "/i");
	    		} else if (strpos($field['variable'], '_x') !== false) {
    				$runVar = substr($field['variable'], 0, strlen($field['variable']) - 2);
    				$runFilters[] = array ('variable' => $runVar, 'operator' => $operator, 'field' => $field, 'paramLeft' => $paramLeft, 'paramRight' => $paramRight);
    				$queryOr = array();
    				$queryOr['$or'] = array();
    				for ($runNum = 1; $runNum <= 10; $runNum++) {
    					$queryOr['$or'][] = array($runVar . '_' . $runNum => $unitQuery);
    				}
    				if (!array_key_exists('$and', $query)) {
    					$query['$and'] = array();
    				}
    				$query['$and'][] = $queryOr;
    			} else {
    				if (is_array($paramLeft) && array_key_exists('_id', $paramLeft)) {
    					$query[$field['variable'] . '.$id'] = $unitQuery;
    				} else if (is_array($paramLeft) && $field['type'] == "StatusJob") {
	    				$query[$field['variable'] . '.$id'] = $unitQuery;
	    			} else {
    					$query[$field['variable']] = $unitQuery;
    				}
    			}
    		}
    	}
    	 
    	$itemCursor = $itemModel->find($query);
    	$itemCursor->sort(array('uid' => -1));
    	foreach ($itemCursor as $item) {
    		foreach($item as $key => $value) {
    			$prop = $item[$key];
    			if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
    				$innerItemModel = $this->db->selectCollection($prop['$ref']);
    				$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
    				$item[$key] = $innerItem;
    			}
    		}
    		$matchRunIdx = null;
    		foreach ($runFilters as $runFilter) {
    			$matchRunUnitIdx = array();
    			$runVar = $runFilter['variable'];
    			$runField = $runFilter['field'];
    			$runOperator = $runFilter['operator'];
    			$runParamLeft = $runFilter['paramLeft'];
    			$runParamRight = $runFilter['paramRight'];
    			for ($runNum = 1; $runNum <= 10; $runNum++) {
    				//$queryOr['$or'][] = array($runVar . '_' . $runNum => $unitQuery);
    				$runVarNum = $runVar . '_' . $runNum;
    				if (array_key_exists($runVarNum, $item) && $runParamLeft) {
    					$valueToCheck = $item[$runVarNum];
    					// check if the query match the row record
    					if ($runOperator['id'] == 1) {
    						if ($runField['type'] == 'int') {
    							$unitQuery = 1 * $runParamLeft;
    							if ($valueToCheck == $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						} else if ($runField['type'] == 'date') {
    							$unitQuery = floor($runParamLeft/86400)*86400;
    							if ($valueToCheck == $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						} else if ($runField['type'] == 'WellCondition') {
    							// do nothing, there are no well condition in Run
    						} else {
    							if (is_array($runParamLeft)) {
    								if (array_key_exists('_id', $runParamLeft)) { // equal to Model
    									$unitQuery = "" . $runParamLeft['_id'];
    									if ($valueToCheck === $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    								} else { // equals to array
    									$unitQuery = array('$in' => $runParamLeft);
    									$found = false;
    									// check is contained or not
    									foreach ($runParamLeft as $runParamLeftItem) {
    										if (in_array($runParamLeftItem, $valueToCheck)) {    											
    											$found = true; break;
    										}
    									}
    									if ($found) { $matchRunUnitIdx[] = $runNum; }
    								}
    							} else { // equals to string or int
    								$unitQuery = "" . $runParamLeft;
    								if (strpos(strtoupper($valueToCheck), strtoupper($unitQuery)) !== false) { $matchRunUnitIdx[] = $runNum; }
    							}
    						}
    					} else if ($runOperator['id'] == 2) { // <
    						if ($runField['type'] == 'int') {
    							$unitQuery = 1 * $runParamLeft;
    							if ($valueToCheck < $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						} else if ($runField['type'] == 'date') {
    							$unitQuery = floor($runParamLeft/86400)*86400;
    							if ($valueToCheck < $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						}
    					} else if ($runOperator['id'] == 3) { // <=
    						if ($runField['type'] == 'int') {
    							$unitQuery = 1 * $runParamLeft;
    							if ($valueToCheck <= $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						} else if ($runField['type'] == 'date') {
    							$unitQuery = floor($runParamLeft/86400)*86400;
    							if ($valueToCheck <= $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						}
    					} else if ($runOperator['id'] == 4) { // >
    						if ($runField['type'] == 'int') {
    							$unitQuery = 1 * $runParamLeft;
    							if ($valueToCheck > $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						} else if ($runField['type'] == 'date') {
    							$unitQuery = floor($runParamLeft/86400)*86400;
    							if ($valueToCheck > $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						}
    					} else if ($runOperator['id'] == 5) { // >=
    						if ($runField['type'] == 'int') {
    							$unitQuery = 1 * $runParamLeft;
    							if ($valueToCheck >= $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						} else if ($runField['type'] == 'date') {
    							$unitQuery = floor($runParamLeft/86400)*86400;
    							if ($valueToCheck > $unitQuery) { $matchRunUnitIdx[] = $runNum; }
    						}
    					} else if ($runOperator['id'] == 6 && $runParamRight) { // between
    						if ($runField['type'] == 'int') {
    							if ($valueToCheck >= (1 * $runParamLeft) && $valueToCheck <= (1 * $runParamRight)) { $matchRunUnitIdx[] = $runNum; }
    						} elseif ($runField['type'] == 'date') {
    							if ($valueToCheck >= (floor($runParamLeft/86400)*86400) && $valueToCheck <= (floor($runParamRight/86400)*86400)) { $matchRunUnitIdx[] = $runNum; }
    						}
    					}
    				}
    			}
    			// take the cutting of all filtered Run num
    			if (count($matchRunUnitIdx) > 0) {
    				if ($matchRunIdx == null) {
    					$matchRunIdx = $matchRunUnitIdx;
    				} else {
    					$matchRunIdx = array_values(array_intersect($matchRunIdx, $matchRunUnitIdx));
    				}
    			}
    		}
    		// remove non match Run
    		if ($matchRunIdx != null) {
    			$matchRunIdx = array_values(array_unique($matchRunIdx));
    		}
    		$item['matchRunIdx'] = $matchRunIdx;
    		$data[] = $item;
    	}
    	//$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data, 'query' => $query];
    	//echo json_encode($output);
	    
	    $objPHPExcel = new PHPExcel();
	    $objPHPExcel->getProperties()->setCreator("Picolov");
	    $objPHPExcel->getProperties()->setLastModifiedBy("Picolov");
	    $objPHPExcel->getProperties()->setTitle("KMS Job Query Result");
	    $objPHPExcel->getProperties()->setSubject("Job Query Result");
	    $objPHPExcel->getProperties()->setDescription("KMS Job Query Result.");
	    
	    
	    // Add some data
	    $objPHPExcel->setActiveSheetIndex(0);
	    $sheet = $objPHPExcel->getActiveSheet();
	    // set title row
	    $sheet->setCellValueByColumnAndRow(0, 1, "RTA");
	    $columnNum = 0;
	    for ($i = 0; $i < count($columns); $i++) {
	    	$sheet->setCellValueByColumnAndRow($i + 1, 1, $columns[$i]['title']);
	    }
	    
	    // start content from row 2
	    $rowNum = 2;
	    for ($i = 0; $i < count($data); $i++) {
	    	$datum = $data[$i];
	    	if (array_key_exists('matchRunIdx', $datum) && $datum['matchRunIdx'] !== null && count($datum['matchRunIdx']) === 0) {
	    		continue;
	    	}
	    	$sheet->setCellValueByColumnAndRow(0, $rowNum, array_key_exists("uid", $datum)?$datum["uid"]:'');
	    	for ($j = 0; $j < count($columns); $j++) {
	    		if ($columns[$j]['variable'] == 'fromDate') { // 1
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("fromDate", $datum)?date("m/d/y", $datum["fromDate"]):'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'toDate') {// 2
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("toDate", $datum)?date("m/d/y", $datum["toDate"]):'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'status.name') {// 3
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("status", $datum)?$datum["status"]["name"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'toolstringName_') {// 4
	    			
	    		} else if ($columns[$j]['variable'] == 'run_') {// 5
	    			
	    		} else if ($columns[$j]['variable'] == 'itemCount') {// 6
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("itemCount", $datum)?$datum["itemCount"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'rigName') {// 7
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("rigName", $datum)?$datum["rigName"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'oilCompany.name') {// 8
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("oilCompany", $datum)?$datum["oilCompany"]["name"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'serviceCompany.name') {// 9
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("serviceCompany", $datum)?$datum["serviceCompany"]["name"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'temp') {// 10
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("temp", $datum)?$datum["temp"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'environment') {// 11
	    			$environment = '';
	    			if (array_key_exists("environment", $datum)) {
	    				if ($datum["environment"] == 0) { $environment = 'Land'; }
	    				if ($datum["environment"] == 1) { $environment = 'Offshore'; }
	    				if ($datum["environment"] == 2) { $environment = 'International'; }
	    			}
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $environment, PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'wellCondition') {// 12
	    			$wellCondition = '';
	    			if (array_key_exists('isCO2', $datum) && $datum['isCO2']) { $wellCondition = $wellCondition . 'CO2,';}
	    			if (array_key_exists('isH2S', $datum) && $datum['isH2S']) { $wellCondition = $wellCondition . 'H2S,';}
	    			if (array_key_exists('isHighTemp', $datum) && $datum['isHighTemp']) { $wellCondition = $wellCondition . 'High Temp,';}
	    			if (array_key_exists('isHighPress', $datum) && $datum['isHighPress']) { $wellCondition = $wellCondition . 'High Press,';}
	    			if (array_key_exists('isOther', $datum) && array_key_exists('isOtherVal', $datum) && $datum['isOther'] && $datum['isOtherVal']) { $wellCondition = $wellCondition . $datum['isOtherVal'] . ',';}
	    			if (strlen($wellCondition) > 1) {
	    				$wellCondition = substr($wellCondition, 0, strlen($wellCondition) - 1);
	    			}
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $wellCondition, PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'wellType') {// 13
	    			$wellType = '';
	    			if (array_key_exists("wellType", $datum)) {
	    				if ($datum["wellType"] == 0) { $wellType = 'Developmental'; }
	    				if ($datum["wellType"] == 1) { $wellType = 'Exploration'; }
	    				if ($datum["wellType"] == 2) { $wellType = 'Appraisal'; }
	    			}
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $wellType, PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'rigType.name') {// 14
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("rigType", $datum)?$datum["rigType"]['name']:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'wellName') {// 15
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("wellName", $datum)?$datum["wellName"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'block') {// 16
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("block", $datum)?$datum["block"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'lease') {// 17
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("lease", $datum)?$datum["lease"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'field') {// 18
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("field", $datum)?$datum["field"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'deviation') {// 19
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("deviation", $datum)?$datum["deviation"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'country') {// 20
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("country", $datum)?$datum["country"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'county') {// 21
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("county", $datum)?$datum["county"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'state') {// 22
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("state", $datum)?$datum["state"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'parish') {// 23
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("parish", $datum)?$datum["parish"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'technician') {// 24
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, (array_key_exists("technician", $datum)?$datum["technician"]:'') . (array_key_exists("technician2", $datum) ? '/' . $datum["technician2"]:''), PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == ' +wirelineEngineer.titleName+wirelineEngineer.firstName+wirelineEngineer.lastName') {// 25
	    			$engineerName = ''; 
	    			if (array_key_exists("wirelineEngineer", $datum)) {
	    				$engineer = $datum["wirelineEngineer"];
	    				if ($engineer && array_key_exists("titleName", $engineer)) {
	    					$engineerName = $engineerName . " " . $engineer["titleName"];
	    				}
	    				if ($engineer && array_key_exists("firstName", $engineer)) {
	    					$engineerName = $engineerName . " " . $engineer["firstName"];
	    				}
	    				if ($engineer && array_key_exists("lastName", $engineer)) {
	    					$engineerName = $engineerName . " " . $engineer["lastName"];
	    				}
	    			}
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $engineerName, PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'totalCountActivation') {// 26
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("totalCountActivation", $datum)?$datum["totalCountActivation"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'toolType') {// 27
    				$jarType = array();
    				$toolCount = 1;
    				if(array_key_exists("toolCount", $datum)) {
    					$toolCount = $datum['toolCount'];
    				}
    				for ($toolNum = 1; $toolNum <= $toolCount; $toolNum++) {
	    				if (array_key_exists("toolType" . $toolNum, $datum)) {
	    					$jarType[] = $datum['toolType' . $toolNum];
	    				}
    				}
    				$jarTypeUnique = array_values(array_unique($jarType));
    				$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, implode(", ", $jarTypeUnique), PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'jarSerial_') {// 28
	    			
	    		} else if ($columns[$j]['variable'] == 'revenue') {// 29
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("revenue", $datum)?$datum["revenue"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'waterDepth') {// 30
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("waterDepth", $datum)?$datum["waterDepth"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'activation_') {// 31
	    			
	    		} else if ($columns[$j]['variable'] == 'actualJar_') {// 32
	    			
	    		} else if ($columns[$j]['variable'] == 'impactPro') {// 33
	    			$impactPro = '';
					if (array_key_exists("impactPro", $datum)) {
						if ($datum["impactPro"] == 0) { $impactPro = 'No'; }
						if ($datum["impactPro"] == 1) { $impactPro = 'Yes'; }
					}
					$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $impactPro, PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'fishing_') {// 34
	    			
	    		} else if ($columns[$j]['variable'] == 'holeSize') {// 35
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("holeSize", $datum)?$datum["holeSize"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'bhp') {// 36
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("bhp", $datum)?$datum["bhp"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'mudWt') {// 37
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("mudWt", $datum)?$datum["mudWt"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'msp_') {// 38
	    			
	    		} else if ($columns[$j]['variable'] == 'wif_') {// 39
	    			
	    		} else if ($columns[$j]['variable'] == 'wia_') {// 40
	    			
	    		} else if ($columns[$j]['variable'] == 'wob_') {// 41
	    			
	    		} else if ($columns[$j]['variable'] == 'toolstringLength_') {// 42
	    			
	    		} else if ($columns[$j]['variable'] == 'cableSize') {// 43
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("cableSize", $datum)?$datum["cableSize"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'cableWeight') {// 44
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("cableWeight", $datum)?$datum["cableWeight"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'actualJar_') {// 45
	    			
	    		} else if ($columns[$j]['variable'] == 'wp_') {// 46
	    			
	    		} else if ($columns[$j]['variable'] == 'td') {// 47
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("td", $datum)?$datum["td"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'ticketNo') {// 48
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("ticketNo", $datum)?$datum["ticketNo"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'po') {// 49
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("po", $datum)?$datum["po"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'api') {// 50
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("api", $datum)?$datum["api"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'costCenter') {// 51
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("costCenter", $datum)?$datum["costCenter"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'afe') {// 52
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("afe", $datum)?$datum["afe"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'ocsg') {// 53
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("ocsg", $datum)?$datum["ocsg"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'stateLease') {// 54
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("stateLease", $datum)?$datum["stateLease"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'jobEnv') {// 55
	    			$jobEnv = '';
	    			if (array_key_exists("jobEnv", $datum)) {
	    				if ($datum["jobEnv"] == 0) { $jobEnv = 'Land'; }
	    				if ($datum["jobEnv"] == 1) { $jobEnv = 'Inland Waters'; }
	    				if ($datum["jobEnv"] == 2) { $jobEnv = 'Shelf'; }
	    				if ($datum["jobEnv"] == 3) { $jobEnv = 'Deepwater'; }
	    			}
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $jobEnv, PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'impactPro_') {// 56
	    			
	    		} else if ($columns[$j]['variable'] == 'serial') {// 57
	    			$serial = array();
	    			if (array_key_exists("serial1", $datum)) {
	    				$serial[] = $datum['serial1'];
	    			}
	    			if (array_key_exists("serial2", $datum)) {
	    				$serial[] = $datum['serial2'];
	    			}
	    			if (array_key_exists("serial3", $datum)) {
	    				$serial[] = $datum['serial3'];
	    			}
	    			if (array_key_exists("serial4", $datum)) {
	    				$serial[] = $datum['serial4'];
	    			}
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, implode(", ", $serial), PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'city') {// 53
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("city", $datum)?$datum["city"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == ' +companyMan.titleName+companyMan.firstName+companyMan.lastName') {// 25
	    			$companyManName = '';
	    			if (array_key_exists("companyMan", $datum)) {
	    				$companyMan = $datum["companyMan"];
	    				if ($companyMan && array_key_exists("titleName", $companyMan)) {
	    					$companyManName = $companyManName . " " . $companyMan["titleName"];
	    				}
	    				if ($companyMan && array_key_exists("firstName", $companyMan)) {
	    					$companyManName = $companyManName . " " . $companyMan["firstName"];
	    				}
	    				if ($companyMan && array_key_exists("lastName", $companyMan)) {
	    					$companyManName = $companyManName . " " . $companyMan["lastName"];
	    				}
	    			}
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $companyManName, PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'sales.employeeName') {// 24
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("sales", $datum) && array_key_exists("employeeName", $datum["sales"])?$datum["sales"]['employeeName']:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'rigPhone') {// 48
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("rigPhone", $datum)?$datum["rigPhone"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'rigFax') {// 48
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("rigFax", $datum)?$datum["rigFax"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'rigEmail') {// 48
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("rigEmail", $datum)?$datum["rigEmail"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'engineerPhone') {// 48
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("engineerPhone", $datum)?$datum["engineerPhone"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'engineerEmail') {// 48
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("engineerEmail", $datum)?$datum["engineerEmail"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		} else if ($columns[$j]['variable'] == 'latLon') {// 48
	    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("latitude", $datum) && array_key_exists("longitude", $datum)?$datum["latitude"] . "," . $datum["longitude"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
	    		}
	    	}
			
			// for Run number
			$firstRun = true;
			$runDisplayCount = 0;
			if (array_key_exists('itemCount', $datum)) {	
				for ($runIdx = 1; $runIdx <= $datum["itemCount"]; $runIdx++) {
					$columnRunDisplayed = false;
					if (array_key_exists('matchRunIdx', $datum) && $datum['matchRunIdx']) {
						$idxMatch = $datum['matchRunIdx'];
						// if the run index is not in matchRunIdx, it means it is not to be displayed, so continue the loop
						if (!in_array($runIdx, $idxMatch)) {
							continue;
						}
					}
					
					//////////////
					
					for ($j = 0; $j < count($columns); $j++) {
						// repeated column
						$sheet->setCellValueByColumnAndRow(0, $rowNum, array_key_exists("uid", $datum)?$datum["uid"]:'');
						if ($columns[$j]['variable'] == 'fromDate') { // 1
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("fromDate", $datum)?date("m/d/y", $datum["fromDate"]):'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'toDate') {// 2
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("toDate", $datum)?date("m/d/y", $datum["toDate"]):'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'status.name') {// 3
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("status", $datum)?$datum["status"]["name"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'toolstringName_') {// 4
						
						} else if ($columns[$j]['variable'] == 'run_') {// 5
						
						} else if ($columns[$j]['variable'] == 'itemCount') {// 6
							// totalRun requested to be not repeated
							//$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("itemCount", $datum)?$datum["itemCount"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'rigName') {// 7
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("rigName", $datum)?$datum["rigName"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'oilCompany.name') {// 8
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("oilCompany", $datum)?$datum["oilCompany"]["name"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'serviceCompany.name') {// 9
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("serviceCompany", $datum)?$datum["serviceCompany"]["name"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'temp') {// 10
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("temp", $datum)?$datum["temp"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'environment') {// 11
							$environment = '';
							if (array_key_exists("environment", $datum)) {
								if ($datum["environment"] == 0) { $environment = 'Land'; }
								if ($datum["environment"] == 1) { $environment = 'Offshore'; }
								if ($datum["environment"] == 2) { $environment = 'International'; }
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $environment, PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'wellCondition') {// 12
							$wellCondition = '';
							if (array_key_exists('isCO2', $datum) && $datum['isCO2']) { $wellCondition = $wellCondition . 'CO2,';}
							if (array_key_exists('isH2S', $datum) && $datum['isH2S']) { $wellCondition = $wellCondition . 'H2S,';}
							if (array_key_exists('isHighTemp', $datum) && $datum['isHighTemp']) { $wellCondition = $wellCondition . 'High Temp,';}
							if (array_key_exists('isHighPress', $datum) && $datum['isHighPress']) { $wellCondition = $wellCondition . 'High Press,';}
							if (array_key_exists('isOther', $datum) && array_key_exists('isOtherVal', $datum) && $datum['isOther'] && $datum['isOtherVal']) { $wellCondition = $wellCondition . $datum['isOtherVal'] . ',';}
							if (strlen($wellCondition) > 1) {
								$wellCondition = substr($wellCondition, 0, strlen($wellCondition) - 1);
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $wellCondition, PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'wellType') {// 13
							$wellType = '';
							if (array_key_exists("wellType", $datum)) {
								if ($datum["wellType"] == 0) { $wellType = 'Developmental'; }
								if ($datum["wellType"] == 1) { $wellType = 'Exploration'; }
								if ($datum["wellType"] == 2) { $wellType = 'Appraisal'; }
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $wellType, PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'rigType.name') {// 14
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("rigType", $datum)?$datum["rigType"]['name']:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'wellName') {// 15
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("wellName", $datum)?$datum["wellName"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'block') {// 16
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("block", $datum)?$datum["block"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'lease') {// 17
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("lease", $datum)?$datum["lease"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'field') {// 18
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("field", $datum)?$datum["field"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'deviation') {// 19
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("deviation", $datum)?$datum["deviation"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'country') {// 20
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("country", $datum)?$datum["country"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'county') {// 21
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("county", $datum)?$datum["county"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'state') {// 22
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("state", $datum)?$datum["state"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'parish') {// 23
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("parish", $datum)?$datum["parish"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'technician') {// 24
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, (array_key_exists("technician", $datum)?$datum["technician"]:'') . (array_key_exists("technician2", $datum) ? '/' . $datum["technician2"]:''), PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == ' +wirelineEngineer.titleName+wirelineEngineer.firstName+wirelineEngineer.lastName') {// 25
							$engineerName = '';
							if (array_key_exists("wirelineEngineer", $datum)) {
								$engineer = $datum["wirelineEngineer"];
								if ($engineer && array_key_exists("titleName", $engineer)) {
									$engineerName = $engineerName . " " . $engineer["titleName"];
								}
								if ($engineer && array_key_exists("firstName", $engineer)) {
									$engineerName = $engineerName . " " . $engineer["firstName"];
								}
								if ($engineer && array_key_exists("lastName", $engineer)) {
									$engineerName = $engineerName . " " . $engineer["lastName"];
								}
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $engineerName, PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'totalCountActivation') {// 26
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("totalCountActivation", $datum)?$datum["totalCountActivation"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'toolType') {// 27
							$jarType = array();
							$toolCount = 1;
							if(array_key_exists("toolCount", $datum)) {
								$toolCount = $datum['toolCount'];
							}
							for ($toolNum = 1; $toolNum <= $toolCount; $toolNum++) {
								if (array_key_exists("toolType" . $toolNum, $datum)) {
									$jarType[] = $datum['toolType' . $toolNum];
								}
							}
							$jarTypeUnique = array_values(array_unique($jarType));
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, implode(", ", $jarTypeUnique), PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'jarSerial_') {// 28
						
						} else if ($columns[$j]['variable'] == 'revenue') {// 29
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("revenue", $datum)?$datum["revenue"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'waterDepth') {// 30
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("waterDepth", $datum)?$datum["waterDepth"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'activation_') {// 31
						
						} else if ($columns[$j]['variable'] == 'actualJar_') {// 32
						
						} else if ($columns[$j]['variable'] == 'impactPro') {// 33
							$impactPro = '';
							if (array_key_exists("impactPro", $datum)) {
								if ($datum["impactPro"] == 0) { $impactPro = 'No'; }
								if ($datum["impactPro"] == 1) { $impactPro = 'Yes'; }
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $impactPro, PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'fishing_') {// 34
						
						} else if ($columns[$j]['variable'] == 'holeSize') {// 35
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("holeSize", $datum)?$datum["holeSize"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'bhp') {// 36
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("bhp", $datum)?$datum["bhp"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'mudWt') {// 37
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("mudWt", $datum)?$datum["mudWt"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'msp_') {// 38
						
						} else if ($columns[$j]['variable'] == 'wif_') {// 39
						
						} else if ($columns[$j]['variable'] == 'wia_') {// 40
						
						} else if ($columns[$j]['variable'] == 'wob_') {// 41
						
						} else if ($columns[$j]['variable'] == 'toolstringLength_') {// 42
						
						} else if ($columns[$j]['variable'] == 'cableSize') {// 43
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("cableSize", $datum)?$datum["cableSize"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'cableWeight') {// 44
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("cableWeight", $datum)?$datum["cableWeight"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'actualJar_') {// 45
						
						} else if ($columns[$j]['variable'] == 'wp_') {// 46
						
						} else if ($columns[$j]['variable'] == 'td') {// 47
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("td", $datum)?$datum["td"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'ticketNo') {// 48
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("ticketNo", $datum)?$datum["ticketNo"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'po') {// 49
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("po", $datum)?$datum["po"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'api') {// 50
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("api", $datum)?$datum["api"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'costCenter') {// 51
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("costCenter", $datum)?$datum["costCenter"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'afe') {// 52
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("afe", $datum)?$datum["afe"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'ocsg') {// 53
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("ocsg", $datum)?$datum["ocsg"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'stateLease') {// 54
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("stateLease", $datum)?$datum["stateLease"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'jobEnv') {// 55
							$jobEnv = '';
							if (array_key_exists("jobEnv", $datum)) {
								if ($datum["jobEnv"] == 0) { $jobEnv = 'Land'; }
								if ($datum["jobEnv"] == 1) { $jobEnv = 'Inland Waters'; }
								if ($datum["jobEnv"] == 2) { $jobEnv = 'Shelf'; }
								if ($datum["jobEnv"] == 3) { $jobEnv = 'Deepwater'; }
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $jobEnv, PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'impactPro_') {// 56
						
						} else if ($columns[$j]['variable'] == 'serial') {// 57
							$serial = array();
							if (array_key_exists("serial1", $datum)) {
								$serial[] = $datum['serial1'];
							}
							if (array_key_exists("serial2", $datum)) {
								$serial[] = $datum['serial2'];
							}
							if (array_key_exists("serial3", $datum)) {
								$serial[] = $datum['serial3'];
							}
							if (array_key_exists("serial4", $datum)) {
								$serial[] = $datum['serial4'];
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, implode(", ", $serial), PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'city') {// 53
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("city", $datum)?$datum["city"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == ' +companyMan.titleName+companyMan.firstName+companyMan.lastName') {// 25
							$companyManName = '';
							if (array_key_exists("companyMan", $datum)) {
								$companyMan = $datum["companyMan"];
								if ($companyMan && array_key_exists("titleName", $companyMan)) {
									$companyManName = $companyManName . " " . $companyMan["titleName"];
								}
								if ($companyMan && array_key_exists("firstName", $companyMan)) {
									$companyManName = $companyManName . " " . $companyMan["firstName"];
								}
								if ($companyMan && array_key_exists("lastName", $companyMan)) {
									$companyManName = $companyManName . " " . $companyMan["lastName"];
								}
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $companyManName, PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'sales.employeeName') {// 24
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("sales", $datum) && array_key_exists("employeeName", $datum["sales"])?$datum["sales"]['employeeName']:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'rigPhone') {// 48
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("rigPhone", $datum)?$datum["rigPhone"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'rigFax') {// 48
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("rigFax", $datum)?$datum["rigFax"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'rigEmail') {// 48
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("rigEmail", $datum)?$datum["rigEmail"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'engineerPhone') {// 48
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("engineerPhone", $datum)?$datum["engineerPhone"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'engineerEmail') {// 48
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("engineerEmail", $datum)?$datum["engineerEmail"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						} else if ($columns[$j]['variable'] == 'latLon') {// 48
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("latitude", $datum) && array_key_exists("longitude", $datum)?$datum["latitude"] . "," . $datum["longitude"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
						}
						// non repeated
						if ($columns[$j]['variable'] == 'toolstringName_') {// 4
							$toolstringName = '';
							if (array_key_exists("toolstringName_$runIdx", $datum)) {
								for ($toolstringLoop = 0; $toolstringLoop < count($datum["toolstringName_$runIdx"]); $toolstringLoop++) {
									$toolstringName = $toolstringName . $datum["toolstringName_$runIdx"][$toolstringLoop] . ",";
									if ($toolstringLoop == count($datum["toolstringName_$runIdx"]) - 1) { $toolstringName = substr($toolstringName, 0, strlen($toolstringName) - 1); }
								}
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $toolstringName, PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'run_') {// 5
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("run_$runIdx", $datum)?$datum["run_$runIdx"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'jarSerial_') {// 28
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("jarSerial_$runIdx", $datum)?$datum["jarSerial_$runIdx"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'activation_') {// 31
							$activation = '';
							if (array_key_exists("activation_$runIdx", $datum)) {
								if ($datum["activation_$runIdx"] == 0) { $activation = 'No'; }
								if ($datum["activation_$runIdx"] == 1) { $activation = 'Yes'; }
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $activation, PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'actualJar_') {// 32
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("actualJar_$runIdx", $datum)?$datum["actualJar_$runIdx"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'fishing_') {// 34
							$fishing = '';
							if (array_key_exists("fishing_$runIdx", $datum)) {
								if ($datum["fishing_$runIdx"] == 0) { $fishing = 'No'; }
								if ($datum["fishing_$runIdx"] == 1) { $fishing = 'Yes'; }
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $fishing, PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'msp_') {// 38
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("msp_$runIdx", $datum)?$datum["msp_$runIdx"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'wif_') {// 39
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("wif_$runIdx", $datum)?$datum["wif_$runIdx"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'wia_') {// 40
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("wia_$runIdx", $datum)?$datum["wia_$runIdx"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'wob_') {// 41
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("wob_$runIdx", $datum)?$datum["wob_$runIdx"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'toolstringLength_') {// 42
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("toolstringLength_$runIdx", $datum)?$datum["toolstringLength_$runIdx"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'actualJar_') {// 45
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("actualJar_$runIdx", $datum)?$datum["actualJar_$runIdx"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'wp_') {// 46
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("wp_$runIdx", $datum)?$datum["wp_$runIdx"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						} else if ($columns[$j]['variable'] == 'impactPro_') {// 56
							$impactProRun = '';
							if (array_key_exists("impactPro_$runIdx", $datum)) {
								if ($datum["impactPro_$runIdx"] == 0) { $impactProRun = 'No'; }
								if ($datum["impactPro_$runIdx"] == 1) { $impactProRun = 'Yes'; }
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $impactProRun, PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
	    				} else if ($columns[$j]['variable'] == 'impactProSlotSize_') {// 56
							$impactProSlotSizeRun = '';
							if (array_key_exists("impactProSlotSize_$runIdx", $datum)) {
								if ($datum["impactProSlotSize_$runIdx"] == 0) { $impactProSlotSizeRun = '.25"'; }
								if ($datum["impactProSlotSize_$runIdx"] == 1) { $impactProSlotSizeRun = '.50"'; }
								if ($datum["impactProSlotSize_$runIdx"] == 2) { $impactProSlotSizeRun = '.75"'; }
								if ($datum["impactProSlotSize_$runIdx"] == 3) { $impactProSlotSizeRun = 'Blank'; }
								if ($datum["impactProSlotSize_$runIdx"] == 4) { $impactProSlotSizeRun = 'N/A'; }
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $impactProSlotSizeRun, PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
	    				} else if ($columns[$j]['variable'] == 'toolsFreed_') {// 56
							$toolsFreed = '';
							if (array_key_exists("toolsFreed_$runIdx", $datum)) {
								if ($datum["toolsFreed_$runIdx"] == 0) { $toolsFreed = 'No'; }
								if ($datum["toolsFreed_$runIdx"] == 1) { $toolsFreed = 'Yes'; }
								if ($datum["toolsFreed_$runIdx"] == 2) { $toolsFreed = 'N/A'; }
							}
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, $toolsFreed, PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
	    				} else if ($columns[$j]['variable'] == 'fromDate_') {// 57
			    			$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("fromDate_$runIdx", $datum)?date("m/d/y", $datum["fromDate_$runIdx"]):'', PHPExcel_Cell_DataType::TYPE_STRING);
			    			$columnRunDisplayed = true;
			    		} else if ($columns[$j]['variable'] == 'totalHour_') {// 46
							$sheet->setCellValueExplicitByColumnAndRow($j + 1, $rowNum, array_key_exists("totalHour_$runIdx", $datum)?$datum["totalHour_$runIdx"]:'', PHPExcel_Cell_DataType::TYPE_STRING);
							$columnRunDisplayed = true;
						}
					}
					
					//////////////
					if ($columnRunDisplayed) {
						$rowNum++;
						$runDisplayCount++;
						// normalize extra space on last loop
						if (array_key_exists('matchRunIdx', $datum)) {
							if ($runDisplayCount === count($datum['matchRunIdx'])) { 
								$rowNum--;
							} else if ($datum['matchRunIdx'] === NULL && $runDisplayCount === $datum["itemCount"]) {
								// if matchRunIdx === NULL it means all run is included in the loop, thus in here it compared to itemCount to check endings
								$rowNum--;
							}
						}
					}
				}
		    }
			$rowNum++;
	    }
	    // autosize column width
	    for ($i = 0; $i <= count($columns); $i++) {
	    	$sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($i))->setAutoSize(true);
	    }
	    // centering all column
	    $sheet->getStyle( $objPHPExcel->getActiveSheet()->calculateWorksheetDimension() )->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	    $filename = 'JobQuery';
	    header("Pragma: public");
	    header("Expires: 0");
	    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	    header("Content-Disposition: attachment;filename=$filename.xls");
	    header("Content-Transfer-Encoding: binary ");
	    $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel); 
	    $objWriter->setOffice2003Compatibility(true);
	    
	    //Add some additional data 
	
	    $objWriter->save('php://output');
    }
    
}
?>