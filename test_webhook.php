<?php
// WorldPay Webhook 测试接收器
// 用于调试WorldPay发送的数据格式

// 记录所有接收到的数据
$logFile = 'webhook_log.txt';

// 测试文件是否能正常工作
if (!isset($_GET['test']) && !$_POST && !file_get_contents('php://input')) {
    echo "<h2>WorldPay Webhook 测试接收器</h2>";
    echo "<p>文件工作正常！</p>";
    echo "<p>当前时间: " . date('Y-m-d H:i:s') . "</p>";
    echo "<p>请在WorldPay后台配置此URL作为Webhook地址</p>";
    echo "<p>日志文件: <a href='webhook_log.txt'>webhook_log.txt</a></p>";
    exit;
}

// 获取当前时间
$timestamp = date('Y-m-d H:i:s');

// 获取所有HTTP头
$headers = getallheaders();

// 获取原始POST数据
$rawInput = file_get_contents('php://input');

// 获取GET和POST参数
$getData = $_GET;
$postData = $_POST;

// 获取请求方法和URL
$method = $_SERVER['REQUEST_METHOD'];
$url = $_SERVER['REQUEST_URI'];

// 构建日志内容
$logContent = "\n" . str_repeat('=', 80) . "\n";
$logContent .= "时间: $timestamp\n";
$logContent .= "方法: $method\n";
$logContent .= "URL: $url\n";
$logContent .= "Headers:\n" . json_encode($headers, JSON_PRETTY_PRINT) . "\n";
$logContent .= "GET参数:\n" . json_encode($getData, JSON_PRETTY_PRINT) . "\n";
$logContent .= "POST参数:\n" . json_encode($postData, JSON_PRETTY_PRINT) . "\n";
$logContent .= "原始输入:\n$rawInput\n";

// 尝试解析JSON
if ($rawInput) {
    $jsonData = json_decode($rawInput, true);
    if ($jsonData) {
        $logContent .= "解析后的JSON:\n" . json_encode($jsonData, JSON_PRETTY_PRINT) . "\n";
    } else {
        $logContent .= "JSON解析失败\n";
    }
}

$logContent .= str_repeat('=', 80) . "\n";

// 写入日志文件
file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);

// 如果是WorldPay的Webhook数据，简化处理
if ($rawInput && $jsonData && isset($jsonData['walletAddress']) && isset($jsonData['status']) && $jsonData['status'] === 'confirmed') {
    // 加载系统（只在需要时加载）
    if (!defined('IN_CRONLITE')) {
        $nosession = true;
        require './includes/common.php';
    }
    
    $address = $jsonData['walletAddress'];
    $amount = $jsonData['amountReadable'];
    $txHash = $jsonData['transactionHash'];
    
    $logContent .= "收到支付回调: 地址={$address}, 金额={$amount}, 交易={$txHash}\n";
    
    // 直接查找最新的待支付订单（不管地址）
    $order_info = $DB->getRow("SELECT * FROM pre_order WHERE status=0 ORDER BY addtime DESC LIMIT 1");
    
    if (!$order_info) {
        $logContent .= "没有找到待支付订单\n";
    } else {
        $logContent .= "找到待支付订单: {$order_info['trade_no']}, 金额: {$order_info['money']}\n";
        
        try {
            // 直接标记订单为已支付
            $updateResult = $DB->exec("UPDATE pre_order SET status=1, endtime=NOW(), api_trade_no=:txhash WHERE trade_no=:trade_no", [
                ':txhash' => $txHash,
                ':trade_no' => $order_info['trade_no']
            ]);
            
            $logContent .= "订单更新结果: " . ($updateResult ? '成功' : '失败') . "\n";
            
            // 更新商户余额
            $rate = floatval($order_info['money']) * (1 - floatval($conf['settle_rate']) / 100);
            $balanceResult = $DB->exec("UPDATE pre_user SET money=money+:money WHERE uid=:uid", [
                ':money' => $rate,
                ':uid' => $order_info['uid']
            ]);
            
            $logContent .= "余额更新结果: " . ($balanceResult ? '成功' : '失败') . "，增加 {$rate} 元\n";
            $logContent .= "订单 {$order_info['trade_no']} 处理成功！\n";
            
        } catch (Exception $e) {
            $logContent .= "处理错误: " . $e->getMessage() . "\n";
        }
    }
    
    // 立即写入处理结果日志
    file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);
}

// 返回成功响应
http_response_code(200);
echo 'OK';
?>
