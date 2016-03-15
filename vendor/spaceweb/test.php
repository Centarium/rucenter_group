<?
/*require_once '/quest/src/classes/QuestAbstract.php';

use SpaceWeb\Quest\QuestDB;
use SpaceWeb\Quest\QuestDone;*/

abstract class QuestAbstract
{
    /**
     * @var \PDO
     */
    private $db = null;

    public final function __construct()
    {
        /*$this->db = new \PDO('sqlite::memory:');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $sql = file_get_contents(__DIR__.'/../../resources/database.sql');
        $this->db->exec($sql);*/
    }

    /**
     * @return \PDO
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @param \PDO $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }

}


//Скинул все классы в одно место, т.к. у rucenter не пашет
class QuestDB extends QuestAbstract
{
    public function setTestDbConnection($host, $dbname, $user_param, $pass_param)
    {
        $dsn = "mysql:host=".$host.";dbname=".$dbname.";charset=utf8";
        $user = $user_param;
        $pass = $pass_param;

        $opt = array(
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        );

        $this->setDb( new \PDO($dsn, $user, $pass, $opt));
    }
}



class QuestDone
{
    private $args=[];
    private $pay_table = 'payments';
    private $docs_table = 'documents';
    private $filtered_array = ['statistic'];
    private $date_field = 'create_ts';


    //set console arguments
    public function setArgs($args)
    {
        $this->args = $args;
    }

    //Получение статистики в виде "Основной запрос+опции консоли+даты"
    public function getStatistic()
    {
        if(!$this->filter()) return;

        $count = count($this->args);
        $query_body = $this->getBodyQuery( $this->args[1] );
        $query_date = $this->getDataRange();

        $query_option ='';
        $query ='';


        for($i=2;$i<$count;$i++)
        {
            $query_option = $this->getOptionQuery( $this->args[$i] );
            if($i > 2) $query .= ' UNION ';

            $query .= $query_body.$query_option.$query_date;
        }

        if($query == '') $query = $query_body;
        return $query;

    }

    //Получение верхнего и нижнего диапазона дат
    private function getDataRange()
    {
        $query_date ='';
        $date_array = [
            "Start_date" => '',
            "End_date" => ''
        ];


        print 'Please enter start date: ';
        $date_array['Start_date'] = rtrim(fgets(STDIN, 1024));

        print 'Please enter end date: ';
        $date_array['End_date'] = rtrim(fgets(STDIN, 1024));


        if( $date_array['Start_date']!= '' &&  $date_array['Start_date']!= 'no')
        {
            $query_date = ' AND '.
                $this->pay_table.'.'.$this->date_field. ' > '."'".$date_array['Start_date']."'";
        }

        if( $date_array['End_date']!= '' &&  $date_array['End_date']!= 'no' )
        {
            $query_date .= ' AND '.
                $this->pay_table.'.'.$this->date_field.' < '."'".$date_array['End_date']."'";
        }

        return $query_date;
    }

    //may be extend to write message(можно будет расширить до вывода сообщений, убирать повторы и т.д.)
    private function filter()
    {
        //too less params
        if(count($this->args) < 2) return false;
        //not possible command
        if( !in_array($this->args[1], $this->filtered_array) ) return false;

        return true;
    }

    private function getBodyQuery($typeQuery)
    {
        $query ='';
        switch($typeQuery)
        {
            case 'statistic' : {$query = $this->getPayments(); break;}
        }
        return $query;
    }

    private function getOptionQuery($option)
    {
        $query ='';
        switch($option)
        {
            case '--without-documents' : {$query .= $this->WithoutDocs(); break;}
            case '--with-documents' : {$query .= $this->WithDocs();break;}
        }
        return $query;
    }

    private function getPayments()
    {
        return " SELECT COUNT( ".$this->pay_table.".`id` ) AS num, SUM( ".$this->pay_table.".`amount` ) AS amount
                FROM ".$this->pay_table."
                LEFT JOIN ".$this->docs_table." ON ".$this->pay_table.".`id` = ".$this->docs_table.".`entity_id`";
    }

    private function WithoutDocs()
    {
        return " WHERE ".$this->docs_table.".`id` IS NULL";
    }

    private function WithDocs()
    {
        return " WHERE ".$this->docs_table.".`id` IS NOT NULL";
    }
}




$quest_db = new QuestDB();
$quest_done = new QuestDone();

$quest_done->setArgs($argv);
$quest_db->setTestDbConnection('localhost', 'rucenter', 'root', '');

$result = $quest_db->getDb()->query( $quest_done->getStatistic() );

print "+---------+----------+\n";
print "| count   | amount   |\n";
print "+---------+----------+\n";

//Выравнивал на С++, подзабыл, как на PHP
while ($row = $result->fetch())
{
    print('| '.$row['num'].' | '.$row['amount'] );
    print "\n";
}
print "+---------+----------+";
?>