<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Tests\Admin;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\BreadcrumbsBuilder;
use Sonata\AdminBundle\Route\RouteGeneratorInterface;
use Sonata\AdminBundle\Translator\LabelTranslatorStrategyInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This test class contains unit and integration tests. Maybe it could be
 * separated into two classes.
 *
 * @author Grégoire Paris <postmaster@greg0ire.fr>
 */
final class BreadcrumbsBuilderTest extends TestCase
{
    public function testChildGetBreadCrumbs(): void
    {
        $action = 'my_action';
        $breadcrumbsBuilder = new BreadcrumbsBuilder(['child_admin_route' => 'show']);
        $admin = $this->createMock(AdminInterface::class);
        $admin->method('isChild')->willReturn(false);

        $admin->method('getMenuFactory')->willReturn(new MenuFactory());
        $labelTranslatorStrategy = $this->createStub(LabelTranslatorStrategyInterface::class);

        $routeGenerator = $this->createMock(RouteGeneratorInterface::class);
        $routeGenerator->method('generate')->with('sonata_admin_dashboard')->willReturn('/dashboard');

        $admin->method('getRouteGenerator')->willReturn($routeGenerator);
        $labelTranslatorStrategy->method('getLabel')->willReturnMap([
            ['my_class_name_list', 'breadcrumb', 'link', 'My class'],
            ['my_child_class_name_list', 'breadcrumb', 'link', 'My child class'],
        ]);

        $subject = new \stdClass();
        $childSubject = new \stdClass();

        $childAdmin = $this->createMock(AdminInterface::class);
        $childAdmin->method('isChild')->willReturn(true);
        $childAdmin->method('getParent')->willReturn($admin);
        $childAdmin->method('getTranslationDomain')->willReturn('ChildBundle');
        $childAdmin->method('getLabelTranslatorStrategy')->willReturn($labelTranslatorStrategy);
        $childAdmin->method('getClassnameLabel')->willReturn('my_child_class_name');
        $childAdmin->method('hasRoute')->with('list')->willReturn(true);
        $childAdmin->method('hasAccess')->with('list')->willReturn(true);
        $childAdmin->method('generateUrl')->with('list')->willReturn('/myadmin/my-object/mychildadmin/list');
        $childAdmin->method('getCurrentChildAdmin')->willReturn(null);
        $childAdmin->method('hasSubject')->willReturn(true);
        $childAdmin->method('getSubject')->willReturn($childSubject);
        $childAdmin->method('toString')->with($childSubject)->willReturn('My subject');

        $admin->method('hasRoute')->willReturnMap([
            ['show', true],
            ['list', true],
            ['edit', true],
        ]);
        $admin->method('hasAccess')->willReturnMap([
            ['show', $subject, true],
            ['list', null, true],
            ['edit', null, true],
        ]);
        $admin->method('generateUrl')->willReturnMap([
            ['show', ['slug' => 'my-object'], UrlGeneratorInterface::ABSOLUTE_PATH, '/myadmin/my-object'],
            ['edit', ['slug' => 'my-object'], UrlGeneratorInterface::ABSOLUTE_PATH, '/myadmin/my-object'],
            ['list', [], UrlGeneratorInterface::ABSOLUTE_PATH, '/myadmin/list'],
        ]);

        $admin->method('getCurrentChildAdmin')->willReturn($childAdmin);
        $request = $this->createMock(Request::class);
        $request->method('get')->with('slug')->willReturn('my-object');

        $admin->method('getIdParameter')->willReturn('slug');
        $admin->method('getRequest')->willReturn($request);
        $admin->method('hasSubject')->willReturn(true);
        $admin->method('getSubject')->willReturn($subject);
        $admin->method('toString')->with($subject)->willReturn('My subject');
        $admin->method('getTranslationDomain')->willReturn('FooBundle');
        $admin->method('getLabelTranslatorStrategy')->willReturn($labelTranslatorStrategy);
        $admin->method('getClassnameLabel')->willReturn('my_class_name');

        $breadcrumbs = $breadcrumbsBuilder->getBreadcrumbs($childAdmin, $action);
        static::assertCount(5, $breadcrumbs);

        [$dashboardMenu, $adminListMenu, $adminSubjectMenu, $childMenu] = $breadcrumbs;

        static::assertSame('link_breadcrumb_dashboard', $dashboardMenu->getName());
        static::assertSame('/dashboard', $dashboardMenu->getUri());
        static::assertSame(
            ['translation_domain' => 'SonataAdminBundle'],
            $dashboardMenu->getExtras()
        );

        static::assertSame('My class', $adminListMenu->getName());
        static::assertSame('/myadmin/list', $adminListMenu->getUri());
        static::assertSame(
            ['translation_domain' => 'FooBundle'],
            $adminListMenu->getExtras()
        );

        static::assertSame('My subject', $adminSubjectMenu->getName());
        static::assertSame('/myadmin/my-object', $adminSubjectMenu->getUri());
        static::assertSame(
            [
                'translation_domain' => false,
                'safe_label' => false,
            ],
            $adminSubjectMenu->getExtras()
        );

        static::assertSame('My child class', $childMenu->getName());
        static::assertSame('/myadmin/my-object/mychildadmin/list', $childMenu->getUri());
        static::assertSame(
            ['translation_domain' => 'ChildBundle'],
            $childMenu->getExtras()
        );
    }

    /**
     * @phpstan-return array<array{string}>
     */
    public function actionProvider(): array
    {
        return [
            ['my_action'],
            ['list'],
            ['edit'],
            ['create'],
        ];
    }

    /**
     * @dataProvider actionProvider
     */
    public function testBuildBreadcrumbs(string $action): void
    {
        $subject = new \stdClass();

        $breadcrumbsBuilder = new BreadcrumbsBuilder();

        $menu = $this->createMock(ItemInterface::class);
        $menuFactory = $this->createMock(MenuFactory::class);
        $menuFactory->method('createItem')->with('root')->willReturn($menu);
        $admin = $this->createMock(AdminInterface::class);
        $admin->method('getMenuFactory')->willReturn($menuFactory);
        $labelTranslatorStrategy = $this->createStub(LabelTranslatorStrategyInterface::class);

        $routeGenerator = $this->createMock(RouteGeneratorInterface::class);
        $routeGenerator->method('generate')->with('sonata_admin_dashboard')->willReturn('/dashboard');
        $admin->method('getRouteGenerator')->willReturn($routeGenerator);

        $menu->method('addChild')->willReturnMap([
            ['link_breadcrumb_dashboard', [
                'uri' => '/dashboard',
                'extras' => ['translation_domain' => 'SonataAdminBundle'],
            ], $menu],
            ['create my object', [
                'extras' => ['translation_domain' => 'FooBundle'],
            ], $menu],
            ['My class', [
                'extras' => ['translation_domain' => 'FooBundle'],
                'uri' => '/myadmin/list',
            ], $menu],
            ['My subject', [
                'extras' => ['translation_domain' => false],
            ], $menu],
            ['My subject', [
                'uri' => null,
                'extras' => ['translation_domain' => false],
            ], $menu],
            ['My child class', [
                'extras' => ['translation_domain' => 'ChildBundle'],
                'uri' => null,
            ], $menu],
            ['My action', [
                'extras' => ['translation_domain' => 'ChildBundle'],
            ], $menu],
        ]);

        $menu->method('setExtra')->with('safe_label', false)->willReturn($menu);

        $labelTranslatorStrategy->method('getLabel')->willReturnMap([
            ['my_class_name_list', 'breadcrumb', 'link', 'My class'],
            ['my_child_class_name_list', 'breadcrumb', 'link', 'My child class'],
            ['my_child_class_name_my_action', 'breadcrumb', 'link', 'My action'],
            ['my_class_name_create', 'breadcrumb', 'link', 'create my object'],
        ]);

        $childAdmin = $this->createMock(AdminInterface::class);
        $childAdmin->method('getTranslationDomain')->willReturn('ChildBundle');
        $childAdmin->method('getLabelTranslatorStrategy')->willReturn($labelTranslatorStrategy);
        $childAdmin->method('getClassnameLabel')->willReturn('my_child_class_name');
        $childAdmin->method('hasRoute')->with('list')->willReturn(false);
        $childAdmin->method('getCurrentChildAdmin')->willReturn(null);
        $childAdmin->method('hasSubject')->willReturn(false);

        $admin->method('hasRoute')->willReturnMap([
            ['list', true],
            ['show', false],
        ]);
        $admin->method('hasAccess')->with('list')->willReturn(true);
        $admin->method('generateUrl')->with('list')->willReturn('/myadmin/list');
        $admin->method('getCurrentChildAdmin')->willReturn('my_action' === $action ? $childAdmin : null);

        if ('list' === $action) {
            $admin->method('isChild')->willReturn(true);
            $menu->expects(static::once())->method('setUri')->with(null);
        } else {
            $menu->expects(static::never())->method('setUri');
        }

        $request = $this->createMock(Request::class);
        $request->method('get')->with('slug')->willReturn('my-object');

        $admin->method('getIdParameter')->willReturn('slug');
        $admin->method('getRequest')->willReturn($request);
        $admin->method('hasSubject')->willReturn(true);
        $admin->method('getSubject')->willReturn($subject);
        $admin->method('toString')->with($subject)->willReturn('My subject');
        $admin->method('getTranslationDomain')->willReturn('FooBundle');
        $admin->method('getLabelTranslatorStrategy')->willReturn($labelTranslatorStrategy);
        $admin->method('getClassnameLabel')->willReturn('my_class_name');

        $breadcrumbsBuilder->buildBreadcrumbs($admin, $action);
    }
}
