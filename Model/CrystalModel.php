<?php

require_once PROJECT_ROOT_PATH . "/Model/Database.php";

class CrystalModel extends Database
{

    //크리스탈 구매 잔액 ( 구매일로부터 5년 안지난것만 )
    // 크리스탈 잔액 조회시 사용
    public function getPrchsBalance($user_PK)
    {
        return $this->select("SELECT IFNULL(SUM(crystal_balance),0) AS crystal_num FROM CRYSTAL_PRCHS WHERE user_PK = ? AND DATE_ADD(reg_date,INTERVAL 5 YEAR ) > NOW()",[$user_PK]);
    }

    // 크리스탈 후원받은 잔액 ( 후원일로부터 5년 안지난것만 )
    // 크리스탈 잔액 조회시 사용
    public function getDonationInBalance($user_PK)
    {
        return $this->select("SELECT IFNULL(SUM(crystal_balance),0) AS crystal_num FROM CRYSTAL_DONATION WHERE receiver_PK = ? AND DATE_ADD(reg_date,INTERVAL 5 YEAR ) > NOW()",[$user_PK]);
    }

    // 크리스탈 후원하기 (공연장 OR 음원구독)
    public function insertDonation($params=[]){
        return $this->insert("INSERT INTO CRYSTAL_DONATION(sender_PK, receiver_PK, crystal_num, crystal_balance, donation_type,spot_PK) VALUES(?,?,?,?,?,?)",$params);
    }

    // 공연 종료시, 공연장 내에서 후원받은 크리스탈 갯수 구하기
    public function getSpotDonation($spot_PK){
        return $this->select("SELECT IFNULL(SUM(crystal_num),0) AS crystal FROM CRYSTAL_DONATION WHERE donation_type = '공연장' AND spot_PK = ?",[$spot_PK]);
    }

    // 크리스탈로 스타 구매하기
    public function insertStar($params=[]){
        return $this->insert("INSERT INTO CRYSTAL_STAR(user_PK, crystal_num, star_num) VALUES (?,?,?)",$params);
    }

    // 기간 상관 없이 후원 받은 크리스탈 갯수 전체 조회
    public function getWholeDonationIn($user_PK)
    {
        return $this->select("SELECT IFNULL(SUM(crystal_num),0) AS crystal_num FROM CRYSTAL_DONATION WHERE receiver_PK = ?",[$user_PK]);
    }

    //기간 상관없이 환전한 모든 크리스탈 갯수 전체 조회
    // 환전상태 0: 처리중, 1:환전 완료, 2 : 환전 취소, 3: 환전거부. 취소나 거부는 환전 안된것이므로 세지 않음.
    public function getWholeExchange($user_PK)
    {
        return $this->select("SELECT IFNULL(SUM(crystal_num),0) AS crystal_num FROM CRYSTAL_EXCHANGE WHERE user_PK = ? AND (exchange_status = 1 OR exchange_status = 2)",[$user_PK]);
    }
}

//    // 크리스탈 구매갯수 불러오기
//    public function getPrchs($user_PK){
//        return $this->select("SELECT IFNULL(SUM(crystal_num),0) AS crystal FROM CRYSTAL_PRCHS WHERE user_PK = ? AND DATE_ADD(reg_date,INTERVAL 5 YEAR ) > NOW()",[$user_PK]);
//    }
//
//    // 크리스탈 후원받은 갯수 불러오기
//    public function getDonationIn($user_PK){
//        return $this->select("SELECT IFNULL(SUM(crystal_num),0) AS crystal FROM CRYSTAL_DONATION WHERE receiver_PK = ? AND DATE_ADD(reg_date,INTERVAL 5 YEAR ) > NOW()",[$user_PK]);
//    }
//
//    // 크리스탈 후원한 갯수 불러오기
//    public function getDonationOut($user_PK){
//        return $this->select("SELECT IFNULL(SUM(crystal_num),0) AS crystal FROM CRYSTAL_DONATION WHERE sender_PK = ?",[$user_PK]);
//    }
//
//    // 스타 구매한 크리스탈 갯수 불러오기
//    public function getStarPrchs($user_PK){
//        return $this->select("SELECT IFNULL(SUM(crystal_num),0) AS crystal FROM CRYSTAL_STAR WHERE user_PK = ?",[$user_PK]);
//    }