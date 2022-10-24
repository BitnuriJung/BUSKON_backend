
const domain = 'https://www.buskontest.gq';
const api_report = '/api/admin/v1/reports';

//기능 카테고리
const select_category = $('#select_function');
// 미처리만 보기 체크박스
const check_not_handled_only =  $('#check_not_handled_only');
// 처리 내용 테이블 바디
const tbdoy = $('#tbody');

// 페지네이션
const pagination = $('#pagination');
let page_clicked = 1;
// 모달창
const modal_report_status = $('#exampleModal');
const textarea_report_remarks = $('#textarea_report_remarks');
const btn_report_status_change = $('#btn_report_status_change');
const input_report_PK = $('#input_report_PK');
const input_report_status = $('#input_report_status');


// 페이지 접속하자마자 데이터 불러오기 (기본값으로 실행)
get_data();


// 처리상태 변경 모달창에서 변경 버튼 클릭시
btn_report_status_change.click(function (){
    let report_status;
    if(input_report_status.is(":checked")){
        report_status = 1;
    }else{
        report_status = 0;
    }
    console.log(input_report_PK.val());
    console.log(textarea_report_remarks.val());

    var api_report_patch = {
        "url": domain+"/api/admin/v1/reports",
        "method": "PATCH",
        // "timeout": 0,
        "headers": {
            "Content-Type": "application/json",
            // "Cookie": "BUSKID=up2ieccc4p9j191iu7rkbh2ead"
        },
        "data": JSON.stringify({
            "report_PK": parseInt(input_report_PK.val()),
            "report_status": report_status,
            "report_remarks": textarea_report_remarks.val()
        }),
    };

    $.ajax(api_report_patch).done(function (response) {
        console.log(response);
        modal_report_status.modal('hide');
        get_data();
    });
})



// 처리, 미처리 버튼 클릭시 해당 테이블 row 데이터 접근해서 모달창에 띄우기
$(document).on('click', '.btn_modal', function(e){
    console.log('hey');

    // 지금 클릭한 줄 - report_PK 존재
    var currentRow=$(this).closest("tr");
    // 고 다음줄 - report_remarks 존재
    let nextRow = $(this).closest('tr').next('tr');

    // 클릭된 버튼안에 처리상태 value 있음. 처리면 1, 미처리면 0 값.
    let report_status = $(this).val();
    console.log(report_status);

    // 비고
    let report_remarks_text = nextRow.find('td:eq(2)').text();
    console.log(report_remarks_text);

    // 신고 pk
    let report_PK = currentRow.find("th:eq(0)").text();
    console.log('pk'+report_PK);

    // 처리상태 변경 모달 띄우기
    modal_report_status.modal('show');

    //저장버튼 클릭시 api에 전송할 비고 내용, 신고pk, 처리상태값 집어넣기
    textarea_report_remarks.val(report_remarks_text);
    input_report_PK.val(report_PK);
    if(report_status==1){
        console.log('처리함');
        input_report_status.prop('checked',true);
    }else{
        input_report_status.prop('checked',false);
    }
})



// 기능 셀렉트 변경시 데이터 다시 불러오기 & 1페이지로 돌아가기
select_category.on('change', function (){
    page_clicked = 1;
    get_data();
})

// 미처리만 보기 체크박스 클릭시 데이터 다시 불러오기 & 1페이지로 돌아가기
check_not_handled_only.click(function (){
    page_clicked = 1;
    get_data();
})

// 페이징 번호 클릭시 데이터 다시 불러오기
pagination.on('click','li',function (){

    console.log($(this).text());
    page_clicked = $(this).text();
    get_data();
})


// 데이터 불러오기
function get_data(){

    // 클릭된 페이지
    let page_num = "page_num="+page_clicked;

    // 선택된 카테고리
    let select_option = select_category.val();
    console.log('selected : '+ select_option);
    let report_category = "report_category="+select_option;

    // 처리 미처리 체크박스
    let not_handled_only_checked = check_not_handled_only.is(":checked")
    let status = "";
    if(not_handled_only_checked){
        status = 0;
    }else{
        status = 'all';
    }
    console.log('checked : '+status);
    let report_status = "report_status="+status;


    // ajax로 api 연동
    var settings = {
        "url": domain+api_report+"?"+page_num+"&"+report_category+"&"+report_status,
        "method": "GET",
        "timeout": 0,
        // 헤더 설정은 위험해 안할거야 하고 안해버림..쩝..
        // "headers": {
        //     "Cookie": "BUSKID=up2ieccc4p9j191iu7rkbh2ead"
        // },
    };

    // 결과 받기
    $.ajax(settings).done(function (response) {
        // console.log(response.response.total_pages_num);
        // console.log(response.response.arr_reports);

        // 데이터 표에 넣기
        insert_tbody(response.response.arr_reports);
        // 페이지 수만큼 페지네이션 만들기
        make_pagination(response.response.total_pages_num)

    });
}

function make_pagination(total_pages_num){

    let pagination_html = '';
    for(let i = 0; i < total_pages_num; i++){
        pagination_html += '<li class="page-item"><a class="page-link">'+(i+1)+'</a></li>'
    }

    pagination.html(pagination_html);
}

// 받아온 데이터를 tbody html 양식에 맞게 표로 만들기
// TODO : 콜랩스 예제
//https://examples.bootstrap-table.com/#methods/expand-collapse-row.html
function insert_tbody(arr_reports){

    let tbody_html = "";

    // 데이터 array 길이만큼 row 만들기
    for(let i = 0; i < arr_reports.length; i++){
        //데이터 잘 보고 있는지 확인
        // console.log(arr_reports[i].report_PK);

        // 비고값이 null 이면 빈칸으로 치환 
        let report_remarks;
        if(arr_reports[i].report_remarks==null){
            report_remarks = "";
        }else{
            report_remarks = arr_reports[i].report_remarks;
        }
        // 숫자로 온 카테고리 데이터 글자로 변경
        let category = "";
        let reported_category_pk_title = "";
        switch (arr_reports[i].report_category) {
            case 0:
                category = '공연장';
                reported_category_pk_title = '공연장 PK';
                break;
        }

        // 숫자로 온 처리,미처리 데이터 글자로 변경
        let td_report_handled = "";
        switch (arr_reports[i].report_status) {
            case 0:
                td_report_handled = 
                    '<td>' +
                    '<button class="btn btn-danger btn_modal" value="0">' +
                    '미처리' +
                    '</button></td>';

                // handled = '미처리';
                break;
            case 1:
                td_report_handled =
                    '<td>' +
                    '<button type="button" class="btn btn-light btn_modal" data-bs-toggle="modal" data-bs-target="#exampleModal" value="1">' +
                    '처리' +
                    '</button></td>';
                // handled = '처리';
                break;
        }

        // 첫번째 줄 - pk,기능, 스피너, 상세, 닉네임, 신고일시, 처리상태
        tbody_html += '<tr class="table-row">';
        tbody_html += "<th>"+arr_reports[i].report_PK+"</th>";
        tbody_html += "<td>"+category+"</td>";
        tbody_html +="<td>"+arr_reports[i].report_spinner+"</td>";
        tbody_html +="<td>"+arr_reports[i].report_detail+"</td>";
        tbody_html +="<td>"+arr_reports[i].user_nickname+"</td>";
        tbody_html +="<td>"+arr_reports[i].reg_date+"</td>";
        // tbody_html +="<td>"+handled+"</td>";
        tbody_html +=td_report_handled;
        tbody_html += "</tr>";

        // 밑에 추가 데이터 (비고, 신고된 pk 정보)
        tbody_html += '<tr>' +
            '<td colspan="1"></td>' +
            '<td class="table-light">비고</td>' +
            '<td colspan="2">'+report_remarks+'</td>' +
            '<td class="table-light">'+reported_category_pk_title+'</td>' +
            '<td>'+arr_reports[i].reported_PK+'</td>' +
            '<td></td>' +
            '</tr>'

    }

    tbdoy.html(tbody_html);
}
