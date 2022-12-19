<?php

namespace YQ;

class YqExcel
{
    /**
     * 读取xls数据
     * @param  string $path  文件路径
     * @param  string $sheetname 读取哪个sheet
     * @return array
     */
    public static function read(string $path, string $sheetname='')
    {
        //文件不存在直接返回
        if (!file_exists($path)) {
            return [];
        }

        $readertype = strstr($path, ".xlsx")?'Excel2007':'Excel5';
        $reader = \PHPExcel_IOFactory::createReader($readertype);

        // 设置为只读模式
        $reader->setReadDataOnly(true);

        $excel = $reader->load($path);
        $allsheet = $excel->getSheetNames();

        $sheetdata = [];
        foreach ($allsheet as $key => $name) {
            if ($name == $sheetname || $sheetname=='') {
                $sheet = $excel->getSheetByName($name);
                $sheetdata[$key] = $sheet->toArray();
                break;
            }
        }
        return $sheetdata;
    }

    /**
     * 写入数据到xls
     * @param  string $path 文件路径
     * @param  array  $data     写入内容 {sheet=>data}
     * @return boolean
     */
    public static function write(string $path, array $data)
    {
        // 删除存在的文件
        if (file_exists($path)) {
            unlink($path);
        }

        $excel = new \PHPExcel;
        $excel->removeSheetByIndex(0);

        foreach ($data as $sheetname => $value) {
            $sheet = $excel->createSheet();
            $sheet->fromArray($value);
            $sheet->setTitle(strval($sheetname));
        }

        $readertype = strstr($path, ".xlsx")?'Excel2007':'Excel5';
        $writer = \PHPExcel_IOFactory::createWriter($excel, $readertype);

        iconv('UTF-8', 'GB2312', $path);
        $writer->save($path);

        $excel->disconnectWorksheets();
        unset($excel);

        return true;
    }

    /**
     * 导出数据给浏览器
     * @param  string $filename 导出xls文件名称
     * @param  array  $data     数据{sheet=>data}
     */
    public static function export(string $filename, array $data)
    {
        $excel = new \PHPExcel;
        $excel->removeSheetByIndex(0);
        foreach ($data as $sheetname => $value) {
            $sheet = $excel->createSheet();
            $sheet->fromArray($value);
            $sheet->setTitle(strval($sheetname));
        }

        $readertype = strstr($filename, ".xlsx") ? 'Excel2007' : 'Excel5';
        $writer     = \PHPExcel_IOFactory::createWriter($excel, $readertype);
        iconv('UTF-8', 'GB2312', $filename);

        //设置头信息
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }
}
