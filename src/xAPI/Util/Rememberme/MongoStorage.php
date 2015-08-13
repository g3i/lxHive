<?php

namespace API\Util\Rememberme;

use Birke\Rememberme\Storage\StorageInterface;

/**
 * Sokil/Mongo-Based Storage
 */
class MongoStorage implements StorageInterface
{
    /**
     * @var \Sokil\Mongo\Client
     */
    protected $documentManager;

    /**
     * @param \Sokil\Mongo\Client $documentManager
     * @param string $suffix
     */
    public function __construct(\Sokil\Mongo\Client $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * @param mixed $credential
     * @param string $token
     * @param string $persistentToken
     * @return int
     */
    public function findTriplet($credential, $token, $persistentToken)
    {
        // Hash the tokens, because they can contain a salt and can be accessed in the file system
        $persistentToken = sha1($persistentToken);
        $token = sha1($token);

        $collection  = $this->getDocumentManager()->getCollection('persistentSessions');
        $cursor      = $collection->find();

        $cursor->where('credential', $credential);
        $cursor->where('persistentToken', $persistentToken);

        $document = $cursor->current();

        if (null === $document) {
            return self::TRIPLET_NOT_FOUND;
        }

        $documentToken = $document->getToken();;

        if ($documentToken == $token) {
            return self::TRIPLET_FOUND;
        }

        return self::TRIPLET_INVALID;
    }

    /**
     * @param mixed $credential
     * @param string $token
     * @param string $persistentToken
     * @param int $expire
     * @return $this
     */
    public function storeTriplet($credential, $token, $persistentToken, $expire = 0)
    {
        // Hash the tokens, because they can contain a salt and can be accessed in the file system
        $persistentToken = sha1($persistentToken);
        $token = sha1($token);

        $collection  = $this->getDocumentManager()->getCollection('persistentSessions');

        $sessionDocument = $collection->createDocument();

        $sessionDocument->setCredential($credential);
        $sessionDocument->setToken($token);
        $sessionDocument->setPersistentToken($per);

        $sessionDocument->save();

        return $this;
    }

    /**
     * @param mixed $credential
     * @param string $persistentToken
     */
    public function cleanTriplet($credential, $persistentToken)
    {
        $persistentToken = sha1($persistentToken);
        $collection = $this->getDocumentManager()->getCollection('persistentSessions');
        $cursor = $collection->find();

        $cursor->where('credential', $credential);
        $cursor->where('persistentToken', $persistentToken);

        $result = $cursor->findOne();

        if ($result) {
            $result->delete();
        }
    }

    /**
     * Replace current token after successful authentication
     * @param $credential
     * @param $token
     * @param $persistentToken
     * @param int $expire
     */
    public function replaceTriplet($credential, $token, $persistentToken, $expire = 0)
    {
        $this->cleanTriplet($credential, $persistentToken);
        $this->storeTriplet($credential, $token, $persistentToken, $expire);
    }

    /**
     * @param $credential
     */
    public function cleanAllTriplets($credential)
    {
        $collection  = $this->getDocumentManager()->getCollection('persistentSessions');

        $expression = $collection->expression();
        $expression->where('credential', $credential);

        $collection->deleteDocuments($expression);
    }

    /**
     * Gets the value of documentManager.
     *
     * @return \Sokil\Mongo\Client
     */
    public function getDocumentManager()
    {
        return $this->documentManager;
    }

    /**
     * Sets the value of documentManager.
     *
     * @param \Sokil\Mongo\Client $documentManager the document manager
     *
     * @return self
     */
    public function setDocumentManager(\Sokil\Mongo\Client $documentManager)
    {
        $this->documentManager = $documentManager;

        return $this;
    }
}