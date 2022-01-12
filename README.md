# Laravel-Model-Builder

Database :
MySQL

=======================================================================================
ELOQUENT MODEL
Create model using laravel eloquent 
How to use eloquent ??
Please read at : https://laravel.com/docs/8.x/eloquent

=======================================================================================
QUERY BUILDER MODEL
Create model using laravel query builder
Installation :
1. Make connection to your database using config file ({base_path}/config/database.php)
2. Copy BuildModel.php to {base_path}/app/Http/Controllers/
3. Call controller using {base_url)/buildmodel/
4. Select table and mode type (Eloquent or query builder)
5. new model file will be automatically created in the folder {base_path}/app/Models/{table_name}.php

Classes :
1. $model->field  // contain single field array
2. $model->fields //contain record array
3. $model->select($keys) // select record (return true or false)
4. $model->insert() // insert record (return empty string or error message)
5. $model->update() // update record (return empty string or error message)
6. $model->delete() // delete record (return empty string or error message)
7. $model->execute($parameter) // $paramter = "insert", or  "update", or "delete"


