<?php
namespace Yodao\Builders;

class MysqlBuilder extends AbstractBuilder
{

    protected $_table;

    public function count($where = '', array $whereData = [])
    {
        if ($where) {
            $this->_composeWhere($where, $whereData);
            $where = " WHERE {$where}";
        }
        $sql = "SELECT count(1) as total FROM `{$this->_table}`{$where}";
        return $this->_result($sql, [], $whereData);
    }

    /*
        $this->_select('folder_id=:folder_id', ['folder_id' => $folderId], 'id DESC', $num, $offset);
     */
    public function select($fields, $where = '', array $whereData = [], 
        $orderBy = null, $limit = null, $offset = null)
    {
        if (empty($fields)) {
            return false;
        }
        if (!is_array($fields)) {
            if ($fields != '*') {
                $fields = "`$fields`";
            }
        } else {
            $fields = '`'.implode('`,`', $fields).'`';
        }
        $orderByStr = '';
        if (null !== $orderBy) {
            $orderByStr = " ORDER BY {$orderBy}";
        }
        $limitStr = '';
        if (null !== $limit) {
            $limit = intval($limit);
            $limitStr = null !== $offset ? ' LIMIT '.intval($offset).','.$limit : " LIMIT $limit";
        }
        if ($where) {
            $this->_composeWhere($where, $whereData);
            $where = " WHERE {$where}";
        }
        $sql = "SELECT {$fields} FROM `{$this->_table}`{$where}{$orderByStr}{$limitStr}";
        return $this->_result($sql, [], $whereData);
    }

    public function selectOne($fields, $where = '', array $whereData = [], $orderBy = null)
    {
        return $this->select($fields, $where, $whereData, $orderBy, 1);
    }

    /*
        $this->_insert([
            'folder_id' => $folderId,
            'name' => $name,
            'url' => $url,
            'create_time' => time(),
        ]);
     */
    public function insert(array $fieldsMap)
    {
        if (empty($fieldsMap)) {
            return false;
        }
        $sql = "INSERT INTO `{$this->_table}` SET %s";
        $fieldsArr = [];
        if ($fieldsMap) {
            foreach ($fieldsMap as $field => $value) {
                $v = "`{$field}`=:{$field}";
                $fieldsArr[] = $v;
            }
        }
        $sql = sprintf($sql, implode(',', $fieldsArr));
        return $this->_result($sql, $fieldsMap);
    }

    public function insertOrUpdate(array $insertFields, array $updateFields)
    {
        if (empty($insertFields) || empty($updateFields)) {
            return false;
        }
        $insertArr = [];
        $updateArr = [];
        $insertBind = [];
        $updateBind = [];
        foreach ($insertFields as $field => $value) {
            $bindKey = "{$field}INSERT";
            $v = "`{$field}`=:$bindKey";
            $insertArr[] = $v;
            $insertBind[$bindKey] = $value;
        }
        foreach ($updateFields as $field => $value) {
            $bindKey = "{$field}UPDATE";
            $v = "`{$field}`=:$bindKey";
            $updateArr[] = $v;
            $updateBind[$bindKey] = $value;
        }
        $sql = "INSERT INTO `{$this->_table}` SET %s ON DUPLICATE KEY UPDATE %s";
        $sql = sprintf($sql, implode(',', $insertArr), implode(',', $updateArr));
        return $this->_result($sql, array_merge($insertBind, $updateBind));
    }

    /*
        $this->_update([
            'folder_id' => $folderId,
        ], '[id in :idList]', [
            'idList' => $idList,
        ]);
     */
    public function update(array $fieldsMap, $where, array $whereData)
    {
        if (empty($fieldsMap) || empty($where) || empty($whereData)) {
            return false;
        }
        $this->_composeWhere($where, $whereData);
        $sql = "UPDATE `{$this->_table}` SET %s WHERE {$where}";
        $fieldsArr = [];
        $bindFields = [];
        if ($fieldsMap) {
            foreach ($fieldsMap as $field => $value) {
                if (false !== strpos($value, "`$field`")) { // `total_num`=`total_num` + 1
                    $v = "`{$field}`=$value";
                } else {
                    $v = "`{$field}`=:{$field}";
                    $bindFields[$field] = $value;
                }
                $fieldsArr[] = $v;
            }
        }
        $sql = sprintf($sql, implode(',', $fieldsArr));
        return $this->_result($sql, $bindFields, $whereData);
    }

    public function delete($where, array $whereData)
    {
        if (empty($where) || empty($whereData)) {
            return false;
        }
        $this->_composeWhere($where, $whereData);
        $sql = "DELETE FROM `{$this->_table}` WHERE {$where}";
        return $this->_result($sql, [], $whereData);
    }

    public function insertFromSelect(array $fieldsMap, $where, array $whereData, $fromTable = null, $limit = null, $offset = null)
    {
        if (empty($fieldsMap) || empty($where) || empty($whereData)) {
            return false;
        }
        $this->_composeWhere($where, $whereData);
        $insertFields = array_keys($fieldsMap);
        $insertFields = array_map([$this, '_wrapField'], $insertFields);
        $bindFields = [];
        $fieldArr = [];
        foreach ($fieldsMap as $k=>$v) {
            if ($v === '.') {
                $fieldArr[] = "`$k`";
                continue;
            }
            $bindFields[$k] = $v;
            $fieldArr[] = ":$k";
        }
        if (empty($fromTable)) {
            $fromTable = $this->_table;
        }
        $limitStr = '';
        if (null !== $limit) {
            $limit = intval($limit);
            $limitStr = null !== $offset ? ' LIMIT '.intval($offset).','.$limit : " LIMIT $limit";
        }
        $sql = "INSERT INTO `$this->_table` (%s) SELECT %s FROM `$fromTable` WHERE {$where}{$limitStr}";
        $sql = sprintf($sql, implode(',', $insertFields), implode(',', $fieldArr));
        return $this->_result($sql, $bindFields, $whereData);
    }

    public function insertMulti(array $rows, array $fixFields = [])
    {
        if (empty($rows)) {
            return false;
        }
        $i = 0;
        $fields = [];
        $bindFields = [];
        $line = [];
        foreach ($rows as $row) {
            if ($i == 0) {
                $fields = array_keys($row);
                if ($fixFields) {
                    $fields = array_merge($fields, array_keys($fixFields));
                }
            }
            $lineField = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $fixFields)) {
                    $bindFields[$field] = $fixFields[$field] ?: '';
                    $lineField[] = ":$field";
                } else {
                    $k = "{$field}{$i}";
                    $bindFields[$k] = $row[$field] ?: '';
                    $lineField[] = ":{$k}";
                }
            }
            $line[] = '('.implode(',', $lineField).')';
            $i++;
        }
        $fieldsStr = '`'.implode('`,`', $fields).'`';
        $lineStr = implode(',', $line);
        $sql = "INSERT INTO `$this->_table`(%s) VALUES %s";
        $sql = sprintf($sql, $fieldsStr, $lineStr);
        return $this->_result($sql, $bindFields);
    }

}
