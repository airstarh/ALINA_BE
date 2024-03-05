<?php

$internalErrors = libxml_use_internal_errors(true);
####################################################################################################

$aString = file_get_contents(__DIR__ . '/mockups/astring.html');
$dom     = new DOMDocument('1.0', 'UTF-8');
$dom->loadHTML($aString);

echo '<pre>';
foreach($dom->childNodes as $k=>$v){
    echo '<br>';
    echo '#####';
    echo '<br>';
    echo $k;
    echo '<br>';
    var_dump($v->nodeValue);
}



####################################################################################################
libxml_use_internal_errors($internalErrors);


####################################################################################################
class MyDom
{
    private function _preg_replace_dom($regex, $domDocument, $replacement, DOMNode $dom, array $excludeParents = array())
    {

        if (!empty($dom->childNodes)) {
            $replacementNodes = array();
            foreach ($dom->childNodes as $node) {
                if ($node instanceof DOMText && !in_array($node->parentNode->nodeName, $excludeParents)) {
                    //$node->nodeValue = preg_replace($regex, $replacement, $node->nodeValue);
                    $replacementHtml = preg_replace($regex, $replacement, $node->nodeValue);

                    $linkReplacement = $domDocument->createDocumentFragment();
                    $linkReplacement->appendXML($replacementHtml);

                    $tmpreplacement          = new stdClass();
                    $tmpreplacement->oldNode = $node;
                    $tmpreplacement->newNode = $linkReplacement;
                    $replacementNodes[]      = $tmpreplacement;
                } else {
                    $this->_preg_replace_dom($regex, $domDocument, $replacement, $node, $excludeParents);
                }
            }

            foreach ($replacementNodes as $replacement) {
                $dom->replaceChild($replacement->newNode, $replacement->oldNode);
            }
        }
    }
####################################################################################################
}