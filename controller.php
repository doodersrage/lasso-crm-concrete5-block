<?php

namespace Concrete\Package\LassoCrm;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Package\Package;

class Controller extends Package
{
    protected $pkgHandle = 'lasso_crm';
    protected $appVersionRequired = '9.0.0';
    protected $pkgVersion = '2.0.0';

    public function getPackageName()
    {
        return t('Lasso CRM');
    }

    public function getPackageDescription()
    {
        return t('Adds a registrant lead capture form block integrated with Lasso CRM.');
    }

    public function install()
    {
        $pkg = parent::install();
        $this->installBlockType($pkg);

        return $pkg;
    }

    public function upgrade()
    {
        parent::upgrade();
        $pkg = Package::getByHandle($this->pkgHandle);
        if ($pkg) {
            $this->installBlockType($pkg);
        }
    }

    public function uninstall()
    {
        $bt = BlockType::getByHandle('lasso_forms');
        if (is_object($bt)) {
            $bt->delete();
        }

        parent::uninstall();
    }

    private function installBlockType(Package $pkg): void
    {
        $bt = BlockType::getByHandle('lasso_forms');
        if (!is_object($bt)) {
            BlockType::installBlockType('lasso_forms', $pkg);
        }
    }
}
