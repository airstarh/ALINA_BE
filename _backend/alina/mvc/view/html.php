<?php

namespace alina\mvc\view;


class html
{
    #region Init
    public $mvcTemplateRoot                         = NULL;
    public $mvcTemplateRootDefault                  = 'mvc/template';
    public $currentControllerDir                    = 'root';
    public $currentActionFileName                   = 'actionIndex';
    public $ext                                     = 'php';
    public $pathToCurrentControllerActionLayoutFile = NULL;
    public $htmlLayout                              = '_system/html/htmlLayout.php';
    public $messageLayout                           = '_system/html/message.php';
    public $content                                 = '';

    public function __construct()
    {
        $this->mvcTemplateRoot        = \alina\app::getConfig('mvc/structure/template');
        $this->mvcTemplateRootDefault = \alina\app::getConfigDefault('mvc/structure/template');
        $this->defineCurrentControllerDir();
        $this->defineCurrentActionFile();
    }
    #endregion Init

    #region Blocks Generation
    public function piece($mvcRelativePathLayout, $data = NULL, $return = TRUE)
    {
        $templateRealPath = $this->resolvePathToTemplate($mvcRelativePathLayout);
        $htmlString       = template($templateRealPath, $data);

        if ($return) {
            return $htmlString;
        } else {
            echo $htmlString;
        }

        return TRUE;
    }

    public function resolvePathToTemplate($mvcRelativePathLayout)
    {
        try {
            $templateFile = buildPathFromBlocks($this->mvcTemplateRoot, $mvcRelativePathLayout);
            $templateFile = \alina\app::get()->resolvePath($templateFile);

            return $templateFile;
        }
        catch (\ErrorException $e) {
            $templateFile = buildPathFromBlocks($this->mvcTemplateRootDefault, $mvcRelativePathLayout);
            $templateFile = \alina\app::get()->resolvePath($templateFile);

            return $templateFile;
        }
    }

    public function defineCurrentControllerDir()
    {
        $this->currentControllerDir = shortClassName(\alina\app::get()->currentController);
    }

    public function defineCurrentActionFile()
    {
        $this->currentActionFileName = \alina\app::get()->currentAction;
    }

    public function definePathToCurrentControllerActionLayoutFile()
    {
        $p = buildPathFromBlocks(
            $this->currentControllerDir,
            $this->currentActionFileName . ".{$this->ext}"
        );

        $this->pathToCurrentControllerActionLayoutFile = $p;

        return $p;

    }

    public function page($data = NULL, $htmlLayout = FALSE)
    {
        if ($htmlLayout) {
            $this->htmlLayout = $htmlLayout;
        }

        $this->content = $this->piece($this->definePathToCurrentControllerActionLayoutFile(), $data);
        $htmlString    = $this->piece($this->htmlLayout, $this);

        return $htmlString;
    }
    #endregion Blocks Generation

    #region HTML page specials (css, js, etc.)
    // ToDo: Complete.
    public function css() { return ''; }

    // ToDo: Complete.
    public function js() { 
        $allJs = \alina\app::getConfig('html/js');
        return ''; 
    }

    public function messages() { return \alina\message::returnAllHtmlString(); }
    #endregion HTML page specials (css, js, etc.)
}