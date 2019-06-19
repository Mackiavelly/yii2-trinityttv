Yii2 + Trinity.TV
=================
Yii2 + Trinity.TV

Документация

https://docs.google.com/document/d/1P43gLkBuNB9DnPF9I3aGG7HvLCnUCCYjylVec8vcjjg/edit

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist mackiavelly/yii2-trinitytv "*"
```

or add

```
"mackiavelly/yii2-trinitytv": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?php
$trinityApi = new mackiavelly\trinitytv\TrinityApi(['partnerId' => 'partnerId', 'salt' => 'salt']);
$trinityApi->create('localId', 'tariffId');
$trinityApi->subscriptionInfo('localId');
$trinityApi->subscription('localId', $trinityApi::SUSPEND);
$trinityApi->autorizeDevice('localId', 'mac', 'uuid');
$trinityApi->autorizeByCode('localId', 'code');
$trinityApi->deleteDevice('localId', 'mac', 'uuid');
$trinityApi->updateUser('localId', 'Имя', 'Фамилия', 'Отчество', 'Город');
$trinityApi->deviceList('localId');
$trinityApi->subscriberList();
?>
```

