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
     * Generate the Special:ReviewAndMerge page
     *
     * @return void
     * */
    function execute()
    {
        $request = $this->getRequest();
        $output = $this->getOutput();
        $this->setHeaders();
        $html = '';
        $origTitle = Title::newFromText(
            $_GET['article']
        );
        $origPage = new WikiPage(
            $origTitle
        );
        $html .= '<h2>'.$_GET['article'].'</h2>';
        $reviewPage = new WikiPage(
            Title::newFromText(
                $_GET['article'].'/Review'
            )
        );
        $regexp = '/(?<=[.?!])\s+(?=[a-z])/i';
        $origText = $origPage->getText();
        $diff = new Diff(
            preg_split($regexp, $origText),
            preg_split($regexp, $reviewPage->getText())
        );
        if (isset($_POST['nbEdits'])) {
            for ($i = 0; $i <= $_POST['nbEdits']; $i++) {
                if (isset($_POST['keepEdit_'.$i])
                    && $_POST['keepEdit_'.$i] == 'on'
                ) {
                    $origText = str_replace(
                        urldecode($_POST['origEdit_'.$i]),
                        urldecode($_POST['newEdit_'.$i]), $origText
                    );
                }
            }
            var_dump($origText);
            $editpage = new EditPage(new Article($origTitle));
            $editpage->setContextTitle($origTitle);
            $editpage->setPreloadedText($origText);
            var_dump($editpage);
            $editpage->showEditForm();
        } else {
            $html .= '<form action="" method="post"><table class="wikitable">';
            $i = 0;
            foreach ($diff->edits as $edit) {
                $i++;
                if ($edit->type != 'copy') {
                    if ($edit->type == 'add') {
                        $html .= '<tr style="background-color: #ccffcc;">';
                    } else if ($edit->type == 'delete') {
                        $html .= '<tr style="background-color: #ffffaa;">';
                    } else if ($edit->type == 'change') {
                        $html .= '<tr style="background-color: #eeeeee;">';
                    } else {
                        $html .= '<tr>';
                    }
                    $html .= '<td>
                    <input type="hidden" name="origEdit_'.$i.'" value="'.
                        urlencode(implode($edit->orig)).'" />
                    <input type="hidden" name="newEdit_'.$i.'" value="'.
                        urlencode(implode($edit->closing)).'" />
                    <input name="keepEdit_'.$i.'" type="checkbox" checked />
                    </td><td>';
                    foreach ($edit->orig as $orig) {
                        $html .= $orig.'<br/>';
                    }
                    $html .= '</td><td>';
                    foreach ($edit->closing as $closing) {
                        $html .= $closing.'<br/>';
                    }
                    $html .= '</td></tr>';
                }
            }
            $html .='</table>
            <input type="hidden" name="nbEdits" value="'.$i.'" />
            <input type="submit" value="Valider les modifications" />
            </form>';
            $output->addHTML($html);
        }
    }
}
