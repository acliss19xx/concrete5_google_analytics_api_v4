<?php
namespace Concrete\Package\PageRankingListGaV4;
use Package;
use Core;
use BlockType;
use BlockTypeSet;
use Config;

//use \Concrete\Core\Page\Single as SinglePage;

defined('C5_EXECUTE') or die(_("Access Denied."));

if (!function_exists('compat_is_version_8')) {
    function compat_is_version_8() {
        return interface_exists('\Concrete\Core\Export\ExportableInterface');
    }
}

class Controller extends Package {
    protected $pkgHandle = 'page_ranking_list_ga_v4';
    protected $appVersionRequired = '5.1.0';
    protected $pkgVersion = '0.0.9';
    protected static $blockTypes = array(
        array(
            'handle' => 'page_ranking_list_v4', 'set' => 'navigation',
        )
    );
    
    
    public function on_start(){
//        require $this->getPackagePath() . '/vendor/Google/vendor/autoload.php';
        require $this->getPackagePath() . '/vendor/autoload.php';
    }
    
    public function getPackageDescription() {
        return t("use google analytics access ranking page list v4.");
    }
    public function getPackageName() {
        return t("page ranking list by google analytics v4");
    }
    public function install() {
        $pkg = parent::install();
        foreach (self::$blockTypes as $blockType) {
            $existingBlockType = BlockType::getByHandle($blockType['handle']);
            if (!$existingBlockType) {
                BlockType::installBlockTypeFromPackage($blockType['handle'], $pkg);
            }
            if (isset($blockType['set']) && $blockType['set']) {
                $navigationBlockTypeSet = BlockTypeSet::getByHandle($blockType['set']);
                if ($navigationBlockTypeSet) {
                    $navigationBlockTypeSet->addBlockType(BlockType::getByHandle($blockType['handle']));
                }
            }
        }

        // file extension
        $file_access_file_types = Core::make('helper/concrete/file')->unserializeUploadFileExtensions(
            Config::get('concrete.upload.extensions'));
        if(array_search('json',$file_access_file_types) === false){
            $file_access_file_types[] = 'json';
            $types = Core::make('helper/concrete/file')->serializeUploadFileExtensions($file_access_file_types);
            Config::save('concrete.upload.extensions', $types);
        }

        // site attribute
        if(compat_is_version_8()){
            $service = $this->app->make('Concrete\Core\Attribute\Category\CategoryService');
            $categoryEntity = $service->getByHandle('site');
            $category = $categoryEntity->getController();
    
            $siteKey = $category->getByHandle('google_api_service_json');
            if(!is_object($siteKey)){
                $key = new \Concrete\Core\Entity\Attribute\Key\SiteKey();
                $key->setAttributeKeyHandle('google_api_service_json');
                $key->setAttributeKeyName('google api service json');
                $key = $category->add('image_file', $key, null, $pkg);
            }
    
            $siteKey = $category->getByHandle('google_api_view_id');
            if(!is_object($siteKey)){
                $key = new \Concrete\Core\Entity\Attribute\Key\SiteKey();
                $key->setAttributeKeyHandle('google_api_view_id');
                $key->setAttributeKeyName('google api viewID');
                $key = $category->add('text', $key, null, $pkg);
            }
        }
    }
}
?>