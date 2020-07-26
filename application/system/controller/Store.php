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
 * Script Name: Store.php
 * Create: 2020/5/28 下午10:57
 * Description: 平台、市场
 * Author: fudaoji<fdj@kuryun.cn>
 */

namespace app\system\controller;

use think\Db;
use think\facade\Log;

class Store extends Base
{
    /**
     * @var \app\common\model\AdminStore
     */
    private $storeM;
    private $storeId;
    private $storeInfo;
    /**
     * @var \app\common\model\AdminAddon
     */
    private $adminAddonM;
    /**
     * @var \app\common\model\Addons
     */
    private $addonM;
    /**
     * @var \app\common\model\AddonsInfo
     */
    private $addonInfoM;
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->storeM = model('adminStore');
        $this->adminAddonM = model('adminAddon');
        $this->addonM = model('addons');
        $this->addonInfoM = model('addonsInfo');
        $this->storeId = (int)session('storeId');
        if($this->storeId){
            $this->storeInfo = $this->storeM->getOne($this->storeId);
        }
    }

    /**
     * 应用采购下单
     * Author: fudaoji<fdj@kuryun.cn>
     */
    public function addOrderPost(){
        if(request()->isPost()){
            $post_data = input('post.');
            $addon = $this->addonM->getOneJoin([
                'alias' => 'a',
                'join' => [
                    ['addons_info ai', 'ai.id=a.id']
                ],
                'where' => ['a.id' => $post_data['id']],
                'field' => 'a.*,ai.*',
                'refresh' => 1
            ]);
            if(empty($post_data['id']) || !$addon){
                $this->error('参数错误');
            }

            $return = ['addon' => $addon];
            $msg = '续费成功';
            $url = url('store/myapps');
            Db::startTrans();
            $res = true;
            try {
                if($addon['price'] <= 0){
                    $res = controller('common/addon', 'event')->afterBuyAddon([
                        'addon' => $addon,
                        'uid' => $this->adminId
                    ]);
                    $msg = '应用开通成功';
                }else{
                    //下单，前台走支付
                    $total = $addon['price'] * 100;
                    $insert_data = [
                        'channel'       => \ky\Payment::WX_NATIVE,
                        'order_no'      => build_order_no('addon'),
                        'addon'         => $addon['addon'],
                        'amount'        => $total,
                        'total'         => $total,
                        'subject'       => '开通'. $addon['name'] .'应用',
                        'body'          => '开通'. $addon['name'] .'应用',
                        'uid'           => $this->adminId,
                        'username'      => $this->adminInfo['username'],
                        'mobile'        => $this->adminInfo['mobile'],
                        'client_ip'     => $this->request->ip()
                    ];

                    model('orderAddon')->addOne($insert_data);
                    $return['order'] = $insert_data;
                    $msg = '下单成功，前往支付';
                    $url = url('payment/orderAddon', ['order_no' => $insert_data['order_no']]);
                }
                Db::commit();
            }catch (\Exception $e){
                $res = false;
                dump($e->getMessage());
                Log::write($e->getMessage());
                Db::rollback();
            }
            if($res){
                $this->success($msg, $url, $return);
            }else{
                $this->error('系统错误，请刷新重试');
            }
        }
    }

    /**
     * 应用详情
     * @return mixed
     * Author: fudaoji<fdj@kuryun.cn>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function appDetail(){
        $id = input('id', 0, 'intval');
        $data = $this->addonM->getOneJoin([
            'alias' => 'a',
            'join' => [
                ['addons_info ai', 'ai.id=a.id']
            ],
            'where' => ['a.id' => $id],
            'field' => 'a.*,ai.*',
            'refresh' => 1
        ]);
        if(! $data){
            $this->error('数据不存在');
        }
        
        $data['snapshot'] = explode(',', $data['snapshot']);
        $this->assign['admin_addon'] = $this->adminAddonM->getOneByMap(['uid' => $this->adminId, 'addon' => $data['addon']]);
        $this->assign['data'] = $data;
        return $this->show();
    }

    /**
     * 切换平台
     * @return mixed
     * @throws \think\exception\DbException
     * @author: fudaoji<fdj@kuryun.cn>
     */
    public function index(){
        $type = input('type', 'all');
        $where = ['uid' => $this->adminId, 'status' => 1];
        $type !== 'all' && $where['type'] = $type;
        $data_list = $this->storeM->page($this->pageSize, $where, ['update_time' => 'desc'], true, true);
        $this->assign['page'] = $data_list->appends(['type' => $type])->render();
        foreach ($data_list as $k => $v){
            $v['data'] = model($v['type'])->getOne($v['id']);
            switch ($v['type']){
                case 'mini':
                    $v['href'] = url($v['type'].'/index/index', ['mini_id' => $v['id']]);
                    break;
                default:
                    $v['href'] = url($v['type'].'/index/index', ['mid' => $v['id']]);
                    break;
            }
            $data_list[$k] = $v;
        }
        $this->assign['types'] = ['all' => '全部'] + $this->storeM->types();
        $this->assign['data_list'] = $data_list;
        $this->assign['type'] = $type;
        return $this->show();
    }

    /**
     * 中转
     * @author: fudaoji<fdj@kuryun.cn>
     */
    public function manage(){
        if(empty($this->storeInfo)){
            $this->redirect(url('index'));
        }else{
            $this->redirect(url($this->storeInfo['type'].'/index/index'));
        }
    }

    /**
     * 应用中心
     * @return mixed
     * Author: fudaoji<fdj@kuryun.cn>
     * @throws \think\exception\DbException
     */
    public function apps(){
        $type = input('type', '');
        $search_key = input('search_key', '');
        $cate = input('cate', 'all');
        $where = ['a.status' => 1];
        $type && $where['a.type'] = $type;
        $search_key && $where['a.name|a.desc'] = ['like', '%'.$search_key.'%'];
        $cate !== 'all' && $where['ai.cates'] = ['like', '%'.$cate.'%'];
        $data_list = $this->addonM->pageJoin([
            'alias' => 'a',
            'join' => [
                ['addons_info ai', 'a.id=ai.id']
            ],
            'order' => ['ai.sale_num_show' => 'desc'],
            'page_size' => $this->pageSize,
            'field' => 'a.id,a.logo,a.name,a.desc,a.type,ai.sale_num_show,ai.price',
            'refresh' => 1,
            'where' => $where
        ]);
        $page = $data_list->appends(['type' => $type, 'search_key' => $search_key])->render();
        $cates = array_values(model('addonsCate')->getField('title'));
        $assign = [
            'data_list' => $data_list,
            'type' => $type,
            'search_key' => $search_key,
            'page' => $page,
            'cates' => ['all' => '全部'] + array_combine($cates, $cates),
            'cate' => $cate
        ];
        return $this->show($assign);
    }

    /**
     * 我的应用
     * @return mixed
     * Author: fudaoji<fdj@kuryun.cn>
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function myApps(){
        if(request()->isPost()){ //开启关闭
            $id = input('post.id');
            $admin_addon = $this->adminAddonM->getOneByMap(['id' => $id, 'uid' => $this->adminId], true, true);
            if(empty($admin_addon)){
                $this->error('数据不存在');
            }
            $this->adminAddonM->updateOne(['id' => $id, 'status' => abs($admin_addon['status'] - 1)]);
            $this->success('操作成功');
        }

        $type = input('type', '');
        $status = input('status', -1);
        $search_key = input('search_key', '');
        $where = [
            'aa.deadline' => ['gt', time()], 'aa.uid' => $this->adminId,
        ];
        $type && $where['ad.type'] = $type;
        $status != -1 && $where['ad.status'] = $status;
        $search_key && $where['ad.name|ad.desc'] = ['like', '%'.$search_key.'%'];
        $data_list = $this->adminAddonM->pageJoin([
            'alias' => 'aa',
            'join' => [
                ['admin a', 'a.id=aa.uid'],
                ['addons ad', 'ad.addon=aa.addon']
            ],
            'order' => ['aa.update_time' => 'desc'],
            'page_size' => $this->pageSize,
            'field' => 'aa.deadline,aa.status,a.username,a.mobile,a.realname,ad.id,ad.name,ad.logo,ad.desc,ad.type',
            'refresh' => 1,
            'where' => $where
        ]);
        $page = $data_list->appends(['status' => $status, 'type' => $type, 'search_key' => $search_key])->render();

        $assign = [
            'data_list' => $data_list,
            'type' => $type,
            'search_key' => $search_key,
            'page' => $page,
            'status' => $status
        ];
        return $this->show($assign);
    }

    /**
     * 过期应用
     * @return mixed
     * Author: fudaoji<fdj@kuryun.cn>
     * @throws \think\exception\DbException
     * @throws \think\Exception
     */
    public function overtime(){
        $type = input('type', '');
        $search_key = input('search_key', '');
        $where = [
            'aa.deadline' => ['lt', time()], 'aa.uid' => $this->adminId,
        ];
        $type && $where['ad.type'] = $type;
        $search_key && $where['ad.name|ad.desc'] = ['like', '%'.$search_key.'%'];
        $data_list = $this->adminAddonM->pageJoin([
            'alias' => 'aa',
            'join' => [
                ['admin a', 'a.id=aa.uid'],
                ['addons ad', 'ad.addon=aa.addon']
            ],
            'order' => ['aa.update_time' => 'desc'],
            'page_size' => $this->pageSize,
            'field' => 'aa.*,a.username,a.mobile,a.realname,ad.name,ad.logo,ad.desc,ad.type',
            'refresh' => 1,
            'where' => $where
        ]);
        $page = $data_list->appends(['type' => $type, 'search_key' => $search_key])->render();

        $assign = [
            'data_list' => $data_list,
            'type' => $type,
            'search_key' => $search_key,
            'page' => $page
        ];
        return $this->show($assign);
    }
}