<?php

namespace Melanef\Examples\Services;

use Melanef\Examples\Entities\Person;

interface PersonService
{
    /**
     * @param $id
     *
     * @return Person
     */
    public function findOneById($id);

    /**
     * @param callable $filters
     *
     * @return Person[]
     */
    public function find(callable $filters = null);

    /**
     * @param Person $person
     *
     * @return Person
     */
    public function createOne(Person $person);

    /**
     * @param Person $person
     *
     * @return void
     */
    public function updateOne(Person $person);

    /**
     * @param Person $person
     *
     * @return Person
     */
    public function deleteOne(Person $person);
}