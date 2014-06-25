<?php
class ReviewAndMergeDiffFormatter extends TableDiffFormatter {
    function __construct() {
        $this->leading_context_lines = 2;
        $this->trailing_context_lines = 2;
        $this->nbEdits = 0;
    }

    /**
     * @param $xbeg
     * @param $xlen
     * @param $ybeg
     * @param $ylen
     * @return string
     */
    function _block_header( $xbeg, $xlen, $ybeg, $ylen ) {
        $r = '<tr><th colspan="4"><input type="checkbox" checked name="keepEdit_'.$this->nbEdits.'" id="keepEdit_'.$this->nbEdits.'" /> <label for="keepEdit_'.$this->nbEdits.'">Keep this edit</label>:</th></tr><tr><td colspan="2" class="diff-lineno"><!--LINE ' . $xbeg . "--></td>\n" .
            '<td colspan="2" class="diff-lineno"><!--LINE ' . $ybeg . "--></td></tr>\n";
        $this->nbEdits++;
        return $r;
    }
}

class StrictUnifiedDiffFormatter extends UnifiedDiffFormatter {
    function _lines( $lines, $prefix = ' ' ) {
        foreach ( $lines as $line ) {
            echo "$prefix$line\n";
        }
    }
}
?>
