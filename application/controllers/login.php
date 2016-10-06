<?php
require APPPATH . '/libraries/UUID.php';
require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/helpers/jwt_helper.php';

class Login extends REST_Controller {
	
	function auth_post() {
		$output = [];
		$username = $this->post('username');
		$password = $this->post('password');
		$con = new MongoClient ();
		$userModel = $con->selectDB(_DB_NAME)->user;
		$user = $userModel->findOne(['username' => $username]);
		if ($user != null && password_verify($password, $user['password'])) {
			$userInfo = ['_id' => $user['_id'], 'username' => $user['username']];
			if (array_key_exists('role', $user)) {
				$userInfo['role'] = $user['role'];
			}
			$nameOfUser = "";
			if (array_key_exists('titleName', $user)) {
				$userInfo['titleName'] = $user['titleName'];
				$nameOfUser = $nameOfUser . $user['titleName'] . " ";
			}
			if (array_key_exists('firstName', $user)) {
				$userInfo['firstName'] = $user['firstName'];
				$nameOfUser = $nameOfUser . $user['firstName'] . " ";
			}
			if (array_key_exists('middleName', $user)) {
				$userInfo['middleName'] = $user['middleName'];
				$nameOfUser = $nameOfUser . $user['middleName'] . " ";
			}
			if (array_key_exists('lastName', $user)) {
				$userInfo['lastName'] = $user['lastName'];
				$nameOfUser = $nameOfUser . $user['lastName'] . " ";
			}
			$userInfo['fullName'] = trim($nameOfUser);
			$token = UUID::v4();
			$output = ['status' => 1, 'message' => 'successfuly login', 'data' => ['token' => JWT::encode($token, _SECRET_KEY)]];
			$tokenModel = $con->selectDB(_DB_NAME)->token;
			$tokenModel->insert(array('_id' => $token, 'user' => $userInfo, 'time' => time()));
		} else {
			$output = ['status' => 2, 'message' => 'username or password is invalid', 'data' => []];
		}
		echo json_encode($output);
	}
	
	function invalidate_post() {
		$output = [];
		if (array_key_exists ( 'HTTP_X_TOKEN_KEY', $_SERVER )) {
			$this->tokenKey['token'] = $_SERVER['HTTP_X_TOKEN_KEY'];
			$token = JWT::decode($_SERVER['HTTP_X_TOKEN_KEY'], _SECRET_KEY);
			$con = new MongoClient ();
			$tokenModel = $con->selectDB(_DB_NAME)->token;
			$tokenItems = $tokenModel->remove(['_id' => $token]);
			$output = ['status' => 1, 'message' => 'successfuly invalidate', 'data' => []];
		} else {
			$this->output->set_status_header('401');
			$output['status'] = 20000;
			$output['message'] = 'no token found';
		}
		echo json_encode($output);
	}
}
?>