<?php

require_once("rest.php");

class Api extends Rest {

	private $connect;
	protected $dbHost = 'localhost';
	protected $dbName = 'cayetano';
	protected $dbUser = 'root';
	protected $dbPass = '';

	function __construct() {
		$this->dbConnect();
	}

	private function dbConnect() {

		$this->connect = new mysqli($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName);

		if (mysqli_connect_errno()) {
			printf("Connection failed: %s\
				", mysqli_connect_error());
			exit();
		}
		return true;
	}

	private function convertUrl($url) {

		$explodeUrl = explode('/', $url);
		switch (count($explodeUrl)) {
			case 5:
				if (ctype_digit($explodeUrl[count($explodeUrl) - 1])) {
					$param = $explodeUrl[count($explodeUrl) - 1];
					$action = $explodeUrl[count($explodeUrl) - 2];
					$path = $explodeUrl[count($explodeUrl) - 2] . '/id';
				} else {
					$param = '';
					$action = $explodeUrl[count($explodeUrl) - 2];
					$path = $explodeUrl[count($explodeUrl) - 2] . '/' . $explodeUrl[count($explodeUrl) - 1];
				}

				break;
			case 6:
				if (ctype_digit($explodeUrl[count($explodeUrl) - 1])) {
					$param = $explodeUrl[count($explodeUrl) - 1];
					$action = $explodeUrl[count($explodeUrl) - 2];
					$path = $explodeUrl[count($explodeUrl) - 3] . '/' . $explodeUrl[count($explodeUrl) - 2] . '/id';
				} else {
					$param = '';
					$action = $explodeUrl[count($explodeUrl) - 2];
					$path = $explodeUrl[count($explodeUrl) - 3] . '/' . $explodeUrl[count($explodeUrl) - 2];
				}

				break;
			default :
				$error = array('status' => 'false', 'message' => 'That path dosnt exist!');
				$this->response($this->json($error), 200);
		}

		$path = array(
			'param' => $param,
			'action' => $action,
			'path' => $path
		);

		return $path;
	}

	private function definePaths() {
		$paths = array(
			['path' => 'jobs/list', 'action' => 'listJobs'],
			['path' => 'jobs/id', 'action' => 'getJobs'],
			['path' => 'candidates/list', 'action' => 'listCandidates'],
			['path' => 'candidates/review/id', 'action' => 'reviewCandidates'],
			['path' => 'candidates/search/id', 'action' => 'searchCandidates']
		);

		return $paths;
	}

	public function dispatcher() {
		$url = $_SERVER['REQUEST_URI'];
		$definePaths = $this->definePaths();
		foreach ($definePaths as $row) {

			$currentUrl = $this->convertUrl($url);
			if ($row['path'] == $currentUrl['path']) {

				if ((int) method_exists($this, $row['action']) > 0) {
					$this->$row['action']($currentUrl['param']);
				} else {
					$error = array('status' => 'false', 'message' => "Dosn't exist method");
					$this->response($this->json($error), 200);
				}
			}
		}
	}

	private function getJobs($param) {

		$query = "SELECT `position`, `description`, `craeted_on` FROM `jobs` WHERE `id`=?";

		if ($stmt = $this->connect->prepare($query)) {

			$stmt->bind_param("i", $param);
			$stmt->execute();
			$stmt->bind_result($position, $description, $craeted_on);

			while ($stmt->fetch()) {

				$arrResult[] = array(
					'position' => $position,
					'description' => $description,
					'craeted_on' => $craeted_on
				);
			}
		}

		$stmt->close();

		if (isset($arrResult)) {
			$res = array('status' => 'true', 'message' => $arrResult);
			$this->response($this->json($res), 200);
		} else {
			$error = array('status' => 'false', 'message' => 'There are no records');
			$this->response($this->json($error), 200);
		}
	}

	private function listJobs() {

		$query = "SELECT `position`, `description`, `craeted_on` FROM `jobs`";

		if ($result = $this->connect->query($query)) {

			while ($obj = $result->fetch_object()) {
				$arrResult[] = array(
					'position' => $obj->position,
					'description' => $obj->description,
					'craeted_on' => $obj->craeted_on
				);
			}

			if (isset($arrResult)) {
				$res = array('status' => 'true', 'message' => $arrResult);
				$this->response($this->json($res), 200);
			} else {
				$error = array('status' => 'false', 'message' => 'There are no records');
				$this->response($this->json($error), 200);
			}
		}

		$result->close();
	}

	private function listCandidates() {

		$query = "SELECT `name`, `position`, `craeted_on` FROM `candidates`";

		if ($result = $this->connect->query($query)) {

			while ($obj = $result->fetch_object()) {
				$arrResult[] = array(
					'name' => $obj->name,
					'position' => $obj->position,
					'craeted_on' => $obj->craeted_on
				);
			}

			if (isset($arrResult)) {
				$res = array('status' => 'true', 'message' => $arrResult);
				$this->response($this->json($res), 200);
			} else {
				$error = array('status' => 'false', 'message' => 'There are no records');
				$this->response($this->json($error), 200);
			}
		}
		$result->close();
	}

	private function reviewCandidates($param) {

		$query = "SELECT c.`name`, c.`position` as candidatePosition, c.`craeted_on` as candidateCreateOn,"
			. "j.`description`, j.`position` as jobPosition, j.`craeted_on` as jobCreateOn "
			. "FROM `candidates` as c "
			. "INNER JOIN `jobs` as j "
			. "ON c.jobs_id = j.id "
			. "WHERE c.`id`= ? "
			. "LIMIT 0,1";

		if ($stmt = $this->connect->prepare($query)) {

			$stmt->bind_param("i", $param);
			$stmt->execute();
			$stmt->bind_result($name, $candidatePosition, $candidateCreateOn, $description, $jobPosition, $jobCreateOn);

			while ($stmt->fetch()) {

				$arrResult[] = array(
					'name' => $name,
					'candidatePosition' => $candidatePosition,
					'candidateCreateOn' => $candidateCreateOn,
					'description' => $description,
					'jobPosition' => $jobPosition,
					'jobCreateOn' => $jobCreateOn
				);
			}
		}

		$stmt->close();

		if (isset($arrResult)) {
			$res = array('status' => 'true', 'message' => $arrResult);
			$this->response($this->json($res), 200);
		} else {
			$error = array('status' => 'false', 'message' => 'There are no records');
			$this->response($this->json($error), 200);
		}
	}

	private function searchCandidates($param) {

		$query = "SELECT `name`, `position`, `craeted_on` FROM `candidates` WHERE `id`=?";

		if ($stmt = $this->connect->prepare($query)) {

			$stmt->bind_param("i", $param);
			$stmt->execute();
			$stmt->bind_result($name, $position, $craeted_on);

			while ($stmt->fetch()) {

				$arrResult[] = array(
					'name' => $name,
					'position' => $position,
					'craeted_on' => $craeted_on
				);
			}
		}

		$stmt->close();

		if (isset($arrResult)) {
			$res = array('status' => 'true', 'message' => $arrResult);
			$this->response($this->json($res), 200);
		} else {
			$error = array('status' => 'false', 'message' => 'There are no records');
			$this->response($this->json($error), 200);
		}
	}

	private function json($data) {
		if (is_array($data)) {
			return json_encode($data);
		}
	}

}

$api = new Api();
$api->dispatcher();
