<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\ORM;

use Espo\ORM\{
    QueryParams\Select,
};

/**
 * Reasonable to use when selecting a large number of records.
 * It doesn't allocate a memory for every entity.
 * Entities are fetched on each iteration while traversing a collection.
 */
class SthCollection implements \IteratorAggregate, Collection
{
    protected $entityManager = null;

    protected $entityType;

    protected $selectParams = null;

    private $sth = null;

    private $sql = null;

    protected $isFetched = false;

    public function __construct(string $entityType, EntityManager $entityManager = null, array $selectParams = [])
    {
        $this->selectParams = $selectParams;
        $this->entityType = $entityType;
        $this->entityManager = $entityManager;
    }

    protected function getQuery()
    {
        return $this->entityManager->getQuery();
    }

    protected function getPdo()
    {
        return $this->entityManager->getPdo();
    }

    protected function getEntityFactory()
    {
        return $this->entityManager->getEntityFactory();
    }

    /**
     * Get select parameters.
     */
    public function setSelectParams(array $selectParams)
    {
        $this->selectParams = $selectParams;
    }

    /**
     * Set an SQL query.
     */
    public function setQuery(?string $sql)
    {
        $this->sql = $sql;
    }

    /**
     * Run an SQL query.
     */
    public function executeQuery()
    {
        if (!$this->sql) {
            $this->sql = $this->getQuery()->create($this->getSelectQueryParams());
        }

        $sth = $this->getPDO()->prepare($this->sql);
        $sth->execute();

        $this->sth = $sth;
    }

    protected function getSelectQueryParams() : Select
    {
        $params = $this->selectParams;
        $params['from'] = $this->entityType;

        return Select::fromRaw($params);
    }

    public function getIterator()
    {
        return (function () {
            if (isset($this->sth)) {
                $this->sth->execute();
            }
            while ($row = $this->fetchRow()) {
                $entity = $this->getEntityFactory()->create($this->entityType);
                $entity->set($row);
                $entity->setAsFetched();
                $this->prepareEntity($entity);
                yield $entity;
            }
        })();
    }

    protected function fetchRow()
    {
        if (!$this->sth) {
            $this->executeQuery();
        }
        return $this->sth->fetch(\PDO::FETCH_ASSOC);
    }

    protected function prepareEntity(Entity $entity)
    {
    }

    /**
     * @deprecated
     */
    public function toArray(bool $itemsAsObjects = false) : array
    {
        $arr = [];
        foreach ($this as $entity) {
            if ($itemsAsObjects) {
                $item = $entity->getValueMap();
            } else {
                $item = $entity->toArray();
            }
            $arr[] = $item;
        }
        return $arr;
    }

    public function getValueMapList() : array
    {
        return $this->toArray(true);
    }

    /**
     * Mark as fetched from DB.
     */
    public function setAsFetched()
    {
        $this->isFetched = true;
    }

    /**
     * Mark as not fetched from DB.
     */
    public function setAsNotFetched()
    {
        $this->isFetched = false;
    }

    /**
     * Is fetched from DB.
     */
    public function isFetched() : bool
    {
        return $this->isFetched;
    }

    /**
     * Get an entity type.
     */
    public function getEntityType() : string
    {
        return $this->entityType;
    }
}
