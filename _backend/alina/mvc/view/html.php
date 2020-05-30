<?php

namespace alina\mvc\view;

use alina\GlobalRequestStorage;
use alina\Message;
use alina\MessageAdmin;
use alina\mvc\model\CurrentUser;
use alina\mvc\view\json as jsonView;
use alina\utils\Sys;

class html
{
    #region Init
    public $mvcTemplateRoot                         = NULL;
    public $mvcTemplateRootDefault                  = 'mvc/template';
    public $currentControllerDir                    = 'root';
    public $currentActionFileName                   = 'actionIndex';
    public $ext                                     = 'php';
    public $pathToCurrentControllerActionLayoutFile = NULL;
    public $pathToGlobalHtmlPageWrapper             = '_system/html/htmlLayout.php';
    public $messageLayout                           = '_system/html/message.php';
    public $content                                 = '';

    public function __construct()
    {
        $this->mvcTemplateRoot        = AlinaCFG('mvc/structure/template');
        $this->mvcTemplateRootDefault = AlinaCFG_default('mvc/structure/template');
        $this->defineCurrentControllerDir();
        $this->defineCurrentActionFile();
    }
    #endregion Init
    #region Blocks Generation
    public function defineCurrentControllerDir()
    {
        $this->currentControllerDir = \alina\utils\Resolver::shortClassName(Alina()->currentController);
    }

    public function defineCurrentActionFile()
    {
        $this->currentActionFileName = Alina()->currentAction;
    }

    public function page($data = NULL, $htmlLayout = FALSE)
    {
        if (Sys::isAjax()) {
            return (new jsonView())->standardRestApiResponse($data);
        }
        if ($htmlLayout) {
            $this->pathToGlobalHtmlPageWrapper = $htmlLayout;
        }
        $this->content = $this->piece($this->definePathToCurrentControllerActionLayoutFile(), $data);
        if (FALSE === $this->content) {
            if ($data === NULL) {
                $this->content = '';
            }
            else {
                $this->content = [
                    '<pre>',
                    var_export($data, 1),
                    '</pre>',
                ];
                $this->content = implode('', $this->content);
            }
        }
        $htmlString = $this->piece($this->pathToGlobalHtmlPageWrapper, $this);

        return $htmlString;
    }

    public function piece($mvcRelativePathLayout, $data = NULL, $return = TRUE)
    {
        $templateRealPath = $this->resolvePathToTemplate($mvcRelativePathLayout);
        if (FALSE === $templateRealPath) {
            return FALSE;
        }
        $htmlString = \alina\utils\Sys::template($templateRealPath, $data);
        if ($return) {
            return $htmlString;
        }
        else {
            echo $htmlString;
        }

        return TRUE;
    }

    public function resolvePathToTemplate($mvcRelativePathLayout)
    {
        try {
            $templateFile = \alina\utils\FS::buildPathFromBlocks($this->mvcTemplateRoot, $mvcRelativePathLayout);
            $templateFile = Alina()->resolvePath($templateFile);

            return $templateFile;
        } catch (\ErrorException $e) {
            try {
                $templateFile = \alina\utils\FS::buildPathFromBlocks($this->mvcTemplateRootDefault, $mvcRelativePathLayout);
                $templateFile = Alina()->resolvePath($templateFile);

                return $templateFile;
            } catch (\Exception $e) {
                return FALSE;
            }
        }
    }

    public function definePathToCurrentControllerActionLayoutFile()
    {
        $p                                             = \alina\utils\FS::buildPathFromBlocks(
            $this->currentControllerDir,
            $this->currentActionFileName . ".{$this->ext}"
        );
        $this->pathToCurrentControllerActionLayoutFile = $p;

        return $p;
    }
    #endregion Blocks Generation
    ##################################################
    #region HTML page specials (css, js, etc.)
    public function pageTitle()
    {
        $res = GlobalRequestStorage::obj()->get('pageTitle');
        if ($res) {
            return $res;
        }

        return AlinaCFG('title');
    }

    public function css()
    {
        $urls = \alina\app::getConfig('html/css');
        if (isset($urls) && !empty($urls && \alina\utils\Data::isIterable($urls))) {
            $result = '';
            foreach ($urls as $i => $url) {
                $result .= $this->piece('_system/html/tag/link.php', $url);
                $result .= PHP_EOL;
            }

            return $result;
        }

        return '';
    }

    public function js()
    {
        $urls = \alina\app::getConfig('html/js');
        if (isset($urls) && !empty($urls && \alina\utils\Data::isIterable($urls))) {
            $result = '';
            foreach ($urls as $i => $url) {
                $result .= $this->piece('_system/html/tag/script.php', $url);
            }

            return $result;
        }

        return '';
    }

    public function messages()
    {
        $str = '';
        $str .= Message::returnAllHtmlString();
        if (AlinaAccessIfAdmin()) {
            $str .= MessageAdmin::returnAllHtmlString();
        }

        return $str;
    }

    public function content()
    {
        return $this->content;
    }
    #endregion HTML page specials (css, js, etc.)
    ##################################################
    #region Elements
    static public function elForm(array $p = [])
    {
        return (new static())->piece('_system/html/_form/form.php', (object)$p);
    }

    static public function elBootstrapBadge(array $p = [])
    {
        return (new static())->piece('_system/html/tag/bootstrapBadge.php', (object)$p);
    }

    static public function elFormSelectOneSimple(array $p = [])
    {
        return (new static())->piece('_system/html/_form/selectOneSimple.php', (object)$p);
    }

    static public function elFormSelect(array $p = [])
    {
        return (new static())->piece('_system/html/_form/select.php', (object)$p);
    }

    static public function elFormInputText(array $p = [])
    {
        return (new static())->piece('_system/html/_form/inputText.php', (object)$p);
    }

    static public function elFormStandardButtons(array $p = [])
    {
        return (new static())->piece('_system/html/_form/standardFormButtons.php', (object)$p);
    }
    #endregion Elements
}
