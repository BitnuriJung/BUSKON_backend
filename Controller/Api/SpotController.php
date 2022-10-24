<?php
//echo "\r      spot controller here";
define(STAR_PER_CREATING_SPOT,20);

class SpotController extends BaseController
{

    /**
    * audience
     * PATCH : 공연장 입장/퇴장. BUSKID 필수 | 공연장의 audience_num 컬럼에 입,퇴장에 맞게 숫자 +-1 해주는 기능. 관객수 정렬이 가능하게 하기 위해 만들었다.
     *          type - 입장 enter, 퇴장 exit
    *
    */
    public function audience()
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
                            $spot_PK = $data["spot_PK"]; // 입장, 퇴장하는 공연장 고유번호
                            $type = $data['type']; // 입장인지 퇴장인지. enter, exit 둘중 하나여야 함

                            /** 파라미터 유효성 검사 */
                            if(!isset($spot_PK)||!is_int($spot_PK)
                               || !isset($type)||!is_string($type) || strlen($type)==0
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                              try {
                                  $spotModel = new SpotModel();

                                /** 입장, 퇴장인지에 따라 관객수 컬럼 +- 처리 */
                                $affected_cnt = $spotModel->updateAudienceNum($type, $spot_PK);

                                /** 수정된 공연장 정보 반환 */
                                  $arr_spots = $spotModel->getSpotRow($spot_PK);
                                  $response_data = $arr_spots[0];

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
    * effect/list
     * GET : 공연장 열린지 1시간이 안된 모집 효과 목록 + 모집된 인원 불러오기
     *      REQUEST : spot_PK
     *      RESPONSE : arr_spot_effect_list
    */
    public function effectsList()
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
                                $spot_PK = filter_input(INPUT_GET, 'spot_PK', FILTER_VALIDATE_INT);
                                /** 파라미터 유효성 검사 */
                                if(!isset($spot_PK)||!is_int($spot_PK)
                                  ){
                                  $str_err_desc = 'Data not valid';
                                  $str_err_header = 'HTTP/2 400';
                                }else{
                                    // 해당 공연장에 열린지 1시간이 안된 모집 효과 목록 + 모집된 인원 불러오기
                                    try {
                                        $spotModel = new SpotModel();
                                        $arr_effect_list = $spotModel->getEffectList($spot_PK);
                                        // array 데이터 전달
                                        // 결과 없는 경우 빈 array, 클라이언트에서 처리함
                                        $response_data = $arr_effect_list;

                                    } catch (Exception $e) {
                                        $str_err_desc = $e->getMessage() ;
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
    * effect-joins
     * POST : 공연장 내 효과 모집에 참여하기
     *      SPOT_EFFECT_JOIN 컬럼에 집어넣기
     *      참여 인원이 모집 인원만큼 모였다면 해당 모집 (SPOT_EFFECT) 의 공연 효과 발동 컬럼 TRUE 로 업데이트
     *      참여한 유저 star 차감
     *      참여한 효과 모집 정보 + 모집된 인원 (gathered_user_num) + 해당 유저의 스타 잔액(user_star_num) 리턴
    *
    */
    public function effectJoins()
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

                        /** 공연장 효과 모집에 참여 */
                        case "POST":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $spot_effect_PK = $data["spot_effect_PK"];
                            $star_num = $data["star_num"];

                            /** 파라미터 유효성 검사 */
                            if(!isset($spot_effect_PK)||!is_int($spot_effect_PK)
                               || !isset($star_num)||!is_int($star_num)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                                try {
                                    // 모집 효과 참여 추가하기
                                    $spotModel = new SpotModel();
                                    $spot_effect_join_PK = $spotModel->insertEffectJoin([$spot_effect_PK, $user_PK]);

                                    // 모집 효과 참여 성공적이면 모집인원수 확인해서 해당 효과 발동여부 업데이트
                                    if($spot_effect_join_PK>0){
                                        $gathered_user = $spotModel->getEffectGatheredUserNum($spot_effect_PK);
                                        $gathered_user_num = $gathered_user[0]['gathered_user_num'];
                                        $effected_cnt = $spotModel->updateEffectActivation([$gathered_user_num,$spot_effect_PK]);
                                    }

                                    // 참여한 유저 스타 감소시키기
                                    $user_star_num = $this->changeStarNum("minus", $user_PK, $star_num);

                                    // 리턴
                                    //1. 효과 모집 인원 + 효과 모집 정보 리턴
                                    //2. 참여 유저 스타 잔액
                                    $obj_spot_effect = $spotModel->getEffect($spot_effect_PK);
                                    $response_data['user_star_num'] = $user_star_num;
                                    $response_data['spot_effect'] = $obj_spot_effect;

                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage() ;
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
    * effects
     * POST : 공연장 효과 모집 열기.
     *      생성된 효과 모집 고유번호 리턴
    *
    */
    public function effects()
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
//            $user_PK = 42;
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        case "POST":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $spot_PK = $data["spot_PK"];
                            $effect_type = $data["effect_type"];
                            $star_per_user = $data["star_per_user"];
                            $goal_user_num = $data["goal_user_num"];

                            /** 파라미터 유효성 검사 */
                            if(!isset($spot_PK)||!is_int($spot_PK)
                                || !isset($effect_type)||!is_int($effect_type)
                                || !isset($star_per_user)||!is_int($star_per_user)
                                || !isset($goal_user_num)||!is_int($goal_user_num)
                            ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                                try {
                                    // 모집 효과 db 등록
                                    $spotModel = new SpotModel();
                                    $spot_effect_PK = $spotModel->insertEffect([$user_PK,$spot_PK,$effect_type,$star_per_user,$goal_user_num]);

                                    // 등록된 spot_effect row 반환
                                    $arr_spot_effect = $spotModel->getEffectRow($spot_effect_PK);
                                    $response_data = $arr_spot_effect[0];
                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage() ;
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
     * spots/list
     *
     * GET 공연장 목록 조회
     *       페이징 처리해서 전달, 전체 숫자와 전체 페이지 수도 같이 보낸다
     */
    public function spotsList()
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

                            $page_num = filter_input(INPUT_GET, 'page_num', FILTER_VALIDATE_INT);// 조회하려는 페이지
                            $sort = $_GET['sort']; // 정렬. 최신순/관객많은순 둘 중 하나
                            $keyword = $_GET['keyword']; // 검색어. 보낸 내용이 없으면 NULL 임

                            if(!isset($page_num)||!is_int($page_num)
                                || !isset($sort)||!is_string($sort) || strlen($sort)==0
                            ){
                                $str_err_desc = 'Data not valid';
                                $str_err_header = 'HTTP/2 400';
                            }else{
                                // 페이지별 제한은 하드코딩. 나중에 변수로 받을 수도 있다
                                $limit = 4;

                                try {
                                    $spotModel = new spotModel();


                                    // 생성된 방 전체 숫자부터 구하기
                                    // 검색어 없는 경우 공연장 전체 갯수 , 검색어가 있는 경우 유저 닉네임, 공연장 이름에 해당 키워드 있는 갯수
                                    $arr_spots = $spotModel->getSpotsCnt($keyword);
                                    $spots_cnt = $arr_spots[0]['count'];


                                    // 페이지 숫자로 offset 정하기 (몇번째 행부터 조회해와야 하는가)
                                    // TODO : Base Controller 에 넣기? (전체 숫자 & 리밋 보내서 offset받아오기)
                                    $total_pages_num = ceil($spots_cnt / $limit);
                                    if ($total_pages_num == 0) {
                                        $total_pages_num = 1;
                                    }

                                    // 유효하지 않은 페이지를 요청한 경우
                                    if ($page_num > $total_pages_num || $page_num == 0) {
                                        $str_err_desc = 'Page not found';
                                        $str_err_header = 'HTTP/2 404';
                                    }
                                    $offset = ($page_num == 1 ? 0 : ($limit * ($page_num - 1)));

                                    // 정렬값, 검색어에 따라 페이징 처리해서 공연장 정보 가져오기
                                    $arr_spots = $spotModel->getSpotsList($keyword, $sort, $limit, $offset);

                                    // 리스폰스 데이터
                                    // 전체 공연장, 공연장 페이지 수, 해당 페이지의 공연장 정보+유저 닉네임프사 보내기
                                    $response_data['spots_cnt'] = $spots_cnt;
                                    $response_data['total_pages_num'] = $total_pages_num;
                                    $response_data['arr_spots'] = $arr_spots;


                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage() . 'Something went wrong! Please contact support.';
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
            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }

    /**
     * spots
     *
     * GET : 공연장 1개 조회 (상세보기, SNS 홍보시 호출)
     * POST : 공연장 생성
     * PATCH : 공연장 정보 수정
     * DELETE : 공연 종료
     *
     */
    public function spots()
    {

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();
        $user_PK = $session['user_PK'];

        if($session){

            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        /** 공연장 1개 조회  */
                        case "GET":
                                $spot_PK = filter_input(INPUT_GET, 'spot_PK', FILTER_VALIDATE_INT);

                                if(!isset($spot_PK)||!is_int($spot_PK)){
                                    $str_err_desc = 'Data not valid';
                                    $str_err_header = 'HTTP/2 400';
                                }else{

                                    try {
                                        $spotModel = new spotModel();
                                        $spot = $spotModel->getSpotInfo($spot_PK);

                                        if(count($spot)==0){
                                            $str_err_header = 'HTTP/2 404';
                                            $str_err_desc = 'Spot not exist';
                                        }

                                        $response_data = $spot[0];

                                    } catch (Exception $e) {
                                        $str_err_desc = $e->getMessage() . 'Something went wrong! Please contact support.';
                                        $str_err_header = 'HTTP/2 500';
                                    }
                                }


                            break;

                        /** 공연장 생성 */
                        case "POST":

                                /** 파라미터 변수 선언 */
                                $data = json_decode(file_get_contents('php://input'), true);
                                $spot_name = $data["spot_name"];
                                $spot_theme = $data["spot_theme"];
                                $donation_possible = $data["donation_possible"];
                                $adult_only = $data["adult_only"];

                                // 공연장 생성
    //                            $spot_name = $_POST['spot_name'];
    //                            $spot_theme = $_POST['spot_theme'];
    //                            $donation_possible = $_POST['donation_possible'];
    //                            $adult_only = $_POST['adult_only'];


                                // 필요한 데이터가 유효하지 않은 경우 에러 리턴
                                if( !isset($spot_name)||!is_string($spot_name)
                                    || !isset($spot_theme)||!is_int($spot_theme)
                                    || !isset($donation_possible)||!is_int($donation_possible)
                                    || !isset($adult_only)||!is_int($adult_only)
                                ){
                                    $str_err_desc = 'Data not valid';
                                    $str_err_header = 'HTTP/2 400';
                                }else{
                                    try {
                                        /** 공연장 생성 */
                                        $spotModel = new SpotModel();
                                        $spot_PK = $spotModel->insertSpot([$user_PK, $spot_name, $spot_theme, $donation_possible, $adult_only]);

                                        /** 제대로 공연 생성된 경우, 스타 차감 ( 최상단에 정의되어 있음 ) */
                                        if($spot_PK>0){
                                            $star_num = STAR_PER_CREATING_SPOT;
//                                            echo "\rstar : $star_num";
                                            $user_star_num = $this->changeStarNum('minus', $user_PK, $star_num);

                                        /** 생성된 공연장 정보 & 유저 스타 잔액 반환 */
                                        $arr_spot = $spotModel->getSpotRow($spot_PK);
                                        $response_data['user_star_num'] = $user_star_num;
                                        $response_data['obj_spot'] = $arr_spot[0];
                                        }




                                    } catch (Exception $e) {
                                        $str_err_desc = $e->getMessage() . 'Something went wrong! Please contact support.';
                                        $str_err_header = 'HTTP/2 500';
                                    }
                                }

                        break;

                        /** 공연장 정보 수정 (이름, 후원여부) */
                        case "PATCH":

                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $spot_PK = $data["spot_PK"];
                            $spot_name = $data["spot_name"];
                            $donation_possible = $data["donation_possible"];
    //                            echo "\r공연장 이름 : $spot_name";

                            /** 파라미터 유효성 검사 */
                            if(!isset($spot_name)||!is_string($spot_name)
                                || !isset($spot_PK) || !is_int($spot_PK)
                                || !isset($donation_possible) || !is_int($donation_possible)
                                ){
                                $str_err_desc = 'Data not valid';
                                $str_err_header = 'HTTP/2 400';

                            }else{

                                try {
                                    $spotModel = new SpotModel();
                                    $effected_cnt = $spotModel->updateSpot([$spot_name,$donation_possible,$spot_PK]);

                                    if($effected_cnt>0){
                                        // 수정된 공연장 정보 리턴
                                        $arr_spot = $spotModel->getSpotRow($spot_PK);
                                        $response_data = $arr_spot[0];
                                    }else{
                                        $response_data = "Same data, nothing updated";

                                    }
                                } catch (Exception $e) {
                                    $str_err_header = 'HTTP/2 500';
                                    $str_err_desc = $e->getMessage() ;
                                }
                            }

                          break;

                        /** 공연 종료
                         * TODO)
                         * 해당 공연에 총 후원이 얼마나 되었는지
                         * 해당 공연 시작일 보내야하나? 클라, 채팅서버와 함께 이야기해야 함
                        */
                        case "DELETE":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $spot_PK = $data["spot_PK"];

                            /** 파라미터 유효성 검사 */
                            if(!isset($spot_PK) || !is_int($spot_PK)){
                                $str_err_desc = 'Data not valid';
                                $str_err_header = 'HTTP/2 400';
                            }else{

                                try {
                                    // spot 종료 처리 (delete_date 업데이트)
                                    $spotModel = new SpotModel();
                                    $effected_cnt = $spotModel->deleteSpot($spot_PK);

                                    // 성공적으로 종료하면 영향받은 row 수 1 이상
                                    if($effected_cnt>0){
                                        $response_data['response_msg'] = "Successfully finished spot";

                                        // 후원 크리스탈 갯수 받아오기
                                        $crystalModel = new CrystalModel();
                                        $spot_donation = $crystalModel->getSpotDonation($spot_PK);
                                        $crystal_num =  $spot_donation[0]['crystal'];
                                        $response_data['crystal_num'] = intval($crystal_num);

                                    }else{
                                        // 영향받은 row가 없으면 이미 종료된 공연장에 또 종료요청 OR 없는 공연장
                                        $str_err_desc = 'No open spot, check spot_PK';
                                        $str_err_header = 'HTTP/2 404';
                                    }


                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage() ;
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

        // 세션이 존재하지 않는 경우
        }else{
            $str_err_header = 'HTTP/2 401';
            $str_err_desc = 'Session not exist';
        }

        // send output
        if (!$str_err_desc) {
            $this->sendOutput(
                json_encode(array('response' => $response_data)),
                array('Content-Type: application/json', 'HTTP/2 200')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                array('Content-Type: application/json', $str_err_header)
            );
        }
    }


    /**
    * forced-exits
     * GET : 특정 공연장 강퇴여부 조회
     * POST : 특정 공연장에 특정 유저 강퇴
    */
    public function forcedExits(){

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc="";

        /** 세션으로 유저 PK & 비로그인 차단 */
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();

        if ($session) {
        $user_PK = $session['user_PK'];

            //버전 스위치
            switch ($version_key){

                case "v1":


                    /** 세션으로 유저 PK & 비로그인 차단 */
                    //세션 존재하는지 확인하는 코드 (BaseController)
                    $session = $this->checkSession();

                        // 메소드 스위치
                        switch (strtoupper($request_method)) {

                            /** 공연장 강퇴 정보 조회 */
                            case "GET":
                                /** 파라미터 변수 선언 */
//                                $data = json_decode(file_get_contents('php://input'), true);
//                                $spot_PK = $data["spot_PK"];
                                  $spot_PK = $_GET['spot_PK'];
                                try {
                                    // 강퇴여부 조회
                                    $spotModel = new SpotModel();
                                    $forced_exit = $spotModel->getForcedExit([$user_PK,$spot_PK]);


                                    if(count($forced_exit)>0){
                                        $response_data['forced-exits'] = 1;
                                    }else{
                                        $response_data['forced-exits'] = 0;
                                    }
                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage() ;
                                    $str_err_header = 'HTTP/2 500';
                                }
                                break;

                            /** 공연장 강퇴시키기 */
                            case "POST":


                                /** 파라미터 변수 선언 */
                                $data = json_decode(file_get_contents('php://input'), true);
                                $spot_PK = $data["spot_PK"];
                                $exit_user_PK = $data["exit_user_PK"];
//                                echo "\rspot : $spot_PK";

                                /** 파라미터 유효성 검사 */
                                if(
                                    !isset($spot_PK)||!is_int($spot_PK)
                                    || !isset($exit_user_PK)||!is_int($exit_user_PK)
                                ){

                                    $str_err_desc = 'Data not valid';
                                    $str_err_header = 'HTTP/2 400';
                                }else{

                                    /** 강퇴처리 */
                                    try {

                                        $spotModel = new SpotModel();
                                        $last_insert_id = $spotModel->insertForcedExit([$exit_user_PK,$spot_PK]);
//                                        echo "\rlast : $last_insert_id";

                                        // 등록된 강퇴 정보 반환
                                        $arr_forced_exit = $spotModel->getForcedExit([$exit_user_PK,$spot_PK]);
                                        $response_data = $arr_forced_exit[0];
//                                    

                                    } catch (Exception $e) {
                                        $str_err_desc = $e->getMessage() ;
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
        }
        // 세션이 없는 경우
        else {
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

