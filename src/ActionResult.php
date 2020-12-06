<?php

namespace Melanef\Wcp;

class ActionResult
{
    /** @var Response */
    private $response;

    /** @var Event */
    private $event;

    /**
     * CwwsActionResult constructor.
     *
     * @param Response   $response
     * @param Event|null $event
     */
    public function __construct(Response $response, Event $event = null)
    {
        $this->response = $response;
        if ($event) {
            $this->event = $event;
        }
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return bool
     */
    public function hasEvent()
    {
        return $this->event !== null;
    }

    /**
     * @return Event|null
     */
    public function getEvent()
    {
        return $this->event;
    }
}
