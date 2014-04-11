<?php
/**
 * User: fri13th
 * Date: 2014/04/11 12:55
 */


class FlatPaginate extends BasePaginate {

    function setTotalCount($totalCount) {
        $this->totalCount = $totalCount;

        if ($this->currentPage < 1 || $this->totalCount < 1 || $this->totalCount < (($this->currentPage - 1)*$this->paginationSize)) { // we don't need to search
            $this->searchable = false;
            return;
        }
        $this->searchable = true;
        $this->totalPage = (int)($this->totalCount/$this->paginationSize) + (($this->totalCount % $this->paginationSize == 0) ? 0 : 1);

        if ($this->endAt > $this->totalCount)
            $this->endAt = $this->totalCount;

        $this->prev = $this->currentPage - 1;
        $this->next = $this->currentPage + 1;

        $this->startPage = (($this->currentPage - $this->listSize) < 1) ? 1 : ($this->currentPage - $this->listSize);
        if (($this->totalPage > ($this->listSize*2 + 3)) && ($this->startPage > $this->totalPage - $this->listSize*2 - 1)) {
            $this->startPage = $this->totalPage - $this->listSize*2 - 1;
        }
        $this->endPage = (($this->currentPage + $this->listSize) > $this->totalPage) ? $this->totalPage : ($this->currentPage + $this->listSize);
        if ($this->totalPage > ($this->listSize*2 + 3) && $this->endPage < ($this->listSize*2 + 1)) {
            $this->endPage = $this->listSize*2 + 1;
        }

        $this->useFirstPage = ($this->startPage > 1);
        $this->useFirstSkip = ($this->startPage > 2);
        $this->useLastPage = (($this->totalPage - $this->endPage) > 0 && $this->totalPage > $this->startPage);
        $this->useLastSkip = (($this->totalPage - $this->endPage) > 1 && $this->totalPage > $this->startPage + 1);
    }

    function html() {

        $empty = "<span class=\"empty\">&nbsp;</span>";

        if (!$this->searchable)
            return $empty;

        if ($this->startPage == $this->endPage) {
            return $empty;
        }

        $html = "<ul>";

        if ($this->prev > 0) {
            $html .= " <li class='previous'><a href=\"?p={$this->prev}\"  class=\"fui-arrow-left\"></a></li> ";
        }

        if ($this->useFirstPage) {
            $html .= "<li><a href=\"?p=1\">1</a></li>";
        }
        if ($this->useFirstSkip) {
            if ($this->startPage == 3) {
                $html .= "<li><a href=\"?p=2\">2</a></li>";
            }
            else {
                $html .= "<li class=\"disabled\"><a href=\"#\">...</a></li>";
            }
        }
        for ($i = $this->startPage; $i <= $this->endPage; $i++) {
            if ($i == $this->currentPage){
                $html .= " <li class='active'><a href=\"?p={$i}\">{$i}</a></li> ";
            }
            else {
                $html .= " <li><a href=\"?p={$i}\">{$i}</a></li> ";
            }
        }
        if ($this->useLastSkip) {
            if ($this->endPage == $this->totalPage - 2) {
                $html .= "<li><a href=\"?p=" . ($this->endPage + 1) . "\">" . ($this->endPage + 1) . "</a></li> ";
            }
            else {
                $html .= "<li class=\"disabled\"><a href=\"#\">...</a></li>";
            }
        }
        if ($this->useLastPage) {
            $html .= "<li><a href=\"?p=" . $this->totalPage . "\">" . $this->totalPage . "</a></li>";
        }
        if ($this->next <= $this->totalPage) {
            $html .= " <li class='next'><a href=\"?p=" . $this->next . "\" class='fui-arrow-right'></a></li> ";
        }

        $html .= "</ul>";
        return $html;
    }

}
