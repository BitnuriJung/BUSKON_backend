<?php
//echo "\r      music controller here";

define("STAR_PER_PLAY", 3);


class MusicController extends BaseController
{

    /**
    * playlists/music
     *  POST : 재생목록) 보유곡을 재생목록에 추가. 받아온 음악 갯수만큼 등록 처리 (없는 음악 pk면 에러반환)
    *   DELETE : 재생목록) 재생목록에서 곡 삭제. 받아온 플레이리스트 음악 갯수만큼 삭제 처리 (없는 플레이리스트 음악 pk면 에러반환)
    */
    public function playlistsMusic()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();


        if ($session) {
        $user_PK = $session['user_PK'];
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {
                        /** 재생목록에 곡 담기 */
                        case "POST":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $playlist_PK = $data["playlist_PK"];
                            $arr_music_PK = $data["arr_music_PK"];

                            if(!isset($arr_music_PK)||!is_array($arr_music_PK) || count($arr_music_PK)==0
                                || !isset($playlist_PK) || !is_int($playlist_PK)
                            ){
                                $str_err_desc = 'Data not valid';
                                $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    $musicModel = new MusicModel();
                                    $affected_cnt = 0;
//
                                    /**  받아온 음악 갯수만큼 등록 처리 (없는 음악 pk면 에러반환) */
                                    for ($i = 0; $i < count($arr_music_PK); $i++){
                                        $music_PK = $arr_music_PK[$i];
                                        $playlist_music_PK = $musicModel->addPlaylistMusic($playlist_PK, $music_PK);
                                        $affected_cnt += 1;
                                        }

                                    $response_data['affected_cnt'] = $affected_cnt;


                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                      $str_err_desc = $e->getMessage();
                                      $str_err_header = 'HTTP/2 500';
                                }
                            }
                            break;
                            
                            /** 재생목록에서 곡 삭제 */
                        case "DELETE":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $arr_playlist_music_PK = $data["arr_playlist_music_PK"];

                            if(!isset($arr_playlist_music_PK)||!is_array($arr_playlist_music_PK) || count($arr_playlist_music_PK)==0
                            ){
                                $str_err_desc = 'Data not valid';
                                $str_err_header = 'HTTP/2 400';
                            }else{

                                try {
                                    $musicModel = new MusicModel();
                                    $affected_cnt = 0;

                                    /** 받아온 플레이리스트 음악 갯수만큼 삭제 처리 (없는 pk면 에러반환) */
                                    for ($i = 0; $i < count($arr_playlist_music_PK); $i++){
                                        $playlist_music_PK = $arr_playlist_music_PK[$i];

                                        $cnt = $musicModel->deletePlaylistMusic($playlist_music_PK);
                                        if($cnt==0){
                                            throw new Error("No such music in playlist");
                                            break;
                                        }else{
                                            $affected_cnt += $cnt;
                                            //echo "\r$affected_cnt";
                                        }

                                    }

                                    $response_data = $affected_cnt;

                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                      $str_err_desc = $e->getMessage();
                                      $str_err_header = 'HTTP/2 500';
                                }
                            }

                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }
        } else {
          $str_err_header = 'HTTP/2 404';
          $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }

    /**
    * my-music/summary
     *  GET : 협회장 ) 내 곡 반응 전체 조회 ( 등록곡, 승인대기곡, 재생/구매/추천/누적스타/최애/받을스타 수 ) | BUSKID 필수 
    *
    */
    public function myMusicSummary()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();


        if ($session) {
        $user_PK = $session['user_PK'];
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        case "GET":
                            try {
                                $musicModel = new MusicModel();

                                /** 내 곡수, 승인대기곡 수, 재생, 구매, 추천수를 불러온다 */
                                $arr_music = $musicModel->getMyMusicSummary($user_PK);
                                $obj_music = $arr_music[0];

                                /** 내 곡이 최애곡으로 선정된 횟수추가 */
                                $arr_music = $musicModel->getFavoriteNum('all', $user_PK,null);
                                $favorite_num = $arr_music[0]['favorite_num'];
                                $obj_music['favorite_num'] = $favorite_num;

                                /** 누적스타수, 받을 스타수 구하기 */

                                // 재생별 스타 받을/받은 수 구해서 PLAY_PER_STAR 와 곱함
                                $arr_music = $musicModel->getPlayNum('all', $user_PK, null);
                                $play_num_to_take = $arr_music[0]['play_num_to_take'];
                                $play_num_taken = $arr_music[0]['play_num_taken'];

                                $play_star_sum_to_take = $play_num_to_take * STAR_PER_PLAY;
                                $play_star_sum_taken = $play_num_taken * STAR_PER_PLAY;
                                //echo "\r$play_star_sum_to_take";
                                //echo "\r$play_star_sum_taken";

                                // 구매별 스타 받을/받은 수 구함
                                $arr_music = $musicModel->getPrchsStarSum('all', $user_PK, null);
                                $prchs_star_sum_to_take = $arr_music[0]['prchs_star_sum_to_take'];
                                $prchs_star_sum_taken = $arr_music[0]['prchs_star_sum_taken'];
                                //echo "\r$prchs_star_sum_to_take";
                                //echo "\r$prchs_star_sum_taken";

                                /** 받은 / 받아야 할 스타수까지 추가해서 반환 */
                                $star_to_take = $play_star_sum_to_take + $prchs_star_sum_to_take;
                                $star_taken = $play_star_sum_taken + $prchs_star_sum_taken;
                                $obj_music['star_taken'] = $star_taken;
                                $obj_music['star_to_take'] = $star_to_take;

                                $response_data = $obj_music;
                            } catch (Exception $e) {
                                $str_err_desc = $e->getMessage();
                                $str_err_header = 'HTTP/2 500';
                            } catch (Error $e){
                                  $str_err_desc = $e->getMessage();
                                  $str_err_header = 'HTTP/2 500';
                            }

                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }
        } else {
          $str_err_header = 'HTTP/2 404';
          $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }
    
    /**
     * my-music
     * GET : 협회장 ) 내 곡 상세조회 (곡 정보 + 곡 반응 - 재생/구매/추천/누적스타/최애/받을스타 수)
     *
     */
    public function myMusic()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();


        if ($session) {
            $user_PK = $session['user_PK'];
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        case "GET":
                            $music_PK = filter_input(INPUT_GET, 'music_PK', FILTER_VALIDATE_INT);

                            /** 파라미터 유효성 검사 */
                            if(!isset($music_PK)||!is_int($music_PK)
                            ){
                                $str_err_desc = 'Data not valid';
                                $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    $musicModel = new MusicModel();

                                    /** 곡 정보 + 추천,재생,구매 수 */
                                    $arr_music = $musicModel->getMyMusic($music_PK);
                                    $obj_music = $arr_music[0];

                                    /** 최애 선정수, 누적스타수, 받을 스타 수 추가하기 */

                                    if($obj_music['approval_state']!=1){
                                        /** 승인상태 아닌 경우 - 최애수,누적,받을 스타 수 모두 0  */
                                        $favorite_num = 0;
                                        $star_to_take = 0;
                                        $star_taken =0;
                                    }else{
                                        /** 승인상태인 경우 최애수,누적,받을 스타 수 조회 */

                                        //최애수
                                        $arr_music = $musicModel->getFavoriteNum('one', $user_PK,$music_PK);
                                        $favorite_num = $arr_music[0]['favorite_num'];

                                        // 재생별 스타 받을/받은 수 구해서 PLAY_PER_STAR 와 곱함
                                        $arr_music = $musicModel->getPlayNum('one', $user_PK, $music_PK);
                                        $play_num_to_take = $arr_music[0]['play_num_to_take'];
                                        $play_num_taken = $arr_music[0]['play_num_taken'];

                                        $play_star_sum_to_take = $play_num_to_take * STAR_PER_PLAY;
                                        $play_star_sum_taken = $play_num_taken * STAR_PER_PLAY;
                                        //echo "\r$play_star_sum_to_take";
                                        //echo "\r$play_star_sum_taken";

                                        // 구매별 스타 받을/받은 수 구함
                                        $arr_music = $musicModel->getPrchsStarSum('one', $user_PK, $music_PK);
                                        $prchs_star_sum_to_take = $arr_music[0]['prchs_star_sum_to_take'];
                                        $prchs_star_sum_taken = $arr_music[0]['prchs_star_sum_taken'];
                                        //echo "\r$prchs_star_sum_taken";
                                        //cho "\r$prchs_star_sum_to_take";

                                        // 받아야 할 스타수
                                        $star_to_take = $play_star_sum_to_take + $prchs_star_sum_to_take;
                                        $star_taken = $play_star_sum_taken + $prchs_star_sum_taken;

                                    }

                                    /** 데이터 반환 */
                                    $obj_music['favorite_num'] = $favorite_num;
                                    $obj_music['star_to_take'] = $star_to_take;
                                    $obj_music['star_taken'] =$star_taken;
                                    $response_data = $obj_music;

                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                }
                            }
                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }
        } else {
            $str_err_header = 'HTTP/2 404';
            $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }

    /**
    * my-music/list
     *  GET : 협회장 ) 유저가 올린 곡 페이징 조회 (삭제한 곡은 조회 불가) | 승인상태별 필터 - all, 0-대기, 1-승인, 2-거절 | 정렬 - 발매일순, 재생순, 구매순, 추천순 ( 한글로 보내면 됨 )
     *          | 곡 정보 + 재생, 추천, 구매 수 / 전체 페이지 수 반환
    *
    */
    public function myMusicList()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();


        if ($session) {
        $user_PK = $session['user_PK'];
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        case "GET":
                            $page_num = filter_input(INPUT_GET, 'page_num', FILTER_VALIDATE_INT);
                            $approval_state = $_GET['approval_state'];
                            $sort = $_GET['sort'];

                            $limit = 15;

                            /** 파라미터 유효성 검사 */
                            if(!isset($page_num)||!is_int($page_num)
                               || !isset($approval_state)
                               || !isset($sort)||!is_string($sort) || strlen($sort)==0
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                              try {
                                  $musicModel = new MusicModel();

                                  /** 전체 숫자 조회 */
                                  $arr_music = $musicModel->getMyMusicListCnt($user_PK, $approval_state);
                                  $total_music_num = $arr_music[0]['count'];
                                  //echo "\r$total_music_num";

                                  $total_pages_num = ceil($total_music_num / $limit);
                                  if ($total_pages_num == 0) {
                                      $total_pages_num = 1;
                                  }

                                  $offset = ($page_num == 1 ? 0 : ($limit * ($page_num - 1)));

                                  /** 유효하지 않은 페이지를 요청한 경우 에러 반환 */
                                  if ($page_num > $total_pages_num || $page_num == 0) {
                                      $str_err_desc = 'Page not found';
                                      $str_err_header = 'HTTP/2 404';
                                  }else{

                                      /** 페이징 음악 데이터 가져오기 */
                                      $arr_music = $musicModel->getMyMusicList($user_PK, $approval_state, $sort, $offset, $limit);

                                      $response_data['total_pages_num'] = $total_pages_num;
                                      $response_data['arr_music'] = $arr_music;

                                  }


                              } catch (Exception $e) {
                                  $str_err_desc = $e->getMessage();
                                  $str_err_header = 'HTTP/2 500';
                              } catch (Error $e){
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                              }
                            }

                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }
        } else {
          $str_err_header = 'HTTP/2 404';
          $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }

    }

    /** 
    * my-music/star
     *  PATCH : 협회장 ) 유저가 올린 곡 하나 OR 전체 스타 수령 하기 | type - all/one 중에 하나 필수. one 이라면 music_PK 필수
     *          | 반환 : 유저 스타 잔액, 유저가 받은 스타 수 (star_taken_num), 스타 수령처리된 row 수
    *
    */
    public function myMusicStar()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();


        if ($session) {
        $user_PK = $session['user_PK'];

            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        case "PATCH":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $type = $data["type"];
                            $music_PK = $data["music_PK"];
                            // music_PK 는 type이 one 일때만 필수. all일때는 필요없음

                            /** 파라미터 유효성 검사 */
                            if(!isset($type)||!is_string($type) || strlen($type)==0
                                || !($type == "all" || $type == "one")
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    $musicModel = new MusicModel();

                                    /** 받아야 할 스타의 갯수를 구한다. (재생, 구매가 발생하면 스타 받아야 함) */

                                    /** 재생으로 받아야 할 스타 수 */
                                    $arr_music = $musicModel->getPlayStarNotTakenNum($type, $user_PK, $music_PK);
                                    $play_num = $arr_music[0]['play_num'];
                                    $play_star_sum = $play_num*STAR_PER_PLAY;
                                    // STAR_PER_PLAY : 뮤직 컨트롤러 최상단에 선언해둠.
                                    //echo "\rplay star sum : $play_star_sum";

                                    /** 구매로 받아야할 스타 수 */
                                    $arr_music = $musicModel->getPrchsStarNotTakenSum($type, $user_PK, $music_PK);
                                    $prchs_star_sum = $arr_music[0]['prchs_star_sum'];
                                    //echo "\rpprchs_star_sum : $prchs_star_sum";

                                    /** 받을 스타 총합 */
                                    $star_to_take_sum = $play_star_sum + $prchs_star_sum;


                                    $musicModel->connection->beginTransaction();

                                    /** 유저 스타 더하기  */
                                    $user_star_num = $this->changeStarNum('plus', $user_PK, $star_to_take_sum);

                                    /** 스타 받은 처리  */

                                    $play_affected_cnt = $musicModel->updatePlayStarTaken($type, $user_PK, $music_PK);
                                    //echo "\r$play_affected_cnt";
                                    $prchs_affected_cnt = $musicModel->updatePrchsStarTaken($type, $user_PK, $music_PK);
                                    //echo "\rprchs affected $prchs_affected_cnt";

                                    $affected_cnt = $play_affected_cnt + $prchs_affected_cnt;

                                    $musicModel->connection->commit();

                                    /** 유저 스타 잔액 & 스타 수령한것으로 업데이트 된 row 수 반환 */
                                    $response_data['user_star_num'] = $user_star_num;
                                    $response_data['star_taken_num'] = $star_to_take_sum;
                                    $response_data['affected_cnt'] = $affected_cnt;


                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                      $str_err_desc = $e->getMessage();
                                      $str_err_header = 'HTTP/2 500';
                                }
                            }
                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }
        } else {
          $str_err_header = 'HTTP/2 404';
          $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }

    /**
    * plays
     *  POST : 곡 재생 등록 (70% 이상 재생했을 경우). 곡 주인 유저는 해당 정보로 곡이 얼마나 재생되었는지 알 수 있고, 일정 STAR를 지급받는다.
    *
    */
    public function plays()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();


        if ($session) {
        $user_PK = $session['user_PK'];

            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        case "POST":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $music_PK = $data["music_PK"];

                            /** 파라미터 유효성 검사 */
                            if(!isset($music_PK)||!is_int($music_PK)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    $musicModel = new MusicModel();

                                    /** 재생 정보 등록 */
                                    $music_play_PK = $musicModel->insertPlay([$user_PK,$music_PK]);

                                    /** 등록된 재생 정보 반환 */
                                    $arr_music = $musicModel->getPlayRow($music_play_PK);
                                    $response_data = $arr_music[0];

                                    
                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                      $str_err_desc = $e->getMessage();
                                      $str_err_header = 'HTTP/2 500';
                                }
                            }
                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }
        } else {
          $str_err_header = 'HTTP/2 404';
          $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }
    

    /**
     * showcase
    * 쇼케이스 조회
     *  GET : 남이 올린 곡 목록 페이징 조회. | 곡 정보 + 추천+위시+구매여부 | 정렬 (발매일순, 발매역순, 인기순)
    *
    */
    public function showcase()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();


        if ($session) {
        $user_PK = $session['user_PK'];
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        /** 쇼케이스 조회 */
                        case "GET":
                            $host_PK = filter_input(INPUT_GET, 'host_PK', FILTER_VALIDATE_INT);
                            $page_num = filter_input(INPUT_GET, 'page_num', FILTER_VALIDATE_INT);
                            $sort = $_GET['sort'];

                            /** 파라미터 유효성 검사 */
                            if(!isset($host_PK)||!is_int($host_PK)
                               || !isset($page_num)||!is_int($page_num)
                                || !isset($sort)||!is_string($sort) || strlen($sort)==0
                            ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                                $limit = 15;
                                try {
                                    $musicModel = new MusicModel();

                                    /** 전체 음악 갯수 & 페이지 수 조회  */
                                    $arr_music = $musicModel->getShowcaseCnt($host_PK);
                                    $total_music_num = $arr_music[0]['count'];
                                    $total_pages_num = ceil($total_music_num / $limit);
                                    if ($total_pages_num == 0) {
                                        $total_pages_num = 1;
                                    }

                                    /** 유효하지 않은 페이지를 요청한 경우 에러 반환 */
                                    if ($page_num > $total_pages_num || $page_num == 0) {
                                        $str_err_desc = 'Page not found';
                                        $str_err_header = 'HTTP/2 404';
                                    }

                                    /** offset & 데이터 가져오기 */
                                    $offset = ($page_num == 1 ? 0 : ($limit * ($page_num - 1)));
                                    $arr_music = $musicModel->getShowcaseList($user_PK, $host_PK, $offset, $limit, $sort);


                                    /** 리스폰스 - 전체 곡수, 전체 페이지수, 결과 음악 목록 */
                                    $response_data['total_music_num'] = $total_music_num;
                                    $response_data['total_pages_num'] = $total_pages_num;
                                    $response_data['arr_music'] = $arr_music;

                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                      $str_err_desc = $e->getMessage();
                                      $str_err_header = 'HTTP/2 500';
                                }
                            }

                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }
        } else {
          $str_err_header = 'HTTP/2 404';
          $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }


    /**
    * music/list
     *  GET : (판매상 ) 음악 페이징 목록 조회. 인기 50, 최신곡, 검색곡 목록 반환. 리미트 15 | 곡 정보 전체 + 해당 유저 추천/위시/구매 여부 (+인기곡은 이전순위, 현재순위까지)
     *        | type은 top, recent, search 3중 하나여야 함 | top, recent 타입이면 genre (장르필터, all 아니면 장르 고유숫자값) 필수 , search 타입이면 keyword 필수.
     *        | 필수 아닌 데이터는 키/값 전체를 보내지 않으면 됨
    */
    public function musicList()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];
        
        $request_method = $_SERVER["REQUEST_METHOD"];
        
        $str_err_desc = '';
        
        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();
        
        
        if ($session) {
        $user_PK = $session['user_PK'];
            //버전 스위치
            switch ($version_key){
            
                case "v1":
            
                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        /** 음원 전체 목록 조회 (판매상) */
                        case "GET":
                            
                            $page_num = filter_input(INPUT_GET, 'page_num', FILTER_VALIDATE_INT);
                            $type = $_GET['type'];
                            $music_genre = $_GET['music_genre'];
                            $keyword = $_GET['keyword'];
                            
                            /** 파라미터 유효성 검사 */
                            if(!isset($page_num)||!is_int($page_num)
                               || !isset($type)||!is_string($type) || strlen($type)==0
                              // || !isset($music_genre) || !isset($keyword)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                                $limit = 15;

                                try {
                                    $musicModel = new MusicModel();

                                    /** 인기 / 최신 / 검색곡 전체 갯수 조회 (없는 페이지 조회 요청 걸러내기 위함)*/
                                    switch ($type){

                                        case "top":
                                            break;

                                        case "recent":
                                            // 최신곡의 경우 갯수 구하는 모델과 데이터 불러오는 모델이 동일.
                                            // 한 쿼리로 모든 데이터를 불러오는 게 아니라 최신 음악 정보를 불러온 후 구매/위시/추천 여부 붙일 것이기 때문. 
                                            // 아래에서 자체 페이징 처리해서 데이터 내보낸다 (최대 50개씩 불러오기 때문에 큰 무리는 아닐것으로...생각함...)
                                            $arr_music = $musicModel->getRecentList($music_genre);
                                            $total_music_num = count($arr_music);
                                            //echo "\rtotal music : ".$total_music_num;

                                            break;

                                        case "search":
                                            break;

                                        default :
                                            throw new Error('Not valid type (only top, recent, search possible)');
                                            // 404를 띄우고 싶은데, 그러면 아래 코드 전체가 실행되므로 ㅠㅠ 500 에러로 던지기로 했다
                                            break;
                                    }

                                    /** (공통) 없는 페이지 요청 걸러내기 & offset 구하기 */
                                    $total_pages_num = ceil($total_music_num / $limit);
                                    if ($total_pages_num == 0) {
                                        $total_pages_num = 1;
                                    }

                                    /** (공통) 유효하지 않은 페이지를 요청한 경우 에러 반환 */
                                    if ($page_num > $total_pages_num || $page_num == 0) {
                                        $str_err_desc = 'Page not found';
                                        $str_err_header = 'HTTP/2 404';
                                    }

                                    $offset = ($page_num == 1 ? 0 : ($limit * ($page_num - 1)));


                                    /** 각 타입별로 response data 각각 반환 */
                                    switch ($type){

                                        case "top":
                                            break;

                                        case "recent":
                                            // 자체 페이징 처리

                                            // array 선언먼저 해줘야 array_puah 가능
                                            $arr_paging_music = [];

                                            // limit 수만큼만 돌게 하고, 최신곡 갯수보다 많은 양을 불러오지는 않게 한다.
                                            for ($i = 0; $i < $limit; $i++){

                                                if(($offset+$i)>=$total_music_num){
                                                    // 최신곡 전체 목록 크기를 넘어가면 멈춘다!
                                                    break;
                                                }else{
                                                    // 최신곡 전체 데이터 중에 필요 데이터만 넣어서 보낸다..ㅎㅎ 자체 페이징!
                                                    $obj_music = $arr_music[$offset+$i];

                                                    /** 추천받은 수 & 해당 유저가 추천했는지 여부, 구매, 위시 여부 추가  */
                                                    $obj_music = $this->addRecoInfo($obj_music['music_PK'],$user_PK, $obj_music);
                                                    $obj_music = $this->addPrchInfo($obj_music['music_PK'],$user_PK, $obj_music);
                                                    $obj_music = $this->addWishInfo($obj_music['music_PK'],$user_PK, $obj_music);

                                                    array_push($arr_paging_music,$obj_music);
                                                }
                                            }

                                            $response_data['total_pages_num'] = $total_pages_num;
                                            $response_data['arr_music'] = $arr_paging_music;

                                            break;

                                        case "search":
                                            break;

                                    }

                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                      $str_err_desc = $e->getMessage();
                                      $str_err_header = 'HTTP/2 500';
                                }

                            }
                            
                            break;
                            
            
                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;
            
                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }
        } else {
          $str_err_header = 'HTTP/2 404';
          $str_err_desc = "Session not exist";
        }
        
        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환
        
            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환
        
            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }

    /**
     * music row(object)를 받아서 특정 음악 추천 수, 특정유저가 그 음악을 추천했는지 여부 추가해줌
     * @param $music_PK : 조회하는 음악 pk. 총 추천수는 reco_num
     * @param $user_PK : 조회하는 유저 pk. 이 유저가 추천을 했으면 reco = 1 , 안했으면 reco = 0
     * @param $obj_music : 반환할 음악 row. reco_num, reco 두개가 추가되어 반환됨
     * @return mixed
     * @throws Exception
     */
    private function addRecoInfo($music_PK,$user_PK, $obj_music){
        try {
            $musicModel = new MusicModel();

            /** 이 음악 추천수(reco_num) 더하기 */
            $arr_music = $musicModel->getRecoCnt($music_PK);
            $reco_num = $arr_music[0]['reco_num'];
            //echo "\r추천수 : $reco_num";
            $obj_music['reco_num'] = $reco_num;

            /** 해당 유저가 추천했는지 여부(reco) 더하기 */
            $arr_music = $musicModel->getIfUserReco([$music_PK,$user_PK]);
            $reco = $arr_music[0]['reco'];
            //echo "\r유저 추천 : ".$reco;
            $obj_music['reco'] = $reco;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());

        } catch (Error $e){
            throw new Error($e->getMessage());

        }

        return $obj_music;

    }

    private function addWishInfo($music_PK,$user_PK,$obj_music){
        try {
            $musicModel = new MusicModel();

            /** 위시리스트에 넣은 곡인지 여부 더해주기 */
            $arr_music = $musicModel->getIfUserWish([$music_PK,$user_PK]);
            $wish = $arr_music[0]['wish'];
            $obj_music['wish'] = $wish;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());

        } catch (Error $e){
            throw new Error($e->getMessage());

        }

        return $obj_music;
    }

    private function addPrchInfo($music_PK,$user_PK,$obj_music){
        try {
            $musicModel = new MusicModel();
            $arr_music = $musicModel->getIfUserPrch([$music_PK,$user_PK]);
            $prch = $arr_music[0]['prch'];
            $obj_music['prch'] = $prch;


        } catch (Exception $e) {
            throw new Exception($e->getMessage());

        } catch (Error $e){
            throw new Error($e->getMessage());

        }

        return $obj_music;
    }



    /**
    * wishes
     *  POST : 위시 등록 (등록할 음악 pk 배열받아서) 한 곡이라도 array에 담아보내야 함.
     * DELETE : 위시 취소 (등록할 음악 pk 배열받아서) 한 곡이라도 array에 담아보내야 함.
    *
    */
    public function wishes()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();


        if ($session) {
        $user_PK = $session['user_PK'];
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        /** 위시리스트 등록 */
                        // 위시목록에서 삭제했다가 다시 하면 UPDATE, 새로 등록하는거면 INSERT
                        case "POST":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $arr_music_PK = $data["arr_music_PK"];

                            /** 파라미터 유효성 검사 */
                            if(!isset($arr_music_PK)|| count($arr_music_PK)==0
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                                try {
                                    $musicModel = new MusicModel();

                                    /** 반복문 돌려서 wish 등록 */
                                    // 위시 취소했던 곡이면 업데이트, 처음이면 새로 등록

                                    $affected_cnt = 0;

                                    for ($i = 0; $i < count($arr_music_PK); $i++){
                                        $music_PK = $arr_music_PK[$i];

                                        $arr_music_wish = $musicModel->getWishRow([$music_PK,$user_PK]);

                                        if(count($arr_music_wish)>0){
                                            // 취소했던 곡인 경우 업데이트
                                            $update_cnt = $musicModel->updateWish([$music_PK,$user_PK]);
                                            $affected_cnt += $update_cnt;

                                        }else{
                                            // 처음 등록하는 경우
                                            $music_wish_PK = $musicModel->insertWish([$music_PK,$user_PK]);
                                            $affected_cnt += 1;
                                        }
                                    }

                                    $response_data['affected_cnt'] = $affected_cnt;
                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                      $str_err_desc = $e->getMessage();
                                      $str_err_header = 'HTTP/2 500';
                                }


                            }
                            break;

                        /** 위시리스트에서 삭제 */
                        case "DELETE":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $arr_music_PK = $data["arr_music_PK"];

                            /** 파라미터 유효성 검사 */
                            if(!isset($arr_music_PK)|| count($arr_music_PK)==0
                            ){
                                $str_err_desc = 'Data not valid';
                                $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    $musicModel = new MusicModel();

                                    /** 반복문 돌려서 삭제 업데이트 진행 */
                                    $affected_cnt = 0;
                                    for ($i = 0; $i <count($arr_music_PK) ; $i++){
                                        $music_PK = $arr_music_PK[$i];
                                        $update_cnt = $musicModel->deleteWish([$music_PK,$user_PK]);
                                        $affected_cnt += $update_cnt;
                                    }

                                    $response_data['affected_cnt'] = $affected_cnt;


                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                      $str_err_desc = $e->getMessage();
                                      $str_err_header = 'HTTP/2 500';
                                }
                            }

                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }
        } else {
          $str_err_header = 'HTTP/2 404';
          $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }

    /**
     * wishes/list
     * GET : 유저 위시 목록 곡을 조회한다, 페이징 조회 , 곡 기본정보 + STAR 가격까지
     *
     */
    public function wishesList()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();


        if ($session) {
            $user_PK = $session['user_PK'];

            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        /** 위시리스트 목록 조회 */
                        case "GET":
                            $page_num = filter_input(INPUT_GET, 'page_num', FILTER_VALIDATE_INT);
                            /** 파라미터 유효성 검사 */
                            if(!isset($page_num)||!is_int($page_num)
                            ){
                                $str_err_desc = 'Data not valid';
                                $str_err_header = 'HTTP/2 400';
                            }else{
                                $limit = 15;

                                try {
                                    $musicModel = new MusicModel();
                                    $arr_music = $musicModel->getWishCnt($user_PK);
                                    $total_music_num = $arr_music = $arr_music[0]['count'];
                                    $total_pages_num = ceil($total_music_num / $limit);
                                    if ($total_pages_num == 0) {
                                        $total_pages_num = 1;
                                    }

                                    /** 유효하지 않은 페이지를 요청한 경우 에러 반환 */
                                    if ($page_num > $total_pages_num || $page_num == 0) {
                                        $str_err_desc = 'Page not found';
                                        $str_err_header = 'HTTP/2 404';
                                    }

                                    $offset = ($page_num == 1 ? 0 : ($limit * ($page_num - 1)));

                                    /** 위시리스트 정보 가져오기 */
                                    $arr_music = $musicModel->getWishList([$user_PK,$offset,$limit]);

                                    /** 전체 위시리스트 곡 수, 페이지 수, 음악 배열 반환 */
                                    $response_data['total_music_num'] = $total_music_num;
                                    $response_data['total_pages_num'] = $total_pages_num;
                                    $response_data['arr_music'] = $arr_music;

                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                }
                            }

                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }
        } else {
            $str_err_header = 'HTTP/2 404';
            $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }

    /**
    * reco
     * POST : 곡 추천 (구매자만 추천 가능, 1곡씩 추천)
     * DELETE : 곡 추천 취소
    *
    */
    public function reco()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();


        if ($session) {
        $user_PK = $session['user_PK'];
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        /** 추천하기 */
                        // 이미 추천했다가 취소한 곡이라면 해당 정보 업데이트 & 그런 적 없으면 정보 insert
                        case "POST":

                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $music_PK = $data["music_PK"];

                            /** 파라미터 유효성 검사 */
                            if(!isset($music_PK)||!is_int($music_PK)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    $musicModel = new MusicModel();

                                    /** 이미 추천했던 곡인지 확인 */
                                    $arr_music_reco = $musicModel->getRecoRow([$music_PK,$user_PK]);

                                    if(count($arr_music_reco)>0){
                                        //이미 추천했던 곡이면 delete_date = null로 수정
                                        $music_reco_PK =  $arr_music_reco[0]['music_reco_PK'];
                                        $musicModel->updateReco($music_reco_PK);

                                    }else{
                                        // 추천한 적 없으면 insert
                                        // 곡 추천 정보 등록
                                        $music_reco_PK = $musicModel->insertReco([$music_PK,$user_PK]);
                                        // 등록된 정보 반환
                                    }

                                    $arr_music_reco = $musicModel->getRecoRow([$music_PK,$user_PK]);
                                    $response_data = $arr_music_reco[0];

                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                      $str_err_desc = $e->getMessage();
                                  	$str_err_header = 'HTTP/2 500';
                                }
                            }
                            break;

                        /** 추천 취소 */
                        case "DELETE":

                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $music_PK = $data["music_PK"];

                            /** 파라미터 유효성 검사 */
                            if(!isset($music_PK)||!is_int($music_PK)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                              try {
                                  $musicModel = new MusicModel();

                                  /** 추천 데이터 삭제처리 */
                                  // delete_date = now()

                                  $musicModel->deleteReco([$music_PK,$user_PK]);
                                  $music_reco_PK = $musicModel->connection->lastInsertId();
                                  //echo "\r$music_reco_PK";

                                  $arr_music_reco = $musicModel->getRecoRow([$music_PK,$user_PK]);
                                  $response_data = $arr_music_reco[0];

                              } catch (Exception $e) {
                                  $str_err_desc = $e->getMessage();
                                  $str_err_header = 'HTTP/2 500';
                              } catch (Error $e){
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                              }
                            }
                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }
        } else {
          $str_err_header = 'HTTP/2 404';
          $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }


    /**
    * prchs
     * POST : 곡 목록 구매 (arr_music : music_PK, star_num 오브젝트로 이루어진 배열) | 없는 음악 PK로 테스트하면 에러 발생함. 반드시 존재하는 음원으로 테스트 하거나 서버에 트랜잭션 처리 꺼달라고 요청할 것
     * DELETE : 보유곡에서 해당 곡 목록 삭제처리
    *
    */
    public function prchs()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();

        if ($session) {
        $user_PK = $session['user_PK'];
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        /** 곡 목록 구매 & 유저 스타 차감 */
                        case "POST":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $arr_music = $data["arr_music"];

                            /** 파라미터 유효성 검사 */
                            if(!isset($arr_music)||!is_array($arr_music) || count($arr_music)==0
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                                // try, catch 두 군데에서 트랜잭션 처리를 위해 musicModel을 다 쓰기 때문에 예외적으로 try문 밖에서부터 선언함
                                $musicModel = new MusicModel();

                                try {

                                    // 유저에게서 차감할 스타 전체 값. 반복문이 돌때 스타값을 계속 더해서 만든다
                                    $whole_star_num = 0 ;

                                    // 곡 구매 성공적으로 처리될때(=music_prchs insert 성공)마다 +1. 총 몇개가 성공적으로 처리되었는지 반환해주기 위함
                                    $affected_cnt = 0;

                                    /** 트랜잭션 처리
                                     * 유저가 음원을 구매 -> 모두 성공시 유저 스타 차감 필수
                                     * 음원 구매 중 하나라도 문제가 발생하는 경우 구매처리 & 스타 차감처리 모두 다 취소될 수 있도록 처리함함
                                     */
                                        $musicModel->connection->beginTransaction();

                                    // 곡 구매 db 처리
                                    // 성공적인 처리 count, 전체 스타합만큼 유저에게 차감하기 위해 값 더함
                                    for ($i = 0; $i < count($arr_music); $i++){
                                        $music_PK = $arr_music[$i]['music_PK'];
                                        $star_num = $arr_music[$i]['star_num'];
                                        $musicModel->insertPrchs([$music_PK,$user_PK,$star_num]);
                                        $whole_star_num += $star_num;
                                        $affected_cnt +=1;
                                    }
                                    //유저 스타 차감 & 유저 스타 잔액
                                    $user_star_num = $this->changeStarNum("minus", $user_PK, $whole_star_num);

                                    $musicModel->connection->commit();
                                    /** 트랜잭션 처리 끝 */

                                    // 유저 스타 잔액 & 처리된 row 갯수 반환
                                    $response_data['user_star_num'] = $user_star_num;
                                    $response_data['affected_cnt'] = $affected_cnt;


                                } catch (Exception $e) {
                                    $musicModel->connection->rollBack();
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                }catch (Error $e){
                                    $musicModel->connection->rollBack();
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                }



                            }


                            break;

                        /** 구매곡 삭제 */
                        case "DELETE":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $arr_music_PK = $data["arr_music_PK"];
                            /** 파라미터 유효성 검사 */
                            if(!isset($arr_music_PK)||!is_array($arr_music_PK) || count($arr_music_PK)==0
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    // 곡 삭제 성공적으로 처리될때(=music_prchs update 성공)마다 +1. 총 몇개가 성공적으로 처리되었는지 반환해주기 위함
                                    $affected_cnt = 0;

                                    // 곡 구매 정보에서 music_pk, user_pk 일치하는 정보 찾아서 delete_date 업데이트
                                    $musicModel = new MusicModel();
                                    for ($i = 0; $i < count($arr_music_PK); $i++){
                                        $music_PK = $arr_music_PK[$i];
                                        $affected_cnt += $musicModel->deletePrchs([$music_PK, $user_PK]);
                                    }

                                    // 성공 횟수 카운트해서 리스폰스
                                    $response_data['affected_cnt'] = $affected_cnt;


                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                 }
                            }
                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }
        } else {
          $str_err_header = 'HTTP/2 404';
          $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }

    /**
     * prchs/list
     *   GET : 보유곡 조회 (곡 정보+추천+최애여부) 페이징, sort값은 에러발생 줄이기 위해 한글로 받기로함.띄어쓰기 없이!'구매최신순''발매일순''곡명순''곡명역순'  | 전체 페이지 수, 전체 곡 수, 곡 array (arr_music) 반환
     */
    public function prchsList()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();


        if ($session) {
            $user_PK = $session['user_PK'];
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        /** 보유곡 조회 (곡 정보+추천+최애여부) 페이징 */
                        case "GET":
                            $page_num = filter_input(INPUT_GET, 'page_num', FILTER_VALIDATE_INT);
                            $sort = $_GET['sort'];

                            /** 파라미터 유효성 검사 */
                            if(!isset($page_num)||!is_int($page_num)
                                || !isset($sort)||!is_string($sort) || strlen($sort)==0
                            ){
                                $str_err_desc = 'Data not valid';
                                $str_err_header = 'HTTP/2 400';
                            }else{

                                $limit = 15;


                                try {
                                    $musicModel = new MusicModel();

                                    /** 전체 페이지 수 구하기 */
                                    $arr_music = $musicModel->getPrchCnt($user_PK);
                                    $total_music_num = $arr_music[0]['count'];
                                    $total_pages_num = ceil($total_music_num / $limit);
                                    if ($total_pages_num == 0) {
                                        $total_pages_num = 1;
                                    }

                                    /** 유효하지 않은 페이지를 요청한 경우 에러 반환 */
                                    if ($page_num > $total_pages_num || $page_num == 0) {
                                        $str_err_desc = 'Page not found';
                                        $str_err_header = 'HTTP/2 404';
                                    }

                                    //offset 구하기 (몇번째부터 조회할지
                                    $offset = ($page_num == 1 ? 0 : ($limit * ($page_num - 1)));

                                    /** 페이징 데이터 가져오기 */
                                    $arr_music = $musicModel->getPrchList($sort,[$user_PK,$offset,$limit]);

                                    /** 전체 곡수, 전체 페이지 수, 보유곡 목록 반환 */
                                    $response_data['total_music_num'] = $total_music_num;
                                    $response_data['total_pages_num'] = $total_pages_num;
                                    $response_data['arr_music'] = $arr_music;

                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                }
                            }

                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }

        } else {
            $str_err_header = 'HTTP/2 404';
            $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }

    /**
    * music
     * POST : 음원정보 등록
     * PATCH : 곡 수정
     * GET : 곡 1개 정보 조회
    *
    */
    public function music()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();

        if ($session) {
        $user_PK = $session['user_PK'];

            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        /** 음원 정보 등록하기 */
                        case "POST":

                            /** 파라미터 변수 선언 */

                            // 필수 데이터들
                            // POST INT 데이터 유효성 검사를 위해 integer 변환
                            $star_num = filter_input(INPUT_POST, 'star_num', FILTER_VALIDATE_INT);
                            $music_genre = filter_input(INPUT_POST, 'music_genre', FILTER_VALIDATE_INT);

                            $music_singer = $_POST["music_singer"];
                            $music_name = $_POST["music_name"];
                            $music_file_path = $_POST["music_file_path"];

                            // 음악 커버 이미지는 아래 둘 중 하나만 있어도 됨
                            // path : 유저가 따로 이미지 설정 안하면 유저 프로필 사진경로가 온다
                            // file : 유저가 따로 이미지 설정한 경우
                            $music_cover_path = $_POST["music_cover_path"];
                            $music_cover_file = $_FILES['music_cover_file'];

                            // 필수 아닌 데이터들
                            $music_composer = $_POST["music_composer"];
                            $music_lyricist = $_POST["music_lyricist"];
                            $music_arranger = $_POST["music_arranger"];
                            $music_featuring = $_POST["music_featuring"];
                            $music_desc = $_POST["music_desc"];
                            $music_lyrics = $_POST["music_lyrics"];

                            /** 파라미터 유효성 검사 */
                            if(
                                !isset($music_genre)||!is_int($music_genre)
                                || !isset($star_num)||!is_int($star_num)
                                || strlen($music_singer)==0 ||!is_string($music_singer)
                                || strlen($music_name)==0 ||!is_string($music_name)
                                || strlen($music_file_path)==0 ||!is_string($music_file_path)
                                || ( strlen($music_cover_path)==0 && $music_cover_file['size']==0 )
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                              try {

                                  // music_cover_path 가 오지 않은 경우 이미지 파일이 온 것.
                                  // 이미지 파일을 music 폴더에 업로드하고 그 경로를 받아온다
                                  if(strlen($music_cover_path)==0){
                                      $music_cover_path = $this->uploadImgFile($music_cover_file, "music");
                                  }

                                  // TODO?
                                  // 만약 비어있는 값은 무조건 NULL값이어야 한다고 클라에서 요청하면
                                  // STRING 길이가 0 인 필수아닌 파라미터는 NULL로 설정하는 코드 만들어서 돌리고 DB에 넣어야 함


                                  $params = [$user_PK,$music_name, $music_singer, $music_genre, $star_num, $music_file_path, $music_cover_path, $music_composer, $music_lyricist, $music_arranger,$music_featuring, $music_desc, $music_lyrics];
                                  $musicModel = new MusicModel();
                                  $music_PK = $musicModel->insertMusic($params);

                                  /** 방금 등록된 음악 정보 리턴 */
                                  $arr_music = $musicModel->getMusicRow($music_PK);
                                  $response_data = $arr_music[0];

                              } catch (Exception $e) {
                                  $str_err_desc = $e->getMessage();
                                  $str_err_header = 'HTTP/2 500';
                              }

                            }
                            break;

                        /** 음원 가사, 설명, 스타가격 수정하기 */
                        case "PATCH":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $music_PK = $data["music_PK"];
                            $type = $data["type"];
                            $content = $data["content"];

                            /** 파라미터 유효성 검사 */
                            if(!isset($music_PK)||!is_int($music_PK)
                                || !isset($type)||!is_string($type)
                                || !isset($content)
                            ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    /** 업데이트 타입에 맞게 각각 실행 */
                                    $musicModel = new MusicModel();
                                    switch ($type){

                                        case 'lyrics':
                                            $effected_cnt = $musicModel->updateLyrics([$content,$music_PK]);
                                            break;
                                        case 'desc':
                                            $effected_cnt = $musicModel->updateDesc([$content,$music_PK]);
                                            break;
                                        case 'star_num':
                                            $effected_cnt = $musicModel->updateStarNum([$content,$music_PK]);

                                            break;

                                        default :
                                            $str_err_desc = 'Not valid type (only "lyrics", "desc", "star_num" possible)';
                                            $str_err_header = 'HTTP/2 400';
                                    }

                                        // 수정된 해당 music 데이터 전송
                                        // 없는 music_PK의 경우 null 값 전송됨
                                        $arr_music = $musicModel->getMusicRow($music_PK);
                                        $response_data = $arr_music[0];


                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                }

                            }

                            break;

                        /** 곡 1개 상세조회 (내가 구매/위시/추천 했는지여부까지) */
                        case "GET":
                            /** 파라미터 변수 선언 */
                            $music_PK = filter_input(INPUT_GET, 'music_PK', FILTER_VALIDATE_INT);

                            /** 파라미터 유효성 검사 */
                            if(!isset($music_PK)||!is_int($music_PK)){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    // 곡 정보 조회하기
                                    // 구매 여부 prch, 추천여부 reco, 추천여부 wish
                                    // 했으면 1, 안했으면 0
                                    $musicModel = new MusicModel();
                                    $arr_music = $musicModel->getMusic([$user_PK,$music_PK]);
                                    $response_data = $arr_music[0];

                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                }
                            }
                            break;

                        // 올바르지 않은 메소드
                        default :
                            $str_err_header = 'HTTP/2 405';
                            $str_err_desc = 'Method not supported';
                            break;
                    }
                    break;

                //올바르지 않은 버전 넘버인 경우
                default :
                    $str_err_desc = "Invalid Version Number";
                    $str_err_header = "HTTP/2 400";
            }

        } else {
          $str_err_header = 'HTTP/2 404';
          $str_err_desc = "Session not exist";
        }

        // send output
        if (!$str_err_desc) {
            // 에러 메시지가 한 번도 생기지 않았다면 성공 반환

            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            // 에러 메시지가 선언된 경우, 에러 반환

            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }


}