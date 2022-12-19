<?php

namespace YQ;

class YqRange
{
    /**
     * 地球的赤道半径ra=6378137m≈6378km
     * 极半径rb=6356752m≈6357km
     * 扁率e=1/298.257
     * 忽略地球非球形对称，平均半径r=6371km
     */
    const EARTH_RADIUS = 6371;
    const EQUATOR_RADIUS = 6378137;

    /**
     * 计算某个经纬度的周围某段距离的正方形的四个点
     * https://fukun.org/archives/06152067.html
     *
     * 在lat和lng上建立一个联合索引后，然后进行sql查询附近的内容
     * SELECT * FROM `xxx` WHERE lat<>0 AND lat>{$squares['right-bottom']['lat']} AND lat<{$squares['left-top']['lat']}
     * AND lng>{$squares['left-top']['lng']} AND lng<{$squares['right-bottom']['lng']}
     *
     * @param  float  $lng      经度
     * @param  float  $lat      纬度
     * @param  float  $distance 该点所在圆的半径，该圆与此正方形内切，默认值为0.5千米
     * @return array            正方形的四个点的经纬度坐标
     */
    public static function returnSquarePoint(float $lng, float $lat, float $distance = 0.5)
    {
        $dlng =  2 * asin(sin($distance / (2 * YqRange::EARTH_RADIUS)) / cos(deg2rad($lat)));
        $dlng = rad2deg($dlng);

        $dlat = $distance / YqRange::EARTH_RADIUS;
        $dlat = rad2deg($dlat);

        return [
            'left-top'     => ['lat'=>$lat + $dlat, 'lng'=>$lng - $dlng],
            'right-top'    => ['lat'=>$lat + $dlat, 'lng'=>$lng + $dlng],
            'left-bottom'  => ['lat'=>$lat - $dlat, 'lng'=>$lng - $dlng],
            'right-bottom' => ['lat'=>$lat - $dlat, 'lng'=>$lng + $dlng]
        ];
    }

    /**
     * 根据经纬度计算距离 其中A($lat1,$lng1)、B($lat2,$lng2)
     * @param  float  $lat1 A维度
     * @param  float  $lng1 A经度
     * @param  float  $lat2 B维度
     * @param  float  $lng2 B经度
     * @return float        米
     */
    public static function getDistance(float $lat1, float $lng1, float $lat2, float $lng2)
    {
        //将角度转为狐度
        $radLat1 = deg2rad($lat1);
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);

        //结果
        $s = acos(cos($radLat1)*cos($radLat2)*cos($radLng1-$radLng2)+sin($radLat1)*sin($radLat2))*YqRange::EQUATOR_RADIUS;

        //精度
        $s = round($s* 10000)/10000;

        return round($s);
    }
}
