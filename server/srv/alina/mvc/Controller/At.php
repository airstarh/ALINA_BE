<?php

namespace alina\mvc\Controller;

use alina\Mailer;
use alina\Message;
use alina\MessageAdmin;
use alina\mvc\Model\_BaseAlinaModel;
use alina\mvc\Model\CurrentUser;
use alina\mvc\Model\user;
use alina\mvc\View\html;
use alina\mvc\View\html as htmlAlias;
use alina\mvc\View\json as jsonView;
use alina\Utils\Arr;
use alina\Utils\Crypto;
use alina\Utils\Data;
use alina\Utils\FS;
use alina\Utils\Request;
use alina\Utils\Sys;
use DOMXpath;
use stdClass;

class At
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    public function actionFast()
    {
        $vd = [
            "___('project')" => ___('project'),
            "___('Alina')"   => ___('Alina'),
            "___('Admin')"   => ___('Admin'),
            "___('ADMIN')"   => ___('ADMIN'),
            "___('admin')"   => ___('admin'),
        ];

        AlinaEcho((new htmlAlias())->page($vd));
    }

    public function actionFast2()
    {
        $vd[] = json_encode('asd');
        $vd[] = json_encode("null");
        $vd[] = json_encode(null);
        $vd[] = json_encode(true);
        $vd[] = json_encode(false);
        $vd[] = json_encode(1234);

        $vd[] = Data::hlpGetBeautifulJsonString('asd');
        $vd[] = Data::hlpGetBeautifulJsonString("null");
        $vd[] = Data::hlpGetBeautifulJsonString(null);
        $vd[] = Data::hlpGetBeautifulJsonString(true);
        $vd[] = Data::hlpGetBeautifulJsonString(false);
        $vd[] = Data::hlpGetBeautifulJsonString(1234);

        AlinaEchoDraft($vd);
    }

    ##############################################
    public function actionSomeData()
    {
        // 405 387 367
        Message::setInfo('Hello, people');
        $vd   = [];
        $m    = new \alina\mvc\Model\tale();
        $vd[] = $m->getChainOfParents(405);
        $vd[] = $m->getChainOfParents(1008);
        $vd[] = $m->getChainOfParents(1009);
        AlinaEcho((new htmlAlias())->page($vd));
    }

    /**
     * @route /at/Errors
     */
    public function actionErrors(...$args)
    {
        $vd = (object) [
            'somw' => 'data',
        ];
        CurrentUser::obj();
        Message::setInfo('Just an Info message');

        // throw new \ErrorException('Error is thrown in the controller!!!');
        AlinaEcho((new htmlAlias())->page('Hellow Error'));
    }
    ##############################################

    /**
     * @route /at/Serialization
     */
    public function actionSerialization()
    {
        $d = require_once ALINA_PATH_TO_FRAMEWORK . '/_MISC_CONTENT/complicated_nixed_object.php';

        AlinaEchoDraft(serialize($d));
    }

    ##############################################

    /**
     * @route /at/JsonEncode
     */
    public function actionJsonEncode()
    {
        $d = require_once ALINA_PATH_TO_FRAMEWORK . '/_MISC_CONTENT/complicated_nixed_object.php';

        AlinaEchoDraft(json_encode($d));
    }

    ##############################################

    /**
     * @route /at/ListTableColumns?table=user
     */
    public function actionListTableColumns()
    {
        $vd = (new _BaseAlinaModel(['table' => $_GET['table']]))->fields();
        AlinaEcho((new jsonView())->standardRestApiResponse($vd));
    }

    ##############################################
    public function actionTestMessages()
    {
        Message::setSuccess('For User');
        MessageAdmin::setSuccess('For Admin');
        AlinaEcho((new htmlAlias())->page('1234'));
    }

    /**
     * Test POST Request
     * @route /at/testpost
     */
    public function actionTestPost()
    {
        Message::setSuccess('Message for %s', ['User']);
        MessageAdmin::setSuccess('Message for %s', ['Admin']);
        AlinaEcho((new htmlAlias())->page(Request::obj()));
    }

    ##############################################

    /**
     * URLs:
     * /egCaseSensitivity/TestCase/lalala?hello='world'
     */
    public function actionTestCase()
    {
        $content = func_get_args();
        AlinaEcho((new htmlAlias())->page($content));
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
            [
                static function ($qu) {
                    $qu->whereIn('user.id', [2, 3]);
                },
            ],
            'firstname' => 'Третий',
        ];
        $orderArray = [["{$m->alias}.id", 'DESC']];
        $limit      = null;
        $offset     = null;
        $m->getAllWithReferences($conditions, $orderArray, $limit, $offset);

        AlinaEchoDraft($m->collection->toArray());
    }

    ##############################################

    /**
     * /at/Mailer
     */
    public function actionMailer()
    {
        $data = Sys::buffer(static function () {
            return (new Mailer())->usageExample();
        });
        AlinaEcho((new htmlAlias())->page($data));
    }

    ##############################################

    /**
     * http://www.codernotes.ru/articles/php/obratimoe-shifrovanie-po-klyuchu-na-php.html
     */
    public function actionReversibleEncryption()
    {
        $vd         = [];
        $vd['str']  = 'mail';
        $vd['encr'] = (new Crypto())->encrypt($vd['str']);
        $vd['decr'] = (new Crypto())->decrypt($vd['encr']);
        AlinaEcho((new htmlAlias())->page($vd));
    }

    ##############################################
    public function actionBaseAlinaModel()
    {
        $res                         = [];
        $res['getById']              = (new user())->getById(1);
        $res['getOneWithReferences'] = (new user())->getOneWithReferences(['user.id' => 1,]);
        AlinaEcho((new html())->page($res));
    }

    ##############################################
    public function actionLocale()
    {
        $vd = [
            'date(\'Z\')' => date('Z'),
        ];
        AlinaEcho((new htmlAlias())->page(date('Z')));
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
        AlinaEcho((new htmlAlias())->page($vd));
    }

    ##############################################
    // /at/DomDocument
    public function actionDomDocument()
    {
        $vd = (object) [
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
        $HTML5DOMDocument->preserveWhiteSpace = true;
        $HTML5DOMDocument->formatOutput       = false;
        $HTML5DOMDocument->loadHTML($html);
        ##################################################
        $DOMXpath = new DOMXpath($HTML5DOMDocument);

        foreach ($DOMXpath->query(implode('|', $forbidden)) as $node) {
            $node->parentNode->removeChild($node);
        }
        $body     = $HTML5DOMDocument->getElementsByTagName('body')->item(0);
        $bodyHTML = $body->innerHTML;
        ##################################################
        $vd->init = $html;
        $vd->res  = $bodyHTML;
        AlinaEcho((new htmlAlias())->page($vd));
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
        Sys::redirect('at/redirect2');
    }

    public function actionRedirect2()
    {
        $vd = new stdClass();
        AlinaEcho((new htmlAlias())->page($vd));
    }
    #endregion Redirect Messages
    #####
    public function actionFileCount()
    {
        $f1 = '/var/www/www-root/data/www/saysimsim.ru/uploads/25';
        $f2 = '/var/www/www-root/data/www/saysimsim.ru/uploads/25AAA';
        $vd = (object) [
            __DIR__ => FS::countFilesInDir(__DIR__),
            $f1     => FS::countFilesInDir($f1),
            $f2     => FS::countFilesInDir($f2),
        ];
        AlinaEcho((new htmlAlias())->page($vd));
    }

    #####

    /**
     * /at/HtmlPageFlex
     */
    public function actionHtmlPageFlex()
    {
        Message::setInfo('Hello, people');
        Message::setInfo('Hello, people');
        Message::setInfo('Hello, people');
        Message::setInfo('Hello, people');
        $vd = [];
        AlinaEcho((new htmlAlias())->page($vd, html::$htmLayoutCleanBody));
    }

    public function actionPhpSettings()
    {
        Message::setInfo('Info');
        Message::setWarning('Warnung');
        Message::setDanger('Danger');
        error_log(__METHOD__);
        $vd = (object) [
                'display_errors'     => ini_get('display_errors'),
                'max_execution_time' => ini_get('max_execution_time'),
                'error_log'          => ini_get('error_log'),
                'ALINA_MODE(env)'    => getenv('ALINA_MODE'),
                'ALINA_MODE(app)'    => ALINA_MODE,
            ];
        AlinaEcho((new htmlAlias())->page($vd, htmlAlias::$htmLayoutWide));
    }

    public function actionUniqModel()
    {
        $ip = 'asd';
        $d  = ['ip' => $ip];

        // $q  = $m->q(-1);

        // $r = $m->getModelByUniqueKeys($d);


        // $r = $m->insert($d);

        // $r = $q->upsert(
        //     $d,
        //     ['ip'],
        //     ['visits' => $q->raw('visits + 1')]
        // );

        $arr1 = [
            'a' => 1,
            'b' => [1,2,3, 'sewa', 'pizda'],
            'c' => [
                'aaa' => 1,
                'bbb' => 2,
            ],
        ];

        $arr2 = [
            'a' => 2,
            'b' => [3, 4, 5, 'sewa', 'huy'],
            'c' => [
                'ccc' => 33,
            ],
        ];

        $res = Arr::arrayMergeRecursive($arr1, $arr2);
        // $res = array_merge($arr1, $arr2);
        // $res = array_merge_recursive($arr1, $arr2);

        AlinaEchoDraft($res);
    }
}
