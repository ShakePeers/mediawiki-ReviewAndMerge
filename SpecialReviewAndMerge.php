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
        $outname = tempnam(sys_get_temp_dir(), "ReviewAndMerge");
        $tmp = fopen($tmpname, "w");
        $out = fopen($outname, "r");
        fwrite($tmp, $text.PHP_EOL);
        $proc = proc_open(
            'patch '.$tmpname.' -o '.$outname, array(
               0 => array("pipe", "r"),
               1 => array("pipe", "w"),
               2 => array("pipe", "w")
            ), $pipes
        );
        if (is_resource($proc)) {
            fwrite($pipes[0], $diff);
            fclose($pipes[0]);
            stream_get_contents($pipes[1]);
            $newText = stream_get_contents($out);
            fclose($pipes[1]);
            return $newText;
        }
    }
    
    /**
     * Extract hunks from a diff
     * 
     * @param string $diff Diff
     * @param array  $num  Indexes of the hunks to extract
     * 
     * @return string Diff
     * */
    static function filterDiff($diff, $num)
    {
        $split = self::splitDiff($diff);
        $newDiff = '';
        foreach ($num as $hunk) {
            $newDiff .= $split[$hunk - 1];
        }
        return $newDiff;
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
            if (isset($_POST['nbEdits'])
                && $origPage->getOldestRevision()->getUser() == $wgUser->getId()
            ) {
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
                    $format->format($diff), $edits
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
                $html .= '<button id="toggleInlineDiff" class="hidden">
                    Toggle inline diff</button>';
                if ($origPage->getOldestRevision()->getUser() == $wgUser->getId()) {
                    $html .= '<form action="" method="post">
                        <input type="hidden" value="'.$nbDiffs.'" name="nbEdits" />';
                }
                $html .= '<div class="hidden" id="inlineDiffs"></div>';
                if ($origPage->getOldestRevision()->getUser() == $wgUser->getId()) {
                    $html .= '<input type="submit" class="sendDiff hidden"
                        value="Validate changes" /></form>';
                }
                if ($origPage->getOldestRevision()->getUser() != $wgUser->getId()) {
                    if ($nbDiffs > 0) {
                        $this->getOutput()->addModuleStyles(
                            'mediawiki.action.history.diff'
                        );
                        $diffEngine = new DifferenceEngine();
                        $format = new TableDiffFormatter();
                        $html .= $diffEngine->addHeader(
                            $format->format($diff),
                            '<a href="'.$origTitle->getFullURL().
                            '?oldid='.$origPage->getRevision()->getId().'">
                            Original version</a>',
                            '<a href="'.$reviewTitle->getFullURL().
                            '?oldid='.$reviewPage->getRevision()->getId().'">
                            Reviewed version</a>'
                        );
                    } else {
                        $html .= 'No changes to review';
                    }
                    $html .= '<br/><i>
                        Only the author of an article can merge reviews.</i>';
                } else {
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
                            '<a href="'.$origTitle->getFullURL().
                            '?oldid='.$origPage->getRevision()->getId().'">
                            Original version</a>',
                            '<a href="'.$reviewTitle->getFullURL().
                            '?oldid='.$reviewPage->getRevision()->getId().'">
                            Reviewed version</a>'
                        );
                        $html .= '<input type="submit" class="sendDiff"
                            value="Validate changes" />
                        </form>';
                    } else {
                        $html .= 'No changes to review';
                    }
                }
                $output->addHTML($html);
            }
        }
    }
}
