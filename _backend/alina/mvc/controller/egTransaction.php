<?php

namespace alina\mvc\controller;

use \alina\mvc\model\_baseAlinaEloquentTransaction as Transaction;


class egTransaction
{
    public function actionIndex() {

	    Transaction::begin(__FUNCTION__);

    	try {
		    $eg1 = new \alina\mvc\model\eg1();
		    $eg2 = new \alina\mvc\model\eg2();

		    $stdEg1 = $eg1->insert([
		    	'val' => 'DELETEME',
		    ]);

		    $stdEg2 = $eg2->insert([
			    'val' => 'DELETEME',
			    'eg1_id' => $stdEg1->id,
		    ]);

		    //throw new \alina\exceptionValidation('EXCEPTION');

		    Transaction::commit(__FUNCTION__);

		    $res = (new \alina\mvc\model\eg2())
			    ->q('eg2')
			    ->select([
			    	'eg1.id AS eg1_id',
				    'eg1.val AS eg1_val',

				    'eg2.id AS eg2_id',
				    'eg2.val AS eg2_val',
				    'eg2.eg1_id AS ref_eg1_id',
			    ])
			    ->leftJoin('eg1 AS eg1', 'eg2.eg1_id', '=', 'eg1.id')
			    ->get()
		    ;

		    echo '<pre>';
		    //print_r('+++++ After +++++');
		    print_r($res);
		    echo '</pre>';

	    } catch (\alina\exception $e) {
    		echo '<pre>';
		    print_r('+++++ CATCH +++++');
    		//print_r($e);
    		echo '</pre>';
	    }
    }
}