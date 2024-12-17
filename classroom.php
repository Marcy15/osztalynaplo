<?php
session_start();
require 'classroom-data.php';
require 'classroom-render.php';
if (isset($_POST['view'])) {
    $_SESSION['view'] = $_POST['view'];
}
$currentAvarageView = DATA["classes"][0];
if (isset($_POST['export_csv'])) {
    $class = $_POST['view'];
    $timestamp = date('Y-m-d_Hi');
    ob_clean();


    $filename = "{$class}-{$timestamp}.csv";
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $file = fopen('php://output', 'w');
    $header = ['Osztaly', 'Nev', 'Nem', 'Tantargy', 'Jegyek'];
    fputcsv($file, $header);

    if($class == "all") {
        foreach(DATA["classes"] as $c) {
            $students = $_SESSION['classList'][$c];
            foreach ($students as $student) {
                foreach ($student['grades'] as $subject => $grades) {
                    fputcsv($file, [
                        $student['class'],
                        $student['name'],
                        $student['gender'],
                        $subject,
                        implode(', ', $grades),
                    ]);
                }
            }
        }
    } else {
        $students = $_SESSION['classList'][$class];
        foreach ($students as $student) {
            foreach ($student['grades'] as $subject => $grades) {
                fputcsv($file, [
                    $student['class'],
                    $student['name'],
                    $student['gender'],
                    $subject,
                    implode(', ', $grades),
                ]);
            }
        }
    }

    fclose($file);
    exit;
}
function generateCsv($filename, $data,$vanKey = false) {
    ob_clean();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    foreach ($data as $key => $row) {
        if(is_string(($row))) {
            $row = [$row];
        } else {
            foreach ($row as &$item) {
                if (is_array($item)) {
                    $item = implode(", ", array: $item);
                }
            }
        }
        
        if($vanKey) {
            array_unshift($row, $key);
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}
if (isset($_POST['newSchool'])) {

    $_SESSION['classList'] = generateClassList();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
if (isset($_POST['fDownload'])) {
    $averages = [];
    $timestamp = date('Y-m-d_Hi');
    foreach ($_SESSION["classList"][$currentAvarageView] as $student) {
        $averages[$student["name"]] = $student["average"];
    } 
    arsort($averages);
    generateCsv("byClass-{$timestamp}.csv",getAvaragesByClass($averages));
}

if (isset($_POST['sDownload'])) {
    $timestamp = date('Y-m-d_Hi');
    generateCsv("bySubject-{$timestamp}.csv",getAvaragesBySubjects($currentAvarageView),true);
}
if (isset($_POST['tDownload'])) {
    $timestamp = date('Y-m-d_Hi');
    generateCsv("subjectAvarages-{$timestamp}.csv",getSubjectAvarages($currentAvarageView),true);
}

echo '<link rel="stylesheet" href="style.css">';
echo '<div id="main">';


function generateStudent($class) {
    $lastname = DATA['lastnames'][array_rand(DATA['lastnames'])];
    $isMale = (bool) rand(0, 1);
    $firstname = $isMale ? DATA['firstnames']['men'][array_rand(DATA['firstnames']['men'])] : DATA['firstnames']['women'][array_rand(DATA['firstnames']['women'])];
    
    $grades = [];
    $average = 0;
    $needToDivideBy = 0;
    foreach (DATA['subjects'] as $subject) {
        $gradeCount = rand(0, 5);
        $grades[$subject] = array_map(fn() => rand(1, 5), range(1, $gradeCount));
        foreach($grades[$subject] as $grade) {
            $average += $grade;
            $needToDivideBy += 1;
        }
    }
    $average /= $needToDivideBy;
    
    $gender = "Fiú";
    if($isMale == 0) {
        $gender = "Lány";
    }
    $student = array();
    $student['name'] = $lastname . ' ' . $firstname;
    $student['class'] = $class;
    $student['grades'] = $grades;
    $student['gender'] = $gender;
    $student["average"] = floatval(number_format((float)$average, 2, '.', ''));


    return $student;
}

function calcClassAverage($class){
    $average = 0;
    foreach($_SESSION['classList'][$class] as $student) {
        $average += $student["average"];
    }
    $average /= count($_SESSION['classList'][$class]);
    return number_format((float)$average, 2, '.', '');;
}

function generateClassList() {
    $classList = [];
    foreach (DATA['classes'] as $class) {
        $studentCount = rand(10, 15);
        for ($i = 0; $i < $studentCount; $i++) {
            $classList[$class][] = generateStudent($class);
        }
    }
    return $classList;
}
$meghivva = 0;
$first = false;
if (!isset($_SESSION['classList'])) {
    $_SESSION['classList'] = generateClassList();
    $first = true;
}
$currentOption = 0;
displayDropdownMenu();
$averageMenu = false;
foreach(DATA["classes"] as $class) {
    if(isset($_POST[$class])) {
        echo "<script>
            document.getElementById('classOption').selectedIndex=".(count(DATA['classes'])+1)."
            </script>
            ";
        $averageMenu = true;
        displayClassAverageSelector();
        displayAveragesByClass($class);
        $currentAvarageView = $class;
    }
}



if (!isset($_POST['view']) && !$averageMenu) {
    displayClass($_SESSION['classList'], "all");
}

function loadAfterRefresh(){
    $view = $_POST['view'];
    $index = 1;
    $currentOption = 0;
    if($view == "averages") {
        $currentOption = count(DATA['classes'])+1;
        displayClassAverageSelector();
        displayAveragesByClass(DATA['classes'][0]);

    } else {
        foreach (DATA['classes'] as $class) {
            if($class == $view) {
                $currentOption = $index;
            }
            $index += 1;
        }
        displayClass($_SESSION['classList'], $view);
    }
    
    echo "<script>
    document.getElementById('classOption').selectedIndex=".$currentOption."
    </script>
    ";
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['view'])) {
    loadAfterRefresh();
}



echo '</div>';
?>


