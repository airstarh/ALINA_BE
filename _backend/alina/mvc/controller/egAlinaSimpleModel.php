<?php

namespace alina\mvc\controller;

use \alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader as Loader;
use \alina\mvc\model\user;
use \alina\vendorExtend\illuminate\alinaLaravelCapsule as Dal;

Loader::init();

class egAlinaSimpleModel
{
    public function actionIndex()
    {
        $m = new \alina\mvc\model\user();

        $r = $m->getAll();

        echo '<pre>';
        print_r($r);
        echo '</pre>';
    }

    public function actionTransaction() {
    	Dal::beginTransaction();
    	try {
		    $m = new user();
		    $r = $m->getAll();

		    $m->insert([
		    	'firstname' => 'DELETEME',
		    	'lastname' => 'DELETEME',
		    	'password' => 'DELETEME',
		    	'mail' => 'DELETEME',
		    ]);

		    $res = $m->getAll(['firstname' => 'DELETEME',]);


	    } catch (\alina\exceptionValidation $e) {
    		echo '<pre>';
    		print_r($e);
    		echo '</pre>';
	    }
	    Dal::commit();

    	echo '<pre>';
    	print_r('+++++ After +++++');
    	print_r($res);
    	echo '</pre>';
    }
}