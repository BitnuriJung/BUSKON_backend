<?php
//
//echo "\r        Api/UserController 들어옴 ";
//echo "\r            listAction 선언";


class UserController extends BaseController
{

    /**
    *character
     * PATCH : 유저 캐릭터 PK 변경 (캐릭터 변경)
    *
    */
    public function character()
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

                        /** 캐릭터 변경 */
                        case "PATCH":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $char_PK = $data["char_PK"];

                            /** 파라미터 유효성 검사 */
                            if(!isset($char_PK)||!is_int($char_PK)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    /** 유저가 가진 캐릭터 번호 변경. USER 테이블에서만 변경한다 */
                                    $userModel = new UserModel();
                                    $affected_cnt = $userModel->updateChar([$char_PK,$user_PK]);

                                    /** TODO : 데이터 변경 없는 상황에 뭘 따로 할건지?? */ 
//                                    if($affected_cnt>0){
//                                        echo "\r데이터 변경 있";
//                                    }else{
//                                        echo "\r데이터 변경 없";
//                                    }

                                    /** 유저 정보 반환 */
                                    $arr_user = $userModel->getUserRow($user_PK);
                                    $response_data = $arr_user[0];

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
    * myphone
    *   GET : 유저 마이폰 열기 (문자, 퀘스트에 빨간점 붙여주기용)
     *      보상 받지 않은 퀘스트 존재 여부, 안읽은 문자 여부 확인
    */
    public function myphone(){
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


                        // 유저 마이폰 열기
                        case "GET":

                            try {

                                /** 유저가 받아야 하는 퀘스트 리워드 있는지 여부 */
                                $questModel = new QuestModel();
                                $reward_to_take = $questModel->getUntakenReward($user_PK);
                                $count_reward = count($reward_to_take);
    //                            echo "\r유저 퀘스트 보상받아야 하는 갯수 : $count_reward" ;
                                if($count_reward>0){
                                    $reward_to_take = 1;
                                }else{
                                    $reward_to_take = 0;
                                }

                                /** 유저가 아직 안읽은 문자 있는지 여부 */
                                $messageModel = new MessageModel();
                                $arr_unread_message = $messageModel->getUnreadMessage($user_PK);
                                $count_unread_message = count($arr_unread_message);
    //                            echo "\r안읽은 거 : $count_unread_message 개";
                                if($count_unread_message>0){
                                    $unread_message = 1;
                                }else{
                                    $unread_message = 0;
                                }

                                $response_data['reward_to_take'] = $reward_to_take;
                                $response_data['unread_message'] = $unread_message;



                            } catch (Exception $e) {
                                $str_err_desc = $e->getMessage() ;
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
    * tutorial/finished
     * PATCH : 유저 튜토리얼 진행 완료 처리
     *  튜토리얼 종료시 실행
    */
    public function tutorialFinished(){
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';
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

                            // 해당 유저 튜토리얼 상태 종료로 바꿈
                            try {
                                $userModel = new UserModel();
                                $effected_cnt = $userModel->updateTutorialFinished($user_PK);

                                if ($effected_cnt == 0) {
                                    $str_err_desc = 'Something went wrong!';
                                    $str_err_header = 'HTTP/2 500';
                                }

                                $response_data = "Successfully updated";

                            } catch (Exception $e) {
                                $str_err_desc = $e->getMessage() ;
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
    * quests
     * GET  유저의 모든 퀘스트 달성여부 반환
     *  게임 서비스 입장시 실행
    */
    public function quests(){
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

                        /** 유저의 전체 퀘스트 달성여부 QUEST, QUEST_ACHIEVE 확인후 배열 반환
                         *  - 했으면 1(true), 안했으면 0(false). quest type 에 따라 daily인 경우 오늘 했는지 값을 보냄
                         * 예 )  1:1, 2:0 (1번퀘스트 했음, 2번퀘스트 안함)
                         */
                        case "GET":
                                try {
                                    $questModel = new QuestModel();
                                    $arr_quests = $questModel->getUserQuestAchieve($user_PK);
                                    $response_data = $arr_quests;

                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage() ;
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
     * GET : 쿠키에 BUSKID만 담아서 요청 전송
     * 200 & 유저정보 : 로그인 유저일 경우 유저 정보 반환
     * 404 : 비로그인 유저일 경우
     *
     */
    public function autoLogin(){
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
                                $userModel = new UserModel();
                                $user = $userModel->getUserRow($user_PK);
                                $crystal_num = $this->getUserCrystalNum($user_PK);
                                $user[0]['crystal_num'] = $crystal_num;
                                $response_data = $user;

                            } catch (Exception $e) {
                                $str_err_desc = $e->getMessage() ;
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
     * POST 가입된 유저인지 확인하고 로그인 처리(세션 저장)해주는 함수
     * 200 & 유저정보 : 가입한 유저이며 로그인처리됨
     * 404 : 유저가 존재하지 않음
     *
     */
    public function login(){
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';


        //버전 스위치
        switch ($version_key){

            case "v1":

                // 메소드 스위치
                switch (strtoupper($request_method)) {


                    case "POST":
                        $data = json_decode(file_get_contents('php://input'), true);
                        $user_email = $data["user_email"];
                        if(!isset($user_email)||!is_string($user_email)){
                            $str_err_desc = 'Data not valid';
                            $str_err_header = 'HTTP/2 400';
                        }

                        try {
                            $userModel = new UserModel();
                            $users = $userModel->getUserByEmail($user_email);

                            // delete_date 가 있어도 불러오기 때문에 동일한 이메일을 가진 탈퇴 유저 있을 수 있음
                            // 일단 처리는 나중에 하고 ..
                            if (count($users) > 0) {
                                foreach ($users as $user) {

                                    if (!is_null($user['delete_date'])) {
                                        // 탈퇴날짜가 존재하는 경우
                                        //TODO : 이미 탈퇴한 유저에 대한 처리는 어떻게 할까!?
//                                echo "\r".$user['user_name'];

//                                        echo "\r".$user['delete_date'];
                                        $str_err_desc = "탈퇴한 유저임";
                                        $str_err_header = 'HTTP/2 404';
                                        break;
                                    } else {
                                        // 탈퇴하지 않고 존재하는 유저인 경우
                                        // 세션 저장
                                        $this->saveSession($user['user_PK'], $user['user_auth']);

                                        // 유저 크리스탈 갯수 추가
                                        $user_PK = $user['user_PK'];
                                        $crystal_num = $this->getUserCrystalNum($user_PK);
                                        $user['crystal_num'] = $crystal_num;
                                        // 유저 정보 반환
                                        $response_data = [$user];
                                    }
                                }


                            } else if (count($users) == 0) {
                                $str_err_desc = "User not exist";
                                $str_err_header = 'HTTP/2 404';
                            }


                        } catch (Exception $e) {
                            $str_err_desc = $e->getMessage() ;
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

            //올바르지 않은 버전 넘버
            default :
                $str_err_desc = "Invalid Version Number";
                $str_err_header = "HTTP/2 400";

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


    public function logout(){
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';


        //버전 스위치
        switch ($version_key){

            case "v1":

                // 메소드 스위치
                switch (strtoupper($request_method)) {


                    case "DELETE":
                        //세션 삭제

                        //세션 존재하는지 확인하는 코드 (BaseController)
                        $session = $this->checkSession();

                        if ($session) {

                            $this->unsetSession();
                            $response_data = "Successfully logged out";

                        } else {
                            $str_err_header = 'HTTP/2 404';
                            $str_err_desc = "No session exists";
                        }

                        break;

                    // 올바르지 않은 메소드
                    default :
                        $str_err_header = 'HTTP/2 405';
                        $str_err_desc = 'Method not supported';
                        break;
                }
                break;

            //올바르지 않은 버전 넘버
            default :
                $str_err_desc = "Invalid Version Number";
                $str_err_header = "HTTP/2 400";

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
     * GET : 전체 유저 목록 조회
     * POST : 회원가입
     *          성공시 가입된 유저 정보 return
     * DELETE : 회원 탈퇴

     */
    public function users()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $arrQueryStringParams = $this->getQueryStringParams();
        $str_err_desc = '';
        //버전 스위치
        switch ($version_key){

            case "v1":

                // 메소드 스위치
                switch (strtoupper($request_method)) {

                    // 전체 유저 페이징 조회
                    // 테스트용도.
                    case "GET":

                        try {
                            $userModel = new UserModel();

                            $limit = 10;
                            if (isset($arrQueryStringParams['limit']) && $arrQueryStringParams['limit']) {
                                $limit = $arrQueryStringParams['limit'];
                            }

                            $arrUsers = $userModel->getUsers($limit);
                            $response_data = $arrUsers;

                        } catch (Exception $e) {
                            $str_err_desc = $e->getMessage() ;
                            $str_err_header = 'HTTP/2 500';
                        }
                        break;

                    // 회원가입
                    case "POST":
                        $data = json_decode(file_get_contents('php://input'), true);
                        $social_type = $data['social_type'];

                        // x-www-form-urlencoded 로 전송했을 때 코드
                        //$social_type = $_POST['social_type'];

                        if(!isset($social_type)||!is_string($social_type)){
                            $str_err_desc = 'Data not valid 소셜타입';
                            $str_err_header = 'HTTP/2 400';
                        }

                        /** 소셜 로그인에 따라 분기처리. 일단 구글로그인만 존재함. 향후 카카오, 네이버 추가 가능
                         *유저 이름, 프로필, 이메일, 소셜 고유 id값을 구한 뒤 끝
                         */
                        switch ($social_type) {

                            case 'google':

                                $id_token = $data['id_token'];
                                echo "\rid token : $id_token";
                                // 구글 아이디 토큰. 유효검증을 거져 구글 계정 정보(이메일, 이름, 구글id 등)를 받아온다
                                // x-www-form-urlencoded 로 전송했을 때 코드
                                //$id_token = $_POST['id_token'];
                                if(!isset($id_token)){
                                    $str_err_desc = 'Data not valid';
                                    $str_err_header = 'HTTP/2 400';
                                }

                                /** 구글 로그인 세팅*/
                                $CLIENT_ID = "320466039919-njv87smi4g2v2s86pg5dv900com7oaos.apps.googleusercontent.com";
                                //구글 클라우드 플랫폼에 buskon 서비스에 등록해둔 client_id
                                //320466039919-njv87smi4g2v2s86pg5dv900com7oaos.apps.googleusercontent.com

                                echo "\r구글 회원가입";
                                require_once PROJECT_ROOT_PATH . '/vendor/autoload.php';
                                // 컴포저로 설치한 google 라이브러리 (구글 계정 유효검증을 위해 리콰이어해야 함)
                                // 자세한 내용이 궁금하면 : https://github.com/googleapis/google-api-php-client,https://developers.google.com/identity/sign-in/web/backend-auth 참고)
                                echo "\rautoload 실행";
                                

                                /** 구글 서버에 credential 전송 - 유효한 정보인지 확인*/
                                $client = new Google_Client(['client_id' => $CLIENT_ID]);
//                                $client = new Google_Client(['client_id' => $CLIENT_ID]);  // Specify the CLIENT_ID of the app that accesses the backend
                                echo "\r구글 클라이언트 만들기";

                                try {
                                    $payload = $client->verifyIdToken($id_token);
                                    echo "\rid 토큰 검증 실행";

                                    if ($payload) {
                                        // 유효한 구글 id인 경우
                                        echo "\r유효한 구글 id";
                                        /** 구글 인증 성공 -> 가입에 필요한 데이터 저장 */
                                        // 존재하는 유저인지 확인 후 없으면 회원가입으로 넘어오므로, 이메일로 존재하는 유저인지 확인하는 작업 생략

                                        $social_ID = $payload['sub'];
                                        $email = $payload['email'];
                                        $name = $payload['name'];
                                        $profile_url = $payload['picture'];
                                        $profile_path = $this->uploadImgFromUrl($profile_url, "user");

                                        // TODO : 아래 값이 구글 CLIENT_ID를 포함해야, 내가 사용 승인한 유저라는 것을 확인할 수 있다고 한다.
                                        // 구글 클라우드 플랫폼 - BUSKON 에 등록해둔 아이디로 테스트해본 결과, 같은 값을 가지고 있음
                                        // 포함 안하는 계정은 처리를 안해야되나? 잘 모르겠다 ;;
                                        $aud = $payload['aud'];

                                        /** (공통 처리) 유저 회원가입 처리 */
                                        try {
                                            // 회원가입
                                            $userModel = new UserModel();
                                            $params = [$name, $email, $profile_path, $social_type, $social_ID];
                                            //user_nickname,user_email,profile_path,social_type,social_ID 순서 달라지면 잘못된 데이터 들어가므로 주의
                                            $user_PK = $userModel->insertUsers($params);

                                            // 유저 정보 리턴
                                            $result_user = $userModel->getUserRow($user_PK);
                                            $response_data = $result_user;

                                        } catch (Exception $e) {
                                            $str_err_desc = $e->getMessage() ;
                                            $str_err_header = 'HTTP/2 500';
                                        }

                                    } else {
                                        // 유효하지 않은 구글 ID token인 경우
                                        echo "\r유효하지 않은 구글 id";

                                        $str_err_desc = 'Invalid GOOGLE ID token';
                                        $str_err_header = 'HTTP/1.1 500 Internal Server Error';
                                    }
                                } catch (Exception $e){
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/1.1 500 Internal Server Error';
                                }



                                break;
                            case 'kakao':

                                echo "카카오 로그인~";
                                break;
                        }


                        break;

                    // 회원 탈퇴
                    case "DELETE":
                        $session = $this->checkSession();
                        //$session = array();
                        //$session['user_PK'] = 22;
                        if ($session) {
                            //세션이 존재하는 경우
                            $user_PK = $session['user_PK'];
//                            echo "\ruser pk : " . $user_PK;

                            // 유저 pk로 users DB delete_date 업데이트
                            try {
                                $userModel = new UserModel();
                                $effected_cnt = $userModel->deleteUser($user_PK);

                                if ($effected_cnt == 0) {
                                    $str_err_desc = 'Something went wrong!';
                                    $str_err_header = 'HTTP/2 500';
                                } else {
                                    // DB 업데이트 변경 후 세션 삭제
                                    $this->unsetSession();

                                }
                            } catch (Exception $e) {
                                $str_err_desc = $e->getMessage() ;
                                $str_err_header = 'HTTP/2 500';
                            }

                            $response_data = "Successfully withdraw user";
                        } else {
                            $str_err_header = 'HTTP/2 404';
                            $str_err_desc = "No session exists";
                        }
                        break;

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
                $this->sendOutput(json_encode(array('error' => $str_err_desc)),
                    array('Content-Type: application/json', $str_err_header)
                );
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
    *   PATCH :  회원 정보 수정. 원래는 위 users 에 속해야 맞는데, form-data 를 받아올 수 없는 관계로 어쩔수 없이 이리로 보냄
     *        // 유저 닉네임, 캐릭터 pk 필수. 프로필 경로나 이미지 둘 중 하나는 필수.
                // 프로필 이미지는 수정사항 없으면 아예 안보내야 함!!!!!
    *
    */
    public function user()
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
                            // 유저 닉네임, 캐릭터 pk 필수. 프로필 경로나 이미지 둘 중 하나는 필수.
                            // 프로필 이미지는 수정사항 없으면 아예 안보내야 함.
                            $user_nickname = $_POST['user_nickname'];
                            $char_PK = filter_input(INPUT_POST, 'char_PK', FILTER_VALIDATE_INT);
                            $profile_path = $_POST['profile_path'];
                            $user_profile_file = $_FILES['user_profile_file'];

                            /** 파라미터 유효성 검사 */
                            if(
                                !isset($char_PK)||!is_int($char_PK)
                               || !isset($user_nickname)||!is_string($user_nickname) || strlen($user_nickname)==0
                               || ( !isset($profile_path) && !isset($user_profile_file) )
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                                /** 이미지 수정한 경우 이미지 존재. 저장해서 경로로 바꾸는 작업해야 함. 없으면 패스. 이미지 경로가 왔을 것임. */
                                if(isset($user_profile_file)){
                                    $profile_path = $this->uploadImgFile($user_profile_file, 'user');
//                                    echo "\r$profile_path";
                                }
                                try {
                                    $userModel = new UserModel();
                                    $affected_cnt = $userModel->updateUser([$user_nickname,$char_PK,$profile_path,$user_PK]);

                                    $arr_user = $userModel->getUserRow($user_PK);
                                    $response_data = $arr_user[0];

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

    //deprecated...ㅋㅋㅋㅋㅋ
    public function google()
    {

        $request_method = $_SERVER["REQUEST_METHOD"];
        //echo "여기".strtoupper($request_method);
        // 메소드 검증
        if (strtoupper($request_method) == 'POST') {
            echo "여기";
            /** 구글 서버에 credential 전송 - 유효한 정보인지 확인*/
            $CLIENT_ID = "320466039919-njv87smi4g2v2s86pg5dv900com7oaos.apps.googleusercontent.com";

            $data = json_decode(file_get_contents('php://input'), true);
            $id_token = $data["credential"];
            echo "raw data id :" . $id_token;

            $id_token = $_POST['credential'];
            echo "urlencoded id :" . $id_token;


            $client = new Google_Client(['client_id' => $CLIENT_ID]);  // Specify the CLIENT_ID of the app that accesses the backend
            $payload = $client->verifyIdToken($id_token);
            if ($payload) {
                /** 구글 인증 성공 -> 이미 가입된 유저인지 확인 */
                $google_ID = $payload['sub'];
                $email = $payload['email'];
                $profile_path = $payload['picture'];
                $name = $payload['name'];
                //echo "\rpayload 전체 : ".print_r($payload);

                $userModel = new UserModel();

                // 이미 가입된 유저인지 확인
                $result_user = $userModel->checkExistUser($google_ID);
//                echo "\r 가입된 유저 수 : " . count($result_user);

                // 가입되지 않은 유저라면 회원가입 진행 후 유저 정보 가져와서 반환
                if (count($result_user) == 0) {
                    $params = [$google_ID, $name, $name, $email, $profile_path];
                    $user_PK = $userModel->insertUsers($params);
                    $result_user = $userModel->getUserRow($user_PK);
                    $response_data = json_encode($result_user);

//                    if ($insertedUserCnt < 0) {
//                        // 제대로 insert 실행되지 않은 경우
//                        $str_err_desc = 'user Insert failed';
//                        $str_err_header = 'HTTP/1.1 500 Internal Server Error';
//                    }


                } // 가입된 유저라면 유저 정보 반환
                else {
                    $response_data = json_encode($result_user);
                }


            } // 유효하지 않은 구글 ID token
            else {
                // Invalid ID token
                //echo "유효하지않아";
                $str_err_desc = 'Invalid ID token';
                $str_err_header = 'HTTP/1.1 500 Internal Server Error';
            }
        } // 유효하지 않은 메소드
        else {
            $str_err_header = 'HTTP/1.1 422 Unprocessable Entity';
            // 그냥 번호만 보내도 됨. 그러면 알아서 Unpro~ 길게 나온다. 깔끔하게 보내고 싶거나 커스텀 문구를 보내고 싶다면 텍스트를 수정하면 됨.
            $str_err_desc = 'Method not supported';
            // json 으로 에러 상세 설명 보내는 부분.
        }

        // response 전송
        // 에러가 있는 경우에만 $str_err_header를 작성한다. 아니라면 굿 보냄
        if (!$str_err_header) {
            //The Response object

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
}
