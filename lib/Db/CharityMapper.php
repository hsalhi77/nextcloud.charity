<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Charity\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Class CharityMapper
 *
 * @package OCA\Charity\Db
 * @deprecated use QBMapper
 *
 * TODO: Move to QBMapper once Nextcloud 14 is a minimum requirement
 */
class CharityMapper extends QBMapper {

	/**
	 * @param $id
	 * @return \OCP\AppFramework\Db\Entity if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 * @throws \OCP\AppFramework\Db\DoesNotExistException
	 */
    public function find($id) {


        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);


    }

public function columnExists(string $table, string $column): bool {
    $sql = "SHOW COLUMNS FROM `*PREFIX*$table` LIKE ?";
    $result = $this->db->executeQuery($sql, [$column]);

    return $result->fetch() !== false;
}
    protected function findEntityString(String $query, array $params = []) {

        $record=$this->findOneQueryString($query,$params);

        if(sizeof($record)>0)
        return $this->mapRowToEntity($record);
        else return  null;
    }


    protected function findOneQueryString(String $query, array $params = []): array {

        $result = $this->db->executeQuery($query,$params);

        $row = $result->fetch();
        if ($row === false) {
            $result->closeCursor();
            return [];
        }

        $row2 = $result->fetch();
        $result->closeCursor();
        if ($row2 !== false) {
            $msg =
                'Did expect one result but found none when executing';
            throw new MultipleObjectsReturnedException($msg);
        }

        return $row;
    }


    protected function findQueryString(String $query, array $params = []): array {

        $result = $this->db->executeQuery($query,$params);

        $entities = [];


        while ($row = $result->fetch()) {

            $entities[] =$row;
        }

        $result->closeCursor();

        return $entities;
    }


    /**
     * Runs a sql query and returns an array of entities
     *
     * @param IQueryBuilder $query
     * @return Entity[] all fetched entities
     * @psalm-return T[] all fetched entities
     * @throws Exception
     * @since 14.0.0
     */
    protected function findEntitiesString(String $query, array $params = []): array {


        $result = $this->db->executeQuery($query,$params);

        $entities = [];


        while ($row = $result->fetch()) {

            $entities[] = $this->mapRowToEntity($row);
        }

        $result->closeCursor();

        return $entities;
    }


    public function update(Entity $entity) : Entity{
        // if entity wasn't changed it makes no sense to run a db query
        $properties = $entity->getUpdatedFields();
        if (count($properties) === 0) {
            return $entity;
        }

        // entity needs an id
        $id = $entity->getId();
        if ($id === null) {
            throw new \InvalidArgumentException(
                'Entity which should be updated has no id');
        }

        // get updated fields to save, fields have to be set using a setter to
        // be saved
        // do not update the id field
        unset($properties['id']);

        $columns = '';
        $params = [];

        // build the fields
        $i = 0;
        foreach ($properties as $property => $updated) {
            $column = $entity->propertyToColumn($property);
            $getter = 'get' . ucfirst($property);

            $columns .= '`' . $column . '` = ?';

            // only append colon if there are more entries
            if ($i < count($properties) - 1) {
                $columns .= ',';
            }
            if($entity->$getter() instanceof \DateTime)
            {
                $params[]=$entity->$getter()->format("Y-m-d H:i:s");

            }
           else $params[] = $entity->$getter();
            $i++;
        }


        $sql = 'UPDATE `*PREFIX*' . $this->tableName . '` SET ' .
            $columns . ' WHERE `id` = ?';
        $params[] = $id;

        $stmt = $this->db->executeUpdate($sql, $params);


        return $entity;
    }

    /**
     * Creates a new entry in the db from an entity
     * @param Entity $entity the entity that should be created
     * @return Entity the saved entity with the set id
     * @since 7.0.0
     * @deprecated 14.0.0 Move over to QBMapper
     */
    public function insert(Entity $entity) : Entity{
        // get updated fields to save, fields have to be set using a setter to
        // be saved
        $properties = $entity->getUpdatedFields();
        $values = '';
        $columns = '';
        $params = [];

        // build the fields
        $i = 0;
        foreach ($properties as $property => $updated) {
            $column = $entity->propertyToColumn($property);
            $getter = 'get' . ucfirst($property);

            $columns .= '`' . $column . '`';
            $values .= '?';

            // only append colon if there are more entries
            if ($i < count($properties) - 1) {
                $columns .= ',';
                $values .= ',';
            }
            if($entity->$getter() instanceof \DateTime)
            {
              $params[]=$entity->$getter()->format("Y-m-d H:i:s");
            }
            else
                $params[] = $entity->$getter();
            $i++;
        }

        $sql = 'INSERT INTO `*PREFIX*' . $this->tableName . '`(' .
            $columns . ') VALUES(' . $values . ')';

        $stmt = $this->db->executeStatement($sql, $params);

        $entity->setId((int) $this->db->lastInsertId($this->tableName));



        return $entity;
    }

}
