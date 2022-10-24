<?php

require_once PROJECT_ROOT_PATH . "/Model/Database.php";

class ReportModel extends Database
{
    // 신고 등록
    public function insertReport($params = [])
    {
        return $this->insert("INSERT INTO REPORT (user_PK, reported_PK, report_category, report_spinner, report_detail) VALUES (?,?,?,?,?)",$params);
    }

    // 신고 row 조회
    public function getReportRow($report_PK)
    {
        return $this->select("SELECT * FROM REPORT WHERE report_PK = ?",[$report_PK]);
    }

    // 카테고리, 처리 상태별 전체 신고 숫자 조회
    public function getReportsCnt($report_category, $report_status)
    {
        // 필터 둘 다 걸린 경우
        if($report_category != "all" && $report_status != "all"){

            return $this->select("SELECT COUNT(*) AS count FROM REPORT WHERE report_category = ? AND report_status = ?",[$report_category, $report_status]);

            // 카테고리만 필터링
        }else if($report_category != "all" && $report_status == "all"){

            return $this->select("SELECT COUNT(*) AS count FROM REPORT WHERE report_category = ?",[$report_category]);

            // 처리 상태만 필터링
        }else if($report_category == "all" && $report_status != "all"){

            return $this->select("SELECT COUNT(*) AS count FROM REPORT WHERE report_status = ?",[$report_status]);

            // 필터 x 전체 숫자 반환
        }else{
            return $this->select("SELECT COUNT(*) AS count FROM REPORT",[]);

        }

    }

    // 처리상태, 카테고리에 따른 신고 목록 페이징 조회
    public function getReportsList($report_category, $report_status, $offset, $limit)
    {
        // 필터 둘 다 걸린 경우
        if($report_category != "all" && $report_status != "all"){

            return $this->select("SELECT report_PK, reported_PK, report_category, report_spinner, report_detail, report_status, report_remarks, REPORT.reg_date, REPORT.update_date,U.user_PK, user_nickname FROM REPORT
                                    LEFT JOIN USER U on U.user_PK = REPORT.user_PK 
                                    WHERE report_category = ? AND report_status = ? ORDER BY reg_date DESC LIMIT ?,?",[$report_category, $report_status, $offset,$limit]);

            // 카테고리만 필터링
        }else if($report_category != "all" && $report_status == "all"){

            return $this->select("SELECT report_PK, reported_PK, report_category, report_spinner, report_detail, report_status, report_remarks, REPORT.reg_date, REPORT.update_date,U.user_PK, user_nickname FROM REPORT
                                    LEFT JOIN USER U on U.user_PK = REPORT.user_PK 
                                    WHERE report_category = ? ORDER BY reg_date DESC LIMIT ?,?",[$report_category, $offset,$limit]);

            // 처리 상태만 필터링
        }else if($report_category == "all" && $report_status != "all"){

            return $this->select("SELECT report_PK, reported_PK, report_category, report_spinner, report_detail, report_status, report_remarks, REPORT.reg_date, REPORT.update_date,U.user_PK, user_nickname FROM REPORT
                                    LEFT JOIN USER U on U.user_PK = REPORT.user_PK WHERE report_status = ? ORDER BY reg_date DESC LIMIT ?,?",[$report_status,$offset,$limit]);

            // 필터 x 전체 숫자 반환
        }else{
            return $this->select("SELECT report_PK, reported_PK, report_category, report_spinner, report_detail, report_status, report_remarks, REPORT.reg_date, REPORT.update_date,U.user_PK, user_nickname FROM REPORT
                                    LEFT JOIN USER U on U.user_PK = REPORT.user_PK ORDER BY reg_date DESC LIMIT ?,?",[$offset,$limit]);


        }
    }

    // 신고 처리상태 변경 & 비고 등록
    public function updateReportsStatus($params = [])
    {
        return $this->update("UPDATE REPORT SET report_status = ?, report_remarks = ?, update_date = CURDATE() WHERE report_PK = ?",$params);
    }

}