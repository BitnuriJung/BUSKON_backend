<?php

require_once PROJECT_ROOT_PATH . "/Model/Database.php";

class MusicModel extends Database
{

    /** 음원 정보 등록 */
    public function insertMusic($params = [])
    {
        return $this->insert("INSERT INTO MUSIC(user_PK, music_name, music_singer, music_genre, star_num, music_file_path, music_cover_path, music_composer, music_lyricist, music_arranger, music_featuring, music_desc, music_lyrics)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",$params);
    }

    /** 음원 정보 등록, 수정 후 해당 music row 반환하기 위해 만든 함수 */
    public function getMusicRow($music_PK)
    {
        return $this->select("SELECT * FROM VIEW_MUSIC_APPROVED WHERE music_PK = ?",[$music_PK]);
    }

    /** 가사 수정 */
    public function updateLyrics($params)
    {
        return $this->update("UPDATE MUSIC SET music_lyrics = ?,update_date = NOW()  WHERE music_PK = ?",$params);
    }

    /** 곡 설명 수정 */
    public function updateDesc($params)
    {
        return $this->update("UPDATE MUSIC SET music_desc = ?,update_date = NOW() WHERE music_PK = ?",$params);
    }

    /** 곡 스타 가격 수정 */
    public function updateStarNum($params)
    {
        return $this->update("UPDATE MUSIC SET star_num = ?,update_date = NOW() WHERE music_PK = ?",$params);
    }

    /** 곡 1개 상세정보 조회 (타 유저가 조회함. 해당 곡 추천/구매/위시 여부까지) */
    // 보통은 $params를 array로 받아서 바로 돌리는데, 이 쿼리문에는 동일한 user_PK를 3번 반복해서 써야 해서 특이하게 넣어둠
    public function getMusic($params)
    {
        return $this->select("SELECT MUSIC.*, COUNT(music_reco_PK) AS reco, COUNT(music_prchs_PK) AS prchs, COUNT(music_wish_PK) AS wish FROM MUSIC
                                    LEFT JOIN MUSIC_RECO MR on MUSIC.music_PK = MR.music_PK AND MR.user_PK = ? AND MR.delete_date IS NULL
                                    LEFT JOIN MUSIC_PRCHS MP on MUSIC.music_PK = MP.music_PK AND MP.user_PK = ? AND MP.delete_date IS NULL
                                    LEFT JOIN MUSIC_WISH MW on MUSIC.music_PK = MW.music_PK AND MW.user_PK = ? AND MW.delete_date IS NULL
                                WHERE MUSIC.music_PK = ? GROUP BY MUSIC.music_PK",[$params[0],$params[0],$params[0],$params[1]]);
    }

    /** 곡 구매 (music_prchs 등록) */
    public function insertPrchs($params = [])
    {
        return $this->insert("INSERT INTO MUSIC_PRCHS (music_PK, user_PK, star_num) VALUES (?,?,?)",$params);
    }

    /** 곡 삭제 */
    public function deletePrchs($params=[])
    {
        return $this->update("UPDATE MUSIC_PRCHS SET delete_date = NOW() WHERE music_PK = ? AND user_PK = ?",$params);
    }

    /** 보유곡 갯수 조회 */
    public function getPrchCnt($user_PK)
    {
        return $this->select("SELECT COUNT(*) AS count FROM MUSIC_PRCHS WHERE user_PK = ? AND delete_date IS NULL", [$user_PK]);
    }

    /** 보유곡 정보 조회 (곡 정보 + 추천 + 최애곡 여부)
     * @throws Exception
     */
    // 정보 저장된 view에서 정렬조회
    // 정렬 추가, 변경 얼마든지 가능!
    public function getPrchList($sort,$params=[])
    {
        $query ="SELECT * FROM VIEW_MUSIC_PRCH WHERE mp_user_PK = ? ORDER BY ";
        switch ($sort){

            case "구매최신순":
                $query .= "mp_reg_date DESC";
                break;
            case "발매일순":
                $query .= "reg_date DESC";
                break;
            case "곡명순":
                $query .= "music_name";
                break;
            case "곡명역순":
                $query .= "music_name DESC";
                break;

            default :
                throw new Exception('Not valid sort : 구매일최신순,발매일순,곡명순,곡명역순')
                ;
        }

        $query .= " LIMIT ?,?";
        //echo "\r$query";

        return $this->select($query,$params);
    }

    /** 곡 추천 ROW 정보 조회 */
    public function getRecoRow($params=[])
    {
        return $this->select("SELECT * FROM MUSIC_RECO WHERE music_PK =? AND user_PK = ?",$params);
    }
    /** 곡 추천 */
    public function insertReco($params = [])
    {
        return $this->insert("INSERT INTO MUSIC_RECO (music_PK, user_PK) VALUES (?,?)",$params);
    }

    /** 곡 추천 취소 */
    public function deleteReco($params = [])
    {
        return $this->update("UPDATE MUSIC_RECO SET delete_date = NOW() WHERE music_PK = ? AND user_PK = ?",$params);
    }

    /** 추천 취소한 곡 다시 추천 */
    public function updateReco($music_reco_PK)
    {
        return $this->update("UPDATE MUSIC_RECO SET delete_date = NULL WHERE music_reco_PK = ?",[$music_reco_PK]);
    }
    
    /** 위시 리스트 등록 */
    public function insertWish($params = [])
    {
        return $this->insert("INSERT INTO MUSIC_WISH (music_PK, user_PK) VALUES (?,?)",$params);
    }

    /** 위시 조회 */
    public function getWishRow($params=[])
    {
        return $this->select("SELECT * FROM MUSIC_WISH WHERE music_PK = ? AND user_PK = ?",$params);
    }

    /** 위시에서 삭제 (취소) */
    public function deleteWish($params = [])
    {
        return $this->update("UPDATE  MUSIC_WISH SET delete_date = NOW() WHERE music_PK =? AND user_PK = ?",$params);
    }
    
    /** 위시 다시 등록  */
    public function updateWish($params = [])
    {
        return $this->update("UPDATE MUSIC_WISH SET delete_date = NULL WHERE music_PK =? AND user_PK = ?",$params);
    }

    /** 위시리스트 갯수 조회 */
    public function getWishCnt($user_PK)
    {
        return $this->select("SELECT COUNT(*) AS count FROM MUSIC_WISH WHERE user_PK = ? AND delete_date IS NULL",[$user_PK]);
    }
    
    /** 위시리스트 정보 페이징 조회 (곡 기본정보 + 스타가격) */
    public function getWishList($params=[])
    {
        return $this->select("SELECT MW.music_wish_PK, MW.user_PK AS mw_user_PK, MUSIC.music_PK, MUSIC.user_PK AS m_user_PK, MUSIC.music_name, MUSIC.music_singer, MUSIC.music_genre, MUSIC.music_composer, MUSIC.music_lyricist, MUSIC.music_arranger, MUSIC.music_featuring, MUSIC.music_desc, MUSIC.music_lyrics, MUSIC.star_num, MUSIC.music_file_path, MUSIC.music_cover_path, MUSIC.delete_state,MUSIC.reg_date, MUSIC.delete_due_date
                                FROM MUSIC
                                    INNER JOIN MUSIC_WISH MW on MUSIC.music_PK = MW.music_PK AND MW.user_PK = ? AND MW.delete_date IS NULL
                                WHERE MUSIC.delete_date IS NULL ORDER BY MW.reg_date DESC LIMIT ?,?",$params);
    }

    /** 쇼케이스에 있는 곡수 조회 (업로드 곡 중 승인된 && 삭제하지 않은 곡 수 조회) */
    public function getShowcaseCnt($host_PK)
    {
        return $this->select("SELECT COUNT(*) AS count FROM VIEW_MUSIC_APPROVED WHERE user_PK = ?",[$host_PK]);
    }

    /** 쇼케이스 곡 정보 조회 (곡정보 + 스타정보 + 조회유저 구매/추천/위시 여부*/
    public function getShowcaseList($user_PK, $host_PK, $offset, $limit, $sort)
    {
        $query = "SELECT IFNULL(COUNT(MR.music_reco_PK),0) AS reco,
                   IFNULL(COUNT(MP.music_prchs_PK),0) AS prchs,
                   IFNULL(COUNT(MW.music_wish_PK),0) AS wish,
                   M.*
                   FROM VIEW_MUSIC_APPROVED M
                                LEFT JOIN MUSIC_RECO MR on M.music_PK = MR.music_PK AND MR.delete_date IS NULL AND MR.user_PK = ?
                                LEFT JOIN MUSIC_PRCHS MP on M.music_PK = MP.music_PK AND MP.delete_date IS NULL AND MP.user_PK = ?
                                LEFT JOIN MUSIC_WISH MW on M.music_PK = MW.music_PK AND MW.delete_date IS NULL AND MW.user_PK = ?
                    WHERE M.user_PK = ?
                    GROUP BY M.music_PK, M.reg_date";

        $params = [$user_PK,$user_PK,$user_PK,$host_PK,$offset,$limit];

        switch ($sort){

            case "발매일순":
                $query .= " LIMIT ?,?";
                return $this->select($query,$params);

                break;
            case "발매역순":
                $query .= " ORDER BY M.reg_date DESC LIMIT ?,?";
                return $this->select($query,$params);

                break;
                // 아직 어케할지 모르겠음
//            case "인기순":
//                break;

            default :
                throw new Exception('Not valid sort : 발매일순, 발매역순 (인기순 아직 안됨)');

                break;
        }

    }

    /** (판매상) 음원 목록 조회  */

    /** 최신 50곡 조회
     * VIEW_MUSIC_APPROVED : 승인된 모든 곡이 최신순으로 정렬되어 있는 뷰
     */
    public function getRecentList($music_genre)
    {
        $query = "";
        if($music_genre=="all"){
            return $this->select("SELECT * FROM VIEW_MUSIC_APPROVED LIMIT 0,50",[]);
        }else{
            return $this->select("SELECT * FROM VIEW_MUSIC_APPROVED WHERE music_genre = ? LIMIT 0,50",[$music_genre]);
        }
    }

    /** 특정 음악 추천수 얻기 */
    public function getRecoCnt($music_PK)
    {
        return $this->select("SELECT COUNT(*) AS reco_num FROM MUSIC_RECO WHERE music_PK = ? AND delete_date IS NULL",[$music_PK]);
    }

    /** 특정 음악에 특정 유저가 추천했는지 여부 가져오기 */
    public function getIfUserReco($params = [])
    {
        return $this->select("SELECT COUNT(*) AS reco FROM MUSIC_RECO WHERE music_PK = ? AND user_PK = ? AND delete_date IS NULL",$params);
    }

    /** 특정 음악을 특정 유저가 구매했는지 여부 가져오기 */
    public function getIfUserPrch($params = [])
    {
        return $this->select("SELECT COUNT(*) AS prchs FROM MUSIC_PRCHS WHERE music_PK = ? AND user_PK = ? AND delete_date IS NULL",$params);
    }

    /** 특정 음악을 특정 유저가 위시리스트에 넣었는지 여부 가져오기 */
    public function getIfUserWish($params = [])
    {
        return $this->select("SELECT COUNT(*) AS wish FROM MUSIC_WISH WHERE music_PK = ? AND user_PK = ? AND delete_date IS NULL",$params);
    }

    /** 곡 재생 등록 (70% 이상 재생했을 경우) */
    public function insertPlay($params = [])
    {
        return $this->insert("INSERT INTO MUSIC_PLAY(user_PK,music_PK) VALUES (?,?)",$params);
    }

    public function getPlayRow($music_play_PK)
    {
        return $this->select("SELECT * FROM MUSIC_PLAY WHERE music_play_PK = ?",[$music_play_PK]);
    }
    
    /** 협회장 */

    /** 내 곡 전체 갯수 구하기
     * @param $user_PK : 필수. 조회하는 유저 pk
     * @param $approval_state : all 이라면 전체 목록, 그 외에는 숫자로 0 대기, 1 승인, 2 거절
     * @return mixed
     */
    public function getMyMusicListCnt($user_PK, $approval_state)
    {
        switch ($approval_state){

            case "all":
                return $this->select("SELECT COUNT(*) AS count FROM VIEW_MY_MUSIC WHERE user_PK = ?",[$user_PK]);
                break;
            default :
                return $this->select("SELECT COUNT(*) AS count FROM VIEW_MY_MUSIC WHERE user_PK = ? AND approval_state = ?",[$user_PK,$approval_state]);
                break;
        }
    }

    /** 내 곡 목록 페이징 조회 */
    public function getMyMusicList($user_PK, $approval_state, $sort, $offset, $limit)
    {
        $query = "SELECT * FROM VIEW_MY_MUSIC WHERE user_PK = ?";

        switch ($approval_state){

            case "all":
                $query .= " ORDER BY";

                switch ($sort){

                    case "발매일순":
                        $query .= " reg_date";
                        break;
                    case "재생순":
                        $query .= " play_num";
                        break;
                    case "구매순":
                        $query .= " prchs_num";
                        break;
                    case "추천순":
                        $query .= " reco_num";
                        break;

                    default :
                        throw new Exception('Not valid sort : 발매일순, 재생순, 구매순, 추천순')
                        ;
                }
                $query .= " DESC LIMIT ?,?";
                //echo "\r$query";
                return $this->select($query,[$user_PK,$offset,$limit]);

            default :
                $query .= " AND approval_state = ? ORDER BY";
                switch ($sort){

                    case "발매일순":
                        $query .= " reg_date";
                        break;
                    case "재생순":
                        $query .= " play_num";
                        break;
                    case "구매순":
                        $query .= " prchs_num";
                        break;
                    case "추천순":
                        $query .= " reco_num";
                        break;

                    default :
                        throw new Exception('Not valid sort : 발매일순, 재생순, 구매순, 추천순')
                        ;
                }
                $query .= " DESC LIMIT ?,?";
                //echo "\r$query";
                return $this->select($query,[$user_PK,$approval_state, $offset, $limit]);
        }


    }

    /** 스타수령 ) 곡 재생됐는데 아직 스타 안받은 수 구하기 (한 곡 or 유저 곡 전체)
     * @param $type : all / one -> all 이면 해당 유저 곡 전체, one은 한 곡에 대한 것만
     * @param $user_PK : all 인 경우에만 필수로 사용함. one 인 경우 null
     * @param $music_PK : one 인 경우에만 필수로 사용함. 전체인 경우 null 로 들어오고 사용하지 않음
     * @return mixed : 리턴 데이터는 play_num 하나임 (arr_return[0]['play_num'] 해서 뽑으면 됨)
     */
    public function getPlayStarNotTakenNum($type, $user_PK, $music_PK)
    {
        switch ($type){

            case 'all':
                return $this->select("SELECT COUNT(music_play_PK) AS play_num FROM MUSIC M
                                            INNER JOIN MUSIC_PLAY MP on M.music_PK = MP.music_PK AND star_taken = 0
                                        WHERE M.user_PK = ? AND M.delete_date IS NULL",[$user_PK]);
                break;
            case 'one':
                return $this->select("SELECT COUNT(music_play_PK) AS play_num FROM MUSIC_PLAY WHERE music_PK = ? AND star_taken = 0",[$music_PK]);
                break;
        }
    }

    /**
     * 스타 수령 ) 곡 구매됐는데 아직 스타 안받은 수 구하기 (한곡 or 유저 곡 전체)
     * @param $type : all / one -> all 이면 해당 유저 곡 전체, one은 한 곡에 대한 것만
     * @param $user_PK : all 인 경우에만 필수로 사용함. one 인 경우 null
     * @param $music_PK : one 인 경우에만 필수로 사용함. 전체인 경우 null 로 들어오고 사용하지 않음
     * @return mixed : 리턴 데이터는 prchs_star_sum 하나임 (arr_return[0]['prchs_star_sum'] 해서 뽑으면 됨)
     */
    public function getPrchsStarNotTakenSum($type, $user_PK, $music_PK)
    {
        switch ($type){

            case 'all':
                return $this->select("SELECT IFNULL(SUM(MP.star_num),0) AS prchs_star_sum FROM MUSIC M
                                                    INNER JOIN MUSIC_PRCHS MP on M.music_PK = MP.music_PK AND star_taken = 0
                                        WHERE M.user_PK = ? AND M.delete_date IS NULL",[$user_PK]);
                break;
            case 'one':
                return $this->select("SELECT IFNULL(SUM(star_num),0) AS prchs_star_sum FROM MUSIC_PRCHS WHERE music_PK = ? AND star_taken = 0",[$music_PK]);
                break;

        }
    }

    /**
     * 스타수령 ) 재생곡 모두 스타 받은것으로 처리! (곡 하나 or 유저 전체)
     */
    public function updatePlayStarTaken($type, $user_PK, $music_PK)
    {
        switch ($type){

            case 'all':
                return $this->update("UPDATE MUSIC_PLAY MP
                                            INNER JOIN MUSIC M ON M.music_PK = MP.music_PK AND M.user_PK = ?
                                        SET MP.star_taken = 1 WHERE MP.star_taken = 0",[$user_PK]);
                break;
            case 'one':
                return $this->update("UPDATE MUSIC_PLAY SET star_taken = 1 WHERE music_PK = ? AND star_taken = 0",[$music_PK]);
                break;

        }
    }

    /** 스타 수령 ) 구매곡 모두 스타 받은것으로 처리 (곡 하나 or 유저 전체) */
    public function updatePrchsStarTaken($type, $user_PK, $music_PK)
    {
        switch ($type){

            case 'all':
                return $this->update("UPDATE MUSIC_PRCHS MP
                                            INNER JOIN MUSIC M on MP.music_PK = M.music_PK AND M.user_PK = ?
                                        SET MP.star_taken = 1 WHERE MP.star_taken = 0",[$user_PK]);
                break;
            case 'one':
                return $this->update("UPDATE MUSIC_PRCHS SET star_taken = 1 WHERE music_PK = ? AND star_taken = 0",[$music_PK]);
                break;

        }
    }

    /** 협회장 곡 상세조회 (곡정보 +재생+구매+추천) */
    public function getMyMusic($music_PK)
    {
        return $this->select("SELECT * FROM VIEW_MY_MUSIC WHERE music_PK = ?",[$music_PK]);
    }

    /** 전체 반응 조회 - 내 곡 전체 수, 승인대기곡 수, 재생/구매/추천수 */
    public function getMyMusicSummary($user_PK)
    {
        return $this->select("SELECT
                                COUNT(*) AS music_num,
                                COUNT(IF(approval_state=1, music_PK, NULL)) AS music_waiting_num,
                                SUM(play_num) AS play_num,
                                   SUM(prchs_num) AS prchs_num,
                                   SUM(reco_num) AS reco_num
                            FROM VIEW_MY_MUSIC WHERE user_PK=?",[$user_PK]);
    }

    /** 곡 최애 선정 수 구하기
     * @param $type : all 이면 유저별, one 이면 곡별 선정수 구하기
     * @param $user_PK : all 이면 필수
     * @param $music_PK : one 이면 필수
     * @return mixed : favorite_num
     */
    public function getFavoriteNum($type, $user_PK,$music_PK)
    {
        switch ($type){

            case 'one':
                return $this->select("SELECT COUNT(*) AS favorite_num FROM USER WHERE favorite_music_PK = ?",[$music_PK]);
                break;
            case 'all':
                return $this->select("SELECT COUNT(*) AS favorite_num FROM USER
                                        INNER JOIN VIEW_MY_MUSIC M on USER.favorite_music_PK = M.music_PK AND M.user_PK = ?",[$user_PK]);
                break;

            default : break;
        }
    }

    /** 재생으로 스타를 받은, 안받은 수 각각 구하기 */
    public function getPlayNum($type, $user_PK, $music_PK)
    {
        switch ($type){
            case 'one':
                return $this->select("SELECT COUNT(IF(star_taken = 0,music_play_PK,NULL)) AS play_num_to_take
                                            , COUNT(IF(star_taken=1,music_play_PK,NULL)) AS play_num_taken
                                        FROM MUSIC_PLAY WHERE music_PK = ?",[$music_PK]);
                break;
            case 'all':
                return $this->select("SELECT
                                            COUNT(IF(star_taken = 0,music_play_PK,NULL)) AS play_num_to_take
                                            ,COUNT(IF(star_taken=1,music_play_PK,NULL)) AS play_num_taken
                                        FROM MUSIC_PLAY
                                        INNER JOIN VIEW_MY_MUSIC M on MUSIC_PLAY.music_PK = M.music_PK AND M.user_PK = ?",[$user_PK]);
                break;

            default : break;
        }
    }

    /** 구매로 받은, 아직 안받은 스타 수 각각 구하기 */
    public function getPrchsStarSum($type, $user_PK, $music_PK)
    {
        switch ($type){
            case 'one':
                return $this->select("SELECT SUM(IF(star_taken = 0,star_num,0)) AS prchs_star_sum_to_take
                                            , SUM(IF(star_taken=1,star_num,0)) AS prchs_star_sum_taken
                                        FROM MUSIC_PRCHS WHERE music_PK = ?",[$music_PK]);
                break;
            case 'all':
                return $this->select("SELECT
                                        SUM(IF(star_taken = 0,MP.star_num,0)) AS prchs_star_sum_to_take
                                        ,SUM(IF(star_taken=1,MP.star_num,0)) AS prchs_star_sum_taken
                                    FROM MUSIC_PRCHS MP
                                    INNER JOIN VIEW_MY_MUSIC M on MP.music_PK = M.music_PK AND M.user_PK = ?",[$user_PK]);
                break;

            default : break;
        }
    }

    /** 플레이리스트 */
    /** 플레이리스트에 음악 넣기 */
    public function addPlaylistMusic($playlist_PK, $music_PK)
    {
        $params = [$playlist_PK, $music_PK, $playlist_PK];
        return $this->insert("INSERT INTO PLAYLIST_MUSIC (playlist_PK, music_PK, playlist_order)
                                VALUES (?,?,(SELECT IFNULL(MAX(playlist_order)+1,1) FROM PLAYLIST_MUSIC B WHERE B.playlist_PK = ?) )",$params);
    }

    /** 플레이리스트에서 음악 빼기 
        update 반환은 affected_cnt. 아예 삭제처리지만 원하는 리턴값이 같으므로 update 함수 돌림
     */
    
    public function deletePlaylistMusic($playlist_music_PK)
    {
        return $this->update("DELETE FROM PLAYLIST_MUSIC WHERE playlist_music_PK = ?",[$playlist_music_PK]);
    }

    /** 관리자) 음원 승인 or 거절 */
    public function updateApprovalState($params)
    {
        return $this->update("UPDATE MUSIC SET approval_state = ?, deny_comment = ?,update_date=NOW() WHERE music_PK = ?",$params);
    }

    /** 관리자) 음원 전체 등록수, 전체 승인/대기/거절 수, 전체 삭제수 */
    public function getSummary()
    {
        return $this->select("SELECT
                                    COUNT(music_PK) AS upload_num,
                                    COUNT(IF(approval_state = 0 and delete_state =0, 1, null)) AS waiting_num,
                                    COUNT(IF(approval_state = 1 and delete_state =0, 1, null)) AS approve_num,
                                    COUNT(IF(approval_state = 2 and delete_state =0, 1, null)) AS deny_num,
                                    COUNT(IF(delete_state =1 or delete_state = 2, 1, null)) AS delete_num
                                FROM MUSIC",[]);
    }

    /** 관리자) 특정 기간 내 업로드된 곡 수 */
    public function getUploadNum($params)
    {
        return $this->select("SELECT COUNT(music_PK) AS upload_num FROM MUSIC WHERE reg_date BETWEEN DATE(?) AND (DATE(?)+1)",$params);
    }

    /** 관리자 ) 음원 비고 내용 업데이트 */
    public function updateAdminComment($params)
    {
        return $this->update("UPDATE MUSIC SET admin_comment = ?, update_date = NOW() WHERE music_PK = ?",$params);
    }

    /** 관리자) 전체 음원 목록 카운트 (필터, 검색어별)
     * @param $filter :  all / 0,1,2 (음원 승인상태를 의미. 순서대로 대기,승인,거절)
     * @param $keyword : 검색어. 없으면 "" 빈칸으로. 검색하면 이름, 가수, 편곡, 피처링, 작사, 작곡에 해당 내용이 있는것으로 검색
     * @return mixed
     */
    public function getAdminListCnt($filter,$keyword)
    {
        switch ($filter){
            // 전체 카운트
            case "all":
                if($keyword==""){
                    return $this->select("SELECT COUNT(*) AS count FROM MUSIC",[]);
                }else{
                    return $this->select("SELECT COUNT(*) AS count FROM MUSIC
                                            WHERE music_name LIKE ?
                                               OR music_singer LIKE ?
                                               OR music_arranger LIKE ?
                                               OR music_featuring LIKE ?
                                               OR music_lyricist LIKE ?
                                               OR music_composer LIKE ?",[$keyword,$keyword,$keyword,$keyword,$keyword,$keyword]);
                }
                break;
            default :
                // 승인상태별 카운트
                if($keyword==""){
                    return $this->select("SELECT COUNT(*) AS count FROM MUSIC WHERE approval_state = ? AND delete_date IS NULL",[$filter]);

                }else{
                    return $this->select("SELECT COUNT(*) AS count FROM MUSIC
                                            WHERE approval_state = ? AND delete_date IS NULL
                                            AND(    music_name LIKE ?
                                                OR music_singer LIKE ?
                                                OR music_arranger LIKE ?
                                                OR music_featuring LIKE ?
                                                OR music_lyricist LIKE ?
                                                OR music_composer LIKE ?)",[$filter,$keyword,$keyword,$keyword,$keyword,$keyword,$keyword]);
                }
                break;
        }
    }

    /** 관리자) 음원 필터별로 페이징 목록 조회 */
    public function getAdminList($filter, $keyword, $params=[])
    {
        switch ($filter){
            case "all":
                if($keyword==""){
                    return $this->select("SELECT * FROM MUSIC LIMIT ?,?",$params);
                }else{
                    for ($i = 0; $i < 6; $i++){
                        array_unshift($params,$keyword);
                    }
                    return $this->select("SELECT * FROM MUSIC
                                            WHERE music_name LIKE ?
                                               OR music_singer LIKE ?
                                               OR music_arranger LIKE ?
                                               OR music_featuring LIKE ?
                                               OR music_lyricist LIKE ?
                                               OR music_composer LIKE ?
                                                LIMIT ?,?",$params);
                }
                break;
            default :
                if($keyword ==""){
                    // filter값 = approval_state값. 따라서 맨 앞 파라미터로 넣어줘야한다.
                    array_unshift($params,$filter);
                    //var_dump($params);
                    return $this->select("SELECT * FROM MUSIC WHERE approval_state = ? LIMIT ?,?",$params);
                }else{
                    for ($i = 0; $i < 6; $i++){
                        array_unshift($params,$keyword);
                    }
                    array_unshift($params,$filter);

                    return $this->select("SELECT * FROM MUSIC
                                            WHERE approval_state = ?
                                              AND(    music_name LIKE ?
                                                OR music_singer LIKE ?
                                                OR music_arranger LIKE ?
                                                OR music_featuring LIKE ?
                                                OR music_lyricist LIKE ?
                                                OR music_composer LIKE ?)
                                            LIMIT ?,?",$params);

                }

                break;
        }
    }

}