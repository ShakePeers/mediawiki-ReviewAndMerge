<?php
/**
 * ReviewAndMerge
 * Review system for MediaWiki inspired by Git pull requests
 * ReviewAndMerge class
 *
 * PHP version 5.4
 *
 * @category SpecialPage
 * @package  ReviewAndMerge
 * @author   Pierre Rudloff <contact@rudloff.pro>
 * @license  GPL http://www.gnu.org/licenses/gpl.html
 * @link     https://github.com/ShakePeers/mediawiki-ReviewAndMerge
 * */
 /**
  * Main ReviewAndMerge class
  *
  * @category Class
  * @package  ReviewAndMerge
  * @author   Pierre Rudloff <contact@rudloff.pro>
  * @license  GPL http://www.gnu.org/licenses/gpl.html
  * @link     https://github.com/ShakePeers/mediawiki-ReviewAndMerge
  * */
class ReviewAndMerge
{
    /**
     * Check if a page is in the Review namespace
     * and if so redirects to the /Review subpage
     *
     * @param EditPage $editpage Page to edit
     *
     * @return void
     * */
    static function checkIfCanEdit($editpage)
    {
        global $wgOut, $wgUser, $ReviewAndMergeNamespace, $wgExtraNamespaces;
        $contextTitle = $editpage->getContextTitle();
        if ($contextTitle->mNamespace == $ReviewAndMergeNamespace
            && !isset($_POST['wpPreview'])
            && !isset($_POST['wpDiff'])
            && !isset($_POST['wpSave'])
        ) {
            if ($editpage->getContextTitle()->getSubpageText() !== 'Review') {
                if ($editpage->getArticle()->getText()) {
                    $wgOut->redirect(
                        Title::newFromText(
                            $contextTitle->getPrefixedText().'/Review'
                        )->getEditURL()
                    );
                }
            } else {
                $origPage = new WikiPage(
                    Title::newFromText(
                        $contextTitle->getNsText().':'.$contextTitle->getBaseText()
                    )
                );
                $editpage->setPreloadedContent(
                    new WikiTextContent($origPage->getText())
                );
            }
        }
    }

    /**
     * Add CSS and JavaScript to <head>
     *
     * @param OutputPage $out Output page
     *
     * @return void
     * */
    static function appendHeader(&$out)
    {
        global $wgScriptPath;
        $out->addScriptFile($wgScriptPath.'/extensions/ReviewAndMerge/diff.js');
        $out->addScriptFile($wgScriptPath.'/extensions/ReviewAndMerge/wDiff.js');
        $out->addStyle($wgScriptPath.'/extensions/ReviewAndMerge/style.css');
    }
}

?>
