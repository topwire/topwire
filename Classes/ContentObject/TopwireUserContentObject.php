<?php
namespace Topwire\ContentObject;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\UserContentObject;

class TopwireUserContentObject extends UserContentObject
{
    public const NAME = 'USER';

    /**
     * @param array<mixed> $conf
     * @return string
     */
    public function render($conf = [])
    {
        $contextAndRequestFix = $this->cObj->parentRecord['data']['topwire'] ?? null;
        if ($this->cObj instanceof ContentObjectRenderer && is_array($contextAndRequestFix)) {
            $this->request = $this->request
                ->withAttribute('routing', $contextAndRequestFix['routing'])
                ->withAttribute('topwire', $contextAndRequestFix['context']);
            $this->cObj->setRequest($this->request);
        }
        return parent::render($conf);
    }
}
