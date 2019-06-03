<?php /** @copyright Pley (c) 2014, All Rights Reserved */

/** @author Alejandro Salazar (alejandros@pley.com) */
class TestCaseNoDB extends Illuminate\Foundation\Testing\TestCase
{

    /** Default preparation for each test */
    public function setUp()
    {
        parent::setUp();

        Mail::pretend(true);
    }

    /**
     * Creates the application.
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        // Variables used when bootstrap calls /Illuminate/Foundation/start.php::75-78
        // if (isset($unitTesting))
        // {
        //     $app['env'] = $env = $testEnvironment;
        // }
        $unitTesting     = true;
        $testEnvironment = 'testing';

        return require __DIR__ . '/../../bootstrap/start.php';
    }

}
