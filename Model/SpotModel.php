<?php

require_once PROJECT_ROOT_PATH . "/Model/Database.php";

class SpotModel extends Database
{

    //공연장 정보 수정
    public function updateSpot($params=[]){
        return $this->update("UPDATE SPOT SET spot_name = ?,donation_possible = ? WHERE spot_PK = ?",$params);
    }

    // 공연장에서 강퇴
    public function insertForcedExit($params=[]){
        return $this->insert("INSERT INTO SPOT_FORCED_EXIT(user_PK, spot_PK) VALUES (?,?)",$params);
    }

    // 공연장에서 강퇴당했는지 조회
    public function getForcedExit($params=[]){
        return $this->select("SELECT * FROM SPOT_FORCED_EXIT WHERE user_PK = ? AND spot_PK = ?",$params);
    }

    // 공연장 종료
    public function deleteSpot($spot_PK){
        return $this->update("UPDATE SPOT SET delete_date = NOW() WHERE spot_PK = ? AND delete_date IS NULL",[$spot_PK]);
    }

    //공연장 생성
    public function insertSpot($params){

        return $this->insert("INSERT INTO SPOT (user_PK,spot_name,spot_theme,donation_possible, adult_only) VALUES(?,?,?,?,?)", $params);
    }

    // 공연장 정보 조회
    public function getSpotRow($spot_PK)
    {
        return $this->select("SELECT * FROM SPOT WHERE spot_PK = ? AND delete_date IS NULL",[$spot_PK]);
    }

    /**
     * 공연장 고유 번호로 공연장 정보 조회
     *      공연장 정보 전체 + 만든 유저 PK,닉네임, 프로필 경로
     * @param $spot_PK
     * @return mixed
     */
    public function getSpotInfo($spot_PK){

        return $this->select("SELECT S.*, U.user_PK, U.user_nickname, U.profile_path FROM SPOT S
                                JOIN USER U on U.user_PK = S.user_PK
                                WHERE spot_PK = ? AND S.delete_date IS NULL", [$spot_PK]);
    }

    // 공연장 정보 + 유저 닉네임, 프로필 사진 페이징 리스트 조회
    public function getSpotsList($keyword, $sort, $limit, $offset){

        $query = "SELECT SPOT.spot_PK, SPOT.user_PK, SPOT.spot_name, SPOT.audience_num, SPOT.spot_theme, SPOT.donation_possible, SPOT.adult_only, SPOT.reg_date, U.user_nickname, U.profile_path FROM SPOT
                        LEFT JOIN USER U on SPOT.user_PK = U.user_PK
                    WHERE SPOT.delete_date IS NULL";

        /** 검색어 없는 경우 */
        if(is_null($keyword)){
            // 정렬 값에 따라 order by 값을 달리 준다
            switch ($sort){

                case "최신순":
                    $query .= " ORDER BY SPOT.reg_date DESC";
                    break;
                case "관객많은순":
                    $query .= " ORDER BY SPOT.audience_num DESC";
                    break;
                default:
                    throw new Exception('Not valid sort : 최신순, 관객많은순')
                    ;
            }
            // 파라미터는 리밋, 오프셋뿐이다.
            $params = [$limit, $offset];
        }else{
            /** 검색어 있는 경우 */

            // 쿼리가 인식하도록 앞뒤로 % 추가.
            $keyword = '%'.$keyword.'%';

            // 검색어 쿼리 추가
            $query .= " AND (spot_name LIKE ? OR U.user_nickname LIKE ?)";

            // 정렬 값에 따라 order by 값을 달리 준다
            switch ($sort){

                case "최신순":
                    $query .= " ORDER BY SPOT.reg_date DESC";
                    break;
                case "관객많은순":
                    $query .= " ORDER BY SPOT.audience_num DESC";
                    break;
                default:
                    throw new Exception('Not valid sort : 최신순, 관객많은순')
                    ;
            }

            // 파라미터에 검색어가 들어간다.
            $params = [$keyword,$keyword, $limit, $offset];
        }

        // 페이징 관련 쿼리문까지 추가.
        $query .= " LIMIT ? OFFSET ?";
        return $this->select($query, $params);
    }

    /** 공연장 갯수 반환
     *      검색어 없는 경우 공연장 전체 갯수
     *      검색어가 있는 경우 유저 닉네임, 공연장 이름에 해당 키워드 있는 갯수
     */
    public function getSpotsCnt($keyword){
        if(is_null($keyword)){
            /** 검색어 없는 경우 공연장 전체 갯수 */
            return $this->select("SELECT COUNT(*) AS count FROM SPOT WHERE delete_date IS NULL",[]);

        }else{
            // 쿼리에서 인식할 수 있게 앞뒤 모두 % 붙여서 검색.
            $keyword = '%'.$keyword.'%';
            /** 검색어가 있는 경우 유저 닉네임, 공연장 이름에 해당 키워드 있는 갯수 */
            return $this->select("SELECT COUNT(*) AS count FROM SPOT
                                      LEFT JOIN USER U on SPOT.user_PK = U.user_PK
                                    WHERE SPOT.delete_date IS NULL AND (spot_name LIKE ? OR U.user_nickname LIKE ?)",[$keyword, $keyword]);
        }
    }

    // 공연장 내 효과 모집하기
    public function insertEffect($params=[])
    {
        return $this->insert("INSERT INTO SPOT_EFFECT (user_PK, spot_PK, effect_type, star_per_user, goal_user_num) VALUES (?,?,?,?,?)",$params);
    }

    // 공연장 내 효과 모집 row 반환
    public function getEffectRow($spot_effect_PK)
    {
        return $this->select("SELECT * FROM SPOT_EFFECT WHERE spot_effect_PK =?",[$spot_effect_PK]);
    }
    
    // 공연장 효과 참여하기 
    public function insertEffectJoin($params=[])
    {
        return $this->insert("INSERT INTO SPOT_EFFECT_JOIN (spot_effect_PK, user_PK) VALUES (?,?)",$params);
    }

    // 공연장 효과에 모집된 인원 조회하기
    public function getEffectGatheredUserNum($spot_effect_PK)
    {
        return $this->select("SELECT COUNT(*) AS gathered_user_num FROM SPOT_EFFECT_JOIN WHERE spot_effect_PK = ?",[$spot_effect_PK]);
    }

    //공연장 효과 모집 인원이 다 차면 효과 발동 (effect_activation) true로 업데이트
    public function updateEffectActivation($params=[])
    {
        return $this->update("UPDATE SPOT_EFFECT SET effect_activation = IF (goal_user_num = ?, 1, 0) WHERE spot_effect_PK = ?",$params);
    }

    //공연장 효과 모집 정보 + 그 모집에 모인 인원까지
    public function getEffect($spot_effect_PK)
    {
        return $this->select("SELECT SPOT_EFFECT.*, COUNT(SEJ.spot_effect_join_PK) AS gathered_user_num FROM SPOT_EFFECT
                                LEFT JOIN SPOT_EFFECT_JOIN SEJ on SPOT_EFFECT.spot_effect_PK = SEJ.spot_effect_PK
                                WHERE SPOT_EFFECT.spot_effect_PK =?",[$spot_effect_PK]);
    }

    // 공연장 내 열린지 1시간이 안된 모집 효과 목록 + 모집된 인원 불러오기
    public function getEffectList($spot_PK)
    {
        return $this->select("SELECT SPOT_EFFECT.*, COUNT(SEJ.spot_effect_join_PK) AS gathered_user_num FROM SPOT_EFFECT
                                       LEFT JOIN SPOT_EFFECT_JOIN SEJ on SPOT_EFFECT.spot_effect_PK = SEJ.spot_effect_PK
                                WHERE SPOT_EFFECT.spot_PK = ? AND DATEDIFF(SPOT_EFFECT.reg_date, NOW())<1 AND TIMESTAMPDIFF(HOUR, SPOT_EFFECT.reg_date, NOW())<1
                                GROUP BY SPOT_EFFECT.spot_effect_PK",[$spot_PK]);
    }

    // 공연장 관객수 수정
    public function updateAudienceNum($type, $spot_PK)
    {
        switch ($type){

            case "enter":
                $query = "UPDATE SPOT SET audience_num = audience_num +1 WHERE spot_PK = ?";
                break;
            case "exit":
                $query = "UPDATE SPOT SET audience_num = CASE WHEN audience_num>0 THEN audience_num = audience_num -1 ELSE 0 END WHERE spot_PK = ?";
                break;

            default :
                throw new Exception('Not valid type : enter, exit');
        }

        return $this->update($query,[$spot_PK]);
    }


}