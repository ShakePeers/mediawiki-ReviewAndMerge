<?php

class ReviewAndMerge
{
	function checkIfCanEdit($editpage)
	{
		global $wgOut, $wgUser;
		if ($editpage->getContextTitle()->getSubpageText() !== 'Review') { 
			if ($editpage->getArticle()->getText()) {
				$wgOut->redirect(Title::newFromText($editpage->getContextTitle()->getBaseText().'/Review')->getEditURL());
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
