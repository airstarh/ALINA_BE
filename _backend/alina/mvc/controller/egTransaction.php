<?php

namespace alina\mvc\controller;

use \alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader as Loader;
use \alina\mvc\model\user;
use \alina\vendorExtend\illuminate\alinaLaravelCapsule as Dal;

class egTransaction
{
    public function actionIndex() {
	    Loader::init();
    	Dal::beginTransaction();
    	try {
		    $eg1 = new \alina\mvc\model\eg1();
		    $eg2 = new \alina\mvc\model\eg2();

		    /*$stdEg1 = $eg1->insert([
		    	'val' => 'DELETEME',
		    ]);

		    $stdEg2 = $eg2->insert([
			    'val' => 'DELETEME',
			    'eg1_id' => $stdEg1->id,
		    ]);*/



		    //throw new \alina\exceptionValidation('EXCEPTION');

		    Dal::commit();

		    $arr = [111,111,111];
		    $res = $eg1->getAll();

		    $res = array_merge($arr, $res->toArray());

		    echo '<pre>';
		    //print_r('+++++ After +++++');
		    print_r($res);
		    echo '</pre>';

	    } catch (\alina\exceptionValidation $e) {
    		echo '<pre>';
		    print_r('+++++ CATCH +++++');
    		//print_r($e);
    		echo '</pre>';
	    }
    }
}