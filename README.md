Yii2 + Trinity.TV
=================
Yii2 + Trinity.TV

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
$trinityApi->subscriptionInfo('1132');
$trinityApi->subscription('1132', $trinityApi::SUSPEND);
$trinityApi->autorizeDevice('1132', 'MAC', 'UUID');
$trinityApi->autorizeByCode('1132', 'CODE');
$trinityApi->deleteDevice('1132', 'MAC');
$trinityApi->updateUser('1132', 'Имя', 'Фамилия', 'Отчество', 'Город');
$trinityApi->deviceList('1132');
$trinityApi->subscriberList();
?>
```

