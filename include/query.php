<?php

// $stmu = null;
// $total_results = null;
// $total_pages = 0;
// $per_page = 10;


function queryWithPagination(
    $con,
    $table,
    $showToAll = false,
    $query = null,
    $additional_where = null) {
    // global $total_results, $total_pages, $per_page;
    if (isset($_GET['search']) ) {
        $search = $_GET['search'];

    }else{
        $search= null;
    }

    if ($_SESSION['user']['rule_id'] == 2 || $_SESSION['user']['rule_id'] == 6 || $showToAll) {

        if (isset($_GET['from_date']) && isset($_GET['to_date'])) {
            exportJs();
            return export($con, $table);
        }

        $sql = 'SELECT COUNT(*) FROM ' . $table;

        if ($search) {
            $sql .= " where code = $search";
        }

        if ($additional_where) {
            if (strpos($sql, 'where') === false) {
                $sql .= " where  " . $additional_where;
            } else {
                $sql .= " and  " . $additional_where;
            }
        }

        $stmt = $con->query($sql);

        if ($stmt) {
            $total_results = $stmt->fetchColumn();
        }

        $per_page = 10;

        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

        $total_pages = ceil($total_results / $per_page);

        $starting_limit = ($page - 1) * $per_page;

        // $date = date('Y-1-1');

        $sql = "SELECT * FROM " . $table;

        if ($search) {
            $sql .= " where code = $search";
        }




        if ($showToAll) {
            if (strpos($sql, 'where') !== false) {
                $sql .= " and branch " . $_SESSION['user']['branch'];
            } else {
                $sql .= " where branch " . $_SESSION['user']['branch'];
            }
        }


        //




        $sql .= " order by id desc  LIMIT :limit OFFSET :offset";

        if ($query) {
            $sql = $query($sql);
        }
        //


        $stmu = $con->prepare($sql);

        $stmu->bindParam(':limit', $per_page, PDO::PARAM_INT);
        $stmu->bindParam(':offset', $starting_limit, PDO::PARAM_INT);

        $stmu->execute();

        // if ($showToAll == false) {
        //append search to table
        echo ' <script src="assets/js/add_search_table.js"></script>';
        // }
    } else {
        $date = date('Y-m-1');

        $user = $_SESSION['user']['id'];
        $sql = "SELECT * FROM " . $table . " where sender_name =? and Date(date_data_come)>=? ";

        if ($search) {
            $sql .= " and code = $search";
        }


        $stmu = $con->prepare($sql);
        $stmu->execute(array($user, $date));
        $total_results = $stmu->rowCount();
    }



    appendPagination($total_pages ?? [], $page ?? null, $total_results);


    return $stmu;
}

function export($con, $table)
{

    $sql = "SELECT * FROM " . $table . " where Date(date_data_come) >= ? and Date(date_data_come) <= ?  order by id desc";
    $stmu = $con->prepare($sql);
    $stmu->execute(array($_GET['from_date'], $_GET['to_date']));
    return  $stmu;
}


function exportJs()
{

    echo "
    <script>

    $(document).ready(function() {
      var table2excel = new Table2Excel();
      table2excel.export(document.querySelectorAll('table'));
     });
   
    </script>
    ";
}



function appendPagination($total_pages, $_page, $total_results)
{

    //

    $el = "<div class='d-flex justify-content-between align-items-center'>";

    if ($_page) {
        $el .= '<div> showing ' . ($_page - 1) * 10 . ' to ' . $_page * 10 . ' of ' . $total_results . '</div>';



        $links = "<ul class='pagination flex justify-content-center' id='admin-pagination' > " . PHP_EOL;

        $prev_page = ($_page == 1) ? null : $_page - 1;

        if ($prev_page) {
            $prev = '<li class="paginate_button page-item previous " ><a href="?page=' . $prev_page . '" class="page-link">السابق</a></li>';
            $links .= $prev;
        }


        foreach (range(1, $total_pages)  as $page) {



            if ($page == $_page) {
                $link = '
            <li class="paginate_button page-item  active">
            <a href="?page=' . $page . '" class="page-link active-page">' . $page . '</a> </li>' . PHP_EOL;
                $links .= $link;
                continue;
            }

            if ($page > 3 && $page < ($total_pages - 2)) {
                if ($page == 4) {
                    $link = '
                <li class="paginate_button page-item disable">
                <a  class="page-link disable ">...</a> </li>' . PHP_EOL;
                    $links .= $link;
                }
                continue;
            }

            $link = '
        <li class="paginate_button page-item ">
        <a href="?page=' . $page . '" class="page-link">' . $page . '</a> </li>' . PHP_EOL;
            $links .= $link;
        }
        $next_page = ($total_pages == $_page) ? null : $_page + 1;

        if ($next_page) {
            $next = '<li class="paginate_button page-item" ><a href="?page=' . $next_page . '" aria-controls="multi-filter-select"  class="page-link">التالي</a></li>';
            $links .= $next;
        }

        $links .= "</ul>";
    } else {
        $el .= '<div> showing '  . $total_results . ' results' . '</div>';
    }



    $el .= $links;
    $el .= "</div>";


    echo <<<EOD
     <script type="text/javascript">
     $(document).ready(function() {
          $('.table').after(`$el`);
     });
     </script>
EOD;
}

function delete($con, $table)
{
    if ($_SESSION['user']['rule_id'] == 2 || $_SESSION['user']['rule_id'] == 6) {
        if (isset($_GET['action'], $_GET['ids']) && $_GET['action'] == 'delete') {
            $id = $_GET['ids'];
            $sql = "DELETE FROM " . $table . " WHERE id=?";
            $stm = $con->prepare($sql);
            $stm->execute(array($id));
            if ($stm->rowCount() > 0) {
                echo "<div class='alert alert-success'>One Row Deleted</div>";
                echo "<script>
                window.open('" . basename($_SERVER['PHP_SELF']) . "','_self');
                </script>";
            } else {
                echo "<div class='alert alert-danger'>One Row Not Deleted</div>";
            }
        }

        appendDeteleActionTableHeader();
    }
}

function appendDeteleActionTableHeader()
{
    echo "<script type='text/javascript'>
    $(document).ready(function() {
    $('.table thead tr').append('<th>حذف</th>');
        $('table tfoot tr').append('<th>حذف</th>');

    });
    </script>";
}
function queryOfReportData(
    $con,
    $table,
    $showToAll = false,
    $query = null,
    $additional_where = null
    
    
) {
    // global $total_results, $total_pages, $per_page;
    if (isset($_GET['search']) ) {
        $search = $_GET['search'];

    }else{
        $search= null;
    }

    if ($_SESSION['user']['rule_id'] == 2 || $_SESSION['user']['rule_id'] == 6 || $showToAll) {

        if (isset($_GET['from_date']) && isset($_GET['to_date'])) {
            exportJs();
            return export($con, $table);
        }

        $sql = 'SELECT COUNT(*) FROM ' . $table;

        if ($search) {
            $sql .= " where code = $search";
        }

        if ($additional_where) {
            if (strpos($sql, 'where') === false) {
                $sql .= " where  " . $additional_where;
            } else {
                $sql .= " and  " . $additional_where;
            }
        }

        $stmt = $con->query($sql);

        if ($stmt) {
            $total_results = $stmt->fetchColumn();
        }

        $per_page = 10;

        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

        $total_pages = ceil($total_results / $per_page);

        $starting_limit = ($page - 1) * $per_page;

        // $date = date('Y-1-1');

        $sql = "SELECT * FROM " . $table;

        if ($search) {
            $sql .= " where code = $search";
        }




        if ($showToAll) {
            if (strpos($sql, 'where') !== false) {
                $sql .= " and branch " . $_SESSION['user']['branch'];
            } else {
                $sql .= " where branch " . $_SESSION['user']['branch'];
            }
        }


        //




        $sql .= " order by id desc  LIMIT :limit OFFSET :offset";

        if ($query) {
            $sql = $query($sql);
        }
        //


        $stmu = $con->prepare($sql);

        $stmu->bindParam(':limit', $per_page, PDO::PARAM_INT);
        $stmu->bindParam(':offset', $starting_limit, PDO::PARAM_INT);

        $stmu->execute();

        // if ($showToAll == false) {
        //append search to table
        echo ' <script src="assets/js/add_search_table.js"></script>';
        // }
    } else {
        $date = date('Y-m-1');

        $user = $_SESSION['user']['id'];
        $sql = "SELECT * FROM " . $table . " where sender_name =? and Date(date_data_come)>=? ";

        if ($search) {
            $sql .= " and code = $search";
        }


        $stmu = $con->prepare($sql);
        $stmu->execute(array($user, $date));
        $total_results = $stmu->rowCount();
    }





    return $stmu;
}
