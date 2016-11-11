gMenu
=====

замена сниппета Wayfinder для MODx 

####Установка
Скопировать в папку assets/snippets/gmenu/

Создать сниппет с кодом
```PHP
<?php
return require MODX_BASE_PATH.'assets/snippets/gmenu/snippet.gmenu.php';
?>
```

####UPD:
Добавлена возможность использования шаблонов в зависимости от уровня сложенности

```CODE
&innerRowTpl=`innerRowTpl`
&innerRow2Tpl=`innerRowTpl_level_2`
&innerRow3Tpl=`innerRowTpl_level_3`

&innerHereTpl=`innerHereTpl`
&innerHere2Tpl=`innerHereTpl_level_2`
&innerHere3Tpl=`innerHereTpl_level_3`
```
