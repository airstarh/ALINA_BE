<?php
namespace alina\mvc\model;

// Laravel initiation
\alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader::init();

class eloquentModel extends \Illuminate\Database\Eloquent\Model
{
    use \alina\mvc\model\eav;
}