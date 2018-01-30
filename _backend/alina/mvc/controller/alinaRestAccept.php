<?php

namespace alina\mvc\controller;


class alinaRestAccept {

    public function actionIndex() {
        $data = resolvePostDataAsObject();
        $this->standardRestApiResponse($data);
    }


    public function actionIndex1() {

		$m   = new \alina\mvc\model\user();
		$res = $m->getAll();

		$this->standardRestApiResponse($res);
	}

	public function actionForm() {
		$data = '';
		echo (new \alina\mvc\view\html)->page($data);
	}

	//ToDo: Never use on prod.
	protected function systemData() {

        if (ALINA_MODE === 'PROD') {return [];}

		$sysData            = [];
		$sysData['GET']     = $_GET;
		$sysData['POST']    = resolvePostDataAsObject();
		$sysData['FILE']    = $_FILES;
		$sysData['COOKIES'] = $_COOKIE;
		$sysData['SERVER']  = $_SERVER;

		return $sysData;
	}

	public function standardRestApiResponse($data, $toReturn = FALSE) {

		$this->setCrossDomainHeaders();

		$response         = [];
		$response['data'] = $data;
		//ToDo: Status, messages, etc.

		//ToDo: DANGER!!! Delete on prod.
		$response['test'] = ['Проверка русских букв.',];
		$response['sys']  = $this->systemData();


		//Output.
		if ($toReturn) {
			return $response;
		}

		header('Content-Type: application/json; charset=utf-8');
		//ToDo: Think about encoding (utf8ize).
		echo json_encode(utf8ize($response));
		//echo json_encode($response, JSON_UNESCAPED_UNICODE);
		//echo json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
		return TRUE;
	}

	public function setCrossDomainHeaders() {
		//https://stackoverflow.com/questions/298745/how-do-i-send-a-cross-domain-post-request-via-javascript
		//ToDo: DANGEROUS IF PROD!!!
		switch ($_SERVER['HTTP_ORIGIN']) {
			default:
				header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
				header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
				header('Access-Control-Max-Age: 1000');
				header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
				break;
		}

		return $this;
	}
}