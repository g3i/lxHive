<?php

namespace API\Extensions\ExtendedQuery\Service;

use API\Service;
use API\Resource;
use Slim\Helper\Set;
use API\Config;

class Statement extends Service
{
    public function statementGet()
    {
        $parameters = $this->getContainer()['parser']->getData()->getParameters();

        $response = $this->statementQuery($parameters);

        return $response;
    }

    public function statementPost()
    {
        // TODO: Move header validation in a json-schema
        /*if ($request->getMediaType() !== 'application/json') {
            throw new \Exception('Media type specified in Content-Type header must be \'application/json\'!', Resource::STATUS_BAD_REQUEST);
        }*/

        // Validation has been completed already - everyhing is assumed to be valid
        $parameters = $this->getContainer()['parser']->getData()->getParameters();
        $bodyParams = $this->getContainer()['parser']->getData()->getPayload();

        $allParams = array_merge($parameters, $bodyParams);
        $response = $this->statementQuery($parameters);

        return $response;
    }

    /**
     * Fetches statements according to the given parameters.
     *
     * @param Set $params The params given by the request.
     *
     * @return array An array of statement objects.
     */
    protected function statementQuery($parameters)
    {
        $storageClass = $this->resolveStorageClass();
        $extendedStatementStorage = new $storageClass($this->getContainer());
        $statementResult = $extendedStatementStorage->extendedQuery($parameters);

        return $statementResult;
    }

    protected function resolveStorageClass()
    {
        $storageInUse = Config::get(['storage', 'in_use']);
        $storageClass = '\\API\\Extensions\\ExtendedQuery\\Storage\\Adapter\\'.$storageInUse.'\\ExtendedStatement';
        if (!class_exists($storageClass)) {
            throw new \InvalidArgumentException('Storage type selected in config is incompatible with ExtendedQuery extension!');
        }

        return $storageClass;
    }
}
