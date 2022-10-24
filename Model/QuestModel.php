<?php

require_once PROJECT_ROOT_PATH . "/Model/Database.php";

class QuestModel extends Database
{
    // 특정유저의 달성했으나 보상은 받지 않은 퀘스트 조회
    // 마이폰 열기에서 부름
    public function getUntakenReward($user_PK){
        return $this->select("SELECT quest_PK FROM QUEST_ACHIEVE WHERE star_taken = FALSE AND user_PK = ?",[$user_PK]);
    }

    // 특정 유저의 퀘스트 달성 여부. 유저 입장시 불러 클라이언트에 저장해두고 사용하는 용도.
    // daily 퀘스트의 경우 오늘 달성했는지 여부를 반환함.
    public function getUserQuestAchieve($user_PK)
    {
        $normal_quest =  $this->select("SELECT QUEST.quest_PK, QUEST.quest_name, QUEST.quest_type, COUNT(QA.quest_achieve_PK) AS finished
                                FROM QUEST
                                         LEFT JOIN QUEST_ACHIEVE QA on QUEST.quest_PK = QA.quest_PK AND QA.user_PK = ?
                                where delete_date is null AND quest_type = 'normal'
                                GROUP BY QUEST.quest_PK",[$user_PK]);
        $daily_qeust = $this->select("SELECT QUEST.quest_PK, QUEST.quest_name, QUEST.quest_type, COUNT(QA.quest_achieve_PK) AS finished
                                FROM QUEST
                                         LEFT JOIN QUEST_ACHIEVE QA on QUEST.quest_PK = QA.quest_PK AND QA.user_PK = ? AND DATE_FORMAT(QA.reg_date,'%Y-%m-%d') = CURDATE()
                                where delete_date is null AND quest_type = 'daily'
                                GROUP BY QUEST.quest_PK",[$user_PK]);
        $user_quest_achieve = array_merge($daily_qeust,$normal_quest);
        return $user_quest_achieve;
    }

    // 퀘스트 달성 등록
    public function insertQuestAchieve($params = [])
    {
        return $this->insert("INSERT INTO QUEST_ACHIEVE(user_PK, quest_PK) VALUES (?,?);",$params);
    }

    // 퀘스트 달성 정보 조회
    public function getQuestAchieveRow($quest_achieve_PK)
    {
        return $this->select("SELECT * FROM QUEST_ACHIEVE WHERE quest_achieve_PK = ?;",[$quest_achieve_PK]);
    }

    //퀘스트 스타 정보 조회
    public function getQuestStarNum($quest_PK)
    {
        return $this->select("SELECT star_num FROM QUEST WHERE quest_PK = ?;",[$quest_PK]);
    }

    // 퀘스트 달성으로 얻은 스타 수령한 것으로 업데이트
    public function updateAchieveStartTaken($quest_achieve_PK)
    {
        return $this->update("UPDATE QUEST_ACHIEVE SET star_taken = 1 WHERE quest_achieve_PK = ?;",[$quest_achieve_PK]);
    }

}