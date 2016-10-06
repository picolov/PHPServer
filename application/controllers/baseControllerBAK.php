<?php
require APPPATH . '/libraries/UUID.php';
require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/helpers/jwt_helper.php';

class BaseController extends REST_Controller {
	
	protected $docName;
	protected $token;
	protected $objMap = array();
	
	function setDoc($docName) {
		$this->docName = $docName;
	}
	
	function setObjMap($objMap) {
		$this->objMap = $objMap;
	}
	
	/**
	 * Generic Get a List of Class Object in Database 
	 * @return multitype:unknown
	 */
	function list_get() {
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
			$data[] = $item;
		}
		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
		return $output;
	}
	
	/**
	 * Generic Get a List of Class Object for current user in Database
	 * @return multitype:unknown
	 */
	function listMine_get() {
		// Continue Real Operation
		$data = [];
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$itemModel = $this->db->selectCollection($this->docName);
		$itemCursor = $itemModel->find(array("user" => $this->token['user']['_id']));
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
	 * Generic Get a Detail of Class Object in Database
	 * @return multitype:unknown
	 */
	function detail_get() {
		// Continue Real Operation
		$data = [];
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$itemModel = $this->db->selectCollection($this->docName);
		$item = $itemModel->findOne(array('_id' => $this->get('_id')));
		foreach($item as $key => $value) {
			$prop = $item[$key];
			if (is_array($prop) && array_key_exists('$ref', $prop) && array_key_exists('$id', $prop)) {
				$innerItemModel = $this->db->selectCollection($prop['$ref']);
				$innerItem = $innerItemModel->findOne(array('_id' => $prop['$id']));
				$item[$key] = $innerItem;
			}
		}
		$data[] = $item;
		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
		return $output;
	}
	
	/**
	 * Generic Create a Class Object in Database
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
		}
		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
		return $output;
	}
	
	/**
	 * Generic Create a Class Object Bind it with User in user field in Database
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
			$data['createdBy'] = $this->token['user']['username'];
			$data['createdTime'] = time();
			$data['user'] = $this->token['user']['_id'];
			$con = new MongoClient ();
			$this->db = $con->selectDB(_DB_NAME);
			$itemModel = $this->db->selectCollection($this->docName);
			$itemModel->save($data);
		}
		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
		return $output;
	}
	
	/**
	 * Generic Update a Class Object in Database
	 * @return multitype:unknown
	 */
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
				$dataModified[$key] = $value;
			}
			$itemModel->update(array("_id" => $data['_id']), $dataModified);
		}
		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
		return $output;
	}
	
	/**
	 * Generic Delete a Class Object in Database
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
		}
		$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $deletedData];
		return $output;
	}
	
	/**
	 * Custom Remap
	 *
	 * Requests are not made to methods directly, the request will be for
	 * an "object". This simply maps the object and method to the correct
	 * Controller method.
	 *
	 * @param string $object_called
	 * @param array  $arguments     The arguments passed to the controller method.
	 */
	public function _remap($object_called, $arguments)
	{
		// Should we answer if not over SSL?
		if (config_item('force_https') and !$this->_detect_ssl()) {
			$this->response(array(config_item('rest_status_field_name') => false, config_item('rest_message_field_name') => 'Unsupported protocol'), 403);
		}
		$url = $object_called;
		$pattern = '/^(.*)\.('.implode('|', array_keys($this->_supported_formats)).')$/';
		$matches = array();
		if (preg_match($pattern, $object_called, $matches)) {
			$object_called = $matches[1];
		}
	
		$controller_method = $object_called.'_'.$this->request->method;
	
		// Do we want to log this method (if allowed by config)?
		$log_method = !(isset($this->methods[$controller_method]['log']) and $this->methods[$controller_method]['log'] == false);
	
		// Use keys for this method?
		$use_key = !(isset($this->methods[$controller_method]['key']) and $this->methods[$controller_method]['key'] == false);
	
		// They provided a key, but it wasn't valid, so get them out of here.
		if (config_item('rest_enable_keys') and $use_key and $this->_allow === false) {
			if (config_item('rest_enable_logging') and $log_method) {
				$this->_log_request();
			}
	
			$this->response(array(config_item('rest_status_field_name') => false, config_item('rest_message_field_name') => 'Invalid API Key '.$this->rest->key), 403);
		}
	
		// Check to see if this key has access to the requested controller.
		if (config_item('rest_enable_keys') and $use_key and !empty($this->rest->key) and !$this->_check_access()) {
			if (config_item('rest_enable_logging') and $log_method) {
				$this->_log_request();
			}
	
			$this->response(array(config_item('rest_status_field_name') => false, config_item('rest_message_field_name') => 'This API key does not have access to the requested controller.'), 401);
		}
	
		// Sure it exists, but can they do anything with it?
		if ( ! method_exists($this, $controller_method)) {
			$this->response(array(config_item('rest_status_field_name') => false, config_item('rest_message_field_name') => 'Unknown method.'), 404);
		}
	
		// Doing key related stuff? Can only do it if they have a key right?
		if (config_item('rest_enable_keys') and !empty($this->rest->key)) {
			// Check the limit
			if (config_item('rest_enable_limits') and !$this->_check_limit($controller_method)) {
				$response = array(config_item('rest_status_field_name') => false, config_item('rest_message_field_name') => 'This API key has reached the hourly limit for this method.');
				$this->response($response, 401);
			}
	
			// If no level is set use 0, they probably aren't using permissions
			$level = isset($this->methods[$controller_method]['level']) ? $this->methods[$controller_method]['level'] : 0;
	
			// If no level is set, or it is lower than/equal to the key's level
			$authorized = $level <= $this->rest->level;
	
			// IM TELLIN!
			if (config_item('rest_enable_logging') and $log_method) {
				$this->_log_request($authorized);
			}
	
			// They don't have good enough perms
			$response = array(config_item('rest_status_field_name') => false, config_item('rest_message_field_name') => 'This API key does not have enough permissions.');
			$authorized or $this->response($response, 401);
		}
	
		// No key stuff, but record that stuff is happening
		else if (config_item('rest_enable_logging') and $log_method) {
			$this->_log_request($authorized = true);
		}
	
		// Pico Custom : Check token auth
		$output = [];
		$con = new MongoClient ();
		if (array_key_exists ( 'HTTP_X_TOKEN_KEY', $_SERVER )) {
			$this->tokenKey['token'] = $_SERVER['HTTP_X_TOKEN_KEY'];
			$tokenKey = JWT::decode($_SERVER['HTTP_X_TOKEN_KEY'], _SECRET_KEY);
			$tokenModel = $con->selectDB(_DB_NAME)->token;
			$tokenItems = $tokenModel->find(['_id' => $tokenKey]);
			if ($tokenItems->count() > 0) {
				$tokenItems->next();
				$this->token = $tokenItems->current();
				if (time() - $this->token["time"] < _TIMEOUT_LIMIT) {
					$this->token["time"] = time();
					$tokenModel->update(array("_id" => $this->token["_id"]), $this->token);
					$data = [];
					// Fire The Actual Processing Method in each controller
					$output = call_user_func_array(array($this, $controller_method), $arguments);
					//$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
				} else {
					$tokenModel->remove(array("_id" => $this->token["_id"]));
					$this->output->set_status_header('401');
					$output['status'] = 20002;
					$output['message'] = 'token timeout';
				}
			} else {
				$this->output->set_status_header('401');
				$output['status'] = 20001;
				$output['message'] = 'token not valid';
			}
		} else {
			$this->output->set_status_header('401');
			$output['status'] = 20000;
			$output['message'] = 'token not found';
		}
		/**
		 * Log Activity
		 */
		if (strtolower($this->request->method) != 'get') {
			$activityModel = $con->selectDB(_DB_NAME)->activityLog;
			$user = ['_id' => null];
			if ($this->token) {
				$user = $this->token['user'];
			}
			$input = $this->_args;
			foreach ($input as $key => $value) {
				// remove hash code generated by jquery
				if ($key == '_') {
					unset($input[$key]);
				}
				if (is_array($value) && array_key_exists('_id', $value)) {
					if (array_key_exists($key, $this->objMap)) {
						$input[$key] = array('$ref' => $this->objMap[$key], '$id' => $value['_id']);
					} else {
						$input[$key] = $value['_id'];
					}
				}
			}
			$mapModel = $con->selectDB(_DB_NAME)->activityMap;
			$urls = explode('/', $this->uri->uri_string());
			$mapCursor = $mapModel->find(array("url" => ($urls[0] . '/' . $urls[1])));
			$mapCursor->limit(1);
			$processedName = $urls[0] . '/' . $urls[1];
			foreach ($mapCursor as $map) {
				$processedName = $map['name'];
				preg_match_all('/{{([0-9A-Za-z_\.]+)}}/', $processedName, $matches);
				foreach($matches[1] as $match)
				{
					$tokens = explode('.', $match);
					$currObj = ['input' => $input, 'output' => $output['data'], 'user' => $user];
					foreach ($tokens as $token) {
						if (isset($currObj[$token])) {
							$currObj = $currObj[$token];
						} else {
							$currObj = "null";
							break;
						}
					}
					$processedName = str_replace('{{'.$match.'}}', $currObj, $processedName);
				}
			}
			$activityModel->save(['_id' => UUID::v4(), 'name' => $processedName, 'url' => $this->uri->uri_string(), 'method' => $this->request->method, 'user' => $user['_id'], 'time' => time(), 'output' => ['status' => $output['status'], 'message' => $output['message']] ]);
		}
		echo json_encode($output);
	}
	
}
?>

