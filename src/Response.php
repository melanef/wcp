<?php

namespace Melanef\Wcp;

use JsonSerializable;

class Response implements JsonSerializable
{
    const FIELD_STATUS = 'status';
    const FIELD_BODY = 'body';
    const FIELD_MESSAGE = 'message';

    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /** @var string */
    public $status;

    /** @var array */
    public $body;

    /** @var string */
    public $message;

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        $json = [
            self::FIELD_STATUS => $this->status,
        ];

        if (!empty($this->body)) {
            $json[self::FIELD_BODY] = $this->body;
        }

        if ($this->status === self::STATUS_FAILED && !empty($this->message)) {
            $json[self::FIELD_MESSAGE] = $this->message;
        }

        return $json;
    }

    /**
     * @param array $body
     *
     * @return static
     */
    public static function success(array $body = [])
    {
        $response = new self();
        $response->status = self::STATUS_SUCCESS;
        $response->body = $body;

        return $response;
    }

    /**
     * @param string $message
     *
     * @return static
     */
    public static function failed($message)
    {
        $response = new self();
        $response->status = self::STATUS_FAILED;
        $response->message = $message;

        return $response;
    }
}
