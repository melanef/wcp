<?php

namespace Melanef\Examples\Components;

use Exception;
use Melanef\Examples\Entities\Person;
use Melanef\Examples\Exceptions\NotFoundException;
use Melanef\Examples\Services\FamilyService;
use Melanef\Examples\Services\PersonService;
use Melanef\Wcp\ActionResult;
use Melanef\Wcp\Event;
use Melanef\Wcp\Response;
use Melanef\Wcp\ServerInterface;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;

use function GuzzleHttp\Psr7\parse_query;

class FamilyDomainComponent implements ServerInterface
{
    const SESSION_IDENTIFIER = 'familyId';
    const ROUTE = '/families/{familyId}';

    const ENTITY_FAMILY = 'family';
    const ENTITY_PERSON = 'person';

    const ENTITIES = [
        self::ENTITY_FAMILY,
        self::ENTITY_PERSON,
    ];

    /** @var FamilyService */
    private $familyService;

    /** @var PersonService */
    private $personService;

    /**
     * PersonComponent constructor.
     *
     * @param FamilyService $familyService
     * @param PersonService $personService
     */
    public function __construct(FamilyService $familyService, PersonService $personService)
    {
        $this->familyService = $familyService;
        $this->personService = $personService;
    }

    /**
     * @inheritDoc
     */
    function onOpen(ConnectionInterface $conn)
    {
        // Validate that the WebSocket exists if necessary
        try {
            $family = $this->familyService->findOneById($this->getSessionIdentifier($conn));
        } catch (NotFoundException $exception) {
            // It doesn't, so return a friendly message before closing
            return new ActionResult(Response::failed(sprintf('This family does not exist: %s', $exception->getMessage())));
        }

        // Send a welcoming message
        return new ActionResult(Response::success([
            'message' => sprintf('Welcome to Family %s', $family->surname)
        ]));
    }

    /**
     * @inheritDoc
     */
    function onClose(ConnectionInterface $conn)
    {
    }

    /**
     * @inheritDoc
     */
    function onError(ConnectionInterface $conn, Exception $e)
    {
    }

    /**
     * @inheritDoc
     */
    public function onCreate(ConnectionInterface $conn, $entity, $parentId, array $data)
    {
        // Prepare
        $person = Person::fromRawPayload($data);

        // Save and internal events
        $person = $this->personService->createOne($person);

        // Return Action result with created person and trigger WebSocket event
        return new ActionResult(
            Response::success($person->jsonSerialize()),
            new Event(
                Event::EVENT_CREATED,
                self::ENTITY_PERSON,
                $parentId,
                $person->id,
                $person->jsonSerialize()
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function onRead(ConnectionInterface $conn, $entity, $id)
    {
        try {
            $person = $this->personService->findOneById($id);

            return new ActionResult(Response::success($person->jsonSerialize()));
        } catch (NotFoundException $exception) {
            return new ActionResult(Response::failed($exception->getMessage()));
        }
    }

    /**
     * @inheritDoc
     */
    public function onUpdate(ConnectionInterface $conn, $entity, $id, array $data)
    {
        try {
            // Find existing entity from internal service
            $this->personService->findOneById($id);
        } catch (NotFoundException $exception) {
            return new ActionResult(Response::failed($exception->getMessage()));
        }

        // Prepare with payload
        $person = Person::fromRawPayload($data);

        // Save and internal events
        $this->personService->updateOne($person);

        // Return Action result with updated person and trigger WebSocket event
        return new ActionResult(
            Response::success($person->jsonSerialize()),
            new Event(
                Event::EVENT_UPDATED,
                self::ENTITY_PERSON,
                $person->familyId,
                $person->id,
                $person->jsonSerialize()
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function onDelete(ConnectionInterface $conn, $entity, $id)
    {
        try {
            // Find existing entity from internal service
            $person = $this->personService->findOneById($id);
        } catch (NotFoundException $exception) {
            return new ActionResult(Response::failed($exception->getMessage()));
        }

        $person = $this->personService->deleteOne($person);

        return new ActionResult(
            Response::success(),
            new Event(
                Event::EVENT_REMOVED,
                self::ENTITY_PERSON,
                $person->familyId,
                $person->id
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function onList(ConnectionInterface $conn, $entity, $parentId)
    {
        $people = $this->personService->find();

        return new ActionResult(Response::success($people));
    }

    /**
     * @inheritDoc
     */
    public function getSupportedEntities()
    {
        return self::ENTITIES;
    }

    /**
     * @inheritDoc
     */
    public function getSessionIdentifier(ConnectionInterface $conn)
    {
        if (!(isset($conn->httpRequest) && $conn->httpRequest instanceof RequestInterface)) {
            return null;
        }

        $queryString = $conn->httpRequest->getUri()->getQuery() ?: '';

        $query = parse_query($queryString);

        if (empty($query[self::SESSION_IDENTIFIER])) {
            return null;
        }

        return $query[self::SESSION_IDENTIFIER];
    }
}