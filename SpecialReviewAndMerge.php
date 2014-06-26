<?php
/**
 * ReviewAndMerge
 * Review system for MediaWiki inspired by Git pull requests
 * SpecialReviewAndMerge class
 *
 * PHP version 5.4
 *
 * @category SpecialPage
 * @package  ReviewAndMerge
 * @author   Pierre Rudloff <contact@rudloff.pro>
 * @license  GPL http://www.gnu.org/licenses/gpl.html
 * @link     https://github.com/ShakePeers/mediawiki-ReviewAndMerge
 * */
require_once 'DiffFormatter.class.php';
 /**
  * Class for the Special:ReviewAndMerge page
  *
  * @category SpecialPage
  * @package  ReviewAndMerge
  * @author   Pierre Rudloff <contact@rudloff.pro>
  * @license  GPL http://www.gnu.org/licenses/gpl.html
  * @link     https://github.com/ShakePeers/mediawiki-ReviewAndMerge
  * */
class SpecialReviewAndMerge extends SpecialPage
{
    /**
     * SpecialReviewAndMerge constructor
     *
     * @return void
     * */
    function __construct()
    {
        parent::__construct('ReviewAndMerge');
    }
    
    /**
     * Applies a diff to a string
     * 
     * @param string $diff Diff
     * @param string $text String to patch
     * 
     * @return string Patched string
     * */
    static function applyDiff($diff, $text)
    {
        $tmpname = tempnam(sys_get_temp_dir(), "ReviewAndMerge");
        $tmp = fopen($tmpname, "w");
        fwrite($tmp, $text.PHP_EOL);
        $proc = proc_open(
            'patch '.$tmpname.' -o - ', array(
               0 => array("pipe", "r"),
               1 => array("pipe", "w"),
               2 => array("pipe", "w")
            ), $pipes
        );
        if (is_resource($proc)) {
            fwrite($pipes[0], $diff);
            fclose($pipes[0]);
            $newText = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            return $newText;
        }
    }
    
    /**
     * Uses filterdiff to extract hunks from a diff
     * 
     * @param string $diff        Diff
     * @param array  $num         Indexes of the hunks to extract
     * @param string $origTitle   Original article title
     * @param string $reviewTitle Review article title
     * 
     * @return string Diff
     * */
    static function filterDiff($diff, $num, $origTitle, $reviewTitle)
    {
        $proc = proc_open(
            __DIR__.'/filterdiff -# '.implode(',', $num), array(
               0 => array("pipe", "r"),
               1 => array("pipe", "w")
            ), $pipes
        );
        if (is_resource($proc)) {
            fwrite(
                $pipes[0], 
                '--- '.$origTitle.PHP_EOL.
                '+++ '.$reviewTitle.PHP_EOL.
                $diff
            );
            fclose($pipes[0]);
            $newDiff = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            return $newDiff;
        }
    }
    
    /**
     * Splits a diff in several hunks
     * 
     * @param string $diff Diff
     * 
     * @return array Diffs
     * */
    static function splitDiff($diff)
    {
        $matches = preg_split(
            '/@@(.*)@@/', $diff,
            null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY
        );
        $isHeader = true;
        $diffs = array();
        $i = 0;
        foreach ($matches as $match) {
            if ($isHeader) {
                $diffs[$i] = '@@'.$match.'@@';
            } else {
                $diffs[$i] .= $match;
                $i++;
            }
            $isHeader = !$isHeader;
        }
        return $diffs;
    }

    /**
     * Generate the Special:ReviewAndMerge page
     *
     * @param void $subpage Not used here
     *
     * @return void
     * */
    function execute($subpage)
    {
        global $wgContLang, $wgUser, $ReviewAndMergeNamespace;
        $request = $this->getRequest();
        $output = $this->getOutput();
        $this->setHeaders();
        $html = '';
        $origTitle = Title::newFromText(
            $_GET['article']
        );
        if ($origTitle->getSubpageText() == 'Review') {
            $origTitle = Title::newFromText(
                $origTitle->getNsText().':'.$origTitle->getBaseText()
            );
        }
        $origPage = new WikiPage(
            $origTitle
        );
        if ($origTitle->mNamespace != $ReviewAndMergeNamespace) {
            $html .= 'Review and Merge is not enabled for this namespace.';
            $output->addHTML($html);
        } else {
            $html .= '<h2><a href="'.$origTitle->getFullURL().'">'.
                $origTitle->getFullText().'</a></h2>';
            if ($origPage->getOldestRevision()->getUser() == $wgUser->getId()) {
                $reviewTitle = Title::newFromText(
                    $origTitle->getFullText().'/Review'
                );
                $reviewPage = new WikiPage(
                    $reviewTitle
                );
                $regexp = '/(?<=[.?!])\s+(?=[a-z])/i';
                $origText = $origPage->getText();

                $diff = new Diff(
                    explode(PHP_EOL, $wgContLang->segmentForDiff($origText)),
                    explode(
                        PHP_EOL,
                        $wgContLang->segmentForDiff($reviewPage->getText())
                    )
                );
                if (isset($_POST['nbEdits'])) {
                    $output->addHTML(
                        'Please check that changes have been applied correctly:'
                    );
                    $edits = array();
                    for ($i = 0; $i <= $_POST['nbEdits']; $i++) {
                        if (isset($_POST['keepEdit_'.$i])
                            && $_POST['keepEdit_'.$i] == 'on'
                        ) {
                            $edits[] = $i + 1;
                        }
                    }
                    if (empty($edits)) {
                        $output->redirect($reviewTitle);
                        return;
                    }
                    $format = new StrictUnifiedDiffFormatter();
                    $newDiff = self::filterDiff(
                        $format->format($diff), $edits,
                        $origTitle, $reviewTitle
                    );
                    $editpage = new EditPage(new Article($origTitle));
                    $editpage->setContextTitle($origTitle);
                    $editpage->initialiseForm();
                    $editpage->summary
                        = 'Merged reviews from '.$reviewTitle->getFullText();
                    $editpage->textbox1 = self::applyDiff($newDiff, $origText);
                    $editpage->showEditForm();

                } else {
                    $format = new UnifiedDiffFormatter();
                    $nbDiffs = sizeof(self::splitDiff($format->format($diff)));
                    if ($nbDiffs > 0) {
                        $html .= '<form action="" method="post">
                            <input type="hidden"
                                value="'.$nbDiffs.'" name="nbEdits" />';
                        $this->getOutput()->addModuleStyles(
                            'mediawiki.action.history.diff'
                        );
                        $diffEngine = new DifferenceEngine();
                        $format = new ReviewAndMergeDiffFormatter();
                        $html .= $diffEngine->addHeader(
                            $format->format($diff),
                            'Original version', 'Reviewed version'
                        );
                        $html .= '<input type="submit" value="Validate changes" />
                        </form>';
                    } else {
                        $html .= 'No changes to review';
                    }
                    $output->addHTML($html);
                }
            } else {
                $html .= 'Only the author of an article can merge reviews.';
                $output->addHTML($html);
            }
        }
    }
}
