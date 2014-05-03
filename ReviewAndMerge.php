<?php
/**
 * ReviewAndMerge
 * Review system for MediaWiki inspired by Git pull requests
 * Extension definition
 *
 * PHP version 5.4
 *
 * @category Extension
 * @package  ReviewAndMerge
 * @author   Pierre Rudloff <contact@rudloff.pro>
 * @license  GPL http://www.gnu.org/licenses/gpl.html
 * @link     https://github.com/ShakePeers/mediawiki-ReviewAndMerge
 * */
$wgExtensionCredits['validextensionclass'][] = array(
   'name' => 'ReviewAndMerge',
   'author' =>'ShakePeers', 
   'url' => 'http://shakepeers.org/'
);

require_once 'ReviewAndMerge.class.php';

$wgHooks['AlternateEdit'][] = 'ReviewAndMerge::checkIfCanEdit';
$wgAutoloadClasses['ReviewAndMerge'] = __DIR__.'/ReviewAndMerge.class.php';
$wgAutoloadClasses['SpecialReviewAndMerge'] = __DIR__.'/SpecialReviewAndMerge.php';
$wgSpecialPages['ReviewAndMerge'] = 'SpecialReviewAndMerge';
$wgExtensionMessagesFiles[ 'ReviewAndMerge' ] = __DIR__ . '/ReviewAndMerge.i18n.php';
?>
