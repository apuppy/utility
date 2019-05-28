<?php

require_once '../vendor/autoload.php';
use GeoIp2\Database\Reader;

/****** 准备 ******/
// 数据下载地址: https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz
// 安装操作geoip的php扩展: composer require geoip2/geoip2:~2.0

/****** 可选 ******/
// 拾取坐标: http://api.map.baidu.com/lbsapi/getpoint/index.html
// 经纬度查询: http://www.gpsspg.com/maps.htm


/****** 使用 ******/
// 获取ip地址
$real_ip = '180.160.0.0';
// $real_ip = Location::get_client_ip(); // http服务下用这个

$cities = Location::$city_nodes; // 城市列表，数据可更改

//返回最近节点的城市名
$result = Location::nearest_city_by_client($real_ip,$cities);
var_dump($result);


/**
 * IP转经纬度坐标、计算坐标间的距离
 * Class Location
 */
class Location {

    /**
     * geoip 数据存放位置
     */
    const GEOIP_DATA = 'E:\dev_env\xampp\php\ext\ext_data\GeoLite2-City_20190521\GeoLite2-City.mmdb'; //TODO 根据环境配置

    /**
     * 城市节点列表
     * @var array
     */
    public static $city_nodes = [
        'Nanjing' => [
            'lng' => '118.792074',
            'lat' => '32.025721'
        ],
        'Changzhou' => [
            'lng' => '119.97631',
            'lat' => '31.817514'
        ],
    ];


    /**
     * @param $real_ip
     * @param $cities
     * @return mixed
     */
    public static function nearest_city_by_client($real_ip, $cities)
    {
        $location = self::get_current_location($real_ip);
        $point_lng = $location['lng'];
        $point_lat = $location['lat'];
        $ret = self::get_nearest_city($point_lng,$point_lat,$cities);
        return $ret;
    }


    /**
     * @param $real_ip
     * @return array
     */
    public static function get_current_location($real_ip)
    {

        $city_name = '';
        $point_lng = null;
        $point_lat = null;
        try{
            $reader = new Reader(self::GEOIP_DATA);
            $record = $reader->city($real_ip);
            $city_name = $record->city->name;

            $point_lng = $record->location->longitude;
            $point_lat = $record->location->latitude;
        } catch (Exception $e){
            // $error_log = $e->getMessage();
            // TODO error log if necessary
        }
        $location = ['city' => $city_name,'lng' => $point_lng,'lat' => $point_lat];
        return $location;
    }

    /**
     * 根据两点间的经纬度计算距离
     * @param $lng1
     * @param $lat1
     * @param $lng2
     * @param $lat2
     * @return int
     */
    public static function get_distance($lng1, $lat1, $lng2, $lat2)
    {
        //将角度转为狐度
        $radLat1 = deg2rad($lat1);//deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
        return $s;
    }


    /**
     * @param $point_lng
     * @param $point_lat
     * @param $city_nodes
     * @return mixed
     */
    public static function get_nearest_city($point_lng,$point_lat,$city_nodes)
    {
        $distance_list = [];
        foreach ($city_nodes as $city_name => $city_node){
            $distance = self::get_distance($point_lng,$point_lat,$city_node['lng'],$city_node['lat']);
            $distance_list[$city_name] = $distance;
        }

        $city_idx = array_search(min($distance_list),$distance_list);

        return $city_idx;
    }


    /**
     * @return mixed|string
     */
    public static function get_client_ip()
    {
        $from_cli = (php_sapi_name() === 'cli');
        if(!$from_cli) {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            } else {
                $ip = '';
            }
        } else {
            $host = gethostname();
            $ip = gethostbyname($host);
        }
        return $ip;
    }

}