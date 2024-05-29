<?php


namespace Modules\Orders\Services;


trait WithHaving
{
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new BuilderWithPaginationHavingSupport(
            $connection,
            $connection->getQueryGrammar(),
            $connection->getPostProcessor()
        );
    }
}