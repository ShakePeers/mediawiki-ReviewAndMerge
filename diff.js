/*global $, WDiffString*/
/*jslint browser: true*/
var wDiffStyleDelete = 'font-weight: bold; background-color: #feeec8;';
var wDiffStyleInsert = 'font-weight: bold; background-color: #d8ecff;';
var wDiffShowBlockMoves = false;
var wDiffWordDiff = false;
$(window).on('load', function () {
    'use strict';
    $('table.diff').each(function (i, item) {
        var orig = '', review = '', newHTML = '';
        $(item).find('.diff-deletedline, .diff-context:nth-child(2)').each(function (i, block) {
            orig += $(block).text() + '\n';
        });
        $(item).find('.diff-addedline, .diff-context:nth-child(4)').each(function (i, block) {
            review += $(block).text() + '\n';
        });
        if ($(item).find('.keepEdit').length > 0) {
            newHTML += '<div>' + $(item).find('.keepEdit').html() + '</div>';
        }
        newHTML += '<table><tr><td class="diff-context">' + WDiffString(orig, review) + '</td></tr></table>';
        $('#inlineDiffs').append(newHTML);
    });
    $('#toggleInlineDiff').show().click(function () {
        $('#inlineDiffs, .sendDiff, table.diff').toggle();
    });
});
