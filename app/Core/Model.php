<?php

namespace App\Core;

class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll($conditions = [])
    {
        $sql = "SELECT * FROM {$this->table}";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', array_map(fn($k) => "$k = :$k", array_keys($conditions)));
        }
        return $this->db->fetchAll($sql, $conditions);
    }

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    public function findOne($conditions = [])
    {
        $sql = "SELECT * FROM {$this->table}";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', array_map(fn($k) => "$k = :$k", array_keys($conditions)));
        }
        $sql .= " LIMIT 1";
        return $this->db->fetch($sql, $conditions);
    }

    public function create($data)
    {
        if (!isset($data[$this->primaryKey])) {
            $data[$this->primaryKey] = Uuid::uuid4();
        }
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $this->db->query($sql, $data);
        
        return $this->find($data[$this->primaryKey]);
    }

    public function update($id, $data)
    {
        $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $set WHERE {$this->primaryKey} = :primaryKey";
        $data['primaryKey'] = $id;
        
        $this->db->query($sql, $data);
        return $this->find($id);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $this->db->query($sql, ['id' => $id]);
    }
}
