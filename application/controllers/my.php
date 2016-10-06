<?php
require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/helpers/jwt_helper.php';
include 'PDFMerger.php';

require_once APPPATH . "/libraries/Jaspersoft/Client/Client.php";
require_once APPPATH . "/libraries/Jaspersoft/Exception/RESTRequestException.php";
require_once APPPATH . "/libraries/Jaspersoft/Tool/RESTRequest.php";
require_once APPPATH . "/libraries/Jaspersoft/Service/JRSService.php";
require_once APPPATH . "/libraries/Jaspersoft/Tool/Util.php";
require_once APPPATH . "/libraries/Jaspersoft/Service/ReportService.php";

class My extends REST_Controller {
	
	function wrapTextMultiline($fieldWidthArray, $text, $maxSize = false) {
		$resultTextArray = array_fill(0, count($fieldWidthArray), '');
		// calculate font size
		$minSize = 6;
		if (strlen($text) == 0) {
			if ($maxSize) {
				$size = $maxSize;
			} else {
				$size = 10;
			}
		} else {
			$isFit = false;
			$totalWidth = 0;
			foreach ($fieldWidthArray as $lineWidth) {
				$totalWidth = $totalWidth + $lineWidth;
			}
			$size = $this->calculateFontSize($totalWidth, strlen($text), $maxSize);
			while (!$isFit) {
				$strUnfitted = $text;
				for ($i = 0; $i < count($fieldWidthArray); $i++) {
					$lineWidth = $fieldWidthArray[$i];
					$lineCharCount = floor($lineWidth / ($size * 0.617284));
					$idxOfSpacebar = strrpos(substr($strUnfitted, 0, $lineCharCount), ' ') + 1;					
					if ($lineCharCount >= strlen($strUnfitted)) {
						$idxOfSpacebar = strlen($strUnfitted);
					}
					if ($idxOfSpacebar == -1) {
						$idxOfSpacebar = 0;
					} else {
						$resultTextArray[$i] = '<style size="' . $size . '">' . htmlspecialchars(substr($strUnfitted, 0, $idxOfSpacebar)) . '</style>';
						//$resultTextArray[$i] = '.' . substr($strUnfitted, 0, $idxOfSpacebar) . '.';
					}
					$strUnfitted = substr($strUnfitted, $idxOfSpacebar);
				}
				if (strlen($strUnfitted) == 0) {
					$isFit = true;
				} else {
					$size--;
					if ($size <= $minSize) {
						$isFit = true;
					}
				}
			}
		}
		return $resultTextArray;
	}
	
	function calculateFontSize($fieldWidth, $charCount, $maxSize = false) {
		//return floor(($fieldSize / $charCount) * (1.62));
		$minSize = 6;
		if ($charCount == 0) {
			if ($maxSize) {
				$size = $maxSize;
			} else {
				$size = 10;
			}
		} else {
			$size = floor(($fieldWidth / $charCount) * 1.62);
			if ($maxSize) {
				if ($size > $maxSize) {
					$size = $maxSize;
				}
			}
		}
		if ($size < $minSize) {
			$size = $minSize;
		}
		return $size;
	}
	
	function wrapText($fieldWidth, $text, $maxSize = false) {
		$size = $this->calculateFontSize($fieldWidth, strlen($text), $maxSize);
		return '<style size="' . $size . '">' . htmlspecialchars($text) . '</style>';
	}

	function test_get() {
		error_log('========================================================= TEST ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
	
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);		
	
		$controls = array(
				'param001' => $this->wrapText(100, 'every son make their own life, and has been the same with me and everyone else', 10),
				'param002' => $this->wrapText(200, 'every son make their own life, and has been the same with me and everyone else', 10),
				'param003' => $this->wrapText(300, 'every son make their own life, and has been the same with me and everyone else', 10),
		);
	
		$report = $c->reportService()->runReport('/Test/001', 'pdf', null, null, $controls);
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=Test_' . date('h_i_s') . '.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
	
	}
	
	function detailTransferPdf_get() {
		error_log('========================================================= detailTransferPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
		
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		
		$objModel = $this->db->selectCollection('OutgoingToolsTransfer');
		$obj = $objModel->findOne(array('_id' => $this->get('_id')));
// 		$job = null;
// 		if ($obj == null) {
// 			$obj = $objModel->findOne(array('job' => $this->get('_id')));
// 			$jobModel = $this->db->selectCollection('Job');
// 			$job = $jobModel->findOne(array('_id' => $this->get('_id')));
// 		}

// 		$controls = array();
// 		foreach ($obj as $key => $item) {
// 			$controls[$key] = (string)$item;
// 		}
		
		
// 		foreach ($controls as $key => $item) {
// 			error_log($key . ' ====>> ' . $item . ' = ' . gettype($item));
// 		}
		
// 		$controls['date'] = key_exists('date', $obj) ? gmdate("m/d/y", $obj['date']) : '';
// 		$controls['createdTime'] = key_exists('createdTime', $obj) ? gmdate("m/d/y", $obj['createdTime']) : '';
// 		$controls['qty1'] = '1';
// 		$controls['qty2'] = '1';
// 		$controls['qty3'] = '1';
		
		$controls = array(
				'date'  => key_exists('date', $obj) ? gmdate("m/d/y", $obj['date']) : '',
				'createdTime'  => key_exists('createdTime', $obj) ? gmdate("m/d/y", $obj['createdTime']) : '',

// 				'source' => key_exists('source', $obj) ? $obj['source'] : '',
				
				'addr1' => $obj['source'] == 0 ? 'X' : '',
				'addr2' => $obj['source'] == 0 ? '' : 'X',
				
				'shipTo1' => key_exists('shipTo1', $obj) ? $obj['shipTo1'] : '', //27
				'shipTo2' => key_exists('shipTo2', $obj) ? $obj['shipTo2'] : '', // 27
				'shipTo3' => key_exists('shipTo3', $obj) ? $obj['shipTo3'] : '',
				'shipBy' => key_exists('shipBy', $obj) ? $obj['shipBy'] : '',
				'requestBy' => key_exists('requestBy', $obj) ? $obj['requestBy'] : '',
				'carrier' => key_exists('carrier', $obj) ? $obj['carrier'] : '',
				'customer' => key_exists('customer', $obj) ? $obj['customer'] : '',
				'comments_1' => (key_exists('comment', $obj) && strlen($obj['comment']) > 0) ? substr($obj['comment'], 0, 71) : '',
				'comments_2' => (key_exists('comment', $obj) && strlen($obj['comment']) > 71) ? substr($obj['comment'], 71, 200) : '',
				
				'rta1' => key_exists('rta1', $obj) ? $obj['rta1'] : '',
				'rta2' => key_exists('rta2', $obj) ? $obj['rta2'] : '',
				'rta3' => key_exists('rta3', $obj) ? $obj['rta3'] : '',
				'rta4' => key_exists('rta4', $obj) ? $obj['rta4'] : '',
				'rta5' => key_exists('rta5', $obj) ? $obj['rta5'] : '',
				'rta6' => key_exists('rta6', $obj) ? $obj['rta6'] : '',
				'rta7' => key_exists('rta7', $obj) ? $obj['rta7'] : '',
				'rta8' => key_exists('rta8', $obj) ? $obj['rta8'] : '',
				'rta9' => key_exists('rta9', $obj) ? $obj['rta9'] : '',
				'rta10' => key_exists('rta10', $obj) ? $obj['rta10'] : '',
				'rta11' => key_exists('rta11', $obj) ? $obj['rta11'] : '',
				'rta12' => key_exists('rta12', $obj) ? $obj['rta12'] : '',
				'rta13' => key_exists('rta13', $obj) ? $obj['rta13'] : '',
				'rta14' => key_exists('rta14', $obj) ? $obj['rta14'] : '',
				'desc1' => key_exists('desc1', $obj) ? $obj['desc1'] : '',
				'desc2' => key_exists('desc2', $obj) ? $obj['desc2'] : '',
				'desc3' => key_exists('desc3', $obj) ? $obj['desc3'] : '',
				'desc4' => key_exists('desc4', $obj) ? $obj['desc4'] : '',
				'desc5' => key_exists('desc5', $obj) ? $obj['desc5'] : '',
				'desc6' => key_exists('desc6', $obj) ? $obj['desc6'] : '',
				'desc7' => key_exists('desc7', $obj) ? $obj['desc7'] : '',
				'desc8' => key_exists('desc8', $obj) ? $obj['desc8'] : '',
				'desc9' => key_exists('desc9', $obj) ? $obj['desc9'] : '',
				'desc10' => key_exists('desc10', $obj) ? $obj['desc10'] : '',
				'desc11' => key_exists('desc11', $obj) ? $obj['desc11'] : '',
				'desc12' => key_exists('desc12', $obj) ? $obj['desc12'] : '',
				'desc13' => key_exists('desc13', $obj) ? $obj['desc13'] : '',
				'desc14' => key_exists('desc14', $obj) ? $obj['desc14'] : '',
				'reason1' => key_exists('reason1', $obj) ? $obj['reason1'] : '',
				'reason2' => key_exists('reason2', $obj) ? $obj['reason2'] : '',
				'reason3' => key_exists('reason3', $obj) ? $obj['reason3'] : '',
				'reason4' => key_exists('reason4', $obj) ? $obj['reason4'] : '',
				'reason5' => key_exists('reason5', $obj) ? $obj['reason5'] : '',
				'reason6' => key_exists('reason6', $obj) ? $obj['reason6'] : '',
				'reason7' => key_exists('reason7', $obj) ? $obj['reason7'] : '',
				'reason8' => key_exists('reason8', $obj) ? $obj['reason8'] : '',
				'reason9' => key_exists('reason9', $obj) ? $obj['reason9'] : '',
				'reason10' => key_exists('reason10', $obj) ? $obj['reason10'] : '',
				'reason11' => key_exists('reason11', $obj) ? $obj['reason11'] : '',
				'reason12' => key_exists('reason12', $obj) ? $obj['reason12'] : '',
				'reason13' => key_exists('reason13', $obj) ? $obj['reason13'] : '',
				'reason14' => key_exists('reason14', $obj) ? $obj['reason14'] : '',
				'serial1' => key_exists('serial1', $obj) ? $obj['serial1'] : '',
				'serial2' => key_exists('serial2', $obj) ? $obj['serial2'] : '',
				'serial3' => key_exists('serial3', $obj) ? $obj['serial3'] : '',
				'serial4' => key_exists('serial4', $obj) ? $obj['serial4'] : '',
				'serial5' => key_exists('serial5', $obj) ? $obj['serial5'] : '',
				'serial6' => key_exists('serial6', $obj) ? $obj['serial6'] : '',
				'serial7' => key_exists('serial7', $obj) ? $obj['serial7'] : '',
				'serial8' => key_exists('serial8', $obj) ? $obj['serial8'] : '',
				'serial9' => key_exists('serial9', $obj) ? $obj['serial9'] : '',
				'serial10' => key_exists('serial10', $obj) ? $obj['serial10'] : '',
				'serial11' => key_exists('serial11', $obj) ? $obj['serial11'] : '',
				'serial12' => key_exists('serial12', $obj) ? $obj['serial12'] : '',
				'serial13' => key_exists('serial13', $obj) ? $obj['serial13'] : '',
				'serial14' => key_exists('serial14', $obj) ? $obj['serial14'] : '',
				'qty1' => key_exists('qty1', $obj) ? $obj['qty1'] : '',
				'qty2' => key_exists('qty2', $obj) ? $obj['qty2'] : '',
				'qty3' => key_exists('qty3', $obj) ? $obj['qty3'] : '',
				'qty4' => key_exists('qty4', $obj) ? $obj['qty4'] : '',
				'qty5' => key_exists('qty5', $obj) ? $obj['qty5'] : '',
				'qty6' => key_exists('qty6', $obj) ? $obj['qty6'] : '',
				'qty7' => key_exists('qty7', $obj) ? $obj['qty7'] : '',
				'qty8' => key_exists('qty8', $obj) ? $obj['qty8'] : '',
				'qty9' => key_exists('qty9', $obj) ? $obj['qty9'] : '',
				'qty10' => key_exists('qty10', $obj) ? $obj['qty10'] : '',
				'qty11' => key_exists('qty11', $obj) ? $obj['qty11'] : '',
				'qty12' => key_exists('qty12', $obj) ? $obj['qty12'] : '',
				'qty13' => key_exists('qty13', $obj) ? $obj['qty13'] : '',
				'qty14' => key_exists('qty14', $obj) ? $obj['qty14'] : ''
		
		);
		
// 		foreach ($obj as $key => $item) {
// 			$controls[$key] = $item;
// 		}
		
		$report = $c->reportService()->runReport('/reports/isi/equipmentTransferSheet', 'pdf', null, null, $controls);
		
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=EquipmentTransferSheet.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
		
		echo $report;
		
	}
	
	function detailExpensePdf_get() {
		error_log('========================================================= detailExpensePdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
		
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$expenseReportModel = $this->db->selectCollection('OutgoingToolsExpense');
		$expenseReport = $expenseReportModel->findOne(array('_id' => $this->get('_id')));
		
		$userModel = $this->db->selectCollection('user');
		$user = $userModel->findOne(array('_id' => $expenseReport['user']));
		
		$profileModel = $this->db->selectCollection('profile');
		$profile = $profileModel->findOne(array('user' => $expenseReport['user']));
		
		//error_log("==============>>>> user name " . $user['username']);
		
// 		$jobModel = $this->db->selectCollection('Job');
// 		$job = $jobModel->findOne(array('_id' => $this->get('_id')));

		$address = "";
// 		if (key_exists('street', $profile)) {
// 			$address = $address . $profile['street'] . '\n';
// 		}
		if (key_exists('city', $profile)) {
			$address = $address . $profile['city'] . ' ';
		}
		if (key_exists('state', $profile)) {
			$address = $address . $profile['state'] . ' ';
		}
		if (key_exists('zip', $profile)) {
			$address = $address . $profile['zip'] . ' ';
		}
		if (strlen($address) > 0) {
			$address = substr($address, 0, strlen($address) - 1);
		}
		
		$controls = array();
		$totalMeal = 0;
		$totalParking = 0;
		$totalPhone = 0;
		$totalEntertain = 0;
		$totalAirfare = 0;
		$totalRental = 0;
		$totalMotel = 0;
		$totalMeeting = 0;
		$totalGas = 0;
		$totalSup = 0;
		$totalMisc = 0;
		$totalAmount = 0;
		$totalExpenseAbove = 0;
		$totalForReport = 0;
		
		foreach ($expenseReport as $key => $item) {
			$controls[$key] = $item;
			if (('date' === "" || strrpos($key, 'date', -strlen($key)) !== FALSE) ||
					('specialDate' === "" || strrpos($key, 'specialDate', -strlen($key)) !== FALSE)) {
				$controls[$key] = key_exists($key, $expenseReport) ? gmdate("m/d/y", $expenseReport[$key]) : '';
			} else {
				if ('meal' === "" || strrpos($key, 'meal', -strlen($key)) !== FALSE ) {
					$totalMeal = $totalMeal + $expenseReport[$key];
					$controls['totalMeal'] = '$ ' . number_format($totalMeal, 2, '.', ',');
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				} else if ('parking' === "" || strrpos($key, 'parking', -strlen($key)) !== FALSE ) {
					$totalParking = $totalParking + $expenseReport[$key];
					$controls['totalParking'] = '$ ' . number_format($totalParking, 2, '.', ',');
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				} else if ('phone' === "" || strrpos($key, 'phone', -strlen($key)) !== FALSE ) {
					$totalPhone = $totalPhone + $expenseReport[$key];
					$controls['totalPhone'] = '$ ' . number_format($totalPhone, 2, '.', ',');
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				} else if ('entertain' === "" || strrpos($key, 'entertain', -strlen($key)) !== FALSE ) {
					$totalEntertain = $totalEntertain + $expenseReport[$key];
					$controls['totalEntertain'] = '$ ' . number_format($totalEntertain, 2, '.', ',');
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				} else if ('airfare' === "" || strrpos($key, 'airfare', -strlen($key)) !== FALSE ) {
					$totalAirfare = $totalAirfare + $expenseReport[$key];
					$controls['totalAirfare'] = '$ ' . number_format($totalAirfare, 2, '.', ',');
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				} else if ('rental' === "" || strrpos($key, 'rental', -strlen($key)) !== FALSE ) {
					$totalRental = $totalRental + $expenseReport[$key];
					$controls['totalRental'] = '$ ' . number_format($totalRental, 2, '.', ',');
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				} else if ('motel' === "" || strrpos($key, 'motel', -strlen($key)) !== FALSE ) {
					$totalMotel = $totalMotel + $expenseReport[$key];
					$controls['totalMotel'] = '$ ' . number_format($totalMotel, 2, '.', ',');
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				} else if ('meeting' === "" || strrpos($key, 'meeting', -strlen($key)) !== FALSE ) {
					$totalMeeting = $totalMeeting + $expenseReport[$key];
					$controls['totalMeeting'] = '$ ' . number_format($totalMeeting, 2, '.', ',');
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				} else if ('gas' === "" || strrpos($key, 'gas', -strlen($key)) !== FALSE ) {
					$totalGas = $totalGas + $expenseReport[$key];
					$controls['totalGas'] = '$ ' . number_format($totalGas, 2, '.', ',');
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				} else if ('sup' === "" || strrpos($key, 'sup', -strlen($key)) !== FALSE ) {
					$totalSup = $totalSup + $expenseReport[$key];
					$controls['totalSup'] = '$ ' . number_format($totalSup, 2, '.', ',');
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				} else if ('misc' === "" || strrpos($key, 'misc', -strlen($key)) !== FALSE ) {
					$totalMisc = $totalMisc + $expenseReport[$key];
					$controls['totalMisc'] = '$ ' . number_format($totalMisc, 2, '.', ',');
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				} else if ('specialAmount' === "" || strrpos($key, 'specialAmount', -strlen($key)) !== FALSE ) {
					$totalAmount = $totalAmount + $expenseReport[$key];
					$controls['totalAmount'] = '$ ' . number_format($totalAmount, 2, '.', ',');
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				} else if ('advance' === "" || strrpos($key, 'advance', -strlen($key)) !== FALSE) {
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				}  else if ('returned' === "" || strrpos($key, 'returned', -strlen($key)) !== FALSE) {
					$controls[$key] = '$ ' . number_format($expenseReport[$key], 2, '.', ',');
				}
				
			}
			
		}
		$totalExpenseAbove = $totalMeal +$totalParking + $totalPhone +$totalEntertain + $totalAirfare + $totalRental + $totalMotel + $totalMeeting + $totalGas + $totalSup + $totalMisc ;
		$controls['totalExpenseAbove'] = '$ ' . number_format($totalExpenseAbove, 2, '.', ',');
		$controls['totalForReport'] = '$ ' . number_format($totalExpenseAbove + $totalAmount + (key_exists('advance', $expenseReport) ? $expenseReport['advance'] : 0) + (key_exists('returned', $expenseReport) ? $expenseReport['returned'] : 0), 2, '.', ',');
		$controls['fromDate'] = key_exists('fromDate', $expenseReport) ? gmdate("m/d/y", $expenseReport['fromDate']) : '';
		$controls['toDate'] = key_exists('toDate', $expenseReport) ? gmdate("m/d/y", $expenseReport['toDate']) : '';
		$controls['submittedBy'] = $user['firstName'] . " " . $user['middleName']  . " " . $user['lastName'];
		
		$controls['title'] = key_exists('positionName', $profile) ? $profile['positionName'] : '';
		$controls['address_1'] = key_exists('street', $profile) ? $profile['street'] : '';
		$controls['address_2'] = $address;
		
		$report = $c->reportService()->runReport('/reports/isi/expenseReport', 'pdf', null, null, $controls);
		
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=ExpenseReport.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
		
		echo $report;
	}
	
	function detailJarSetsOpenHolePdf_get() {
		error_log('========================================================= detailJarSetsOpenHolePdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$jarSetsOpenHoleModel = $this->db->selectCollection('JarSetsOpenHole');
		$jarSetsOpenHole = $jarSetsOpenHoleModel->findOne(array('job' => $this->get('_id')));
		
		$outgoingToolsCasedInspectionModel = $this->db->selectCollection('OutgoingToolsCasedInspection');
		$outgoingToolsCasedInspection = $outgoingToolsCasedInspectionModel->findOne(array('job' => $this->get('_id')));
		
		$jobModel = $this->db->selectCollection('Job');
		$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		
		if (key_exists('serviceCompany', $job) && $job['serviceCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['serviceCompany']['$ref']);
			$wirelineCompany = $innerItemModel->findOne(array('_id' => $job['serviceCompany']['$id']));
		} else {
			$wirelineCompany = array();
		}
		
		if (key_exists('oilCompany', $job) && $job['oilCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
			$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
		} else {
			$oilCompany = array();
		}
		
			
		$controls = array(
				'date' => $outgoingToolsCasedInspection && key_exists('sentDate', $outgoingToolsCasedInspection) ? $this->wrapText(236, gmdate("m/d/y", $outgoingToolsCasedInspection['sentDate']), 10) : '',
				'rta' => key_exists('uid', $job) ? $this->wrapText(212, $job['uid'], 10) : '',
				'jarSn' => key_exists('jarSN', $jarSetsOpenHole) ? $this->wrapText(241, $jarSetsOpenHole['jarSN'], 10) : '',
				'wirelineCo' => key_exists('name', $wirelineCompany) ? $this->wrapText(207, $wirelineCompany['name'], 10) : '',
				'oilGasCo' => key_exists('name', $oilCompany) ? $this->wrapText(204, $oilCompany['name'], 10) : '',
				'field' => key_exists('field', $job) ? $this->wrapText(238, $job['field'], 10) : '',
				'lease' => key_exists('lease', $job) ? $this->wrapText(212, $job['lease'], 10) : '',
				'well' => key_exists('wellName', $job) ? $this->wrapText(210, $job['wellName'], 10) : '',
				'rigNameNumber' => key_exists('rigName', $job) ? $this->wrapText(154, $job['rigName'], 10) : ''
		);
		
		$jarSn = '';
		$jarSnArr = array();
		$runDateList = array();
		
		foreach ($jarSetsOpenHole as $key => $item) {
			//$controls[$key] = $item;
			//error_log($key . '-->>' . $item);
			if (strrpos($key, 'fromDate', -strlen($key)) !== FALSE) {
				array_push($runDateList, $item);
			} else if (strrpos($key, 'toDate', -strlen($key)) !== FALSE) {
				array_push($runDateList, $item);
			} else if (strrpos($key, 'wob', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(46, '' . $item, 10);
			} else if (strrpos($key, 'msp', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(46, '' . $item, 10);
			} else if (strrpos($key, 'wp', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(72, '' . $item, 10);
			} else if (strrpos($key, 'allowance', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(353, '' . $item, 10);
			} else if (strrpos($key, 'wia', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(407, '' . $item, 10);
			} else if (strrpos($key, 'recommended', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(280, '' . $item, 10);
			} else if (strrpos($key, 'actualJar', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(388, '' . $item, 10);
			} else if (strrpos($key, 'wif', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(46, $item, 10);
				$idx = explode('_', $key)[1];
				$sum1 = $item + 2000;
				$controls[$key . 'a'] = $this->wrapText(198, '' . $sum1, 10);
				$sum2 = $jarSetsOpenHole['msp_' . $idx] - ($jarSetsOpenHole['wob_' . $idx] - $jarSetsOpenHole['wif_' . $idx]) - 2000;
				$controls['msp_' . $idx . 'a'] = $this->wrapText(63, '' . $sum2, 10);
				$sum3 = $jarSetsOpenHole['wp_' . $idx] - 2000;
				$controls['wp_' . $idx . 'a'] = $this->wrapText(275, '' . $sum3, 10);
				$controls['maximum_' . $idx] = $this->wrapText(220, '' . min($sum1, $sum2, $sum3), 10);
			} else if (strrpos($key, 'jarSerial', -strlen($key)) !== FALSE) {
				//$jarSn = $jarSn . $item . ', ';
				$idx = explode('_', $key)[1];
				
				$jarToken = explode(' - ', $item, 2);
				$jarSnArr[] = count($jarToken) > 1 ? $jarToken[1] : $jarToken[0];
				$runTitle = count($jarToken) > 1 ? $jarToken[1] : $jarToken[0];
				if (key_exists('impactPro_' . $idx, $jarSetsOpenHole) && $jarSetsOpenHole['impactPro_' . $idx] == "1" && key_exists('impactProSlotSize_' . $idx, $jarSetsOpenHole)) {
					$slotSizeId = $jarSetsOpenHole['impactProSlotSize_' . $idx];
					if ($slotSizeId != 4) {
						$innerItemModel = $this->db->selectCollection('ImpactProSlotSize');
						$slotSize = $innerItemModel->findOne(array('_id' => $slotSizeId));
						$runTitle = $runTitle . "/Impact Pro Slot " . $slotSize['name'];
					}
				}
				$controls['runTitle_' . $idx] = $this->wrapText(400, '' . $runTitle, 13);
			} else if (strrpos($key, 'ifPreset', -strlen($key)) !== FALSE) {
				$idx = explode('_', $key)[1];
				$presetArr = $this->wrapTextMultiline(array(215, 523), $item, 10);
				$controls['ifPreset_' . $idx . '_1'] = $presetArr[0];
				$controls['ifPreset_' . $idx . '_2'] = $presetArr[1];
			} else if (strrpos($key, 'run', -strlen($key)) !== FALSE) {
				$controls[$key] = $item;
			} else if (strrpos($key, 'signatureName', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(202, '' . $item, 10);
			} else if (strrpos($key, 'techName', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(202, '' . $item, 10);
			} else if (strrpos($key, 'wlcoName', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(202, '' . $item, 10);
			} else if (strrpos($key, 'oilName', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(202, '' . $item, 10);
			} else if (strrpos($key, 'signatureDate', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(96, gmdate("m/d/y", intval($jarSetsOpenHole[$key])), 10);
			} else if (strrpos($key, 'techDate', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(96, gmdate("m/d/y", intval($jarSetsOpenHole[$key])), 10);
			} else if (strrpos($key, 'wlcoDate', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(96, gmdate("m/d/y", intval($jarSetsOpenHole[$key])), 10);
			} else if (strrpos($key, 'oilDate', -strlen($key)) !== FALSE) {
				$controls[$key] = $this->wrapText(96, gmdate("m/d/y", intval($jarSetsOpenHole[$key])), 10);
			} else {
				//$controls[$key] = $item;
			}
		}
		
		$jarSnArr = array_values(array_unique($jarSnArr));
		foreach ($jarSnArr as $jarSnItem) {
			$jarSn = $jarSn . $jarSnItem . '/';
		}
		if (strlen($jarSn) > 0) {
			$jarSn = substr($jarSn, 0, strlen($jarSn) - 1);
		}
		if (sizeof($runDateList) > 0) {
			$controls['date'] = $this->wrapText(236, gmdate("m/d/y", min($runDateList)) . ' - ' . gmdate("m/d/y", max($runDateList)), 10);
		}
		$controls['jarSn'] = $this->wrapText(228, $jarSn, 10);
		
		error_log('finish iterating value from model');
		
		$runCount = $jarSetsOpenHole['itemCount'];
		//ob_start();
		$pdf = new PDFMerger;
		error_log('initiate PDF Merger');
		//error_log('-----------------control : \n' . var_export($controls, true));
		$report = $c->reportService()->runReport('/reports/isi/openHoleJarApproval1', 'pdf', null, null, $controls);
		$fp = fopen($jarSetsOpenHole['_id'] . 'page1' . '.pdf', 'w');
		fwrite($fp, $report);
		fclose($fp);
		$pdf->addPDF($jarSetsOpenHole['_id'] . 'page1' . '.pdf', '1');
		error_log('create PDF page 1');
		
		if ($runCount > 1) {
			$report = $c->reportService()->runReport('/reports/isi/openHoleJarApproval2', 'pdf', null, null, $controls);
			$fp = fopen($jarSetsOpenHole['_id'] . 'page2', 'w');
			fwrite($fp, $report);
			fclose($fp);
			$pdf->addPDF($jarSetsOpenHole['_id'] . 'page2', '1');
			
			error_log('create PDF page 2');
		}
		
		
		if ($runCount > 3) { 
			error_log('create PDF page 2');
			for ($x = 4; $x <= $runCount; $x = $x+2) {
				//error_log('=====loop=========>>> ' . $x);
				// Because we use the same template for page 2, we need to remove previous value (run no2 and no3)
				foreach ($controls as $key => $item) {
					if ('_2' === "" || (($temp = strlen($key) - strlen('_2')) >= 0 && strpos($key, '_2', $temp) !== FALSE)) {
						// don't delete if this is part of isPreset_X_2
						if (substr($key, strlen($key) - 4, 1) != '_') {
							unset($controls[$key]);
						}
					}
					if ('_2a' === "" || (($temp = strlen($key) - strlen('_2a')) >= 0 && strpos($key, '_2a', $temp) !== FALSE)) {
						unset($controls[$key]);
					}
					if ('_3' === "" || (($temp = strlen($key) - strlen('_3')) >= 0 && strpos($key, '_3', $temp) !== FALSE)) {
						unset($controls[$key]);
					}
					if ('_3a' === "" || (($temp = strlen($key) - strlen('_3a')) >= 0 && strpos($key, '_3a', $temp) !== FALSE)) {
						unset($controls[$key]);
					}
				}
				// Copy value to run no2 and no3
				foreach ($controls as $key => $item) {
					//error_log('Copyiing==============>>> ' . $key . ':' . $item);
					if ('_' . $x === "" || (($temp = strlen($key) - strlen('_' . $x)) >= 0 && strpos($key, '_' . $x, $temp) !== FALSE)) {
						$controls[substr($key, 0, -2) . '_2'] = $controls[$key];
					}
					if ('_' . $x . 'a' === "" || (($temp = strlen($key) - strlen('_' . $x . 'a')) >= 0 && strpos($key, '_' . $x . 'a', $temp) !== FALSE)) {
						$controls[substr($key, 0, -3) . '_2a'] = $controls[$key];
					}
					if($x < $runCount) {
						if ('_' . ($x+1) === "" || (($temp = strlen($key) - strlen('_' . ($x+1))) >= 0 && strpos($key, '_' . ($x+1), $temp) !== FALSE)) {
							$controls[substr($key, 0, -2) . '_3'] = $controls[$key];
						}
						if ('_' . ($x+1) . 'a' === "" || (($temp = strlen($key) - strlen('_' . ($x+1) . 'a')) >= 0 && strpos($key, '_' . ($x+1) . 'a', $temp) !== FALSE)) {
							$controls[substr($key, 0, -3) . '_3a'] = $controls[$key];
						}
					}
				}
				if (key_exists('ifPreset_' . $x . '_1', $controls)) {
					$controls['ifPreset_2_1'] = $controls['ifPreset_' . $x . '_1'];
				} else {
					$controls['ifPreset_2_1'] = '';
				}
				if (key_exists('ifPreset_' . $x . '_2', $controls)) {
					$controls['ifPreset_2_2'] = $controls['ifPreset_' . $x . '_2'];
				} else {
					$controls['ifPreset_2_2'] = '';
				}
				if (key_exists('ifPreset_' . ($x+1) . '_1', $controls)) {
					$controls['ifPreset_3_1'] = $controls['ifPreset_' . ($x+1) . '_1'];
				} else {
					$controls['ifPreset_3_1'] = '';
				}
				if (key_exists('ifPreset_' . ($x+1) . '_2', $controls)) {
					$controls['ifPreset_3_2'] = $controls['ifPreset_' . ($x+1) . '_2'];
				} else {
					$controls['ifPreset_3_2'] = '';
				}
				$report = $c->reportService()->runReport('/reports/isi/openHoleJarApproval2', 'pdf', null, null, $controls);
				$fp = fopen($jarSetsOpenHole['_id'] . 'page' . $x . '.pdf', 'w');
				fwrite($fp, $report);
				fclose($fp);
				$pdf->addPDF($jarSetsOpenHole['_id'] . 'page' . $x . '.pdf', '1');
				}
			}
		ob_end_clean();
		try {
			$pdf->merge('download', 'JarSetsOpenHole.pdf');
		} catch(Exception $e) {
			error_log('ERROR');	
			error_log($e);	
		}

	}
	//TESTED
	function detailJSEAPdf_get() {
		error_log('========================================================= detailJSEAPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
		
		error_log('JSEA _id' . $this->get('_id'));
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$onSiteJSEAModel = $this->db->selectCollection('OnSiteJSEA');
		$onSiteJSEA = $onSiteJSEAModel->findOne(array('_id' => $this->get('_id')));
		
		$jobModel = $this->db->selectCollection('Job');
		$job = $jobModel->findOne(array('_id' => $onSiteJSEA['job']));
		
		$onSiteDailyJobModel = $this->db->selectCollection('OnSiteDailyJob');
		$onSiteDailyJob = $onSiteDailyJobModel->findOne(array('job' => $onSiteJSEA['job']));
		
		if (key_exists('oilCompany', $job) && $job['oilCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
			$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
		} else {
			$oilCompany = array();
		}
		
		error_log('===== start preparing data ...' . $job['rigName']);
		$controls = array(
// 				'onSiteJSEA' => key_exists('uid', $job) ? $job['uid'] : '',
		
				'date' => key_exists('date', $onSiteJSEA) ? $this->wrapText(43, gmdate("m/d/y", $onSiteJSEA['date']), 7) : '',
				'company' => key_exists('name', $oilCompany) ? $oilCompany['name'] : '',
				'rigName' => key_exists('rigName', $job) ? $job['rigName'] : '',
				'rev' => key_exists('rev', $onSiteJSEA) ? $this->wrapText(38, $onSiteJSEA['rev'], 7) : '',
				'version' => key_exists('version', $onSiteJSEA) ? $this->wrapText(100, $onSiteJSEA['version'], 7) : '',
				'location' => $this->wrapText(158, (key_exists('name', $oilCompany) ? $oilCompany['name'] : '') . ' / ' . (key_exists('rigName', $job) ? $job['rigName'] : ''), 7),
				'djrNo' => ($onSiteDailyJob != null && key_exists('ticketNo', $onSiteDailyJob)) ? $this->wrapText(47, $onSiteDailyJob['ticketNo'], 7) : '',
				'technician' => key_exists('technician', $onSiteJSEA) ? $this->wrapText(132, $onSiteJSEA['technician'], 7) : '',
				'workActivityBy' => key_exists('workActivity', $onSiteJSEA) ? $this->wrapText(297, $onSiteJSEA['workActivity'], 7) : '',
				'approvedBy' => key_exists('approvedBy', $onSiteJSEA) ? $this->wrapText(492, $onSiteJSEA['approvedBy'], 7) : '',
				'signature' => key_exists('signature', $onSiteJSEA) ? $this->wrapText(482, $onSiteJSEA['signature'], 7) : '',
				//'assess1' => $onSiteJSEA['assess1'] ? 'X' : '',
				'assess1' => key_exists('assess1', $onSiteJSEA) ? $onSiteJSEA['assess1'] ? 'X' : '' : '',
				'assess2' => key_exists('assess2', $onSiteJSEA) ? $onSiteJSEA['assess2'] ? 'X' : '' : '',
				'assess3' => key_exists('assess3', $onSiteJSEA) ? $onSiteJSEA['assess3'] ? 'X' : '' : '',
				'assess4' => key_exists('assess4', $onSiteJSEA) ? $onSiteJSEA['assess4'] ? 'X' : '' : '',
				'assess5' => key_exists('assess5', $onSiteJSEA) ? $onSiteJSEA['assess5'] ? 'X' : '' : '',
				'assess6' => key_exists('assess6', $onSiteJSEA) ? $onSiteJSEA['assess6'] ? 'X' : '' : '',
				'assess7' => key_exists('assess7', $onSiteJSEA) ? $onSiteJSEA['assess7'] ? 'X' : '' : '',
				'assess8' => key_exists('assess8', $onSiteJSEA) ? $onSiteJSEA['assess8'] ? 'X' : '' : '',
				'assess9' => key_exists('assess9', $onSiteJSEA) ? $onSiteJSEA['assess9'] ? 'X' : '' : '',
				'assess10' => key_exists('assess10', $onSiteJSEA) ? $onSiteJSEA['assess10'] ? 'X' : '' : '',
				'assess11' => key_exists('assess11', $onSiteJSEA) ? $onSiteJSEA['assess11'] ? 'X' : '' : '',
				'assess12' => key_exists('assess12', $onSiteJSEA) ? $onSiteJSEA['assess12'] ? 'X' : '' : '',
				'assess13' => key_exists('assess13', $onSiteJSEA) ? $onSiteJSEA['assess13'] ? 'X' : '' : '',
				'assess14' => key_exists('assess14', $onSiteJSEA) ? $onSiteJSEA['assess14'] ? 'X' : '' : '',
				'assess15' => key_exists('assess15', $onSiteJSEA) ? $onSiteJSEA['assess15'] ? 'X' : '' : '',
				'assess16' => key_exists('assess16', $onSiteJSEA) ? $onSiteJSEA['assess16'] ? 'X' : '' : '',
				'assess17' => key_exists('assess17', $onSiteJSEA) ? $onSiteJSEA['assess17'] ? 'X' : '' : '',
				'assess18' => key_exists('assess18', $onSiteJSEA) ? $onSiteJSEA['assess18'] ? 'X' : '' : '',
				'assess19' => key_exists('assess19', $onSiteJSEA) ? $onSiteJSEA['assess19'] ? 'X' : '' : '',
				'assess19Val' => key_exists('assess19Val', $onSiteJSEA) ? $this->wrapText(48, $onSiteJSEA['assess19Val'], 7) : '',
				'assess20' => key_exists('assess20', $onSiteJSEA) ? $onSiteJSEA['assess20'] ? 'X' : '' : '',
				'assess20Val' => key_exists('assess20Val', $onSiteJSEA) ? $this->wrapText(48, $onSiteJSEA['assess20Val'], 7) : '',
				'weather' => key_exists('weather', $onSiteJSEA) ? $this->wrapText(49, $onSiteJSEA['weather'], 7) : '',
				'msds' => key_exists('msds', $onSiteJSEA) ? $this->wrapText(49, $onSiteJSEA['msds'], 7) : '',
				'eyewash' => key_exists('eyewash', $onSiteJSEA) ? $this->wrapText(49, $onSiteJSEA['eyewash'], 7) : '',
				'material' => key_exists('material', $onSiteJSEA) ? $this->wrapText(49, $onSiteJSEA['material'], 7) : '',
				'lockOut' => key_exists('lockOut', $onSiteJSEA) ? $this->wrapText(187, $onSiteJSEA['lockOut'], 7) : '',
				'other' => key_exists('other', $onSiteJSEA) ? $this->wrapText(187, $onSiteJSEA['other'], 7) : '',
				'work1' => key_exists('work1', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['work1'], 7) : '',
				'work2' => key_exists('work2', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['work2'], 7) : '',
				'work3' => key_exists('work3', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['work3'], 7) : '',
				'work4' => key_exists('work4', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['work4'], 7) : '',
				'work5' => key_exists('work5', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['work5'], 7) : '',
				'work6' => key_exists('work6', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['work6'], 7) : '',
				'work7' => key_exists('work7', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['work7'], 7) : '',
				'work8' => key_exists('work8', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['work8'], 7) : '',
				'work9' => key_exists('work9', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['work9'], 7) : '',
				'work10' => key_exists('work10', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['work10'], 7) : '',
				'hazard1' => key_exists('hazard1', $onSiteJSEA) ? $this->wrapText(180, $onSiteJSEA['hazard1'], 7) : '',
				'hazard2' => key_exists('hazard2', $onSiteJSEA) ? $this->wrapText(180, $onSiteJSEA['hazard2'], 7) : '',
				'hazard3' => key_exists('hazard3', $onSiteJSEA) ? $this->wrapText(180, $onSiteJSEA['hazard3'], 7) : '',
				'hazard4' => key_exists('hazard4', $onSiteJSEA) ? $this->wrapText(180, $onSiteJSEA['hazard4'], 7) : '',
				'hazard5' => key_exists('hazard5', $onSiteJSEA) ? $this->wrapText(180, $onSiteJSEA['hazard5'], 7) : '',
				'hazard6' => key_exists('hazard6', $onSiteJSEA) ? $this->wrapText(180, $onSiteJSEA['hazard6'], 7) : '',
				'hazard7' => key_exists('hazard7', $onSiteJSEA) ? $this->wrapText(180, $onSiteJSEA['hazard7'], 7) : '',
				'hazard8' => key_exists('hazard8', $onSiteJSEA) ? $this->wrapText(180, $onSiteJSEA['hazard8'], 7) : '',
				'hazard9' => key_exists('hazard9', $onSiteJSEA) ? $this->wrapText(180, $onSiteJSEA['hazard9'], 7) : '',
				'hazard10' => key_exists('hazard10', $onSiteJSEA) ? $this->wrapText(180, $onSiteJSEA['hazard10'], 7) : '',
				'recommend1' => key_exists('recommend1', $onSiteJSEA) ? $this->wrapText(236, $onSiteJSEA['recommend1'], 7) : '',
				'recommend2' => key_exists('recommend2', $onSiteJSEA) ? $this->wrapText(236, $onSiteJSEA['recommend2'], 7) : '',
				'recommend3' => key_exists('recommend3', $onSiteJSEA) ? $this->wrapText(236, $onSiteJSEA['recommend3'], 7) : '',
				'recommend4' => key_exists('recommend4', $onSiteJSEA) ? $this->wrapText(236, $onSiteJSEA['recommend4'], 7) : '',
				'recommend5' => key_exists('recommend5', $onSiteJSEA) ? $this->wrapText(236, $onSiteJSEA['recommend5'], 7) : '',
				'recommend6' => key_exists('recommend6', $onSiteJSEA) ? $this->wrapText(236, $onSiteJSEA['recommend6'], 7) : '',
				'recommend7' => key_exists('recommend7', $onSiteJSEA) ? $this->wrapText(236, $onSiteJSEA['recommend7'], 7) : '',
				'recommend8' => key_exists('recommend8', $onSiteJSEA) ? $this->wrapText(236, $onSiteJSEA['recommend8'], 7) : '',
				'recommend9' => key_exists('recommend9', $onSiteJSEA) ? $this->wrapText(236, $onSiteJSEA['recommend9'], 7) : '',
				'recommend10' => key_exists('recommend10', $onSiteJSEA) ? $this->wrapText(236, $onSiteJSEA['recommend10'], 7) : '',
				'protect1' => key_exists('protect1', $onSiteJSEA) ? $onSiteJSEA['protect1'] ? 'X' : '' : '',
				'protect2' => key_exists('protect2', $onSiteJSEA) ? $onSiteJSEA['protect2'] ? 'X' : '' : '',
				'protect3' => key_exists('protect3', $onSiteJSEA) ? $onSiteJSEA['protect3'] ? 'X' : '' : '',
				'protect4' => key_exists('protect4', $onSiteJSEA) ? $onSiteJSEA['protect4'] ? 'X' : '' : '',
				'protect5' => key_exists('protect5', $onSiteJSEA) ? $onSiteJSEA['protect5'] ? 'X' : '' : '',
				'protect6' => key_exists('protect6', $onSiteJSEA) ? $onSiteJSEA['protect6'] ? 'X' : '' : '',
				'protect7' => key_exists('protect7', $onSiteJSEA) ? $onSiteJSEA['protect7'] ? 'X' : '' : '',
				'protect8' => key_exists('protect8', $onSiteJSEA) ? $onSiteJSEA['protect8'] ? 'X' : '' : '',
				'protect9' => key_exists('protect9', $onSiteJSEA) ? $onSiteJSEA['protect9'] ? 'X' : '' : '',
				'protect10' => key_exists('protect10', $onSiteJSEA) ? $onSiteJSEA['protect10'] ? 'X' : '' : '',
				'protect11' => key_exists('protect11', $onSiteJSEA) ? $onSiteJSEA['protect11'] ? 'X' : '' : '',
				'protect12' => key_exists('protect12', $onSiteJSEA) ? $onSiteJSEA['protect12'] ? 'X' : '' : '',
				'protect12Val' => key_exists('protect12Val', $onSiteJSEA) ? $this->wrapText(48, $onSiteJSEA['protect12Val'], 7) : '',
				'tool1' => key_exists('tool1', $onSiteJSEA) ? $onSiteJSEA['tool1'] ? 'X' : '' : '',
				'tool2' => key_exists('tool2', $onSiteJSEA) ? $onSiteJSEA['tool2'] ? 'X' : '' : '',
				'tool3' => key_exists('tool3', $onSiteJSEA) ? $onSiteJSEA['tool3'] ? 'X' : '' : '',
				'tool4' => key_exists('tool4', $onSiteJSEA) ? $onSiteJSEA['tool4'] ? 'X' : '' : '',
				'tool5' => key_exists('tool5', $onSiteJSEA) ? $onSiteJSEA['tool5'] ? 'X' : '' : '',
				'tool6' => key_exists('tool6', $onSiteJSEA) ? $onSiteJSEA['tool6'] ? 'X' : '' : '',
				'tool7' => key_exists('tool7', $onSiteJSEA) ? $onSiteJSEA['tool7'] ? 'X' : '' : '',
				'tool8' => key_exists('tool8', $onSiteJSEA) ? $onSiteJSEA['tool8'] ? 'X' : '' : '',
				'tool9' => key_exists('tool9', $onSiteJSEA) ? $onSiteJSEA['tool9'] ? 'X' : '' : '',
				'tool10' => key_exists('tool10', $onSiteJSEA) ? $onSiteJSEA['tool10'] ? 'X' : '' : '',
				'tool10Val' => key_exists('tool10Val', $onSiteJSEA) ? $this->wrapText(48, $onSiteJSEA['tool10Val'], 7) : '',
				'tool11' => key_exists('tool11', $onSiteJSEA) ? $onSiteJSEA['tool11'] ? 'X' : '' : '',
				'tool11Val' => key_exists('tool11Val', $onSiteJSEA) ? $this->wrapText(48, $onSiteJSEA['tool11Val'], 7) : '',
				'tool12' => key_exists('tool12', $onSiteJSEA) ? $onSiteJSEA['tool12'] ? 'X' : '' : '',
				'tool12Val' => key_exists('tool12Val', $onSiteJSEA) ? $this->wrapText(48, $onSiteJSEA['tool12Val'], 7) : '',
				'printedName1' => key_exists('printedName1', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['printedName1'], 7) : '',
				'printedName2' => key_exists('printedName2', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['printedName2'], 7) : '',
				'printedName3' => key_exists('printedName3', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['printedName3'], 7) : '',
				'printedName4' => key_exists('printedName4', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['printedName4'], 7) : '',
				'printedName5' => key_exists('printedName5', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['printedName5'], 7) : '',
				'printedName6' => key_exists('printedName6', $onSiteJSEA) ? $this->wrapText(139, $onSiteJSEA['printedName6'], 7) : '',
				'location1' => key_exists('location1', $onSiteJSEA) ? $this->wrapText(229, $onSiteJSEA['location1'], 7) : '',
				'location2' => key_exists('location2', $onSiteJSEA) ? $this->wrapText(229, $onSiteJSEA['location2'], 7) : '',
				'location3' => key_exists('location3', $onSiteJSEA) ? $this->wrapText(229, $onSiteJSEA['location3'], 7) : '',
				'location4' => key_exists('location4', $onSiteJSEA) ? $this->wrapText(229, $onSiteJSEA['location4'], 7) : '',
				'location5' => key_exists('location5', $onSiteJSEA) ? $this->wrapText(229, $onSiteJSEA['location5'], 7) : '',
				'location6' => key_exists('location6', $onSiteJSEA) ? $this->wrapText(229, $onSiteJSEA['location6'], 7) : '',
				'answer1' => key_exists('answer1', $onSiteJSEA) ? $onSiteJSEA['answer1'] : '',
				'answer1Val' => key_exists('answer1Val', $onSiteJSEA) ? $this->wrapText(185, $onSiteJSEA['answer1Val'], 7) : '',
				'answer2' => key_exists('answer2', $onSiteJSEA) ? $onSiteJSEA['answer2'] : '',
				'answer2Val' => key_exists('answer2Val', $onSiteJSEA) ? $this->wrapText(161, $onSiteJSEA['answer2Val'], 7) : '',
				'answer3' => key_exists('answer3', $onSiteJSEA) ? $onSiteJSEA['answer3'] : '',
				'answer3Val' => key_exists('answer3Val', $onSiteJSEA) ? $this->wrapText(185, $onSiteJSEA['answer3Val'], 7) : '',
				'answer4' => key_exists('answer4', $onSiteJSEA) ? $onSiteJSEA['answer4'] : '',
				'answer4Val' => key_exists('answer4Val', $onSiteJSEA) ? $this->wrapText(185, $onSiteJSEA['answer4Val'], 7) : '',
				'answer5' => key_exists('answer5', $onSiteJSEA) ? $onSiteJSEA['answer5'] : '',
				'answer5Val' => key_exists('answer5Val', $onSiteJSEA) ? $this->wrapText(185, $onSiteJSEA['answer5Val'], 7) : '',
				'answer6' => key_exists('answer6', $onSiteJSEA) ? $onSiteJSEA['answer6'] : '',
				'answer6Val' => key_exists('answer6Val', $onSiteJSEA) ? $this->wrapText(185, $onSiteJSEA['answer6Val'], 7) : '',
				'answer7' => key_exists('answer7', $onSiteJSEA) ? $onSiteJSEA['answer7'] : '',
				'answer7Val' => key_exists('answer7Val', $onSiteJSEA) ? $this->wrapText(292, $onSiteJSEA['answer7Val'], 7) : '',
				'answer8' => key_exists('answer8', $onSiteJSEA) ? $onSiteJSEA['answer8'] : '',
				'answer8Val' => key_exists('answer8Val', $onSiteJSEA) ? $this->wrapText(185, $onSiteJSEA['answer8Val'], 7) : '',
				'answer9' => key_exists('answer9', $onSiteJSEA) ? $onSiteJSEA['answer9'] : '',
				'answer9Val' => key_exists('answer9Val', $onSiteJSEA) ? $this->wrapText(185, $onSiteJSEA['answer9Val'], 7) : '',
				'answer10' => key_exists('answer10', $onSiteJSEA) ? $onSiteJSEA['answer10'] : '',
				'answer10Val' => key_exists('answer10Val', $onSiteJSEA) ? $this->wrapText(185, $onSiteJSEA['answer10Val'], 7) : '',
				'answer11' => key_exists('answer11', $onSiteJSEA) ? $onSiteJSEA['answer11'] : '',
				'answer11Val' => key_exists('answer11Val', $onSiteJSEA) ? $this->wrapText(185, $onSiteJSEA['answer11Val'], 7) : '',
				'answer12' => key_exists('answer12', $onSiteJSEA) ? $onSiteJSEA['answer12'] : '',
				'answer12Val' => key_exists('answer12Val', $onSiteJSEA) ? $this->wrapText(185, $onSiteJSEA['answer12Val'], 7) : '',
				'answer13' => key_exists('answer13', $onSiteJSEA) ? $onSiteJSEA['answer13'] : '',
				'answer13Val' => key_exists('answer13Val', $onSiteJSEA) ? $this->wrapText(185, $onSiteJSEA['answer13Val'], 7) : '',
				'answer14' => key_exists('answer14', $onSiteJSEA) ? $onSiteJSEA['answer14'] : '',
				'answer14Val' => key_exists('answer14Val', $onSiteJSEA) ? $this->wrapText(185, $onSiteJSEA['answer14Val'], 7) : ''
				
		);

		error_log('===== END preparing data ...');
		$report = $c->reportService()->runReport('/reports/isi/jsea1', 'pdf', null, null, $controls);
		$fp = fopen($onSiteJSEA['_id'] . 'page1', 'w');
		fwrite($fp, $report);
		fclose($fp);
		
		$report = $c->reportService()->runReport('/reports/isi/jsea2', 'pdf', null, null, $controls);
		$fp = fopen($onSiteJSEA['_id'] . 'page2', 'w');
		fwrite($fp, $report);
		fclose($fp);
		
		ob_start();
		$pdf = new PDFMerger;
		
		ob_end_clean();
		$pdf->addPDF($onSiteJSEA['_id'] . 'page1', '1')
		->addPDF($onSiteJSEA['_id'] . 'page2', '1')
		->merge('download', 'JSEA.pdf');
		
		
// 		$report = $c->reportService()->runReport('/reports/isi/jsea2', 'pdf', null, null, $controls);
		
		
// 		header('Cache-Control: must-revalidate');
// 		header('Pragma: public');
// 		header('Content-Description: File Transfer');
// 		header('Content-Disposition: attachment; filename=JSEA.pdf');
// 		header('Content-Transfer-Encoding: binary');
// 		header('Content-Length: ' . strlen($report));
// 		header('Content-Type: application/pdf');
		
// 		echo $report;
	}
	// TESTED
	function detailAlertPdf_get() {
		error_log('========================================================= detailAlertPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$onSiteAlertModel = $this->db->selectCollection('OnSiteAlert');
		$onSiteAlert = $onSiteAlertModel->findOne(array('_id' => $this->get('_id')));
		
		$jobId = $onSiteAlert['job'];
		
		$onSiteDailyJobModel = $this->db->selectCollection('OnSiteDailyJob');
		$onSiteDailyJob = $onSiteDailyJobModel->findOne(array('job' => $jobId));
		
		$jarSetsOpenHoleModel = $this->db->selectCollection('JarSetsOpenHole');
		$jarSetsOpenHole = $jarSetsOpenHoleModel->findOne(array('job' => $jobId));
	
		$jobModel = $this->db->selectCollection('Job');
		$job = $jobModel->findOne(array('_id' => $jobId));
		
		$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
		$serviceOrder = $serviceOrderModel->findOne(array('job' => $jobId));
	
		if (key_exists('serviceCompany', $job) && $job['serviceCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['serviceCompany']['$ref']);
			$wirelineCompany = $innerItemModel->findOne(array('_id' => $job['serviceCompany']['$id']));
		} else {
			$wirelineCompany = array();
		}
		if (key_exists('oilCompany', $job) && $job['oilCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
			$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
		} else {
			$oilCompany = array();
		}

		$runAlert = key_exists('run', $onSiteAlert) ? $onSiteAlert['run'] : '';		

		$comanComment = key_exists('comanComment', $onSiteAlert) ? $this->wrapTextMultiline(array(302, 467, 467), $onSiteAlert['comanComment'], 14) : array('','','');
		$additionalComment = key_exists('additionalComment', $onSiteAlert) ? $this->wrapTextMultiline(array(334, 467, 467), $onSiteAlert['additionalComment'], 14) : array('','','');
		$controls = array(
				'onSiteAlert' => key_exists('uid', $job) ? $job['uid'] : '',
	
				'dateOfOccurence' => key_exists('date', $onSiteAlert) ? $this->wrapText(347, gmdate("m/d/y", $onSiteAlert['date']), 14) : '',
				'technicianName' => key_exists('technician', $onSiteAlert) ? $this->wrapText(357, $onSiteAlert['technician'], 14) : '',
				'serviceCompany' => ($wirelineCompany != null && key_exists('name', $wirelineCompany)) ? $this->wrapText(357, $wirelineCompany['name'], 14) : '',
				'oilCompany' => ($oilCompany != null && key_exists('name', $oilCompany)) ? $this->wrapText(381, $oilCompany['name'], 14) : '',
				'natureOfAlert' => key_exists('nature', $onSiteAlert) ? $this->wrapText(369, $onSiteAlert['nature'], 14) : '',
				'holeSize' => ($onSiteDailyJob != null && key_exists('holeSize', $onSiteDailyJob)) ? $this->wrapText(406, $onSiteDailyJob['holeSize'], 14) : '',
				'deviation' => key_exists('deviation', $job) ? $this->wrapText(406, $job['deviation'], 14) : '',
				'mudWeight' => ($serviceOrder != null && key_exists('mudWt', $serviceOrder)) ? $this->wrapText(391, $serviceOrder['mudWt'], 14) : '',
				'temperature' => ($job != null && key_exists('temp', $job)) ? $this->wrapText(381, $job['temp'], 14) : '', 
				'tvd' => key_exists('tvd', $onSiteAlert) ? $this->wrapText(432, $onSiteAlert['tvd'], 14) : '',
				'casingShoeDepth' => key_exists('casingShoe', $onSiteAlert) ? $this->wrapText(347, $onSiteAlert['casingShoe'], 14) : '',
				'depthStuck' => key_exists('depthStuck', $onSiteAlert) ? $this->wrapText(381, $onSiteAlert['depthStuck'], 14) : '',
				'toolStringWeightInAir' => key_exists('wia_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(320, $jarSetsOpenHole['wia_' . $runAlert], 14) : '',
				'toolStringWeightInFluid' => key_exists('wif_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(307, $jarSetsOpenHole['wif_' . $runAlert], 14) : '',
				'weakPoint' => key_exists('wp_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(391, $jarSetsOpenHole['wp_' . $runAlert], 14) : '',
				'nameOfToolString' => key_exists('toolstringName_' . $runAlert, $jarSetsOpenHole) && is_array($jarSetsOpenHole['toolstringName_' . $runAlert]) ? $this->wrapText(343, implode(' , ',$jarSetsOpenHole['toolstringName_' . $runAlert]), 14) : '',
				'lbsPulledOnCableAtSurface' => key_exists('pullSurface', $onSiteAlert) ? $this->wrapText(282, $onSiteAlert['pullSurface'], 14) : '',
				'headTension' => key_exists('headTension', $onSiteAlert) ? $this->wrapText(379, $onSiteAlert['headTension'], 14) : '',
				'jarNumber' => key_exists('jarSerial_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(391, $jarSetsOpenHole['jarSerial_' . $runAlert], 14) : '',
				'jarSetting' => key_exists('actualJar_' . $runAlert, $jarSetsOpenHole) ? $this->wrapText(397, $jarSetsOpenHole['actualJar_' . $runAlert], 14) : '',
				'bottomHolePressure' => ($serviceOrder != null && key_exists('bhp', $serviceOrder)) ? $this->wrapText(333, $serviceOrder['bhp'], 14) : '',
				'companyMansComments_1' => $comanComment[0],
				'companyMansComments_2' => $comanComment[1],
				'companyMansComments_3' => $comanComment[2],
				'additionalComments_1' => $additionalComment[0],
				'additionalComments_2' => $additionalComment[1],
				'additionalComments_3' => $additionalComment[2]

		);
		
		$report = $c->reportService()->runReport('/reports/isi/firstAlertInformationNeeded', 'pdf', null, null, $controls);
	
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=FirstAlertInformationNeeded.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
	}
	// TESTED
	function detailCustomerPdf_get() {
		error_log('========================================================= detailCustomerPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$onSiteCustomerModel = $this->db->selectCollection('OnSiteCustomer');
		$onSiteCustomer = $onSiteCustomerModel->findOne(array('job' => $this->get('_id')));
	
		$jobModel = $this->db->selectCollection('Job');
		$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		
		if (key_exists('customer', $onSiteCustomer)) {
			$innerItemModel = $this->db->selectCollection($onSiteCustomer['customer']['$ref']);
			$customer = $innerItemModel->findOne(array('_id' => $onSiteCustomer['customer']['$id']));
		} else {
			$customer = array();
		}
		$additionalComment = key_exists('comment', $onSiteCustomer) ? $this->wrapTextMultiline(array(473, 473, 473, 473, 473), $onSiteCustomer['comment'], 12) : array('','','','','');
		$controls = array(
				'onSiteCustomer' => key_exists('uid', $job) ? $job['uid'] : '',
				'customerName' => key_exists('name', $customer) ? $this->wrapText(408, $customer['name'], 12) : '',
				'customerLocation' => key_exists('customerLocation', $onSiteCustomer) ? $this->wrapText(408, $onSiteCustomer['customerLocation'], 12) : '',
				'point1' => key_exists('point1', $onSiteCustomer) ? $onSiteCustomer['point1'] : '',
				'point2' => key_exists('point2', $onSiteCustomer) ? $onSiteCustomer['point2'] : '',
				'point3' => key_exists('point3', $onSiteCustomer) ? $onSiteCustomer['point3'] : '',
				'point4' => key_exists('point4', $onSiteCustomer) ? $onSiteCustomer['point4'] : '',
				'point5' => key_exists('point5', $onSiteCustomer) ? $onSiteCustomer['point5'] : '',
				'point6' => key_exists('point6', $onSiteCustomer) ? $onSiteCustomer['point6'] : '',
				'point7' => key_exists('point7', $onSiteCustomer) ? $onSiteCustomer['point7'] : '',
				'point8' => key_exists('point8', $onSiteCustomer) ? $onSiteCustomer['point8'] : '',
				'point9' => key_exists('point9', $onSiteCustomer) ? $onSiteCustomer['point9'] : '',
				'point10' => key_exists('point10', $onSiteCustomer) ? $onSiteCustomer['point10'] : '',
				'printName' => key_exists('customerRepName', $onSiteCustomer) ? $this->wrapText(105, $onSiteCustomer['customerRepName'], 10) : '',
				'date' => key_exists('customerRepDate', $onSiteCustomer) ? $this->wrapText(63, gmdate("m/d/y", $onSiteCustomer['customerRepDate']), 10) : '',				
				'jobTitle' => key_exists('jobTitle', $onSiteCustomer) ? $this->wrapText(138, $onSiteCustomer['jobTitle'], 10) : '',
				'isiFacility' => key_exists('isiFacility', $onSiteCustomer) ? $this->wrapText(101, $onSiteCustomer['isiFacility'], 10) : '',
				'customerAdditionalComments1' => $additionalComment[0],
				'customerAdditionalComments2' => $additionalComment[1],
				'customerAdditionalComments3' => $additionalComment[2],
				'customerAdditionalComments4' => $additionalComment[3],
				'customerAdditionalComments5' => $additionalComment[4],
				'rtaNo' => key_exists('uid', $job) ? $this->wrapText(107, $job['uid'], 10) : '',
				'jobType' =>  strcmp($job['uid'], '1') ? 'OH' : 'CH'
		);
		$totalPoor = 0;
		$totalMediocre = 0;
		$totalAdequate = 0;
		$totalGood = 0;
		$totalExcellent = 0;
		
		foreach ($controls as $key => $item) {
// 			error_log("$key => $item\n");
			if ('point' === "" || strrpos($key, 'point', -strlen($key)) !== FALSE) {
				//error_log('point detected');
				if(strcmp($item, '2') == 0) {
					$totalPoor = $totalPoor + 2;
				} else if(strcmp($item, '4') == 0) {
					$totalMediocre = $totalMediocre + 4;
				} else if(strcmp($item, '6') == 0) {
					$totalAdequate = $totalAdequate + 6;
				}else if(strcmp($item, '8') == 0) {
					$totalGood = $totalGood + 8;
				}else if(strcmp($item, '10') == 0) {
					$totalExcellent = $totalExcellent + 10;
				}	
			}
		}
	
		$controls['totalPoor'] = $totalPoor;
		$controls['totalMediocre'] = $totalMediocre;
		$controls['totalAdequate'] = $totalAdequate;
		$controls['totalGood'] = $totalGood;
		$controls['totalExcellent'] = $totalExcellent;
		$controls['overall'] = ($totalPoor + $totalMediocre + $totalAdequate + $totalGood + $totalExcellent) . ' %';
		$report = $c->reportService()->runReport('/reports/isi/customerSatisfactionPerformanceReport', 'pdf', null, null, $controls);
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=CustomerSatisfactionPerformanceReport.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
	}
	// TESTED
	function detailTechDataPdf_get() {
		error_log('========================================================= detailTechDataPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$onSiteTechDataModel = $this->db->selectCollection('OnSiteTechData');
		$onSiteTechData = $onSiteTechDataModel->findOne(array('job' => $this->get('_id')));
		$onSiteDailyJobModel = $this->db->selectCollection('OnSiteDailyJob');
		$onSiteDailyJob = $onSiteDailyJobModel->findOne(array('job' => $this->get('_id')));
	
		$jobModel = $this->db->selectCollection('Job');
		$job = $jobModel->findOne(array('_id' => $this->get('_id')));
	
		if (key_exists('oilCompany', $job) && $job['oilCompany'] != null && is_array($job['oilCompany'])) {
			$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
			$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
			if (!$oilCompany) {$oilCompany = array();}
		} else {
			$oilCompany = array();
		}
		if (key_exists('serviceCompany', $job) && $job['serviceCompany'] != null && is_array($job['serviceCompany'])) {
			$innerItemModel = $this->db->selectCollection($job['serviceCompany']['$ref']);
			$serviceCompany = $innerItemModel->findOne(array('_id' => $job['serviceCompany']['$id']));
			if (!$serviceCompany) {$serviceCompany = array();}
		} else {
			$serviceCompany = array();
		}
		if (key_exists('region', $onSiteTechData) && $onSiteTechData['region'] != null && is_array($onSiteTechData['region'])) {
			$innerItemModel = $this->db->selectCollection($onSiteTechData['region']['$ref']);
			$region = $innerItemModel->findOne(array('_id' => $onSiteTechData['region']['$id']));
			if (!$region) {$region = array();}
		} else {
			$region = array();
		}

		$address1_2 = "";
		if (key_exists('billingCity', $oilCompany)) {
			$address1_2 = $address1_2 . $oilCompany['billingCity'] . ' ';
		}
		if (key_exists('billingState', $oilCompany)) {
			$address1_2 = $address1_2 . $oilCompany['billingState'] . ' ';
		}
		if (key_exists('billingZip', $oilCompany)) {
			$address1_2 = $address1_2 . $oilCompany['billingZip'] . ' ';
		}
		if (strlen($address1_2) > 0) {
			$address1_2 = substr($address1_2, 0, strlen($address1_2) - 1);
		}
		$address2_2 = "";
		if (key_exists('billingCity', $serviceCompany)) {
			$address2_2 = $address2_2 . $serviceCompany['billingCity'] . ' ';
		}
		if (key_exists('billingState', $serviceCompany)) {
			$address2_2 = $address2_2 . $serviceCompany['billingState'] . ' ';
		}
		if (key_exists('billingZip', $serviceCompany)) {
			$address2_2 = $address2_2 . $serviceCompany['billingZip'] . ' ';
		}
		if (strlen($address2_2) > 0) {
			$address2_2 = substr($address2_2, 0, strlen($address2_2) - 1);
		}

		$coman = array();
		$comanCount = $onSiteTechData['comanCount'];
		for ($i = 0; $i < $comanCount; $i++) {
			if (key_exists('coman_' . ($i + 1), $onSiteTechData) && $onSiteTechData['coman_' . ($i + 1)] != null && is_array($onSiteTechData['coman_' . ($i + 1)])) {
				$innerItemModel = $this->db->selectCollection($onSiteTechData['coman_' . ($i + 1)]['$ref']);
				$comanObj = $innerItemModel->findOne(array('_id' => $onSiteTechData['coman_' . ($i + 1)]['$id']));
				if (!$comanObj) {$comanObj = array();}
			} else {
				$comanObj = array();
			}
			$comanUnit = array('name' => key_exists('firstName', $comanObj) ? $comanObj['titleName'] . " " . $comanObj['firstName'] . " " . $comanObj['lastName'] : '',
				'dayNight' => (key_exists('comanDayNight_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['comanDayNight_' . ($i + 1)]:''),
				'email' => (key_exists('comanEmail_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['comanEmail_' . ($i + 1)]:''),
				'phone' => (key_exists('comanPhone_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['comanPhone_' . ($i + 1)]:''));
			array_push($coman, $comanUnit);
		}
		$engineer = array();
		$engineerCount = $onSiteTechData['engineerCount'];
		for ($i = 0; $i < $engineerCount; $i++) {
			if (key_exists('engineer_' . ($i + 1), $onSiteTechData) && $onSiteTechData['engineer_' . ($i + 1)] != null && is_array($onSiteTechData['engineer_' . ($i + 1)])) {
				$innerItemModel = $this->db->selectCollection($onSiteTechData['engineer_' . ($i + 1)]['$ref']);
				$engineerObj = $innerItemModel->findOne(array('_id' => $onSiteTechData['engineer_' . ($i + 1)]['$id']));
				if (!$engineerObj) {$engineerObj = array();}
			} else {
				$engineerObj = array();
			}
			$engineerUnit = array('name' => key_exists('firstName', $engineerObj) ? $engineerObj['titleName'] . " " . $engineerObj['firstName'] . " " . $engineerObj['lastName'] : '',
				'title' => (key_exists('engineerTitle_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['engineerTitle_' . ($i + 1)]:''),
				'email' => (key_exists('engineerEmail_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['engineerEmail_' . ($i + 1)]:''),
				'phone' => (key_exists('engineerPhone_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['engineerPhone_' . ($i + 1)]:''));
			array_push($engineer, $engineerUnit);
		}
		$geo = array();
		$geoCount = $onSiteTechData['geoCount'];
		for ($i = 0; $i < $geoCount; $i++) {
			if (key_exists('geo_' . ($i + 1), $onSiteTechData) && $onSiteTechData['geo_' . ($i + 1)] != null && is_array($onSiteTechData['geo_' . ($i + 1)])) {
				$innerItemModel = $this->db->selectCollection($onSiteTechData['geo_' . ($i + 1)]['$ref']);
				$geoObj = $innerItemModel->findOne(array('_id' => $onSiteTechData['geo_' . ($i + 1)]['$id']));
				if (!$geoObj) {$geoObj = array();}
			} else {
				$geoObj = array();
			}
			$geoUnit = array('name' => key_exists('firstName', $geoObj) ? $geoObj['titleName'] . " " . $geoObj['firstName'] . " " . $geoObj['lastName'] : '',
				'title' => (key_exists('geoTitle_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['geoTitle_' . ($i + 1)]:''),
				'email' => (key_exists('geoEmail_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['geoEmail_' . ($i + 1)]:''),
				'phone' => (key_exists('geoPhone_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['geoPhone_' . ($i + 1)]:''));
			array_push($geo, $geoUnit);
		}
		$other = array();
		$otherCount = $onSiteTechData['otherCount'];
		for ($i = 0; $i < $otherCount; $i++) {
			if (key_exists('other_' . ($i + 1), $onSiteTechData) && $onSiteTechData['other_' . ($i + 1)] != null && is_array($onSiteTechData['other_' . ($i + 1)])) {
				$innerItemModel = $this->db->selectCollection($onSiteTechData['other_' . ($i + 1)]['$ref']);
				$otherObj = $innerItemModel->findOne(array('_id' => $onSiteTechData['other_' . ($i + 1)]['$id']));
				if (!$otherObj) {$otherObj = array();}
			} else {
				$otherObj = array();
			}
			$otherUnit = array('name' => key_exists('firstName', $otherObj) ? $otherObj['titleName'] . " " . $otherObj['firstName'] . " " . $otherObj['lastName'] : '',
				'title' => (key_exists('otherTitle_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['otherTitle_' . ($i + 1)]:''),
				'email' => (key_exists('otherEmail_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['otherEmail_' . ($i + 1)]:''),
				'phone' => (key_exists('otherPhone_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['otherPhone_' . ($i + 1)]:''),
				'role' => (key_exists('otherRole_' . ($i + 1), $onSiteTechData) ? $onSiteTechData['otherRole_' . ($i + 1)]:''));
			array_push($other, $otherUnit);
		}
		$loggingComment = key_exists('loggingComment', $onSiteTechData) ? $onSiteTechData['loggingComment'] : '';
//		$loggingCommentLines = explode("\n", wordwrap($loggingComment, 103));
		$loggingCommentArr = key_exists('loggingComment', $onSiteTechData) ? $this->wrapTextMultiline(array(558, 558, 558, 558, 558), $onSiteTechData['loggingComment'], 10) : array('','','','','');
		$controls = array(
				'technician' => key_exists('technician', $onSiteTechData) ? $this->wrapText(110, $onSiteTechData['technician'], 8) : '',
				'operator' => key_exists('name', $oilCompany) ? $this->wrapText(117, $oilCompany['name'], 8) : '',
				'address1_1' => key_exists('billingStreet', $oilCompany) ? $this->wrapText(117, $oilCompany['billingStreet'], 8) : '',
				'address1_2' => $this->wrapText(117, $address1_2, 8),
				'rta' => key_exists('uid', $job) ? $this->wrapText(110, $job['uid'], 8) : '',
				'serviceCompany' => key_exists('name', $serviceCompany) ? $this->wrapText(110, $serviceCompany['name'], 8) : '',
				'address2_1' => key_exists('billingStreet', $serviceCompany) ? $this->wrapText(110, $serviceCompany['billingStreet'], 8) : '',
				'address2_2' => $this->wrapText(110, $address2_2, 8),
				'date' => key_exists('date', $onSiteTechData) ? $this->wrapText(120, gmdate("m/d/y", $onSiteTechData['date']), 8) : '',
				'rigName' => key_exists('rigName', $job) ? $this->wrapText(120, $job['rigName'], 8) : '',
				'location' => key_exists('name', $region) ? $this->wrapText(120, $region['name'], 8) : '',
				'project' => key_exists('field', $onSiteTechData) ? $this->wrapText(120, $onSiteTechData['field'], 8) : '',

				'coman_1' => count($coman) >= 1?$this->wrapText(111, $coman[0]['name'], 8):'',
				'comanDay_1' => count($coman) >= 1?$this->wrapText(111, $coman[0]['dayNight'], 8):'',
				'comanEmail_1' => count($coman) >= 1?$this->wrapText(111, $coman[0]['email'], 8):'',
				'comanPhone_1' => count($coman) >= 1?$this->wrapText(111, $coman[0]['phone'], 8):'',

				'coman_2' => count($coman) >= 2?$this->wrapText(111, $coman[1]['name'], 8):'',
				'comanDay_2' => count($coman) >= 2?$this->wrapText(111, $coman[1]['dayNight'], 8):'',
				'comanEmail_2' => count($coman) >= 2?$this->wrapText(111, $coman[1]['email'], 8):'',
				'comanPhone_2' => count($coman) >= 2?$this->wrapText(111, $coman[1]['phone'], 8):'',

				'coman_3' => count($coman) >= 3?$this->wrapText(111, $coman[2]['name'], 8):'',
				'comanDay_3' => count($coman) >= 3?$this->wrapText(111, $coman[2]['dayNight'], 8):'',
				'comanEmail_3' => count($coman) >= 3?$this->wrapText(111, $coman[2]['email'], 8):'',
				'comanPhone_3' => count($coman) >= 3?$this->wrapText(111, $coman[2]['phone'], 8):'',

				'engineer_1' => count($engineer) >= 1?$this->wrapText(111, $engineer[0]['name'], 8):'',
				'engineerTitle_1' => count($engineer) >= 1?$this->wrapText(111, $engineer[0]['title'], 8):'',
				'engineerEmail_1' => count($engineer) >= 1?$this->wrapText(111, $engineer[0]['email'], 8):'',
				'engineerPhone_1' => count($engineer) >= 1?$this->wrapText(111, $engineer[0]['phone'], 8):'',

				'engineer_2' => count($engineer) >= 2?$this->wrapText(111, $engineer[1]['name'], 8):'',
				'engineerTitle_2' => count($engineer) >= 2?$this->wrapText(111, $engineer[1]['title'], 8):'',
				'engineerEmail_2' => count($engineer) >= 2?$this->wrapText(111, $engineer[1]['email'], 8):'',
				'engineerPhone_2' => count($engineer) >= 2?$this->wrapText(111, $engineer[1]['phone'], 8):'',

				'engineer_3' => count($engineer) >= 3?$this->wrapText(111, $engineer[2]['name'], 8):'',
				'engineerTitle_3' => count($engineer) >= 3?$this->wrapText(111, $engineer[2]['title'], 8):'',
				'engineerEmail_3' => count($engineer) >= 3?$this->wrapText(111, $engineer[2]['email'], 8):'',
				'engineerPhone_3' => count($engineer) >= 3?$this->wrapText(111, $engineer[2]['phone'], 8):'',

				'geo_1' => count($geo) >= 1?$this->wrapText(111, $geo[0]['name'], 8):'',
				'geoTitle_1' => count($geo) >= 1?$this->wrapText(111, $geo[0]['title'], 8):'',
				'geoEmail_1' => count($geo) >= 1?$this->wrapText(111, $geo[0]['email'], 8):'',
				'geoPhone_1' => count($geo) >= 1?$this->wrapText(111, $geo[0]['phone'], 8):'',

				'geo_2' => count($geo) >= 2?$this->wrapText(111, $geo[1]['name'], 8):'',
				'geoTitle_2' => count($geo) >= 2?$this->wrapText(111, $geo[1]['title'], 8):'',
				'geoEmail_2' => count($geo) >= 2?$this->wrapText(111, $geo[1]['email'], 8):'',
				'geoPhone_2' => count($geo) >= 2?$this->wrapText(111, $geo[1]['phone'], 8):'',

				'geo_3' => count($geo) >= 3?$this->wrapText(111, $geo[2]['name'], 8):'',
				'geoTitle_3' => count($geo) >= 3?$this->wrapText(111, $geo[2]['title'], 8):'',
				'geoEmail_3' => count($geo) >= 3?$this->wrapText(111, $geo[2]['email'], 8):'',
				'geoPhone_3' => count($geo) >= 3?$this->wrapText(111, $geo[2]['phone'], 8):'',

				'other_1' => count($other) >= 1?$this->wrapText(111, $other[0]['name'], 8):'',
				'otherTitle_1' => count($other) >= 1?$this->wrapText(111, $other[0]['title'], 8):'',
				'otherEmail_1' => count($other) >= 1?$this->wrapText(111, $other[0]['email'], 8):'',
				'otherPhone_1' => count($other) >= 1?$this->wrapText(111, $other[0]['phone'], 8):'',
				'otherRole_1' => count($other) >= 1?$this->wrapText(61, $other[0]['role'], 8):'',

				'other_2' => count($other) >= 2?$this->wrapText(111, $other[1]['name'], 8):'',
				'otherTitle_2' => count($other) >= 2?$this->wrapText(111, $other[1]['title'], 8):'',
				'otherEmail_2' => count($other) >= 2?$this->wrapText(111, $other[1]['email'], 8):'',
				'otherPhone_2' => count($other) >= 2?$this->wrapText(111, $other[1]['phone'], 8):'',
				'otherRole_2' => count($other) >= 2?$this->wrapText(61, $other[1]['role'], 8):'',

				'other_3' => count($other) >= 3?$this->wrapText(111, $other[2]['name'], 8):'',
				'otherTitle_3' => count($other) >= 3?$this->wrapText(111, $other[2]['title'], 8):'',
				'otherEmail_3' => count($other) >= 3?$this->wrapText(111, $other[2]['email'], 8):'',
				'otherPhone_3' => count($other) >= 3?$this->wrapText(111, $other[2]['phone'], 8):'',
				'otherRole_3' => count($other) >= 3?$this->wrapText(61, $other[2]['role'], 8):'',

				'toolstring_1' => key_exists('toolstring_1', $onSiteTechData) && is_array($onSiteTechData['toolstring_1']) ? $this->wrapText(128, implode(", ", $onSiteTechData['toolstring_1']), 8) : '',
				'toolstring_2' => key_exists('toolstring_2', $onSiteTechData) && is_array($onSiteTechData['toolstring_2']) ? $this->wrapText(128, implode(", ", $onSiteTechData['toolstring_2']), 8) : '',
				'toolstring_3' => key_exists('toolstring_3', $onSiteTechData) && is_array($onSiteTechData['toolstring_3']) ? $this->wrapText(128, implode(", ", $onSiteTechData['toolstring_3']), 8) : '',
				'toolstring_4' => key_exists('toolstring_4', $onSiteTechData) && is_array($onSiteTechData['toolstring_4']) ? $this->wrapText(128, implode(", ", $onSiteTechData['toolstring_4']), 8) : '',
				'toolstring_5' => key_exists('toolstring_5', $onSiteTechData) && is_array($onSiteTechData['toolstring_5']) ? $this->wrapText(128, implode(", ", $onSiteTechData['toolstring_5']), 8) : '',
				'toolstring_6' => key_exists('toolstring_6', $onSiteTechData) && is_array($onSiteTechData['toolstring_6']) ? $this->wrapText(128, implode(", ", $onSiteTechData['toolstring_6']), 8) : '',

				'activation_1' => key_exists('activation_1', $onSiteTechData) ? $this->wrapText(128, $onSiteTechData['activation_1'] === '0'?'NO':($onSiteTechData['activation_1'] === '1'?'YES':''), 8) : '',
				'activation_2' => key_exists('activation_2', $onSiteTechData) ? $this->wrapText(128, $onSiteTechData['activation_2'] === '0'?'NO':($onSiteTechData['activation_2'] === '1'?'YES':''), 8) : '',
				'activation_3' => key_exists('activation_3', $onSiteTechData) ? $this->wrapText(128, $onSiteTechData['activation_3'] === '0'?'NO':($onSiteTechData['activation_3'] === '1'?'YES':''), 8) : '',
				'activation_4' => key_exists('activation_4', $onSiteTechData) ? $this->wrapText(128, $onSiteTechData['activation_4'] === '0'?'NO':($onSiteTechData['activation_4'] === '1'?'YES':''), 8) : '',
				'activation_5' => key_exists('activation_5', $onSiteTechData) ? $this->wrapText(128, $onSiteTechData['activation_5'] === '0'?'NO':($onSiteTechData['activation_5'] === '1'?'YES':''), 8) : '',
				'activation_6' => key_exists('activation_6', $onSiteTechData) ? $this->wrapText(128, $onSiteTechData['activation_6'] === '0'?'NO':($onSiteTechData['activation_6'] === '1'?'YES':''), 8) : '',

				'toolsfreed_1' => key_exists('toolsfreed_1', $onSiteTechData) ? $this->wrapText(115, $onSiteTechData['toolsfreed_1'] === '0'?'NO':($onSiteTechData['toolsfreed_1'] === '1'?'YES':($onSiteTechData['toolsfreed_1'] === '2'?'N/A':'')), 8) : '',
				'toolsfreed_2' => key_exists('toolsfreed_2', $onSiteTechData) ? $this->wrapText(115, $onSiteTechData['toolsfreed_2'] === '0'?'NO':($onSiteTechData['toolsfreed_2'] === '1'?'YES':($onSiteTechData['toolsfreed_2'] === '2'?'N/A':'')), 8) : '',
				'toolsfreed_3' => key_exists('toolsfreed_3', $onSiteTechData) ? $this->wrapText(115, $onSiteTechData['toolsfreed_3'] === '0'?'NO':($onSiteTechData['toolsfreed_3'] === '1'?'YES':($onSiteTechData['toolsfreed_3'] === '2'?'N/A':'')), 8) : '',
				'toolsfreed_4' => key_exists('toolsfreed_4', $onSiteTechData) ? $this->wrapText(115, $onSiteTechData['toolsfreed_4'] === '0'?'NO':($onSiteTechData['toolsfreed_4'] === '1'?'YES':($onSiteTechData['toolsfreed_4'] === '2'?'N/A':'')), 8) : '',
				'toolsfreed_5' => key_exists('toolsfreed_5', $onSiteTechData) ? $this->wrapText(115, $onSiteTechData['toolsfreed_5'] === '0'?'NO':($onSiteTechData['toolsfreed_5'] === '1'?'YES':($onSiteTechData['toolsfreed_5'] === '2'?'N/A':'')), 8) : '',
				'toolsfreed_6' => key_exists('toolsfreed_6', $onSiteTechData) ? $this->wrapText(115, $onSiteTechData['toolsfreed_6'] === '0'?'NO':($onSiteTechData['toolsfreed_6'] === '1'?'YES':($onSiteTechData['toolsfreed_6'] === '2'?'N/A':'')), 8) : '',

				'fishing_1' => key_exists('fishing_1', $onSiteTechData) ? $this->wrapText(134, $onSiteTechData['fishing_1'] === '0'?'NO':($onSiteTechData['fishing_1'] === '1'?'YES':''), 8) : '',
				'fishing_2' => key_exists('fishing_2', $onSiteTechData) ? $this->wrapText(134, $onSiteTechData['fishing_2'] === '0'?'NO':($onSiteTechData['fishing_2'] === '1'?'YES':''), 8) : '',
				'fishing_3' => key_exists('fishing_3', $onSiteTechData) ? $this->wrapText(134, $onSiteTechData['fishing_3'] === '0'?'NO':($onSiteTechData['fishing_3'] === '1'?'YES':''), 8) : '',
				'fishing_4' => key_exists('fishing_4', $onSiteTechData) ? $this->wrapText(134, $onSiteTechData['fishing_4'] === '0'?'NO':($onSiteTechData['fishing_4'] === '1'?'YES':''), 8) : '',
				'fishing_5' => key_exists('fishing_5', $onSiteTechData) ? $this->wrapText(134, $onSiteTechData['fishing_5'] === '0'?'NO':($onSiteTechData['fishing_5'] === '1'?'YES':''), 8) : '',
				'fishing_6' => key_exists('fishing_6', $onSiteTechData) ? $this->wrapText(134, $onSiteTechData['fishing_6'] === '0'?'NO':($onSiteTechData['fishing_6'] === '1'?'YES':''), 8) : '',

				'futureLocation_1' => key_exists('futureLocation_1', $onSiteTechData) ? $this->wrapText(104, $onSiteTechData['futureLocation_1'], 8) : '',
				'futureLocation_2' => key_exists('futureLocation_2', $onSiteTechData) ? $this->wrapText(104, $onSiteTechData['futureLocation_2'], 8) : '',
				'moveDate_1' => key_exists('moveDate_1', $onSiteTechData) ? $this->wrapText(104, gmdate("m/d/y", $onSiteTechData['moveDate_1']), 8) : '',
				'moveDate_2' => key_exists('moveDate_2', $onSiteTechData) ? $this->wrapText(104, gmdate("m/d/y", $onSiteTechData['moveDate_2']), 8) : '',

				'status_1' => key_exists('status_1', $onSiteTechData) ? $this->wrapText(122, $onSiteTechData['status_1'], 8) : '',
				'status_2' => key_exists('status_2', $onSiteTechData) ? $this->wrapText(122, $onSiteTechData['status_2'], 8) : '',
				'length_1' => key_exists('length_1', $onSiteTechData) ? $this->wrapText(99, $onSiteTechData['length_1'], 8) : '',
				'length_2' => key_exists('length_2', $onSiteTechData) ? $this->wrapText(99, $onSiteTechData['length_2'], 8) : '',

			'loggingDate_1' => key_exists('loggingDate_1', $onSiteTechData) ? $this->wrapText(90, gmdate("m/d/y", $onSiteTechData['loggingDate_1']), 8) : '',
			'loggingDate_2' => key_exists('loggingDate_2', $onSiteTechData) ? $this->wrapText(90, gmdate("m/d/y", $onSiteTechData['loggingDate_2']), 8) : '',
			'loggingDate_3' => key_exists('loggingDate_3', $onSiteTechData) ? $this->wrapText(90, gmdate("m/d/y", $onSiteTechData['loggingDate_3']), 8) : '',
			'projectName_1' => key_exists('projectName_1', $onSiteTechData) ? $this->wrapText(133, $onSiteTechData['projectName_1'], 8) : '',
			'projectName_2' => key_exists('projectName_2', $onSiteTechData) ? $this->wrapText(133, $onSiteTechData['projectName_2'], 8) : '',
			'projectName_3' => key_exists('projectName_3', $onSiteTechData) ? $this->wrapText(133, $onSiteTechData['projectName_3'], 8) : '',
			'location_1' => key_exists('location_1', $onSiteTechData) ? $this->wrapText(109, $onSiteTechData['location_1'], 8) : '',
			'location_2' => key_exists('location_2', $onSiteTechData) ? $this->wrapText(109, $onSiteTechData['location_2'], 8) : '',
			'location_3' => key_exists('location_3', $onSiteTechData) ? $this->wrapText(109, $onSiteTechData['location_3'], 8) : '',
			'equipment_1' => key_exists('equipment_1', $onSiteTechData) ? $this->wrapText(122, $onSiteTechData['equipment_1'], 8) : '',
			'equipment_2' => key_exists('equipment_2', $onSiteTechData) ? $this->wrapText(122, $onSiteTechData['equipment_2'], 8) : '',
			'equipment_3' => key_exists('equipment_3', $onSiteTechData) ? $this->wrapText(122, $onSiteTechData['equipment_3'], 8) : '',
			'hphtStandard_1' => key_exists('hphtStandard_1', $onSiteTechData) ? $this->wrapText(99, $onSiteTechData['hphtStandard_1'], 8) : '',
			'hphtStandard_2' => key_exists('hphtStandard_2', $onSiteTechData) ? $this->wrapText(99, $onSiteTechData['hphtStandard_2'], 8) : '',
			'hphtStandard_3' => key_exists('hphtStandard_3', $onSiteTechData) ? $this->wrapText(99, $onSiteTechData['hphtStandard_3'], 8) : '',

			'otherRigName_1' => key_exists('otherRigName_1', $onSiteTechData) ? $this->wrapText(90, $onSiteTechData['otherRigName_1'], 8) : '',
			'otherRigName_2' => key_exists('otherRigName_2', $onSiteTechData) ? $this->wrapText(90, $onSiteTechData['otherRigName_2'], 8) : '',
			'otherRigName_3' => key_exists('otherRigName_3', $onSiteTechData) ? $this->wrapText(90, $onSiteTechData['otherRigName_3'], 8) : '',
			'otherRigName_4' => key_exists('otherRigName_4', $onSiteTechData) ? $this->wrapText(90, $onSiteTechData['otherRigName_4'], 8) : '',
			'otherRigName_5' => key_exists('otherRigName_5', $onSiteTechData) ? $this->wrapText(90, $onSiteTechData['otherRigName_5'], 8) : '',
			'otherRigName_6' => key_exists('otherRigName_6', $onSiteTechData) ? $this->wrapText(90, $onSiteTechData['otherRigName_6'], 8) : '',
			'otherOperator_1' => key_exists('otherOperator_1', $onSiteTechData) ? $this->wrapText(133, $onSiteTechData['otherOperator_1'], 8) : '',
			'otherOperator_2' => key_exists('otherOperator_2', $onSiteTechData) ? $this->wrapText(133, $onSiteTechData['otherOperator_2'], 8) : '',
			'otherOperator_3' => key_exists('otherOperator_3', $onSiteTechData) ? $this->wrapText(133, $onSiteTechData['otherOperator_3'], 8) : '',
			'otherOperator_4' => key_exists('otherOperator_4', $onSiteTechData) ? $this->wrapText(133, $onSiteTechData['otherOperator_4'], 8) : '',
			'otherOperator_5' => key_exists('otherOperator_5', $onSiteTechData) ? $this->wrapText(133, $onSiteTechData['otherOperator_5'], 8) : '',
			'otherOperator_6' => key_exists('otherOperator_6', $onSiteTechData) ? $this->wrapText(133, $onSiteTechData['otherOperator_6'], 8) : '',
			'otherServiceCompany_1' => key_exists('otherServiceCompany_1', $onSiteTechData) ? $this->wrapText(109, $onSiteTechData['otherServiceCompany_1'], 8) : '',
			'otherServiceCompany_2' => key_exists('otherServiceCompany_2', $onSiteTechData) ? $this->wrapText(109, $onSiteTechData['otherServiceCompany_2'], 8) : '',
			'otherServiceCompany_3' => key_exists('otherServiceCompany_3', $onSiteTechData) ? $this->wrapText(109, $onSiteTechData['otherServiceCompany_3'], 8) : '',
			'otherServiceCompany_4' => key_exists('otherServiceCompany_4', $onSiteTechData) ? $this->wrapText(109, $onSiteTechData['otherServiceCompany_4'], 8) : '',
			'otherServiceCompany_5' => key_exists('otherServiceCompany_5', $onSiteTechData) ? $this->wrapText(109, $onSiteTechData['otherServiceCompany_5'], 8) : '',
			'otherServiceCompany_6' => key_exists('otherServiceCompany_6', $onSiteTechData) ? $this->wrapText(109, $onSiteTechData['otherServiceCompany_6'], 8) : '',
			'otherLocation_1' => key_exists('otherLocation_1', $onSiteTechData) ? $this->wrapText(87, $onSiteTechData['otherLocation_1'], 8) : '',
			'otherLocation_2' => key_exists('otherLocation_2', $onSiteTechData) ? $this->wrapText(87, $onSiteTechData['otherLocation_2'], 8) : '',
			'otherLocation_3' => key_exists('otherLocation_3', $onSiteTechData) ? $this->wrapText(87, $onSiteTechData['otherLocation_3'], 8) : '',
			'otherLocation_4' => key_exists('otherLocation_4', $onSiteTechData) ? $this->wrapText(87, $onSiteTechData['otherLocation_4'], 8) : '',
			'otherLocation_5' => key_exists('otherLocation_5', $onSiteTechData) ? $this->wrapText(87, $onSiteTechData['otherLocation_5'], 8) : '',
			'otherLocation_6' => key_exists('otherLocation_6', $onSiteTechData) ? $this->wrapText(87, $onSiteTechData['otherLocation_6'], 8) : '',
			'otherEquipment_1' => key_exists('otherEquipment_1', $onSiteTechData) ? $this->wrapText(68, $onSiteTechData['otherEquipment_1'], 8) : '',
			'otherEquipment_2' => key_exists('otherEquipment_2', $onSiteTechData) ? $this->wrapText(68, $onSiteTechData['otherEquipment_2'], 8) : '',
			'otherEquipment_3' => key_exists('otherEquipment_3', $onSiteTechData) ? $this->wrapText(68, $onSiteTechData['otherEquipment_3'], 8) : '',
			'otherEquipment_4' => key_exists('otherEquipment_4', $onSiteTechData) ? $this->wrapText(68, $onSiteTechData['otherEquipment_4'], 8) : '',
			'otherEquipment_5' => key_exists('otherEquipment_5', $onSiteTechData) ? $this->wrapText(68, $onSiteTechData['otherEquipment_5'], 8) : '',
			'otherEquipment_6' => key_exists('otherEquipment_6', $onSiteTechData) ? $this->wrapText(68, $onSiteTechData['otherEquipment_6'], 8) : '',
			'otherCallout_1' => key_exists('otherCallout_1', $onSiteTechData) ? $this->wrapText(64, gmdate("m/d/y", $onSiteTechData['otherCallout_1']), 8) : '',
			'otherCallout_2' => key_exists('otherCallout_2', $onSiteTechData) ? $this->wrapText(64, gmdate("m/d/y", $onSiteTechData['otherCallout_2']), 8) : '',
			'otherCallout_3' => key_exists('otherCallout_3', $onSiteTechData) ? $this->wrapText(64, gmdate("m/d/y", $onSiteTechData['otherCallout_3']), 8) : '',
			'otherCallout_4' => key_exists('otherCallout_4', $onSiteTechData) ? $this->wrapText(64, gmdate("m/d/y", $onSiteTechData['otherCallout_4']), 8) : '',
			'otherCallout_5' => key_exists('otherCallout_5', $onSiteTechData) ? $this->wrapText(64, gmdate("m/d/y", $onSiteTechData['otherCallout_5']), 8) : '',
			'otherCallout_6' => key_exists('otherCallout_6', $onSiteTechData) ? $this->wrapText(64, gmdate("m/d/y", $onSiteTechData['otherCallout_6']), 8) : '',


				'loggingComment_1' => $loggingCommentArr[0],
				'loggingComment_2' => $loggingCommentArr[1],
				'loggingComment_3' => $loggingCommentArr[2],
				'loggingComment_4' => $loggingCommentArr[3],
				'loggingComment_5' => $loggingCommentArr[4]
				
		);
	
		$report = $c->reportService()->runReport('/reports/isi/isiTechDataSheet', 'pdf', null, null, $controls);
		$email = $this->get('email');
		if (!empty($email)) {
			// Settings
			$to          = $email;
			$from        = "KMS admin";
			$subject     = "Tech Data Report Sent";
			$mainMessage = "Tech Data Report Sent, emailed with the PDF attachment";
			$fileatttype = "application/pdf";
			$fileattname = "TechData.pdf";
			$headers = "From: $from";

			// This attaches the file
			$semi_rand     = md5(time());
			$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
			$headers      .= "\nMIME-Version: 1.0\n" .
				"Content-Type: multipart/mixed;\n" .
				" boundary=\"{$mime_boundary}\"";
			$message = "This is a multi-part message in MIME format.\n\n" .
				"-{$mime_boundary}\n" .
				"Content-Type: text/plain; charset=\"iso-8859-1\n" .
				"Content-Transfer-Encoding: 7bit\n\n" .
				$mainMessage  . "\n\n";

			$dataPdf = chunk_split(base64_encode($report));
			$message .= "--{$mime_boundary}\n" .
				"Content-Type: {$fileatttype};\n" .
				" name=\"{$fileattname}\"\n" .
				"Content-Disposition: attachment;\n" .
				" filename=\"{$fileattname}\"\n" .
				"Content-Transfer-Encoding: base64\n\n" .
				$dataPdf . "\n\n" .
				"-{$mime_boundary}-\n";

			// Send the email
			if(mail($to, $subject, $message, $headers)) {
				$mailMessage = 'successfuly executed and email sent to ' . $email;
			}
			else {
				$mailMessage ='successfuly executed, but error when sent email to ' . $email;
			}
			if ($mailMessage) {
				$output = ['status' => 1, 'message' => $mailMessage];
			} else {
				$output = ['status' => 1, 'message' => 'successfuly executed'];
			}
			return $output;
		} else {
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Description: File Transfer');
			header('Content-Disposition: attachment; filename=IsiTechDataSheet.pdf');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . strlen($report));
			header('Content-Type: application/pdf');

			echo $report;
		}
	}
	
	function insertRunArr($runArr, $key, $runNum) {
		$valPos = -1;
		for ($i = 0; $i < count($runArr); $i++) {
			if ($runArr[$i]['key'] == $key) {
				$valPos = $i;
			}
		}
		if ($valPos != -1) {
			$runArr[$valPos]['runNum'][] = $runNum;
		} else {
			$runArr[] = array('key' => $key, 'runNum' => array($runNum));
		}
		return $runArr;
	}
	
	function createRunString($runArr) {
		$result = '';
		for ($i = 0; $i < count($runArr); $i++) {
			$key = $runArr[$i]['key'];
			$runNumArr = $runArr[$i]['runNum'];
			$runString = '';
			$start = $runNumArr[0];
			if (count($runNumArr) > 1) {
				for ($j = 1; $j < count($runNumArr); $j++) {
					if ($runNumArr[$j] - $runNumArr[$j - 1] > 1) { // not sequence anymore
						if ($start == $runNumArr[$j - 1]) {
							$runString = $runString . $start . ',';
						} else {
							$runString = $runString . $start . '-' . $runNumArr[$j - 1] . ',';
						}
						$start = $runNumArr[$j];
					}
					if ($j == count($runNumArr) - 1) {
						if ($start == $runNumArr[$j]) {
							$runString = $runString . $start . ',';
						} else {
							$runString = $runString . $start . '-' . $runNumArr[$j] . ',';
						}
					}
				}
				$runString = substr($runString, 0, strlen($runString) - 1);
			} else {
				$runString = $runString . $start;
			}
			$result =  $result . $runString . ': ' . $key . '; ';
		}
		return $result;
	}
	//TESTED
	function detailSummaryJobPdf_get() {
		error_log('========================================================= detailSummaryJobPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);

		// Start Daily Job Report
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$onSiteDailyJobModel = $this->db->selectCollection('OnSiteDailyJob');
		$onSiteDailyJob = $onSiteDailyJobModel->findOne(array('job' => $this->get('_id')));
		
		$jarSetsOpenHoleModel = $this->db->selectCollection('JarSetsOpenHole');
		$jarSetsOpenHole = $jarSetsOpenHoleModel->findOne(array('job' => $this->get('_id')));
		
		$jobModel = $this->db->selectCollection('Job');
		$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		
		$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
		$serviceOrder = $serviceOrderModel->findOne(array('job' => $this->get('_id')));
		
		// END Daily Job Report and START Jar Sets Open Hole 
		$runCount = ($jarSetsOpenHole != null && key_exists('itemCount', $jarSetsOpenHole))  ? $jarSetsOpenHole['itemCount'] : 0;
		$controls = array(
				'onSiteSummaryJob' => key_exists('uid', $job) ? $job['uid'] : '',
				'dailyJobReportNumber' => key_exists('ticketNo', $onSiteDailyJob) ? $this->wrapText(102, $onSiteDailyJob['ticketNo'], 10) : '',
				'companyRepresentative' => key_exists('operator', $onSiteDailyJob) ? $this->wrapText(382, $onSiteDailyJob['operator'], 10) : '',
				
				'totalNumberOfRuns' => ($jarSetsOpenHole != null && key_exists('itemCount', $jarSetsOpenHole)) ? $this->wrapText(393, $jarSetsOpenHole['itemCount'], 10) : '0',
				
				'totalWellDepth' => ($serviceOrder != null && key_exists('td', $serviceOrder)) ? $this->wrapText(417, $serviceOrder['td'], 10) : '',
				'deviation' => key_exists('deviation', $job) ? $this->wrapText(451, $job['deviation'], 10) : '',
				'waterDepth' => key_exists('waterDepth', $onSiteDailyJob) ? $this->wrapText(435, $onSiteDailyJob['waterDepth'], 10) : '',
				'holeSize' => key_exists('holeSize', $onSiteDailyJob) ? $this->wrapText(453, $onSiteDailyJob['holeSize'], 10) : '',
				'bottomHolePressure' => ($serviceOrder != null && key_exists('bhp', $serviceOrder)) ? $this->wrapText(393, $serviceOrder['bhp'], 10) : '',
				'temperature' => ($job != null && key_exists('temp', $job)) ? $this->wrapText(435, $job['temp'], 10) : '',
				'mudWeight' => ($serviceOrder != null && key_exists('mudWt', $serviceOrder)) ? $this->wrapText(434, $serviceOrder['mudWt'], 10) : '',
				'cableSizeAndWeight' => $this->wrapText(335, (key_exists('cableSize', $onSiteDailyJob) ? $onSiteDailyJob['cableSize'] . ' and ' : '') . (key_exists('cableWeight', $onSiteDailyJob) ? $onSiteDailyJob['cableWeight'] : ''), 10)
		);
		
		$serialNumberOfToolUsed = '';
		$totalNumberOfActivations = 0;
		$toolStringName = '';
		$toolStringLength = '';
		$maxPullAtSurface = '';
		$maxPullAtHead = '';
		$weakPoint = '';
		$toolWeightInFluid = '';
		$toolWeightInAir = '';
		$estimatedLineWeightOnBottom = '';
		$formulaUsed_1 = '';
		$actualJarSetting = '';
		
		$serialNumberOfToolUsedArr = array();
		$toolStringLengthArr = array();
		$maxPullAtSurfaceArr = array();
		$maxPullAtHeadArr = array();
		$weakPointArr = array();
		$toolWeightInAirArr = array();
		$toolWeightInFluidArr = array();
		$estimatedLineWeightOnBottomArr = array();
		$formulaUsed_1Arr = array();
		
		for ($x = 1; $x <= $runCount; $x = $x+1) {
			if (key_exists('run_' . $x , $jarSetsOpenHole) && trim($jarSetsOpenHole['run_' . $x]) != '') {
				if ($jarSetsOpenHole != null && key_exists('jarSerial_' . $x, $jarSetsOpenHole)) {
					$jarTypeSerial = explode('-', $jarSetsOpenHole['jarSerial_' . $x], 2);
					//$serialNumberOfToolUsed =  $serialNumberOfToolUsed . $jarSetsOpenHole['run_' . $x] . ': ' . (count($jarTypeSerial) > 1 ? trim($jarTypeSerial[1]) : trim($jarTypeSerial[0])) . '; ';
					$serialRun = count($jarTypeSerial) > 1 ? trim($jarTypeSerial[1]) : trim($jarTypeSerial[0]);
					$serialNumberOfToolUsedArr = $this->insertRunArr($serialNumberOfToolUsedArr, $serialRun, $x);
				}
				if ($jarSetsOpenHole != null && key_exists('activation_' . $x, $jarSetsOpenHole))
					$totalNumberOfActivations = $jarSetsOpenHole['activation_' . $x] == '1' ? $totalNumberOfActivations + 1  : $totalNumberOfActivations;
				//$toolStringName =  $toolStringName . '; ' . $jarSetsOpenHole['toolstringName_' . $x];
				if ($jarSetsOpenHole != null && key_exists('toolstringLength_' . $x, $jarSetsOpenHole))
					//$toolStringLength =  $toolStringLength . $jarSetsOpenHole['run_' . $x] . ': ' . $jarSetsOpenHole['toolstringLength_' . $x] . '; ';
					$toolStringLengthArr = $this->insertRunArr($toolStringLengthArr, $jarSetsOpenHole['toolstringLength_' . $x], $x);
				if ($jarSetsOpenHole != null && key_exists('msp_' . $x, $jarSetsOpenHole)) {
					//$maxPullAtSurface =  $maxPullAtSurface . $jarSetsOpenHole['run_' . $x] . ': ' . $jarSetsOpenHole['msp_' . $x] . '; ';
					$maxPullAtSurfaceArr = $this->insertRunArr($maxPullAtSurfaceArr, $jarSetsOpenHole['msp_' . $x], $x);
					$msp_exist = true;
				} else {
					$msp_exist = false;
				}
				if ($jarSetsOpenHole != null && key_exists('pullHead_' . $x, $jarSetsOpenHole))
					//$maxPullAtHead =  $maxPullAtHead . $jarSetsOpenHole['run_' . $x] . ': ' . $jarSetsOpenHole['pullHead_' . $x] . '; ';
					$maxPullAtHeadArr = $this->insertRunArr($maxPullAtHeadArr, $jarSetsOpenHole['pullHead_' . $x], $x);
				if ($jarSetsOpenHole != null && key_exists('wp_' . $x, $jarSetsOpenHole)) {
					//$weakPoint =  $weakPoint . $jarSetsOpenHole['run_' . $x] . ': ' . $jarSetsOpenHole['wp_' . $x] . '; ';
					$weakPointArr = $this->insertRunArr($weakPointArr, $jarSetsOpenHole['wp_' . $x], $x);
					$wp_exist = true;
				} else {
					$wp_exist = false;
				}
					
				if ($jarSetsOpenHole != null && key_exists('wia_' . $x, $jarSetsOpenHole))
					//$toolWeightInAir =  $toolWeightInAir . $jarSetsOpenHole['run_' . $x] . ': ' . $jarSetsOpenHole['wia_' . $x] . '; ';
					$toolWeightInAirArr = $this->insertRunArr($toolWeightInAirArr, $jarSetsOpenHole['wia_' . $x], $x);
				if ($jarSetsOpenHole != null && key_exists('wif_' . $x, $jarSetsOpenHole)) {
					//$toolWeightInFluid =  $toolWeightInFluid . $jarSetsOpenHole['run_' . $x] . ': ' . $jarSetsOpenHole['wif_' . $x] . '; ';
					$toolWeightInFluidArr = $this->insertRunArr($toolWeightInFluidArr, $jarSetsOpenHole['wif_' . $x], $x);
					$wif_exist = true;
				} else {
					$wif_exist = false;
				}
					
				if ($jarSetsOpenHole != null && key_exists('wob_' . $x, $jarSetsOpenHole)) {
					//$estimatedLineWeightOnBottom =  $estimatedLineWeightOnBottom . $jarSetsOpenHole['run_' . $x] . ': ' . $jarSetsOpenHole['wob_' . $x] . '; ';
					$estimatedLineWeightOnBottomArr = $this->insertRunArr($estimatedLineWeightOnBottomArr, $jarSetsOpenHole['wob_' . $x], $x);
					$wob_exist = true;
				} else {
					$wob_exist = false;
				}
					
				if ($jarSetsOpenHole != null && key_exists('actualJar_' . $x, $jarSetsOpenHole)) {
					$actualJarSetting =  $actualJarSetting . $jarSetsOpenHole['run_' . $x] . ': ' . $jarSetsOpenHole['actualJar_' . $x] . '; ';
				}
				if ($wif_exist) {
					$wif_a = $jarSetsOpenHole['wif_' . $x] + 2000;
					$a = true;
				} else {
					$a = false;
				}
				if ($msp_exist && $wob_exist && $wif_exist) {
					$msp_a = $jarSetsOpenHole['msp_' . $x] - ($jarSetsOpenHole['wob_' . $x] - $jarSetsOpenHole['wif_' . $x]) - 2000;
					$b = true;
				} else {
					$b = false;
				}
					
				if ($wp_exist) {
					$wp_a = $jarSetsOpenHole['wp_' . $x] - 2000;
					$cc = true;
				} else {
					$cc = false;
				}	
				if ($a && $b && $cc) {
					$valPicked = min($wif_a, $msp_a, $wp_a);
					if ($valPicked == $wif_a) {
						//$formulaUsed_1 = $formulaUsed_1 . $jarSetsOpenHole['run_' . $x] . ': ' . '(A); ' ;
						$formulaUsed_1Arr = $this->insertRunArr($formulaUsed_1Arr, '(A)', $x);
					} else if ($valPicked == $msp_a) {
						//$formulaUsed_1 = $formulaUsed_1 . $jarSetsOpenHole['run_' . $x] . ': ' . '(B); ' ;
						$formulaUsed_1Arr = $this->insertRunArr($formulaUsed_1Arr, '(B)', $x);
					} else if ($valPicked == $wp_a) {
						//$formulaUsed_1 = $formulaUsed_1 . $jarSetsOpenHole['run_' . $x] . ': ' . '(C); ' ;
						$formulaUsed_1Arr = $this->insertRunArr($formulaUsed_1Arr, '(C)', $x);
					}
				}
			}	
		}
		$serialNumberOfToolUsed = $this->createRunString($serialNumberOfToolUsedArr);
		$toolStringLength = $this->createRunString($toolStringLengthArr);
		$maxPullAtSurface = $this->createRunString($maxPullAtSurfaceArr);
		$maxPullAtHead = $this->createRunString($maxPullAtHeadArr);
		$weakPoint = $this->createRunString($weakPointArr);
		$toolWeightInAir = $this->createRunString($toolWeightInAirArr);
		$toolWeightInFluid = $this->createRunString($toolWeightInFluidArr);
		$estimatedLineWeightOnBottom = $this->createRunString($estimatedLineWeightOnBottomArr);
		$formulaUsed_1 = $this->createRunString($formulaUsed_1Arr);
		
		$controls['serialNumberOfToolUsed'] = $this->wrapText(368, $serialNumberOfToolUsed, 10);
		$controls['totalNumberOfActivations'] = $this->wrapText(363, $totalNumberOfActivations, 10);
		//$controls['toolStringName'] = $toolStringName;
		$controls['toolStringLength'] = $this->wrapText(410, $toolStringLength, 10);
		$controls['maxPullAtSurface'] = $this->wrapText(307, $maxPullAtSurface, 10);
		$controls['maxPullAtHead'] = $this->wrapText(318, $maxPullAtHead, 10);
		$controls['weakPoint'] = $this->wrapText(443, $weakPoint, 10);
		$controls['toolWeightInFluid'] = $this->wrapText(403, $toolWeightInFluid, 10);
		$controls['toolWeightInAir'] = $this->wrapText(412, $toolWeightInAir, 10);
		$controls['estimatedLineWeightOnBottom'] = $this->wrapText(338, $estimatedLineWeightOnBottom, 10);
		$controls['formulaUsed_1'] = $this->wrapText(426, $formulaUsed_1, 10);
		$controls['formulaUsed_2'] = '';
		$controls['formulaUsed_3'] = '';
		$controls['formulaUsed_4'] = '';
		$controls['actualJarSetting'] = $actualJarSetting;
		//error_log('var_control ===> \n' . var_export($controls, true));
		$report = $c->reportService()->runReport('/reports/isi/dailyJobReportSummary', 'pdf', null, null, $controls);
	
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=DailyJobReportSummary.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
	}
	// TESTED
	function detailDailyJobPdf_get() {
		error_log('========================================================= detailDailyJobPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$onSiteDailyJobModel = $this->db->selectCollection('OnSiteDailyJob');
		$onSiteDailyJob = $onSiteDailyJobModel->findOne(array('job' => $this->get('_id')));
	
		$jobModel = $this->db->selectCollection('Job');
		$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		
		$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
		$serviceOrder = $serviceOrderModel->findOne(array('job' => $this->get('_id')));
	
		if (key_exists('serviceCompany', $job) && $job['serviceCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['serviceCompany']['$ref']);
			$wirelineCompany = $innerItemModel->findOne(array('_id' => $job['serviceCompany']['$id']));
			if (empty($wirelineCompany)) {
				$wirelineCompany = array();
			}
		} else {
			$wirelineCompany = array();
		}
		if (key_exists('well', $job)) {
			$innerItemModel = $this->db->selectCollection($job['well']['$ref']);
			$well = $innerItemModel->findOne(array('_id' => $job['well']['$id']));
			if (empty($well)) {
				$well = array();
			}
		} else {
			$well = array();
		}
		if (key_exists('oilCompany', $job) && $job['oilCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
			$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
			if (empty($oilCompany)) {
				$oilCompany = array();
			}
		} else {
			$oilCompany = array();
		}
		error_log('preparing data Job');
		
		$fieldLease = "";
		if (key_exists('lease', $job) && $job['lease'] != null) {
			$fieldLease = $fieldLease . $job['lease'] . '/';
		}
		if (key_exists('field', $job) && $job['field'] != null) {
			$fieldLease = $fieldLease . $job['field'] . '/';
		}
		if (strlen($fieldLease) > 0) {
			$fieldLease = substr($fieldLease, 0, strlen($fieldLease) - 1);
		}
		error_log('preparing data FieldLease');
		$unitSerial = "";
		if ($serviceOrder != null) {
			for ($i = 1; $i <= $serviceOrder['toolCount']; $i++) {
				if (key_exists('serial' . $i, $serviceOrder) && $serviceOrder['serial' . $i] != null) {
					$unitSerial = $unitSerial . $serviceOrder['serial' . $i] . '/';
				}
			}
			if (strlen($unitSerial) > 0) {
				$unitSerial = substr($unitSerial, 0, strlen($unitSerial) - 1);
			}
		}
		
		error_log('preparing data Service Order');
		$poApiCostCenter = "";
		
		if (key_exists('po', $job) && $job['po'] != null ) {
			$poApiCostCenter = $poApiCostCenter . $job['po'] . '/';
		}
		if (key_exists('api', $job) && $job['api'] != null) {
			$poApiCostCenter = $poApiCostCenter . $job['api'] . '/';
		}
		if (key_exists('costCenter', $onSiteDailyJob) && $onSiteDailyJob['costCenter'] != null ) {
			$poApiCostCenter = $poApiCostCenter . $onSiteDailyJob['costCenter'] . '/';
		}
		if (strlen($poApiCostCenter) > 0) {
			$poApiCostCenter = substr($poApiCostCenter, 0, strlen($poApiCostCenter) - 1);
		}

		$fromDate = key_exists('fromDate', $onSiteDailyJob) ? gmdate("m/d/y", $onSiteDailyJob['fromDate']) : '';
		$toDate = key_exists('toDate', $onSiteDailyJob) ? gmdate("m/d/y", $onSiteDailyJob['toDate']) : '';
		
		error_log('preparing data PoApiCostCenter');
		$customerCommentArr = key_exists('customerComment', $onSiteDailyJob) ? $this->wrapTextMultiline(array(419, 555, 555), $onSiteDailyJob['customerComment'], 9) : array('', '', '');
		$controls = array(
				'onSiteDailyJob' => key_exists('uid', $job) ? $job['uid'] : '',
	
				'date' => $this->wrapText(120, $fromDate . ' - ' . $toDate, 9),
				'ticketNo' => key_exists('ticketNo', $onSiteDailyJob) ? $this->wrapText(67, $onSiteDailyJob['ticketNo'], 9) : '',
				'wirelineCo' => key_exists('name', $wirelineCompany) ? $this->wrapText(208, $wirelineCompany['name'], 9) : '',
				'oilCompany' => key_exists('name', $oilCompany) ? $this->wrapText(195, $oilCompany['name'], 9) : '',
				'rta' => key_exists('uid', $job) ? $this->wrapText(67, $job['uid'], 9) : '',
				'leaseField' => $this->wrapText(205, $fieldLease, 9),
				'well' => key_exists('wellName', $job) ? $this->wrapText(240, $job['wellName'], 9) : '',
				'rig' => key_exists('rigName', $job) ? $this->wrapText(252, $job['rigName'], 9) : '',
				'county' => key_exists('county', $job) ? $this->wrapText(105, $job['county'], 9) : (key_exists('parish', $job) ? $this->wrapText(105, $job['parish'] . ' Parish', 9) : ''),
				'state' => key_exists('state', $job) ? $this->wrapText(84, $job['state'], 9) : '',
				'ocsg' => key_exists('ocsg', $job) ? $this->wrapText(234, $job['ocsg'], 9) : '',
				'poApiCostCenter' => $this->wrapText(138, $poApiCostCenter, 9), 
				'costCenter' => key_exists('costCenter', $job) ? $this->wrapText(120, $job['costCenter'], 9) : '',
				'unitSerial' => $this->wrapText(242, $unitSerial, 9),
				'jobDescription' => key_exists('description', $onSiteDailyJob) ? $this->wrapText(177, $onSiteDailyJob['description'], 9) : '',
				
				'mannedWorkingHours' => key_exists('workingHours', $onSiteDailyJob) ? $this->wrapText(83, $onSiteDailyJob['workingHours'], 14) : '',
				'mannedStandbyHours' => key_exists('standbyHours', $onSiteDailyJob) ? $this->wrapText(83, $onSiteDailyJob['standbyHours'], 14) : '',
				'offDutyWellsiteHours' => key_exists('offDutyHours', $onSiteDailyJob) ? $this->wrapText(83, $onSiteDailyJob['offDutyHours'], 14) : '',
				'fieldEstimatedDailyCost' => key_exists('fieldEstimatedDailyCost', $onSiteDailyJob) ? $this->wrapText(105, $onSiteDailyJob['fieldEstimatedDailyCost'], 14) : '',
				'customerComment_1' => $customerCommentArr[0],
				'customerComment_2' => $customerCommentArr[1],
				'customerComment_3' => $customerCommentArr[2],
				'customerName' => key_exists('customerName', $onSiteDailyJob) ? $this->wrapText(131, $onSiteDailyJob['customerName'], 9) : '',
				
				'isiOperator' => key_exists('operator', $onSiteDailyJob) ? $this->wrapText(181, $onSiteDailyJob['operator'], 9) : ''
				
		);
		
		$controls['jobDescription'] = $this->wrapText(177, $job['type'] == '0' ? 'Open Hole Wireline' : 'Cased Hole Wireline', 9);
		
		$idxLine = 1;
		foreach ($onSiteDailyJob['itemList'] as $runItem) {
			$controls['from_' . ($idxLine)] = key_exists('from', $runItem) ? $this->wrapText(50, $runItem['from'], 9) : '';
			$controls['to_' . ($idxLine)] = key_exists('to', $runItem) ? $this->wrapText(50, $runItem['to'], 9) : '';
			$activityDate = key_exists('activityDate', $runItem) ? gmdate("m/d/y", $runItem['activityDate']) : '';
			$status = key_exists('status', $runItem) ? $runItem['status'] : '';
			$desc = key_exists('desc', $runItem) ? $runItem['desc'] : '';
			$description =  $activityDate . ' : ' . $status . ' : ' .  $desc;
			//$controls['description_' . ($idx)] = $activityDate . ' : ' . $status . ' : ' .  $desc;
			
			$descLength = strlen($description);
			$maxLine = 81;
			for ($start = 0; $start <= $descLength; ) {// 455 = 81 char font-9pt
				$lineString = substr($description, $start, $maxLine);
				
				$spacebarPos = strrpos($lineString, ' ');
				if (strlen($lineString) >= $maxLine && $spacebarPos) {
					$controls['description_' . ($idxLine)] = substr($description, $start, $spacebarPos + 1);
					$idxLine++;
					$start = $start + $spacebarPos + 1;
				} else {
					$spacebarPos = $maxLine;
					$controls['description_' . ($idxLine)] = substr($description, $start, $spacebarPos);
					$idxLine++;
					$start = $start + $maxLine;
				}
			}
			
		}
		
		if ($idxLine <= 27) {
			error_log('====================== SINGLE PAGE =============================' . $idxLine);
			$report = $c->reportService()->runReport('/reports/isi/dailyJobReport', 'pdf', null, null, $controls);
	
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Description: File Transfer');
			header('Content-Disposition: attachment; filename=DailyJobReport.pdf');
			header('Content-Transfer-Encoding: binary');
			header('Content-Length: ' . strlen($report));
			header('Content-Type: application/pdf');
	
			echo $report;
		} else {

			error_log('====================== MULTI PAGE =============================' . $idxLine);
			ob_start();
			$pdf = new PDFMerger;
			$idx = 1;
			$page = 1;
			
			while($idx < $idxLine) {
				for ($x = 1; $x <= 27; $x++) {
					$controls['from_' . $x] = key_exists('from_' . $idx, $controls) ? $controls['from_' . $idx] : '';	
					$controls['to_' . $x] = key_exists('to_' . $idx, $controls) ? $controls['to_' . $idx] : '';	
					$controls['description_' . $x] = key_exists('description_' . $idx, $controls) ? $controls['description_' . $idx] : '';	
					$idx++;
				}
				$copiedControls = array_merge($controls);
				for($i = 1; $i <= $idxLine; $i++) {
					if ($i > 27) {
						unset($copiedControls['from_' . $i]);
						unset($copiedControls['to_' . $i]);
						unset($copiedControls['description_' . $i]);
					}
				}
				$report = $c->reportService()->runReport('/reports/isi/dailyJobReport', 'pdf', null, null, $copiedControls);
				$fp = fopen($onSiteDailyJob['_id'] . 'page' . $page . '.pdf', 'w');
				fwrite($fp, $report);
				fclose($fp);
				$pdf->addPDF($onSiteDailyJob['_id'] . 'page' . $page . '.pdf', '1');
				error_log('create PDF page ' . $page);
				
				$page++;
			}
			ob_end_clean();
			$pdf->merge('download', 'DailyJobReport.pdf');
		}
		

	}
	//TESTED
	function detailJarSetsImpactOrderPdf_get() {
		error_log('========================================================= detailJarSetsImpactOrderPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$JarSetsImpactOrderModel = $this->db->selectCollection('JarSetsImpactOrder');
		$JarSetsImpactOrder = $JarSetsImpactOrderModel->findOne(array('job' => $this->get('_id')));
		
		$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
		$serviceOrder = $serviceOrderModel->findOne(array('job' => $this->get('_id')));
		
		$jobModel = $this->db->selectCollection('Job');
		$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		
		$onSiteDailyJobModel = $this->db->selectCollection('OnSiteDailyJob');
		$onSiteDailyJob = $onSiteDailyJobModel->findOne(array('job' => $this->get('_id')));
		
		if (key_exists('well', $job)) {
			$innerItemModel = $this->db->selectCollection($job['well']['$ref']);
			$well = $innerItemModel->findOne(array('_id' => $job['well']['$id']));
		} else {
			$well = array();
		}
		if (key_exists('oilCompany', $job) && $job['oilCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
			$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
		} else {
			$oilCompany = array();
		}
		if (key_exists('serviceCompany', $job) && $job['serviceCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['serviceCompany']['$ref']);
			$wirelineCompany = $innerItemModel->findOne(array('_id' => $job['serviceCompany']['$id']));
		} else {
			$wirelineCompany = array();
		}
		if (key_exists('cableType', $JarSetsImpactOrder)) {
			$innerItemModel = $this->db->selectCollection($JarSetsImpactOrder['cableType']['$ref']);
			$cableType = $innerItemModel->findOne(array('_id' => $JarSetsImpactOrder['cableType']['$id']));
		} else {
			$cableType = array();
		}
		
		$blockFieldLease = "";
		if (key_exists('block', $job)) {
			$blockFieldLease = $blockFieldLease . $job['block'] . '/';
		}
		if (key_exists('lease', $job)) {
			$blockFieldLease = $blockFieldLease . $job['lease'] . '/';
		}
		if (key_exists('field', $job)) {
			$blockFieldLease = $blockFieldLease . $job['field'] . '/';
		}
		if (strlen($blockFieldLease) > 0) {
			$blockFieldLease = substr($blockFieldLease, 0, strlen($blockFieldLease) - 1);
		}
		
		$detailsNotesArr = key_exists('detailsNotes', $JarSetsImpactOrder) ? $this->wrapTextMultiline(array(633, 710), $JarSetsImpactOrder['detailsNotes'], 10) : array('', '');
		
		$controls = array(
				'JarSetsImpactOrder' => key_exists('uid', $job) ? $job['uid'] : '',
	
				'orderDate' => key_exists('orderDate', $JarSetsImpactOrder) ? $this->wrapText(253, gmdate("m/d/y", $JarSetsImpactOrder['orderDate']), 10) : '',
				'orderBy' => key_exists('orderedBy', $JarSetsImpactOrder) ? $this->wrapText(153, $JarSetsImpactOrder['orderedBy'], 10) : '',
				'serviceCompany' => key_exists('name', $wirelineCompany) ? $this->wrapText(253, $wirelineCompany['name'], 10) : '',
				'operatingCompany' => key_exists('name', $oilCompany) ? $this->wrapText(253, $oilCompany['name'], 10) : '',
				'contactName' => key_exists('contactName', $JarSetsImpactOrder) ? $this->wrapText(253, $JarSetsImpactOrder['contactName'], 10) : '',
				'contactPhone' => key_exists('contactPhone', $JarSetsImpactOrder) ? $this->wrapText(253, $JarSetsImpactOrder['contactPhone'], 10) : '',
				'contactEmail' => key_exists('contactEmail', $JarSetsImpactOrder) ? $this->wrapText(253, $JarSetsImpactOrder['contactEmail'], 10) : '',
				'rtaNumber' => key_exists('uid', $job) ? $this->wrapText(153, $job['uid'], 10) : '',
				'blockLeaseField' => $this->wrapText(153, $blockFieldLease, 10),
				'wellNumber' => key_exists('wellName', $job) ? $this->wrapText(153, $job['wellName'], 10) : '',
				'rigPlatform' => key_exists('rigName', $job) ? $this->wrapText(153, $job['rigName'], 10) : '',
				'mudWeight' => key_exists('mudWt', $serviceOrder) ? $this->wrapText(253, $serviceOrder['mudWt'], 10) : '',
				'cableType' => key_exists('name', $cableType) ? $this->wrapText(253, $cableType['name'], 10) : '',
				'cableWeightInAir' => ($onSiteDailyJob!=null && key_exists('cableWeight', $onSiteDailyJob)) ? $this->wrapText(253, $onSiteDailyJob['cableWeight'], 10) : '',
				'maxSafePull' => key_exists('maxSafePull', $JarSetsImpactOrder) ? $this->wrapText(153, $JarSetsImpactOrder['maxSafePull'], 10) : '',
				'weakPoint' => key_exists('weakPoint', $JarSetsImpactOrder) ? $this->wrapText(153, $JarSetsImpactOrder['weakPoint'], 10) : '',
				'totalDepth' => key_exists('td', $serviceOrder) ? $this->wrapText(153, $serviceOrder['td'], 10) : '',
				'detailsNotes_1' => $detailsNotesArr[0],
				'detailsNotes_2' => $detailsNotesArr[1],
				"isiRep" => key_exists('technician', $JarSetsImpactOrder) ? $this->wrapText(323, $JarSetsImpactOrder['technician'], 10) : '',
				'date' => key_exists('isiRepDate', $JarSetsImpactOrder) ? $this->wrapText(290, gmdate("m/d/y", $JarSetsImpactOrder['isiRepDate']), 10) : '',
	
		);
	
		foreach ($JarSetsImpactOrder['itemList'] as $idx => $runItem) {
			$controls['run_' . ($idx + 1)] = key_exists('run', $runItem) ? $this->wrapText(54, $runItem['run'], 10) : '';
			$controls['toolstring_' . ($idx + 1)] = key_exists('toolString', $runItem) && is_array($runItem['toolString']) ? $this->wrapText(159, implode(", ", $runItem['toolString']), 10) : '';
			$controls['length_' . ($idx + 1)] = key_exists('length', $runItem) ? $this->wrapText(54, $runItem['length'], 10) : '';
			$controls['weightInAir_' . ($idx + 1)] = key_exists('weightInAir', $runItem) ? $this->wrapText(83, $runItem['weightInAir'], 10) : '';
			$controls['weigthInFluid_' . ($idx + 1)] = key_exists('weightInFluid', $runItem) ? $this->wrapText(58, $runItem['weightInFluid'], 10) : '';
			$controls['surfaceTensionTotalDepth_' . ($idx + 1)] = key_exists('surfaceTensionTotalDepth', $runItem) ? $this->wrapText(74, $runItem['surfaceTensionTotalDepth'], 10) : '';
			$controls['maxToolOuterDiameter_' . ($idx + 1)] = key_exists('maxToolOuterDiameter', $runItem) ? $this->wrapText(80, $runItem['maxToolOuterDiameter'], 10) : '';
			$controls['targetJarSetting_' . ($idx + 1)] = key_exists('targetJarSetting', $runItem) ? $this->wrapText(74, $runItem['targetJarSetting'], 10) : '';
			$controls['targetMaxJarForce_' . ($idx + 1)] = key_exists('targetMaxJarForce', $runItem) ? $this->wrapText(74, $runItem['targetMaxJarForce'], 10) : '';
		}
		$report = $c->reportService()->runReport('/reports/isi/impactProServiceOrder', 'pdf', null, null, $controls);

		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=JarSetsImpactOrder.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
	}
	// DEPRECATED
	function detailRtaPdf_get() {
		error_log('========================================================= detailRtaPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
		 
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$rtaModel = $this->db->selectCollection('RTA');
		$rta = $rtaModel->findOne(array('job' => $this->get('_id')));
		if (key_exists('billTo', $rta)) {
			$innerItemModel = $this->db->selectCollection($rta['billTo']['$ref']);
			$billTo = $innerItemModel->findOne(array('_id' => $rta['billTo']['$id']));
		} else {
			$billTo = array();
		}
		if (key_exists('shipTo', $rta)) {
			$innerItemModel = $this->db->selectCollection($rta['shipTo']['$ref']);
			$shipTo = $innerItemModel->findOne(array('_id' => $rta['shipTo']['$id']));
		} else {
			$shipTo = array();
		}
		if (key_exists('remitTo', $rta)) {
			$innerItemModel = $this->db->selectCollection($rta['remitTo']['$ref']);
			$remitTo = $innerItemModel->findOne(array('_id' => $rta['remitTo']['$id']));
		} else {
			$remitTo = array();
		}
		
		$jobModel = $this->db->selectCollection('Job');
		$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		
		if (key_exists('well', $job)) {
			$innerItemModel = $this->db->selectCollection($job['well']['$ref']);
			$well = $innerItemModel->findOne(array('_id' => $job['well']['$id']));
		} else {
			$well = array();
		}
		if (key_exists('oilCompany', $job) && $job['oilCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
			$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
		} else {
			$oilCompany = array();
		}
		if (key_exists('serviceCompany', $job) && $job['serviceCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['serviceCompany']['$ref']);
			$wirelineCompany = $innerItemModel->findOne(array('_id' => $job['serviceCompany']['$id']));
		} else {
			$wirelineCompany = array();
		}
		//'shipDate' => key_exists('shipDate', $serviceOrder) ? gmdate("m/d/y", $serviceOrder['shipDate']) : '',
		$billToAddress = "";
		if (key_exists('billingStreet', $billTo)) {
			$billToAddress = $billToAddress . $billTo['billingStreet']. "\n";
		}
		if (key_exists('billingCity', $billTo)) {
			$billToAddress = $billToAddress . $billTo['billingCity']. "\n";
		}
		if (key_exists('billingState', $billTo)) {
			$billToAddress = $billToAddress . $billTo['billingState']. "\n";
		}
		if (key_exists('billingZip', $billTo)) {
			$billToAddress = $billToAddress . $billTo['billingZip']. "\n";
		}
		$shipToAddress = "";
		if (key_exists('billingStreet', $shipTo)) {
			$shipToAddress = $shipToAddress . $shipTo['billingStreet']. "\n";
		}
		if (key_exists('billingCity', $shipTo)) {
			$shipToAddress = $shipToAddress . $shipTo['billingCity']. "\n";
		}
		if (key_exists('billingState', $shipTo)) {
			$shipToAddress = $shipToAddress . $shipTo['billingState']. "\n";
		}
		if (key_exists('billingZip', $shipTo)) {
			$shipToAddress = $shipToAddress . $shipTo['billingZip']. "\n";
		}
		$remitToAddress = "";
		if (key_exists('billingStreet', $remitTo)) {
			$remitToAddress = $remitToAddress . $remitTo['billingStreet']. "\n";
		}
		if (key_exists('billingCity', $remitTo)) {
			$remitToAddress = $remitToAddress . $remitTo['billingCity']. "\n";
		}
		if (key_exists('billingState', $remitTo)) {
			$remitToAddress = $remitToAddress . $remitTo['billingState']. "\n";
		}
		if (key_exists('billingZip', $remitTo)) {
			$remitToAddress = $remitToAddress . $remitTo['billingZip']. "\n";
		}
		$controls = array(
				'rta' => key_exists('uid', $job) ? $job['uid'] : '',
				
				'printedOnDate' => key_exists('printedOnDate', $rta) ? gmdate("m/d/y", $rta['printedOnDate']) : '',
				'soldFrom' => key_exists('soldFrom', $rta) ? $rta['soldFrom'] : '',
				
				'billTo' => key_exists('name', $billTo) ? $billTo['name'] : '',
				
				'billToAddress' => $billToAddress,
				'shipTo' => key_exists('name', $shipTo) ? $shipTo['name'] : '',
				'shipToAddress' => $shipToAddress,
				'remitTo' => key_exists('name', $remitTo) ? $remitTo['name'] : '',
				'remitToAddress' => $remitToAddress,
				
				'oilCompany' => key_exists('name', $oilCompany) ? $oilCompany['name'] : '',
				'block' => key_exists('block', $job) ? $job['block'] : '',
				'ocsg' => key_exists('ocsg', $job) ? $job['ocsg'] : '',
				'well' => key_exists('wellName', $job) ? $job['wellName'] : '',
				'bskt' => key_exists('bskt', $rta) ? $rta['bskt'] : '',
				'wireline' => key_exists('name', $wirelineCompany) ? $wirelineCompany['name'] : '',
				'fieldContact' => key_exists('fieldContact', $rta) ? $rta['fieldContact'] : '',
				'afe' => key_exists('afe', $job) ? $job['afe'] : '',
				'po' => key_exists('po', $job) ? $job['po'] : '',
				'costCenter' => key_exists('costCenter', $rta) ? $rta['costCenter'] : '',
				'dailyJob' => key_exists('dailyJob', $rta) ? $rta['dailyJob'] : '',
				'rig' => key_exists('rigName', $job) ? $job['rigName'] : '',
				'customerName' => key_exists('customerName', $rta) ? $rta['customerName'] : '',
				'isiRepName' => key_exists('isiRepName', $rta) ? $rta['isiRepName'] : '',
				'customerDate' => key_exists('customerDate', $rta) ? gmdate("m/d/y", $rta['customerDate']) : '',
				'isiRepDate' => key_exists('isiRepDate', $rta) ? gmdate("m/d/y", $rta['isiRepDate']) : '',
				'page' => '1',
				'subtotalText' => 'Sub Total',
				'subtotalValue' => '',
				'totalText' => 'Total',
				'totalValue' => '',
				'currencyText' => '',
				'currencyValue' => ''
				
		);
		
		$totalPrice = 0;
		foreach ($rta['rentItemList'] as $idx => $rentItem) {
			$controls['item_' . ($idx + 1)] = key_exists('item', $rentItem) ? $rentItem['item'] : '';
			$controls['desc_' . ($idx + 1)] = key_exists('description', $rentItem) ? $rentItem['description'] : '';
			$controls['units_' . ($idx + 1)] = key_exists('units', $rentItem) ? $rentItem['units'] : '';
			$controls['dayPerUnit_' . ($idx + 1)] = key_exists('dayPerUnit', $rentItem) ? $rentItem['dayPerUnit'] : '';
			$controls['ratePerUnit_' . ($idx + 1)] = key_exists('ratePerUnit', $rentItem) ? $rentItem['ratePerUnit'] : '';
			$controls['uom_' . ($idx + 1)] = key_exists('uom', $rentItem) ? $rentItem['uom'] : '';
			$controls['price_' . ($idx + 1)] = key_exists('extendedPrice', $rentItem) ? $rentItem['extendedPrice'] : '';
			if (key_exists('extendedPrice', $rentItem)) {
				$totalPrice += $rentItem['extendedPrice'];
			}
		}
		$controls['subtotalValue'] = $totalPrice;
		$controls['totalValue'] = $totalPrice;
	
		$report = $c->reportService()->runReport('/reports/isi/rta', 'pdf', null, null, $controls);
	
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=RTA.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
	}
	// TESTED
	function detailServiceOrderOpenPdf_get() {
		error_log('========================================================= detailServiceOrderOpenPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
		$serviceOrder = $serviceOrderModel->findOne(array('job' => $this->get('_id')));
	
		if (key_exists('billTo', $serviceOrder) && $serviceOrder['billTo'] != null) {
			$innerItemModel = $this->db->selectCollection($serviceOrder['billTo']['$ref']);
			$billTo = $innerItemModel->findOne(array('_id' => $serviceOrder['billTo']['$id']));
			if (empty($billTo)) {
				$billTo = array();
			}
		} else {
			$billTo = array();
		}
	
		$jobModel = $this->db->selectCollection('Job');
		$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		if (key_exists('oilCompany', $job) && $job['oilCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
			$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
			if (empty($oilCompany)) {
				$oilCompany = array();
			}
		} else {
			$oilCompany = array();
		}
		$wlcoCityStateZip = '';
		if (key_exists('serviceCompany', $job) && $job['serviceCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['serviceCompany']['$ref']);
			$wlco = $innerItemModel->findOne(array('_id' => $job['serviceCompany']['$id']));
			if (empty($wlco)) {
				$wlco = array();
			} else {
				$wlcoCityStateZip = $wlco['billingCity'] . ', ' . $wlco['billingState'] . ' ' . $wlco['billingZip'];
			}
		} else {
			$wlco = array();
		}
		
		
		if (key_exists('rigType', $job) && $job['rigType'] != null) {
			$innerItemModel = $this->db->selectCollection($job['rigType']['$ref']);
			$rigType = $innerItemModel->findOne(array('_id' => $job['rigType']['$id']));
			if (empty($rigType)) {
				$rigType = array();
			}
		} else {
			$rigType = array();
		}
		
		if (key_exists('wirelineEngineer', $job) && $job['wirelineEngineer'] !=null) {
			$innerItemModel = $this->db->selectCollection($job['wirelineEngineer']['$ref']);
			$wirelineEngineer = $innerItemModel->findOne(array('_id' => $job['wirelineEngineer']['$id']));
			if (empty($wirelineEngineer)) {
				$wirelineEngineer = array();
			}
		} else {
			$wirelineEngineer = array();
		}
		
		if (key_exists('companyMan', $job) && $job['companyMan'] != null) {
			$innerItemModel = $this->db->selectCollection($job['companyMan']['$ref']);
			$companyMan = $innerItemModel->findOne(array('_id' => $job['companyMan']['$id']));
			if (empty($companyMan)) {
				$companyMan = array();
			}
		} else {
			$companyMan = array();
		}
		
		
		$blockFieldLease = "";
		if (key_exists('block', $job)) {
			$blockFieldLease = $blockFieldLease . $job['block'] . '/';
		}
		if (key_exists('lease', $job)) {
			$blockFieldLease = $blockFieldLease . $job['lease'] . '/';
		}
		if (key_exists('field', $job)) {
			$blockFieldLease = $blockFieldLease . $job['field'] . '/';
		}
		if (strlen($blockFieldLease) > 0) {
			$blockFieldLease = substr($blockFieldLease, 0, strlen($blockFieldLease) - 1);
		}
		$countyCityState = "";
		if (key_exists('county', $job)) {
			$countyCityState = $countyCityState . $job['county'] . '/';
		} else {
			if (key_exists('parish', $job)) {
				$countyCityState = $countyCityState . $job['parish'] . ' Parish/';
			}
		}
		if (key_exists('city', $job)) {
			$countyCityState = $countyCityState . $job['city'] . '/';
		}
		if (key_exists('state', $job)) {
			$countyCityState = $countyCityState . $job['state'] . '/';
		}
		if (strlen($countyCityState) > 0) {
			$countyCityState = substr($countyCityState, 0, strlen($countyCityState) - 1);
		}
		$ocsgStateLease = "";
		if (key_exists('ocsg', $job)) {
			$ocsgStateLease = $ocsgStateLease . $job['ocsg'] . '/';
		}
		if (key_exists('stateLease', $job)) {
			$ocsgStateLease = $ocsgStateLease . $job['stateLease'] . '/';
		}
		if (strlen($ocsgStateLease) > 0) {
			$ocsgStateLease = substr($ocsgStateLease, 0, strlen($ocsgStateLease) - 1);
		}
		$jarTypes = array();
		$toolCount = $serviceOrder['toolCount'];
		for ($i = 1; $i <= 4; $i++) {
			if ($i <= $toolCount && key_exists('toolType' . $i, $serviceOrder)) {
				$jarTypes[] = $serviceOrder['toolType' . $i];
			}
		}
		$jarTypes = array_values(array_unique($jarTypes));
		$baskets = array();
		for ($i = 1; $i <= $serviceOrder['basketCount']; $i++) {
			if (key_exists('basket' . $i, $serviceOrder)) {
				$baskets[] = $serviceOrder['basket' . $i];
			}
		}
		$slings = array();
		for ($i = 1; $i <= $serviceOrder['slingCount']; $i++) {
			if (key_exists('sling' . $i, $serviceOrder)) {
				$slings[] = $serviceOrder['sling' . $i];
			}
		}
		
		$controls = array(
				
				'shipDate' => key_exists('shipDate', $serviceOrder) ? $this->wrapText(82, gmdate("m/d/y", $serviceOrder['shipDate']), 10) : '',
				'shipCharges' => key_exists('shipCharges', $serviceOrder) ? $serviceOrder['shipCharges'] : '',
				'rta' => key_exists('uid', $job) ? $this->wrapText(147, $job['uid'], 10) : '',
				
				'jobType' => key_exists('environment', $job) ? $job['environment'] : '',
				'exemptionStatus' => key_exists('exemptionStatus', $serviceOrder) ? $serviceOrder['exemptionStatus'] : '',
				'shippingMethod' => key_exists('shippingMethod', $serviceOrder) ? $this->wrapText(164, $serviceOrder['shippingMethod'], 10) : '',
				'billTo' => key_exists('name', $billTo) ? $this->wrapText(224, $billTo['name'], 10) : '',
				'billToAddress' => key_exists('billToAddress', $serviceOrder) ? $this->wrapText(224, $serviceOrder['billToAddress'], 10) : '',
				'billToAddress2' => key_exists('billToAddress2', $serviceOrder) ? $this->wrapText(198, $serviceOrder['billToAddress2'], 10) : '',
				'billToPhone' => key_exists('billToPhone', $serviceOrder) ? $this->wrapText(219, $serviceOrder['billToPhone'], 10) : '',
				'wlco' => key_exists('name', $wlco) ? $this->wrapText(217, $wlco['name'], 10) : '',
				'wlcoAddress' => key_exists('billingStreet', $wlco) ? $this->wrapText(222, $wlco['billingStreet'], 10) : '',
				//'wlcoAddress2' => key_exists('billingCity', $wlco) ? $wlco['billingCity'] : '',
				'wlcoAddress2' => $this->wrapText(196, $wlcoCityStateZip, 10),
				'wlcoPhone' => key_exists('phone', $wlco) ? $this->wrapText(218, $wlco['phone'], 10) : '',
				'wlcoFax' => key_exists('fax', $wlco) ? $this->wrapText(228, $wlco['fax'], 10) : '',
				'wlcoEmail' => key_exists('email', $wirelineEngineer) ? $this->wrapText(223, $wirelineEngineer['email'], 10) : '',
				
				"jarType" =>$this->wrapText(218, implode(", ", $jarTypes), 10),
				"serial1" => $toolCount > 0 && key_exists('serial1', $serviceOrder) ? $this->wrapText(93, $serviceOrder['serial1'], 10) : '',
				"serial1Value" => $toolCount > 0 && key_exists('serialValue1', $serviceOrder) ? $this->wrapText(97, $serviceOrder['serialValue1'], 10) : '',
				"serial2" => $toolCount > 1 && key_exists('serial2', $serviceOrder) ? $this->wrapText(93, $serviceOrder['serial2'], 10) : '',
				"serial2Value" => $toolCount > 1 && key_exists('serialValue2', $serviceOrder) ? $this->wrapText(97, $serviceOrder['serialValue2'], 10) : '',
				"serial3" => $toolCount > 2 && key_exists('serial3', $serviceOrder) ? $this->wrapText(93, $serviceOrder['serial3'], 10) : '',
				"serial3Value" => $toolCount > 2 && key_exists('serialValue3', $serviceOrder) ? $this->wrapText(97, $serviceOrder['serialValue3'], 10) : '',
				"serial4" => $toolCount > 3 && key_exists('serial4', $serviceOrder) ? $this->wrapText(93, $serviceOrder['serial4'], 10) : '',
				"serial4Value" => $toolCount > 3 && key_exists('serialValue4', $serviceOrder) ? $this->wrapText(97, $serviceOrder['serialValue4'], 10) : '',
				"liftBoat" => key_exists('liftBoat', $serviceOrder) ? $this->wrapText(237, $serviceOrder['liftBoat'], 10) : '',
				"sap" => key_exists('sap', $serviceOrder) ? $this->wrapText(248, $serviceOrder['sap'], 10) : '',
				"dock" => key_exists('dock', $serviceOrder) ? $this->wrapText(247, $serviceOrder['dock'], 10) : '',
				"dockLoc" => key_exists('dockLoc', $serviceOrder) ? $this->wrapText(236, $serviceOrder['dockLoc'], 10) : '',
				"basket" => $this->wrapText(236, implode('/', $baskets), 10),
				"sling" => $this->wrapText(244, implode('/', $slings), 10),
				"loadOut" => key_exists('loadOut', $serviceOrder) ? $this->wrapText(425, $serviceOrder['loadOut'], 10) : '',
				"engineer" => key_exists('firstName', $wirelineEngineer) ? $this->wrapText(214, $wirelineEngineer['titleName'] . " " . $wirelineEngineer['firstName'] . " " . $wirelineEngineer['lastName'], 10) : '',
				"coman" => key_exists('firstName', $companyMan) ? $this->wrapText(215, $companyMan['titleName'] . " " . $companyMan['firstName'] . " " . $companyMan['lastName'], 10) : '',
				"servicePerformed" => key_exists('servicePerformed', $serviceOrder) ? $this->wrapText(425, $serviceOrder['servicePerformed'], 10) : '',
				"noOfRuns" => key_exists('runs', $job) ? $this->wrapText(192, $job['runs'], 10) : '',
				"bhp" => key_exists('bhp', $serviceOrder) ? $this->wrapText(213, $serviceOrder['bhp'], 10) : '',
				"td" => key_exists('td', $serviceOrder) ? $this->wrapText(213, $serviceOrder['td'], 10) : '',
				"isCO2" => key_exists('isCO2', $job) ? ($job['isCO2'] ? '1' : '') : '',
				"isH2S" => key_exists('isH2S', $job) ? ($job['isH2S'] ? '1' : '') : '',
				"isHighTemp" => key_exists('isHighTemp', $job) ? ($job['isHighTemp'] ? '1' : '') : '',
				"isHighPress" => key_exists('isHighPress', $job) ? ($job['isHighPress'] ? '1' : '') : '',
				"isOther" => key_exists('isOther', $job) ? ($job['isOther'] ? '1' : '') : '',
				"isOtherVal" => key_exists('isOtherVal', $job) ? $this->wrapText(150, $job['isOtherVal'], 10) : '',
				"dev" => key_exists('deviation', $job) ? $this->wrapText(237, $job['deviation'], 10) : '',
				"temp" => key_exists('temp', $job) ? $this->wrapText(237, $job['temp'], 10) : '',
				"mudWt" => key_exists('mudWt', $serviceOrder) ? $this->wrapText(237, $serviceOrder['mudWt'], 10) : '',
				"details" => key_exists('details', $serviceOrder) ? $this->wrapText(493, $serviceOrder['details'], 10) : '',
				
				"companyName" => key_exists('name', $oilCompany) ? $this->wrapText(208, $oilCompany['name'], 10) : '',
				"county" => $this->wrapText(203, $countyCityState, 10),
				"blk" => $this->wrapText(204, $blockFieldLease, 10),
				"ocsg" => $this->wrapText(203, $ocsgStateLease, 10),
				"well" => key_exists('wellName', $job) ? $this->wrapText(243, $job['wellName'], 10) : '',
				"rigName" => key_exists('rigName', $job) ? $this->wrapText(231, $job['rigName'], 10) : '',
				"rigPhone" => key_exists('rigPhone', $job) ? $this->wrapText(223, $job['rigPhone'], 10) : '',
				"rigFax" => key_exists('rigFax', $job) ? $this->wrapText(239, $job['rigFax'], 10) : '',
				"platform" => key_exists('name', $rigType) ? $this->wrapText(205, $rigType['name'], 10) : '',
				"afe" => key_exists('afe', $job) ? $this->wrapText(250, $job['afe'], 10) : '',
				"po" => key_exists('po', $job) ? $this->wrapText(248, $job['po'], 10) : '',
				
				"isiRepName" => key_exists('isiRepName', $serviceOrder) ? $this->wrapText(234, $serviceOrder['isiRepName'], 10) : '',
				"isiRepDate" => key_exists('isiRepDate', $serviceOrder) ? $this->wrapText(236, gmdate("m/d/y", $serviceOrder['isiRepDate']), 10) : ''
				
				
		);
		
		$pdf = new PDFMerger;
		//error_log('-----------------control : \n' . var_export($controls, true));
		$report = $c->reportService()->runReport('/reports/isi/serviceOrderOpenHoleJobs', 'pdf', null, null, $controls);
		$fp = fopen($serviceOrder['_id'] . 'page1' . '.pdf', 'w');
		fwrite($fp, $report);
		fclose($fp);
		$pdf->addPDF($serviceOrder['_id'] . 'page1' . '.pdf', '1');
		
		if ($toolCount > 4) {
			for ($page = 2, $x = 5; $x <= $toolCount; $x = $x + 4, $page++) {
				$jarTypes = array();
				for ($i = $x; $i <= $x + 4; $i++) {
					if ($i <= $toolCount && key_exists('toolType' . $i, $serviceOrder)) {
						$jarTypes[] = $serviceOrder['toolType' . $i];
					}
				}
				$jarTypes = array_values(array_unique($jarTypes));
				$controls["jarType"] = $this->wrapText(218, implode(", ", $jarTypes), 10);
				$controls["serial1"] = $x <= $toolCount && key_exists('serial' . $x, $serviceOrder) ? $this->wrapText(93, $serviceOrder['serial' . $x], 10) : '';
				$controls["serial1Value"] = $x <= $toolCount && key_exists('serialValue' . $x, $serviceOrder) ? $this->wrapText(97, $serviceOrder['serialValue' . $x], 10) : '';
				$controls["serial2"] = ($x+1) <= $toolCount && key_exists('serial' . ($x+1), $serviceOrder) ? $this->wrapText(93, $serviceOrder['serial' . ($x+1)], 10) : '';
				$controls["serial2Value"] = ($x+1) <= $toolCount && key_exists('serialValue' . ($x+1), $serviceOrder) ? $this->wrapText(97, $serviceOrder['serialValue' . ($x+1)], 10) : '';
				$controls["serial3"] = ($x+2) <= $toolCount && key_exists('serial' . ($x+2), $serviceOrder) ? $this->wrapText(93, $serviceOrder['serial' . ($x+2)], 10) : '';
				$controls["serial3Value"] = ($x+2) <= $toolCount && key_exists('serialValue' . ($x+2), $serviceOrder) ? $this->wrapText(97, $serviceOrder['serialValue' . ($x+2)], 10) : '';
				$controls["serial4"] = ($x+3) <= $toolCount && key_exists('serial' . ($x+3), $serviceOrder) ? $this->wrapText(93, $serviceOrder['serial' . ($x+2)], 10) : '';
				$controls["serial4Value"] = ($x+3) <= $toolCount && key_exists('serialValue' . ($x+3), $serviceOrder) ? $this->wrapText(97, $serviceOrder['serialValue' . ($x+3)], 10) : '';
				
				$report = $c->reportService()->runReport('/reports/isi/serviceOrderOpenHoleJobs', 'pdf', null, null, $controls);
				$fp = fopen($serviceOrder['_id'] . 'page' . $page . '.pdf', 'w');
				fwrite($fp, $report);
				fclose($fp);
				$pdf->addPDF($serviceOrder['_id'] . 'page' . $page . '.pdf', '1');
			}
		}
		ob_end_clean();
		try {
			$pdf->merge('download', 'ServiceOrder.pdf');
		} catch(Exception $e) {
			error_log('ERROR');
			error_log($e);
		}

// 		foreach($controls as $key => $value) {
// 			error_log($key . '=>' . $value);
// 		}
	
		/*
		$report = $c->reportService()->runReport('/reports/isi/serviceOrderOpenHoleJobs', 'pdf', null, null, $controls);
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=ServiceOrder.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
		
		echo $report;
		*/
	}
	// TESTED
	function detailServiceOrderCasedPdf_get() {
		error_log('========================================================= detailServiceOrderCasedPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
		$serviceOrder = $serviceOrderModel->findOne(array('job' => $this->get('_id')));
	
		if (key_exists('billTo', $serviceOrder) && $serviceOrder['billTo'] != null) {
			$innerItemModel = $this->db->selectCollection($serviceOrder['billTo']['$ref']);
			$billTo = $innerItemModel->findOne(array('_id' => $serviceOrder['billTo']['$id']));
			if (empty($billTo)) {
				$billTo = array();
			}
		} else {
			$billTo = array();
		}
	
		$jobModel = $this->db->selectCollection('Job');
		$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		
		
		if (key_exists('oilCompany', $job) && $job['oilCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['oilCompany']['$ref']);
			$oilCompany = $innerItemModel->findOne(array('_id' => $job['oilCompany']['$id']));
			if (empty($oilCompany)) {
				$oilCompany = array();
			}
		} else {
			$oilCompany = array();
		}
		
		$wlcoCityStateZip = '';
		if (key_exists('serviceCompany', $job) && $job['serviceCompany'] != null) {
			$innerItemModel = $this->db->selectCollection($job['serviceCompany']['$ref']);
			$wlco = $innerItemModel->findOne(array('_id' => $job['serviceCompany']['$id']));
			if (empty($wlco)) {
				$wlco = array();
			} else {
				$wlcoCityStateZip = $wlco['billingCity'] . ', ' . $wlco['billingState'] . ' ' . $wlco['billingZip'];
			}
		} else {
			$wlco = array();
		}
		
		if (key_exists('rigType', $job) && $job['rigType'] != null) {
			$innerItemModel = $this->db->selectCollection($job['rigType']['$ref']);
			$rigType = $innerItemModel->findOne(array('_id' => $job['rigType']['$id']));
			if (empty($rigType)) {
				$rigType = array();
			}
		} else {
			$rigType = array();
		}
		
		if (key_exists('wirelineEngineer', $job) && $job['wirelineEngineer'] !=null) {
			$innerItemModel = $this->db->selectCollection($job['wirelineEngineer']['$ref']);
			$wirelineEngineer = $innerItemModel->findOne(array('_id' => $job['wirelineEngineer']['$id']));
			if (empty($wirelineEngineer)) {
				$wirelineEngineer = array();
			}
		} else {
			$wirelineEngineer = array();
		}
		
		if (key_exists('companyMan', $job) && $job['companyMan'] != null) {
			$innerItemModel = $this->db->selectCollection($job['companyMan']['$ref']);
			$companyMan = $innerItemModel->findOne(array('_id' => $job['companyMan']['$id']));
			if (empty($companyMan)) {
				$companyMan = array();
			}
		} else {
			$companyMan = array();
		}
		
		$projectPlatform = "";
		if (key_exists('platform', $job)) {
			$projectPlatform = $projectPlatform . $job['platform'] . '/';
		}
		if (key_exists('county', $job)) {
			$projectPlatform = $projectPlatform . $job['county'] . '/';
		} else {
			if (key_exists('parish', $job)) {
				$projectPlatform = $projectPlatform . $job['parish'] . ' Parish/';
			}
		}
		if (key_exists('city', $job)) {
			$projectPlatform = $projectPlatform . $job['city'] . '/';
		}
		if (key_exists('state', $job)) {
			$projectPlatform = $projectPlatform . $job['state'] . '/';
		}
		if (strlen($projectPlatform) > 0) {
			$projectPlatform = substr($projectPlatform, 0, strlen($projectPlatform) - 1);
		}
		
		$blockFieldLease = "";
		if (key_exists('block', $job)) {
			$blockFieldLease = $blockFieldLease . $job['block'] . '/';
		}
		if (key_exists('lease', $job)) {
			$blockFieldLease = $blockFieldLease . $job['lease'] . '/';
		}
		if (key_exists('field', $job)) {
			$blockFieldLease = $blockFieldLease . $job['field'] . '/';
		}
		if (strlen($blockFieldLease) > 0) {
			$blockFieldLease = substr($blockFieldLease, 0, strlen($blockFieldLease) - 1);
		}
		
		$countyCityState = "";
		if (key_exists('county', $job)) {
			$countyCityState = $countyCityState . $job['county'] . '/';
		} else {
			if (key_exists('parish', $job)) {
				$countyCityState = $countyCityState . $job['parish'] . ' Parish/';
			}
		}
		if (key_exists('city', $job)) {
			$countyCityState = $countyCityState . $job['city'] . '/';
		}
		if (key_exists('state', $job)) {
			$countyCityState = $countyCityState . $job['state'] . '/';
		}
		if (strlen($countyCityState) > 0) {
			$countyCityState = substr($countyCityState, 0, strlen($countyCityState) - 1);
		}
		$ocsgStateLease = "";
		if (key_exists('ocsg', $job)) {
			$ocsgStateLease = $ocsgStateLease . $job['ocsg'] . '/';
		}
		if (key_exists('stateLease', $job)) {
			$ocsgStateLease = $ocsgStateLease . $job['stateLease'] . '/';
		}
		if (strlen($ocsgStateLease) > 0) {
			$ocsgStateLease = substr($ocsgStateLease, 0, strlen($ocsgStateLease) - 1);
		}
		$toolCount = $serviceOrder['toolCount'];
		$controls = array(
	
				'shipDate' => key_exists('shipDate', $serviceOrder) ? $this->wrapText(85, gmdate("m/d/y", $serviceOrder['shipDate']), 10) : '',
				'shipCharges' => key_exists('shipCharges', $serviceOrder) ? $serviceOrder['shipCharges'] : '',
				'rta' => key_exists('uid', $job) ? $this->wrapText(148, $job['uid'], 10) : '',
	
				'jobType' => key_exists('environment', $job) ? $job['environment'] : '',
				'exemptionStatus' => key_exists('exemptionStatus', $serviceOrder) ? $serviceOrder['exemptionStatus'] : '',
				'shippingMethod' => key_exists('shippingMethod', $serviceOrder) ? $this->wrapText(144, $serviceOrder['shippingMethod'], 10) : '',
				'billTo' => key_exists('name', $billTo) ? $this->wrapText(235, $billTo['name'], 10) : '',
				'billToAddress' => key_exists('billToAddress', $serviceOrder) ? $this->wrapText(230, $serviceOrder['billToAddress'], 10) : '',
				'billToAddress2' => key_exists('billToAddress2', $serviceOrder) ? $this->wrapText(206, $serviceOrder['billToAddress2'], 10) : '',
				'billToPhone' => key_exists('billToPhone', $serviceOrder) ? $this->wrapText(230, $serviceOrder['billToPhone'], 10) : '',
				'wlco' => key_exists('name', $wlco) ? $this->wrapText(208, $wlco['name'], 10) : '',
				'wlcoAddress' => key_exists('billingStreet', $wlco) ? $this->wrapText(229, $wlco['billingStreet'], 10) : '',
				'wlcoAddress2' => $this->wrapText(208, $wlcoCityStateZip, 10),
				'wlcoPhone' => key_exists('phone', $wlco) ? $this->wrapText(229, $wlco['phone'], 10) : '',
				'wlcoFax' => key_exists('fax', $wlco) ? $this->wrapText(243, $wlco['fax'], 10) : '',
				'wlcoEmail' => key_exists('email', $wirelineEngineer) ? $this->wrapText(236, $wirelineEngineer['email'], 10) : '',
	
				"jarType" => key_exists('jarType', $serviceOrder) ? $this->wrapText(85, $serviceOrder['jarType'], 10) : '',
				"serial1" => key_exists('serial1', $serviceOrder) ? $serviceOrder['serial1'] : '',
				"serial1Value" => key_exists('serialValue1', $serviceOrder) ? $serviceOrder['serialValue1'] : '',
				"serial2" => key_exists('serial2', $serviceOrder) ? $serviceOrder['serial2'] : '',
				"serial2Value" => key_exists('serialValue2', $serviceOrder) ? $serviceOrder['serialValue2'] : '',
				"serial3" => key_exists('serial3', $serviceOrder) ? $serviceOrder['serial3'] : '',
				"serial3Value" => key_exists('serialValue3', $serviceOrder) ? $serviceOrder['serialValue3'] : '',
				"liftBoat" => key_exists('liftBoat', $serviceOrder) ? $this->wrapText(85, $serviceOrder['liftBoat'], 10) : '',
				"sap" => key_exists('sap', $serviceOrder) ? $this->wrapText(85, $serviceOrder['sap'], 10) : '',
				"dock" => key_exists('dock', $serviceOrder) ? $this->wrapText(85, $serviceOrder['dock'], 10) : '',
				"dockLoc" => key_exists('dockLoc', $serviceOrder) ? $this->wrapText(85, $serviceOrder['dockLoc'], 10) : '',
				"basket" => key_exists('basket', $serviceOrder) ? $this->wrapText(85, $serviceOrder['basket'], 10) : '',
				"sling" => key_exists('sling', $serviceOrder) ? $this->wrapText(85, $serviceOrder['sling'], 10) : '',
				"loadOut" => key_exists('loadOut', $serviceOrder) ? $this->wrapText(85, $serviceOrder['loadOut'], 10) : '',
				"engineer" => (key_exists('firstName', $wirelineEngineer) ? $this->wrapText(85, $wirelineEngineer['titleName'] . " " . $wirelineEngineer['firstName'] . " " . $wirelineEngineer['lastName'], 10) : ''),
				"coman" => (key_exists('firstName', $companyMan) ? $this->wrapText(227, $companyMan['titleName'] . " " . $companyMan['firstName'] . " " . $companyMan['lastName'], 10) : ''),
				"servicePerformed" => key_exists('servicePerformed', $serviceOrder) ? $this->wrapText(428, $serviceOrder['servicePerformed'], 10) : '',
				"noOfRuns" => key_exists('runs', $job) ? $this->wrapText(85, $job['runs'], 10) : '',
				"bhp" => key_exists('bhp', $serviceOrder) ? $this->wrapText(85, $serviceOrder['bhp'], 10) : '',
				"td" => key_exists('td', $serviceOrder) ? $this->wrapText(85, $serviceOrder['td'], 10) : '',
				"isCO2" => key_exists('isCO2', $job) ? ($job['isCO2'] ? '1' : '') : '',
				"isH2S" => key_exists('isH2S', $job) ? ($job['isH2S'] ? '1' : '') : '',
				"isHighTemp" => key_exists('isHighTemp', $job) ? ($job['isHighTemp'] ? '1' : '') : '',
				"isHighPress" => key_exists('isHighPress', $job) ? ($job['isHighPress'] ? '1' : '') : '',
				"isOther" => key_exists('isOther', $job) ? ($job['isOther'] ? '1' : '') : '',
				"isOtherVal" => key_exists('isOtherVal', $job) ? $this->wrapText(174, $job['isOtherVal'], 10) : '',
				"dev" => key_exists('deviation', $job) ? $this->wrapText(85, $job['deviation'], 10) : '',
				"temp" => key_exists('temp', $job) ? $this->wrapText(85, $job['temp'], 10) : '',
				"mudWt" => key_exists('mudWt', $serviceOrder) ? $this->wrapText(85, $serviceOrder['mudWt'], 10) : '',
				"details" => key_exists('details', $serviceOrder) ? $this->wrapText(487, $serviceOrder['details'], 10) : '',
	
				"companyName" => key_exists('name', $oilCompany) ? $this->wrapText(173, $oilCompany['name'], 10) : '',
				"blk" => $this->wrapText(197, $blockFieldLease, 10),
				"ocsg" => $this->wrapText(193, $ocsgStateLease, 10),
				"well" => key_exists('wellName', $job) ? $this->wrapText(244, $job['wellName'], 10) : '',
				"rigName" => key_exists('rigName', $job) ? $this->wrapText(229, $job['rigName'], 10) : '',
				"rigPhone" => key_exists('rigPhone', $job) ? $this->wrapText(224, $job['rigPhone'], 10) : '',
				"rigFax" => key_exists('rigFax', $job) ? $this->wrapText(85, $job['rigFax'], 10) : '',
				"projectPlatform" => key_exists('name', $rigType) ? $this->wrapText(203, $rigType['name'], 10) : '',
				"county" => $this->wrapText(193, $countyCityState, 10),
				"afe" => key_exists('afe', $job) ? $this->wrapText(247, $job['afe'], 10) : '',
				"po" => key_exists('po', $job) ? $this->wrapText(252, $job['po'], 10) : '',
				
				"toolType_1" => $toolCount > 0 && key_exists('toolType1', $serviceOrder) ? $this->wrapText(217, $serviceOrder['toolType1'], 10) : '',
				"serial_1" => $toolCount > 0 && key_exists('serial1', $serviceOrder) ? $this->wrapText(89, $serviceOrder['serial1'], 10) : '',
				"serial_1Value" => $toolCount > 0 && key_exists('serialValue1', $serviceOrder) ? $this->wrapText(107, $serviceOrder['serialValue1'], 10) : '',
				"connection_1" => $toolCount > 0 && key_exists('connection1', $serviceOrder) ? $this->wrapText(79, $serviceOrder['connection1'], 10) : '',
				"fn_1" => $toolCount > 0 && key_exists('fn1', $serviceOrder) ? $this->wrapText(111, $serviceOrder['fn1'], 10) : '',
				
				"toolType_2" => $toolCount > 1 && key_exists('toolType2', $serviceOrder) ? $this->wrapText(227, $serviceOrder['toolType2'], 10) : '',
				"serial_2" => $toolCount > 1 && key_exists('serial2', $serviceOrder) ? $this->wrapText(89, $serviceOrder['serial2'], 10) : '',
				"serial_2Value" => $toolCount > 1 && key_exists('serialValue2', $serviceOrder) ? $this->wrapText(121, $serviceOrder['serialValue2'], 10) : '',
				"connection_2" => $toolCount > 1 && key_exists('connection2', $serviceOrder) ? $this->wrapText(79, $serviceOrder['connection2'], 10) : '',
				"fn_2" => $toolCount > 1 && key_exists('fn2', $serviceOrder) ? $this->wrapText(121, $serviceOrder['fn2'], 10) : '',
				
				"toolType_3" => $toolCount > 2 && key_exists('toolType3', $serviceOrder) ? $this->wrapText(217, $serviceOrder['toolType3'], 10) : '',
				"serial_3" => $toolCount > 2 && key_exists('serial3', $serviceOrder) ? $this->wrapText(92, $serviceOrder['serial3'], 10) : '',
				"serial_3Value" => $toolCount > 2 && key_exists('serialValue3', $serviceOrder) ? $this->wrapText(111, $serviceOrder['serialValue3'], 10) : '',
				"connection_3" => $toolCount > 2 && key_exists('connection3', $serviceOrder) ? $this->wrapText(79, $serviceOrder['connection3'], 10) : '',
				"fn_3" => $toolCount > 2 && key_exists('fn3', $serviceOrder) ? $this->wrapText(111, $serviceOrder['fn3'], 10) : '',
				
				"toolType_4" => $toolCount > 3 && key_exists('toolType4', $serviceOrder) ? $this->wrapText(227, $serviceOrder['toolType4'], 10) : '',
				"serial_4" => $toolCount > 3 && key_exists('serial4', $serviceOrder) ? $this->wrapText(89, $serviceOrder['serial4'], 10) : '',
				"serial_4Value" => $toolCount > 3 && key_exists('serialValue4', $serviceOrder) ? $this->wrapText(117, $serviceOrder['serialValue4'], 10) : '',
				"connection_4" => $toolCount > 3 && key_exists('connection4', $serviceOrder) ? $this->wrapText(79, $serviceOrder['connection4'], 10) : '',
				"fn_4" => $toolCount > 3 && key_exists('fn4', $serviceOrder) ? $this->wrapText(117, $serviceOrder['fn4'], 10) : '',
				
				"orderedBy" => key_exists('orderedBy', $serviceOrder) ? $this->wrapText(217, $serviceOrder['orderedBy'], 10) : '',
				"snCo_1" => key_exists('snco1', $serviceOrder) ? $this->wrapText(231, $serviceOrder['snco1'], 10) : '',
				"snCo_2" => key_exists('snco2', $serviceOrder) ? $this->wrapText(231, $serviceOrder['snco2'], 10) : '',
				
	
				"isiRepName" => key_exists('isiRepName', $serviceOrder) ? $this->wrapText(226, $serviceOrder['isiRepName'], 10) : '',
				"isiRepDate" => key_exists('isiRepDate', $serviceOrder) ? $this->wrapText(247, gmdate("m/d/y", $serviceOrder['isiRepDate']), 10) : '',
	
	
		);
		$pdf = new PDFMerger;
		//error_log('-----------------control : \n' . var_export($controls, true));
		$report = $c->reportService()->runReport('/reports/isi/serviceOrderCasedHoleJobs', 'pdf', null, null, $controls);
		$fp = fopen($serviceOrder['_id'] . 'page1' . '.pdf', 'w');
		fwrite($fp, $report);
		fclose($fp);
		$pdf->addPDF($serviceOrder['_id'] . 'page1' . '.pdf', '1');
		
		if ($toolCount > 4) {
			for ($page = 2, $x = 5; $x <= $toolCount; $x = $x + 4, $page++) {
				$controls["toolType_1"] = $x <= $toolCount && key_exists('toolType' . $x, $serviceOrder) ? $this->wrapText(217, $serviceOrder['toolType' . $x], 10) : '';
				$controls["serial_1"] = $x <= $toolCount && key_exists('serial' . $x, $serviceOrder) ? $this->wrapText(89, $serviceOrder['serial' . $x], 10) : '';
				$controls["serial_1Value"] = $x <= $toolCount && key_exists('serialValue' . $x, $serviceOrder) ? $this->wrapText(107, $serviceOrder['serialValue' . $x], 10) : '';
				$controls["connection_1"] = $x <= $toolCount && key_exists('connection' . $x, $serviceOrder) ? $this->wrapText(79, $serviceOrder['connection' . $x], 10) : '';
				$controls["fn_1"] = $x <= $toolCount && key_exists('fn' . $x, $serviceOrder) ? $this->wrapText(111, $serviceOrder['fn' . $x], 10) : '';
				
				$controls["toolType_2"] = ($x+1) <= $toolCount && key_exists('toolType' . ($x+1), $serviceOrder) ? $this->wrapText(217, $serviceOrder['toolType' . ($x+1)], 10) : '';
				$controls["serial_2"] = ($x+1) <= $toolCount && key_exists('serial' . ($x+1), $serviceOrder) ? $this->wrapText(89, $serviceOrder['serial' . ($x+1)], 10) : '';
				$controls["serial_2Value"] = ($x+1) <= $toolCount && key_exists('serialValue' . ($x+1), $serviceOrder) ? $this->wrapText(107, $serviceOrder['serialValue' . ($x+1)], 10) : '';
				$controls["connection_2"] = ($x+1) <= $toolCount && key_exists('connection' . ($x+1), $serviceOrder) ? $this->wrapText(79, $serviceOrder['connection' . ($x+1)], 10) : '';
				$controls["fn_2"] = ($x+1) <= $toolCount && key_exists('fn' . ($x+1), $serviceOrder) ? $this->wrapText(111, $serviceOrder['fn' . ($x+1)], 10) : '';
				
				$controls["toolType_3"] = ($x+2) <= $toolCount && key_exists('toolType' . ($x+2), $serviceOrder) ? $this->wrapText(217, $serviceOrder['toolType' . ($x+2)], 10) : '';
				$controls["serial_3"] = ($x+2) <= $toolCount && key_exists('serial' . ($x+2), $serviceOrder) ? $this->wrapText(89, $serviceOrder['serial' . ($x+2)], 10) : '';
				$controls["serial_3Value"] = ($x+2) <= $toolCount && key_exists('serialValue' . ($x+2), $serviceOrder) ? $this->wrapText(107, $serviceOrder['serialValue' . ($x+2)], 10) : '';
				$controls["connection_3"] = ($x+2) <= $toolCount && key_exists('connection' . ($x+2), $serviceOrder) ? $this->wrapText(79, $serviceOrder['connection' . ($x+2)], 10) : '';
				$controls["fn_3"] = ($x+2) <= $toolCount && key_exists('fn' . ($x+2), $serviceOrder) ? $this->wrapText(111, $serviceOrder['fn' . ($x+2)], 10) : '';
				
				$controls["toolType_4"] = ($x+3) <= $toolCount && key_exists('toolType' . ($x+3), $serviceOrder) ? $this->wrapText(217, $serviceOrder['toolType' . ($x+3)], 10) : '';
				$controls["serial_4"] = ($x+3) <= $toolCount && key_exists('serial' . ($x+3), $serviceOrder) ? $this->wrapText(89, $serviceOrder['serial' . ($x+3)], 10) : '';
				$controls["serial_4Value"] = ($x+3) <= $toolCount && key_exists('serialValue' . ($x+3), $serviceOrder) ? $this->wrapText(107, $serviceOrder['serialValue' . ($x+3)], 10) : '';
				$controls["connection_4"] = ($x+3) <= $toolCount && key_exists('connection' . ($x+3), $serviceOrder) ? $this->wrapText(79, $serviceOrder['connection' . ($x+3)], 10) : '';
				$controls["fn_4"] = ($x+3) <= $toolCount && key_exists('fn' . ($x+3), $serviceOrder) ? $this->wrapText(111, $serviceOrder['fn' . ($x+3)], 10) : '';
				
				$report = $c->reportService()->runReport('/reports/isi/serviceOrderCasedHoleJobs', 'pdf', null, null, $controls);
				$fp = fopen($serviceOrder['_id'] . 'page' . $page . '.pdf', 'w');
				fwrite($fp, $report);
				fclose($fp);
				$pdf->addPDF($serviceOrder['_id'] . 'page' . $page . '.pdf', '1');
			}
		}
		ob_end_clean();
		try {
			$pdf->merge('download', 'ServiceOrder.pdf');
		} catch(Exception $e) {
			error_log('ERROR');
			error_log($e);
		}
	/*
		$report = $c->reportService()->runReport('/reports/isi/serviceOrderCasedHoleJobs', 'pdf', null, null, $controls);
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=ServiceOrder.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
		*/
	}
	// TESTING DONE
	function detailInspectionOpenPdf_get() {
		error_log('========================================================= detailInspectionOpenPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$objModel = $this->db->selectCollection('OutgoingToolsOpenInspection');
		$obj = $objModel->findOne(array('_id' => $this->get('_id')));
		
		error_log('param _id -> ' . $this->get('_id'));
		
		$job = null;
		$serviceOrder = null;
		if ($obj == null) {
			$obj = $objModel->findOne(array('job' => $this->get('_id')));
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		} else {
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $obj['job']));
		}
		
		if ($job != null) {
			$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
			$serviceOrder = $serviceOrderModel->findOne(array('job' => $job['_id']));
			$descriptionOfJars = '';
			$descriptionOfJarsArr = array();
			if (key_exists('serial1', $obj) && $obj['serial1']) {
				$descriptionOfJarsArr[] = explode(' - ', $obj['serial1'],2)[0];
			}
			if (key_exists('serial2', $obj) && $obj['serial2']) {
				$descriptionOfJarsArr[] = explode(' - ', $obj['serial2'],2)[0];
			}
			if (key_exists('serial3', $obj) && $obj['serial3']) {
				$descriptionOfJarsArr[] = explode(' - ', $obj['serial3'],2)[0];
			}
			if (key_exists('serial4', $obj) && $obj['serial4']) {
				$descriptionOfJarsArr[] = explode(' - ', $obj['serial4'],2)[0];
			}
			if (key_exists('serial5', $obj) && $obj['serial5']) {
				$descriptionOfJarsArr[] = explode(' - ', $obj['serial5'],2)[0];
			}
			if (key_exists('serial6', $obj) && $obj['serial6']) {
				$descriptionOfJarsArr[] = explode(' - ', $obj['serial6'],2)[0];
			}
			$descriptionOfJars = implode(", ", array_values(array_unique($descriptionOfJarsArr)));
		} else {
			$descriptionOfJars = '';
			if (key_exists('jarType1', $obj) && $obj['jarType1'] != null) {
				$descriptionOfJars = $descriptionOfJars . $obj['jarType1'] . ',';
			}
			if (key_exists('jarType2', $obj) && $obj['jarType2'] != null) {
				$descriptionOfJars = $descriptionOfJars . $obj['jarType2'] . ',';
			}
			if (key_exists('jarType3', $obj) && $obj['jarType3'] != null) {
				$descriptionOfJars = $descriptionOfJars . $obj['jarType3'] . ',';
			}
			if (key_exists('jarType4', $obj) && $obj['jarType4'] != null) {
				$descriptionOfJars = $descriptionOfJars . $obj['jarType4'] . ',';
			}
			if (key_exists('jarType5', $obj) && $obj['jarType5'] != null) {
				$descriptionOfJars = $descriptionOfJars . $obj['jarType5'] . ',';
			}
			if (key_exists('jarType6', $obj) && $obj['jarType6'] != null) {
				$descriptionOfJars = $descriptionOfJars . $obj['jarType6'] . ',';
			}
			if (strlen($descriptionOfJars) > 0) {
				$descriptionOfJars = substr($descriptionOfJars, 0, strlen($descriptionOfJars) - 1);
			}
		}
		error_log('$descriptionOfJars -> ' . $descriptionOfJars);
		$additionalCommentArr = key_exists('additionalComment', $obj) ? $this->wrapTextMultiline(array(291, 451, 451), $obj['additionalComment'], 14) : array('', '', '');
			
		$controls = array(
				'dateJarsSentOut'  => key_exists('sentDate', $obj) ? $this->wrapText(92, gmdate("m/d/y", $obj['sentDate']), 14) : '',
				'rta' => $job != null ? $this->wrapText(154, $job['uid'], 14) : '',
				
				'serialNumbers1' => key_exists('serial1', $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial1'], 2)[1] : $obj['serial1'], 14) : '',
				'serialNumbers2' => key_exists('serial2', $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial2'], 2)[1] : $obj['serial2'], 14) : '',
				'serialNumbers3' => key_exists('serial3', $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial3'], 2)[1] : $obj['serial3'], 14) : '',
				'serialNumbers4' => key_exists('serial4', $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial4'], 2)[1] : $obj['serial4'], 14) : '',
				'serialNumbers5' => key_exists('serial5', $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial5'], 2)[1] : $obj['serial5'], 14) : '',
				'serialNumbers6' => key_exists('serial6', $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial6'], 2)[1] : $obj['serial6'], 14) : '',

				'descriptionOfJars' => $this->wrapText(332, $descriptionOfJars, 14),
				
				'yes_1' => key_exists('check1', $obj) && $obj['check1'] == '0' ? 'X' : '',
				'no_1' => key_exists('check1', $obj) && $obj['check1'] == '1' ? 'X' : '',
				'yes_2' => key_exists('check2', $obj) && $obj['check2'] == '0' ? 'X' : '',
				'no_2' => key_exists('check2', $obj) && $obj['check2'] == '1' ? 'X' : '',
				'yes_3' => key_exists('check3', $obj) && $obj['check3'] == '0' ? 'X' : '',
				'no_3' => key_exists('check3', $obj) && $obj['check3'] == '1' ? 'X' : '',
				'yes_4' => key_exists('check4', $obj) && $obj['check4'] == '0' ? 'X' : '',
				'no_4' => key_exists('check4', $obj) && $obj['check4'] == '1' ? 'X' : '',
				'yes_5' => key_exists('check5', $obj) && $obj['check5'] == '0' ? 'X' : '',
				'no_5' => key_exists('check5', $obj) && $obj['check5'] == '1' ? 'X' : '',
				'yes_6' => key_exists('check6', $obj) && $obj['check6'] == '0' ? 'X' : '',
				'no_6' => key_exists('check6', $obj) && $obj['check6'] == '1' ? 'X' : '',
				'yes_7' => key_exists('check7', $obj) && $obj['check7'] == '0' ? 'X' : '',
				'no_7' => key_exists('check7', $obj) && $obj['check7'] == '1' ? 'X' : '',
				'yes_8' => key_exists('check8', $obj) && $obj['check8'] == '0' ? 'X' : '',
				'no_8' => key_exists('check8', $obj) && $obj['check8'] == '1' ? 'X' : '',
				'yes_9' => key_exists('check9', $obj) && $obj['check9'] == '0' ? 'X' : '',
				'no_9' => key_exists('check9', $obj) && $obj['check9'] == '1' ? 'X' : '',
				
				'anyAdditionalComments_1' => $additionalCommentArr[0],
				'anyAdditionalComments_2' => $additionalCommentArr[1],
				'anyAdditionalComments_3' => $additionalCommentArr[2],
				'printedName_1' => key_exists('signatureName', $obj) ? $this->wrapText(212, $obj['signatureName'], 14) : '',
				'printedName_2' => key_exists('facilityName', $obj) ? $this->wrapText(212, $obj['facilityName'], 14) : '',
				'printedName_3' => key_exists('thirdPartyName', $obj) ? $this->wrapText(212, $obj['thirdPartyName'], 14) : '',
				'date_1' => (key_exists('signatureDate', $obj) && $obj['signatureDate'] !=null)  ? $this->wrapText(87, gmdate("m/d/y", $obj['signatureDate']), 14) : '',
				'date_2' => (key_exists('facilityDate', $obj) && $obj['facilityDate'] !=null) ? $this->wrapText(87, gmdate("m/d/y", $obj['facilityDate']), 14) : '',
				'date_3' => key_exists('thirdPartyDate', $obj) ? $this->wrapText(87, gmdate("m/d/y", $obj['thirdPartyDate']), 14) : ''

		);
		
		$pdf = new PDFMerger;
		//error_log('-----------------control : \n' . var_export($controls, true));
		$report = $c->reportService()->runReport('/reports/isi/outgoingOpenHoleJarInspection', 'pdf', null, null, $controls);
		$fp = fopen($obj['_id'] . 'page1' . '.pdf', 'w');
		fwrite($fp, $report);
		fclose($fp);
		$pdf->addPDF($obj['_id'] . 'page1' . '.pdf', '1');
		$toolCount = $obj['toolCount'];
		if ($toolCount > 6) {
			for ($page = 2, $x = 7; $x <= $toolCount; $x = $x + 6, $page++) {
				$descriptionOfJars = '';
				$descriptionOfJarsArr = array();
				if (key_exists('serial' . ($x), $obj) && $obj['serial' . ($x)]) {
					$descriptionOfJarsArr[] = explode(' - ', $obj['serial' . ($x)],2)[0];
				}
				if (key_exists('serial' . ($x+1), $obj) && $obj['serial' . ($x+1)]) {
					$descriptionOfJarsArr[] = explode(' - ', $obj['serial' . ($x+1)],2)[0];
				}
				if (key_exists('serial' . ($x+2), $obj) && $obj['serial' . ($x+2)]) {
					$descriptionOfJarsArr[] = explode(' - ', $obj['serial' . ($x+2)],2)[0];
				}
				if (key_exists('serial' . ($x+3), $obj) && $obj['serial' . ($x+3)]) {
					$descriptionOfJarsArr[] = explode(' - ', $obj['serial' . ($x+3)],2)[0];
				}
				if (key_exists('serial' . ($x+4), $obj) && $obj['serial' . ($x+4)]) {
					$descriptionOfJarsArr[] = explode(' - ', $obj['serial' . ($x+4)],2)[0];
				}
				if (key_exists('serial' . ($x+5), $obj) && $obj['serial' . ($x+5)]) {
					$descriptionOfJarsArr[] = explode(' - ', $obj['serial' . ($x+5)],2)[0];
				}
				$descriptionOfJars = implode(", ", array_values(array_unique($descriptionOfJarsArr)));
				$controls['descriptionOfJars'] = $this->wrapText(332, $descriptionOfJars, 14);
				$controls['serialNumbers1'] = key_exists('serial' . ($x), $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial' . ($x)], 2)[1] : $obj['serial' . ($x)], 14) : '';
				$controls['serialNumbers2'] = key_exists('serial' . ($x+1), $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial' . ($x+1)], 2)[1] : $obj['serial' . ($x+1)], 14) : '';
				$controls['serialNumbers3'] = key_exists('serial' . ($x+2), $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial' . ($x+2)], 2)[1] : $obj['serial' . ($x+2)], 14) : '';
				$controls['serialNumbers4'] = key_exists('serial' . ($x+3), $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial' . ($x+3)], 2)[1] : $obj['serial' . ($x+3)], 14) : '';
				$controls['serialNumbers5'] = key_exists('serial' . ($x+4), $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial' . ($x+4)], 2)[1] : $obj['serial' . ($x+4)], 14) : '';
				$controls['serialNumbers6'] = key_exists('serial' . ($x+5), $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial' . ($x+5)], 2)[1] : $obj['serial' . ($x+5)], 14) : '';
				$report = $c->reportService()->runReport('/reports/isi/outgoingOpenHoleJarInspection', 'pdf', null, null, $controls);
				$fp = fopen($serviceOrder['_id'] . 'page' . $page . '.pdf', 'w');
				fwrite($fp, $report);
				fclose($fp);
				$pdf->addPDF($serviceOrder['_id'] . 'page' . $page . '.pdf', '1');
			}
		}
		ob_end_clean();
		try {
			$pdf->merge('download', 'OutgoingOpenHoleJarInspection.pdf');
		} catch(Exception $e) {
			error_log('ERROR');
			error_log($e);
		}
	/*
		$report = $c->reportService()->runReport('/reports/isi/outgoingOpenHoleJarInspection', 'pdf', null, null, $controls);
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=OutgoingOpenHoleJarInspection.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
		*/
	}
	//TESTING DONE
	function detailInspectionCasedPdf_get() {
		error_log('========================================================= detailInspectionCasedPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$objModel = $this->db->selectCollection('OutgoingToolsCasedInspection');
		$obj = $objModel->findOne(array('job' => $this->get('_id'))); // if param is job_id

		error_log('param -->> ' . $this->get('_id'));
		
		$job = null;
		if ($obj == null) {
			error_log("OutgoingToolsCasedInspection is NULL");
			$obj = $objModel->findOne(array('_id' => $this->get('_id'))); // if param is obj id
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $obj['job']));
			
		} else {
			error_log("OutgoingToolsCasedInspection is EXIST");
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $obj['job']));			
		}
		
		if ($job != null) {
			
			$jarTypes = array();
			if (key_exists('serial1', $obj)) { $jarTypes[explode('-', $obj['serial1'],2)[0]] = ''; }
			if (key_exists('serial2', $obj)) { $jarTypes[explode('-', $obj['serial2'],2)[0]] = ''; }
			if (key_exists('serial3', $obj)) { $jarTypes[explode('-', $obj['serial3'],2)[0]] = ''; }
			if (key_exists('serial4', $obj)) { $jarTypes[explode('-', $obj['serial4'],2)[0]] = ''; }
			if (key_exists('serial5', $obj)) { $jarTypes[explode('-', $obj['serial5'],2)[0]] = ''; }
			if (key_exists('serial6', $obj)) { $jarTypes[explode('-', $obj['serial6'],2)[0]] = ''; }
			
			$descriptionOfJars = '';
			foreach ($jarTypes as $key => $item) {
				//error_log($key . ' ====>> ' . $item);
				$descriptionOfJars = $descriptionOfJars . $key . ' ,';
			}
			if (strlen($descriptionOfJars) > 0) {
				$descriptionOfJars = substr($descriptionOfJars, 0, strlen($descriptionOfJars) - 1);
			}
		} else {
			$jarTypes = array();
			if (key_exists('jarType1', $obj)) { $jarTypes[$obj['jarType1']] = ''; }
			if (key_exists('jarType2', $obj)) { $jarTypes[$obj['jarType2']] = ''; }
			if (key_exists('jarType3', $obj)) { $jarTypes[$obj['jarType3']] = ''; }
			if (key_exists('jarType4', $obj)) { $jarTypes[$obj['jarType4']] = ''; }
			if (key_exists('jarType5', $obj)) { $jarTypes[$obj['jarType5']] = ''; }
			if (key_exists('jarType6', $obj)) { $jarTypes[$obj['jarType6']] = ''; }
				
			$descriptionOfJars = '';
			foreach ($jarTypes as $key => $item) {
				//error_log($key . ' ====>> ' . $item);
				$descriptionOfJars = $descriptionOfJars . $key . ' ,';
			}
			if (strlen($descriptionOfJars) > 0) {
				$descriptionOfJars = substr($descriptionOfJars, 0, strlen($descriptionOfJars) - 1);
			}
		}
		
		$additionalCommentArr = key_exists('additionalComment', $obj) ? $this->wrapTextMultiline(array(291, 451, 451), $obj['additionalComment'], 14) : array('', '', '');
			
		$controls = array(
				'dateJarsSentOut'  => key_exists('sentDate', $obj) ? $this->wrapText(100, gmdate("m/d/y", $obj['sentDate']), 14) : '',
				'rta' => $job != null ? $this->wrapText(84, $job['uid'], 14) : '',
				
				'descriptionOfJars' => $this->wrapText(316, $descriptionOfJars, 14),
	
				'yes_1' => key_exists('check1', $obj) && $obj['check1'] == '0' ? 'X' : '',
				'no_1' => key_exists('check1', $obj) && $obj['check1'] == '1' ? 'X' : '',
				'yes_2' => key_exists('check2', $obj) && $obj['check2'] == '0' ? 'X' : '',
				'no_2' => key_exists('check2', $obj) && $obj['check2'] == '1' ? 'X' : '',
				'yes_3' => key_exists('check3', $obj) && $obj['check3'] == '0' ? 'X' : '',
				'no_3' => key_exists('check3', $obj) && $obj['check3'] == '1' ? 'X' : '',
				'yes_4' => key_exists('check4', $obj) && $obj['check4'] == '0' ? 'X' : '',
				'no_4' => key_exists('check4', $obj) && $obj['check4'] == '1' ? 'X' : '',
				'yes_5' => key_exists('check5', $obj) && $obj['check5'] == '0' ? 'X' : '',
				'no_5' => key_exists('check5', $obj) && $obj['check5'] == '1' ? 'X' : '',
				'yes_6' => key_exists('check6', $obj) && $obj['check6'] == '0' ? 'X' : '',
				'no_6' => key_exists('check6', $obj) && $obj['check6'] == '1' ? 'X' : '',
				'yes_7' => key_exists('check7', $obj) && $obj['check7'] == '0' ? 'X' : '',
				'no_7' => key_exists('check7', $obj) && $obj['check7'] == '1' ? 'X' : '',
				'yes_8' => key_exists('check8', $obj) && $obj['check8'] == '0' ? 'X' : '',
				'no_8' => key_exists('check8', $obj) && $obj['check8'] == '1' ? 'X' : '',
	
				'serialNumbers1' => key_exists('serial1', $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial1'], 2)[1] : $obj['serial1'], 14) : '',
				'serialNumbers2' => key_exists('serial2', $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial2'], 2)[1] : $obj['serial2'], 14) : '',
				'serialNumbers3' => key_exists('serial3', $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial3'], 2)[1] : $obj['serial3'], 14) : '',
				'serialNumbers4' => key_exists('serial4', $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial4'], 2)[1] : $obj['serial4'], 14) : '',
				'serialNumbers5' => key_exists('serial5', $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial5'], 2)[1] : $obj['serial5'], 14) : '',
				'serialNumbers6' => key_exists('serial6', $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial6'], 2)[1] : $obj['serial6'], 14) : '',
				
				'anyAdditionalComments_1' => $additionalCommentArr[0],
				'anyAdditionalComments_2' => $additionalCommentArr[1],
				'anyAdditionalComments_3' => $additionalCommentArr[2],
				'printedName_1' => key_exists('signatureName', $obj) ? $this->wrapText(221, $obj['signatureName'], 14) : '',
				'printedName_2' => key_exists('facilityName', $obj) ? $this->wrapText(221, $obj['facilityName'], 14) : '',
				'printedName_3' => key_exists('thirdPartyName', $obj) ? $this->wrapText(221, $obj['thirdPartyName'], 14) : '',
				'date_1' => (key_exists('signatureDate', $obj) && $obj['signatureDate'] !=null)  ? $this->wrapText(75, gmdate("m/d/y", $obj['signatureDate']), 14) : '',
				'date_2' => (key_exists('facilityDate', $obj) && $obj['facilityDate'] !=null) ? $this->wrapText(75, gmdate("m/d/y", $obj['facilityDate']), 14) : '',
				'date_3' => key_exists('thirdPartyDate', $obj) ? $this->wrapText(75, gmdate("m/d/y", $obj['thirdPartyDate']), 14) : ''
	
		);
		
		$pdf = new PDFMerger;
		//error_log('-----------------control : \n' . var_export($controls, true));
		$report = $c->reportService()->runReport('/reports/isi/casedHoleOutgoingJarInspection', 'pdf', null, null, $controls);
		$fp = fopen($obj['_id'] . 'page1' . '.pdf', 'w');
		fwrite($fp, $report);
		fclose($fp);
		$pdf->addPDF($obj['_id'] . 'page1' . '.pdf', '1');
		$toolCount = $obj['toolCount'];
		if ($toolCount > 6) {
			for ($page = 2, $x = 7; $x <= $toolCount; $x = $x + 6, $page++) {
				$descriptionOfJars = '';
				$descriptionOfJarsArr = array();
				if (key_exists('serial' . ($x), $obj) && $obj['serial' . ($x)]) {
					$descriptionOfJarsArr[] = explode(' - ', $obj['serial' . ($x)],2)[0];
				}
				if (key_exists('serial' . ($x+1), $obj) && $obj['serial' . ($x+1)]) {
					$descriptionOfJarsArr[] = explode(' - ', $obj['serial' . ($x+1)],2)[0];
				}
				if (key_exists('serial' . ($x+2), $obj) && $obj['serial' . ($x+2)]) {
					$descriptionOfJarsArr[] = explode(' - ', $obj['serial' . ($x+2)],2)[0];
				}
				if (key_exists('serial' . ($x+3), $obj) && $obj['serial' . ($x+3)]) {
					$descriptionOfJarsArr[] = explode(' - ', $obj['serial' . ($x+3)],2)[0];
				}
				if (key_exists('serial' . ($x+4), $obj) && $obj['serial' . ($x+4)]) {
					$descriptionOfJarsArr[] = explode(' - ', $obj['serial' . ($x+4)],2)[0];
				}
				if (key_exists('serial' . ($x+5), $obj) && $obj['serial' . ($x+5)]) {
					$descriptionOfJarsArr[] = explode(' - ', $obj['serial' . ($x+5)],2)[0];
				}
				$descriptionOfJars = implode(", ", array_values(array_unique($descriptionOfJarsArr)));
				$controls['descriptionOfJars'] = $this->wrapText(332, $descriptionOfJars, 14);
				$controls['serialNumbers1'] = key_exists('serial' . ($x), $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial' . ($x)], 2)[1] : $obj['serial' . ($x)], 14) : '';
				$controls['serialNumbers2'] = key_exists('serial' . ($x+1), $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial' . ($x+1)], 2)[1] : $obj['serial' . ($x+1)], 14) : '';
				$controls['serialNumbers3'] = key_exists('serial' . ($x+2), $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial' . ($x+2)], 2)[1] : $obj['serial' . ($x+2)], 14) : '';
				$controls['serialNumbers4'] = key_exists('serial' . ($x+3), $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial' . ($x+3)], 2)[1] : $obj['serial' . ($x+3)], 14) : '';
				$controls['serialNumbers5'] = key_exists('serial' . ($x+4), $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial' . ($x+4)], 2)[1] : $obj['serial' . ($x+4)], 14) : '';
				$controls['serialNumbers6'] = key_exists('serial' . ($x+5), $obj) ? $this->wrapText(90, $job != null? explode(' - ', $obj['serial' . ($x+5)], 2)[1] : $obj['serial' . ($x+5)], 14) : '';
				$report = $c->reportService()->runReport('/reports/isi/casedHoleOutgoingJarInspection', 'pdf', null, null, $controls);
				$fp = fopen($obj['_id'] . 'page' . $page . '.pdf', 'w');
				fwrite($fp, $report);
				fclose($fp);
				$pdf->addPDF($obj['_id'] . 'page' . $page . '.pdf', '1');
			}
		}
		ob_end_clean();
		try {
			$pdf->merge('download', 'CasedHoleOutgoingJarInspection.pdf');
		} catch(Exception $e) {
			error_log('ERROR');
			error_log($e);
		}
		
	/*
		$report = $c->reportService()->runReport('/reports/isi/casedHoleOutgoingJarInspection', 'pdf', null, null, $controls);
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=CasedHoleOutgoingJarInspection.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
		*/
	}
	// TESTED
	function detailInOutPdf_get() {
		error_log('========================================================= detailInOutPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		
		$job =null;
		$controls = array();
		if ($this->get('_id') != null) {
			$objModel = $this->db->selectCollection('OutgoingToolsInOut');
			$objs = $objModel->find(array('job' => $this->get('_id')));
			
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $this->get('_id')));
			
			$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
			$serviceOrder = $serviceOrderModel->findOne(array('job' => $this->get('_id')));
			
			$idx = 0;
			foreach ($objs as $item) {
				foreach($item as $key => $value) {
					if ($item['serialType'] == '0') {
						$controls['jarNo_' . ($idx + 1)] = key_exists('serial', $item) ? $this->wrapText(110, key_exists('jarType', $item)?$item['jarType'] . ' - ' .$item['serial']:$item['serial'], 10) : '';
					} else if ($item['serialType'] == '1') {
						$controls['jarNo_' . ($idx + 1)] = key_exists('basket', $item) ? $this->wrapText(110, 'basket : ' . $item['basket'], 10) : '';
					} else if ($item['serialType'] == '2') {
						$controls['jarNo_' . ($idx + 1)] = key_exists('sling', $item) ? $this->wrapText(110, 'sling : ' . $item['sling'], 10) : '';
					} 
					
					$controls['destination_' . ($idx + 1)] = key_exists('destination', $item) ? $this->wrapText(220, $item['destination'], 10) : '';
					$controls['date_' . ($idx + 1)] = key_exists('date', $item) ? $this->wrapText(64, gmdate("m/d/y", $item['date']), 10) : '';
					$controls['inOut_' . ($idx + 1)] = key_exists('inOut', $item) ? $this->wrapText(50, ($item['inOut'] == '0'?'In':'Out'), 10) : '';
					$controls['ran_' . ($idx + 1)] = $this->wrapText(81, (key_exists('ran', $item) ? ($item['ran'] == '0'?'Y':'N') : '') . ',' . (key_exists('level', $item) ? $item['level'] : ''), 10);
					$fa = key_exists('fa', $item) ? ' / ' . $item['fa'] : '';
					$controls['rta_' . ($idx + 1)] = $job != null ? $this->wrapText(99, ($job['uid'] . $fa), 10) : '';
					$controls['et_' . ($idx + 1)] = key_exists('et', $item) ? $this->wrapText(62, $item['et'], 10) : '';
					$controls['signature_' . ($idx + 1)] = key_exists('name', $item) ? $this->wrapText(64, $item['name'], 10) : '';
				}
				$idx = $idx + 1;
			}
		} else {
			$objModel = $this->db->selectCollection('OutgoingToolsInOut');
			
			if ($this->get('from') && $this->get('to')) {
				$itemCursor = $objModel->find(array("date" => array('$gt' => (int) $this->get('from'), '$lte' => (int) $this->get('to'))));
			} else {
				$itemCursor = $objModel->find();
			}
			$idx = 0;
			foreach ($itemCursor as $item) {
				
				$serviceOrder = null;
				if (key_exists('job', $item)) {
					$serviceOrderModel = $this->db->selectCollection('ServiceOrder');
					$serviceOrder = $serviceOrderModel->findOne(array('job' => $item['job']));
				}

				if ($item['serialType'] == '0') {
					$controls['jarNo_' . ($idx + 1)] = key_exists('serial', $item) ? $this->wrapText(110, key_exists('jarType', $item)?$item['jarType'] . ' - ' .$item['serial']:$item['serial'], 10) : '';
				} else if ($item['serialType'] == '1') {
					$controls['jarNo_' . ($idx + 1)] = key_exists('basket', $item) ? $this->wrapText(110, 'basket:' . $item['basket'], 10) : '';
				} else if ($item['serialType'] == '2') {
					$controls['jarNo_' . ($idx + 1)] = key_exists('sling', $item) ? $this->wrapText(110, ('sling:' . $item['sling']), 10) : '';
				}
				$controls['destination_' . ($idx + 1)] = key_exists('destination', $item) ? $this->wrapText(220, $item['destination'], 10) : '';
				$controls['date_' . ($idx + 1)] = key_exists('date', $item) ? $this->wrapText(64, gmdate("m/d/y", $item['date']), 10) : '';
				$controls['inOut_' . ($idx + 1)] = key_exists('inOut', $item) ? $this->wrapText(50, ($item['inOut'] == '0'?'In':'Out'), 10) : '';
				$controls['ran_' . ($idx + 1)] = $this->wrapText(81, (key_exists('ran', $item) ? ( ['ran'] == '0'?'Y':'N') : '') . ',' . (key_exists('level', $item) ? $item['level'] : ''), 10);
				$controls['et_' . ($idx + 1)] = key_exists('et', $item) ? $this->wrapText(62, $item['et'], 10) : '';
				$controls['signature_' . ($idx + 1)] = key_exists('name', $item) ? $this->wrapText(64, $item['name'], 10) : '';
				
				if (key_exists('job', $item)) {
					$jobModel = $this->db->selectCollection('Job');
					$job = $jobModel->findOne(array('_id' => $item['job']));
					$fa = key_exists('fa', $item) ? ' / ' . $item['fa'] : '';
					$controls['rta_' . ($idx + 1)] = $job != null ? $this->wrapText(99, ($job['uid'] . $fa), 10) : '';
				} else {
					$controls['rta_' . ($idx + 1)] = key_exists('et', $item) ? $this->wrapText(99, $item['et'], 10) : '';
				}
				$idx++;
			}
		}
		//error_log('----debug controls : \n' . var_export($controls, true));
		$report = $c->reportService()->runReport('/reports/isi/jarSigningSheet', 'pdf', null, null, $controls);
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=JarSigningSheet.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
	}
	// TESTING DONE
	function detailLeakBakerOpenPdf_get() {
		error_log('========================================================= detailLeakBakerOpenPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$objModel = $this->db->selectCollection('OutgoingToolsOpenLeakBaker');
		$obj = $objModel->findOne(array('_id' => $this->get('_id')));
		$job = null;
		if ($obj == null) {
			$obj = $objModel->findOne(array('job' => $this->get('_id')));
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		} else if (key_exists('job', $obj)) {
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $obj['job']));
		}
			
		$controls = array(
				'jarModel' => key_exists('jarModel', $obj) ? $obj['jarModel'] : '',
				'jarType' => key_exists('jarType', $obj) ? $obj['jarType'] : '',
				'jarSerial' => key_exists('jarSerial', $obj) ? $this->wrapText(191, $obj['jarSerial'], 12) : '',
				'jarSerialDate' => key_exists('date', $obj) ? $this->wrapText(90, gmdate("m/d/y", $obj['date']), 12) : '',
				'rta' => $job != null ? $this->wrapText(89, $job['uid'], 12) : '',
				'megMeterSerial' => key_exists('megSerial', $obj) ? $this->wrapText(153, $obj['megSerial'], 12) : '',
				'megMeterCalibrationDate' => key_exists('megDate', $obj) ? $this->wrapText(101, gmdate("m/d/y", $obj['megDate']), 12) : '',
				'checkOne_1' => key_exists('inOut', $obj) && $obj['inOut'] == '0' ? ' x' : '',
				'incomingFrom' => key_exists('inOut0Value', $obj) ? $this->wrapText(120, $obj['inOut0Value'], 12) : '',
				'checkOne_2' => key_exists('inOut', $obj) && $obj['inOut'] == '1' ? ' x' : '',
				'outgoingTo' => key_exists('inOut1Value', $obj) ? $this->wrapText(140, $obj['inOut1Value'], 12) : '',
				
				'printedName_1' => key_exists('techName', $obj) ? $this->wrapText(252, $obj['techName'], 12) : '',
				'printedName_2' => key_exists('facilityName', $obj) ? $this->wrapText(252, $obj['facilityName'], 12) : '',
				'printedName_3' => key_exists('inspectorName', $obj) ? $this->wrapText(252, $obj['inspectorName'], 12) : '',
				'signature_1' => '',
				'signature_2' => '',
				'signature_3' => '',
				'date_1' => key_exists('techDate', $obj) ? $this->wrapText(104, gmdate("m/d/y", $obj['techDate']), 12) : '',
				'date_2' => key_exists('facilityDate', $obj) ? $this->wrapText(104, gmdate("m/d/y", $obj['facilityDate']), 12) : '',
				'date_3' => key_exists('inspectorDate', $obj) ? $this->wrapText(104, gmdate("m/d/y", $obj['inspectorDate']), 12) : '',
		);
	
		for ($idx = 0; $idx < 26; $idx++) {
			$controls['continuity_' . ($idx + 1)] = 'N/A';
			$controls['leaks_' . ($idx + 1)] = 'N/A';
		}
		
		for ($idx = 0; $idx < 26; $idx++) {
			$controls['continuity_' . ($idx + 1)] = key_exists('continuity' . ($idx + 1), $obj) && !empty($obj['continuity' . ($idx + 1)])? $this->wrapText(211, $obj['continuity' . ($idx + 1)], 12) : 'N/A';
			$controls['leaks_' . ($idx + 1)] = key_exists('leaks' . ($idx + 1), $obj) && !empty($obj['leaks' . ($idx + 1)]) ? $this->wrapText(211, $obj['leaks' . ($idx + 1)], 12) : 'N/A';
		}
		
		$report = $c->reportService()->runReport('/reports/isi/openHoleJarLeakContinuityCheckBaker', 'pdf', null, null, $controls);
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=OpenHoleJarLeakContinuityCheckBaker.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
	}
	// TESTED
	function detailLeakBakerCasedPdf_get() {
		error_log('========================================================= detailLeakBakerCasedPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$objModel = $this->db->selectCollection('OutgoingToolsCasedLeakBaker');
		$obj = $objModel->findOne(array('_id' => $this->get('_id')));
		$job = null;
		if ($obj == null) {
			$obj = $objModel->findOne(array('job' => $this->get('_id')));
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		} else if (key_exists('job', $obj)) {
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $obj['job']));
		}
			
		$controls = array(
	
				'productType' => key_exists('jarModel', $obj) ? $obj['jarModel'] : '',
				'productSerial' => key_exists('jarSerial', $obj) ? $this->wrapText(165, $obj['jarSerial'], 12) : '',
				'date' => key_exists('date', $obj) ? $this->wrapText(88, gmdate("m/d/y", $obj['date']), 12) : '',
				'rta' =>  $job != null ? $this->wrapText(86, $job['uid'], 12) : '',
				'megMeterSerial' => key_exists('megSerial', $obj) ? $this->wrapText(170, $obj['megSerial'], 12) : '',
				'megMeterCalibrationDate' => key_exists('megDate', $obj) ? $this->wrapText(79, gmdate("m/d/y", $obj['megDate']), 12) : '',
				'checkOne_1' => key_exists('inOut', $obj) && $obj['inOut'] == '0' ? ' x' : '',
				'incomingFrom' => key_exists('inOut0Value', $obj) ? $this->wrapText(136, $obj['inOut0Value'], 12) : '',
				'checkOne_2' => key_exists('inOut', $obj) && $obj['inOut'] == '1' ? ' x' : '',
				'outgoingTo' => key_exists('inOut1Value', $obj) ? $this->wrapText(123, $obj['inOut1Value'], 12) : '',
	
				'printedName_1' => key_exists('techName', $obj) ? $this->wrapText(232, $obj['techName'], 12) : '',
				'printedName_2' => key_exists('facilityName', $obj) ? $this->wrapText(232, $obj['facilityName'], 12) : '',
				'printedName_3' => key_exists('inspectorName', $obj) ? $this->wrapText(232, $obj['inspectorName'], 12) : '',
				'signature_1' => '',
				'signature_2' => '',
				'signature_3' => '',
				'date_1' => key_exists('techDate', $obj) ? $this->wrapText(104, gmdate("m/d/y", $obj['techDate']), 12) : '',
				'date_2' => key_exists('facilityDate', $obj) ? $this->wrapText(104, gmdate("m/d/y", $obj['facilityDate']), 12) : '',
				'date_3' => key_exists('inspectorDate', $obj) ? $this->wrapText(104, gmdate("m/d/y", $obj['inspectorDate']), 12) : ''
		);
	
		for ($idx = 0; $idx < 2; $idx++) {
			$controls['continuity_' . ($idx + 1)] = 'N/A';
			$controls['leaks_' . ($idx + 1)] = 'N/A';
		}
	
		for ($idx = 0; $idx < 2; $idx++) {
			$controls['continuity_' . ($idx + 1)] = key_exists('continuity' . ($idx + 1), $obj) && !empty($obj['continuity' . ($idx + 1)])? $this->wrapText(211, $obj['continuity' . ($idx + 1)], 12) : 'N/A';
			$controls['leaks_' . ($idx + 1)] = key_exists('leaks' . ($idx + 1), $obj) && !empty($obj['leaks' . ($idx + 1)])? $this->wrapText(211, $obj['leaks' . ($idx + 1)], 12) : 'N/A';
		}
	
		$report = $c->reportService()->runReport('/reports/isi/casedHoleLeakContinuityCheck', 'pdf', null, null, $controls);
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=casedHoleLeakContinuityCheck.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
	}
	// TESTING DONE
	function detailLeakBurtonOpenPdf_get() {
		error_log('========================================================= detailLeakBurtonOpenPdf_get ======================================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
			
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$objModel = $this->db->selectCollection('OutgoingToolsLeakBurton');
			
		$obj = $objModel->findOne(array('_id' => $this->get('_id')));
		$job = null;
	
		if ($obj == null) {
			$obj = $objModel->findOne(array('job' => $this->get('_id')));
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		} else if (key_exists('job', $obj)) {
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $obj['job']));
		}
	
		$controls = array(
	
				'jarModel' => key_exists('jarModel', $obj) ? $obj['jarModel'] : '',
				'jarType' => key_exists('jarType', $obj) ? $obj['jarType'] : '',
				'jarSerial' => key_exists('jarSerial', $obj) ? $this->wrapText(180, $obj['jarSerial'], 12) : '',
				'jarSerialDate' => key_exists('date', $obj) ? $this->wrapText(81, gmdate("m/d/y", $obj['date']), 12) : '',
				'rta' => $job != null ? $this->wrapText(96, $job['uid'], 12) : '',
				'megMeterSerial' => key_exists('megSerial', $obj) ? $this->wrapText(142, $obj['megSerial'], 12) : '',
				'megMeterCalibrationDate' => key_exists('megDate', $obj) ? $this->wrapText(80, gmdate("m/d/y", $obj['megDate']), 12) : '',
				'checkOne_1' => key_exists('inOut', $obj) ? ($obj['inOut'] == '0' ? 'X' : '') : '',
				'incomingFrom' => key_exists('inOut0Value', $obj) ? $this->wrapText(121, $obj['inOut0Value'], 12) : '',
				'checkOne_2' => key_exists('inOut', $obj) ? ($obj['inOut'] == '1' ? 'X' : '') : '',
				'outgoingTo' => key_exists('inOut1Value', $obj) ? $this->wrapText(122, $obj['inOut1Value'], 12) : '',
	
				'printedName_1' => key_exists('techName', $obj) ? $this->wrapText(232, $obj['techName'], 12) : '',
				'printedName_2' => key_exists('facilityName', $obj) ? $this->wrapText(232, $obj['facilityName'], 12) : '',
				'printedName_3' => key_exists('inspectorName', $obj) ? $this->wrapText(232, $obj['inspectorName'], 12) : '',
				'signature_1' => '',
				'signature_2' => '',
				'signature_3' => '',
				'date_1' => key_exists('techDate', $obj) ? $this->wrapText(104, gmdate("m/d/y", $obj['techDate']), 12) : '',
				'date_2' => key_exists('facilityDate', $obj) ? $this->wrapText(104, gmdate("m/d/y", $obj['facilityDate']), 12) : '',
				'date_3' => key_exists('inspectorDate', $obj) ? $this->wrapText(104, gmdate("m/d/y", $obj['inspectorDate']), 12) : ''
	
		);
	
		$allPin = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "12", "13", "14", "15", "16", "17", "18", "19", "31");
		$pin = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "12", "13", "14", "15", "16", "17", "18", "19", "31");
	
		for ($idx = 0; $idx < count($allPin); $idx++) {
			$controls['continuity_' . $allPin[$idx]] = 'N/A';
			$controls['leaks_' . $allPin[$idx]] = 'N/A';
		}
	
		for ($idx = 0; $idx < count($pin); $idx++) {
			$controls['continuity_' . $pin[$idx]] = key_exists('continuity' . $pin[$idx], $obj) && !empty($obj['continuity' . $pin[$idx]]) ? $this->wrapText(211, $obj['continuity' . $pin[$idx]], 12) : 'N/A';
			$controls['leaks_' . $pin[$idx]] = key_exists('leaks' . $pin[$idx], $obj) && !empty($obj['leaks' . $pin[$idx]]) ? $this->wrapText(211, $obj['leaks' . $pin[$idx]], 12) : 'N/A';
		}
	
		$report = $c->reportService()->runReport('/reports/isi/openHoleJarLeakContinuityCheckBakerHAL', 'pdf', null, null, $controls);
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=OpenHoleJarLeakContinuityCheckBakerHAL.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
	}
	
	function detailLeakBergerOpenPdf_get() {
		error_log('========================================================= detailLeakBergerOpenPdf_get ====================' . $this->get('_id') . '==================================');
		$c = new Jaspersoft\Client\Client(
				"http://iclogik.com:8080/jasperserver",
				"jasperadmin",
				"jasperadmin"
		);
		$con = new MongoClient ();
		$this->db = $con->selectDB(_DB_NAME);
		$objModel = $this->db->selectCollection('OutgoingToolsLeakBerger');
			
		$obj = $objModel->findOne(array('_id' => $this->get('_id')));
		$job = null;	
		if ($obj == null) {
			$obj = $objModel->findOne(array('job' => $this->get('_id')));
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $this->get('_id')));
		} else if (key_exists('job', $obj)) {
			$jobModel = $this->db->selectCollection('Job');
			$job = $jobModel->findOne(array('_id' => $obj['job']));
		}

		$controls = array(
				
				'jarModel' => key_exists('jarModel', $obj) ? $obj['jarModel'] : '',
				'jarType' => key_exists('jarType', $obj) ? $obj['jarType'] : '',
				'jarSerial' => key_exists('jarSerial', $obj) ? $this->wrapText(188, $obj['jarSerial'], 12) : '',
				'jarSerialDate' => key_exists('date', $obj) ? $this->wrapText(88, gmdate("m/d/y", $obj['date']), 12) : '',
				'rta' => $job != null ? $this->wrapText(96, $job['uid'], 12) : '',
				'megMeterSerial' => key_exists('megSerial', $obj) ? $this->wrapText(155, $obj['megSerial'], 12) : '',
				'megMeterCalibrationDate' => key_exists('megDate', $obj) ? $this->wrapText(71, gmdate("m/d/y", $obj['megDate']), 12) : '',
				'checkOne_1' => key_exists('inOut', $obj) ? ($obj['inOut'] == '0' ? 'X' : '') : '',
				'incomingFrom' => key_exists('inOut0Value', $obj) ? $this->wrapText(114, $obj['inOut0Value'], 12) : '',
				'checkOne_2' => key_exists('inOut', $obj) ? ($obj['inOut'] == '1' ? 'X' : '') : '',
				'outgoingTo' => key_exists('inOut1Value', $obj) ? $this->wrapText(123, $obj['inOut1Value'], 12) : '',
	
				'printedName_1' => key_exists('techName', $obj) ? $this->wrapText(232, $obj['techName'], 12) : '',
				'printedName_2' => key_exists('facilityName', $obj) ? $this->wrapText(232, $obj['facilityName'], 12) : '',
				'printedName_3' => key_exists('inspectorName', $obj) ? $this->wrapText(232, $obj['inspectorName'], 12) : '',
				'signature_1' => '',
				'signature_2' => '',
				'signature_3' => '',
				'date_1' => key_exists('techDate', $obj) ? $this->wrapText(104, gmdate("m/d/y", $obj['techDate']), 12) : '',
				'date_2' => key_exists('facilityDate', $obj) ? $this->wrapText(104, gmdate("m/d/y", $obj['facilityDate']), 12) : '',
				'date_3' => key_exists('inspectorDate', $obj) ? $this->wrapText(104, gmdate("m/d/y", $obj['inspectorDate']), 12) : ''
				
		);
		
		$allPin = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "12", "13", "14", "15", "16", "19", "20", "30", "31");
		if ($obj['jarType'] == '0') {
			$pin = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "14", "15", "16", "19", "20");
		} else {
			$pin = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "12", "13", "14", "15", "16", "19", "20", "30", "31");
		}
		
		for ($idx = 0; $idx < count($allPin); $idx++) {
			$controls['continuity_' . $allPin[$idx]] = 'N/A';
			$controls['leaks_' . $allPin[$idx]] = 'N/A';
		}
	
		for ($idx = 0; $idx < count($pin); $idx++) {
			$controls['continuity_' . $pin[$idx]] = key_exists('continuity' . $pin[$idx], $obj) && !empty($obj['continuity' . $pin[$idx]]) ? $this->wrapText(211, $obj['continuity' . $pin[$idx]], 12) : 'N/A';
			$controls['leaks_' . $pin[$idx]] = key_exists('leaks' . $pin[$idx], $obj) && !empty($obj['leaks' . $pin[$idx]]) ? $this->wrapText(211, $obj['leaks' . $pin[$idx]], 12) : 'N/A';
		}
	
		$report = $c->reportService()->runReport('/reports/isi/openHoleJarLeakContinuityCheckBakerSLB', 'pdf', null, null, $controls);
	
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=OpenHoleJarLeakContinuityCheckBakerSLB.pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($report));
		header('Content-Type: application/pdf');
	
		echo $report;
	}

	function login_post() {
		$output = [];
		if (array_key_exists ( 'HTTP_X_TOKEN_KEY', $_SERVER )) {
			$this->tokenKey['token'] = $_SERVER['HTTP_X_TOKEN_KEY'];
			$token = JWT::decode($_SERVER['HTTP_X_TOKEN_KEY'], _SECRET_KEY);
			$con = new MongoClient ();
			$tokenModel = $con->selectDB(_DB_NAME)->token;
			$tokenItems = $tokenModel->find(['_id' => $token]);
			if ($tokenItems->count() > 0) {
				$tokenItems->next();
				$tokenItem = $tokenItems->current();
				$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => []];
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
		echo json_encode($output);
	}
	
	function info_get() {
		$output = [];
		if (array_key_exists ( 'HTTP_X_TOKEN_KEY', $_SERVER )) {
			$this->tokenKey['token'] = $_SERVER['HTTP_X_TOKEN_KEY'];
			$token = JWT::decode($_SERVER['HTTP_X_TOKEN_KEY'], _SECRET_KEY);
			$con = new MongoClient ();
			$tokenModel = $con->selectDB(_DB_NAME)->token;
			$tokenItems = $tokenModel->find(['_id' => $token]);
			if ($tokenItems->count() > 0) {
				$tokenItems->next();
				$tokenItem = $tokenItems->current();
				$output = ['status' => 1, 'message' => 'successfuly executed', 'data' => $tokenItem['user']];
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
		echo json_encode($output);
	}
	
	
}
?>