<?php

namespace alina\mvc\view;

class html {
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

	public function __construct() {
		$this->mvcTemplateRoot        = \alina\app::getConfig('mvc/structure/template');
		$this->mvcTemplateRootDefault = \alina\app::getConfigDefault('mvc/structure/template');
		$this->defineCurrentControllerDir();
		$this->defineCurrentActionFile();
	}
	#endregion Init

	#region Blocks Generation
	public function defineCurrentControllerDir() {
		$this->currentControllerDir = shortClassName(\alina\app::get()->currentController);
	}

	public function defineCurrentActionFile() {
		$this->currentActionFileName = \alina\app::get()->currentAction;
	}

	public function page($data = NULL, $htmlLayout = FALSE) {
		if ($htmlLayout) {
			$this->pathToGlobalHtmlPageWrapper = $htmlLayout;
		}

		$this->content = $this->piece($this->definePathToCurrentControllerActionLayoutFile(), $data);
		if (FALSE === $this->content) {
			$this->content = $data;
		}
		$htmlString = $this->piece($this->pathToGlobalHtmlPageWrapper, $this);

		return $htmlString;
	}

	public function piece($mvcRelativePathLayout, $data = NULL, $return = TRUE) {
		$templateRealPath = $this->resolvePathToTemplate($mvcRelativePathLayout);
		if (FALSE === $templateRealPath) {
			return FALSE;
		}
		$htmlString = template($templateRealPath, $data);

		if ($return) {
			return $htmlString;
		} else {
			echo $htmlString;
		}

		return TRUE;
	}

	public function resolvePathToTemplate($mvcRelativePathLayout) {
		try {
			$templateFile = buildPathFromBlocks($this->mvcTemplateRoot, $mvcRelativePathLayout);
			$templateFile = \alina\app::get()->resolvePath($templateFile);

			return $templateFile;
		} catch (\ErrorException $e) {
			try {
				$templateFile = buildPathFromBlocks($this->mvcTemplateRootDefault, $mvcRelativePathLayout);
				$templateFile = \alina\app::get()->resolvePath($templateFile);

				return $templateFile;
			} catch (\Exception $e) {
				return FALSE;
			}
		}
	}

	public function definePathToCurrentControllerActionLayoutFile() {
		$p = buildPathFromBlocks(
			$this->currentControllerDir,
			$this->currentActionFileName . ".{$this->ext}"
		);

		$this->pathToCurrentControllerActionLayoutFile = $p;

		return $p;
	}
	#endregion Blocks Generation

	#region HTML page specials (css, js, etc.)
	public function css() {
		$urls = \alina\app::getConfig('html/css');
		if (isset($urls) && !empty($urls && isIterable($urls))) {
			$result = '';
			foreach ($urls as $i => $url) {
				$result .= $this->piece('_system/html/tag/link.php', $url);
                $result .= PHP_EOL;
			}

			return $result;
		}

		return '';
	}

	public function js() {
		$urls = \alina\app::getConfig('html/js');
		if (isset($urls) && !empty($urls && isIterable($urls))) {
			$result = '';
			foreach ($urls as $i => $url) {
				$result .= $this->piece('_system/html/tag/script.php', $url);
			}

			return $result;
		}

		return '';
	}

	public function messages() { return \alina\message::returnAllHtmlString(); }

	public function content() {
		//ToDo: This is ridiculous!
		if (!is_string($this->content)) {
			return '';
		}

		return $this->content;
	}
	#endregion HTML page specials (css, js, etc.)
}
