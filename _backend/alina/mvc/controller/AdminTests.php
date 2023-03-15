<?php

namespace alina\mvc\controller;

use alina\AppExceptionCatcher;
use alina\Mailer;
use alina\Message;
use alina\MessageAdmin;
use alina\mvc\model\_BaseAlinaModel;
use alina\mvc\model\CurrentUser;
use alina\mvc\model\user;
use alina\mvc\view\html;
use alina\mvc\view\json as jsonView;
use alina\utils\Crypy;
use alina\utils\Data;
use alina\utils\FS;
use alina\utils\Request;
use alina\utils\Sys;
use Ratchet\Wamp\Exception;
use \Illuminate\Database\Capsule\Manager as Dal;

class AdminTests
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    ##############################################
    public function actionSomeData()
    {
        // 405 387 367
        Message::setInfo('Hello, people');
        $vd   = [];
        $m    = new \alina\mvc\model\tale();
        $vd[] = $m->getChainOfParents(405);
        $vd[] = $m->getChainOfParents(387);
        $vd[] = $m->getChainOfParents(367);
        echo (new html)->page($vd);
    }

    /**
     * @route /AdminTests/Errors
     */
    public function actionErrors(...$args)
    {
        $vd = (object)[
            'somw' => 'data',
        ];
        CurrentUser::obj();
        Message::setInfo('Just an Info message');
        throw new \ErrorException('Error is thrown in the controller!!!');
        echo (new html)->page($vd);
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
     * @route https://alinazero/AdminTests/ListTableColumns?table=user
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
        Message::setSuccess('For User');
        MessageAdmin::setSuccess('For Admin');
        echo (new html)->page('1234');
    }

    public function actionTestPost()
    {
        Message::setSuccess('For User');
        MessageAdmin::setSuccess('For Admin');
        echo (new html)->page(Request::obj()->POST);
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

    /**
     * @rpite http://alinazero:7001/AdminTests/Nailer
     */
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

    public function actionphpinfo()
    {
        phpinfo();
    }

    #####
    #region Redirect Messages
    public function actionRedirect1()
    {
        Message::setInfo('Info');
        Message::setWarning('Warning');
        Message::setDanger('Danger');
        Message::setSuccess('Проверка руссских букаф');
        //Message::setSuccess(file_get_contents(ALINA_PATH_TO_FRAMEWORK.'/_MISC_CONTENT/_TEST_FILES_CONTENT/001_text_more_2000_chars.txt'));
        Message::setSuccess(file_get_contents(ALINA_PATH_TO_FRAMEWORK . '/_MISC_CONTENT/_TEST_FILES_CONTENT/002.txt'));
        Sys::redirect('admintests/redirect2');
    }

    public function actionRedirect2()
    {
        $vd = (object)[];
        echo (new html)->page($vd);
    }
    #endregion Redirect Messages
    #####
    public function actionFileCount()
    {
        $f1 = '/var/www/www-root/data/www/saysimsim.ru/uploads/25';
        $f2 = '/var/www/www-root/data/www/saysimsim.ru/uploads/25AAA';
        $vd = (object)[
            __DIR__ => FS::countFilesInDir(__DIR__),
            $f1     => FS::countFilesInDir($f1),
            $f2     => FS::countFilesInDir($f2),
        ];
        echo (new html)->page($vd);
    }

    #####

    /**
     * @link https://alinazero/admintests/HtmlPageFlex
     */
    public function actionHtmlPageFlex()
    {
        Message::setInfo('Hello, people');
        Message::setInfo('Hello, people');
        Message::setInfo('Hello, people');
        Message::setInfo('Hello, people');
        $vd = [];
        echo (new html)->page($vd, html::$htmLayoutCleanBody);
    }
    #####
}
