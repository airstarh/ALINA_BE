<?php

namespace alina\mvc\controller;

use alina\exceptionCatcher;
use alina\Mailer;
use alina\Message;
use alina\MessageAdmin;
use alina\mvc\model\_BaseAlinaModel;
use alina\mvc\model\user;
use alina\mvc\view\html;
use alina\mvc\view\json as jsonView;
use alina\utils\Crypy;
use alina\utils\Data;
use alina\utils\Request;
use alina\utils\Sys;
use Ratchet\Wamp\Exception;

class AdminTests
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    ##############################################
    public function actionSomeData()
    {
        Message::setInfo('Hello, people');
        $vd = [
            'hello' => 'world',
            'Yo',
        ];
        echo (new html)->page($vd);
    }

    /**
     * @route /AdminTests/Errors
     */
    public function actionErrors(...$args)
    {
        Message::set('We throw error in the template!!!');
        echo (new html)->page();
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

    /**
     * URLs:
     * http://alinazero/egCaseSensitivity/TestCase/lalala?hello='world'
     */
    public function actionTestCase()
    {
        $content = func_get_args();
        echo (new \alina\mvc\view\html)->page($content);
    }

    ##############################################
    public function actionTestReferences()
    {
        $m          = new user();
        $conditions = ["{$m->alias}.id" => '2',];
        $orderArray = [["{$m->alias}.id", 'DESC']];
        $limit      = 2;
        $offset     = 2;
        $m          = new user();
        $conditions = [
            [function ($qu) {
                $qu->whereIn('user.id', [2, 3]);
            }],
            'firstname' => 'Третий',
        ];
        $orderArray = [["{$m->alias}.id", 'DESC']];
        $limit      = NULL;
        $offset     = NULL;
        $m->getAllWithReferences($conditions, $orderArray, $limit, $offset);
        echo '<pre>';
        print_r($m->collection->toArray());
        echo '</pre>';
    }

    ##############################################
    public function actionMailer()
    {
        $data = Sys::buffer(function () {
            return (new Mailer())->usageExample();
        });
        echo (new html)->page($data);
    }

    ##############################################

    /**
     * http://www.codernotes.ru/articles/php/obratimoe-shifrovanie-po-klyuchu-na-php.html
     */
    public function actionReversibleEncryption()
    {
        $vd         = [];
        $vd['str']  = 'mail';
        $vd['encr'] = (new Crypy())->revencr($vd['str']);
        $vd['decr'] = (new Crypy())->revdecr($vd['encr']);
        echo (new html)->page($vd);
    }

    ##############################################
    public function actionBaseAlinaModel()
    {
        $res                         = [];
        $res['getById']              = (new user())->getById(1);
        $res['getOneWithReferences'] = (new user())->getOneWithReferences(['user.id' => 1,]);
        echo (new html)->page($res);
    }

    ##############################################
    public function actionLocale()
    {
        $vd = [
            'date(\'Z\')' => date('Z'),
        ];
        echo (new html)->page(date('Z'));
    }

    ##############################################
    public function actionConversionToObject()
    {
        $initial   = file_get_contents(ALINA_PATH_TO_FRAMEWORK . '/_MISC_CONTENT/001.json');
        $converted = Data::toObject($initial);
        $vd        = [
            'initial'   => $initial,
            'converted' => $converted,
        ];
        echo (new html)->page($vd);
    }

    ##############################################
    // http://alinazero:8080/AdminTests/DomDocument
    public function actionDomDocument()
    {
        $vd = (object)[
            'init' => 'val',
            'res'  => 'val',
        ];
        #####
        $forbidden = [
            '//style',
            '//script',
        ];
        #####
        $html = file_get_contents(ALINA_PATH_TO_FRAMEWORK . '/_MISC_CONTENT/_TEST_FILES_CONTENT/HTML/001.html');
        $html = 1234;
        ##################################################
        $HTML5DOMDocument                     = new \IvoPetkov\HTML5DOMDocument();
        $HTML5DOMDocument->preserveWhiteSpace = TRUE;
        $HTML5DOMDocument->formatOutput       = FALSE;
        $HTML5DOMDocument->loadHTML($html);
        ##################################################
        $DOMXpath = new \DOMXpath($HTML5DOMDocument);
        foreach ($DOMXpath->query(implode('|', $forbidden)) as $node) {
            $node->parentNode->removeChild($node);
        }
        $body     = $HTML5DOMDocument->getElementsByTagName('body')->item(0);
        $bodyHTML = $body->innerHTML;
        ##################################################
        $vd->init = $html;
        $vd->res  = $bodyHTML;
        echo (new html)->page($vd);
    }
}
