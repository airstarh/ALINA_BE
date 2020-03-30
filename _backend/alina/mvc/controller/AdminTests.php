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
        Message::setInfo('Hello, people');
        $vd = [
            'hello' => 'world',
            'Yo',
        ];
        #####
        //WORKS
        // $items = Dal::table('information_schema.columns')
        //     ->select('column_name')
        //     ->where('table_name', '=', 'tale')
        //     ->where('table_schema', '=', AlinaCFG('db/database'))
        //     ->pluck('column_name');
        // $vd = $items;
        #####
        #####
        //Works
        //$vd = Dal::select(Dal::raw('SELECT * FROM tale  LIMIT  10'));
        #####
        $by_answer_to_tale_id = "(SELECT COUNT(*) FROM tale AS tale1 WHERE tale1.answer_to_tale_id = tale.id) AS by_answer_to_tale_id";
        $by_root_tale_id      = "(SELECT COUNT(*) FROM tale AS tale2 WHERE tale2.root_tale_id = tale.id) AS by_root_tale_id";
        $m                    = new \alina\mvc\model\tale();
        $q                    = $m->q();
        $q->addSelect('tale.id');
        $q->addSelect(Dal::raw("(SELECT COUNT(*) FROM tale AS tale1 WHERE tale1.answer_to_tale_id = {$m->alias}.{$m->pkName}) AS count_answer_to_tale_id"));
        $q->addSelect(Dal::raw("(SELECT COUNT(*) FROM tale AS tale2 WHERE tale2.root_tale_id = {$m->alias}.{$m->pkName}) AS count_root_tale_id"));
        $vd = $q->get()->toArray();
        #####
        echo (new html)->page($vd);
    }

    /**
     * @route /AdminTests/Errors
     */
    public function actionErrors(...$args)
    {
        Message::setInfo('We throw error in the template!!!');
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
        Message::setSuccess('For User');
        MessageAdmin::setSuccess('For Admin');
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
        Message::setSuccess(file_get_contents(ALINA_PATH_TO_FRAMEWORK.'/_MISC_CONTENT/_TEST_FILES_CONTENT/002.txt'));
        Sys::redirect('admintests/redirect2');
    }

    public function actionRedirect2()
    {
        $vd = (object)[];
        echo (new html)->page($vd);
    }
    #endregion Redirect Messages
    #####
}
