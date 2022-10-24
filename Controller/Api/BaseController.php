<?php

//echo "\r          Api/baseController 들어옴 ";
//echo "\r             getUriSegments, getQueryStringParams(),sendOutput(data, httpHeaders=array() 선언";


class BaseController
{

    /**
     * 유저가 가진 스타 수 더하거나 빼는 함수
     * @param $user_PK
     * @param $star_num : 더하거나 뺄 스타 숫자
     * @param $type : 더하기인지 빼기인지 'plus' 'minus'
     * @return mixed : 제대로 동작했다면 해당 유저 스타 잔액을 반환함 / 아니면 오류 메시지 반환
     */
    public function changeStarNum($type, $user_PK, $star_num){
        $userModel = new UserModel();
//        if($userModel->connection->inTransaction()){
//            echo "\r유저 모델 트랜잭션 on";
//        }else{
//            echo "\r유저 모델 트랜잭션 off";
//        }
        switch ($type){

            case "plus":
                $effected_cnt = $userModel->addStarNum([$star_num, $user_PK]);
                break;
            case "minus":
                $effected_cnt = $userModel->minusStarNum([$star_num, $user_PK]);
                break;
        }

        // 문제 없이 변경된 경우
        if($effected_cnt>0){
            $star_num = $this->getUserStarNum($user_PK);
            return $star_num;
        }else{
            throw new Exception( "changeStarNum not success");
        }

    }

    /**
    * 유저가 가진 스타 수를 반환하는 함수
    *  changeStarNum 사용 후에는 반드시 해당 유저의 스타 잔액을 반환하는데, 여러번 쓰기 귀찮아서 만듬
    */
    public function getUserStarNum($user_PK)
    {

        $userModel = new UserModel();
        $arr_star_num = $userModel->getStarNum($user_PK);
        $star_num = $arr_star_num[0]['star_num'];

        return $star_num;
    }


    /**
     * 유저가 가진 크리스탈 갯수를 구하는 함수
     * user, crystal 컨트롤러에서 모두 필요해서 공통함수에 넣음
     * @param $user_PK
     * @return int
     */
    public function getUserCrystalNum($user_PK){

        $crystalModel = new CrystalModel();

        // 구매 크리스탈 잔액, 후원받은 크리스탈 잔액 가져오기
        $prchs_num = $crystalModel->getPrchsBalance($user_PK);
        $donation_in_num = $crystalModel->getDonationInBalance($user_PK);

        $prchs_num = intval($prchs_num[0]['crystal_num']);
        $donation_in_num = intval($donation_in_num[0]['crystal_num']);

        // 두 잔액 합산 = 남은 크리스탈 갯수
        $crystal_num = ($prchs_num+$donation_in_num);

        return $crystal_num;
    }

    public function getRedis()
    {
//        echo "\rgetRedis";
        //외부 레디스 서버와 연동
        $redis = new Redis();
        $redis->connect(REDIS_HOST, 6379);
        $redis->auth(REDIS_PASSWORD);
//            echo "\r레디스 연결확인";
        return $redis;
    }

    /**
     * 1. 세션에 저장된 데이터를 지운다
     * 2. 세션 자체를 지운다. (php-redis 저장소에서 삭제)
     * 3. 브라우저에서 BUSKID 쿠키를 지운다
    */
    public function unsetSession(){
        session_unset();
        session_destroy();
        setcookie('BUSKID', null, time() -3600, '/', '.buskon.tk');
        session_write_close();

        /** 다중 로그인 방지 처리 */
        // 남 세션 삭제 안된다면 ... 이 코드를 살려야 한다
        try {
            //레디스에 유저 할당 세션이 일치하는지 확인
            // = 마지막으로 로그인한 게 이 세션이 맞는지 확인하고 아니라면 로그인 요청해야 함
            // 여러 기기, 브라우저에서 동시접속하면 안되기 때문에 추가한 처리
            $redis = $this->getRedis();
            $key_PK = $_SESSION['user_PK']."_session";
            $redis_data = $redis->get($key_PK);
            $decoded_data = json_decode($redis_data,true);

            $redis_session = $decoded_data['session'];
            $session = strval(session_id());

            if(strcmp($redis_session,$session)!==0){
                // 활성 세션이 지금 세션이 아님 = 따로 처리 필요 없음
            }else{
                // 활성 세션이 이 세션인 경우 세션 정보를 지운다
                $redis->unlink($key_PK);

            }
        }catch (Exception $e){

        }

    }


    /**
     * 유저 로그인 성공시 호출되는 함수
     * 로그인 정보 중 유저 PK와 권한을 세션에 저장한다
     * 유저 활성 세션을 지금 세션, 지금 시간으로 저장한다 -> 오직 하나의 세션만 활성화되어있을 수 있다
     *
     * @throws RedisException
     */
    public function saveSession($user_PK, $user_auth)
    {

//        echo "\rsaveSession 실행";
        // 세션 id 에 값 저장!!! 이게 없으면 버스콘 서비스 모두 사용 못함!  
        $_SESSION['user_PK'] = $user_PK;
        $_SESSION['user_auth'] = $user_auth;

        /** 다중 로그인 방지 처리 */
        try {
            // 활성 로그인 세션 정보 존재하는지 확인
            $redis = $this->getRedis();
            $key_PK = $user_PK."_session";
            $redis_data = $redis->get($key_PK);

            if(!$redis_data){
                // 로그인 정보 없음, 유저 pk에 할당된 세션값 없음

            }else{
                // 로그인 정보 있음
                // 유저 pk에 할당된 활성화 세션 정보 존재

                // 지금 세션과 같은 세션인지 확인
                $decoded_data = json_decode($redis_data,true);
                $redis_session = $decoded_data['session'];
                $session = strval(session_id());

                if(strcmp($redis_session,$session)!==0){
//                    echo strcmp($redis_session,$session);
//                    echo "\r다른 세션임";
//                    echo "\r레디스 세션".$redis_session;
//                    echo "\r지금 세션".$session;

                    // 다른 세션인 경우, 지정시간이 7일이 지나지 않았다면 이 로그인으로 인해 로그아웃된다
                    $now_time = time();
                    $set_time = $decoded_data['set_time'];
                    $time_gap =(int)$now_time-(int)$set_time;
                    //echo "\rtime gap : $time_gap";

                    if($time_gap<(60*60*24*7)){
                        // 7일이 지나지 않았음
                        // TODO : 유저에게 너 다른 곳에서 한 로그인 로그아웃된다~! 알려주려면 여기
                        $redis->unlink('PHPREDIS_SESSION:'.$redis_session);
                    }else{
                        // 7일 지남. 어차피 자동 로그아웃됐을 시간이므로 별도 처리 안함
                    }

                }
            }

            // 지금 로그인하는 세션을 활성 세션으로 저장
            $set_time = time();
            $data['session'] = session_id();
            $data['set_time'] = $set_time;
            $redis->set($key_PK, json_encode($data));

            $redis_data = $redis->get($key_PK);
//            echo "저장된 값 $key_PK :" .$redis_data;

        } catch(RedisException $e) {
            throw $e;

        }

    }

    /**
     * 요청에 유저 권한이 필요하거나 PK가 필요한 경우에 호출되는 함수
     * 이 유저에게 활성화된 세션이 맞는지 확인하고, 아닌 경우 false 리턴
     * 세션 아이디에 유저 정보가 저장되어있는지 확인하고, 권한이 없는 경우 false 리턴
     *
     */
    public function checkSession()
    {
        if (!$_SESSION['user_PK'] || is_null($_SESSION['user_auth'])) {
            //세션에 저장된 데이터가 없는 경우 = 로그인 유저 아님
//            echo "\r".$_SESSION['user_PK'];
//            echo "\r유저 권한 : ".$_SESSION['user_auth'];
//            echo "\r check 비로그인";
            return false;
        } else {
//            echo "\r check 로그인";
//            echo "\r".$_SESSION['user_PK'];
//            echo "\r유저권한 : ".$_SESSION['user_auth'];

            // 세션에 저장된 데이터가 있는 경우 = 비로그인 유저
            return $_SESSION;
        }

    }



    /**
     * Send API output.
     *
     * @param mixed $data
     * @param string $httpHeader
     */
    protected function sendOutput($data, $httpHeaders = array())
    {


        header('Access-Control-Allow-Origin:https://buskontest.gq');
//        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Max-Age: 86400');
        header('Access-Control-Allow-Headers: Content-Type, Origins, X-Auth-Token');

        if (is_array($httpHeaders) && count($httpHeaders)) {
            foreach ($httpHeaders as $httpHeader) {
                header($httpHeader);
            }

        }

        echo $data;
        exit;
    }


    /**
     * uploadImgFromUrl
     *
     * @param $url : 이미지가 저장되어 있는 url 주소
     * @param $directory : user, spot 중 하나. 어디에 속하는 이미지인지 구분하고 해당 images/내부 폴더에 저장한다
     * @return string : 이미지 경로를 반환
     * @throws Exception
     */
    protected function uploadImgFromUrl($url, $directory): string
    {
        $upload_dir = __DIR__ . '/../../images/' . $directory . "/";
        // 저장될 폴더 경로

        $file_name = explode('/', $url);
        $file_name = date("Hms").end($file_name).'.jpeg';
        // 경로 내부에 저장될 이름
        // 중복을 막기 위해 시분초 + url 맨 뒤에 주소값을 붙여서 만든다

        $file_path = $upload_dir.$file_name;
        // 저장될 파일의 최종 경로

        // 경로가 존재하는지 & 경로에 쓸 권한이 있는지 확인
        if (is_dir($upload_dir) && is_writable($upload_dir)) {
            // do upload logic here

            // 이미지 업로드
            if(!file_put_contents($file_path, file_get_contents($url))){
                // 업로드에 실패한 경우 false를 반환한다
                throw new Exception('Image file upload failed');
            }else{
                //성공적으로 업로드한 경우, 경로를 깔끔하게 바꿔서 전달~
                $img_path = "/images/" . $directory . "/" . $file_name;
                // ex) /images/user/161029AOh14Ggeg5Anig5qPeLvup5O_3sCCFDQueWRVw0IkNQiayc=s96.jpeg
                return $img_path;
            }

        } else {
//            echo 'Upload directory is not writable, or does not exist.';
            throw new Exception('Directory not exits or no write permission');
        }


    }

    /**
     * uploadImgFile
     *
     * 파일로 된 이미지를 분류에 맞는 폴더에 저장하고, 저장된 경로를 반환한다
     *
     * @param $imgFile : 전달받은 파일. $_FILES['업로드 이미지 변수명']
     * @param $directory : user, spot, music 중 하나. 어디에 속하는 이미지인지 구분하기 위함
     * @return string : 이미지가 저장된 경로. 이 경로가 DB에 저장된다
     * @throws Exception
     */
    protected function uploadImgFile($imgFile, $directory): string
    {

        $uploaddir = __DIR__ . '/../../images/' . $directory . "/";
        // images폴더는 /html/back_end/images에 존재함  
        $basename = basename(date("Hms") . $imgFile['name']);
        // 시분초 + 유저에게 저장되어 있던 이름 붙여서 똑같은 이름이 없게 함
        // 만약 겹칠 것 같으면 user_PK를 받아와서 추가해주는 방법도 있을 것 같음

        $uploadfile = $uploaddir . $basename;

        /** 이미지 사이즈 제한 (2MB 이하)*/
        if ($imgFile["size"] <= 2097152) {

            /** 이미지 타입 제한 */
            $imageFileType = strtolower(pathinfo($uploadfile, PATHINFO_EXTENSION));
            if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg"
                || $imageFileType == "gif") {
                   // echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";

                /** 경로가 존재하는지 & 경로에 쓸 권한이 있는지 확인 */
                if (is_dir($uploaddir) && is_writable($uploaddir)) {

                    /** 이미지 업로드 */
                    $move = move_uploaded_file($imgFile['tmp_name'], $uploadfile);
                    if ($move) {

                        /** 반환할 이미지 경로 */
                        //echo "File is valid, and was successfully uploaded.\n";
                        $img_path = "/images/" . $directory . "/" . $basename;

                    } else {
                        echo "Possible file upload attack!\n".$imgFile["error"];
                    }

                } else {
                    echo 'Upload directory is not writable, or does not exist.';

                    if(is_dir($uploaddir)){
//                        echo "\r디렉토리 존재";
                    }else if(is_writable($uploaddir)){
//                        echo "\r권한 있음";
                    }else{

                    }
                }

            } else {
                throw new Exception("Only jpg, jpeg, png, gif files are allowed");
            }

        } else {
            $image_size = $imgFile["size"];
            throw new Exception("Image too large : $image_size");

        }



        return $img_path;

    }



    /**
     * __call magic method.
     */
    public function __call($name, $arguments)
    {
        $this->sendOutput('', array('HTTP/1.1 404 Not Found'));
    }

    /**
     * Get URI elements.
     *
     * @return array
     */
    protected function getUriSegments()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode('/', $uri);

        return $uri;
    }

    /**
     * Get querystring params.
     *
     * @return array
     */
    protected function getQueryStringParams()
    {
        return parse_str($_SERVER['QUERY_STRING'], $query);
    }


}