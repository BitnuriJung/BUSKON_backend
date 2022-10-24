<?php
//echo "\r      message controller here";

class MessageController extends BaseController
{

    /**
    * messages
     *  POST : 문자 전송
     * (TODO) GET : 문자목록 상세 조회, DELETE : 문자목록 삭제
    *
    */
    public function messages(){
//        echo "\r messages 펑션";

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
                            $data = json_decode(file_get_contents('php://input'), true);
                            $partner_PK = $data["partner_PK"];
                            $message = $data["message"];
    //                        echo "\r내용 : $message";

                            /** 파라미터 유효성 검사 */
                            if(!isset($partner_PK)||!is_int($partner_PK)
                                || !isset($message)||!is_string($message)
                            ){
                                $str_err_desc = 'Data not valid';
                                $str_err_header = 'HTTP/2 400';
                            }
    //                        echo "\r유효함";

                            /** 상대방과 문자한 적 없는 경우 CHAT_ROOM, CHAT_JOIN, CHAT 모두 추가
                             *  문자한 적 있으면 CHAT 만 추가
                             */

                            /** 상대방과 문자한 적 있는지 확인 */
                            try {
                                $messageModel = new MessageModel();
                                $message_history = $messageModel->getMessageHistory([$user_PK, $partner_PK]);
    //                            echo "\r문자 전적 : $message_history";

                                // 문자한 적 없으므로 CHAT_ROOM, CHAT_JOIN(내꺼, 상대방꺼) 추가
                                if(count($message_history)==0){
                                    //echo "\r문자한 적 없음";
                                    $message_room_PK = $messageModel->insertMessageRoom();
    //                                echo "\r등록된 문자방 번호 : $message_room_PK";
                                    $messageModel->insertMessageJoin([$message_room_PK, $user_PK, $partner_PK]);
                                    $messageModel->insertMessageJoin([$message_room_PK, $partner_PK, $user_PK]);
                                }else{
                                    // 문자한 적 있으면 방번호만 뽑기
                                    $message_room_PK = $message_history[0]['message_room_PK'];
    //                                echo "\r등록된 문자방 번호 : $message_room_PK";
                                }

                                /** 문자한 적 있으나 없으나 문자문자 내용 저장 */
                                $message_PK = $messageModel->insertMessage([$message_room_PK, $user_PK, $message]);
    //                                echo "\r등록된 문자 번호 : $message_PK";

                                $response_data = "Successfully sent message";


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


}

