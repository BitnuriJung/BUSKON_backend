<?php
ini_set('session.cookie_lifetime', 60*60*24*7);  // 이 값이 0이면 세션은 브라우저가 종료되자마자 삭제된다. 7일간 유지되도록 세팅
ini_set('session.gc_maxlifetime', 60*60*24*7); // 시간은 초단위. 이 값이 위 값보다 적으면 세션이 삭제되니 둘 다 세팅해줘야 한다
session_set_cookie_params(60*60*24*7,"/","buskon.tk",true,true); // 첫번째 파라미터가 쿠키 라이프타임이니까 첫번째 줄은 삭제해도 될줄 알았는데 셋 다 있어야 정상작동
session_start();

//echo "\r".session_id();
//echo "\r".session_status();


require __DIR__ . "/inc/bootstrap.php";


// API URI /api/controller/version/function_key 순서
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
//echo "\r   (index.php) uri : ".$uri;
$uri = explode('/', $uri);
//echo "\r exploded uri : ".print_r($uri);
//echo "\r".$uri[4];

$controller_key = $uri[2];
//echo "\r   (index.php) controller_key : ".$controller_key;
// API 주소는 /api/users/... 식으로 진행
// 2번째 값인 users, spots 등을 기준으로 큰 분류를 먼저 한다

$function_key = end($uri);
// API 주소가 얼마나 길어질지 지금으로서는 모르겠다.
// 따라서 맨 마지막 값을 함수값으로 한다.
//  = uri 키와 function키가 겹치는 API 주소를 만들면 에러가 날 것이다.


switch ($controller_key) {

    case "users":
        require PROJECT_ROOT_PATH . "/Controller/Api/UserController.php";
        $controller = new UserController();
        // 요청이 들어올때, 새로운 유저 controller 선언후, uri 주소대로 함수를 호출한다

        $controller->{$function_key}();
        // uri 주소 맨 끝 = 함수 이름

        break;

    case "spots":
//        echo "\r 스팟으로";
        require PROJECT_ROOT_PATH . "/Controller/Api/SpotController.php";
        $controller = new SpotController();
        $controller->{$function_key}();
        break;

    case "item":
        break;

    case "pay":
        echo'pay';
        require PROJECT_ROOT_PATH . "/test/boot_access.php";
        break;

    default :
        break;
}


?>
