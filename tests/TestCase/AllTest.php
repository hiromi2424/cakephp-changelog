<?php
namespace Changelog\Test\TestSuite;

use Cake\Core\Plugin;
use Cake\TestSuite\TestSuite;
use PHPUnit\Framework\TestSuite as BaseTestSuite;

class AllTest extends BaseTestSuite
{
    public static function suite()
    {
        $suite = new TestSuite('All Changelog plugin tests');
        $path = Plugin::path('Changelog');
        $testPath = $path . DS . 'tests' . DS . 'TestCase';
        if (!is_dir($testPath)) {
            return $suite;
        }
        $suite->addTestDirectoryRecursive($testPath);

        return $suite;
    }
}
