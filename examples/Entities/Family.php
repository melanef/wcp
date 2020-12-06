<?php

namespace Melanef\Examples\Entities;

use JsonSerializable;

class Family implements JsonSerializable
{
    const FIELD_ID = 'id';
    const FIELD_SURNAME = 'surname';

    /** @var int */
    public $id;

    /** @var string */
    public $surname;

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

        $self->surname = $data[self::FIELD_SURNAME];

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            self::FIELD_ID => $this->id,
            self::FIELD_SURNAME => $this->surname,
        ];
    }
}