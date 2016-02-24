<?php

/**
 * Created by PhpStorm.
 * User: Lich
 * Date: 16/2/23
 * Time: 14:39
 */
class ProxyList {
    private $redis;
    private $username = 'root';
    private $password = '';
    public function __construct() {
        $this->redis = new Redis(); //todo redis挂掉进行处理
        $this->redis->connect('127.0.0.1');
//        $this->redis->auth();  //redis认证, attention:配置redis时绑定127.0.0.1并设置密码
    }

    /**
     * 获取MySQL连接
     * @param $type String OL 离线, RO 只读, RW 可读写
     * @return null|PDO
     */
    public function getConnection($type = 'RO') {
        $dsn = $this->getHost($type);
        if(!$dsn) {
            return null;
        }
        $handle = new PDO($dsn, $this->username, $this->password); //slave数据库的账号密码, 一般所有的都为同样的
        if(!$handle) { //某个服务器down掉从缓存和数据库中移除/更改状态, 并重新获取
            $this->downHost($dsn);
            return $this->getConnection($type);
        }
        return $handle;
    }

    /**
     * 可根据权重计算分配, 此处为简单随机
     * @param $type
     * @return null|string
     */
    private function getHost($type) {
        $hosts = json_decode($this->redis->get($type), true);
        if($host = $hosts[rand(0, count($hosts)-1)]){
            return 'mysql:host='.$host['host'].';port='.$host['port'].';dbname=mysqlbalancelist;charset=UTF8;';
        } else {
            return null;
        }
    }

    /**
     * 改变down掉的服务器的状态
     * @param $dsn
     */
    private function downHost($dsn) {
        $host = explode('=', $dsn);
        $ip = $host[0];
        $port = $host[1];
        $server = new PDO('mysql:host=127.0.0.1;port=3306;dbname=mytest;charset=UTF8;', 'root', '', array(PDO::ATTR_PERSISTENT=>true));
        $server->prepare("UPDATE list set type = 'OL' WHERE host = '$ip' AND port = '$port'");
        $server->exec();
        $this->redis->delete('OL', 'RO', 'RW');
        $OL = '';
        $RO = '';
        $RW = '';
        $this->redis->set();
        //todo 从redis里改变状态
    }
}