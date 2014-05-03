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
    function checkIfCanEdit($editpage)
    {
        global $wgOut, $wgUser;
        if ($editpage->getContextTitle()->getSubpageText() !== 'Review') {
            if ($editpage->getArticle()->getText()) {
                $wgOut->redirect(
                    Title::newFromText(
                        $editpage->getContextTitle()->getBaseText().'/Review'
                    )->getEditURL()
                );
            }
        } else {
            $origPage = new WikiPage(
                Title::newFromText(
                    $editpage->getContextTitle()->getBaseText()
                )
            );
            $editpage->setPreloadedText($origPage->getText());
        }
    }
}

?>
