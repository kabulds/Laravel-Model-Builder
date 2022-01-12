<?php

/*
Add route
Route::get('/query/{method?}/{id?}',[TestController::class,"query"]);
*/
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Tb_employee;

class EmployeeController extends Controller
{

    public function index() {
      $this->query();
    }

    public function query($method="list",$employee_id=0) {
      $data = new Tb_employee();
      switch($method) {
        case "list":
          $data->query();
          return $data->fields;
        case "insert":
          $data->query();
          $lastid = count($data->fields) + 1;
          $data->field['employee_no'] = sprintf("%02d", $lastid); // $_GET['employee_no']
          $data->field['employee_name'] = "Employee " . $data->field['employee_no']; // $_GET['employee_name']
          $result = $data->insert();
          if  ( $result == true ) {
            return $this->query('list');
          } else {
            return $result;
          }; 
        case "update":
          $data->select($employee_id);
          $data->field['employee_name'] = $data->field['employee_name'] . " updated"; // $_GET['employee_name']
          $result = $data->update();
          if  ( $result == true ) {
            return $this->query('list');
          } else {
            return $result;
          }; 
        case "delete":
          $data->select($employee_id);
          $result = $data->delete();
          if  ( $result == true ) {
            return $this->query('list');
          } else {
            return $result;
          }; 
        }

    }

}
