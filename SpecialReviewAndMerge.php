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
        if (isset($_GET['article'])) {
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
            if ($origPage->getOldestRevision()->getUser() == $wgUser->getId()
                || in_array('reviewandmerge', $wgUser->getRights())
            ) {
                $hasRights = true;
            } else {
                $hasRights = false;
            }
            if ($origTitle->mNamespace != $ReviewAndMergeNamespace) {
                $output->showErrorPage('error', 'notenabledfornamespace');
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
                    && $hasRights
                ) {
                    $output->addHTML(
                        wfMessage('pleasecheckchanges').wfMessage('colon')
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
                        = wfMessage('mergedreviewsfrom').' '.$reviewTitle->getFullText();
                    $editpage->textbox1 = self::applyDiff($newDiff, $origText);
                    $editpage->showEditForm();

                } else {
                    $format = new StrictUnifiedDiffFormatter();
                    $nbDiffs = sizeof(self::splitDiff($format->format($diff)));
                    $html .= '<button id="toggleInlineDiff" class="rw_hidden">'.
                        wfMessage('toggleinlinediff').'</button>';
                    if ($hasRights) {
                        $html .= '<form action="" method="post">
                            <input type="hidden" value="'.$nbDiffs.'" name="nbEdits" />';
                    }
                    $html .= '<div class="rw_hidden" id="inlineDiffs"></div>';
                    if ($hasRights) {
                        $html .= '<input type="submit" class="sendDiff rw_hidden"
                            value="'.wfMessage('validchanges').'" /></form>';
                    }
                    if (!$hasRights) {
                        if ($nbDiffs > 0) {
                            $this->getOutput()->addModuleStyles(
                                'mediawiki.action.history.diff'
                            );
                            $diffEngine = new DifferenceEngine();
                            $format = new TableDiffFormatter();
                            $html .= $diffEngine->addHeader(
                                $format->format($diff),
                                '<a href="'.$origTitle->getFullURL().
                                '?oldid='.$origPage->getRevision()->getId().'">'.
                                wfMessage('origversion').'</a>',
                                '<a href="'.$reviewTitle->getFullURL().
                                '?oldid='.$reviewPage->getRevision()->getId().'">'.
                                wfMessage('revversion').'</a>'
                            );
                        } else {
                            $html .= wfMessage('nochangesreview');
                        }
                        $html .= '<br/><i>'.
                            wfMessage('cantmergereviews').'</i>';
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
                                '?oldid='.$origPage->getRevision()->getId().'">'.
                                wfMessage('origversion').'</a>',
                                '<a href="'.$reviewTitle->getFullURL().
                                '?oldid='.$reviewPage->getRevision()->getId().'">'.
                                wfMessage('revversion').'</a>'
                            );
                            $html .= '<input type="submit" class="sendDiff"
                                value="'.wfMessage('validchanges').'" />
                            </form>';
                        } else {
                            $html = '';
                            $output->showErrorPage('error', 'nochangesreview');
                        }
                    }
                    $output->addHTML($html);
                }
            }
        } else {
            $output->showErrorPage('error', 'noarticle');
        }
    }
}
