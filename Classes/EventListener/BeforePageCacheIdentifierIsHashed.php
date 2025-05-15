<?php
declare(strict_types = 1);

namespace Topwire\EventListener;
use Topwire\Context\TopwireContext;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\BeforePageCacheIdentifierIsHashedEvent;

#[AsEventListener(
    identifier: 'TopwireBeforePageCacheIdentifierIsHashed',
    event: BeforePageCacheIdentifierIsHashedEvent::class,
)]
class BeforePageCacheIdentifierIsHashed {
    public function __invoke(BeforePageCacheIdentifierIsHashedEvent $event): void
    {
        $topwireContext = $event->getRequest()->getAttribute('topwire');
        if (!$topwireContext instanceof TopwireContext) {
            return;
        }
        $cacheIdentifiers = $event->getPageCacheIdentifierParameters();
        $cacheIdentifiers['topwire'] = $topwireContext->cacheId;
        $event->setPageCacheIdentifierParameters($cacheIdentifiers);
    }
}
