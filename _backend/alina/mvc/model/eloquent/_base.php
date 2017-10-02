<?php
namespace alina\mvc\model\eloquent;

use \alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader AS LaravelEloquentLoader;
use Illuminate\Database\Eloquent\Relations\Relation;
use \Illuminate\Database\Eloquent\Model AS LaravelEloquentModel;
use \alina\mvc\model\eloquent\eav\trait_entity;

#region Laravel initiation

LaravelEloquentLoader::init();

Relation::morphMap([
    'product' => 'alina\mvc\model\eloquent\product',
    'user'    => 'alina\mvc\model\eloquent\user',
]);

#endregion Laravel initiation

class _base extends LaravelEloquentModel
{
    use trait_entity;
}