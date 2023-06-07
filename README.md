# php-datatable
Datatable server side processing


#Example 
On Server side just use

Add package via composer

```
composer require Waqarali/datatable
```

```php
$dt->setTable("table1 t1");
$dt->setPrimaryKey("t1.id");
$dt->joinTable(array(
    'type' => "left",
    'table' => "table2 t2",
    'primary' => 't1.id',
    'secondary' => 't2.ref_id'
));
$dt->joinTable(array(
    'type' => "left",
    'table' => "table3 t3",
    'primary' => 't3.id',
    'secondary' => 't2.ref_id2'
));

$dt->addColumn(array( 'db' => 't1.id', 'alies' => 'id',  'dt' => 0 , 'type' => 1 ));
$dt->addColumn(array( 'db' => 't1first_name', 'alies' => '', 'dt' => 1 , 'type' => 2));
$dt->addColumn(array( 'db' => 't1.last_name', 'alies' => '', 'dt' => 2 , 'type' => 2));
$dt->addColumn(array( 'db' => 't1.email', 'alies' => '', 'dt' => 3 , 'type' => 2));
$dt->addColumn(array( 'db' => 't3.name', 'alies' => 'name', 'dt' => 4 , 'type' => 2));
$dt->addColumn(array(
    'db'        => 't2.created_date',
    'alies' => 'created_date',
    'dt'        => 5,
    'type'        => 99,
    'formatter' => function( $d, $row ) {
        return date( 'd-m-Y', strtotime($d));
    }
));

echo json_encode($dt->getData());