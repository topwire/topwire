<?php
declare(strict_types=1);
namespace Topwire\Tests\Functional\Fluid\View\Fixtures\TopwireViewFactoryTest\Controller;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('topwire.controller')]
final class TaggedController extends TemplateController
{
}
