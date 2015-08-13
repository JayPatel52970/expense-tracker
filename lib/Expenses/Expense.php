<?php

namespace Expenses;

use \PDO;
use \Exception;
use \InvalidArgumentException;

use Config;

class Expense extends AbstractSingular {
    public static $attributeTypes = array(
        'expenseid'     =>  PDO::PARAM_INT,
        'date'          =>  PDO::PARAM_STR,
        'typeid'        =>  PDO::PARAM_INT,
        'locationid'    =>  PDO::PARAM_INT,
        'amount'        =>  PDO::PARAM_STR,
        'comment'       =>  PDO::PARAM_STR
    );
    
    public static $table = 'expenses';
    public static $idColumn = 'expenseid';
    
    public static function create($data) {
        global $db;
        
        if (! (array_key_exists('typeid', $data))) {
            throw new InvalidArgumentException("Specified data array must include typeid key.");
        } elseif (! (array_key_exists('locationid', $data))) {
            throw new InvalidArgumentException("Specified data array must include locationid key.");
        } elseif (! (array_key_exists('amount', $data))) {
            throw new InvalidArgumentException("Specified data array must include amount key.");
        }
        
        if (! self::validateId($data['typeid'])) {
            throw new Exception("Invalid typeid specified.");
        } elseif (! self::validateId($data['locationid'])) {
            throw new Exception("Invalid locationid specified.");
        } elseif (! self::validateAmount($data['amount'])) {
            throw new Exception("Invalid amount specified.");
        }
        
        if (array_key_exists('comment', $data)) {
            if (! self::validateComment($data['comment'])) {
                throw new Exception("Invalid comment specified.");
            }
        } else {
            $data['comment'] = "";
        }
        
        if (array_key_exists('date', $data)) {
            if (! self::validateDateString($data['date'])) {
                throw new Exception("Invalid date specified.");
            }
        } else {
            $data['date'] = date(DB_DATE_FORMAT);
        }
        
        $newExpenseQuery = $db->prepare("
            INSERT INTO " . Config::TABLE_PREFIX . static::$table . " (date, typeid, locationid, amount, comment)
            VALUES (:date, :typeid, :locationid, :amount, :comment)
        ");
        
        $newExpenseQuery->bindParam(':date', $data['date'], self::$attributeTypes['date']);
        $newExpenseQuery->bindParam(':typeid', $data['typeid'], self::$attributeTypes['typeid']);
        $newExpenseQuery->bindParam(':locationid', $data['locationid'], self::$attributeTypes['locationid']);
        $newExpenseQuery->bindParam(':amount', $data['amount'], self::$attributeTypes['amount']);
        $newExpenseQuery->bindParam(':comment', $data['comment'], self::$attributeTypes['comment']);
        
        $newExpenseQuery->execute();
        
        if (! $newExpenseQuery->rowCount() === 1) {
            throw new Exception("Database entry not inserted.");
        }
        
        $expenseId = $db->lastInsertId();
        
        $expense = new self($expenseId);
        $expense->load();
        
        return $expense;
    }
    
    public function getDate($user, $descriptive = true) {
        // TODO: check user is valid object
        return $user->formatDate($this->getAttribute('date'), $descriptive);
    }
    
    public function getType() {
        $type = new Type($this->getAttribute('typeid'));
        $type->load();
        
        return $type;
    }
    
    public function getLocation() {
        $location = new Location($this->getAttribute('locationid'));
        $location->load();
        
        return $location;
    }
    
    public static function validateAmount($amount) {        
        return (
            (floatval($amount) >= 0)
        );
    }
    
    public static function validateComment($comment) {
        return (
            (is_string($comment)) &&
            (strlen($comment) <= 255) // database-defined maximum length
        );
    }
}

?>