<?php
// ===============================
// report_data/group_sessions_data.php
// ===============================

// 1) نافذة التاريخ:
//    - لو الواجهة القديمة مرسلة $from_date/$to_date نستخدمها.
//    - لو مش موجودة: نثبت الشهر الحالي.
if (empty($from_date) || empty($to_date)) {
    date_default_timezone_set('Asia/Aden');
    $from_date = date('Y-m-01'); // أول يوم في الشهر
    $to_date   = date('Y-m-t');  // آخر يوم في الشهر
}

// 2) أذونات الأدوار ومنطق الفروع عند عدم وجود branch في POST
$rule_id      = (int)($_SESSION['user']['rule_id'] ?? 0);
$can_view_all = in_array($rule_id, [2, 6], true); // الواجهة القديمة: 2 و 6 يشوفوا كل الفروع

// 3) بناء الاستعلام ديناميكيًا
$sql    = "SELECT * FROM group_sessions WHERE `date` BETWEEN ? AND ?";
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
                <th>اسم الموظف</th>
                <th>الفرع</th>
                <th>رقم اكواد الحالات</th>
                <th>نوع المشكلة</th>
                <th>اسم الجلسة</th>
                <th>نوع الجلسة</th>
                <th>المواضيع التي تم مناقشتها</th>
                <th>عدد الحضور</th>
                <th>الأساليب العلاجية المستخدمة</th>
                <th>المهام والواجبات</th>
                <th>التاريخ</th>
                <th>تعديل</th>
            </tr>
        </thead>
        <tfoot>
            <th>اسم الموظف</th>
            <th>الفرع</th>
            <th>رقم اكواد الحالات</th>
            <th>نوع المشكلة</th>
            <th>اسم الجلسة</th>
            <th>نوع الجلسة</th>
            <th>المواضيع التي تم مناقشتها</th>
            <th>عدد الحضور</th>
            <th>الأساليب العلاجية المستخدمة</th>
            <th>المهام والواجبات</th>
            <th>التاريخ</th>
            <th>تعديل</th>
        </tfoot>
        <tbody>
            <?php
            foreach ($rows as $row) {
                // اسم الموظف
                $stmUser = $con->prepare("SELECT name FROM user WHERE id = ?");
                $stmUser->execute([$row['sender_name']]);
                $nameRow     = $stmUser->fetch(PDO::FETCH_ASSOC);
                $sender_name = $nameRow['name'] ?? '';

                // اسم الفرع
                $stmBranch = $con->prepare("SELECT branch_name FROM branch WHERE id = ?");
                $stmBranch->execute([$row['branch']]);
                $branchRow   = $stmBranch->fetch(PDO::FETCH_ASSOC);
                $branch_name = $branchRow['branch_name'] ?? '';

                // تفكيك الأكواد (قائمة مفصولة بفواصل)
                $codes = array_filter(array_map('trim', explode(',', $row['code'] ?? '')));
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($sender_name); ?></td>
                    <td><?php echo htmlspecialchars($branch_name); ?></td>
                    <td>
                        <?php
                        foreach ($codes as $code) {
                            echo htmlspecialchars($code) . '<br>';
                        }
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['problem_many']); ?></td>
                    <td><?php echo htmlspecialchars($row['section_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['section_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['projict']); ?></td>
                    <td><?php echo htmlspecialchars($row['attendance']); ?></td>
                    <td><?php echo htmlspecialchars($row['techniques']); ?></td>
                    <td><?php echo htmlspecialchars($row['task_andduties']); ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                    <?php if (!$rule_id == 24 ) { ?> 
                    <td>
                        <a href="group_sessions_edit.php?action=edit&ids=<?php echo (int)$row['id']; ?>">
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
