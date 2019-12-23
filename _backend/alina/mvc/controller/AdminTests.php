<?php

namespace alina\mvc\controller;

use alina\exceptionCatcher;
use alina\Mailer;
use alina\Message;
use alina\MessageAdmin;
use alina\mvc\model\_BaseAlinaModel;
use alina\mvc\view\html;
use alina\mvc\view\json as jsonView;
use alina\utils\Crypy;
use alina\utils\Request;
use alina\utils\Sys;

class AdminTests
{
    ##############################################
    /**
     * @route /AdminTests/Errors
     */
    public function actionErrors(...$args)
    {
        throw new \ErrorException(11111111111111);
    }
    ##############################################

    /**
     * @route /AdminTests/Serialization
     */
    public function actionSerialization()
    {
        $d = require_once ALINA_PATH_TO_FRAMEWORK . '/_MISC_CONTENT/complicated_nixed_object.php';
        echo '<pre>';
        print_r(serialize($d));
        echo '</pre>';

        return $this;
    }

    ##############################################

    /**
     * @route /AdminTests/JsonEncode
     */

    public function actionJsonEncode()
    {
        $d = require_once ALINA_PATH_TO_FRAMEWORK . '/_MISC_CONTENT/complicated_nixed_object.php';
        echo '<pre>';
        print_r(json_encode($d));
        echo '</pre>';

        return $this;
    }

    ##############################################

    /**
     * @route /AdminTests/ListTableColumns?table=user
     */

    public function actionListTableColumns()
    {
        $vd = (new _BaseAlinaModel(['table' => $_GET['table']]))->fields();
        echo (new jsonView())->standardRestApiResponse($vd);

        return $this;
    }

    ##############################################
    public function actionTestMessages()
    {
        Message::set('For User');
        MessageAdmin::set('For Admin');

        echo (new html)->page('1234');
    }

    ##############################################

    public function actionMailer()
    {
        $data = Sys::buffer(function () {
            return (new Mailer())->usageExample();
        });
        echo (new html)->page($data);
    }

    /**
     * http://www.codernotes.ru/articles/php/obratimoe-shifrovanie-po-klyuchu-na-php.html
     */
    public function actionReversibleEncryption()
    {
        $vd           = [];
        $vd['str']    = 'mail';
        $vd['encr']   = (new Crypy())->revencr($vd['str']);
        $vd['decr']   = (new Crypy())->revdecr($vd['encr']);

        echo (new html)->page($vd);
    }
}
