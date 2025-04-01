<?php
declare(strict_types=1);

namespace Topwire\EventListener;

use Topwire\ContentObject\TopwireContentObject;
use Topwire\Context\TopwireContext;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Event\ModifyTypoScriptConfigEvent;

/**
 * THIS MUST BE REMOVED AGAIN AS IT IS A HACK AND IT
 * DOES NOT WORK PROPERLY WITH TYPOSCRIPT CACHING
 * SEE WORKAROUND IN ext_localconf.php
 */

#[AsEventListener(
    identifier: 'TopwireRenderContentElementByContext',
    event: ModifyTypoScriptConfigEvent::class,
)]
class TopwireRenderContentElementByContext
{
    private const turboStreamContentType = 'text/vnd.turbo-stream.html';

    public function __invoke(ModifyTypoScriptConfigEvent $event): void
    {
        $request = $event->getRequest();

        $context = $request->getAttribute('topwire');

        if (!$context instanceof TopwireContext) {
            return;
        }

        $routing = $request->getAttribute('routing');
        if (!$routing instanceof PageArguments) {
            return;
        }

        $pageType = $routing->getPageType();
        if ($pageType === '') {
            return;
        }

        $pageAstNode = $this->getPageByTypeNum($event->getSetupTree(), $pageType);
        if ($pageAstNode === null) {
            return;
        }

        // This is super dirty and hacky! It is not possible to modify the "setup" in this event
        // but only the "config" part. But as the PHP AST is using objects and objects are passed by reference
        // in php we can modify the setup part by using these references.
        // So we can partially modify the setup part here. This is a hack and should be replaced by a proper
        // event in the future once TYPO3 has a proper event for this.
        $firstChildNode = null;
        foreach ($pageAstNode->getNextChild() as $childNode) {
            // We can't remove the node itself as we need to keep the object reference
            // so we remove all children instead
            foreach ($childNode->getNextChild() as $childChildNode) {
                $childNode->removeChildByName($childChildNode->getName());
            }

            if (MathUtility::canBeInterpretedAsInteger($childNode->getName()) && $firstChildNode === null) {
                // Keep the first content node as we modify it by reference
                $firstChildNode = $childNode;
                continue;
            }

            // Remove the content of the node to it does not do anything
            $childNode->setValue('');
            $childNode->updateName('');
        }

        if ($firstChildNode === null) {
            // We can't do anything here as we require at least one content node that
            // we can modify by reference
            return;
        }

        $firstChildNode->setValue(TopwireContentObject::NAME);
        $topwireContextNode = new ChildNode('context');
        $topwireContextNode->setValue($context->toHashedString());
        $firstChildNode->addChild(
            $topwireContextNode
        );

        $config = $event->getConfigTree();

        $debugNode = new ChildNode('debug');
        $debugNode->setValue('0');

        $disableAllHeaderCodeNode = new ChildNode('disableAllHeaderCode');
        $disableAllHeaderCodeNode->setValue('1');

        $disableCharsetHeaderNode = new ChildNode('disableCharsetHeader');
        $disableCharsetHeaderNode->setValue('0');

        $config->addChild($debugNode);
        $config->addChild($disableAllHeaderCodeNode);
        $config->addChild($disableCharsetHeaderNode);

        // Not required due to object reference?
        $event->setConfigTree($config);
    }

    /**
     * Filter the PAGE object by typeNum to get the correct one.
     * This is necessary in TYPO3 v13 but will maybe change in v14
     */
    private function getPageByTypeNum(RootNode $setupAst, string $pageType)
    {
        if ((new Typo3Version())->getMajorVersion() >= 14) {
            trigger_deprecation('Check if filtering for PAGE objects is still required. See FrontendTypoScriptFactory::createSetupConfigOrFullSetup()', E_USER_WARNING);
        }
        $rawSetupPageNodeFromType = null;
        $pageNodeFoundByType = false;
        foreach ($setupAst->getNextChild() as $potentialPageNode) {
            // Find the PAGE object that matches given type/typeNum
            if ($potentialPageNode->getValue() === 'PAGE') {
                $typeNumChild = $potentialPageNode->getChildByName('typeNum');
                if ($typeNumChild !== null && $pageType === $typeNumChild->getValue()) {
                    $rawSetupPageNodeFromType = $potentialPageNode;
                    $pageNodeFoundByType = true;
                    break;
                }
                if ($typeNumChild === null && $pageType === '0') {
                    // The first PAGE node that has no typeNum is considered '0' automatically.
                    $rawSetupPageNodeFromType = $potentialPageNode;
                    $pageNodeFoundByType = true;
                    break;
                }
            }
        }

        if (!$pageNodeFoundByType) {
            return null;
        }

        return $rawSetupPageNodeFromType;
    }
}
