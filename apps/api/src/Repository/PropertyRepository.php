<?php
declare(strict_types=1);
namespace Guard51\Repository;
use Guard51\Entity\Property;

/** @extends BaseRepository<Property> */
class PropertyRepository extends BaseRepository
{
    protected function getEntityClass(): string { return Property::class; }
}
