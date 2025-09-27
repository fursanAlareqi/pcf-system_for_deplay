<?php
include "include/header.php";

if(!isset($_SESSION['user'])){
    echo "<script> window.open('logout.php','_self')</script>";
    exit;
}

$rule_id = isset($_SESSION['user']['rule_id']) ? (int) $_SESSION['user']['rule_id'] : 0;
$allowed_rules = [2, 19, 6, 22, 23, 24];

if (!in_array($rule_id, $allowed_rules, true)) {
    // use header if no output has started
    if (!headers_sent()) {
        header("Location: index.php");
        exit;
    }
    echo "<script>window.open('index.php','_self')</script>";
    exit;
}
// Force current month window (Asia/Aden)
date_default_timezone_set('Asia/Aden');
$from_date = date('Y-m-01'); // first day of this month
$to_date   = date('Y-m-t');  // last day of this month

// Read filters
$report_type = isset($_POST['report_type']) ? trim($_POST['report_type']) : '';
$posted_branch = isset($_POST['branch']) ? trim($_POST['branch']) : 'الكل';

// Normalize branch to numeric ID or empty string for "all"
if ($posted_branch === 'الكل' || $posted_branch === '') {
    $branch = ''; // means "all branches" for includes
} else {
    $branch = ctype_digit($posted_branch) ? (string)intval($posted_branch) : '';
}

$did_search = isset($_POST['search']);
?>
<div class="content">
    <div class="page-inner">
        <div class="row">
            <div class="col-md-12">
                <form action="" method="post" autocomplete="off">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">عرض بيانات هذا الشهر</div>
                            <div class="small text-muted">
                                الفترة: <?php echo $from_date; ?> → <?php echo $to_date; ?>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                <!-- REMOVED date fields completely -->

                                <!-- Branch selector (same markup you sent; id changed to avoid duplicate) -->
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group form-floating-label">
                                        <label for="branch">الفرع</label>
                                        <select class="selectpicker form-control" name="branch" id="branch" data-live-search="true" required>
                                            <option value="الكل"<?php echo ($posted_branch==='الكل' || $posted_branch==='')?' selected':''; ?>>الكل</option>
                                            <?php
                                                $sql="SELECT * FROM branch";
                                                $stm=$con->prepare($sql);
                                                $stm->execute();
                                                if($stm->rowCount()>0){
                                                    foreach($stm->fetchAll() as $row){
                                                        $selected = ($posted_branch == (string)$row['id']) ? ' selected' : '';
                                                        echo '<option value="'.htmlspecialchars($row['id']).'"'.$selected.'>'.
                                                             htmlspecialchars($row['branch_name']).'</option>';
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Report type -->
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-group form-floating-label">
                                        <label for="report_type">القسم</label>
                                        <select class="selectpicker form-control" name="report_type" id="report_type" data-live-search="true" required>
                                            <?php if($_SESSION['user']['rule_id']==2 || $_SESSION['user']['rule_id']==6 || $_SESSION['user']['rule_id']==24 || $_SESSION['user']['rule_id']==22 || $_SESSION['user']['rule_id']==23){ ?>
                                                <option value=""></option>
                                                <option value="3" <?php echo $report_type==='3'?'selected':''; ?>>الطبيب العام</option>
                                                <option value="4" <?php echo $report_type==='4'?'selected':''; ?>>الطبيب النفسي</option>
                                                <option value="6" <?php echo $report_type==='6'?'selected':''; ?>>الجلسات الفردية</option>
                                                <option value="7" <?php echo $report_type==='7'?'selected':''; ?>>الجلسات الجماعية</option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="col-md-6 col-lg-4 d-flex align-items-end">
                                    <div class="form-group">
                                        <input class="btn btn-success" type="submit" name="search" value="search">
                                    </div>
                                </div>
                            </div>

                            <?php if($did_search && !empty($report_type)){ ?>
                                <div class="table-responsive">
                                    <?php
                                        // Your includes will now see:
                                        // $from_date, $to_date (month window), and $branch (numeric id or empty='')
                                        if($report_type==3){
                                            include "report_data/doctor_data.php";
                                        } elseif($report_type==4){
                                            include "report_data/psyshological_data.php";
                                        } elseif($report_type==6){
                                            include "report_data/individual_sessions_data.php";
                                        } elseif($report_type==7){
                                            include "report_data/group_sessions_data.php";
                                        }
                                    ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "include/footer.php"; ?>
