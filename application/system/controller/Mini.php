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
 * Script Name: Mp.php
 * Create: 2020/7/22 下午11:15
 * Description: 小程序管理
 * Author: fudaoji<fdj@kuryun.cn>
 */

namespace app\system\controller;

use app\admin\controller\FormBuilder;
use app\common\model\AdminStore;
use think\Db;
use think\facade\Log;

class Mini extends Base
{
    /**
     * @var \app\common\model\Mini $miniM
     */
    private $miniM;
    /**
     * @var \think\Model
     */
    private $storeM;
    private $miniInfo;
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->miniM = model('mini');
        $this->storeM = model('adminStore');
        if($id = input('store_id', 0, 'intval')){
            $this->miniInfo = $this->miniM->getOne($id);
            if(empty($this->miniInfo)){
                $this->error('小程序不存在');
            }
        }
    }

    /**
     * 获取授权链接
     * @return mixed
     * Author: fudaoji<fdj@kuryun.cn>
     */
    protected function getAuthUrl(){
        $redirect_url = request()->domain() . url('mini/auth/authCallback');
        return controller('mini/mini', 'event')->getOpenPlatform()->getPreAuthorizationUrl($redirect_url);
    }

    /**
     * 编辑手动接入的小程序
     * @return mixed
     * @throws \think\Exception
     * @author: fudaoji<fdj@kuryun.cn>
     */
    public function edit(){
        if(request()->isPost()){
            $post_data = input('post.');

            $res = $this->validate($post_data,'Mini.edit');
            if($res !== true){
                $this->error($res, '', ['token' => request()->token()]);
            }
            if($this->miniM->updateOne($post_data)){
                $this->success('编辑成功', url('info', ['store_id' => $this->miniInfo['id']]));
            }else{
                $this->error('编辑失败，请刷新重试', '', ['token' => request()->token()]);
            }
        }
        $builder = new FormBuilder();
        $builder->addFormItem('id', 'hidden', 'id', 'id')
            ->addFormItem('nick_name', 'text', '小程序名称', '不超过30字', [], 'required maxlength=30')
            ->addFormItem('signature', 'textarea', '小程序描述', '版本描述', [], 'maxlength=200')
            ->addFormItem('verify_type_info', 'select', '认证', '认证', $this->miniM->verifyTypes(), 'required')
            ->addFormItem('appid', 'text', 'APPID', 'appID', [], 'required')
            ->addFormItem('appsecret', 'text', 'APPSecret', 'APPSecret', [], 'required')
            ->addFormItem('user_name', 'text', '原始ID', '原始ID', [], 'required')
            ->addFormItem('head_img', 'picture_url', '头像', '头像')
            ->addFormItem('qrcode_url', 'picture_url', '二维码', '二维码')
            ->addFormItem('verify_file', 'file_to_root', '校验文件', '校验文件')
            ->setFormData($this->miniInfo);
        return $builder->show();
    }

    /**
     * 管理设置
     * @return mixed
     * Author: fudaoji<fdj@kuryun.cn>
     */
    public function info(){
        $assign = [
            'auth_url' => $this->getAuthUrl(),
            'data_info' => $this->miniInfo,
            'host' => request()->host(),
            'domain' => request()->domain()
        ];
        return $this->show($assign);
    }

    /**
     * 小程序列表
     * @return mixed
     * Author: fudaoji<fdj@kuryun.cn>
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $data_list = $this->miniM->page($this->pageSize, ['uid' => $this->adminId], ['update_time' => 'desc'],true, 1);
        $page = $data_list->render();
        $assign = ['data_list' => $data_list, 'page' => $page, 'verify_type' => $this->miniM->verifyTypes()];
        return $this->show($assign);
    }

    /**
     * 选择添加方式
     * @return mixed
     * @author: fudaoji<fdj@kuryun.cn>
     */
    public function choose(){
        $this->checkCanAdd();
        $url = $this->getAuthUrl();
        $assign = [
            'url' => $url
        ];
        return $this->show($assign);
    }

    /**
     * 判断是否可以继续添加小程序
     * @author: fudaoji<fdj@kuryun.cn>
     */
    protected function checkCanAdd(){
        $res = controller('common/adminGroup', 'event')->canAddStore([
            'admin_info' => $this->adminInfo,
            'type' => 'mini'
        ]);
        if($res === false){
            $this->error('您当前添加的小程序数量已达上限，请升级会员');
        }
    }

    /**
     * 手动添加小程序
     * @return mixed
     * @throws \think\Exception
     * @author: fudaoji<fdj@kuryun.cn>
     */
    public function add(){
        if(request()->isPost()){
            $post_data = input('post.');
            $err_msg = '添加失败，请刷新重试';
            Db::startTrans();
            try{
                $res = $this->validate($post_data,'Mini.add');
                if($res === true){
                    $store = $this->storeM->addOne(['uid' => $this->adminId, 'type' => AdminStore::MINI]);
                    $post_data['uid'] = $this->adminId;
                    $post_data['id'] = $store['id'];
                    $res = $this->miniM->addOne($post_data);
                }else{
                    $err_msg = $res;
                }
                Db::commit();
            }catch (\Exception $e){
                Log::write(json_encode($e->getMessage()));
                $res = false;
                Db::rollback();
            }
            if($res){
                $this->success('添加成功', url('index'));
            }else{
                $this->error($err_msg, '', ['token' => request()->token()]);
            }
        }
        $builder = new FormBuilder();
        $builder->addFormItem('nick_name', 'text', '小程序名称', '不超过30字', [], 'required maxlength=30')
            ->addFormItem('signature', 'textarea', '小程序描述', '版本描述', [], 'maxlength=200')
            ->addFormItem('verify_type_info', 'select', '认证', '认证', $this->miniM->verifyTypes(), 'required')
            ->addFormItem('appid', 'text', 'APPID', 'appID', [], 'required')
            ->addFormItem('appsecret', 'text', 'APPSecret', 'APPSecret', [], 'required')
            ->addFormItem('user_name', 'text', '原始ID', '原始ID', [], 'required')
            ->addFormItem('head_img', 'picture_url', '头像', '头像')
            ->addFormItem('qrcode_url', 'picture_url', '二维码', '二维码')
            ->addFormItem('verify_file', 'file_to_root', '校验文件', '校验文件');
        return $builder->show();
    }

    /**
     * 删除
     * Author: fudaoji<fdj@kuryun.cn>
     */
    public function delete(){
        if (request()->isPost()) {
            $id = input('id');
            $this->miniM->delOne($id);
            $this->success('删除成功');
        }
    }

    /**
     * 设置状态
     * Author: fudaoji<fdj@kuryun.cn>
     */
    public function setStatus(){
        if (request()->isPost()) {
            Db::startTrans();
            try{
                $this->miniM->updateOne([
                    'id' => $this->miniInfo['id'],
                    'status' => abs($this->miniInfo['status'] - 1)
                ]);
                $this->storeM->updateOne([
                    'id' => $this->miniInfo['id'],
                    'status' => abs($this->miniInfo['status'] - 1)
                ]);
                Db::commit();
                $res = true;
            }catch (\Exception $e){
                Log::write(json_encode($e->getMessage()));
                Db::rollback();
                $res = false;
            }
            if($res){
                $this->success('操作成功');
            }else{
                $this->error('操作失败，请刷新重试');
            }
        }
    }
}