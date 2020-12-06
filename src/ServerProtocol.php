<?php

namespace Melanef\Wcp;

use Exception;
use InvalidArgumentException;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\WsServerInterface;
use SplObjectStorage;

class ServerProtocol implements MessageComponentInterface, WsServerInterface
{
    const PROTOCOL_NAME = 'wcp';

    /** @var ServerInterface */
    protected $decorating;

    /** @var SplObjectStorage[] */
    protected $connections;

    /**
     * CwwsServerProtocol constructor.
     *
     * @param ServerInterface $serverComponent
     */
    public function __construct(ServerInterface $serverComponent)
    {
        $this->decorating = $serverComponent;
        $this->connections = [];
    }

    /**
     * @inheritDoc
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $sessionIdentifier = $this->decorating->getSessionIdentifier($conn);

        if (!array_key_exists($sessionIdentifier, $this->connections)) {
            $this->connections[$sessionIdentifier] = new SplObjectStorage();
        }

        $this->connections[$sessionIdentifier]->attach($conn);
        try {
            $this->handleResult($conn, $this->decorating->onOpen($conn));
        } catch (Exception $exception) {
            $conn->send(json_encode(Response::failed($exception->getMessage())));
            $this->connections[$sessionIdentifier]->detach($conn);
        }
    }

    /**
     * @inheritDoc
     */
    public function onClose(ConnectionInterface $conn)
    {
        $sessionIdentifier = $this->decorating->getSessionIdentifier($conn);
        $this->connections[$sessionIdentifier]->detach($conn);

        try {
            $this->handleResult($conn, $this->decorating->onClose($conn));
        } catch (Exception $exception) {
            $conn->send(json_encode(Response::failed($exception->getMessage())));
            $this->connections[$sessionIdentifier]->detach($conn);
        }
    }

    /**
     * @inheritDoc
     */
    public function onError(ConnectionInterface $conn, Exception $e)
    {
        $sessionIdentifier = $this->decorating->getSessionIdentifier($conn);
        $this->connections[$sessionIdentifier]->detach($conn);
        $conn->close();
    }

    /**
     * @inheritDoc
     */
    public function onMessage(ConnectionInterface $conn, $msg)
    {
        try {
            $message = $this->parseMessage($msg);

            $result = $this->handleAction($conn, $message);

            $this->handleResult($conn, $result);
        } catch (Exception $exception) {
            $conn->send(json_encode(Response::failed($exception->getMessage())));
        }
    }

    /**
     * @param ConnectionInterface   $conn
     * @param ActionResult|null $result
     */
    public function handleResult(ConnectionInterface $conn, ActionResult $result = null)
    {
        if (empty($result)) {
            return;
        }

        $conn->send(json_encode($result->getResponse()));
        if ($result->hasEvent()) {
            $this->sendEvent($conn, $result->getEvent());
        }
    }

    /**
     * @inheritDoc
     */
    public function getSubProtocols()
    {
        return [self::PROTOCOL_NAME];
    }

    /**
     * @param ConnectionInterface $from
     * @param Message             $message
     *
     * @return ActionResult
     */
    private function handleAction(ConnectionInterface $from, Message $message)
    {
        if ($message->action === Message::ACTION_CREATE) {
            return $this->decorating->onCreate($from, $message->entity, $message->parentId, $message->payload);
        }

        if ($message->action === Message::ACTION_READ) {
            return $this->decorating->onRead($from, $message->entity, $message->id);
        }

        if ($message->action === Message::ACTION_UPDATE) {
            return $this->decorating->onUpdate($from, $message->entity, $message->id, $message->payload);
        }

        if ($message->action === Message::ACTION_DELETE) {
            return $this->decorating->onDelete($from, $message->entity, $message->id);
        }

        if ($message->action === Message::ACTION_LIST) {
            return $this->decorating->onList($from, $message->entity, $message->parentId);
        }

        throw new InvalidArgumentException('Input for WCP is invalid. Value for "action" field is invalid');
    }

    /**
     * @param string $json
     *
     * @return Message
     * @throws InvalidArgumentException
     */
    private function parseMessage($json)
    {
        $message = Message::fromJsonString($json);

        if (!in_array($message->entity, $this->decorating->getSupportedEntities())) {
            throw new InvalidArgumentException(
                'Input for WCP is invalid. Value for "entity" field is not supported for this component.'
            );
        }

        return $message;
    }

    /**
     * @param ConnectionInterface $from
     * @param Event               $event
     */
    private function sendEvent(ConnectionInterface $from, Event $event)
    {
        $sessionIdentifier = $this->decorating->getSessionIdentifier($from);
        $connections = $this->connections[$sessionIdentifier];
        foreach ($connections as $connection) {
            if ($connection === $from) {
                continue;
            }

            $connection->send(json_encode($event));
        }
    }
}
