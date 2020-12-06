<?php

namespace Melanef\Examples\Services;

use Melanef\Examples\Entities\Family;

interface FamilyService
{
    /**
     * @param $id
     *
     * @return Family
     */
    public function findOneById($id);

    /**
     * @param callable $filters
     *
     * @return Family[]
     */
    public function find(callable $filters = null);

    /**
     * @param Family $person
     *
     * @return Family
     */
    public function createOne(Family $person);

    /**
     * @param Family $person
     *
     * @return void
     */
    public function updateOne(Family $person);

    /**
     * @param Family $person
     *
     * @return Family
     */
    public function deleteOne(Family $person);
}