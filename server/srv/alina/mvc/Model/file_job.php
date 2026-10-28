<?php

namespace alina\mvc\Model;

class file_job extends _BaseAlinaModel
{
    public $table='file_job';
    public function fields()
    {
        return [
            'id'=>[],'public_id'=>[],'type'=>[],'status'=>['default'=>'uploading'],'owner_id'=>[],
            'entity_table'=>[],'entity_id'=>[],'source_tmp'=>[],'result_tmp'=>[],'original_name'=>[],
            'mime'=>[],'payload'=>[],'progress'=>['default'=>0],'attempts'=>['default'=>0],'error'=>[],
            'worker_id'=>[],'lease_until'=>[],'created_at'=>['default'=>ALINA_TIME],
            'started_at'=>[],'finished_at'=>[],'updated_at'=>['default'=>ALINA_TIME],
        ];
    }
    public function uniqueKeys(){return [['public_id']];}
}
