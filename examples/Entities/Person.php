<?php

namespace Melanef\Examples\Entities;

use JsonSerializable;

class Person implements JsonSerializable
{
    const FIELD_ID = 'id';
    const FIELD_NAME = 'name';
    const FIELD_FAMILY_ID = 'familyId';

    /** @var int */
    public $id;

    /** @var string */
    public $name;

    /** @var int */
    public $familyId;

    /**
     * @param array $data
     *
     * @return static
     */
    public static function fromRawPayload(array $data)
    {
        $self = new static();

        if (array_key_exists(self::FIELD_ID, $data)) {
            $self->id = $data[self::FIELD_ID];
        }

        $self->name = $data[self::FIELD_NAME];
        $self->familyId = $data[self::FIELD_FAMILY_ID];

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            self::FIELD_ID => $this->id,
            self::FIELD_NAME => $this->name,
            self::FIELD_FAMILY_ID => $this->familyId,
        ];
    }
}