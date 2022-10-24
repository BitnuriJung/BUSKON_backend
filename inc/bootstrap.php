<?php
//echo "\r    inc/bootstrap 들어옴";
//echo "\r        PROCJECT_ROOT_PATH지정 & CONFIG, BASECONTROLLER, USERMODEL 리콰이어";

define("PROJECT_ROOT_PATH", __DIR__ . "/../");

//echo "\r       부트스트랩 프로젝트 루트 패스 : ".PROJECT_ROOT_PATH;

// include main configuration file
// 보안상 루트 폴더 (/var/www/html/) 내부에 있는 게 좋지 않아 바깥위치로 빼둠
// 절대 깃 커밋하지 말것 !!
require_once PROJECT_ROOT_PATH . "../../config.php";

// include the base controller file
require_once PROJECT_ROOT_PATH . "/Controller/Api/BaseController.php";

// include the use model file
require_once PROJECT_ROOT_PATH . "/Model/UserModel.php";
require_once PROJECT_ROOT_PATH . "/Model/SpotModel.php";
require_once PROJECT_ROOT_PATH . "/Model/MessageModel.php";
require_once PROJECT_ROOT_PATH . "/Model/QuestModel.php";
require_once PROJECT_ROOT_PATH . "/Model/CrystalModel.php";
require_once PROJECT_ROOT_PATH . "/Model/MusicModel.php";
require_once PROJECT_ROOT_PATH . "/Model/ReportModel.php";



?>