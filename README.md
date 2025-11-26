彩虹易支付usdt稳定币收款插件# WorldPay TRX钱包插件 商业版可以支持2000个地址以上 普通免费版本固定几个地址就可以了 支持7大主网
只做了简单的插件开发 需要更好的插件自己写代码看官方文档就行


插件目录增加文件worldpay文件上传worldpay_plugin.php
test_webhook.php放根目录即可 然后配置到账通知


## 功能说明
- 集成WorldPay Web3钱包系统
- 支持TRX网络USDT收款
- 自动创建TRX收款地址
- 实时监控到账并自动完成订单

## 配置步骤

### 1. 添加支付类型
在管理后台 → 支付方式管理 → 添加支付方式：
- 调用值：`usdt`
- 显示名称：`USDT-TRC20`
- 支持设备：`电脑+手机`
- 状态：`启用`

### 2. 配置支付通道
在管理后台 → 支付通道管理 → 添加通道：
- 支付方式：选择刚创建的`USDT-TRC20`
- 支付插件：选择`WorldPay TRX钱包`
- App ID：填入WorldPay分配的应用ID
- Secret Key：填入WorldPay分配的密钥
- API地址：`https://admin.worldpay.im/client-api`（默认）

### 3. 配置Webhook
在WorldPay管理后台配置Webhook URL：
```
https://你的域名/test_webhook.php
```

**重要说明**：
- 使用根目录的专用Webhook处理文件
- 系统会根据Webhook中的`walletAddress`字段自动匹配对应的订单
- 确保URL能被WorldPay服务器访问（不能是内网地址）
- 建议使用HTTPS协议提高安全性
- 所有处理日志记录在 `worldpay_log.txt` 文件中

**WorldPay数据格式**：
- 地址字段：`walletAddress`
- 金额字段：`amountReadable`
- 币种字段：`tokenType`
- 网络字段：`networkType`
- 交易哈希：`transactionHash`
- 状态字段：`status` (必须为 "confirmed")

## 使用流程
1. 用户选择USDT-TRC20支付
2. 系统自动调用WorldPay API创建TRX地址
3. 用户向该地址转账USDT
4. WorldPay监控到转账后发送Webhook通知
5. 系统自动完成订单并通知商户

## 注意事项
- 确保WorldPay账户有足够的地址配额
- TRX网络转账需要消耗TRX作为手续费
- 建议设置最小支付金额（如10 USDT）
- Webhook URL需要能被外网访问

## 联系支持
- WorldPay官网：https://www.worldpay.im/

