<?php
trait YodaoSqlBuilder 
{

    public function count($where = '', array $whereData = [])
    {
        if ($where) {
            $this->_transformWhere($where, $whereData);
            $where = " WHERE {$where}";
        }
        $sql = "SELECT count(1) as total FROM `{$this->_table}`{$where}";
        $ret = $this->_executeSql($sql, [], $whereData);
        return $ret ? intval($ret[0]['total']) : 0;
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
            $this->_transformWhere($where, $whereData);
            $where = " WHERE {$where}";
        }
        $sql = "SELECT {$fields} FROM `{$this->_table}`{$where}{$orderByStr}{$limitStr}";
        $ret = $this->_executeSql($sql, [], $whereData);
        return $ret;
    }

    public function selectOne($fields, $where = '', array $whereData = [], $orderBy = null)
    {
        $ret = $this->select($fields, $where, $whereData, $orderBy, 1);
        if (empty($ret)) {
            return $ret;
        }
        return $ret[0] ?: [];
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
        $ret = $this->_executeSql($sql, $fieldsMap);
        return $ret ? $this->_dbh->lastInsertId() : false;
    }

    public function insertUpdate(array $insertFields, array $updateFields)
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
        $ret = $this->_executeSql($sql, array_merge($insertBind, $updateBind));
        return $ret ? $this->_rowCount() : 0;
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
        $this->_transformWhere($where, $whereData);
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
        $ret = $this->_executeSql($sql, $bindFields, $whereData);
        return $ret;
    }

    public function delete($where, array $whereData)
    {
        if (empty($where) || empty($whereData)) {
            return false;
        }
        $this->_transformWhere($where, $whereData);
        $sql = "DELETE FROM `{$this->_table}` WHERE {$where}";
        $ret = $this->_executeSql($sql, [], $whereData);
        return $ret;
    }

    public function insertSelect(array $fieldsMap, $where, array $whereData, $fromTable = null, $limit = null, $offset = null)
    {
        if (empty($fieldsMap) || empty($where) || empty($whereData)) {
            return false;
        }
        $this->_transformWhere($where, $whereData);
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
        $ret = $this->_executeSql($sql, $bindFields, $whereData);
        return $ret;
    }

    public function multiInsert(array $rows, array $fixFields = [])
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
        $ret = $this->_executeSql($sql, $bindFields);
        return $ret;
    }

    public function rowCount()
    {
        return $this->_stmt ? $this->_stmt->rowCount() : 0;
    }

    private function _executeSql($sql, array $fieldsMap = [], array $whereData = [])
    {
        $this->_checkTable();
        $type = $this->_checkType($sql);
        $this->_stmt = $stmt = $this->prepare($sql);
        if ($fieldsMap) {
            foreach ($fieldsMap as $field => $v) {
                $stmt->bindValue(":$field", $v);
            }
        }
        if ($whereData) {
            foreach ($whereData as $k=>$v) {
                $stmt->bindValue(":$k", $v);
            }
        }
        if (false === $stmt->execute()) {
            $this->_logWriter->log("execute sql failed. sql: $sql; data:".var_export($fieldsMap, true). "; where:".var_export($whereData, true));
            return false;
        }
        if (0 === strpos($sql, 'SELECT ')) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        return true;
    }

    /**
     * _transformWhere 
     * 
     * @param string $where e.g. [id in :idList] AND pack_id=:pack_id
     * @param array $whereData e.g. ['idList' => $idList, 'pack_id' => $packId]
     */
    private function _transformWhere(&$where, &$whereData)
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
            foreach ($idList as $id) {
                $k = "id{$id}";
                $idPH[] = ":$k";
                $whereData[$k] = $id;
            }
            $newIn = sprintf($tpl, implode(',', $idPH));
            $where = str_replace($oldIn, $newIn, $where);
        }
    }

    private function _wrapField($field)
    {
        return "`$field`";
    }

    private function _checkType($sql)
    {
        $type = substr($sql, 0, 7);
        if (in_array(
            $type, 
            [
                self::TYPE_SELECT,
                self::TYPE_INSERT,
                self::TYPE_UPDATE,
                self::TYPE_DELETE
            ]
        )) {
            return $type;
        }
        return self::TYPE_UNKNOWN;
    }

}
