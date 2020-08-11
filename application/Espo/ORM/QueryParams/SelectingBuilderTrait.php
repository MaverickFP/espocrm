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

namespace Espo\ORM\QueryParams;

trait SelectingBuilderTrait
{
    use BaseBuilderTrait;

    /**
     * Set FROM parameter. For what entity type to build a query.
     */
    public function from(string $entityType) : self
    {
        if (isset($this->params['from'])) {
            throw new LogicException("Method 'from' can be called only once.");
        }

        $this->params['from'] = $entityType;

        return $this;
    }

    /**
     * Set DISTINCT parameter.
     */
    public function distinct() : self
    {
        $this->params['distinct'] = true;

        return $this;
    }

    /**
     * Add a WHERE clause.
     *
     * Two usage options:
     * * `where(array $whereClause)`
     * * `where(string $key, string $value)`
     */
    public function where($param1 = [], $param2 = null) : self
    {
        $this->params['whereClause'] = $this->params['whereClause'] ?? [];

        if (is_array($param1)) {
            $this->params['whereClause'] = $param1 + $this->params['whereClause'];

            return $this;
        }

        if (!is_null($param2)) {
            $this->params['whereClause'][] = [$param1 => $param2];

            return $this;
        }

        throw new BadMethodCallException();
    }

    /**
     * Apply ORDER.
     *
     * @param string|array $orderBy An attribute to order by or order definitions as an array.
     * @param bool|string $direction 'ASC' or 'DESC'. TRUE for DESC order.
     */
    public function order($orderBy = null, $direction = Select::ORDER_ASC) : self
    {
        if (!$orderBy) {
            throw BadMethodCallException();
        }

        $this->params['orderBy'] = $orderBy;
        $this->params['order'] = $direction;

        return $this;
    }

    /**
     * Add JOIN.
     *
     * @param string|array $relationName A relationName or table. A relationName is in camelCase, a table is in CamelCase.
     *
     * Usage options:
     * * `join(string $relationName)`
     * * `join(array $joinDefinitionList)`
     *
     * Usage examples:
     * ```
     * ->join($relationName)
     * ->join($relationName, $alias, $conditions)
     * ->join([$relationName1, $relationName2, ...])
     * ->join([[$relationName, $alias], ...])
     * ->join([[$relationName, $alias, $conditions], ...])
     * ```
     */
    public function join($relationName, ?string $alias = null, ?array $conditions = null) : self
    {
        if (empty($this->params['joins'])) {
            $this->params['joins'] = [];
        }

        if (is_array($relationName)) {
            $joinList = $relationName;

            foreach ($joinList as $item) {
                $this->params['joins'][] = $item;
            }

            return $this;
        }

        if (is_null($alias) && is_null($conditions)) {
            $this->params['joins'][] = $relationName;

            return $this;
        }

        if (is_null($conditions)) {
            $this->params['joins'][] = [$relationName, $alias];

            return $this;
        }

        $this->params['joins'][] = [$relationName, $alias, $conditions];

        return $this;
    }

    /**
     * Add LEFT JOIN.
     *
     * @param string|array $relationName A relationName or table. A relationName is in camelCase, a table is in CamelCase.
     *
     * This method works the same way as `join` method.
     */
    public function leftJoin($relationName, ?string $alias = null, ?array $conditions = null) : self
    {
        if (empty($this->params['leftJoins'])) {
            $this->params['leftJoins'] = [];
        }

        if (is_array($relationName)) {
            $joinList = $relationName;

            foreach ($joinList as $item) {
                $this->params['leftJoins'][] = $item;
            }

            return $this;
        }

        if (is_null($alias) && is_null($conditions)) {
            $this->params['leftJoins'][] = $relationName;

            return $this;
        }

        if (is_null($conditions)) {
            $this->params['leftJoins'][] = [$relationName, $alias];

            return $this;
        }

        $this->params['leftJoins'][] = [$relationName, $alias, $conditions];

        return $this;
    }
}