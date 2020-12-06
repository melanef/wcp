<?php

namespace Melanef\Wcp;

use Ratchet\ComponentInterface;
use Ratchet\ConnectionInterface;

interface ServerInterface extends ComponentInterface
{
    /**
     * @param ConnectionInterface $conn
     * @param string              $entity
     * @param string|int          $parentId
     * @param array               $data
     *
     * @return ActionResult
     */
    public function onCreate(ConnectionInterface $conn, $entity, $parentId, array $data);

    /**
     * @param ConnectionInterface $conn
     * @param string              $entity
     * @param string|int          $id
     *
     * @return ActionResult
     */
    public function onRead(ConnectionInterface $conn, $entity, $id);

    /**
     * @param ConnectionInterface $conn
     * @param string              $entity
     * @param string|int          $id
     * @param array               $data
     *
     * @return ActionResult
     */
    public function onUpdate(ConnectionInterface $conn, $entity, $id, array $data);

    /**
     * @param ConnectionInterface $conn
     * @param string              $entity
     * @param string|int          $id
     *
     * @return ActionResult
     */
    public function onDelete(ConnectionInterface $conn, $entity, $id);

    /**
     * @param ConnectionInterface $conn
     * @param string              $entity
     * @param string|int          $parentId
     *
     * @return ActionResult
     */
    public function onList(ConnectionInterface $conn, $entity, $parentId);

    /**
     * @return string[]
     */
    public function getSupportedEntities();

    /**
     * @param ConnectionInterface $conn
     *
     * @return string|null
     */
    public function getSessionIdentifier(ConnectionInterface $conn);
}