<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Bundle\ContentBundle\Tests\Integration\Form\Type\Property;

use DTL\Bundle\ContentBundle\Form\Type\Content\ResourceLocatorType;
use DTL\Bundle\ContentBundle\Tests\Integration\Form\Type\TypeTestCase;

class ResourceLocatorTypeTest extends TypeTestCase
{
    public function getPropertyAlias()
    {
        return 'resource_locator';
    }
}
