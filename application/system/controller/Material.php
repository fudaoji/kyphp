<?php
// +----------------------------------------------------------------------
// | [KyPHP System] Copyright (c) 2020 http://www.kuryun.com/
// +----------------------------------------------------------------------
// | [KyPHP] 并不是自由软件,你可免费使用,未经许可不能去掉KyPHP相关版权
// +----------------------------------------------------------------------
// | Author: fudaoji <fdj@kuryun.cn>
// +----------------------------------------------------------------------

/**
 * Created by PhpStorm.
 * Script Name: Material.php
 * Create: 2020/5/25 下午9:28
 * Description: 素材管理
 * @link https://developers.weixin.qq.com/doc/offiaccount/Asset_Management/Adding_Permanent_Assets.html  (微信素材文档说明)
 * Author: fudaoji<fdj@kuryun.cn>
 */

namespace app\system\controller;

use app\common\model\AdminStore;

class Material extends Base
{
    /**
     * @var \app\common\model\Addons
     */
    private $addonsM;
    /**
     * @var \app\common\model\AdminAddon
     */
    private $adminAddonM;


    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->addonsM = model('addons');
        $this->adminAddonM = model('adminAddon');
        set_time_limit(0);
    }

    /**
     * 应用插件
     * @return mixed
     * @throws \think\exception\DbException
     * @author: fudaoji<fdj@kuryun.cn>
     */
    public function addon(){
        $field = input('field', ''); //目标input框
        $where = [
            'aa.uid' => $this->adminId,
            'a.status' => 1,
            'aa.deadline' => ['gt', time()],
            'a.type' => AdminStore::MINI
        ];
        $data_list = $this->adminAddonM->pageJoin([
            'alias' => 'aa',
            'join' => [['addons a', 'a.addon=aa.addon']],
            'page_size' => 7,
            'where' => $where,
            'field' => ['a.id','a.name', 'a.desc', 'a.logo', 'a.addon'],
            'order' => ['aa.id' => 'desc'],
            'refresh' => 1
        ]);
        $pager = $data_list->render();
        $assign = ['data_list' => $data_list, 'pager' => $pager, 'field' => $field];
        return $this->show($assign, __FUNCTION__);
    }
}