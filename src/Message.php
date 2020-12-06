<?php

namespace Melanef\Wcp;

use InvalidArgumentException;

class Message
{
    const FIELD_ACTION = 'action';
    const FIELD_ENTITY = 'entity';
    const FIELD_PARENT_ID = 'parentId';
    const FIELD_ID = 'id';
    const FIELD_PAYLOAD = 'payload';

    const ACTION_CREATE = 'create';
    const ACTION_READ = 'read';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_LIST = 'list';

    const ACTIONS = [
        self::ACTION_CREATE,
        self::ACTION_READ,
        self::ACTION_UPDATE,
        self::ACTION_DELETE,
        self::ACTION_LIST,
    ];

    /** @var string */
    public $action;

    /** @var string */
    public $entity;

    /** @var int */
    public $parentId;

    /** @var int */
    public $id;

    /** @var array */
    public $payload;

    /**
     * @param string $message
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromJsonString($message)
    {
        if (null === ($json = @json_decode($message, true))) {
            throw new InvalidArgumentException('JSON is invalid');
        }

        $message = new self();
        self::assertFieldExists($json, self::FIELD_ACTION);
        $message->action = $json[self::FIELD_ACTION];

        self::assertFieldExists($json, self::FIELD_ENTITY);
        $message->entity = $json[self::FIELD_ENTITY];

        if (in_array($message->action, [self::ACTION_CREATE, self::ACTION_LIST])) {
            self::assertFieldExists($json, self::FIELD_PARENT_ID);
            $message->parentId = $json[self::FIELD_PARENT_ID];
        }

        if (in_array($message->action, [self::ACTION_READ, self::ACTION_UPDATE, self::ACTION_DELETE])) {
            self::assertFieldExists($json, self::FIELD_ID);
            $message->id = $json[self::FIELD_ID];
        }

        if (in_array($message->action, [self::ACTION_CREATE, self::ACTION_UPDATE])) {
            self::assertFieldExists($json, self::FIELD_PAYLOAD);
            $message->payload = $json[self::FIELD_PAYLOAD];
        }

        return $message;
    }

    /**
     * @param array  $data
     * @param string $field
     *
     * @throws InvalidArgumentException
     */
    private static function assertFieldExists(array $data, $field)
    {
        if (!array_key_exists($field, $data)) {
            throw new InvalidArgumentException(sprintf(
                'Input for WCP is invalid. "%s" field missing',
                $field
            ));
        }
    }
}
