<?php
namespace alina\mvc\model;

use Illuminate\Database\Eloquent\Relations\Relation;

#region Laravel initiation
\alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader::init();

Relation::morphMap([
    'product' => 'alina\mvc\model\eloquentProduct',
    'user'    => 'alina\mvc\model\eloquentUser',
]);
#endregion Laravel initiation

class EloquentModel extends \Illuminate\Database\Eloquent\Model
{
    use \alina\mvc\model\eav;
}