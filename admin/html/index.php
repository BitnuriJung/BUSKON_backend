<!DOCTYPE html>
<html>
    <head>
        <title>버스콘 관리자 페이지</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
        <style>
            .con {
                margin: 0 auto;
                border: 3px solid red;
                width: 300px;
            }

            .a {
                padding: 30px;
                text-align: center;
                border: 5px solid green;
            }
        </style>
    </head>
    <body>
    <?php
    require ("nav.html");
    ?>
    <div class="container-fluid">


        <div class="container">

            <!--            셀렉트, 체크박스 -->

            <div class="row bg-warning mb-2">

<!--                셀렉트-->
                <div class="col">
                    <select class="form-select" id="select_function">
                        <option selected value="all">전체 기능</option>
                        <option value="0">공연장</option>
                        <option value="1">Two</option>
                    </select>
                </div>

<!--                체크박스-->
                <div class="col d-flex align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="check_not_handled_only">
                        <label class="form-check-label" for="check_not_handled_only">
                            미처리 신고만 보기
                        </label>
                    </div>
                </div>

<!--                빈칸용 col-->
                <div class="col">
                </div>

            </div>
            <!--            셀렉트, 체크박스 -->
            <div id="table"></div>


            <!--            테이블-->
            <table class="table table-hover mb-5">
                <thead>
                <tr class="table-light">
                    <th scope="col" >PK</th>
                    <th scope="col" style="width: 7%">기능</th>
                    <th scope="col" style="width: 15%">스피너</th>
                    <th scope="col" style="width:40% ">상세</th>
                    <th scope="col">닉네임</th>
                    <th scope="col" style="width: 15%">신고 일시</th>
                    <th scope="col">처리</th>

                </tr>
                </thead>
                <tbody id="tbody">
<!--                <tr>-->
<!--                    <th scope="row">1</th>-->
<!--                    <td>Mark</td>-->
<!--                    <td>Otto</td>-->
<!--                    <td>@mdo</td>-->
<!--                </tr>-->
<!--                <tr>-->
<!--                    <th scope="row">2</th>-->
<!--                    <td>Jacob</td>-->
<!--                    <td>Thornton</td>-->
<!--                    <td>@fat</td>-->
<!--                </tr>-->
<!--                <tr>-->
<!--                    <th scope="row">3</th>-->
<!--                    <td colspan="2">Larry the Bird</td>-->
<!--                    <td>@twitter</td>-->
<!--                </tr>-->
                </tbody>
            </table>
            <!--            테이블-->

<!--            페이징-->
            <nav aria-label="Page navigation example">
                <ul class="pagination justify-content-center" id="pagination">
                    <li class="page-item disabled"><a class="page-link">Previous</a></li>
                    <li class="page-item"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                </ul>
            </nav>
<!--페이징-->


<!--            살짝 좁게 해둔곳 끝 -->
        </div>

        <!-- Button trigger modal -->


        <!-- Modal -->
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">처리상태 변경</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label for="textarea_report_remarks" class="form-label">비고</label>
                        <textarea type="text" id="textarea_report_remarks" class="form-control" maxlength="100"></textarea>
                        <input type="hidden" id="input_report_PK" class="form-control">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="input_report_status">
                            <label class="form-check-label" for="input_report_status">처리함</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="button" class="btn btn-primary" id="btn_report_status_change">저장</button>
                    </div>
                </div>
            </div>
        </div>

        <!--            제일 넓은 컨테이너 끝 -->


    </div>

        <script src="../js/report.js"></script>
    </body>
</html>