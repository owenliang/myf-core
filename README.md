# myf-core

## 介绍

myf means 'my framework'

我一直想自己做个简单的不能再简单的PHP框架，为了少一件心事，所以抽了点时间搞出来。

## 原则

* 框架核心myf-core作为composer library发布
* 框架脚手架myf-app作为composer project发布，依赖myf-core包
* 支持多应用开发，共享公共代码
* 基于namespace的类自动加载
* 没有IOC容器，namespace本身就是单例
* 没有框架基类，不绑架开发者习惯，PHP原汁原味

## 组成

### myf\App

框架入口，解决几个核心问题：

* 加载框架配置文件
* 调试/生产环境 - 错误级别切换
* 全局异常捕获
* 类自动加载
* 解析URI，完成到Controller类的路由

### myf\View

视图层，渲染给定的视图文件

### myf\Http

HTTP客户端，CURL库的封装

### myf\Mysql

MYSQL主从分离客户端，PDO库的封装

### myf\Redis

Redis主从/集群客户端，Phpredis库的封装

### myf\Exception\...

自定义异常类

## myf/app 脚手架

基于脚手架，一键生成项目：

[myf-app](https://github.com/owenliang/myf-app)