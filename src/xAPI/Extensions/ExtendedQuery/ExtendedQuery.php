<?php

namespace API\Extensions\ExtendedQuery;

use API\ExtensionInterface;

class ExtendedQuery implements ExtensionInterface
{
    protected $container;

    public function __construct($container)
    {
        $this->setContainer($container);
    }

	/*
        Format: [['event' => 'statement.get', 'callable' => function(), 'priority' => 1 (optional)], [], ...]
    */
    public function getEventListeners()
    {
    	return [];
    }

    /*
        Format: [['pattern' => '/plus/superstatements', 'callable' => function(), 'methods' => ['GET', 'HEAD']], [], ...]
    */
    public function getRoutes()
    {
    	return [
            ['pattern' => '/plus/statements/find', 'callable' => 'handleGetRoute', 'methods' => ['GET', 'HEAD']],
            ['pattern' => '/plus/statements/find', 'callable' => 'handlePostRoute', 'methods' => ['POST']],
            ['pattern' => '/plus/statements/find', 'callable' => 'handleOptionsRoute', 'methods' => ['OPTIONS']]
        ];
    }

    /*
        Format: [['hook' => 'slim.before.router', 'callable' => function()], [], ...]
    */
    public function getHooks()
    {
    	return [];
    }

    /*
        Installer
    */
    public function install()
    {
        // Do nothing
    }

    protected function getResource()
    {
        $versionString = $this->getContainer()->version->generateClassNamespace();
        $resourceName = __NAMESPACE__.'\\Resource\\' . $versionString . '\\ExtendedQuery';
        $resource = new $resourceName();
        return $resource;
    }

    public function handleGetRoute()
    {
    	$response = $this->getResource()->get();
    	return $response;
    }

    public function handlePostRoute()
    {
        $response = $this->getResource()->post();
        return $response;
    }

    public function handleOptionsRoute()
    {
        $response = $this->getResource()->options();
        return $response;
    }

    /**
     * Gets the value of container.
     *
     * @return mixed
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sets the value of container.
     *
     * @param mixed $container the container
     *
     * @return self
     */
    protected function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }
}