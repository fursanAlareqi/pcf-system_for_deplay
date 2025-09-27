<?php
// ===============================
// report_data/individual_sessions_data.php
// ===============================

// 1) نافذة التاريخ:
//    - إن جات من الواجهة القديمة ($from_date/$to_date) نستخدمها كما هي.
//    - إن ما جات: نضبط الشهر الحالي.
if (empty($from_date) || empty($to_date)) {
    date_default_timezone_set('Asia/Aden');
    $from_date = date('Y-m-01'); // أول يوم بالشهر
    $to_date   = date('Y-m-t');  // آخر يوم بالشهر
}

// 2) أذونات الأدوار ومنطق الفروع عند عدم وجود branch في POST
$rule_id      = (int)($_SESSION['user']['rule_id'] ?? 0);
$can_view_all = in_array($rule_id, [2, 6], true); // الواجهة القديمة: 2 و 6 يشوفوا كل الفروع

// 3) بناء الاستعلام ديناميكيًا
$sql    = "SELECT * FROM individual_sessions WHERE `date` BETWEEN ? AND ?";
$params = [$from_date, $to_date];

// لو الصفحة الجديدة أرسلت branch نستخدمه (إلا إذا كان "الكل"/فارغ)
if (isset($_POST['branch'])) {
    $b = trim($_POST['branch']);
    if ($b !== '' && $b !== 'الكل') {
        $sql     .= " AND branch = ?";
        $params[] = (int)$b;
    }
} else {
    // الواجهة القديمة (لا يوجد branch في POST):
    // 2 و 6 يشوفوا الكل، غيرهم يُقيّد لفرع السيشن
    if (!$can_view_all) {
        $sql     .= " AND branch = ?";
        $params[] = (int)($_SESSION['user']['branch_id'] ?? 0);
    }
}

$sql .= " ORDER BY `date` DESC, id DESC";

$stmu = $con->prepare($sql);
$stmu->execute($params);

// عدد الحالات الكلية
if ($stmu->rowCount() > 0) {
    $rows = $stmu->fetchAll(PDO::FETCH_ASSOC);
    $cnt  = 1;
    ?>
    <table id="multi-filter-select" class="table table-bordered table-head-bg-info table-bordered-bd-info mt-4">
        <center>
            <?php if ($rule_id === 2 || $rule_id === 6) { ?>
                <button id="export" class="btn btn-success">Export to excel</button>
            <?php } ?>
        </center>
        <thead>
            <tr>
                <th>اسم الموظف</th>
                <th>نوع التسجيل</th>
                <th>الفرع</th>
                <th>العمر</th>
                <th>الجنس</th>
                <th>رقم كود الحالة</th>
                <th>الجلسة</th>
                <th>عنوان الجلسة</th>
                <th>الزمن من</th>
                <th>الزمن إلى</th>
                <th>تحديد المشكلة</th>
                <th>إجراءات الجلسة</th>
                <th>الأساليب العلاجية</th>
                <th>المهام والواجبات</th>
                <th>الاختبارات النفسية</th>
                <th>التاريخ</th>
                 <?php if (!$rule_id == 24 ) { ?> 
                <th>تعديل</th>
                                <?php } ?>

            </tr>
        </thead>
        <tfoot>
            <th>اسم الموظف</th>
            <th>نوع التسجيل</th>
            <th>الفرع</th>
            <th>العمر</th>
            <th>الجنس</th>
            <th>رقم كود الحالة</th>
            <th>الجلسة</th>
            <th>عنوان الجلسة</th>
            <th>الزمن من</th>
            <th>الزمن إلى</th>
            <th>تحديد المشكلة</th>
            <th>إجراءات الجلسة</th>
            <th>الأساليب العلاجية</th>
            <th>المهام والواجبات</th>
            <th>الاختبارات النفسية</th>
            <th>التاريخ</th>
             <?php if (!$rule_id == 24 ) { ?> 
            <th>تعديل</th>
                            <?php } ?>

        </tfoot>
        <tbody>
            <?php
            foreach ($rows as $row) {
                // اسم الموظف
                $stmUser = $con->prepare("SELECT name FROM user WHERE id = ?");
                $stmUser->execute([$row['sender_name']]);
                $nameRow = $stmUser->fetch(PDO::FETCH_ASSOC);
                $sender_name = $nameRow['name'] ?? '';

                // اسم الفرع
                $stmBranch = $con->prepare("SELECT branch_name FROM branch WHERE id = ?");
                $stmBranch->execute([$row['branch']]);
                $branchRow   = $stmBranch->fetch(PDO::FETCH_ASSOC);
                $branch_name = $branchRow['branch_name'] ?? '';

                // العمر + الجنس:
                // لو الدور 16 → من جدول hotline، غير كذا → من resption
                if ($rule_id === 16) {
                    $stmAge = $con->prepare("
                        SELECT TIMESTAMPDIFF(YEAR, brithday, CURDATE()) AS age, sex
                        FROM hotline
                        WHERE code = ? AND type = ?
                    ");
                } else {
                    $stmAge = $con->prepare("
                        SELECT 
                            YEAR(CURDATE()) - YEAR(brithday) 
                              - (DATE_FORMAT(CURDATE(), '%m-%d') < DATE_FORMAT(brithday, '%m-%d')) AS age,
                            sex
                        FROM resption
                        WHERE code = ? AND type = ?
                    ");
                }
                $stmAge->execute([$row['code'], 'جديد']);
                $row_brithday_sex = $stmAge->fetch(PDO::FETCH_ASSOC) ?: ['age' => null, 'sex' => ''];

                ?>
                <tr>
                    <td><?php echo htmlspecialchars($sender_name); ?></td>
                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                    <td><?php echo htmlspecialchars($branch_name); ?></td>
                    <td><?php echo htmlspecialchars($row_brithday_sex['age']); ?></td>
                    <td><?php echo htmlspecialchars($row_brithday_sex['sex']); ?></td>
                    <td><?php echo htmlspecialchars($row['code']); ?></td>
                    <td><?php echo htmlspecialchars($row['section']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td><?php echo htmlspecialchars($row['from_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['to_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['select_s']); ?></td>
                    <td><?php echo htmlspecialchars($row['agrat']); ?></td>
                    <td><?php echo htmlspecialchars($row['alasalib']); ?></td>
                    <td><?php echo htmlspecialchars($row['taks']); ?></td>
                    <td><?php echo htmlspecialchars($row['violense']); ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <?php if (!$rule_id == 24 ) { ?> 
                    <td>
                        <a href="individual_sessions_edit.php?action=edit&ids=<?php echo (int)$row['id']; ?>">
                            <i class="fa fa-pencil"></i>تعديل
                        </a>
                    </td>
                    <?php } ?>
                </tr>
                <?php
            } // foreach
            ?>
        </tbody>
    </table>
    <?php
} else {
    echo '<div class="alert alert-danger">NO Row</div>';
}
