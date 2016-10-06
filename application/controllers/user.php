<?php
require 'baseController.php';

require_once APPPATH . "/libraries/Unirest.php";

class User extends BaseController {
	
	public function __construct()
    {
    	$this->setDoc('user');
    	$this->setObjMap(array(
    			'employee' => 'profile'
    	));
        parent::__construct();
    }
    
    /**
     * Override Create user
     * @return multitype:unknown
     */
    function save_post() {
    	$data = $this->_post_args;
    	if (is_array($data)) {
    		if (!array_key_exists('_id', $data)) {
    			$data['_id'] = UUID::v4();
    		}
    		$password = "" . mt_rand(100000, 999999);
    		$data['password'] = password_hash($password, PASSWORD_BCRYPT);
    		$data['createdBy'] = $this->token['user']['username'];
    		$data['createdTime'] = time();
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$itemModel = $this->db->selectCollection($this->docName);
    		
    		$email_to = $data['email'];
    		$email_subject = 'New User Created';
    		$email_message = "Hi " . $data['firstName'] . ", \r\n\r\nHere is your account detail : \r\n\r\nUsername: " . $data['username'] . "\r\nPassword : " . $password . "\r\n\r\nPlease change password after your first login.";
    		
    		// create email headers
    		$headers = 'From: admin@isiaws.radmond.com'."\r\n".
    				'Reply-To: admin@isiaws.radmond.com'."\r\n" .
    				'X-Mailer: PHP/' . phpversion();
    		$result = mail($email_to, $email_subject, $email_message, $headers);
    		
    		// create chat user in openfire
    		$nameOfUser = "";
    		if (array_key_exists('titleName', $data)) {
    			$nameOfUser = $nameOfUser . $data['titleName'] . " ";
    		}
    		if (array_key_exists('firstName', $data)) {
    			$nameOfUser = $nameOfUser . $data['firstName'] . " ";
    		}
    		if (array_key_exists('middleName', $data)) {
    			$nameOfUser = $nameOfUser . $data['middleName'] . " ";
    		}
    		if (array_key_exists('lastName', $data)) {
    			$nameOfUser = $nameOfUser . $data['lastName'] . " ";
    		}
    		$nameOfUser = trim($nameOfUser);
    		$headers = array("Content-Type" => "application/json");
    		$username = str_replace('@', '_', $data['username']);
    		$body = array("username" => $username, "password" => "password", "name" => $nameOfUser, "email" => $data['email']);
    		Unirest\Request::auth('admin', 'loot1234');
    		$response = Unirest\Request::post("http://" . _CHAT_SERVER . ":9090/plugins/restapi/v1/users", $headers, json_encode($body));
    		// right now to simplify we don't need to check the status of the creation back, just assume it is success
    		$response->code;        // HTTP Status code
    		$response->headers;     // Headers
    		$response->body;        // Parsed body
    		$response->raw_body;    // Unparsed body
    		
    		if ($result) {
    			foreach ($data as $key => $value) {
    				if (is_array($value) && array_key_exists('_id', $value)) {
    					if (array_key_exists($key, $this->objMap)) {
    						$data[$key] = array('$ref' => $this->objMap[$key], '$id' => $value['_id']);
    					} else {
    						$data[$key] = $value['_id'];
    					}
    				}
    			}
    			// unlink prev profile, then link current one
    			$profileModel = $this->db->selectCollection("profile");
    			if (array_key_exists('employee', $data) && !empty($data['employee'])) {
    				$profileModified = $profileModel->findOne(array('_id' => $data['employee']['$id']));
    				if ($profileModified) {
    					$profileModified['user'] = $data['_id'];
    					$profileModel->update(array("_id" => $data['employee']['$id']), $profileModified);
    				}
    			}
    			$itemModel->save($data);
    			$output = ['status' => 1, 'message' => 'Email has been sent to ' . $data['email'] . " with the detail of your account", 'data' => $data];
    		} else {
    			$output = ['status' => 40000, 'message' => 'Email cannot be sent', 'data' => $data];
    		}
    	}
    	
    	return $output;
    }
    
    /**
     * Update User, and update linking to profile also
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
    		// unlink prev profile, then link current one
    		$profileModel = $this->db->selectCollection("profile");
    		if (array_key_exists('employee', $dataModified)) {
	    		$profileModified = $profileModel->findOne(array('_id' => $dataModified['employee']['$id']));
	    		if ($profileModified) {
	    			unset($profileModified['user']);
	    			$profileModel->update(array("_id" => $dataModified['employee']['$id']), $profileModified);
	    		}
    		}
    		if (array_key_exists('employee', $data) && !empty($data['employee'])) {
    			$profileModified = $profileModel->findOne(array('_id' => $data['employee']['$id']));
    			if ($profileModified) {
    				$profileModified['user'] = $data['_id'];
    				$profileModel->update(array("_id" => $data['employee']['$id']), $profileModified);
    			}
    		}
    		foreach ($data as $key => $value) {
    			$dataModified[$key] = $value;
    		}
    		$itemModel->update(array("_id" => $data['_id']), $dataModified);
    		
    	}
    	$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $data];
    	return $output;
    }
    
    /**
     * Reset user password
     * @return multitype:unknown
     */
    function resetPassword_post() {
    	$dataPost = $this->_post_args;
    	$output = ['status' => 50000, 'message' => 'no _id found in post param', 'data' => $dataPost];
    	if (array_key_exists('_id', $dataPost)) {
    		$con = new MongoClient ();
    		$this->db = $con->selectDB(_DB_NAME);
    		$itemModel = $this->db->selectCollection($this->docName);
    		$data = $itemModel->findOne(array('_id' => $dataPost['_id']));
    		
    		$password = "" . mt_rand(100000, 999999);
    		$data['password'] = password_hash($password, PASSWORD_BCRYPT);
    		$data['updatedBy'] = $this->token['user']['username'];
    		$data['updatedTime'] = time();
    		
    		$email_to = $data['email'];
    		$email_subject = "User Password Reset";
    		$email_message = "Hi " . $data['firstName'] . ", \r\n\r\nHere is your new password : \r\n\r\nPassword : " . $password . "\r\n\r\nPlease change password after your first login.";
    		
    		// create email headers
    		$headers = 'From: admin@isiaws.radmond.com'."\r\n".
    				'Reply-To: admin@isiaws.radmond.com'."\r\n" .
    				'X-Mailer: PHP/' . phpversion();
    		$result = mail($email_to, $email_subject, $email_message, $headers);
    		
    		if ($result) {
    			$itemModel->save($data);
    			$output = ['status' => 1, 'message' => 'Email has been sent to ' . $data['email'] . " with the detail of your account", 'data' => $data];
    		} else {
    			$output = ['status' => 40000, 'message' => 'Email cannot be sent', 'data' => $data];
    		}
    	}
    	return $output;
    }
    
    function changePassword_post() {
    	$dataPost = $this->_post_args;
    	$con = new MongoClient ();
    	$this->db = $con->selectDB(_DB_NAME);
    	$itemModel = $this->db->selectCollection($this->docName);
    	$user = $itemModel->findOne(array('_id' => $this->token['user']['_id']));
    	if (password_verify($dataPost['currentPassword'], $user['password'])) {
    		$dataPost['newPassword'] = trim($dataPost['newPassword']);
    		if ($dataPost['newPassword'] && strlen($dataPost['newPassword']) > 0) {
	    		$itemModel->update(array('_id' => $this->token['user']['_id']), array('$set' => array("password" => password_hash($dataPost['newPassword'], PASSWORD_BCRYPT))));
	    		$output = ['status' => 1, 'message' => 'Password successfully changed', 'data' => $this->token['user']];
    		} else {
    			$output = ['status' => 40001, 'message' => 'Current Password is not valid', 'data' => $this->token['user']];
    		}
    	} else {
    		$output = ['status' => 40001, 'message' => 'Current Password is not valid', 'data' => $this->token['user']];
    	}
    	return $output;
    }
	
}
?>