<?php 

/**
 * xcel : A simple data import script from excel copy and paste. Inspired by a MailChimp feature.
 *
 * @author    Jean-François Lefebvre <lefebvre.jf at gmail dot com>
 * @license   MIT License
 */

include 'config.inc.php'; 

?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
		<?php if ($prod) { ?>
        <link rel="stylesheet" href="css/xcel.min.css"> 
        <?php } else { ?>
		<link rel="stylesheet" href="css/xcel.css"> 
		<link rel="stylesheet" href="css/codemirror.css"> 
		<link rel="stylesheet" href="css/codemirror-evolution.css">
        <?php } ?>
        <!-- Place favicon.ico and apple-touch-icon.png in the root directory -->
    </head>
    <body>
        <!--[if lt IE 7]>
            <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

<form name="excel-form" action="/index.php" method="POST">

<?php

function getTableInfo() {

    global $config;

    $infos = null;

    try {

        $dbh = new PDO('mysql:host='.$config['host'].';dbname='.$config['database'], $config['username'], $config['password']);
        $infos = $dbh->query('describe ' . $config['table']);
        $dbh = null;

    } catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }

    return $infos;
}

function getHTMLSelect() {

    $htmlSelect  = '<select class="col-name-field mar-b0 select-small" name="field[]">';
    $htmlSelect .= '<option value="-">-</option>';
    
    $infos = getTableInfo();
    
    foreach($infos as $row) {
        $htmlSelect .= '<option value="' . $row['Field'] . '">' . $row['Field'] . ' - ' . $row['Type'] . '</option>';
    }

    $htmlSelect .= '</select>';

    return $htmlSelect;
}

$html = getHTMLSelect();

$step = (!empty($_POST) && isset($_POST['step'])) ? $_POST['step'] : '';

switch($step) {

    case 'map':
        
            $str = $_POST['import-text'];
            
            $stats = count_chars($str);

            // check if at least, there is TABS and CRLF : means that it's a copy and paste
            if ($stats[9]>0 && $stats[10]>0 && $stats[13]>0) {

                $arrayCode = array();
                $rows = explode("\n", $str);
                foreach($rows as $idx => $row)
                {
                    $row = explode( "\t", $row );
                    foreach( $row as $field )
                    {
                        $arrayCode[$idx][] = $field;
                    }
                }

                // remove duplicate lines   
                $arrayCode = array_map("unserialize", array_unique(array_map("serialize", $arrayCode)));

                $columns = $arrayCode[0];

                echo '<div id="map-columns">';
                echo '<table id="import-map">';
                echo '<thead>';
                echo '<tr>';
                foreach ($columns as $index=>$column) {
                    echo '<th id="header-' . $index . '" class="header-cell map-activecol"><label for="col-name-' . $index . '" class="">Column Name</label>' . $html . '</th>';
                }
                echo '<th class="clean"><button id="import" name="import" type="submit">Import</button></th>';
                echo '</thead></tr>';
                echo '<tbody>';
                foreach ($arrayCode as $row) {
                    echo '<tr>';
                    foreach ($row as $key => $value) {
                        echo '<td style="min-width: 100px;" class="map-activecol">' . $value . '</td>';
                    }
                    echo '<td class="clean"></td></tr>';
                }
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
                
                $serializedData = base64_encode(serialize($arrayCode));
                
                echo '<input type="hidden" name="data" value="' . $serializedData . '" />';
                echo '<input type="hidden" name="step" value="import" />';      

            } else {
                echo 'This is not a valid copy and paste from excel';
            }

        break;

    case 'import':
    
            $fields = $_POST['field'];
     
            $infos = getTableInfo();
            // var_dump($infos);die();
            $tableInfos = array();
            foreach ($infos as $row) {
                $tableInfos[] = $row;
            }

            // build an array to check later if a field is a string 
            $fieldsIsString = array();
            foreach ($fields as $field) {
              
                foreach ($tableInfos as $row) {
                    
                    if ($row[0] === $field) {
                        $fieldsIsString[] = (strpos($row[1], 'varchar')!==FALSE) ? 1 : 0;
                    }
                }
            }
            
            //var_dump($fields);
            //var_dump($fieldsIsString);
            //die();

            $data = $_POST['data'];
            $data = unserialize(base64_decode($data));
    
            // Build sql query             
            $sql = 'insert into ' . $config['table'] . '(' . implode($fields, ',') . ') values ';
            $idx = 0;
            foreach ($data as $row) {
                if ($idx>0) {
                    $sql .= ',';
                }

                $sql .= '(';
                $idx2 = 0;
                foreach ($row as $index=>$value) {
                    if ($idx2>0) {
                        $sql .= ',';
                    }   
                    // must add ' if fields[$index] is of type string
                    if ($fieldsIsString[$index]) {
                        $sql .= "'" . $value . "'"; 
                    } else {
                        $sql .= $value; 
                    }
                    $idx2++;
                }
                $sql .= ')';
                $idx++;
            }
            $sql .= ';';
            
            // echo '<pre>' . $sql . '</pre>';
            // echo 'import data';

            global $config;

            $infos = null;

            try {

                $dbh = new PDO('mysql:host='.$config['host'].';dbname='.$config['database'], $config['username'], $config['password']);
                $count = $dbh->exec($sql);
                $dbh = null;
                echo '<div>' . $count . ' elements inserted successfully ! :)</div>';

            } catch (PDOException $e) {
                print "Error!: " . $e->getMessage() . "<br/>";
                die();
            }


        break;

    default:
?>
        <div class="codemirror-border-wrap full-width nowrap">
            <textarea name="import-text" id="import-text" placeholder="Email Address...    First Name...    Last Name...
        Email Address...    First Name...    Last Name...
        Email Address...    First Name...    Last Name...
        Email Address...    First Name...    Last Name...
        Email Address...    First Name...    Last Name..."></textarea>
        </div>

        <input id="step" type="hidden" name="step" value="map" /> 
        <button id="upload" name="upload" type="submit">Upload</button>
<?php

} // case

?>

</form>

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.10.2.min.js"><\/script>')</script>
        <?php if ($prod) { ?>
        <script src="js/xcel.min.js"></script> 
        <?php } else { ?>
        <script src="js/codemirror.js"></script> 
        <script src="js/placeholder.js"></script> 
        <?php } ?>
        <script>
        $( document ).ready(function() {
 			CodeMirror.fromTextArea(
 				$('#import-text')[0], {
                    mode: "text",
                    autofocus: true,
                    tabMode: "indent",
                    indentUnit: 2,
                    smartIndent: false,
                    theme: "default",
                    autoClearEmptyLines: true,
                    lineWrapping: false,
                    lineNumbers: true
                });
		});

 		</script>
    </body>
</html>