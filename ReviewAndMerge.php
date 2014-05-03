<?php
$wgExtensionCredits['validextensionclass'][] = array(
   'name' => 'ReviewAndMerge',
   'author' =>'ShakePeers', 
   'url' => 'http://shakepeers.org/'
);

require_once 'ReviewAndMerge.class.php';

//$wgGroupPermissions['*']['edit'] = false;
//$wgGroupPermissions['user']['edit'] = false;
$wgHooks['AlternateEdit'][] = 'ReviewAndMerge::checkIfCanEdit';
$wgAutoloadClasses['ReviewAndMerge'] = __DIR__.'/ReviewAndMerge.class.php';
$wgAutoloadClasses['SpecialReviewAndMerge'] = __DIR__.'/SpecialReviewAndMerge.php';
$wgSpecialPages['ReviewAndMerge'] = 'SpecialReviewAndMerge';
$wgExtensionMessagesFiles[ 'ReviewAndMerge' ] = __DIR__ . '/ReviewAndMerge.i18n.php';
?>
