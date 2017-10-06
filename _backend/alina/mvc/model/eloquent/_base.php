<?php
namespace alina\mvc\model\eloquent;

use \alina\vendorExtend\illuminate\alinaLaravelCapsuleLoader AS LaravelEloquentLoader;
use Illuminate\Database\Eloquent\Relations\Relation;
use \Illuminate\Database\Eloquent\Model AS LaravelEloquentModel;
use \alina\trait_all_classes;
use \alina\mvc\model\eloquent\trait_eloquent;
use \alina\mvc\model\eloquent\trait_validation;
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
    use trait_all_classes;
    use trait_eloquent;
    use trait_validation;
    use trait_entity;

    protected $primaryKey = 'id';
    protected $guarded    = [];
    protected $dateFormat = 'U';
    public    $timestamps = FALSE;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}