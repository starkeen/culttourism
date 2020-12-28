<?php

function print_x($data) {
    if(is_array($data)) {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    elseif(is_bool($data)) {
        echo '<p>BOOLEAN: ';
        if ($data) echo 'TRUE';
        else echo 'FALSE';
    }
    elseif(is_float($data)) {
        echo '<p>FLOAT: ' . $data;
    }
    elseif (is_string($data)) {
        echo "<p>STRING: <pre>";
        echo $data;
        echo "</pre>";
    }
    elseif(is_object($data)) {
        echo '<p>OBJECT: ';
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    else {
        echo '<p>DATA: '.$data;
    }
}

?>
