<?php


namespace ApolloClient;


class ApolloFacade extends \Illuminate\Support\Facades\Facade
{
    /**
     * 获取Facade注册名称
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return ApolloClient::class;
    }
}