<?php
//echo "\rhello";
ini_set('session.cookie_lifetime', 60*60*24*7);  // 이 값이 0이면 세션은 브라우저가 종료되자마자 삭제된다. 7일간 유지되도록 세팅
ini_set('session.gc_maxlifetime', 60*60*24*7); // 시간은 초단위. 이 값이 위 값보다 적으면 세션이 삭제되니 둘 다 세팅해줘야 한다
session_set_cookie_params(60*60*24*7,"/","buskontest.gq",true,true); // 첫번째 파라미터가 쿠키 라이프타임이니까 첫번째 줄은 삭제해도 될줄 알았는데 셋 다 있어야 정상작동

session_start();

//echo "\r".session_id();
//echo "\rstatus(파기  0, 없음 1, 살아있음 2):".session_status();
// 파기 처리되면 0, 없으면 1, 살아있으면 2

//echo __DIR__;
require __DIR__ . "/inc/bootstrap.php";
require __DIR__ . "/inc/PreFunction.php";

// CORS 에러 방지를 위해서 OPTIONS 요청에 대해 200 결과 반환하기
// GET, POST 요청은 괜찮은데 PATCH 요청의 경우 preflight 요청이 OPTIONS 메소드로 옴. 여기에 200 반환해줘야 에러 발생하지 않음.
$request_method = strtoupper($_SERVER["REQUEST_METHOD"]);
if($request_method=='OPTIONS'){
    header(array('Content-Type: application/json', 'HTTP/2 200'));
    header('Access-Control-Allow-Origin:https://buskontest.gq');
//        header("Access-Control-Allow-Origin: *");
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 86400');
    header('Access-Control-Allow-Headers: Content-Type, Origins, X-Auth-Token');


    // 메세지를 보내고 싶다면 아래 에코 추가하면 됨
//    echo 'good';
    exit;
}




/** API URI /api/controller/version/function_key 순서 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
//echo "\r   (index.php) uri : ".$uri;

/** API 주소의 2번째 값인 컨트롤러는 admin, users, spots 등 (관리자 제외 DB기준임)
    각 컨트롤러로 분기해서 보내기 위해 뽑아둠 */
$array_uri = explode('/', $uri);
$controller_key = $array_uri[2];

/** 버전키는 컨트롤러 내에서 분기처리. */
$version_key = $array_uri[3];

//echo "\r   (index.php) controller_key : ".$controller_key;
//echo "\r exploded uri : ".print_r($uri);

/** API 주소의 /api/controller/version 부분을 삭제하고 카멜문자로 만든 함수키.
// 각 컨트롤러의 함수명이다 */
$pre_function = new PreFunction();
$function_key = $pre_function->makeFunctionKey($uri, $controller_key);
//echo "\r 기능키 완성 : $function_key";

switch ($controller_key) {
    case "quests":
//        echo "\rquest 컨트롤러로";
        require PROJECT_ROOT_PATH . "/Controller/Api/QuestController.php";
        $controller = new QuestController();
        $controller->{$function_key}();
    case "admin":
        //echo "\radmin 컨트롤러로";
        require PROJECT_ROOT_PATH . "/Controller/Api/AdminController.php";
        $controller = new AdminController();
        $controller->{$function_key}();

        break;
    case "music":
//            echo "\rmusic 컨트롤러로";
        require PROJECT_ROOT_PATH . "/Controller/Api/MusicController.php";
        $controller = new MusicController();
        $controller->{$function_key}();

        break;
    case "crystals":
//            echo "\rcrystals 컨트롤러로";
        require PROJECT_ROOT_PATH . "/Controller/Api/CrystalController.php";
        $controller = new CrystalController();
        $controller->{$function_key}();

        break;

    case "messages":
        //echo "\rmessages 컨트롤러로";
        require PROJECT_ROOT_PATH . "/Controller/Api/MessageController.php";
        $controller = new MessageController();
        $controller->{$function_key}();
        break;

    case "users":
//        echo "\rusers controller로";
        require PROJECT_ROOT_PATH . "/Controller/Api/UserController.php";
        $controller = new UserController();
        // 요청이 들어올때, 새로운 유저 controller 선언후, uri 주소대로 함수를 호출한다
        $controller->{$function_key}();
        // 컨트롤러 내 해당 함수로 이동

        break;

    case "spots":
//        echo "\r 스팟으로";
        require PROJECT_ROOT_PATH . "/Controller/Api/SpotController.php";
        $controller = new SpotController();
        $controller->{$function_key}();
        break;

    default :
        header(array('Content-Type: application/json', 'HTTP/2 404'));
        echo "No such controller";
        exit;
    }



?>
