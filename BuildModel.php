<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuildModel extends Controller
{

    public $fileid = 0;
    public $classname = "";

    public function page_start() {
        echo '<html lang="en">';
        echo '<head>';
        echo '<meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<meta http-equiv="x-ua-compatible" content="ie=edge">';
        echo '<title>Class tools</title> ';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">';
        echo '</head>';
        echo '<body>';
        echo '<div class="container">';
    }

    public function page_end() {
        echo '</div>';
        echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>';
        echo '<body>';
    }

    
    public function sqlquery($sql) {
        $temp = DB::select($sql);
        return json_decode(json_encode($temp), true);
    }

    public function get_info() {
        $dbname = config('database.connections.mysql.database');
        $temp = $this->sqlquery("SHOW TABLES");
        $tables = array();
        foreach ( $temp as $table ) {
            //get columns
            $tblname = $table['Tables_in_' . $dbname];
            $tables[$tblname]['name'] = $tblname;
            $tables[$tblname]['columns'] = array();
            $tables[$tblname]['auto_increment'] = "";
            $cols = $this->sqlquery("SHOW COLUMNS FROM " . $tblname);
            $columns = array();
            foreach ( $cols as $col ) {
                $fldname = $col['Field'];
                $fldtype = $col['Type'];
                $pos1 = strpos($fldtype,"(");
                if ( $pos1 > 0 ) {
                    $col['Type'] = substr($fldtype,0,$pos1);
                    $pos2 = strpos($fldtype,")");
                    $col['Size'] = substr($fldtype,$pos1+1,$pos2-$pos1);
                } else {
                    $col['Type'] = $fldtype;
                    $col['Size'] = "";
                };
                $col['Default'] = '""'; 
                $pos = strpos("nothing|int|float|decimal|double|real:bit|boolean", $col['Type'], 0);
                if ( $pos > 0 ) {$col['Default'] = '0'; };
                if ( $col['Type'] == "datetime" ) { $col['Default'] = '0000-00-00 00:00:00'; };
                if ( $col['Type'] == "date" ) { $col['Default'] = '0000-00-00'; };
                if ( $col['Type'] == "time" ) { $col['Default'] = '00:00:00'; };
                if ( $col['Type'] == "tinyint" ) { $col['Default'] = true; };      
                //set auto increment
                if ( $col['Extra'] == "auto_increment" ) {
                    $tables[$tblname]['auto_increment'] = $fldname;
                };
                $tables[$tblname]['columns'][$fldname] = $col;
            };
            $temp = $this->sqlquery("SHOW INDEX FROM " . $tblname);
            $tables[$tblname]['indexes'] = array();
            $tables[$tblname]['primarykey'] = "";
            foreach($temp as $index) {
                $tables[$tblname]['indexes'][$index['Column_name']] = $index;
                if ( $index['Key_name']=="PRIMARY" ) { $tables[$tblname]['primarykey']= $index['Column_name']; };
            };
        };
        return $tables;
    }

    public function index() {
        $tables = $this->get_info();
        $tblname = ( isset($_GET['table']) ) ? $_GET['table'] : "";
        $type = ( isset($_GET['type']) ) ? $_GET['type'] : "";
        $this->page_start();
        echo "<div class='container'>";
            echo "<h1 class='display-4 mt-2'>Laravel Model Builder</h1>";
            echo "<form class='row'>";
                echo "<div class='col-md-3 form-group'>";
                    echo "<label class='col-form-label'>Select Table</label>";
                    echo "<select class='form-control' id='table' name='table'>";
                    foreach ( $tables as $table) {
                        echo "<option value='" . $table['name'] . "'>" .  $table['name'] . "</option>";
                    };
                    echo "</select>";                    
                echo "</div>";
                echo "<div class='col-md-3 form-group'>";
                    echo "<label class='col-form-label'>Model Type</label>";
                    echo "<select class='form-control' id='type' name='type'>";
                        echo "<option value='eloquent'>Eloquent Model</option>";
                        echo "<option value='query' selected='selected'>Database Query Class</option>";
                    echo "</select>";                    
                echo "</div>";
                echo "<div class='col-md-3 form-group'>";
                    echo "<label class='col-form-label'>&nbsp;</label>";
                    echo "<button type='submit' class='btn btn-primary'>Go</button>";
                echo "</div>";

            echo "</form>";
        echo "</div>";
        if ( $tblname !== "" ) {
            switch($type) {
                case "eloquent":
                    $this->build_eloquent($tables[$tblname]); 
                    break;
                case "query":
                    $this->build_query($tables[$tblname]); 
                    break;
            }
        }
        $this->page_end();
    }

    public function create_file($table) {
        $path = app_path('Models');
        $this->classname = strtoupper(substr($table['name'],0,1)) . substr($table['name'],1,strlen($table['name']));
        $filename = $path . "/" . $this->classname . ".php";
        $filename = str_replace(chr(92),"/",$filename);
        echo "File created at : " . $filename . "<br>";
        if ( file_exists($filename) ) { unlink($filename); };
        $this->fileid = fopen($filename, "w") or die("Unable to open file!");
        $this->write_file("<?php",0,2);
        $this->write_file("namespace App\Models;",0,2);
        return $this->fileid;
    }

    public function write_file($text,$tabs =0, $enter = 1) {
        $text = str_repeat(chr(9), $tabs) . $text . str_repeat(chr(13), $enter)  ;
        $text = str_replace("^",'"',$text);
        //echo $text;
        fwrite($this->fileid,$text);
    }

    public function build_eloquent($table) {
        
        $fileid = $this->create_file($table);
        $this->write_file('use Illuminate\Database\Eloquent\Factories\HasFactory;');
        $this->write_file('use Illuminate\Database\Eloquent\Model;',0,2);
        //begin class
        $this->write_file('class ' . $this->classname . ' extends Model ',0);
        $this->write_file('{',0,2);
            $this->write_file('use HasFactory;',1,2);
            $this->write_file('protected $table = "' . $table['name'] . '";',1);
            $this->write_file('protected $primaryKey = "' . $table['primarykey'] . '";',1);
            if ( $table['auto_increment'] !== "" ) {
                $this->write_file('public $incrementing = true;',1);
            } else {
                $this->write_file('public $incrementing = false;',1);
                $this->write_file('protected $keyType = "string"',1);
            };
            $this->write_file('');
            $this->write_file('protected $attributes = [',1);
            foreach ( $table['columns'] as $column ) {
                $this->write_file('"' . $column['Field'] . '" => ' . $column['Default'] . ',' ,2);
            };
            $this->write_file('];',1);
        //end of class
        $this->write_file('');
        $this->write_file('}',0,2);
        fclose($fileid);

    }

    public function build_query($table) {
        $fileid = $this->create_file($table);
        $tblname = $table['name'];
        //begin class
        $this->write_file('use Illuminate\Support\Facades\DB;',0,2);
        $this->write_file('class ' . $this->classname ,0);
        $this->write_file('{',0,2);
            //variable
            $this->write_file('public $field = array();',1);
            $this->write_file('public $fields = array();',1);
            $this->write_file('');
            // init
            $this->write_file('public function init()',1);
            $this->write_file('{',1);
                $this->write_file('$this->fields = array();',2);
                $this->write_file('$this->field = [',2);
                foreach ( $table['columns'] as $column ) {
                    $this->write_file('"' . $column['Field'] . '" => ' . $column['Default'] . ',' ,3);
                }
                $this->write_file('];',2);
            $this->write_file('}',1);

            //query
            $this->write_file('');
            $this->write_file('public function query($filter="",$orderby="",$limit=0)',1);
            $this->write_file('{',1);
                $this->write_file('$data = DB::table("' . $table['name'] . '");',2);
                $this->write_file('if ( $filter !== "" ) { $data->whereRaw($filter); };',2);
                $this->write_file('if ( $orderby !== "" ) { $data->groupByRaw($orderby); };',2);
                $this->write_file('if ( $limit > 0 ) { $data->limit($limit); };',2);
                $this->write_file('$this->fields = json_decode(json_encode($data->get()), true);',2);
                $this->write_file('return count($this->fields);',2);
            $this->write_file('}',1);
            $this->write_file('');

            //select
            $keys = "";
            $primary = "";
            $secondary = "";
            foreach ( $table['indexes'] as $index ) {
                $keys = $keys . '$' . $index['Column_name'] . '='. $table['columns'][$index['Column_name']]['Default'] . ',' ;
                if ( $index['Key_name']=="PRIMARY" ) {
                    $primary = "^(" . $index['Column_name'] . "='^ . $" . $index['Column_name'] . " . ^')^";
                } else {
                    $secondary = $secondary .  $index['Column_name'] . "='^ . $" . $index['Column_name'] . " . ^' AND ";
                };
            };            
            $keys = substr($keys,0,strlen($keys)-1);
            if ( $secondary !== "" ) {
                $secondary = substr($secondary,0,strlen($secondary)-5);
            };
            $this->write_file('public function select(' . $keys . ')',1);
            $this->write_file('{',1);
                $this->write_file('$result = false;',2);
                $filter = '$filter = ' . $primary ;
                if ( $secondary !== "" ) {
                    $filter = substr($filter,0,strlen($filter)-1);
                    $filter = $filter . ' OR (' . $secondary . ")^" ;
                };
                $filter = $filter . ";";
                $this->write_file($filter,2);
                $this->write_file('$this->query($filter);',2);
                $this->write_file('if ( count($this->fields) > 0 ) {',2);
                    $this->write_file('$this->field = $this->fields[0];',3);
                    $this->write_file('$result = true;',3);
                $this->write_file('};',2);
                $this->write_file('return $result;',2);
            $this->write_file('}',1);
            $this->write_file('');

            //insert
            $this->write_file('public function insert()',1);
            $this->write_file('{',1);
              $this->write_file('try {',2);
                if ( $table['auto_increment'] == "" ) {
                  $this->write_file('$result = DB::table("' . $tblname . '")->insert($this->field);',3);
                } else {
                  $this->write_file('$result = DB::table("' . $tblname . '")->insertGetId($this->field);',3);
                  $this->write_file('$this->field["' . $table['auto_increment'] . '"] = $result;' ,3);
                  $this->write_file('$result = true;',3);
                };
              $this->write_file('} catch(\Illuminate\Database\QueryException $error) {',2);
                $this->write_file(' $result = $error->getMessage();',3);
              $this->write_file('}',2);
              $this->write_file('return $result;',2);
            $this->write_file('}',1);
            $this->write_file('');

            //update
            $this->write_file('public function update()',1);
            $this->write_file('{',1);
              $this->write_file('try {',2);
                $this->write_file('$query = DB::table("' . $tblname . '");',3);
                foreach ( $table['indexes'] as $index ) {
                  if ( $index['Key_name'] == "PRIMARY" ) {
                    $this->write_file('$query->where("' . $index['Column_name'] . '", $this->field["' . $index['Column_name'] . '"]);',3);
                  };
                };
                $this->write_file('$effected = $query->update($this->field);',3);
                $this->write_file('$result = ($effected > 0);',3);
              $this->write_file('} catch(\Illuminate\Database\QueryException $error) {',2);
                $this->write_file(' $result = $error->getMessage();',3);
              $this->write_file('}',2);
              $this->write_file('return $result;',2);
            $this->write_file('}',1);
            $this->write_file('');

            //delete
            $this->write_file('public function delete()',1);
            $this->write_file('{',1);
              $this->write_file('try {',2);
                $this->write_file('$query = DB::table("' . $tblname . '");',3);
                foreach ( $table['indexes'] as $index ) {
                  if ( $index['Key_name'] == "PRIMARY" ) {
                    $this->write_file('$query->where("' . $index['Column_name'] . '", $this->field["' . $index['Column_name'] . '"]);',3);
                  };
                };
                $this->write_file('$effected = $query->delete();',3);
                $this->write_file('$result = ($effected > 0);',3);
              $this->write_file('} catch(\Illuminate\Database\QueryException $error) {',2);
                $this->write_file(' $result = $error->getMessage();',3);
              $this->write_file('}',2);
              $this->write_file('return $result;',2);
            $this->write_file('}',1);
            $this->write_file('');

            //execute
            $this->write_file("public function execute($" . "action) {",1);
            $this->write_file('switch ( $' . 'action ) {',2);
            $this->write_file("case 'insert': return $" . "this->insert(); break ;",3);
            $this->write_file("case 'update': return $" . "this->update(); break ;",3);
            $this->write_file("case 'delete': return $" . "this->delete(); break ;",3);
            $this->write_file('};',2);
            $this->write_file("}",2);
            $this->write_file("");            

        //end of class
        $this->write_file('');
        $this->write_file('}',0,2);
        fclose($fileid);
        dd($table);

    }

}
