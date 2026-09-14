<?php

namespace alina\mvc\Controller;

use alina\mvc\Model\_baseAlinaEloquentTransaction as Transaction;

class egTransaction
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    public function actionIndex()
    {
        try {
            Transaction::begin(__FUNCTION__);
            $asd   = new \alina\mvc\Model\asd();
            $asd_j = new \alina\mvc\Model\asd();
            $asd1  = $asd->insert([
                'price' => '222',
                'txt'   => 'DELETEME',
            ]);
            $asd2 = $asd_j->insert([
                'price' => '111',
                'txt'   => $asd1->id,
            ]);
            //throw new \alina\exceptionValidation('EXCEPTION');
            Transaction::commit(__FUNCTION__);
            $res = (new \alina\mvc\Model\asd())
                    ->q('asd')
                    ->select([
                        'asd.id AS asd_id',
                        'asd.price AS asd_price',
                        'asd.txt AS asd_txt',
                        'asd_j.id AS asd_j_id',
                        'asd_j.price AS asd_j_price',
                        'asd_j.txt AS asd_j_txt',
                    ])
                    ->leftJoin('asd AS asd_j', 'asd_j.txt', '=', 'asd.id')
                    ->get();

            AlinaEchoDraft($res);
        }
        catch (\alina\AppException $e) {
            error_log($e->getMessage());
            AlinaEchoDraft($e);
        }
    }
}
