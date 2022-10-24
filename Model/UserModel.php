<?php
//echo "\r         Model/UserModel 들어옴 ";
//echo "\r             Model/Database리콰이어, getUsers 선언";
require_once PROJECT_ROOT_PATH . "/Model/Database.php";


class UserModel extends Database
{

    //회원가입
    public function insertUsers($params = [])
    {
        return $this->insert("INSERT INTO USER (user_nickname,user_email,profile_path,social_type,social_ID) VALUES (?,?,?,?,?)",$params);

    }

    //회원탈퇴
    public function deleteUser($user_PK){
        return$this->update("UPDATE USER SET delete_date = CURTIME() WHERE user_PK = ?",[$user_PK]);
    }

    //로그인
    public function getUserByEmail($user_email){
        return $this->select("SELECT * FROM USER WHERE user_email = ? AND delete_date IS NULL",[$user_email]);
        // 탈퇴한 유저인 경우 어떤 처리를 할지 일단 고민. delete date가 있는 유저도 다 가져온다
        // 로그인시 존재하는 유저인지 확인하기 위해서 호출함
    }

    // 유저 정보 반환
    public function getUserRow($user_PK){
        return $this->select("SELECT * FROM VIEW_USER WHERE user_PK = ?",[$user_PK]);
    }

    public function updateUser($params = [])
    {
        return $this->update("UPDATE USER SET user_nickname = ?, char_PK = ?, profile_path = ? WHERE user_PK = ?;",$params);
    }

    // 유저가 튜토리얼 실행함
    public function updateTutorialFinished($user_PK){
        return $this->update("UPDATE USER SET tutorial_finished = true, update_date = NOW() WHERE user_PK = ?",[$user_PK]);
    }

    // 스타 더하기
    public function addStarNum($params=[]){
        return $this->update("UPDATE USER SET star_num = (star_num+?), update_date = NOW() WHERE user_PK = ?",$params);
    }

    // 스타 빼기
    public function minusStarNum($params=[]){
        return $this->update("UPDATE USER SET star_num = (star_num-?), update_date = NOW() WHERE user_PK = ?",$params);
    }

    // 유저가 가진 스타 갯수 가져오기
    public function getStarNum($user_PK)
    {
        return $this->select("SELECT star_num FROM USER WHERE user_PK = ?",[$user_PK]);
    }

    // 유저 캐릭터 고유번호 변경
    public function updateChar($params = [])
    {
        return $this->update("UPDATE USER SET char_PK = ? WHERE user_PK = ?",$params);
    }

    //??
    public function checkExistUser($google_ID){
        return $this->select("SELECT * FROM user WHERE google_ID = ? AND delete_date IS NULL",[$google_ID]);
    }

    //테스트용
    public function getUsers($limit)
    {
//        echo "\r          Model/UserModel-getUsers에서 select실행";

        return $this->select("SELECT * FROM USER WHERE delete_date is null ORDER BY user_PK ASC LIMIT ?", [$limit]);
    }


}