<?php
require 'baseController.php';

class ForumPost extends BaseController {
	public function __construct()
	{
		$this->setDoc('forumPost');
		parent::__construct();
	}
	
	/**
	 * Get Posts based on Topic id
	 * @return multitype:unknown
	 */
	function listByTopic_get() {
		// Continue Real Operation
		$data = [];
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$itemModel = $this->db->selectCollection($this->docName);
		$itemCursor = $itemModel->find(array("topic" => $this->get('_id')));
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
	 * save post of topic, and increase reply count in topic
	 * @return multitype:unknown
	 */
	function saveMine_post() {
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
			$time = time();
			$data['createdBy'] = $this->token['user']['username'];
			$data['createdTime'] = $time;
			$data['user'] = $this->token['user'];
			$con = new MongoClient ();
			$this->db = $con->selectDB(_DB_NAME);
			$itemModel = $this->db->selectCollection($this->docName);
			$itemModel->save($data);
			// increase reply count
			$topicModel = $this->db->selectCollection("forumTopic");
			$topic = $topicModel->findOne(array('_id' => $data['topic']));
			$topic['reply']++;
			$topic['replyUser'] = $this->token['user'];
			$topic['replyTime'] = $time;
			$topicModel->update(array("_id" => $topic['_id']), $topic);
		}
		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
		return $output;
	}
	
	/**
	 * update topic with user contained
	 * @return multitype:unknown
	 */
	function update_post() {
		// Continue Real Operation
		$data = $this->_post_args;
		if (is_array($data) && array_key_exists('_id', $data)) {
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
		}
		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
		return $output;
	}
}
?>