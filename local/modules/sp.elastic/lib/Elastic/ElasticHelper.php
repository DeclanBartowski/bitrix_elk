<?php

namespace SP\Elastic\Elastic;

use Elasticsearch\ClientBuilder,
    Bitrix\Main\Config\Option;

class ElasticHelper
{
    private static function getModuleOptions()
    {
        $arOptions = Option::getForModule('sp.elastic');
        if (!$arOptions['host'] || !$arOptions['username'] || !$arOptions['password']) {
            throw new \Exception('Не заполнены настройки модуля');
        }
        return $arOptions;
    }

    public static function getElasticClient()
    {
        require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php');
        $arOptions = self::getModuleOptions();
        return ClientBuilder::create()
            ->setHosts([$arOptions['host']])
            ->setBasicAuthentication($arOptions['username'], $arOptions['password'])
            ->build();
    }

    public static function getIBlock($iBlockId)
    {
        return \CIBlock::GetList([], ['ID' => $iBlockId])->Fetch();
    }

    public static function indexMapping($arParams, $index)
    {
        $client = self::getElasticClient();
        $params = [
            'index' => $index,
            'body' => [
                'properties' => $arParams,
            ],
        ];
        $isIndexExists = $client->indices()->exists(['index' => $index]);
        if ($isIndexExists) {
            $client->indices()->delete(['index' => $index]);
        }

        $client->indices()->create(['index' => $index]);
        $client->indices()->putMapping($params);
    }
}