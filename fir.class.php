<?php

/**
* FIR Algorithm Core Class
* @author   Jokin
*/

// +----------------------------------------------------------------------
// | Marked -1 as empty checker
// | Marked  0 as white stone
// | Marked  1 as black stone
// +----------------------------------------------------------------------
// | 0_普通字符串消息
// | 1_落子人代号_落子行_落子列
// | 2_@相关指令
// | e_获胜方代号
// +----------------------------------------------------------------------

class fir {

  // 类库版本
  const VERSION = '0.0.2-alpha';

  // 数据结构版本
  const DATA_VERSION = 'v2';

  // 棋盘数组存储函数
  public static $chessboard = array();

  // 自动保存函数存储
  public static $default_filename = 'chessboard.json';
  public static $filename = null;

  /**
   * 初始化函数
   * @param  int     row
   * @param  int     col
   * @param  string  timezone
   * @return array
   */
  public static function init(int $row = 19, int $col = 19) : array {
    for ($i=1; $i <= $row; $i++) {
      for ($j=1; $j <= $col; $j++) {
        self::$chessboard['chessboard'][$i][$j] = -1;
      }
    }
    // 写入初始化信息
    self::$chessboard['info']['version'] = self::VERSION;
    self::$chessboard['info']['data_version'] = self::DATA_VERSION;
    self::$chessboard['info']['next'] = 1;
    self::$chessboard['info']['logs'][] = '0_FIR Class for php created by Jokin';
    self::$chessboard['info']['logs'][] = '0_chessboard initialized at ' . date('Y-m-d H:i:s');
    // 棋盘签名
    self::sign_chessboard(self::$chessboard);
    return self::$chessboard;
  }

  /**
   * 读取棋盘
   * @param  string filename
   * @param  bool   verify
   * @return bool
   */
  public static function resume(string $filename = null, bool $verify = true) : bool {
    // 获取与修正filename
    if ($filename === null) {
      if (self::$filename === null) {
        $filename = self::$default_filename;
      } else {
        $filename = self::$filename;
      }
    } else {
      self::$filename = $filename;  // 提交全局
    }
    if (!is_readable('./' . $filename)) {
      die('文件读取失败');
      return false; // 不可读取
    }
    $chessboard = file_get_contents($filename);
    if (!$chessboard = json_decode($chessboard, true)) {
      die('文件数据不规范');
      return false; // 棋盘非json数据
    }
    // 验证版本
    if (!isset($chessboard['info']['data_version'])) {
      // 0.0.1-alpha 版本
      echo '数据文件版本(v1)过低，建议使用 0.0.1-alpha 版本的类库进行操作或升级数据文件以避免不必要的错误。';
      // die('数据文件版本(v1)过低，请使用 0.0.1-alpha 版本的类库进行操作。');
    }
    // 检查合法性
    if ($verify === true) {
      if (!self::check_chessboard($chessboard)) {
        die('签名验证失败');
        return false;
      }
    }
    self::$chessboard = $chessboard;
    return true;
  }

  /**
   * 存储函数
   * @param  string   filename
   * @return bool
   */
  public static function save(string $filename = null, $data = null) : bool {
    // 获取与修正filename
    if ($filename === null) {
      if (self::$filename === null) {
        $filename = self::$default_filename;
      } else {
        $filename = self::$filename;
      }
    } else {
      self::$filename = $filename;  // 提交全局
    }
    // 签名
    self::sign_chessboard(self::$chessboard);
    if (is_writable("./")) {
      // 获取棋盘数据
      if ($data === null) {
        $data = json_encode(self::$chessboard);
      }
      // 保存
      if (file_put_contents("./{$filename}", $data)) {
        return true;
      } else {
        return false;
      }
    } else {
      return false;
      die('目录没有写权限');
    }
  }

  /**
   * 棋盘签名
   * @param  array  chessboard
   * @return void
   */
  public static function sign_chessboard(array &$chessboard) : void {
    $copy = $chessboard;
    if (isset($copy['verification'])) {
      unset($copy['verification']);
    }
    $chessboard['verification'] = md5(json_encode($copy));
  }

  /**
   * 检查棋盘签名
   * @param  void
   * @return bool
   */
  public static function check_chessboard(array $chessboard) : bool {
    $copy = $chessboard;
    if (isset($copy['verification'])) {
      unset($copy['verification']);
    }
    $signature = md5(json_encode($copy));
    return $signature === $chessboard['verification'];
  }

  /**
   * 落子
   * @param  int   who
   * @param  array position
   * @param  bool  save
   * @return int
   */
  public static function place(int $who, array $position, bool $save = true) : int {
    if (self::$chessboard['info']['next'] === -1) {
      return true;
    }
    // 是否为其落子轮
    if (self::$chessboard['info']['next'] !== $who) {
      return -2; // 非其落子轮
    }
    // 位置合法性
    if (!isset(self::$chessboard['chessboard'][$position['row']][$position['col']])) {
      return -2; // 超范围落子
    }
    if (self::$chessboard['chessboard'][$position['row']][$position['col']] !== -1) {
      return -2; // 非空位
    }
    // 落子
    self::$chessboard['chessboard'][$position['row']][$position['col']] = $who;
    // 记录
    self::$chessboard['info']['logs'][] = "1_{$who}_{$position['row']}_{$position['col']}";
    // 换边
    self::$chessboard['info']['next'] = self::$chessboard['info']['next'] === 0 ? 1 : 0;
    // 保存
    if ($save === true) self::save();
    // 计算结果
    $res = self::getResult($position, $save);
    // 返回结果
    return $res;
  }

  /**
   * 自动落子
   * @param  array position
   * @param  bool  save
   * @return int
   */
  public static function autoPlace(array $position, bool $save = true) : int {
    // 自动获取落子方
    $who = self::$chessboard['info']['next'];
    return self::place($who, $position, $save);
  }

  /**
   * 计算落子结果
   * @param  array position
   * @param  bool  save
   * @return bool
   */
  public static function getResult(array $position, bool $save = true) : int {
    // 检查边界
    $max_row = max(array_keys(self::$chessboard['chessboard']));
    $max_col = max(array_keys(self::$chessboard['chessboard'][$max_row]));
    // 获取位置
    $row = $position['row'];
    $col = $position['col'];
    // 获取落子方
    $who = self::$chessboard['chessboard'][$row][$col];
    // 检查横向
    $pointc = 0;
    $pointc_max = 0;
    for ($i=-4; $i <= 4; $i++) {
      $c = $col + $i;
      if ($c <= 0 || $c > $max_col) continue;  // 越界
      if (self::$chessboard['chessboard'][$row][$c] === $who) {
        $pointc ++;
      } else {
        // 断裂
        $pointc_max = $pointc > $pointc_max ? $pointc : $pointc_max;
        if ($pointc < 5) $pointc = 0;
      }
    }
    // 检查纵向
    $pointr = 0;
    $pointr_max = 0;
    for ($i=-4; $i <= 4; $i++) {
      $r = $row + $i;
      if ($r <= 0 || $r > $max_row) continue;  // 越界
      if (self::$chessboard['chessboard'][$r][$col] === $who) {
        $pointr ++;
      } else {
        // 断裂
        $pointr_max = $pointr > $pointr_max ? $pointr : $pointr_max;
        if ($pointr < 5) $pointr = 0;
      }
    }
    // 检查斜向
    $pointdl = 0;
    $pointdl_max = 0;
    $pointdr = 0;
    $pointdr_max = 0;
    // left
    for ($i=-4; $i <= 4; $i++) {
      $r = $row + $i;
      $c = $col - $i;
      if ($r <= 0 || $r > $max_row || $c <= 0 || $c > $max_col) continue;  // 越界
      if (self::$chessboard['chessboard'][$r][$c] === $who) {
        $pointdl ++;
      } else {
        // 断裂
        if ($pointdl < 5) $pointdl = 0;  // 断裂计数清零
      }
      $pointdl_max = $pointdl > $pointdl_max ? $pointdl : $pointdl_max;
    }
    // right
    for ($i=-4; $i <= 4; $i++) {
      $r = $row - $i;
      $c = $col - $i;
      if ($r <= 0 || $r > $max_row || $c <= 0 || $c > $max_col) continue;  // 越界
      if (self::$chessboard['chessboard'][$r][$c] === $who) {
        $pointdr ++;
      } else {
        // 断裂
        $pointdr_max = $pointdr > $pointdr_max ? $pointdr : $pointdr_max;
        if ($pointdr < 5) $pointdr = 0;  // 断裂计数清零
      }
    }
    self::$chessboard['info']['logs'][] = "0_横{$pointc_max}_纵{$pointr_max}_左{$pointdl_max}_右{$pointdr_max}";
    if ($pointr >=5 || $pointc >= 5 || $pointdl >= 5 || $pointdr >= 5) {
      $win = $who;
      self::$chessboard['info']['logs'][] = "e_{$who}";
      self::$chessboard['info']['next'] = -1;  // 游戏结束
      $result = $who;  // 返回获胜方
    } else {
      $result =  -1;  // 继续游戏
    }
    if ($save === true) self::save();
    return $result;
  }

  /**
   * 获取棋盘界
   * @param  void
   * @return array
   */
  public static function getEdge() : array {
    $row = max(array_keys(self::$chessboard['chessboard']));
    $col = max(array_keys(self::$chessboard['chessboard'][$row]));
    return [$row, $col];
  }

  /**
   * 获取棋盘指定位置内容
   * @param  int row
   * @param  int col
   * @return int
   */
  public static function getStatus(int $row, int $col) : int {
    // 错误返回-2
    return isset(self::$chessboard['chessboard'][$row][$col]) ? self::$chessboard['chessboard'][$row][$col] : -2;
  }
}

?>
