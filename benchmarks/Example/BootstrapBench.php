<?php
namespace Bench\Example;

use API\Bootstrap;

/**
 * Example Benchmark
 */
class BootstrapBench
{
    /**
    * @Revs(1)
    * @Iterations(50)
    */
    public function benchFactoryNone()
    {
        Bootstrap::factory(Bootstrap::None);
    }

    /**
    * @Revs(1)
    * @Iterations(50)
    */
    public function benchFactoryConfig()
    {
        Bootstrap::factory(Bootstrap::Config);
    }

    /**
    * @Revs(1)
    * @Iterations(50)
    */
    public function benchFactoryWeb()
    {
        Bootstrap::factory(Bootstrap::Web);
    }
}
