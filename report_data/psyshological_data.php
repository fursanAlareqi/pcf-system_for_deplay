<?php
// ===============================
// report_data/psyshological_data.php
// ===============================

// 1) نافذة التاريخ: إن ما جت من الواجهة القديمة، نثبّت شهر الحالي
if (empty($from_date) || empty($to_date)) {
    date_default_timezone_set('Asia/Aden');
    $from_date = date('Y-m-01'); // اليوم الأول من الشهر
    $to_date   = date('Y-m-t');  // آخر يوم من الشهر
}

// 2) صلاحيات الأدوار + منطق رؤية الفروع في حال عدم وجود branch بالـ POST
$rule_id       = (int)($_SESSION['user']['rule_id'] ?? 0);
$can_view_all  = in_array($rule_id, [2, 6], true); // الواجهة القديمة: 2 و 6 يشوفوا كل الفروع

// 3) بناء الاستعلام ديناميكياً
$sql    = "SELECT * FROM psyshological WHERE `date` BETWEEN ? AND ?";
$params = [$from_date, $to_date];

// لو الصفحة الجديدة أرسلت branch نستخدمه، وإلا نرجع لمنطق الواجهة القديمة
if (isset($_POST['branch'])) {
    $b = trim($_POST['branch']);
    if ($b !== '' && $b !== 'الكل') {
        $sql     .= " AND branch = ?";
        $params[] = (int)$b;
    }
} else {
    // الواجهة القديمة (مافي branch بالـ POST)
    if (!$can_view_all) {
        $sql     .= " AND branch = ?";
        $params[] = (int)($_SESSION['user']['branch_id'] ?? 0);
    }
}

$sql .= " ORDER BY `date` DESC, id DESC";

$stmu = $con->prepare($sql);
$stmu->execute($params);

// عدد الحالات الكليّة
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
        <br>
        <thead>
            <tr>
                <th>Sno.</th>
                <th>اسم الموظف</th>
                <th class="hidden-phone">الفرع</th>
                <th>جديد/عودة</th>
                <th>رقم كود الحالة</th>
                <th>العمر</th>
                <th>الجنس</th>
                <th>التاريخ</th>
                <th>تشخيص رئيسي</th>
                <th>تشخيص فرعي</th>
                <th>رقم الزيارة</th>
                <th>العلاج</th>
                <th>ملخص الحالة</th>
                <th>التوصيات</th>
                <th>Consciousness</th>
                <th>Orientations</th>
                <th>Attention_Concentration</th>
                <th>Memory</th>
                <th>Appearance and Behavior</th>
                <th>Affect_Mood</th>
                <th>Suicide_Homicide</th>
                <th>Speech</th>
                <th>Thinking</th>
                <th>Perception</th>
                <th>Insight</th>
                <th>الفحوصات</th>
                <th>تقييم وضع الحالة</th>
                 <?php if (!$rule_id == 24 ) { ?> 
                <th>تعديل</th>
                <?php } ?>
            </tr>
        </thead>
        <tfoot>
            <th>Sno.</th>
            <th>اسم الموظف</th>
            <th class="hidden-phone">الفرع</th>
            <th>جديد/عودة</th>
            <th>رقم كود الحالة</th>
            <th>العمر</th>
            <th>الجنس</th>
            <th>التاريخ</th>
            <th>تشخيص رئيسي</th>
            <th>تشخيص فرعي</th>
            <th>رقم الزيارة</th>
            <th>العلاج</th>
            <th>ملخص الحالة</th>
            <th>التوصيات</th>
            <th>Consciousness</th>
            <th>Orientations</th>
            <th>Attention_Concentration</th>
            <th>Memory</th>
            <th>Appearance and Behavior</th>
            <th>Affect_Mood</th>
            <th>Suicide_Homicide</th>
            <th>Speech</th>
            <th>Thinking</th>
            <th>Perception</th>
            <th>Insight</th>
            <th>الفحوصات</th>
            <th>تقييم وضع الحالة</th>
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

            // تفكيك العلاج/الفحوصات
            $medical_list = array_filter(array_map('trim', explode(',', $row['medical'] ?? '')));
            $lap_list     = array_filter(array_map('trim', explode(',', $row['lap'] ?? '')));
            ?>
            <tr>
                <td><?php echo $cnt++; ?></td>
                <td><?php echo htmlspecialchars($sender_name); ?></td>
                <td><?php echo htmlspecialchars($branch_name); ?></td>
                <td><?php echo htmlspecialchars($row['type']); ?></td>
                <td><?php echo htmlspecialchars($row['code']); ?></td>
                <td><?php echo htmlspecialchars($row_brithday_sex['age']); ?></td>
                <td><?php echo htmlspecialchars($row_brithday_sex['sex']); ?></td>
                <td><?php echo htmlspecialchars($row['date']); ?></td>
                <td><?php echo htmlspecialchars($row['diagnosis']); ?></td>
                <td><?php echo htmlspecialchars($row['sub_diagnosis']); ?></td>
                <td><?php echo htmlspecialchars($row['visites']); ?></td>
                <td><?php foreach ($medical_list as $m) echo htmlspecialchars($m) . '<br>'; ?></td>
                <td><?php echo htmlspecialchars($row['summerie']); ?></td>
                <td><?php echo htmlspecialchars($row['end_diagnosis']); ?></td>
                <td><?php echo htmlspecialchars($row['Consciousness']); ?></td>
                <td><?php echo htmlspecialchars($row['Orientations']); ?></td>
                <td><?php echo htmlspecialchars($row['Attention_Concentration']); ?></td>
                <td><?php echo htmlspecialchars($row['Memory']); ?></td>
                <td><?php echo htmlspecialchars($row['Appearance']); ?></td>
                <td><?php echo htmlspecialchars($row['Affect_Mood']); ?></td>
                <td><?php echo htmlspecialchars($row['Suicide_Homicide']); ?></td>
                <td><?php echo htmlspecialchars($row['Speech']); ?></td>
                <td><?php echo htmlspecialchars($row['Thinking']); ?></td>
                <td><?php echo htmlspecialchars($row['Perception']); ?></td>
                <td><?php echo htmlspecialchars($row['Insight']); ?></td>
                <td><?php foreach ($lap_list as $l) echo htmlspecialchars($l) . '<br>'; ?></td>
                <td><?php echo htmlspecialchars($row['appraisal']); ?></td>
                <?php if (!$rule_id == 24 ) { ?> 
                <td>
                    <a href="psyshological_edit.php?action=edit&ids=<?php echo (int)$row['id']; ?>">
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
