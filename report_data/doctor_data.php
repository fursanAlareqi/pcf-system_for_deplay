<?php

if (empty($from_date) || empty($to_date)) {
    date_default_timezone_set('Asia/Aden');
    $from_date = date('Y-m-01'); // اليوم الأول من الشهر
    $to_date   = date('Y-m-t');  // آخر يوم من الشهر
}

// 2) أذونات الأدوار ومنطق الفرع
$rule_id = (int)($_SESSION['user']['rule_id'] ?? 0);
$can_view_all = in_array($rule_id, [2, 6], true); // نفس منطقك: 2 و 6 يشوفوا كل الفروع

// 3) بناء الاستعلام ديناميكياً
$sql    = "SELECT * FROM doctor WHERE `date` BETWEEN ? AND ?";
$params = [$from_date, $to_date];

// إذا الصفحة الجديدة أرسلت branch، طبّقها فقط لو ليست "الكل"
if (isset($_POST['branch'])) {
    $b = trim($_POST['branch']);
    if ($b !== '' && $b !== 'الكل') {
        $sql     .= " AND branch = ?";
        $params[] = (int)$b;
    }
} else {
    // الواجهة القديمة: لا يوجد branch في POST
    if (!$can_view_all) {
        // غير 2 و 6 محصورين بفرع السيشن
        $forced_branch = (int)($_SESSION['user']['branch_id'] ?? 0);
        $sql     .= " AND branch = ?";
        $params[] = $forced_branch;
    }
}

$sql .= " ORDER BY `date` DESC, id DESC";

$stmu = $con->prepare($sql);
$stmu->execute($params);

// عدد الحالات الكليّة
if ($stmu->rowCount() > 0) {
    ?>
    <table id="multi-filter-select" class="table table-bordered table-head-bg-info table-bordered-bd-info mt-4">
        <center>
            <?php if ($rule_id === 2 || $rule_id === 6) { ?>
                <button id="export" class="btn btn-success">Export to excel</button>
            <?php } ?>
        </center>
        <br>
        <thead>
            <tr>
                <th>Sno.</th>
                <th>اسم الموظف</th>
                <th class="hidden-phone">الفرع</th>
                <th>عودة/جديدة</th>
                <th>العمر</th>
                <th>الجنس</th>
                <th>رقم كود الحالة</th>
                <th>الحالة النفسية العامة للمريض</th>
                <th>ملاحظات</th>
                <th>Physical Examination</th>
                <th>العلاج</th>
                <th>الفحص</th>
                <th>تقييم وضع الحالة</th>
                <th>مدى التحسن</th>
                <th>التاريخ</th>
                <?php if (!$rule_id == 24 ) { ?> 
                    <th>تعديل</th>
                <?php } ?>
            </tr>
        </thead>
        <tfoot>
            <th>Sno.</th>
            <th>اسم الموظف</th>
            <th class="hidden-phone">الفرع</th>
            <th>عودة/جديدة</th>
            <th>العمر</th>
            <th>الجنس</th>
            <th>رقم كود الحالة</th>
            <th>الحالة النفسية العامة للمريض</th>
            <th>ملاحظات</th>
            <th>Physical Examination</th>
            <th>العلاج</th>
            <th>الفحص</th>
            <th>تقييم وضع الحالة</th>
            <th>مدى التحسن</th>
            <th>التاريخ</th>
             <?php if (!$rule_id == 24 ) { ?> 
            <th>تعديل</th>
            <?php } ?>
        </tfoot>
        <tbody>
            <?php
            $cnt = 1;
            // ناخذ كل الصفوف أولاً حتى لو استخدمنا $stmu لاحقاً لاستعلامات أخرى
            $rows = $stmu->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                // اسم الموظف
                $stmUser = $con->prepare("SELECT name FROM user WHERE id = ?");
                $stmUser->execute([$row['sender_name']]);
                $nameRow = $stmUser->fetch(PDO::FETCH_ASSOC);
                $sender_name = $nameRow['name'] ?? '';

                // اسم الفرع
                $stmBranch = $con->prepare("SELECT branch_name FROM branch WHERE id = ?");
                $stmBranch->execute([$row['branch']]);
                $branchRow = $stmBranch->fetch(PDO::FETCH_ASSOC);
                $branch_name = $branchRow['branch_name'] ?? '';

                // العمر + الجنس من الاستقبال (resption)
                $stmAge = $con->prepare("
                    SELECT 
                        YEAR(CURDATE()) - YEAR(brithday) 
                          - (DATE_FORMAT(CURDATE(), '%m-%d') < DATE_FORMAT(brithday, '%m-%d')) AS age,
                        sex
                    FROM resption
                    WHERE code = ? AND type = ?
                ");
                $stmAge->execute([$row['code'], 'جديد']);
                $row_brithday_sex = $stmAge->fetch(PDO::FETCH_ASSOC) ?: ['age' => null, 'sex' => ''];

                // تفكيك العلاج/الفحص
                $medical_list = array_filter(array_map('trim', explode(',', $row['medical'] ?? '')));
                $lap_list     = array_filter(array_map('trim', explode(',', $row['lap'] ?? '')));
                ?>
                <tr>
                    <td><?php echo $cnt++; ?></td>
                    <td><?php echo htmlspecialchars($sender_name); ?></td>
                    <td><?php echo htmlspecialchars($branch_name); ?></td>
                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                    <td><?php echo htmlspecialchars($row_brithday_sex['age']); ?></td>
                    <td><?php echo htmlspecialchars($row_brithday_sex['sex']); ?></td>
                    <td><?php echo htmlspecialchars($row['code']); ?></td>
                    <td><?php echo htmlspecialchars($row['diagnosis']); ?></td>
                    <td><?php echo htmlspecialchars($row['note']); ?></td>
                    <td><?php echo htmlspecialchars($row['Physical_Examination']); ?></td>
                    <td><?php foreach ($medical_list as $m) echo htmlspecialchars($m) . '<br>'; ?></td>
                    <td><?php foreach ($lap_list as $l) echo htmlspecialchars($l) . '<br>'; ?></td>
                    <td><?php echo htmlspecialchars($row['appraisal']); ?></td>
                    <td><?php echo htmlspecialchars($row['recover']); ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <?php if (!$rule_id == 24 ) { ?> 
                    <td>
                        <a href="doctor_edit.php?action=edit&ids=<?php echo (int)$row['id']; ?>">
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
