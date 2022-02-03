<?php
namespace app\api\controller;
use Db\Db;
use Ehua\Caiji\Selenum;
use Facebook\WebDriver\WebDriverBy;
require_once "vendor/autoload.php";
require_once "config.php";
require_once "Db.php";

class Temp
{
    function init()
    {
        $jincheng = Db::name('system')->where(['key' => 'jincheng'])->field('value')->find()['value'] + 1;
        Db::name('system')->where(['key' => 'jincheng'])->update(['value' => $jincheng]);
        $config = get_config();
        $driver = Selenum::init(false, __DIR__ . '\lib\Chrome-bin\chrome.exe');
        $driver->get($config['base_url']);
        Selenum::js($driver, file_get_contents('http://libs.baidu.com/jquery/2.1.4/jquery.min.js'));
        for ($i = 0; $i < 1000; $i++) {
            $jincheng2 = Db::name('system')->where(['key' => 'jincheng'])->field('value')->find();
            if ($jincheng != $jincheng2['value']) { $driver->close(); die(1); }//结束进程
            $cc = $driver->findElement(WebDriverBy::className('webcast-chatroom___items'))->findElements(WebDriverBy::className('webcast-chatroom___item'));
            $count = count($cc);
            if ($count > 2) {
                try {
                    for ($i = 0; $i < $count; $i++) {
                        try { $r = $cc[$i]->getAttribute('outerHTML');   } catch (\Exception $exception) {   continue;  }//送礼物异常跳出
                        $temp = $cc[$i]->getText();
                        $temp = explode('：', $temp);
                        $data['name'] = array_reverse(explode("
                    ", $temp[0]))[0];
                        $data['msg'] = $temp[1];
                        preg_match("/user_grade_level_v5_\d+.png/", $r, $lv);
                        $data['lv'] = str_replace('user_grade_level_v5_', '', str_replace('.png', '', $lv[0]));
                        preg_match("/data-id=\"\d+\"/", $r, $uid);
                        $data['uid']  = str_replace('"', '', str_replace('data-id="', '', $uid[0]));
                        if(empty($data['lv'])){
                            $data['lv']=0;
                        }
                        if (!empty($data['msg'])) {  Db::name('log')->insert($data);  }//xxx来了 不记录  跳出
                    }
                    $JS = <<<EOF
$('.webcast-chatroom___items .webcast-chatroom___enter-done').remove()
EOF;
                    Selenum::js($driver, $JS);
                } catch (\Exception $exception) {}
            }
            usleep(5000);
        }
    }
}
$A = new Temp();
$A->init();