<?php
function showSQL($sql) {
    $colors = array('SELECT' => 'green', 'FROM' => 'green', 'WHERE' => 'green', 'AND' => 'green',
            'ORDER BY' => 'blue', 'GROUP BY' => 'green',
            'LIKE' => 'yellow', 'JOIN' => 'yellow', 'LEFT' => 'yellow',
            'DESC' => 'magenta', 'LIMIT' => 'magenta', '%' => 'red');
    $out = '<style>
	#sqlback {
	font-size:12px;
	background: black;
	color: white;
	padding: 10px;
	font-family: Courier New, Courier, monospace;
	}
	.green {
	color:#00FF00;
	}
	.red {
	color:#FB4F53;
	}
	.yellow {
	color:#FFFF80;
	}
	.blue {
	color:#80FFFF;
	}
	.magenta {
	color:#FF80C0;
	}
	
	</style>'."\n";

    foreach ($colors as $word => $class) $sql = str_replace($word, "<span class='$class'>$word</span>", $sql);

    $out .= "<div id='sqlback'>\n".nl2br($sql)."</div>\n";
    echo $out;
}

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