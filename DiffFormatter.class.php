<?php
/**
 * ReviewAndMerge
 * Review system for MediaWiki inspired by Git pull requests
 * Diff formatting classes
 *
 * PHP version 5.4
 *
 * @category Class
 * @package  ReviewAndMerge
 * @author   Pierre Rudloff <contact@rudloff.pro>
 * @license  GPL http://www.gnu.org/licenses/gpl.html
 * @link     https://github.com/ShakePeers/mediawiki-ReviewAndMerge
 * */

/**
 * Modified table diff formatter
 *
 * @category Class
 * @package  ReviewAndMerge
 * @author   Pierre Rudloff <contact@rudloff.pro>
 * @license  GPL http://www.gnu.org/licenses/gpl.html
 * @link     https://github.com/ShakePeers/mediawiki-ReviewAndMerge
 * */
class ReviewAndMergeDiffFormatter extends TableDiffFormatter
{
    /**
     * ReviewAndMergeDiffFormatter constructor
     *
     * @return void
     * */
    function __construct()
    {
        $this->leading_context_lines = 2;
        $this->trailing_context_lines = 2;
        $this->nbEdits = 0;
    }

    /**
     * Add header to diff
     *
     * @param int $xbeg X beginning
     * @param int $xlen X length
     * @param int $ybeg Y beginning
     * @param int $ylen Y length
     *
     * @return string
     */
    function blockHeader( $xbeg, $xlen, $ybeg, $ylen )
    {
        $r = '';
        if ($xbeg > 1) {
            $r .= '</table><table class="diff diff-contentalign-left">
            <colgroup><col class="diff-marker">
            <col class="diff-content">
            <col class="diff-marker">
            <col class="diff-content">
            </colgroup>';
        }
        $r .= '<tr><th colspan="4" class="keepEdit">
            <input type="checkbox" checked
            name="keepEdit_'.$this->nbEdits.'"
            id="keepEdit_'.$this->nbEdits.'" />
            <label for="keepEdit_'.$this->nbEdits.'">'.
            wfMessage('keepedit').'</label>'.wfMessage('colon').'
            </th></tr><tr><td colspan="2" class="diff-lineno">
            <!--LINE ' . $xbeg . "--></td>\n" .
            '<td colspan="2" class="diff-lineno">
            <!--LINE ' . $ybeg . "--></td></tr>\n";
        $this->nbEdits++;
        return $r;
    }
}

/**
 * Unified diff formatter with strict syntax
 *
 * @category Class
 * @package  ReviewAndMerge
 * @author   Pierre Rudloff <contact@rudloff.pro>
 * @license  GPL http://www.gnu.org/licenses/gpl.html
 * @link     https://github.com/ShakePeers/mediawiki-ReviewAndMerge
 * */
class StrictUnifiedDiffFormatter extends UnifiedDiffFormatter
{
    /**
     * Number of leading context lines
     * @var int
     * */
    protected $leadingContextLines = 0;

    /**
     * Number of trailing context lines
     * @var int
     * */
    protected $trailingContextLines = 0;
    /**
     * Add lines to diff
     *
     * @param array  $lines  Lines
     * @param string $prefix Prefix
     *
     * @return void
     */
    function lines( $lines, $prefix = ' ' )
    {
        foreach ( $lines as $line ) {
            echo "$prefix$line\n";
        }
    }
}
?>
