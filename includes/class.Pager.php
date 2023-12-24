<?php

use app\utils\NumberEnding;

class Pager
{
    public $pages = '';
    public $out = [];
    private $items_per_page = 20;
    private $items_per_max = 25;
    private $show_pager = false;
    private $show_selector;
    private $var_perpage = [20, 40, 100, 200];
    private $show_total;
    public $cnt_total = 0;

    public function __construct($all, $show_selector = true, $show_total = false)
    {
        $this->show_selector = $show_selector;
        $this->show_total = $show_total;

        $cur_page = (isset($_GET['page'])) ? (int) $_GET['page'] : 0;
        $cnt_items = count($all);
        $this->cnt_total = $cnt_items;

        if (isset($_GET['pager_perpage']) && (int) $_GET['pager_perpage'] !== 0) {
            $this->items_per_page = (int) $_GET['pager_perpage'];
            $this->items_per_max = $this->items_per_page + 5;
        }
        $cnt_pages = ceil($cnt_items / $this->items_per_page);

        if ($cnt_items >= $this->items_per_max) {
            //------------------- содержимое -------------------
            $this->show_pager = true;

            $i = 0;
            foreach ($all as $id => $item) {
                if (
                    ($i >= $cur_page * $this->items_per_page)
                    && ($i < ($cur_page + 1) * $this->items_per_page)
                ) {
                    $this->out[$id] = $item;
                }
                $i++;
            }
            //------------------- страницы пейджера -------------------
            $this->pages = '<div class="pager_block">';
            $empty_before = false;
            $empty_after = false;
            for ($i = 0; $i <= ($cnt_pages - 1); $i++) {
                $pagebutton = '';
                $linkbutton_array = array_merge($_GET, ['page' => $i]);
                $linkbutton = http_build_query($linkbutton_array);
                $linktext = $i + 1;
                if (mb_strlen($linktext) < 2) {
                    $linktext = '&nbsp;' . $linktext . '&nbsp;';
                }

                if ($i == $cur_page) { //__________ текущая страница
                    $pagebutton .= '<span class="pager_nolink" title="вы на странице ' . $linktext . '">' . $linktext . '</span>';
                } elseif (
                    $i == 0                     //первая
                    || abs($i - $cur_page) < 3  //по две рядом с текущей
                    || $i == ($cnt_pages - 1)   //последняя
                ) { //__________ первая и последняя страницы, по две сбоку текущей
                    $pagebutton .= "<a href=\"?$linkbutton\" class=\"pager_link\" title=\"перейти к странице $linktext\">$linktext</a>";
                } elseif ($i < $cur_page && !$empty_before) {
                    //__________ между первой и текущей
                    $pagebutton .= '&nbsp;&hellip;&nbsp;';
                    $empty_before = true;
                } elseif ($i > $cur_page && !$empty_after) {
                    //__________ между последней и текущей
                    $pagebutton .= '&nbsp;&hellip;&nbsp;';
                    $empty_after = true;
                }

                $this->pages .= $pagebutton;
            }
            if ($this->show_total) {
                $this->pages .= "всего: $this->cnt_total " . NumberEnding::getNumEnding(
                    $this->cnt_total,
                    ['строка', 'строки', 'строк']
                );
            }
            if ($this->show_selector) {
                //------------------- селектор -------------------
                $this->pages .= '<form method="get" class="pager_form">';
                if (isset($_GET) && !empty($_GET)) {
                    foreach ($_GET as $k => $v) {
                        $this->pages .= "<input type=\"hidden\" name=\"$k\" value=\"$v\" />";
                    }
                }
                $this->pages .= 'отображать по <select name="pager_perpage" class="pager_perpage">';
                foreach ($this->var_perpage as $option) {
                    $select = '';
                    if ($option == $this->items_per_page) {
                        $select = 'selected="true"';
                    }
                    if ($option <= $cnt_items) {
                        $this->pages .= "<option value=\"$option\" $select>$option</option>";
                    }
                }
                $this->pages .= '</select> на странице</form>';
            }
            $this->pages .= '</div>';
        } else {
            $this->out = $all;
        }
    }
}
