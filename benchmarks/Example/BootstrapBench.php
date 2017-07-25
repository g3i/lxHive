<?php
namespace Bench\Example;

use API\Bootstrap;

/**
 * Example Benchmark
 */
class BootstrapBench
{
    /**
    * @Iterations(50)
    */
    public function benchFactoryConfig()
    {
        Bootstrap::factory(Bootstrap::Config);
    }

    /**
    * @Iterations(50)
    */
    public function benchFactoryWeb()
    {
        Bootstrap::factory(Bootstrap::Web);
    }
}
