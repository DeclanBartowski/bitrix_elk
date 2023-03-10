<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\EventManager;
use Bitrix\Main\Context;

Loc::loadMessages(__FILE__);

class Sp_elastic extends CModule
{
    var $MODULE_ID = 'sp.elastic';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $PARTNER_URI;

    protected $module_path;
    protected $root_dir;

    /**
     * $_SERVER
     * @var
     */
    private $server;

    protected static $events = [
        [
            'main',
            'OnPageStart',
            'sp.elastic',
            '\SP\Elastic\Events\Main',
            'DoRedirect'
        ],
    ];

    public function __construct()
    {
        $arModuleVersion = array();

        include(__DIR__.'/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('SP_ELASTIC_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage("SP_ELASTIC_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("SP_ELASTIC_MODULE_PARTNER_NAME");
        $this->PARTNER_URI = Loc::getMessage("SP_ELASTIC_MODULE_PARTNER_URI");
        $this->module_path = dirname(__DIR__);
        $this->root_dir = (strpos($this->module_path, '/local/')!==false ? 'local' : 'bitrix');
        $this->server = Context::getCurrent()->getServer();
    }

    /**
     * Установка модуля
     */
    public function DoInstall()
    {
        global $APPLICATION;
        if (count(self::$events) > 0) {
            $this->InstallEvents();
        }
        $this->InstallFiles();
        RegisterModule($this->MODULE_ID);
        $APPLICATION->IncludeAdminFile('Установка модуля ' . $this->MODULE_ID, $this->module_path . "/install/step.php");
    }

    /**
     * Удаление модуля
     */
    public function DoUninstall()
     {
         global $APPLICATION;
         if (count(self::$events) > 0) {
             $this->UnInstallEvents();
         }
        /* $this->UnInstallFiles();*/
         UnRegisterModule($this->MODULE_ID);
         $APPLICATION->IncludeAdminFile('Деинсталляция модуля ' . $this->MODULE_ID, $this->module_path . "/install/unstep.php");
     }

    /**
     * Добавляем события
     * @return bool
     */
    public function InstallEvents()
    {
        $event_manager = EventManager::getInstance();

        foreach (self::$events as $event) {
            $event_manager->registerEventHandlerCompatible($event[0], $event[1], $event[2], $event[3], $event[4], $event[5]);
        }

        return true;
    }

    /**
     * Удаляем события
     * @return bool
     */
    public function UnInstallEvents()
    {
        $event_manager = EventManager::getInstance();
        foreach (self::$events as $event) {
            $event_manager->unRegisterEventHandler($event[0], $event[1], $event[2], $event[3], $event[4], $event[5]);
        }
        return true;
    }

    /**
     * Копируем файлы модуля
     * @param array $arParams
     * @return bool
     */
    public function InstallFiles($arParams = [])
    {
        return true;
    }

    /**
     * Удаляем файлы модуля
     * @return bool
     */
    public function UnInstallFiles()
    {
        return true;
    }
}