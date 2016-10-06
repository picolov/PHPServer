<?php
require 'baseController.php';

class WorkLog extends BaseController {
	
	public function __construct()
	{
		$this->setDoc('WorkLog');
		$this->setObjMap(array(
		));
		parent::__construct();
	}
	
	function getStartAndEndDate($week, $year) {
		
		$dto = new DateTime();
		$ret['start'] = $dto->setISODate($year, $week)->getTimestamp();
		$ret['end'] = $dto->modify('+6 days')->getTimestamp();
		return $ret;
	}
	
	/**
	 * Override Get a List of WorkLog
	 * @return multitype:unknown
	 */
	function list_get() {
		$data = [];
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$itemModel = $this->db->selectCollection($this->docName);
		if ($this->get('args')) {
			$startEndTime = $this->get('args');
			$startEndArray = explode(",", $startEndTime);
			$itemCursor = $itemModel->find(array("createdBy" => $this->token['user']['username'], "logType" => "Daily", "workTime" => array('$gte' => (int) $startEndArray[0], '$lte' => (int) $startEndArray[1])));
			foreach ($itemCursor as $item) {
				$data[] = $item;
			}
			$startDateTime = date_create("@" . (int) $startEndArray[0]);
			$startWeek = $this->getStartAndEndDate((int) ($startDateTime->format("W")), (int) ($startDateTime->format("Y")));
			$endDateTime = date_create("@" . (int) $startEndArray[1]);
			$endWeek = $this->getStartAndEndDate((int) ($endDateTime->format("W")), (int) ($endDateTime->format("Y")));
			$itemCursor = $itemModel->find(array("createdBy" => $this->token['user']['username'], "logType" => "Weekly", "workTime" => array('$gte' => (int) $startWeek['start'], '$lte' => (int) $endWeek['end'])));
			foreach ($itemCursor as $item) {
				$data[] = $item;
			}
		} else {
			$itemCursor = $itemModel->find(array("createdBy" => $this->token['user']['username']));
			$itemCursor->limit(100);
			foreach ($itemCursor as $item) {
				$data[] = $item;
			}
		}
		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
		return $output;
	}
	
	/**
	 * Create a Class Object in Database
	 * @return multitype:unknown
	 */
	function save_post() {
		// Continue Real Operation
		$data = $this->_post_args;
		if (is_array($data)) {
			if (!array_key_exists('_id', $data)) {
				$data['_id'] = UUID::v4();
			}
			foreach ($data as $key => $value) {
				if (is_array($value) && array_key_exists($key, $this->objMap) && array_key_exists('_id', $value)) {
					$data[$key] = array('$ref' => $this->objMap[$key], '$id' => $value['_id']);
				}
			}
			$data['createdBy'] = $this->token['user']['username'];
			$data['createdTime'] = time();
			$con = new MongoClient ();
			$this->db = $con->selectDB(_DB_NAME);
			$itemModel = $this->db->selectCollection($this->docName);
			// Check if same workTime and createdBy is already exist
			$itemCursor = $itemModel->find(array('workTime' => $data['workTime'], 'createdBy' => $data['createdBy'], 'logType' => 'Daily'));
			if ($itemCursor->count() > 0) {
				$output = ['status' => 30000, 'message' => 'record already exist', 'data' => $data];
			} else {
				$itemModel->save($data);
				$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
			}
		}
		
		return $output;
	}
	
	/**
	 * Update a Class Object in Database
	 * @return multitype:unknown
	 */
	function update_post() {
		// Continue Real Operation
		$data = $this->_post_args;
		if (is_array($data) && array_key_exists('_id', $data)) {
			foreach ($data as $key => $value) {
				if (is_array($value) && array_key_exists($key, $this->objMap) && array_key_exists('_id', $value)) {
					$data[$key] = array('$ref' => $this->objMap[$key], '$id' => $value['_id']);
				}
			}
			$data['updatedBy'] = $this->token['user']['username'];
			$data['updatedTime'] = time();
			$con = new MongoClient ();
			$this->db = $con->selectDB(_DB_NAME);
			$itemModel = $this->db->selectCollection($this->docName);
			// Check if same workTime and createdBy is already exist
			$itemCursor = $itemModel->find(array('workTime' => $data['workTime'], 'createdBy' => $data['createdBy'], 'logType' => 'Daily'));
			$dataModified = $itemModel->findOne(array('_id' => $data['_id']));
			if ($dataModified['workTime'] != $data['workTime'] && $itemCursor->count() > 0) {
				$output = ['status' => 30000, 'message' => 'record already exist', 'data' => $data];
			} else {
				foreach ($data as $key => $value) {
					$dataModified[$key] = $value;
				}
				$itemModel->update(array("_id" => $data['_id']), $dataModified);
				$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
			}
			
		}
		return $output;
	}
}
?>