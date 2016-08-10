<?php
namespace Yodao\Builders;

abstract class AbstractBuilder
{

    protected $_table;

    abstract public function count($where = '', array $whereData = []);

    abstract public function select($fields, $where = '', array $whereData = [], $orderBy = null, $limit = null, $offset = null);

    abstract public function selectOne($fields, $where = '', array $whereData = [], $orderBy = null);

    abstract public function insert(array $fieldsMap);

    abstract public function insertOrUpdate(array $insertFields, array $updateFields);

    abstract public function update(array $fieldsMap, $where, array $whereData);

    abstract public function delete($where, array $whereData);

    abstract public function insertFromSelect(array $fieldsMap, $where, array $whereData, $fromTable = null, $limit = null, $offset = null);

    abstract public function insertMulti(array $rows, array $fixFields = []);

    public function table($table)
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * _composeWhere 
     * 
     * @param string $where e.g. [id in :idList] AND pack_id=:pack_id
     * @param array $whereData e.g. ['idList' => $idList, 'pack_id' => $packId]
     */
    protected function _composeWhere(&$where, &$whereData)
    {
        if (!preg_match_all('@\[([\w-]+)\s+(in|not in)\s+:([\w-]+)\]@i', $where, $matches, PREG_SET_ORDER)) {
            return;
        }
        foreach ($matches as $match) {
            $oldIn = $match[0];
            $field = $match[1];
            $inOp = $match[2];
            $matchIdList = $match[3];
            $tpl = $field.' '.strtoupper($inOp).' (%s)';
            $idList = $whereData[$matchIdList];
            unset($whereData[$matchIdList]);
            $idPH = [];
            $index = 0;
            foreach ($idList as $id) {
                $k = "{$field}_{$index}";
                $idPH[] = ":$k";
                $whereData[$k] = $id;
                $index++;
            }
            $newIn = sprintf($tpl, implode(',', $idPH));
            $where = str_replace($oldIn, $newIn, $where);
        }
    }

    protected function _wrapField($field)
    {
        return "`$field`";
    }

    protected function _result($sql, array $bindMap = [], array $whereMap = [])
    {
        return [
            'sql' => $sql,
            'bindingMap' => $bindMap,
            'whereMap' => $whereMap
        ];
    }

}
