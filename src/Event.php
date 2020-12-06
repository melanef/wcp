<?php

namespace Melanef\Wcp;

use JsonSerializable;

class Event implements JsonSerializable
{
    const FIELD_EVENT = 'event';
    const FIELD_ENTITY = 'entity';
    const FIELD_PARENT_ID = 'parentId';
    const FIELD_ID = 'id';
    const FIELD_PAYLOAD = 'payload';

    const EVENT_CREATED = 'created';
    const EVENT_UPDATED = 'updated';
    const EVENT_REMOVED = 'removed';

    /** @var string */
    public $event;

    /** @var string */
    public $entity;

    /** @var int|string */
    public $parentId;

    /** @var int|string */
    public $id;

    /** @var array|null */
    public $payload;

    /**
     * Event constructor.
     *
     * @param string     $event
     * @param string     $entity
     * @param int|string $parentId
     * @param int|string $id
     * @param array|null $payload
     */
    public function __construct($event, $entity, $parentId, $id, array $payload = null)
    {
        $this->event = $event;
        $this->entity =  $entity;
        $this->parentId = $parentId;
        $this->id = $id;
        $this->payload = $payload;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        $json = [
            self::FIELD_EVENT => $this->event,
            self::FIELD_ENTITY => $this->entity,
            self::FIELD_PARENT_ID => $this->parentId,
            self::FIELD_ID => $this->id,
        ];

        if (!empty($this->payload)) {
            $json[self::FIELD_PAYLOAD] = $this->payload;
        }

        return $json;
    }
}
