<?php

namespace alina\mvc\Model;

class audit extends _BaseAlinaModel
{
    public $table = 'audit';

    public function fields()
    {
        return [
            'id'         => [],
            'at'         => [
                'default' => ALINA_TIME,
            ],
            'user_id'    => [
                'default' => CurrentUser::obj()->id(),
            ],
            'table_name' => [

            ],
            'table_id'   => [

            ],
            'event_name' => [

            ],
            'event_data' => [

            ],
        ];
    }

    #####
    public function referencesTo()
    {
        return [
            '_user' => [
                'has'        => 'one',
                'joins'      => [
                    ['leftJoin', 'user AS _user', '_user.id', '=', "{$this->alias}.user_id"],
                    //ToDo: JOIN table by table.name & table.id
                    // https://stackoverflow.com/questions/16848987/a-join-with-additional-conditions-using-query-builder-or-eloquent
                ],
                'conditions' => [],
                'addSelects' => [
                    [
                        'addSelect', [
                        '_user.firstname AS _user_firstname',
                        '_user.lastname AS _user_lastname',
                        '_user.emblem AS _user_emblem',
                    ],
                    ],
                ],
            ],
        ];
    }
    #####
}
