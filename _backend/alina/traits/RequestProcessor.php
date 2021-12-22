<?php

namespace alina\traits;

use alina\mvc\model\_BaseAlinaModel;

trait RequestProcessor
{
    /**
     * @param $model _BaseAlinaModel
     * @param $conditions
     * @param $sort
     * @param $pageSize
     * @param $pageCurrentNumber
     * @param $paginationVersa
     * @return array
     */
    protected function processGetModelList($model, $conditions = [], $sort = [], $pageSize = NULL, $pageCurrentNumber = NULL, $paginationVersa = FALSE)
    {
        $q          = $model->getAllWithReferencesPart1($conditions);
        $collection = $model->getAllWithReferencesPart2($sort, $pageSize, $pageCurrentNumber, $paginationVersa);
        $pagination = (object)[
            'pageCurrentNumber' => $model->pageCurrentNumber,
            'pageSize'          => $model->pageSize,
            'pagesTotal'        => $model->pagesTotal,
            'rowsTotal'         => $model->state_ROWS_TOTAL,
            'paginationVersa'   => $paginationVersa,
        ];

        return ['collection' => $collection, 'pagination' => $pagination];
    }

}
