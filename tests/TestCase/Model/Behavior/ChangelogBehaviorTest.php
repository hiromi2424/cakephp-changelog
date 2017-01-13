<?php
namespace SlackLogEngine\Test\TestCase\Model\Behavior;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Exception;

use Changelog\Model\Behavior\ChangelogBehavior;

class TestException extends Exception
{
}

class ChangelogBehaviorTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Changelog\Model\Behavior\ChangelogBehavior
     */
    public $Bahavior;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    }
    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test initiakize method
     *
     * @test
     */
    public function initialize()
    {
    }
}
