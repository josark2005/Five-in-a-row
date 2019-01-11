<?php

/**
 * Gobang Algorithm
 * @version  1.0.0
 * @author Jokin
 */

// 设置时区
date_default_timezone_set('PRC');

// 载入核心类
include './gobang.class.php';
if (!is_file('./chessboard.json')) {
  $chessbord = gobang::init();
  gobang::save();
} else {
  $chessbord = gobang::resume('chessboard.json');
}
if (!$chessbord) die('载入棋盘失败！请检查棋盘文件是否可读、结构是否正常、签名是否正确。');
if (isset($_GET['action'])) {
  if ($_GET['action'] === 'clear') {
    unlink('./chessboard.json');
    header('Location: index.php');
  }
}
if (isset($_GET['row']) && isset($_GET['col'])) {
  gobang::autoPlace($_GET);
}
// 获取棋盘界
$edge = gobang::getEdge();
?>
<!DOCTYPE html>
<html lang="zh-CN" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Gobang</title>
    <script type="text/javascript">
      function place(row, col) {
        let form;
        form = document.createElement('form');
        form.action = './index.php';
        form.method = 'get';
        form.style.display = 'none';
        let r = document.createElement('input');
        r.name = 'row';
        r.value = row;
        form.appendChild(r);
        let c = document.createElement('input');
        c.name = 'col';
        c.value = col;
        form.appendChild(c);
        document.body.appendChild(form);
        form.submit();
        return form;
      }
    </script>
  </head>
  <body>
    <div style="text-align:center;">
      <h1>Gobang v0.0.1-alpha</h1>
      <p>
        <a href="./index.php?action=clear" target="_self">清空棋盘</a>
      </p>
      <table style="text-align:center;margin:auto;" border="1">
        <?php for ($i=0; $i<=$edge[0]; $i++): ?>
          <tr>
            <td width='25px;'><?php echo $i===0?'':$i; ?></td>
            <?php for($j=1; $j<=$edge[1]; $j++): ?>
              <td width='25px;' <?php if($i!==0): echo "id='{$i}_{$j}'"; endif; ?> onclick="javascript:place(<?php echo $i; ?>, <?php echo $j; ?>);">
                <?php if ($i === 0):?>
                  <?php echo $j; ?>
                <?php else: ?>
                  <!-- <?php echo $i.'_'.$j; ?> -->
                  <?php $status = gobang::getStatus($i, $j); ?>
                  <?php if($status === 0): echo '□'; ?>
                  <?php elseif($status === 1): ?>
                    <?php echo '■'; ?>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
            <?php endfor; ?>
          </tr>
        <?php endfor; ?>
      </table>
      <!-- 信息 -->
      <p>
        <?php $info = array_reverse(gobang::$chessboard['info']['logs']); ?>
        <?php foreach ($info as $key => $value) {
          echo $value.'<br />';
        } ?>
      </p>
    </div>
  </body>
</html>