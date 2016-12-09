<?php

namespace API\Extensions\ExtendedQuery\Service;

use API\Service;
use API\Resource;
use Slim\Helper\Set;

class Statement extends Service
{
    public function statementGet($request)
    {
        $params = new Set($request->get());
        $response = $this->statementQuery($params);
        return $response;
    }

    public function statementPost($request)
    {
        // TODO: Move header validation in a json-schema
        if ($request->getMediaType() !== 'application/json') {
            throw new \Exception('Media type specified in Content-Type header must be \'application/json\'!', Resource::STATUS_BAD_REQUEST);    
        }

        // Validation has been completed already - everyhing is assumed to be valid
        $body = $request->getBody();
        $body = json_decode($body, true);

        // Some clients escape the JSON - handle them
        if (is_string($body)) {
            $body = json_decode($body, true);
        }

        $params = new Set($body);
        $params->replace($request->get());
        $response = $this->statementQuery($params);
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
        // TODO: In future getSlim will be getContainer, replace when applicable!
        $extendedStatementStorage = new $storageClass($this->getSlim());
        $statementResult = $extendedStatementStorage->extendedQuery($parameters);

        return $statementResult;
    }

    protected function resolveStorageClass()
    {
        $storageInUse = $app->config('storage')['in_use'];
        $storageClass = '\\API\\Extensions\\ExtendedQuery\\Storage\\Adapter\\' . $storageInUse . '\\ExtendedStatement';
        if (!class_exists($storageClass)) {
            throw new \InvalidArgumentException('Storage type selected in config is incompatible with ExtendedQuery extension!');
        }
        return $storageClass;
    }
}