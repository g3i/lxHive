<?php

namespace API\Extensions\ExtendedQuery;

use API\BaseTrait;
use API\Extensions\ExtensionInterface;

class ExtendedQuery implements ExtensionInterface
{
    use BaseTrait;

    /**
     * [__construct description].
     *
     * @param [type] $container [description]
     */
    public function __construct($container)
    {
        $this->setContainer($container);
    }

    /**
     * Returns any event listeners that need to be added for this extension.
     *
     * @return array Format: [['event' => 'statement.get', 'callable' => function(), 'priority' => 1 (optional)], [], ...]
     */
    public function getEventListeners()
    {
        return [];
    }

    /**
     * Returns any routes that need to be added for this extension.
     *
     * @return array Format: [['pattern' => '/plus/superstatements', 'callable' => function(), 'methods' => ['GET', 'HEAD']], [], ...]
     */
    public function getRoutes()
    {
        return [
            ['pattern' => '/plus/statements/find', 'callable' => 'handleGetRoute', 'methods' => ['GET', 'HEAD']],
            ['pattern' => '/plus/statements/find', 'callable' => 'handlePostRoute', 'methods' => ['POST']],
            ['pattern' => '/plus/statements/find', 'callable' => 'handleOptionsRoute', 'methods' => ['OPTIONS']],
        ];
    }

    /**
     * Returns any hooks that need to be added for this extension.
     *
     * @return array Format: [['hook' => 'slim.before.router', 'callable' => function()], [], ...]
     */
    public function getHooks()
    {
        return [];
    }

    /**
     * Called by extension initializer, does nothing.
     */
    public function install()
    {
    }

    protected function getResource($request, $response)
    {
        $versionString = $this->getContainer()->version->generateClassNamespace();
        $resourceName = __NAMESPACE__.'\\Resource\\'.$versionString.'\\ExtendedQuery';
        $resource = new $resourceName($this->getContainer(), $request, $response);

        return $resource;
    }

    public function handleGetRoute($request, $response, $args)
    {
        $response = $this->getResource($request, $response)->get();

        return $response;
    }

    public function handlePostRoute($request, $response, $args)
    {
        $response = $this->getResource($request, $response)->post();

        return $response;
    }

    public function handleOptionsRoute($request, $response, $args)
    {
        $response = $this->getResource($request, $response)->options();

        return $response;
    }
}
