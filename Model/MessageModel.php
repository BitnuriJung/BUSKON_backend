<?php

require_once PROJECT_ROOT_PATH . "/Model/Database.php";

class MessageModel extends Database
{
    // 상대방과 문자한 적 있는지 확인 (한적 있으면 room, join row 생성필요없음)
    // 문자 전송시 호출
    public function getMessageHistory($params = []){
        return $this->select("SELECT message_room_PK FROM MESSAGE_JOIN WHERE user_PK = ? AND partner_PK = ?", $params);
    }

    // 채팅방 새로 생성
    // 채팅 처음 보내는 상대인 경우 실행됨
    // 참여자 정보나 아무것도 필요없고 그냥 자동 생성되는 PK, 생성날짜만 있음.
    public function insertMessageRoom(){
        return $this->insert("INSERT INTO MESSAGE_ROOM() VALUES ()",[]);
    }

    // 채팅참여정보 새로 생성
    // 채팅 처음 보내는 상대인 경우 실행됨
    public function insertMessageJoin($params=[]){
        return $this->insert("INSERT INTO MESSAGE_JOIN (message_room_PK, user_PK, partner_PK) VALUES (?,?,?)",$params);
    }

    // 새 채팅 전송정보 저장
    public function insertMessage($params=[]){
        return $this->insert("INSERT INTO MESSAGE (message_room_PK, sender_PK, message) VALUES (?,?,?)",$params);
    }

    // 특정 유저한테 읽지않은 채팅있나 확인
    // 마이폰 열기에서 확인
    public function getUnreadMessage($user_PK){
        return $this->select("SELECT MESSAGE_JOIN.message_room_PK, last_read_date, C.message, C.reg_date FROM MESSAGE_JOIN
                                JOIN MESSAGE C on MESSAGE_JOIN.message_room_PK = C.message_room_PK AND sender_PK != ? AND C.reg_date > last_read_date
WHERE user_PK = ?",[$user_PK,$user_PK]);
    }

    // 문자 row 정보 조회
    public function getMessageRow($message_PK)
    {
        return $this->select("SELECT * FROM MESSAGE WHERE message_PK = ?",[$message_PK]);
    }
}