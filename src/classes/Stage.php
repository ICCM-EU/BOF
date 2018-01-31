<?php

namespace ICCM\BOF;
use \PDO;

class Stage
{
   private $db;
 
 

    function __construct($db) {
        $this->db = $db;
    }

    public function getstage() {
        $sql = 'SELECT * FROM `config`';
        $query=$this->db->prepare($sql);
        $param = array ();
	    $config = array ();
        $query->execute($param);

        while ($row=$query->fetch(PDO::FETCH_OBJ)) {
		    $config [$row->item]=$row->value;
        }         

        if (time() > strtotime($config['nomination_begins']) && time() < strtotime($config['nomination_ends'])){
            return 'nominating';
        }
        if (time() > strtotime($config['voting_begins']) && time() < strtotime($config['voting_ends'])){
            return 'voting';
        }
        
        return 'locked';
	}
}

?>
