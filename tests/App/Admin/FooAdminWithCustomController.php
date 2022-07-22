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

namespace Sonata\AdminBundle\Tests\App\Admin;

final class FooAdminWithCustomController extends FooAdmin
{
    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'tests/app/foo-with-custom-controller';
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'admin_foo_with_custom_controller';
    }
}
