<?php
//echo "\r              Model/Database 들어옴 ";
//echo "\r                  construct(db연동), select 선언";

class Database
{
    public $connection = null;

    public function __construct()
    {
//        echo "\r          Model/Database construct 실행"

        try {
            $this->connection =
                new PDO(DSN, DB_USERNAME, DB_PASSWORD);
//            new PDO('mysql:host=mariadb;port=3306;dbname=buskon;charset=utf8','root', 'test');
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            //            echo " - MariaDB conn 성공";
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

    }


    /**
     * 업데이트 쿼리문
     * 해당 쿼리에서 영향받은 count 반환. 이 숫자가 0이면 뭔가 잘못된 거
     * @param string $query
     * @param array $params
     * @return int
     * @throws Exception
     */
    public function update($query = "", $params = [])
    {
        try {
            $stmt = $this->connection->prepare($query);


            if (!is_array($params) || !isset($params)) {
                echo "\rparam not exist or not array";
                exit;
            } else {
                $stmt->execute($params);
                $affected_cnt = $stmt->rowCount();
                //echo "\raffected cnt : $affected_cnt";
                return $affected_cnt;
            }

        } catch (Exception $e) {

            throw new Exception($e->getMessage());

        }
    }

    /**insert 쿼리문
     * 마지막으로 insert 된 id를 반환한다
     *
     * @param string $query
     * @param array $params
     * @return int
     * @throws Exception
     */
    public function insert($query = "", $params = [])
    {
        try {
            //        echo "\r          Model/Database insert 진행";
            $stmt = $this->connection->prepare($query);

            // $param이 없거나 array가 아닌경우, 백엔드단 실수 - echo 되도록 함
            if (!is_array($params) || !isset($params)) {
                echo "\rparam not exist or not array";
                exit;
            } else {
                $stmt->execute($params);
                //echo "execute 성공";
                $lastInsertId = $this->connection->lastInsertId();
                // lastInsertId는 PDO에 실행해야 한다! $stmt에 하면 에러만 남!
                //echo "\r last insert : ".$lastInsertId;


                return $lastInsertId;
            }


        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

    }

    /** select 쿼리문
     *  쿼리문을 통해 생성된 데이터가 array, 혹은 false 로 반환됨
     *
     * @param string $query : 쿼리문
     * @param array $params : 파라미터 배열은 앞은 1부터 시작하는 숫자, 뒤는 파라미터 값을 넣으면 된다.
     * @return array|false
     * @throws Exception
     */
    public function select($query = "", $params = [])
    {
        //echo "\r          Model/Database select 진행";

        try {
            $stmt = $this->connection->prepare($query);

            if (!is_array($params) || !isset($params)) {
                echo "\rparam not exist or not array";
                exit;
            } else {

                $stmt->execute($params);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }




}