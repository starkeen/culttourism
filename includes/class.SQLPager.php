<?php

class SQLPager {

    private $_params = array(
        'limit' => 0,
        'offset' => 0,
        'total' => 0,
    );
    private $_limit_default = 20;
    private $var_perpage = array(20, 50, 100, 200);
    private $_page_current = 0;
    private $_get;

    public function __construct($get = array()) {
        if (!empty($get)) {
            $this->_get = $get;
        } else {
            $this->_get = isset($_GET) ? $_GET : array();
        }
        $this->_params['limit'] = $this->_limit_default;

        $this->_page_current = isset($this->_get['page']) ? intval($this->_get['page']) : 0;
        if (isset($this->_get['pager_perpage'])) {
            $this->setParam('limit', intval($this->_get['pager_perpage']));
            unset($this->_get['pager_perpage']);
        }
    }

    private function calcOffset() {
        $this->_params['offset'] = $this->_page_current * $this->_params['limit'];
    }

    public function setParam($p, $v) {
        if ($p == 'limit' && $this->_params['limit'] == $this->_limit_default) {
            $this->_params['limit'] = intval($v);
        } elseif (isset($this->_params[$p]) && $p != 'limit') {
            $this->_params[$p] = intval($v);
        }

        $this->calcOffset();
    }

    public function getParam($p) {
        if (isset($this->_params[$p])) {
            return $this->_params[$p];
        } else {
            return null;
        }
    }

    public function getHTML($show_selector = true, $show_total = false) {
        $pages_cnt = ceil($this->_params['total'] / $this->_params['limit']);

        $out = '<div class="pager_block">';
        $empty_before = false;
        $empty_after = false;

        for ($i = 0; $i <= ($pages_cnt - 1); $i++) {
            $pagebutton = '';
            $linkbutton_array = array_merge($_GET, array('page' => $i));
            $linkbutton = http_build_query($linkbutton_array);
            $linktext = $i + 1;

            if (mb_strlen($linktext) < 2) {
                $linktext = '&nbsp;' . $linktext . '&nbsp;';
            }

            if ($i == $this->_page_current) {//__________ текущая страница
                $pagebutton .= '<span class="pager_nolink" title="вы на странице ' . $linktext . '">' . $linktext . '</span>';
            } elseif (
                    $i == 0                     //первая
                    || abs($i - $this->_page_current) < 3  //по две рядом с текущей
                    || $i == ($pages_cnt - 1)   //последняя
            ) {//__________ первая и последняя страницы, по две сбоку текущей
                $pagebutton .= "<a href=\"?$linkbutton\" class=\"pager_link\" title=\"перейти к странице $linktext\">$linktext</a>";
            } elseif ($i < $this->_page_current && !$empty_before) {//__________ между первой и текущей
                $pagebutton .= '&nbsp;&hellip;&nbsp;';
                $empty_before = true;
            } elseif ($i > $this->_page_current && !$empty_after) {//__________ между последней и текущей
                $pagebutton .= '&nbsp;&hellip;&nbsp;';
                $empty_after = true;
            }

            $out .= $pagebutton;
        }

        if ($show_total) {
            $out .= "всего: {$this->_params['total']} " . Helper::getNumEnding($this->_params['total'], array('строка', 'строки', 'строк'));
        }

        if ($show_selector) {
            //------------------- селектор -------------------
            $out .= '<form method="get" class="pager_form">';
            foreach ($this->_get as $k => $v) {
                $out .= "<input type=\"hidden\" name=\"$k\" value=\"$v\" />";
            }

            $out .= 'отображать по <select name="pager_perpage" class="pager_perpage">';
            foreach ($this->var_perpage as $option) {
                $select = '';
                if ($option == $this->_params['limit']) {
                    $select = 'selected="true"';
                }
                if ($option <= $this->_params['total']) {
                    $out .= "<option value=\"$option\" $select>$option</option>";
                }
            }
            $out .= '</select> на странице</form>';
        }

        $out .= '</div>';

        return $out;
    }

}
