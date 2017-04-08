gMenu
=====

замена сниппета Wayfinder для MODx 

<h3>Установка</h3>
<p>Скопировать в папку assets/snippets/gmenu/</p>

Создать сниппет с кодом
```PHP
<?php
return require MODX_BASE_PATH.'assets/snippets/gmenu/snippet.gmenu.php';
?>
```

<h6>Параметры идеинтичны как и в Wayfinder</h6>
<h5>Общая конфигурация</h5>
<p>
<b>&startId</b> - родительская категория из котороый будут браться документы <br>
<b>&rowIdPrefix</b> - преффикс для аттрибута id <br>
<b>&hideSubMenus</b> - скрывать субменю  (1 / 0) <br>
<b>&hideFirstLevel</b> - скрывать первый уровень меню (1 / 0) <br>
<b>&limit</b> - лимит уровней (использовать level) <br>
<b>&useCache</b> - использовать кэш файл (1 / 0) <br>
<b>&level</b> - количество уровней меню  <br>
<b>&tvList</b> - tv через запятую без пробелов <br>
</p>

<h5>Шаблоны</h5>
<p>
<b>&outerTpl</b> - обёртка<br>
<b>&rowTpl</b> - пункт<br>
<b>&parentRowTpl</b> - родитель<br>
<b>&parentRowHereTpl</b> - текущий активный родитель<br>
<b>&hereTpl</b> - текущий активный пункт<br>
<b>&innerTpl</b> - обёртка второго уровня и далее<br>
<b>&innerRowTpl</b> - пункт второго уровня и далее<br>
<b>&innerHereTpl</b> - активный пункт второго уровня и далее<br>
<b>&activeParentRowTpl</b> - активный родитель<br>
<b>&categoryFoldersTpl</b> - категория (если шаблон blank или в аттрибутах ссылки указано rel="category")<br>
<b>&startItemTpl</b> - первый уровень меню<br>
</p>

<h5>Классы</h5>
<p>
<b>&firstClass</b> - первый класс <br>
<b>&lastClass</b> - последний класс (по умолчанию last)<br>
<b>&hereClass</b> - текущий класс (по умолчанию here)<br>
<b>&parentClass</b> - родительский класс (по умолчанию parent)<br>
<b>&rowClass</b> - класс для каждого пункта <br>
<b>&levelClass</b> - класс для каждого уровня <br>
<b>&outerClass</b> - класс для обёртки меню ( ul class="[+wf.classes+]" [+wf.wrapper+] ) <br>
<b>&innerClass</b> - класс для обёртки второго уровня <br>
</p>
<br>
<h5>Вызов сниппета</h5>

```CODE
[[gmenu?
&startId=`0`
]]
```
<br>

<h3>UPD: 04.2017</h3>
<p>Добавлена возможность использования кэш файла для каждого вызова сниппета</p>

```CODE
&useCache=`1`
```

<h3>UPD: 11.2016</h3>
<p>Добавлена возможность использования шаблонов в зависимости от уровня сложенности</p>
<p>Добавлена возможность использования тв-параметров</p>

```CODE
&innerRowTpl=`innerRowTpl`
&innerRow2Tpl=`innerRowTpl_level_2`
&innerRow3Tpl=`innerRowTpl_level_3`

&innerHereTpl=`innerHereTpl`
&innerHere2Tpl=`innerHereTpl_level_2`
&innerHere3Tpl=`innerHereTpl_level_3`

&tvList=`image,img`
```
