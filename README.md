
# 버스콘 백엔드 구조 

- Controlloer
    - index.php
        - bootstrap 호출
        - 라우팅 파일 
            
            - API uri 변환
            - API별로 Controller 호출
    - inc
        - bootstrap.php
            
            컨피그, 베이스 컨트롤러, 사용하는 모델(Database말고) 리콰이어
            
            유저 모델 말고 추가될 시, 여기에 리콰이어 추가 
            
        - (config.php)
            
            DB 연결과 관련된 계정 이름, 비번, DB이름 define
    - API
        - BaseController.php
            - uri 구분, param 구분, 아웃풋 전송 등 컨트롤러에서 사용해야 할 기본 공통 함수 선언
        - UserController.php ...
            - API 엔드포인트
            - API별 실행되어야 할 함수(/user/list 등, 메소드 안맞으면 퇴출) 선언. 
    - Model
        - Database.php
            
            DB연동 construct, 다른 모델에서 쿼리문을 넣어서 보내면 데이터 베이스에 있는 함수로 쿼리 실질적으로 execute하고 결과를 모델에게 보냄
            
        - UserModel.php ...