<?php
//echo "\r      crystal controller here";

class CrystalController extends BaseController
{

    /**
    * crystals/summary
     * GET : 유저가 크리스탈 뱅킹 들어갔을 때, 상단 정보 (크리스탈 잔액, 누적 후원 크리스탈, 누적 환전 크리스탈)
    */
    public function crystalsSummary()
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
                            //유저가 가진 크리스탈 잔액 조회
                            $crystal_num = $this->getUserCrystalNum($user_PK);

                            try {
                                $crystalModel = new CrystalModel();

                                // 누적 후원받은 크리스탈 갯수 & 환전한 크리스탈 갯수 구하기
                                $donation_crystal = $crystalModel->getWholeDonationIn($user_PK);
                                $donation_crystal_num = intval($donation_crystal[0]['crystal_num']);

                                $exchange_crystal = $crystalModel->getWholeExchange($user_PK);
                                $exchange_crystal_num = intval($exchange_crystal[0]['crystal_num']);

                                // 데이터 리턴
                                $response_data['crystal_num'] = $crystal_num;
                                $response_data['donation_crystal_num'] = $donation_crystal_num;
                                $response_data['exchange_crystal_num'] = $exchange_crystal_num;

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
    * stars
     * POST : 크리스탈로 스타 구매하기. 유저 스타수 올리기 & CRYSTAL_STAR에 내역 넣기
    *
    */
    public function stars(){
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

                        /** 크리스탈로 스타 구매 */
                        case "POST":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $crystal_num = $data["crystal_num"];
                            $star_num = $data['star_num'];

                            /** 파라미터 유효성 검사 */
                            if(!isset($crystal_num)||!is_int($crystal_num)
                               || !isset($star_num)||!is_int($star_num)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    // 크리스탈 구매 내역 추가하기
                                    $crystalModel = new CrystalModel();
                                    $last_insert_id = $crystalModel->insertStar([$user_PK, $crystal_num, $star_num]);

                                    // 크리스탈 구매내역 성공적
                                    if($last_insert_id>0){

                                        // 스타 갯수 증가시키기
                                        // baseController에 있는 함수
                                        // 성공적이면 스타 잔액 반환
                                        $user_star_num = $this->changeStarNum("plus", $user_PK, $star_num);

                                        // 해당유저의 크리스탈 갯수 반환
                                        $user_crystal_num = $this->getUserCrystalNum($user_PK);

                                        // 리턴 데이터
                                        $response_data['response_msg'] = "Crystal star successful";
                                        $response_data['user_crystal_num'] = $user_crystal_num;
                                        $response_data['user_star_num'] = $user_star_num;
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
    * donations
     * POST : 특정 유저에게 크리스탈 후원. TYPE: 공연장 (TODO : 음원 구독 ..)
    *  TODO
     * GET : 후원한, 후원받은 내역 조회
    */
    public function donations()
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
                            break;

                        case "POST":
                            /** 파라미터 변수 선언 */
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $receiver_PK = $data["receiver_PK"];
                            $crystal_num = $data["crystal_num"];
                            $donation_type = $data["donation_type"];
                            $spot_PK = $data["spot_PK"];

                            /** 파라미터 유효성 검사 */
                            if(
                                !isset($receiver_PK)||!is_int($receiver_PK)
                            || !isset($donation_type)||!is_int($donation_type)
                            || !isset($crystal_num)||!is_int($crystal_num)
                            ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                                // 후원 타입에 따라서 다른 처리 진행 ㄱ
                                switch ($donation_type){

                                    case 0:
                                        // 공연 후원일 경우에만 spot PK 유효성 검사 필요
                                        if(!isset($spot_PK)||!is_int($spot_PK)){
                                            $str_err_desc = 'Data not valid';
                                            $str_err_header = 'HTTP/2 400';
                                        }else{
                                           try {
                                               // 후원 처리
                                               $crystalModel = new CrystalModel();
                                               // crystal_num, crystal_balance 같은 값을 넣어주기 위해 crystal_num 변수를 두번 넣어줌
                                               $last_insert_id = $crystalModel->insertDonation([$user_PK,$receiver_PK,$crystal_num,$crystal_num,$donation_type,$spot_PK]);
                                               if($last_insert_id>0){

                                                   // 성공할 경우 보낸 유저의 크리스탈 잔액 더해서 보내기
                                                   // getUserCrystalNum은 BaseController에 있음
                                                   $response_data['response_msg'] = "Donation Successful";
                                                   $response_data['user_crystal_num'] = $this->getUserCrystalNum($user_PK);
                                               }else{
                                                    echo "\r에러 처리 필요";
                                               }
                                           } catch (Exception $e) {
                                               $str_err_desc = $e->getMessage();
                                               $str_err_header = 'HTTP/2 500';
                                           }
                                        }
                                        break;

                                    //TODO 2차 기능, 음원구독이나 기타등등 추가..
                                    case 1:
                                        break;

                                    default :
                                        $str_err_desc = "Invalid donation type";
                                        $str_err_header = "HTTP/2 400";
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