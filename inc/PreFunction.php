<?php
//echo "\r pre function 파일";

class PreFunction
{

    /**
     * API URI 주소를 받아 앞부분에 필요없는 건 삭제하고 (/api/controller/version 부분)
     * 슬래시, 대시로 연결된 부분은 삭제하고 카멜글자로 바꿔서 최종 function key 반환한다
     *
     * @param $uri : API URI 주소, 여기에서 불필요한 걸 빼고 최종 기능키를 만든다
     * @param $controller_key : 컨트롤러 키도 삭제해줘야 해서 받음
     * @return mixed|string|string[] : 카멜키로 만들어진 function key, 각 컨트롤러에서 함수명과 동일
     */
    public function makeFunctionKey($uri, $controller_key){
//        echo "\rhere";

        // URI에서 컨트롤러와 그 앞에 붙은 슬래시 삭제
        $str_controller = "/".$controller_key;
        $position_controller = strpos($uri, $str_controller);
        $uri = substr_replace($uri, "", $position_controller, strlen($str_controller));
//        echo "\r$uri";

        // '/api/v1/' 부분 삭제
        $uri = substr($uri,8);

        // /, - 뒤에 있는 글자 대문자로 바꾸고 슬래시와 대시는 삭제
        $uri = $this->replaceAndRemove($uri, "/");
        $uri = $this->replaceAndRemove($uri,"-");

        $function_key = $uri;
        return $function_key;
    }

    /**
    * 수정된 uri 받아서 / 나 - 를 needle로 받아 그 뒤에 있는 소문자는 대문자로 바꾸고 해당 needle은 삭제해주는 함수
    * 예 ) abl/dd -> ablDd 로 변환!
    */
    private function replaceAndRemove($uri, $needle){
        while(strpos($uri,$needle)!=false){

            $position = strpos($uri,$needle);
//            echo "\r 슬래시 포지션 : $position";
            $str_to_upper = substr($uri,intval($position)+1,1);
//            echo "\r 대문자로 바꿔야하는 글자 $str_to_upper";
            $str_upper = strtoupper($str_to_upper);
//            echo "\r 대문자 : $str_upper";
            $uri = substr_replace($uri,$str_upper,intval($position)+1,1);
//            echo "\r 대문자로 바꿈 : $uri";
            $uri = substr_replace($uri,"",intval($position),1);
//            echo "\r 슬래시 지움 : $uri";

        }

        $uri = str_replace($needle,"",$uri);
        return $uri;
    }



}



?>