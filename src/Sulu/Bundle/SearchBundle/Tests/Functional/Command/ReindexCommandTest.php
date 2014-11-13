<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Functional\Command;

use Sulu\Bundle\SearchBundle\Command\ReindexCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Sulu\Bundle\SearchBundle\Tests\Functional\BaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class ReindexCommandTest extends BaseTestCase
{
    public function testCommand()
    {
        $this->indexStructure('Hello', '/hello');
        $this->indexStructure('Goodbye', '/goodbye');
        $this->indexStructure('Auf wiedersehen', '/aufwiedersehen');
        $this->indexStructure('Aurevoir', '/aurevoir', false);

        $kernel = $this->getContainer()->get('kernel');
        $application = new Application($kernel);
        $application->add(new ReindexCommand());

        $command = $application->find('sulu:search:reindex');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array());

        $out = $commandTester->getDisplay();

        $this->assertContains('Indexing published', $out);
        $this->assertContains('De-indexing', $out);
    }
}
