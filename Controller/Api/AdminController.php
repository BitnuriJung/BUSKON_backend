<?php
//echo "\r      admin controller here";

class AdminController extends BaseController
{

    /**
    * reports
     *  POST : BUSKID 필수 | 신고 등록(BY 유저). 구체적 사유 (report_detail) 제외 모두 필수 데이터. report category (공연장 = 0 클라이언트와 서버 합의하에 숫자 맞춰서 작업하면 됨).
     *         reported_PK 는 상황에 맞게 공연장이면 공연장 PK, 채팅이면 상대 유저 pk
     *  GET : BUSKID 필수 | 신고 목록 페이징 조회. 카테고리는 전체면 all, 공연장만 0 / 상태는 전체 all, 미처리만 0 , 페이지당 수는 15.
     * PATCH : BUSKID 필수 | 신고 처리 상태 변경 (처리<->미처리) | status 미처리->처리 : 1, 처리->미처리 : 0, remarks 비고는 선택. 없으면 안보내면 됨
    *
    */
    public function reports()
    {
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

                    /** 유저 신고 등록. 여기는 일반유저 접근이므로 auth 체크 안함!  */
                    case "POST":
                        /** 세션으로 유저 PK & 비로그인 차단 */
                        //세션 존재하는지 확인하는 코드 (BaseController)
                        $session = $this->checkSession();

                        if ($session) {
                            $user_PK = $session['user_PK'];

                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $reported_PK = $data["reported_PK"]; // 공연장에서 발생하는 신고면 공연장 pk, 음원이면 음원pk
                            $report_category = $data['report_category']; // 공연장 = 0. 클라와 서버 합의 필수
                            $report_spinner = $data['report_spinner']; // 스피너에서 고르는 신고 사유
                            $report_detail = $data['report_detail']; // 구체적인 사유. (필수는 아님?)


                            /** 파라미터 유효성 검사 */
                            //TODO : 구체적 사유도 필수라면 추가해야 함
                            if(!isset($reported_PK)||!is_int($reported_PK)
                               || !isset($report_category)||!is_int($report_category)
                               || !isset($report_spinner)||!is_string($report_spinner) || strlen($report_spinner)==0
                            ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                              try {
                                  $reportModel = new ReportModel();

                                  /** 데이터로 신고 등록 */
                                  $report_PK = $reportModel->insertReport([$user_PK, $reported_PK, $report_category, $report_spinner, $report_detail]);
//                                  echo "\r$report_PK";

                                  /** 등록된 신고 반환 */
                                  $arr_report = $reportModel->getReportRow($report_PK);
                                  $response_data = $arr_report[0];

                              } catch (Exception $e) {
                                  $str_err_desc = $e->getMessage();
                                  $str_err_header = 'HTTP/2 500';
                              } catch (Error $e){
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                              }
                            }

                            // 로그인한 유저가 아닌 경우
                        } else {
                            $str_err_header = 'HTTP/2 404';
                            $str_err_desc = "Session not exist";
                        }
                        break;

                    /** 유저 신고 목록 페이징 조회 */
                    case "GET":

                        /** 세션으로 운영자 외에 접근 차단 */
                        // 운영자 : user 테이블에 user_auth :1 & 세션에 해당 값이 저장됨
                        //세션 존재하는지 확인하는 코드 (BaseController)
                        $session = $this->checkSession();
                        

//                        if ($session&&$session['user_auth']==1) {

                            /** 데이터 처리 시작 */
                            $page_num = filter_input(INPUT_GET, 'page_num', FILTER_VALIDATE_INT);
                            $report_category = $_GET['report_category']; // 전체 all, 공연장 0
                            $report_status = $_GET['report_status']; // 전체 all, 미처리만 0

                            /** 파라미터 유효성 검사 */
                            if(!isset($page_num)||!is_int($page_num)
                               || !isset($report_category)
                               || !isset($report_status)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                                try {
                                    $reportModel = new ReportModel();

                                    /** 카테고리, 처리상태별 전체 숫자 조회 */
                                    $arr_report = $reportModel->getReportsCnt($report_category, $report_status);
                                    $total_reports_num = $arr_report[0]['count'];
                                    
                                    /** offset, 전체 페이지 수 */
                                    $limit = 15;
                                    $total_pages_num = ceil($total_reports_num / $limit);
                                    if ($total_pages_num == 0) {
                                        $total_pages_num = 1;
                                    }

                                    $offset = ($page_num == 1 ? 0 : ($limit * ($page_num - 1)));

                                    /** 유효하지 않은 페이지를 요청한 경우 에러 반환 */
                                    if ($page_num > $total_pages_num || $page_num == 0) {
                                        $str_err_desc = 'Page not found';
                                        $str_err_header = 'HTTP/2 404';
                                    }else{
                                        /** 페이징 신고 목록 가져오기 */
                                        $arr_reports = $reportModel->getReportsList($report_category, $report_status, $offset, $limit);

                                        /** 전체 페이지 수, 신고 페이징 데이터 반환 */
                                        $response_data['total_pages_num'] = $total_pages_num;
                                        $response_data['arr_reports'] = $arr_reports;
                                    }

                                    
                                    
                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                      $str_err_desc = $e->getMessage();
                                      $str_err_header = 'HTTP/2 500';
                                }

                            }


//                        } else {
//                            $str_err_header = 'HTTP/2 403';
//                            $str_err_desc = "Only admin can access";
//                        }
                        break;

                    /** 신고 처리상태 변경 (처리-미처리) */
                    case "PATCH":

                        /** 세션으로 운영자 외에 접근 차단 */
                        // 운영자 : user 테이블에 user_auth :1 & 세션에 해당 값이 저장됨
                        //세션 존재하는지 확인하는 코드 (BaseController)
                        $session = $this->checkSession();

//                        if ($session&&$session['user_auth']==1) {

                            /** 상태변경 처리 시작 */
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $report_PK = $data["report_PK"];

                            $report_status = $data['report_status'];
                            $report_remarks = $data['report_remarks'];

                            /** 파라미터 유효성 검사 */
                            if(
                                !isset($report_PK)||!is_int($report_PK)
                                || !isset($report_status)||!is_int($report_status)
                                || !isset($report_remarks)||!is_string($report_remarks) || strlen($report_remarks)==0

                            ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                                try {
                                    $reportModel = new ReportModel();
                                    /** 신고 상태 변경 */
                                    $affected_cnt = $reportModel->updateReportsStatus([$report_status, $report_remarks, $report_PK]);

                                    if($affected_cnt>0){
                                        // 성공적으로 업데이트시
                                        /** 변경된 신고 row 반환 */
                                        $arr_reports = $reportModel->getReportRow($report_PK);
                                        $response_data = $arr_reports[0];
                                    }else{
                                        // 전달된 데이터가 같은 경우 영향받은 수가 0임
                                        $response_data = "data same, nothing updated";
                                    }

                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                      $str_err_desc = $e->getMessage();
                                      $str_err_header = 'HTTP/2 500';
                                }
                            }

//                        } else {
//                            $str_err_header = 'HTTP/2 403';
//                            $str_err_desc = "Only admin can access";
//                        }
                        break;

//                    case "OPTIONS":
//                        $response_data = 'good';
//                        break;
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
     * GET : 필터별(전체, 승인상태별)/검색어별 등록된 음원 페이징 목록 조회. 리미트는 임의로 15개로 한다. / 조회 결과가 0인 경우 arr_music : [] 빈 어레이가 간다
            / 검색어는 음악 제목,가수,작사,작곡,편곡,피쳐링을 검색한다. 검색어가 없다면 keyword 키/값을 다 보내지 않으면 됨
    *
    */
    public function musicList()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 운영자 외에 접근 차단 */
        // 운영자 : user 테이블에 user_auth :1 & 세션에 해당 값이 저장됨
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();

        if ($session&&$session['user_auth']==1) {
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        /** 음원목록 페이징 조회 */
                        case "GET":
                            $page_num = filter_input(INPUT_GET, 'page_num', FILTER_VALIDATE_INT);
                            $filter = $_GET['filter'];
                            $keyword = $_GET['keyword'];

                            /** 파라미터 유효성 검사 */
                            if(!isset($page_num)||!is_int($page_num)
                                || !isset($filter)||!is_string($filter)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                                $limit = 15;

                                try {
                                    $musicModel = new MusicModel();

                                    if($keyword != NULL){
                                        $keyword = '%'.$keyword.'%';
                                    }else{
                                        $keyword = "";
                                    }

                                    /** 조회하려는 목록별 전체 숫자부터 카운팅 */
                                    // filter 값에 따라 전체 목록 or 승인상태별 목록 전체 숫자 가져옴
                                    // 검색어가 "" 이 아니라 내용이 있으면 검색어 포함 결과를 가져옴
                                    $arr_music = $musicModel->getAdminListCnt($filter,$keyword);
                                    $music_cnt = $arr_music[0]['count'];
                                    //echo "\r전체 카운트 : $music_cnt";

                                    /** 페이지 숫자로 offset 정하기 (몇번째 행부터 조회해와야 하는가) */
                                    // TODO : Base Controller 에 넣기? (전체 숫자 & 리밋 보내서 offset받아오기)
                                    $total_pages_num = ceil($music_cnt / $limit);
                                    if ($total_pages_num == 0) {
                                        $total_pages_num = 1;
                                    }

                                    // 유효하지 않은 페이지를 요청한 경우 에러 반환
                                    // 유효한 페이지 요청한 경우 결과 반환
                                    if ($page_num > $total_pages_num || $page_num == 0) {
                                        $str_err_desc = 'Page not found';
                                        $str_err_header = 'HTTP/2 404';
                                    }else{

                                        $offset = ($page_num == 1 ? 0 : ($limit * ($page_num - 1)));

                                        /** 페이징 처리해서 데이터 가져오기 */
                                        $arr_music = $musicModel->getAdminList($filter, $keyword, [$offset,$limit]);

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
          $str_err_header = 'HTTP/2 403';
          $str_err_desc = "Only admin can access";
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
    *   music/admin-comment
     * PATCH : 비고 등록, 수정, 삭제 모두 하나로 통일
    *
    */
    public function musicAdminComment()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 운영자 외에 접근 차단 */
        // 운영자 : user 테이블에 user_auth :1 & 세션에 해당 값이 저장됨
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();

        if ($session&&$session['user_auth']==1) {
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        case "PATCH":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $music_PK = $data["music_PK"];
                            $content = $data['content'];

                            /** 파라미터 유효성 검사 */
                            if(!isset($music_PK)||!is_int($music_PK)
                               || !isset($content)||!is_string($content)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    // 데이터 업데이트 후 해당 music row 반환
                                    $musicModel = new MusicModel();
                                    $musicModel->updateAdminComment([$content, $music_PK]);
                                    $arr_music = $musicModel->getMusicRow($music_PK);
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
          $str_err_header = 'HTTP/2 403';
          $str_err_desc = "Only admin can access";
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
    * music/upload-num
     * GET : 시작일,종료일 기간을 받아 해당 기간동안 업로드된 곡 갯수 반환
    *
    */
    public function musicUploadNum()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 운영자 외에 접근 차단 */
        // 운영자 : user 테이블에 user_auth :1 & 세션에 해당 값이 저장됨
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();

        if ($session && $session['user_auth']==1) {
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        case "GET":
                            /** 파라미터 변수 선언 */
                            $start_date = $_GET['start_date'];
                            $end_date = $_GET['end_date'];

                            /** 파라미터 유효성 검사 */
                            if(!isset($start_date)||!is_string($start_date) || strlen($start_date)==0
                                || !isset($end_date)||!is_string($end_date) || strlen($end_date)==0
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                                try {
                                    // 기간 내 업로드 곡 조회 및 반환
                                    $musicModel = new MusicModel();
                                    $arr_music = $musicModel->getUploadNum([$start_date,$end_date]);
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
          $str_err_header = 'HTTP/2 403';
          $str_err_desc = "Only admin can access";
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
    * music/summary
    *   GET : 전체 등록곡 수, 승인상태별 수(삭제곡은 제외), 삭제곡 수 조회
    */
    public function musicSummary()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 운영자 외에 접근 차단 */
        // 운영자 : user 테이블에 user_auth :1 & 세션에 해당 값이 저장됨
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();

        if ($session && $session['user_auth']==1) {
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        case "GET":
                            try {
                                /** 조회 & 데이터 리턴. upload_num, waiting_num, approve_num, deny_num, delete_num을 반환한다 */
                                $musicModel = new MusicModel();
                                $arr_music = $musicModel->getSummary();
                                $response_data = $arr_music[0];

                            } catch (Exception $e) {
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
          $str_err_header = 'HTTP/2 403';
          $str_err_desc = "Only admin can access";
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
    * music/approval-state
     * PATCH : 음원 승인 OR 거절
    *
    */
    public function musicApprovalState()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $array_uri = explode('/', $uri);
        $version_key = $array_uri[3];

        $request_method = $_SERVER["REQUEST_METHOD"];

        $str_err_desc = '';

        /** 세션으로 운영자 외에 접근 차단 */
        // 운영자 : user 테이블에 user_auth :1 & 세션에 해당 값이 저장됨
        //세션 존재하는지 확인하는 코드 (BaseController)
        $session = $this->checkSession();

        if ($session&&$session['user_auth']==1) {
            //버전 스위치
            switch ($version_key){

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        case "PATCH":

                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $music_PK = $data["music_PK"];
                            $type = $data['type'];
                            $deny_comment = $data['deny_comment'];

                            /** 파라미터 유효성 검사 */
                            if(!isset($music_PK)||!is_int($music_PK)
                               || !isset($type)||!is_string($type)
                                || ($type=="deny"&&strlen($deny_comment)==0)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                                /** 승인상태 변경값, 유저에게 보낼 문자내용 분기처리 */
                                // 음악 승인 상태값. 0 : 승인 대기 1 : 승인 완료 2 : 승인 거절.
                                $approve_state=0;
                                $message = "";

                                $musicModel = new MusicModel();
                                $arr_music = $musicModel->getMusicRow($music_PK);
                                $row_music = $arr_music[0];
                                $music_name = $row_music['music_name'];
                                $user_PK = $row_music['user_PK'];

                                switch ($type){

                                    case "approve":
                                        $approve_state = 1;
                                        $deny_comment = null;
                                        $message = "등록하신 음원 [$music_name] 승인이 완료되었습니다";
                                        break;
                                    case "deny":
                                        $approve_state = 2;
                                        $message = "등록하신 음원 [$music_name] 승인이 거절되었습니다. 거절사유 : [$deny_comment] 버스콘을 사용해주셔서 감사합니다. 다음 곡은 꼭 승인할 수 있길 바랄게요.";


                                        break;

                                    default :
                                        $str_err_desc = 'Not valid type (only approve, deny possible)';
                                        $str_err_header = 'HTTP/2 400';
                                        break;
                                }

                                //echo "\r$message";
                                try {

                                    /** 승인상태 업데이트 후 해당 수정된 정보 반환 */
                                    $musicModel->updateApprovalState([$approve_state,$deny_comment, $music_PK]);
                                    $arr_music = $musicModel->getMusicRow($music_PK);

                                    /** 유저 문자함으로 관리자 알림 전송 & 전송문자 정보 반환(승인, 거절 결과) */
                                    $row_message = $this->sendAdminMessage($user_PK,$message);

                                    $response_data['music'] = $arr_music[0];
                                    $response_data['message'] = $row_message;

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
          $str_err_header = 'HTTP/2 403';
          $str_err_desc = "Only admin can access";
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
     * 관리자 문자 전송기능
     *  관리자는 partner_PK 0. 관리자가 보낸 문자는 확인을 위해 삭제처리에서 예외시키려고 함
     * @param $receive_user_PK : 문자 전송받을 유저
     * @param $message : 문자 내용
     * @return object : 전송된 문자 row 오브젝트
     * @throws Exception
     */
    protected function sendAdminMessage($receive_user_PK, $message)
    {
        try {
            $messageModel = new MessageModel();
            $message_history = $messageModel->getMessageHistory([$receive_user_PK, 0]);
            //echo "\r관리자 문자 전적 : $message_history";

            // 관리자문자 보낸 적 있는지 여부에 따라 문자 방 만드는 분기처리
            if(count($message_history)==0){


                // 관리자 문자 보낸적 없는 경우 CHAT_ROOM, CHAT_JOIN(내꺼, 상대방꺼) 추가
                 //echo "\r관리자문자 보낸적 없음";
                $message_room_PK = $messageModel->insertMessageRoom();
                //echo "\r등록된 문자방 번호 : $message_room_PK";

                // 문자 join 정보도 추가
                $messageModel->insertMessageJoin([$message_room_PK, $receive_user_PK, 0]);
                $messageModel->insertMessageJoin([$message_room_PK, 0, $receive_user_PK]);
            }else{
                // 채팅한 적 있으면 방번호만 뽑기
                //echo "\r문자한적 있음";
                $message_room_PK = $message_history[0]['message_room_PK'];
                //echo "\r등록된 문자방 번호 : $message_room_PK";
            }

            /** 전송 이력과 상관 없이 문자 내용 저장 */
            $message_PK = $messageModel->insertMessage([$message_room_PK, 0, $message]);
            //echo "\r등록된 문자 번호 : $message_PK";

            /** 전송 문자내용 반환 */
            $arr_message = $messageModel->getMessageRow($message_PK);

            return $arr_message[0];

        } catch (Exception $e) {
            //echo "\r여기 옴 : $e";
            throw new Exception($e->getMessage());
        } catch (Error $e){
            echo "\r$e";
         }


    }

}