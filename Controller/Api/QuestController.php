<?php
//
//echo "\r        Api/questController 들어옴 ";
//echo "\r            listAction 선언";


class QuestController extends BaseController
{

    /**
     * achieves
     *  POST : 퀘스트 달성 정보 등록. 퀘스트 pk, BUSKID 필수
     *
     */

    public function achieves()
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
            switch ($version_key) {

                case "v1":

                    // 메소드 스위치
                    switch (strtoupper($request_method)) {

                        /** 퀘스트 달성 ! 데이터 insert */
                        case "POST":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $quest_PK = $data['quest_PK'];

                            /** 파라미터 유효성 검사 */
                            if(!isset($quest_PK)||!is_int($quest_PK)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{

                                try {
                                    /** 퀘스트 달성정보 등록 */
                                    $questModel = new QuestModel();
                                    $quest_achieve_PK = $questModel->insertQuestAchieve([$user_PK,$quest_PK]);

                                    $arr_quest_achieve = $questModel->getQuestAchieveRow($quest_achieve_PK);
                                    $response_data = $arr_quest_achieve[0];

                                } catch (Exception $e) {
                                    $str_err_desc = $e->getMessage();
                                    $str_err_header = 'HTTP/2 500';
                                } catch (Error $e){
                                      $str_err_desc = $e->getMessage();
                                      $str_err_header = 'HTTP/2 500';
                                }
                            }
                            break;

                        /** 퀘스트 달성으로 얻은 스타 보상 받기  */
                        case "PATCH":
                            /** 파라미터 변수 선언 */
                            $data = json_decode(file_get_contents('php://input'), true);
                            $quest_PK = $data["quest_PK"];
                            $quest_achieve_PK = $data['quest_achieve_PK'];


                            /** 파라미터 유효성 검사 */
                            if(!isset($quest_PK)||!is_int($quest_PK)
                               ||!isset($quest_achieve_PK)||!is_int($quest_achieve_PK)
                              ){
                              $str_err_desc = 'Data not valid';
                              $str_err_header = 'HTTP/2 400';
                            }else{
                              try {
                                  $questModel = new QuestModel();

                                  /** 퀘스트로 얻어야 하는 스타 갯수부터 구하기 */
                                  $arr_quest = $questModel->getQuestStarNum($quest_PK);
                                  $star_num = $arr_quest[0]['star_num'];
//                                  echo "\rstar : $star_num";

                                  /** 퀘스트 달성정보에서 스타 획득한 것으로 수정 */
                                  $affected_cnt = $questModel->updateAchieveStartTaken($quest_achieve_PK);
//                                  echo "\raff : $affected_cnt";

                                  if($affected_cnt>0){

                                      /** 제대로 업데이트 된 경우에만 유저 스타 올리기 */
                                      $user_star_num = $this->changeStarNum('plus', $user_PK, $star_num);
//                                      echo "\r$user_star_num";

                                      /** 업데이트된 퀘스트 달성정보 반환 */
                                      $arr_quest_achieve = $questModel->getQuestAchieveRow($quest_achieve_PK);
                                      $response_data['user_star_num'] = $user_star_num;
                                      $response_data['obj_quest_achieve'] = $arr_quest_achieve[0];

                                  }else{
                                        throw new Error('quest_achieve update failed (이미 획득한 스타 보상일 수 있음)');
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

}
