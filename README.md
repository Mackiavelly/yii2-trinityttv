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
$trinityApi = new mackiavelly\trinitytv\TrinityApi(['partnerID' => '000', 'salt' => '0000000000']);
$trinityApi->create('localId', 'TariffId');
$trinityApi->subscriptionInfo('localId');
$trinityApi->subscription('localId', $trinityApi::SUSPEND);
$trinityApi->autorizeDevice('localId', 'MAC', 'UUID');
$trinityApi->autorizeByCode('localId', 'CODE');
$trinityApi->deleteDevice('localId', 'MAC', 'UUID');
$trinityApi->updateUser('localId', 'Имя', 'Фамилия', 'Отчество', 'Город');
$trinityApi->deviceList('localId');
$trinityApi->subscriberList();
?>
```

