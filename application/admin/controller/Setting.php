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
 * Script Name: Setting.php
 * Create: 2020/5/24 上午10:25
 * Description: 站点配置
 * Author: fudaoji<fdj@kuryun.cn>
 */

namespace app\admin\controller;

class Setting extends Base
{
    private $settingM;
    private $tabList;
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->settingM = model('setting');
        $this->tabList = [
            'site' => [
                'title' => '站点信息',
                'href' => url('index', ['name' => 'site'])
            ],
            'upload' => [
                'title' => '附件设置',
                'href' => url('index', ['name' => 'upload'])
            ],
            'pay' => [
                'title' => '支付设置',
                'href' => url('index', ['name' => 'pay'])
            ],
            'common' => [
                'title' => '其他设置',
                'href' => url('index', ['name' => 'common'])
            ]
        ];
    }

    /**
     * 设置
     * @return mixed
     * @author: fudaoji<fdj@kuryun.cn>
     */
    public function index(){
        $current_name = input('name', 'site');
        $setting = $this->settingM->getOneByMap(['name' => $current_name]);
        if(request()->isPost()){
            $post_data = input('post.');
            unset($post_data['__token__']);
            if(empty($setting)){
                $res = $this->settingM->addOne([
                    'name' => $current_name,
                    'title' => $this->tabList[$current_name]['title'],
                    'value' => json_encode($post_data)
                ]);
            }else{
                $res = $this->settingM->updateOne([
                    'id' => $setting['id'],
                    'value' => json_encode($post_data)
                ]);
            }
            if($res){
                $this->success('保存成功');
            }else{
                $this->error('保存失败，请刷新重试', '', ['token' => request()->token()]);
            }
        }

        if(empty($setting)){
            $data = [];
        }else{
            $data = json_decode($setting['value'], true);
        }
        $builder = new FormBuilder();
        switch ($current_name){
            case 'common':
                $builder->addFormItem('map_title', 'legend', '地图', '地图')
                    ->addFormItem('map_qq_key', 'text', '腾讯地图key', '获取方法详见：https://lbs.qq.com/', [], 'required maxlength=150');
                break;
            case 'pay':
                $builder->addFormItem('wx_title', 'legend', '微信支付', '微信支付')
                    ->addFormItem('wx_appid', 'text', 'AppId', 'AppId', [], 'required maxlength=150')
                    ->addFormItem('wx_secret', 'text', 'Secret', 'Secret', [], 'required maxlength=150')
                    ->addFormItem('wx_merchant_id', 'text', '商户ID', '商户ID', [], 'required maxlength=100')
                    ->addFormItem('wx_key', 'text', '支付秘钥', '支付秘钥', [], 'required maxlength=32 minlength=32')
                    ->addFormItem('wx_cert_path', 'textarea', '支付证书cert', '请在微信商户后台下载支付证书，用记事本打开apiclient_cert.pem，并复制里面的内容粘贴到这里。', [], 'maxlength=20000')
                    ->addFormItem('wx_key_path', 'textarea', '支付证书key', '请在微信商户后台下载支付证书，使用记事本打开apiclient_key.pem，并复制里面的内容粘贴到这里。', [], ' maxlength=20000')
                    ->addFormItem('wx_rsa_path', 'textarea', 'RSA公钥', '企业付款到银行卡需要RSA公钥匙');
                break;
            case 'site':
                empty($data) && $data['close'] = 0;
                $builder->addFormItem('close', 'radio', '关闭站点', '关闭站点', [1 => '是', 0 => '否'], 'required')
                    ->addFormItem('close_reason', 'textarea', '关闭原因', '不超过100个字', [], 'maxlength=100')
                    ->addFormItem('icp', 'text', '备案号', '备案号')
                    ->addFormItem('logo', 'picture_url', 'LOGO', '250x36')
                    ->addFormItem('kefu', 'picture_url', '客服信息', '请将客服信息拼装成一张图片')
                    ->addFormItem('login_title', 'legend', '注册站点', '注册站点')
                    ->addFormItem('default_group_id', 'number', '游客组id', '自主注册进来的用户组id')
                    ->addFormItem('seo_title', 'legend', '推广', '推广')
                    ->addFormItem('keywords', 'text', 'SEO关键词', 'head头部的keywords')
                    ->addFormItem('description', 'textarea', 'SEO描述', 'head头部的description')
                    ->addFormItem('tongji', 'textarea', '统计代码', '从第三方复制的统计代码');
                break;
            case 'upload':
                empty($data) && $data = [
                    'driver' => 'local',
                    'file_size' => 53000000,
                    'image_size' => 5000000,
                    'image_ext' => 'jpg,gif,png,jpeg',
                    'file_ext' => 'jpg,gif,png,jpeg,zip,rar,tar,gz,7z,doc,docx,txt,xml,mp3,mp4,xls,xlsx,pdf',
                ];
                $builder->addFormItem('driver_title', 'legend', '上传驱动', '上传驱动')
                    ->addFormItem('driver', 'select', '上传驱动', '上传驱动', model('upload')->locations())
                    ->addFormItem('qiniu_ak', 'text', '七牛accessKey', '七牛accessKey')
                    ->addFormItem('qiniu_sk', 'text', '七牛secretKey', '七牛secretKey')
                    ->addFormItem('qiniu_bucket', 'text', '七牛bucket', '七牛bucket')
                    ->addFormItem('qiniu_domain', 'url', '七牛domain', '七牛domain')
                    ->addFormItem('qiniu_region_url', 'url', '七牛源站上传域名', '七牛源站上传域名')
                    ->addFormItem('image_title', 'legend', '图片设置', '图片设置')
                    ->addFormItem('image_size', 'number', '图片大小限制', '单位B', [], 'required min=1 max=1000000000')
                    ->addFormItem('image_ext', 'text', '图片格式支持', '多个用逗号隔开', [], 'required')
                    ->addFormItem('file_title', 'legend', '文件设置', '文件设置')
                    ->addFormItem('file_size', 'number', '文件大小限制', '单位B', [], 'required min=1 max=1000000000')
                    ->addFormItem('file_ext', 'text', '文件格式支持', '多个用逗号隔开', [], 'required')
                    ->addFormItem('voice_title', 'legend', '音频设置', '音频设置')
                    ->addFormItem('voice_size', 'number', '音频大小限制', '单位B', [], 'required min=1 max=1000000000')
                    ->addFormItem('voice_ext', 'text', '音频格式支持', '多个用逗号隔开', [], 'required')
                    ->addFormItem('video_title', 'legend', '视频设置', '视频设置')
                    ->addFormItem('video_size', 'number', '视频大小限制', '单位B', [], 'required min=1 max=1000000000')
                    ->addFormItem('video_ext', 'text', '视频格式支持', '多个用逗号隔开', [], 'required')
                    ;
                break;
        }
        $builder->setFormData($data);
        return $builder->show(['tab_nav' => ['tab_list' => $this->tabList, 'current_tab' => $current_name]]);
    }
}