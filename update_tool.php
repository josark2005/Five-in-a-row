<!DOCTYPE html>
<html lang="zh-CN" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>数据文件升级</title>
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
  </head>
  <body>
    <h1>数据文件升级</h1>
    <form action="./update_tool.php" method="post" enctype="multipart/form-data">
      <input type="file" name="datefile" />
      <br />
      <label for="new_version">升级版本：</label>
      <select name="new_version">
        <option value="v2">v2</option>
      </select>
      <br />
      <button type="submit" name="submit">提交</button>
    </form>
  </body>
</html>
<?php

/**
 * FIR Update Library
 * @author   Jokin
 */

updateTool::getFile();

class updateTool {

  const CONVERT_TABLE = array(
    'v1'  => array(
      'v2'
    ),
    'v2'  => array(),
  );

  public static function getFile() : void {
    if (isset($_POST['new_version'], $_FILES['datefile'])) {
      $error = $_FILES['datefile']['error'];
      if (!$error) {
        $name = $_FILES['datefile']['name'];
        $tmp_name = $_FILES['datefile']['tmp_name'];
        $data = json_decode(file_get_contents($tmp_name), true);
        if (!$data) die('数据非JSON格式，无法解析！');
        self::convert(self::getVersion($data), $_POST['new_version'], $data);
      }else{
        die('发生错误：'.$error);
      }
    }
  }

  public static function getVersion(array $chessboard) : string {
    if (!isset($chessboard['info']['data_version'])) {
      return 'v1'; // 0.0.1-alpha 版本
    } else {
      return $chessboard['info']['data_version'];
    }
  }

  public static function convert(string $version, string $new_version, array $chessboard) : void {
    // 检查是否支持
    if (!isset(self::CONVERT_TABLE[$version]) && in_array($new_version, self::CONVERT_TABLE)) {
      die('不支持的转换');
    }
    $fun = $version.'To'.$new_version;
    $data = self::$fun($chessboard);
    $filename = "./{$fun}.json";
    $res = file_put_contents($filename, json_encode($data));
    if (!$res) die('写出文件失败！请检查目录写权限');
    echo '<a href="'.$filename.'" target="_blank">下载</a>';
  }

  private static function v1Tov2(array $chessboard) : array {
    $chessboard['info']['version'] = 'v2';
    $chessboard['info']['data_version'] = '0.0.2-alpha';
    return $chessboard;
  }

}

?>
