<?php
/**
 * Created by PhpStorm.
 * User: ladmin
 * Date: 19.05.2017
 * Time: 6:05
 */

namespace alina\mvc\view;


class html
{
    #region Init
    public $mvcTemplateRoot   = '';
    public $currentController = '';
    public $currentAction     = '';
    public $ext               = 'php';

    public $htmlLayout    = '_system/html/htmlLayout.php';
    public $messageLayout = '_system/html/message.php';

    public function __construct()
    {
        $this->mvcTemplateRoot   = \alina\app::getConfig('mvc/structure/template');
        $this->currentController = \alina\app::get()->router->controller;
        $this->currentAction     = \alina\app::get()->router->action;
    }
    #endregion Init

    #region Blocks Generation
    public function piece($blockLayout, $data = NULL, $return = TRUE)
    {
        $templateFile = $this->mvcTemplateRoot . DIRECTORY_SEPARATOR . $blockLayout;
        $templateFile = \alina\app::get()->resolvePath($templateFile);
        $htmlString   = template($templateFile, $data);

        if ($return) {
            return $htmlString;
        }
        echo $htmlString;

        return TRUE;
    }

    public function controllerAction($data = NULL, $blockLayout = FALSE)
    {
        if (empty($blockLayout)) {
            $c = $this->currentController;
            $a = $this->currentAction ? $this->currentAction : \alina\app::getConfig('mvc/defaultAction');
            $a .= ".{$this->ext}";
            $blockLayout = buildPathFromBlocks($c, $a);
        }

        return $this->piece($blockLayout, $data);
    }

    public $content = '';

    public function page($data = NULL, $blockLayout = FALSE, $htmlLayout = FALSE)
    {
        if ($htmlLayout) {
            $this->htmlLayout = $htmlLayout;
        }

        $this->content = $this->controllerAction($data, $blockLayout);
        $htmlString    = $this->piece($this->htmlLayout, $this);

        return $htmlString;
    }
    #endregion Blocks Generation

    #region HTML page specials (css, js, etc.)
    public function css() {return '';}
    public function js() {return '';}
    #endregion HTML page specials (css, js, etc.)
}